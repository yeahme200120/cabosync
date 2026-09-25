<?php

use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\ConciliacionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\ReporteController;
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
});

// ============================================
// DESCARGA PÚBLICA (sin auth)
// ============================================
Route::get('/r/{token}',                  [ReporteController::class, 'descargaPublica'])->name('reportes.publico.descarga');
Route::get('/r/{token}/descargar/{tipo}', [ReporteController::class, 'descargarArchivo'])->name('reportes.publico.descargar');