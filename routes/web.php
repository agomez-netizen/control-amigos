<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\AvanceController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\UsuarioEmpresaController;

/*
|--------------------------------------------------------------------------
| Ruta raíz
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return session()->has('user')
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| Rutas públicas (solo invitados)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {

    Route::get('/login', [AuthController::class, 'show'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login'])
        ->name('login.post');
});

/*
|--------------------------------------------------------------------------
| Logout (usa tu AuthController)
|--------------------------------------------------------------------------
*/
Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Rutas protegidas (tu middleware de sesión + no cache)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth.custom', 'nocache'])->group(function () {

    // =========================
    // DASHBOARD
    // =========================
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/scatter', [DashboardController::class, 'scatter'])->name('dashboard.scatter');

    // =========================
    // AVANCES
    // =========================
    Route::get('/avances', [AvanceController::class, 'create'])->name('avances.create');
    Route::post('/avances', [AvanceController::class, 'store'])->name('avances.store');

    Route::get('/avances/por-fecha', [AvanceController::class, 'byDate'])->name('avances.byDate');

    // Editar + Bitácora
    Route::put('/avances/{id}', [AvanceController::class, 'update'])->name('avances.update');
    Route::get('/avances/{id}/historial', [AvanceController::class, 'historial'])->name('avances.historial');

    // Si usas editor con imágenes
    Route::post('/avances/upload-image', [AvanceController::class, 'uploadImage'])
        ->name('avances.uploadImage');

    Route::get('/avances/dashboard', [AvanceController::class, 'dashboard'])->name('avances.dashboard');

    // =========================
    // EMPRESAS
    // =========================

    // (Opcional) alias para entrar con /empresa
    Route::get('/empresa', fn () => redirect()->route('empresas.index'))
        ->name('empresa.alias');

    // Exportaciones

    Route::get('empresas/export/excel', [EmpresaController::class, 'exportEmpresasExcel'])
    ->name('empresas.export.excel');

    Route::get('empresas/{empresa}/contactos/export/excel', [EmpresaController::class, 'exportContactosExcel'])
    ->name('empresas.contactos.export.excel');

        Route::get('/empresas/import', [EmpresaController::class, 'importForm'])
    ->name('empresas.importForm');

Route::post('/empresas/import', [EmpresaController::class, 'importExcel'])
    ->name('empresas.importExcel');
    // CRUD completo
    Route::resource('empresas', EmpresaController::class);

    Route::get('/avances/exportar', [AvanceController::class, 'exportByDate'])
    ->name('avances.export');

    Route::post('empresas/{empresa}/contactos', [EmpresaController::class, 'contactoStore'])
  ->name('empresas.contactos.store');

    Route::put('empresas/{empresa}/contactos/{contacto}', [EmpresaController::class, 'contactoUpdate'])
    ->name('empresas.contactos.update');

    Route::delete('empresas/{empresa}/contactos/{contacto}', [EmpresaController::class, 'contactoDestroy'])
    ->name('empresas.contactos.destroy');

    // =========================
    // VENTAS
    // =========================
    Route::get('/ventas/create', [VentaController::class, 'create'])->name('ventas.create');
    Route::post('/ventas', [VentaController::class, 'store'])->name('ventas.store');


        Route::get('/usuarios/empresas', [UsuarioEmpresaController::class, 'index'])
        ->name('usuarios.empresas');

    Route::get('/usuarios/{id}/empresas', [UsuarioEmpresaController::class, 'edit'])
        ->name('usuarios.empresas.edit');

    Route::post('/usuarios/{id}/empresas', [UsuarioEmpresaController::class, 'update'])
        ->name('usuarios.empresas.update');



});

/*
|--------------------------------------------------------------------------
| DASHBOARD PÚBLICO (sin login)
|--------------------------------------------------------------------------
*/

Route::get('/public/dashboard', [DashboardController::class, 'public'])
    ->name('dashboard.public');
