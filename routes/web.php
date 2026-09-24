<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmpleadoController;

Route::get('/', function () {
    return redirect()->route('login');
});

Auth::routes(['register' => false]);

Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/home', [DashboardController::class, 'index'])->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Módulo Empleados
    Route::prefix('empleados')->name('empleados.')->group(function () {

        // ============================================
        // RUTAS ESPECÍFICAS (SIEMPRE PRIMERO)
        // ============================================
        Route::get('/', [EmpleadoController::class, 'index'])->name('index');
        Route::post('/', [EmpleadoController::class, 'store'])->name('store');

        // Plantillas (descarga directa)
        Route::get('/plantilla', [EmpleadoController::class, 'descargarPlantilla'])->name('plantilla');
        Route::get('/plantilla-excel', [EmpleadoController::class, 'descargarPlantillaExcel'])->name('plantillaExcel');

        // Importación
        Route::post('/importar', [EmpleadoController::class, 'importar'])->name('importar');
        Route::post('/importar-sql', [EmpleadoController::class, 'importarSQL'])->name('importarSQL');

        // ============================================
        // RUTAS CON PARÁMETROS (SIEMPRE AL FINAL)
        // ============================================
        Route::get('/{empleado}/card', [EmpleadoController::class, 'card'])->name('card');
        Route::get('/{empleado}', [EmpleadoController::class, 'mostrar'])->name('mostrar');
        Route::put('/{empleado}', [EmpleadoController::class, 'update'])->name('update');
        Route::delete('/{empleado}', [EmpleadoController::class, 'destroy'])->name('destroy');
    });
});
