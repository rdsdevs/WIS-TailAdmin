<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RH;
use Illuminate\Support\Facades\Route;

// Rutas de autenticación
Route::middleware('guest')->group(function (): void {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);
});

Route::post('logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// Dashboard
Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware('auth')
    ->name('dashboard');

// Ruta raíz — redirige al dashboard
Route::get('/', fn () => redirect()->route('dashboard'));

// ─── Módulo de Recursos Humanos ──────────────────────────────────────────────
Route::prefix('rh')->name('rh.')->middleware('auth')->group(function (): void {
    // Dependencias (departamentos)
    Route::resource('departamentos', RH\DepartmentController::class);

    // Cargos (positions)
    Route::resource('cargos', RH\PositionController::class);

    // Colaboradores (unificado — reemplaza empleados y contratistas)
    // Las rutas adicionales DEBEN ir antes del resource para evitar
    // que Laravel interprete los segmentos como el parámetro {collaborator}

    // Importación masiva de colaboradores
    Route::get('colaboradores/importar', [\App\Http\Controllers\RH\CollaboratorImportController::class, 'create'])
        ->name('colaboradores.importar');
    Route::post('colaboradores/importar', [\App\Http\Controllers\RH\CollaboratorImportController::class, 'store'])
        ->name('colaboradores.importar.store');
    Route::get('colaboradores/plantilla/{tipo}', [\App\Http\Controllers\RH\CollaboratorImportController::class, 'template'])
        ->name('colaboradores.plantilla')
        ->where('tipo', 'empleados|contratistas|todos');

    Route::get('colaboradores/exportar/{tipo}', [RH\CollaboratorController::class, 'export'])
        ->name('colaboradores.export');
    Route::post('colaboradores/{colaborador}/change-type', [RH\CollaboratorController::class, 'changeType'])
        ->name('colaboradores.change-type');
    Route::resource('colaboradores', RH\CollaboratorController::class)
        ->parameters(['colaboradores' => 'collaborator']);

    // Importación masiva de contratos
    Route::get('contratos/importar', [\App\Http\Controllers\RH\ContractImportController::class, 'create'])
        ->name('contratos.importar');
    Route::post('contratos/importar', [\App\Http\Controllers\RH\ContractImportController::class, 'store'])
        ->name('contratos.importar.store');
    Route::get('contratos/plantilla', [\App\Http\Controllers\RH\ContractImportController::class, 'template'])
        ->name('contratos.plantilla');

    // Contratos — acción de terminar contrato
    Route::patch('contratos/{contrato}/terminar', [RH\ContractController::class, 'terminate'])
        ->name('contratos.terminate');
    Route::resource('contratos', RH\ContractController::class);
});

// ─── Administración de usuarios ──────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->middleware('auth')->group(function (): void {
    Route::resource('usuarios', \App\Http\Controllers\Admin\UserController::class)
        ->parameters(['usuarios' => 'usuario']);
});

// calender pages
Route::get('/calendar', function () {
    return view('pages.calender', ['title' => 'Calendar']);
})->name('calendar');

// Perfil de usuario
Route::get('/profile', function () {
    $user = auth()->user()->load('institution', 'roles');
    return view('pages.profile', ['title' => 'Mi perfil', 'user' => $user]);
})->middleware('auth')->name('profile');

// form pages
Route::get('/form-elements', function () {
    return view('pages.form.form-elements', ['title' => 'Form Elements']);
})->name('form-elements');

// tables pages
Route::get('/basic-tables', function () {
    return view('pages.tables.basic-tables', ['title' => 'Basic Tables']);
})->name('basic-tables');

// pages

Route::get('/blank', function () {
    return view('pages.blank', ['title' => 'Blank']);
})->name('blank');

// error pages
Route::get('/error-404', function () {
    return view('pages.errors.error-404', ['title' => 'Error 404']);
})->name('error-404');

// chart pages
Route::get('/line-chart', function () {
    return view('pages.chart.line-chart', ['title' => 'Line Chart']);
})->name('line-chart');

Route::get('/bar-chart', function () {
    return view('pages.chart.bar-chart', ['title' => 'Bar Chart']);
})->name('bar-chart');

// authentication pages
Route::get('/signin', function () {
    return view('pages.auth.signin', ['title' => 'Sign In']);
})->name('signin');

Route::get('/signup', function () {
    return view('pages.auth.signup', ['title' => 'Sign Up']);
})->name('signup');

// ui elements pages
Route::get('/alerts', function () {
    return view('pages.ui-elements.alerts', ['title' => 'Alerts']);
})->name('alerts');

Route::get('/avatars', function () {
    return view('pages.ui-elements.avatars', ['title' => 'Avatars']);
})->name('avatars');

Route::get('/badge', function () {
    return view('pages.ui-elements.badges', ['title' => 'Badges']);
})->name('badges');

Route::get('/buttons', function () {
    return view('pages.ui-elements.buttons', ['title' => 'Buttons']);
})->name('buttons');

Route::get('/image', function () {
    return view('pages.ui-elements.images', ['title' => 'Images']);
})->name('images');

Route::get('/videos', function () {
    return view('pages.ui-elements.videos', ['title' => 'Videos']);
})->name('videos');
