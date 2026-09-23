<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class EmpleadoController extends Controller
{
    public function index()
    {
        return view('empleados.index');
    }

    public function obtenerDatosTabla(Request $request)
    {
        $query = Empleado::query()
            ->select(['id', 'empresa_id', 'curp_dni', 'nombre', 'apellido', 'puesto_cargo', 'estatus', 'created_at']);

        if ($request->has('empresa_id') && $request->empresa_id != '') {
            $query->where('empresa_id', $request->empresa_id);
        }

        return DataTables::of($query)
            ->addColumn('nombre_completo', function ($row) {
                return $row->nombre . ' ' . $row->apellido;
            })
            ->addColumn('acciones', function ($row) {
                return '
                    <button class="btn btn-sm btn-primary btn-editar" data-id="' . $row->id . '">
                        <i class="bi bi-pencil"></i> Editar
                    </button>
                    <button class="btn btn-sm btn-info btn-expediente" data-id="' . $row->id . '">
                        <i class="bi bi-folder"></i> Expediente
                    </button>
                ';
            })
            ->rawColumns(['acciones'])
            ->make(true);
    }
}
