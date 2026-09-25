<?php

namespace App\Imports;

use App\Models\Empresa;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UsuarioImport implements ToCollection, WithHeadingRow
{
    protected $currentUser;
    protected $modoSQL;
    protected $resultado = [
        'cargados'     => [],
        'actualizados' => [],
        'omitidos'     => [],
        'errores'      => [],
        'resumen'      => [
            'cargados'     => 0,
            'actualizados' => 0,
            'omitidos'     => 0,
            'errores'      => 0,
        ],
    ];

    public function __construct($currentUser, $modoSQL = false)
    {
        $this->currentUser = $currentUser;
        $this->modoSQL = $modoSQL;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $this->procesarFila($row->toArray(), $index + 2);
        }
    }

    public function procesarSQL(string $contenido)
    {
        // Parser robusto de INSERT INTO users
        if (!preg_match_all('/INSERT\s+INTO\s+`?users`?\s*\([^)]+\)\s*VALUES\s*(.+?);/is', $contenido, $matches)) {
            return;
        }

        foreach ($matches[1] as $valuesBlock) {
            if (!preg_match_all('/\(([^)]+)\)/', $valuesBlock, $rowsMatches)) {
                continue;
            }

            foreach ($rowsMatches[1] as $fila) {
                $valores = str_getcsv($fila, ',', "'");
                $valores = array_map(fn($v) => trim($v, " '\""), $valores);

                $row = [
                    'nombre'       => $valores[1] ?? null,
                    'email'        => $valores[2] ?? null,
                    'rol_codigo'   => 'jefe_obra',
                    'empresa_rfc'  => null,
                    'estatus'      => 'activo',
                ];

                $this->procesarFila($row, count($this->resultado['cargados']) + count($this->resultado['errores']) + 1);
            }
        }
    }

    protected function procesarFila(array $row, int $numeroFila)
    {
        $nombre     = $this->normalizar($row['nombre'] ?? $row['NOMBRE'] ?? null);
        $email      = strtolower(trim($row['email'] ?? $row['EMAIL'] ?? ''));
        $rolCodigo  = strtolower(trim($row['rol_codigo'] ?? $row['ROL_CODIGO'] ?? 'jefe_obra'));
        $empresaRfc = $this->normalizar($row['empresa_rfc'] ?? $row['EMPRESA_RFC'] ?? null);
        $estatus    = strtolower(trim($row['estatus'] ?? $row['ESTATUS'] ?? 'activo'));

        if (empty($nombre) || empty($email)) {
            $this->resultado['errores'][] = [
                'fila'   => $numeroFila,
                'campo'  => 'nombre/email',
                'motivo' => 'Nombre y email son obligatorios',
            ];
            $this->resultado['resumen']['errores']++;
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->resultado['errores'][] = [
                'fila'   => $numeroFila,
                'campo'  => 'email',
                'motivo' => "Email inválido: {$email}",
            ];
            $this->resultado['resumen']['errores']++;
            return;
        }

        $rol = Rol::where('codigo', $rolCodigo)->where('tipo', 'sistema')->first();
        if (!$rol) {
            $this->resultado['errores'][] = [
                'fila'   => $numeroFila,
                'campo'  => 'rol_codigo',
                'motivo' => "Rol no encontrado: {$rolCodigo}",
            ];
            $this->resultado['resumen']['errores']++;
            return;
        }

        if ($this->currentUser->esContratista() && $rolCodigo === 'admin') {
            $this->resultado['errores'][] = [
                'fila'   => $numeroFila,
                'campo'  => 'rol_codigo',
                'motivo' => 'No puedes crear usuarios Admin',
            ];
            $this->resultado['resumen']['errores']++;
            return;
        }

        // ============================================
        // ASIGNACIÓN AUTOMÁTICA DE EMPRESA
        // ============================================
        // Regla:
        //   - Contratista: SIEMPRE su propia empresa.
        //   - Admin / otros: primera empresa ACTIVA del sistema.
        // No se lee RFC del archivo porque ese campo ya no existe.
        // ============================================
        $empresaId = null;

        if ($this->currentUser->esContratista()) {
            $empresaId = $this->currentUser->empresa_id;
        } else {
            // Admin u otros roles: primera empresa activa
            $empresaId = Empresa::where('estatus', 'activo')
                ->orderBy('id')
                ->value('id');
        }

        // Si no hay ninguna empresa activa en el sistema, sí es un error real
        if (!$empresaId) {
            $this->resultado['errores'][] = [
                'fila'   => $numeroFila,
                'campo'  => 'empresa',
                'motivo' => 'No hay empresas activas registradas en el sistema. Registra al menos una empresa antes de importar usuarios.',
            ];
            $this->resultado['resumen']['errores']++;
            return;
        }

        $existente = User::where('email', $email)->first();

        if ($existente) {
            $cambios = [];
            if ($existente->nombre !== $nombre) $cambios['nombre'] = ['antes' => $existente->nombre, 'despues' => $nombre];
            if ($existente->rol_id !== $rol->id) $cambios['rol_id'] = ['antes' => $existente->rol_id, 'despues' => $rol->id];
            if ($existente->empresa_id !== $empresaId) $cambios['empresa_id'] = ['antes' => $existente->empresa_id, 'despues' => $empresaId];
            if ($existente->estatus !== $estatus) $cambios['estatus'] = ['antes' => $existente->estatus, 'despues' => $estatus];

            if (empty($cambios)) {
                $this->resultado['omitidos'][] = [
                    'fila'   => $numeroFila,
                    'nombre' => $nombre,
                    'email'  => $email,
                    'motivo' => 'Sin cambios',
                ];
                $this->resultado['resumen']['omitidos']++;
                return;
            }

            $soloCambios = [];
            foreach ($cambios as $campo => $diff) {
                $soloCambios[$campo] = $diff['despues'];
            }
            $existente->update($soloCambios);

            $this->resultado['actualizados'][] = [
                'fila'    => $numeroFila,
                'nombre'  => $nombre,
                'email'   => $email,
                'cambios' => $cambios,
            ];
            $this->resultado['resumen']['actualizados']++;
            return;
        }

        try {
            $passwordTemporal = Str::random(10) . rand(10, 99);

            $nuevo = User::create([
                'nombre'     => $nombre,
                'email'      => $email,
                'password'   => Hash::make($passwordTemporal),
                'rol_id'     => $rol->id,
                'empresa_id' => $empresaId,
                'estatus'    => $estatus,
            ]);

            $this->resultado['cargados'][] = [
                'fila'   => $numeroFila,
                'id'     => $nuevo->id,
                'nombre' => $nombre,
                'email'  => $email,
            ];
            $this->resultado['resumen']['cargados']++;
        } catch (\Exception $e) {
            $this->resultado['errores'][] = [
                'fila'   => $numeroFila,
                'campo'  => 'general',
                'motivo' => $e->getMessage(),
            ];
            $this->resultado['resumen']['errores']++;
        }
    }

    protected function normalizar($valor): ?string
    {
        if (empty($valor)) return null;
        return strtoupper(trim(preg_replace('/\s+/', ' ', $valor)));
    }

    public function getResultado(): array
    {
        return $this->resultado;
    }
}
