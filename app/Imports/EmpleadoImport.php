<?php

namespace App\Imports;

use App\Models\Empleado;
use App\Models\Empresa;
use App\Models\Obra;
use App\Models\Rol;
use App\Models\User;
use App\Models\BitacoraAccion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EmpleadoImport
{
    public array $cargados = [];
    public array $actualizados = [];
    public array $omitidos = [];
    public array $noCargados = [];
    public array $errores = [];
    public int $totalProcesados = 0;

    protected int $registradoPorId;
    protected string $registradoPorCargo;
    protected bool $modoSQL = false;
    protected ?int $empresaDefaultId = null;
    protected ?int $obraDefaultId = null;

    public function __construct(bool $modoSQL = false)
    {
        $user = Auth::user();
        $this->registradoPorId = $user?->id ?? 1;
        $this->registradoPorCargo = $user?->rol?->nombre ?? 'Administrador';
        $this->modoSQL = $modoSQL;

        // Determinar empresa por defecto según el usuario
        if ($modoSQL) {
            if ($user && $user->esContratista() && $user->empresa_id) {
                // Contratista: usa su empresa
                $this->empresaDefaultId = $user->empresa_id;
            } else {
                // Admin: usa la primera empresa externa (no matriz)
                $empresaExterna = \App\Models\Empresa::where('tipo', 'externa')
                    ->where('estatus', 'activo')
                    ->orderBy('id')
                    ->first();
                $this->empresaDefaultId = $empresaExterna?->id;
            }

            // Determinar obra por defecto
            if ($this->empresaDefaultId) {
                $obra = \App\Models\Obra::where('empresa_id', $this->empresaDefaultId)
                    ->where('estatus', 'activa')
                    ->orderBy('id')
                    ->first();
                $this->obraDefaultId = $obra?->id;
            }
        }
    }

    /**
     * Procesa un array de filas (ya parseadas desde CSV/Excel)
     */
    public function procesarFilas(array $filas, int $filaInicial = 2): void
    {
        foreach ($filas as $index => $fila) {
            $this->totalProcesados++;
            $numFila = $filaInicial + $index;

            try {
                $this->procesarFila($fila, $numFila);
            } catch (\Exception $e) {
                $this->errores[] = [
                    'fila'   => $numFila,
                    'campo'  => 'general',
                    'motivo' => $e->getMessage(),
                ];
            }
        }
    }

    /**
     * Procesa una sola fila
     */
    protected function procesarFila(array $fila, int $numFila): void
    {
        // 1. Normalizar: MAYÚSCULAS + trim
        $data = $this->normalizar($fila);

        // 2. Aplicar valores por defecto en modo SQL
        if ($this->modoSQL) {
            // Si no viene empresa_rfc, usar la empresa por defecto
            if (empty($data['empresa_rfc']) && $this->empresaDefaultId) {
                $empresaDefault = \App\Models\Empresa::find($this->empresaDefaultId);
                $data['empresa_rfc'] = $empresaDefault?->rfc;
            }

            // Si no viene obra_codigo, usar la obra por defecto
            if (empty($data['obra_codigo']) && $this->obraDefaultId) {
                $obraDefault = \App\Models\Obra::find($this->obraDefaultId);
                $data['obra_codigo'] = $obraDefault?->codigo;
            }

            // Si no viene estatus, default activo
            if (empty($data['estatus'])) {
                $data['estatus'] = 'activo';
            }

            // Si no viene puesto_cargo pero viene rol_id, resolver el nombre
            if (empty($data['puesto_cargo']) && !empty($data['rol_id'])) {
                $rol = \App\Models\Rol::find($data['rol_id']);
                $data['puesto_cargo'] = $rol?->nombre;
            }
        }

        // 3. Validar
        $validator = Validator::make($data, [
            'nombre'       => 'required|string|max:100',
            'apellido'     => 'required|string|max:100',
            'curp_dni'     => 'nullable|string|max:50',
            'puesto_cargo' => 'required|string|max:100',
            'empresa_rfc'  => 'required|string|max:50',
            'obra_codigo'  => 'nullable|string|max:50',
            'estatus'      => 'nullable|in:ACTIVO,INACTIVO,activo,inactivo',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $mensaje) {
                $this->errores[] = [
                    'fila'   => $numFila,
                    'campo'  => $this->extraerCampoDeError($mensaje),
                    'motivo' => $mensaje,
                ];
            }
            return;
        }

        // 4. Buscar empresa por RFC
        $empresa = Empresa::where('rfc', $data['empresa_rfc'])->first();
        if (!$empresa) {
            $this->noCargados[] = [
                'fila'     => $numFila,
                'nombre'   => $data['nombre'],
                'apellido' => $data['apellido'],
                'campo'    => 'empresa_rfc',
                'motivo'   => "Empresa no encontrada con RFC: {$data['empresa_rfc']}",
            ];
            return;
        }

        // 5. Buscar obra por código (opcional)
        $obraId = null;
        if (!empty($data['obra_codigo'])) {
            $obra = Obra::where('codigo', $data['obra_codigo'])
                ->where('empresa_id', $empresa->id)
                ->first();
            if (!$obra) {
                $this->noCargados[] = [
                    'fila'     => $numFila,
                    'nombre'   => $data['nombre'],
                    'apellido' => $data['apellido'],
                    'campo'    => 'obra_codigo',
                    'motivo'   => "Obra no encontrada con código: {$data['obra_codigo']}",
                ];
                return;
            }
            $obraId = $obra->id;
        }

        // 6. Buscar rol operativo por puesto_cargo
        $rol = Rol::where('tipo', 'operativo')
            ->whereRaw('UPPER(nombre) = ?', [strtoupper($data['puesto_cargo'])])
            ->first();

        $rolId = $rol?->id;

        // 7. Buscar empleado existente
        $existente = Empleado::where('empresa_id', $empresa->id)
            ->where('nombre', $data['nombre'])
            ->where('apellido', $data['apellido'])
            ->first();

        $estatus = strtolower($data['estatus'] ?? 'activo');
        if (!in_array($estatus, ['activo', 'inactivo'])) $estatus = 'activo';

        $datosNuevos = [
            'empresa_id'                 => $empresa->id,
            'obra_id'                    => $obraId,
            'rol_id'                     => $rolId,
            'curp_dni'                   => $data['curp_dni'] ?? null,
            'nombre'                     => $data['nombre'],
            'apellido'                   => $data['apellido'],
            'puesto_cargo'               => $data['puesto_cargo'],
            'estatus'                    => $estatus,
            'registrado_por_usuario_id'  => $this->registradoPorId,
            'registrado_por_cargo'       => $this->registradoPorCargo,
            'es_titular_externo'         => true,
        ];

        // 8. Si NO existe → INSERT
        if (!$existente) {
            $empleado = Empleado::create($datosNuevos);

            $this->cargados[] = [
                'fila'     => $numFila,
                'id'       => $empleado->id,
                'nombre'   => $data['nombre'],
                'apellido' => $data['apellido'],
            ];

            BitacoraAccion::create([
                'usuario_id'   => $this->registradoPorId,
                'accion'       => 'empleado.importar',
                'descripcion'  => "Empleado importado: {$empleado->nombre_completo}",
                'direccion_ip' => request()->ip(),
                'navegador'    => request()->userAgent(),
                'created_at'   => now(),
            ]);
            return;
        }

        // 9. Si existe → comparar campos
        $cambios = [];
        $camposComparables = [
            'obra_id',
            'rol_id',
            'curp_dni',
            'puesto_cargo',
            'estatus',
        ];

        foreach ($camposComparables as $campo) {
            $valorActual = $existente->{$campo};
            $valorNuevo = $datosNuevos[$campo];

            // Normalizar null vs ''
            if ($valorActual === null && ($valorNuevo === null || $valorNuevo === '')) continue;
            if ($valorNuevo === null && $valorActual === '') continue;

            // Comparar con strings en mayúsculas
            if (is_string($valorActual) && is_string($valorNuevo)) {
                if (strtoupper(trim($valorActual)) !== strtoupper(trim($valorNuevo))) {
                    $cambios[$campo] = [
                        'antes'   => $valorActual,
                        'despues' => $valorNuevo,
                    ];
                }
            } elseif ($valorActual != $valorNuevo) {
                $cambios[$campo] = [
                    'antes'   => $valorActual,
                    'despues' => $valorNuevo,
                ];
            }
        }

        // Si NO hay cambios → OMITIR
        if (empty($cambios)) {
            $this->omitidos[] = [
                'fila'     => $numFila,
                'id'       => $existente->id,
                'nombre'   => $data['nombre'],
                'apellido' => $data['apellido'],
                'motivo'   => 'Sin cambios',
            ];
            return;
        }

        // Si hay cambios → UPDATE solo lo que cambió
        $soloCambios = [];
        foreach ($cambios as $campo => $diff) {
            $soloCambios[$campo] = $diff['despues'];
        }

        $existente->update($soloCambios);

        $this->actualizados[] = [
            'fila'     => $numFila,
            'id'       => $existente->id,
            'nombre'   => $data['nombre'],
            'apellido' => $data['apellido'],
            'cambios'  => $cambios,
        ];

        BitacoraAccion::create([
            'usuario_id'   => $this->registradoPorId,
            'accion'       => 'empleado.importar.update',
            'descripcion'  => "Empleado actualizado (importación): {$existente->nombre_completo}",
            'direccion_ip' => request()->ip(),
            'navegador'    => request()->userAgent(),
            'created_at'   => now(),
        ]);
    }

    /**
     * Normaliza todos los valores: MAYÚSCULAS + trim
     */
    protected function normalizar(array $fila): array
    {
        $data = [];

        foreach ($fila as $clave => $valor) {
            $claveLower = strtolower(trim((string) $clave));

            // Ignorar columnas vacías o índices numéricos
            if ($claveLower === '' || is_numeric($claveLower)) continue;

            if (is_string($valor)) {
                $valor = trim($valor);
                // Eliminar caracteres raros pero preservar acentos, ñ, etc.
                $valor = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $valor);
                $valor = strtoupper($valor);
                // Convertir comillas tipográficas a rectas
                // Convertir comillas tipográficas a rectas (usando códigos Unicode)
                $valor = str_replace(
                    ["\u{201C}", "\u{201D}", "\u{2018}", "\u{2019}"],
                    ["\"", "\"", "'", "'"],
                    $valor
                );
                // Eliminar posibles intentos de inyección SQL
                $valor = preg_replace('/--|\/\*|\*\/|;|\\\\/i', '', $valor);
                $valor = trim($valor);
            }

            $data[$claveLower] = ($valor === '') ? null : $valor;
        }

        // Mapear alias comunes
        $alias = [
            'nombres'      => 'nombre',
            'apellidos'    => 'apellido',
            'curp'         => 'curp_dni',
            'dni'          => 'curp_dni',
            'puesto'       => 'puesto_cargo',
            'cargo'        => 'puesto_cargo',
            'rfc'          => 'empresa_rfc',
            'empresa'      => 'empresa_rfc',
            'obra'         => 'obra_codigo',
            'codigo_obra'  => 'obra_codigo',
        ];

        foreach ($alias as $origen => $destino) {
            if (isset($data[$origen]) && !isset($data[$destino])) {
                $data[$destino] = $data[$origen];
                unset($data[$origen]);
            }
        }

        return $data;
    }

    /**
     * Extrae el campo desde un mensaje de error
     */
    protected function extraerCampoDeError(string $mensaje): string
    {
        $campos = ['nombre', 'apellido', 'curp_dni', 'puesto_cargo', 'empresa_rfc', 'obra_codigo', 'estatus'];

        foreach ($campos as $campo) {
            if (stripos($mensaje, $campo) !== false || stripos($mensaje, str_replace('_', ' ', $campo)) !== false) {
                return $campo;
            }
        }

        return 'general';
    }

    /**
     * Resumen final
     */
    public function resumen(): array
    {
        return [
            'total_procesados' => $this->totalProcesados,
            'cargados'         => count($this->cargados),
            'actualizados'     => count($this->actualizados),
            'omitidos'         => count($this->omitidos),
            'no_cargados'      => count($this->noCargados),
            'errores'          => count($this->errores),
        ];
    }
}
