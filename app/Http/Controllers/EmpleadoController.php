<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\Empresa;
use App\Models\Obra;
use App\Models\Rol;
use App\Models\BitacoraAccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

use App\Imports\EmpleadoImport;
use App\Exports\EmpleadoTemplateExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EmpleadoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Vista principal del módulo de empleados.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $empresasQuery = Empresa::query();
        $obrasQuery = Obra::query();

        if ($user->esContratista()) {
            $empresasQuery->where('id', $user->empresa_id);
            $obrasQuery->where('empresa_id', $user->empresa_id);
        }

        $empresas = $empresasQuery->where('estatus', 'activo')->orderBy('nombre')->get();
        $obras = $obrasQuery->where('estatus', 'activa')->orderBy('nombre')->get();
        $rolesOperativos = Rol::operativo()->orderBy('nombre')->get();

        // Consulta de empleados con filtros
        $query = Empleado::query()->with(['empresa', 'obra', 'rol']);

        if ($user->esContratista()) {
            $query->where('empresa_id', $user->empresa_id);
        } elseif ($request->filled('empresa_id')) {
            $query->where('empresa_id', $request->empresa_id);
        }

        if ($request->filled('obra_id')) {
            $query->where('obra_id', $request->obra_id);
        }

        if ($request->filled('rol_id')) {
            $query->where('rol_id', $request->rol_id);
        }

        if ($request->filled('estatus')) {
            $query->where('estatus', $request->estatus);
        }

        if ($request->filled('busqueda')) {
            $b = $request->busqueda;
            $query->where(function ($q) use ($b) {
                $q->where('nombre', 'LIKE', "%{$b}%")
                    ->orWhere('apellido', 'LIKE', "%{$b}%")
                    ->orWhere('curp_dni', 'LIKE', "%{$b}%");
            });
        }

        $empleados = $query->orderBy('created_at', 'desc')->paginate(12)->withQueryString();

        return view('empleados.index', compact(
            'empresas',
            'obras',
            'rolesOperativos',
            'empleados'
        ));
    }

    /**
     * Guardar nuevo empleado.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'nombre'       => 'required|string|max:100',
            'apellido'     => 'required|string|max:100',
            'curp_dni'     => 'nullable|string|max:50',
            'empresa_id'   => 'required|exists:empresas,id',
            'obra_id'      => 'nullable|exists:obras,id',
            'rol_id'       => 'required|exists:roles,id',
            'puesto_cargo' => 'required|string|max:100',
            'foto'         => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        if ($user->esContratista() && $data['empresa_id'] != $user->empresa_id) {
            return response()->json(['error' => 'No puedes crear empleados en otra empresa'], 403);
        }

        $fotoPath = null;
        if ($request->hasFile('foto')) {
            $fotoPath = $request->file('foto')->store('empleados', 'public');
        }

        $data['foto'] = $fotoPath;
        $data['estatus'] = 'activo';
        $data['registrado_por_usuario_id'] = $user->id;
        $data['registrado_por_cargo'] = $user->rol?->nombre ?? 'Usuario';
        $data['es_titular_externo'] = true;

        $empleado = Empleado::create($data);

        BitacoraAccion::create([
            'usuario_id'   => $user->id,
            'accion'       => 'empleado.crear',
            'descripcion'  => "Empleado creado: {$empleado->nombre_completo} ({$empleado->puesto_cargo})",
            'direccion_ip' => $request->ip(),
            'navegador'    => $request->userAgent(),
            'created_at'   => now(),
        ]);

        return response()->json([
            'success'  => true,
            'mensaje'  => 'Empleado registrado correctamente',
            'empleado' => $empleado->load(['empresa', 'obra', 'rol']),
        ], 201);
    }

    /**
     * Mostrar un empleado (para editar).
     */
    public function mostrar(Empleado $empleado)
    {
        $user = Auth::user();

        if ($user->esContratista() && $empleado->empresa_id != $user->empresa_id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        return response()->json($empleado->load(['empresa', 'obra', 'rol']));
    }

    /**
     * Actualizar empleado.
     */
    public function update(Request $request, Empleado $empleado)
    {
        $user = $request->user();

        if ($user->esContratista() && $empleado->empresa_id != $user->empresa_id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $data = $request->validate([
            'nombre'       => 'required|string|max:100',
            'apellido'     => 'required|string|max:100',
            'curp_dni'     => 'nullable|string|max:50',
            'empresa_id'   => 'required|exists:empresas,id',
            'obra_id'      => 'nullable|exists:obras,id',
            'rol_id'       => 'required|exists:roles,id',
            'puesto_cargo' => 'required|string|max:100',
            'estatus'      => 'required|in:activo,inactivo',
            'foto'         => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        if ($user->esContratista() && $data['empresa_id'] != $user->empresa_id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        if ($request->hasFile('foto')) {
            if ($empleado->foto && Storage::disk('public')->exists($empleado->foto)) {
                Storage::disk('public')->delete($empleado->foto);
            }
            $data['foto'] = $request->file('foto')->store('empleados', 'public');
        }

        $empleado->update($data);

        BitacoraAccion::create([
            'usuario_id'   => $user->id,
            'accion'       => 'empleado.editar',
            'descripcion'  => "Empleado actualizado: {$empleado->nombre_completo}",
            'direccion_ip' => $request->ip(),
            'navegador'    => $request->userAgent(),
            'created_at'   => now(),
        ]);

        return response()->json([
            'success'  => true,
            'mensaje'  => 'Empleado actualizado correctamente',
            'empleado' => $empleado->fresh(['empresa', 'obra', 'rol']),
        ]);
    }

    /**
     * Desactivar empleado (soft delete).
     */
    public function destroy(Request $request, Empleado $empleado)
    {
        $user = $request->user();

        if ($user->esContratista() && $empleado->empresa_id != $user->empresa_id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $empleado->update(['estatus' => 'inactivo']);

        BitacoraAccion::create([
            'usuario_id'   => $user->id,
            'accion'       => 'empleado.desactivar',
            'descripcion'  => "Empleado desactivado: {$empleado->nombre_completo}",
            'direccion_ip' => $request->ip(),
            'navegador'    => $request->userAgent(),
            'created_at'   => now(),
        ]);

        return response()->json([
            'success' => true,
            'mensaje' => 'Empleado desactivado correctamente',
        ]);
    }

    /**
     * Devuelve el HTML de la card de un empleado (para actualización en vivo).
     */
    public function card(Empleado $empleado)
    {
        $user = Auth::user();

        if ($user->esContratista() && $empleado->empresa_id != $user->empresa_id) {
            return response('No autorizado', 403);
        }

        $empleado->load(['empresa', 'obra', 'rol']);

        return view('empleados._card', ['empleado' => $empleado]);
    }
    /**
     * Descargar plantilla CSV limpia
     */
    /**
     * Descargar plantilla CSV limpia
     */
    public function descargarPlantilla()
    {
        $headers = [
            'NOMBRE',
            'APELLIDO',
            'CURP_DNI',
            'PUESTO_CARGO',
            'EMPRESA_RFC',
            'OBRA_CODIGO',
            'ESTATUS',
        ];

        $ejemplo1 = ['JOSE JUAN', 'BERNAL GARCIA', '', 'OFICIAL CARPINTERO', 'CAH240101BBB', 'LC-08', 'ACTIVO'];
        $ejemplo2 = ['ANGEL ALEXIS', 'MELCHOR ARENAS', '', 'OFICIAL ALBAÑIL', 'CAH240101BBB', 'LC-08', 'ACTIVO'];

        $filename = 'plantilla_empleados_' . date('Ymd_His') . '.csv';

        // Contenido del CSV en un string
        $contenido = "\xEF\xBB\xBF"; // BOM UTF-8
        $contenido .= implode(',', $headers) . "\n";
        $contenido .= implode(',', $ejemplo1) . "\n";
        $contenido .= implode(',', $ejemplo2) . "\n";

        return response($contenido, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length'      => strlen($contenido),
            'Cache-Control'       => 'no-cache, must-revalidate',
            'Pragma'              => 'no-cache',
            'Expires'             => '0',
        ]);
    }

    /**
     * Descargar plantilla Excel
     */
    /**
     * Descargar plantilla Excel
     */
    public function descargarPlantillaExcel()
    {
        return Excel::download(
            new EmpleadoTemplateExport(),
            'plantilla_empleados_' . date('Ymd_His') . '.xlsx',
            \Maatwebsite\Excel\Excel::XLSX,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]
        );
    }

    /**
     * Importar archivo CSV o Excel
     */
    public function importar(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240', // 10MB
        ]);

        $archivo = $request->file('archivo');
        $extension = strtolower($archivo->getClientOriginalExtension());

        DB::beginTransaction();
        try {
            $import = new EmpleadoImport();

            if (in_array($extension, ['csv', 'txt'])) {
                // Leer CSV manualmente
                $filas = $this->leerCSV($archivo->getRealPath());
                $import->procesarFilas($filas, 2); // Fila 2 en adelante
            } else {
                // Excel con maatwebsite/excel
                $data = Excel::toArray(new \stdClass(), $archivo);
                $filas = $data[0] ?? [];

                // Primera fila es encabezado, la quitamos
                if (count($filas) > 0) {
                    $encabezados = array_shift($filas);
                    $filas = array_map(function ($fila) use ($encabezados) {
                        $resultado = [];
                        foreach ($encabezados as $i => $enc) {
                            $resultado[$enc] = $fila[$i] ?? null;
                        }
                        return $resultado;
                    }, $filas);
                }

                $import->procesarFilas($filas, 2);
            }

            DB::commit();

            return response()->json([
                'success'  => true,
                'mensaje'  => 'Importación completada',
                'resumen'  => $import->resumen(),
                'cargados' => $import->cargados,
                'actualizados' => $import->actualizados,
                'omitidos' => $import->omitidos,
                'no_cargados' => $import->noCargados,
                'errores'  => $import->errores,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error'   => 'Error al procesar la importación: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Importar desde archivo .sql
     * Acepta dumps completos (phpMyAdmin, mysqldump, etc.) pero SOLO procesa
     * los bloques INSERT INTO `empleados`. Todo lo demás se IGNORA.
     */
    public function importarSQL(Request $request)
    {
        $request->validate([
            'archivo_sql' => 'required|file|max:5120', // 5MB
        ]);

        $archivo = $request->file('archivo_sql');
        $sql = file_get_contents($archivo->getRealPath());

        // ============================================
        // 1. LIMPIAR COMENTARIOS SQL
        // ============================================
        $sqlLimpio = preg_replace('/--.*$/m', '', $sql);              // -- comentarios
        $sqlLimpio = preg_replace('/#[^\n]*/m', '', $sqlLimpio);       // # comentarios
        $sqlLimpio = preg_replace('/\/\*.*?\*\//s', '', $sqlLimpio);   // /* */ comentarios
        $sqlLimpio = trim($sqlLimpio);

        if (empty($sqlLimpio)) {
            return response()->json([
                'success' => false,
                'error'   => 'El archivo SQL está vacío.',
            ], 422);
        }

        // ============================================
        // 2. EXTRAER SOLO LOS INSERT INTO empleados
        // ============================================
        // Match: INSERT INTO `empleados` (...) VALUES (...);
        // Soporta múltiples VALUES en un mismo INSERT
        preg_match_all(
            '/INSERT\s+INTO\s+`?empleados`?\s*\([^)]+\)\s*VALUES\s*(.+?);/is',
            $sqlLimpio,
            $matchInserts,
            PREG_SET_ORDER
        );

        if (empty($matchInserts)) {
            return response()->json([
                'success' => false,
                'error'   => 'No se encontraron sentencias INSERT INTO empleados en el archivo.',
            ], 422);
        }

        // ============================================
        // 3. EXTRAER LAS COLUMNAS DEL PRIMER INSERT
        // ============================================
        preg_match(
            '/INSERT\s+INTO\s+`?empleados`?\s*\(([^)]+)\)/i',
            $matchInserts[0][0],
            $matchColumnas
        );

        if (empty($matchColumnas[1])) {
            return response()->json([
                'success' => false,
                'error'   => 'No se pudieron detectar las columnas del INSERT.',
            ], 422);
        }

        $columnas = array_map(function ($col) {
            return trim($col, " `'\"\n\r\t");
        }, explode(',', $matchColumnas[1]));

        // ============================================
        // 4. PARSEAR TODAS LAS FILAS DE TODOS LOS INSERTs
        // ============================================
        $filasConvertidas = [];

        foreach ($matchInserts as $match) {
            $bloqueValues = $match[1]; // Todo lo que está entre VALUES y ;

            // Match de grupos (...) respetando comillas simples
            $valoresBloque = $this->extraerGruposParentesis($bloqueValues);

            foreach ($valoresBloque as $filaValores) {
                $valores = $this->parsearValoresSQL($filaValores);

                if (count($valores) !== count($columnas)) {
                    // Fila con columnas desalineadas: se salta
                    continue;
                }

                $filasConvertidas[] = array_combine($columnas, $valores);
            }
        }

        if (empty($filasConvertidas)) {
            return response()->json([
                'success' => false,
                'error'   => 'No se pudieron extraer filas válidas del archivo SQL.',
            ], 422);
        }

        // ============================================
        // 5. PROCESAR EN TRANSACCIÓN
        // ============================================
        DB::beginTransaction();

        try {
            $import = new EmpleadoImport(true);
            $import->procesarFilas($filasConvertidas, 1);

            DB::commit();

            return response()->json([
                'success'      => true,
                'mensaje'      => 'Importación SQL completada',
                'resumen'      => $import->resumen(),
                'cargados'     => $import->cargados,
                'actualizados' => $import->actualizados,
                'omitidos'     => $import->omitidos,
                'no_cargados'  => $import->noCargados,
                'errores'      => $import->errores,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error'   => 'Error al procesar SQL: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Extrae los grupos (...) de un bloque VALUES, respetando
     * comillas simples y paréntesis anidados.
     * Ejemplo: "('A', 'B'), ('C', 'D'), ('E', 'F')"
     * Retorna: ["'A', 'B'", "'C', 'D'", "'E', 'F'"]
     */
    protected function extraerGruposParentesis(string $bloque): array
    {
        $grupos = [];
        $buffer = '';
        $nivel = 0;
        $enComillas = false;
        $largo = strlen($bloque);

        for ($i = 0; $i < $largo; $i++) {
            $char = $bloque[$i];
            $prev = $i > 0 ? $bloque[$i - 1] : '';

            // Toggle comillas simples (no escapadas)
            if ($char === "'" && $prev !== '\\') {
                $enComillas = !$enComillas;
            }

            if (!$enComillas) {
                if ($char === '(') {
                    $nivel++;
                    if ($nivel === 1) {
                        $buffer = '';
                        continue;
                    }
                } elseif ($char === ')') {
                    if ($nivel === 1) {
                        $grupos[] = $buffer;
                        $buffer = '';
                    }
                    $nivel--;
                    continue;
                }
            }

            if ($nivel >= 1) {
                $buffer .= $char;
            }
        }

        return $grupos;
    }

    /**
     * Parsea los valores de una fila SQL.
     * Ejemplo: "'JUAN', 'PEREZ', NULL, 123"
     * Retorna: ['JUAN', 'PEREZ', null, '123']
     */
    protected function parsearValoresSQL(string $fila): array
    {
        $valores = [];
        $buffer = '';
        $enComillas = false;
        $largo = strlen($fila);

        for ($i = 0; $i < $largo; $i++) {
            $char = $fila[$i];
            $prev = $i > 0 ? $fila[$i - 1] : '';

            if ($char === "'" && $prev !== '\\') {
                $enComillas = !$enComillas;
                continue;
            }

            if ($char === ',' && !$enComillas) {
                $valores[] = $this->limpiarValorSQL($buffer);
                $buffer = '';
                continue;
            }

            $buffer .= $char;
        }

        if (trim($buffer) !== '' || $enComillas) {
            $valores[] = $this->limpiarValorSQL($buffer);
        }

        return $valores;
    }

    /**
     * Limpia un valor SQL crudo.
     */
    protected function limpiarValorSQL(string $valor): ?string
    {
        $valor = trim($valor);

        // NULL → null
        if (strtoupper($valor) === 'NULL') {
            return null;
        }

        // Quitar comillas simples externas
        if (strlen($valor) >= 2 && $valor[0] === "'" && substr($valor, -1) === "'") {
            $valor = substr($valor, 1, -1);
        }

        // Convertir escapes comunes
        $valor = str_replace(["\\'", '\\"', "\\n", "\\r", "\\t"], ["'", '"', "\n", "\r", "\t"], $valor);
        $valor = str_replace("''", "'", $valor); // Dobles comillas simples → una

        return $valor;
    }

    /**
     * Leer CSV como array asociativo
     */
    protected function leerCSV(string $ruta): array
    {
        $handle = fopen($ruta, 'r');
        if (!$handle) throw new \Exception('No se pudo leer el archivo CSV');

        // Detectar BOM UTF-8
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $encabezados = fgetcsv($handle);
        if (!$encabezados) throw new \Exception('El archivo CSV está vacío');

        // Normalizar encabezados (trim + lowercase)
        $encabezados = array_map(fn($e) => strtolower(trim((string) $e)), $encabezados);

        $filas = [];
        while (($fila = fgetcsv($handle)) !== false) {
            if (count($fila) === count($encabezados)) {
                $filas[] = array_combine($encabezados, $fila);
            }
        }

        fclose($handle);
        return $filas;
    }
}
