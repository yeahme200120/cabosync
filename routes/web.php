<?php

use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\BitacoraController;
use App\Http\Controllers\ConciliacionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\ObraController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return redirect()->route('login');
});

Auth::routes(['register' => false]);

Route::middleware(['auth'])->group(function () {

    // ============================================
    // DASHBOARD
    // ============================================
    Route::get('/home', [DashboardController::class, 'index'])->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ============================================
    // MÓDULO EMPLEADOS
    // ============================================
    Route::prefix('empleados')->name('empleados.')->group(function () {
        Route::get('/', [EmpleadoController::class, 'index'])->name('index');
        Route::post('/', [EmpleadoController::class, 'store'])->name('store');

        Route::get('/plantilla', [EmpleadoController::class, 'descargarPlantilla'])->name('plantilla');
        Route::get('/plantilla-excel', [EmpleadoController::class, 'descargarPlantillaExcel'])->name('plantillaExcel');

        Route::post('/importar', [EmpleadoController::class, 'importar'])->name('importar');
        Route::post('/importar-sql', [EmpleadoController::class, 'importarSQL'])->name('importarSQL');

        Route::get('/{empleado}/card', [EmpleadoController::class, 'card'])->name('card');
        Route::get('/{empleado}', [EmpleadoController::class, 'mostrar'])->name('mostrar');
        Route::put('/{empleado}', [EmpleadoController::class, 'update'])->name('update');
        Route::delete('/{empleado}', [EmpleadoController::class, 'destroy'])->name('destroy');
    });

    // ============================================
    // MÓDULO ASISTENCIA
    // ============================================
    Route::prefix('asistencia')->name('asistencia.')->group(function () {
        Route::get('/pase-lista',                 [AsistenciaController::class, 'index'])->name('index');
        Route::get('/datos',                      [AsistenciaController::class, 'obtenerDatos'])->name('datos');
        Route::post('/guardar',                   [AsistenciaController::class, 'guardarPase'])->name('guardar');
        Route::post('/horas-extra',               [AsistenciaController::class, 'guardarHorasExtra'])->name('horasExtra');
        Route::post('/justificar',                [AsistenciaController::class, 'justificarFalta'])->name('justificar');
        Route::post('/quitar-justificacion',      [AsistenciaController::class, 'quitarJustificacion'])->name('quitarJustificacion');
        Route::get('/empleado/{empleado}/estado', [AsistenciaController::class, 'estadoEmpleado'])->name('empleado.estado');
    });

    // ============================================
    // MÓDULO CONCILIACIÓN
    // ============================================
    Route::prefix('conciliacion')->name('conciliacion.')->group(function () {
        Route::get('/',                           [ConciliacionController::class, 'index'])->name('index');
        Route::get('/datos',                      [ConciliacionController::class, 'obtenerMatriz'])->name('datos');
        Route::get('/detalle',                    [ConciliacionController::class, 'detalleCelda'])->name('detalle');
        Route::post('/conciliar-dia',             [ConciliacionController::class, 'conciliarDia'])->name('conciliarDia');
        Route::post('/conciliar-masiva',          [ConciliacionController::class, 'conciliarMasivo'])->name('masiva');
        Route::post('/conciliar-semana-completa', [ConciliacionController::class, 'conciliarSemanaCompleta'])->name('conciliarSemanaCompleta');
        Route::post('/desbloquear',               [ConciliacionController::class, 'desbloquear'])->name('desbloquear');
        Route::post('/desbloquear-semana',        [ConciliacionController::class, 'desbloquearSemana'])->name('desbloquearSemana');
        Route::get('/jefes',                      [ConciliacionController::class, 'listarJefes'])->name('jefes');
        Route::post('/toggle-permiso-jefe',       [ConciliacionController::class, 'togglePermisoJefe'])->name('togglePermisoJefe');
    });

    // ============================================
    // MÓDULO REPORTES (fuera de conciliación)
    // ============================================
    Route::prefix('reportes')->name('reportes.')->group(function () {
        Route::get('/descargar/{tipo}', [ReporteController::class, 'descargar'])->name('descargar');
        Route::get('/blob/{tipo}',      [ReporteController::class, 'obtenerBlob'])->name('blob');
        Route::post('/generar-link',    [ReporteController::class, 'generarLink'])->name('generarLink');
        Route::post('/enviar-correo',   [ReporteController::class, 'enviarCorreo'])->name('enviarCorreo');
    });

    // =========================================================
    // EMPRESAS
    // =========================================================
    Route::prefix('empresas')->name('empresas.')->middleware('auth')->group(function () {
        // Específicas primero
        Route::get('/',                    [EmpresaController::class, 'index'])->name('index');
        Route::post('/',                   [EmpresaController::class, 'store'])->name('store');

        // Con parámetros al final
        Route::get('/{id}',                [EmpresaController::class, 'mostrar'])->name('mostrar');
        Route::put('/{id}',                [EmpresaController::class, 'update'])->name('update');
        Route::delete('/{id}',             [EmpresaController::class, 'destroy'])->name('destroy');
        Route::get('/{id}/card',           [EmpresaController::class, 'card'])->name('card');
        Route::post('/{id}/desactivar',    [EmpresaController::class, 'desactivar'])->name('desactivar');
    });
    // =========================================================
    // OBRAS
    // =========================================================
    Route::prefix('obras')->name('obras.')->middleware('auth')->group(function () {
        Route::get('/',                    [ObraController::class, 'index'])->name('index');
        Route::post('/',                   [ObraController::class, 'store'])->name('store');

        Route::get('/{id}',                [ObraController::class, 'mostrar'])->name('mostrar');
        Route::put('/{id}',                [ObraController::class, 'update'])->name('update');
        Route::delete('/{id}',             [ObraController::class, 'destroy'])->name('destroy');
        Route::get('/{id}/card',           [ObraController::class, 'card'])->name('card');
        Route::post('/{id}/pausar',        [ObraController::class, 'pausar'])->name('pausar');
        Route::post('/{id}/activar',       [ObraController::class, 'activar'])->name('activar');
        Route::post('/{id}/terminar',      [ObraController::class, 'terminar'])->name('terminar');
    });
    // ============================================
    // MÓDULO USUARIOS
    // ============================================
    Route::prefix('usuarios')->name('usuarios.')->group(function () {
        Route::get('/',                    [UsuarioController::class, 'index'])->name('index');
        Route::post('/',                   [UsuarioController::class, 'store'])->name('store');
        Route::get('/plantilla',           [UsuarioController::class, 'descargarPlantilla'])->name('plantilla');
        Route::get('/plantilla-excel',     [UsuarioController::class, 'descargarPlantillaExcel'])->name('plantillaExcel');
        Route::post('/importar',           [UsuarioController::class, 'importar'])->name('importar');
        Route::post('/importar-sql',       [UsuarioController::class, 'importarSQL'])->name('importarSQL');
        Route::post('/dejar-impersonar',   [UsuarioController::class, 'dejarImpersonar'])->name('dejarImpersonar');

        Route::get('/{id}',                [UsuarioController::class, 'mostrar'])->name('mostrar');
        Route::put('/{id}',                [UsuarioController::class, 'update'])->name('update');
        Route::delete('/{id}',             [UsuarioController::class, 'destroy'])->name('destroy');
        Route::get('/{id}/card',           [UsuarioController::class, 'card'])->name('card');
        Route::get('/{id}/historial',       [UsuarioController::class, 'historial'])->name('historial');
        Route::post('/{id}/desactivar',    [UsuarioController::class, 'desactivar'])->name('desactivar');
        Route::post('/{id}/reset-password', [UsuarioController::class, 'resetPassword'])->name('resetPassword');
        Route::post('/{id}/impersonar',    [UsuarioController::class, 'impersonar'])->name('impersonar');
    });
    // =========================================================
    // BITÁCORA
    // =========================================================
    Route::prefix('bitacora')->name('bitacora.')->middleware('auth')->group(function () {
        Route::get('/',          [BitacoraController::class, 'index'])->name('index');
        Route::get('/{id}',      [BitacoraController::class, 'mostrar'])->name('mostrar');
    });
    Route::prefix('legal')->name('legal.')->group(function () {
        Route::get('/mis-consentimientos', [LegalController::class, 'misConsentimientos'])->name('misConsentimientos');
        Route::post('/aceptar',            [LegalController::class, 'aceptar'])->name('aceptar');
    });
    Route::prefix('configuracion/legal')->name('legal.admin.')->group(function () {
        Route::get('/',       [LegalController::class, 'admin'])->name('index');
        Route::post('/guardar', [LegalController::class, 'guardar'])->name('guardar');
    });
});

// ============================================
// DESCARGA PÚBLICA (sin auth)
// ============================================
// =========================================================
// LEGAL — PÚBLICO (sin auth)
// =========================================================
Route::prefix('legal')->name('legal.')->group(function () {
    Route::get('/terminos',   [LegalController::class, 'terminos'])->name('terminos');
    Route::get('/aviso',      [LegalController::class, 'aviso'])->name('aviso');
});

Route::get('/r/{token}',                  [ReporteController::class, 'descargaPublica'])->name('reportes.publico.descarga');
Route::get('/r/{token}/descargar/{tipo}', [ReporteController::class, 'descargarArchivo'])->name('reportes.publico.descargar');
