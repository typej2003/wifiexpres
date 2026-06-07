<?php

use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use App\Models\DatosBasicos;
use App\Http\Controllers\WelcomeController;
use App\Http\Controllers\Auth\RedirectController;
use App\Http\Controllers\AuthController;
use App\Http\Livewire\Dashboards\AdminDashboard;
use App\Http\Livewire\Dashboards\AliadoDashboard;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Livewire\Error\ShowError;
use App\Http\Livewire\Welcome;
use App\Http\Livewire\Layouts\Components\ListCarrusel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Livewire\Package\PackageManagement;
use App\Http\Livewire\Package\SubscriptionManager;
use App\Http\Livewire\Mikrotik\Router\HotspotVersions;
use App\Http\Livewire\Mikrotik\Router\AllSales;
use App\Http\Livewire\Mikrotik\Router\ListUsersRouter;
use App\Http\Livewire\Mikrotik\Aliado\AntennaMappingManager;
// NUEVO IMPORT
use App\Http\Livewire\Mikrotik\Herramientas\RouterAuditor;

use App\Http\Livewire\Mikrotik\Data\HourAnalysis;

Route::get('/', Welcome::class)->name('welcome'); 
Route::get('/home', [RedirectController::class, 'dashboard'])->middleware('auth');
Route::get('/listCarrusel', ListCarrusel::class)->name('listCarrusel'); 
Route::post('/autenticar', [AuthController::class, 'autenticar'])->name('autenticar');
Route::post('/registrarse', [AuthController::class, 'registrarse'])->name('registrarse');

Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect('/');
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::get('/forgot-password', function () {
    return view('auth.forgot-password');
})->middleware('guest')->name('password.request');

Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])
    ->middleware('guest')
    ->name('password.email');

Route::get('/reset-password/{token}', function (string $token) {
    return view('auth.reset-password', ['token' => $token]);
})->middleware('guest')->name('password.reset');

Route::post('/reset-password', [ForgotPasswordController::class, 'reset'])
    ->middleware('guest')
    ->name('password.update');

Route::get('/login-google', function () {
    return Socialite::driver('google')->redirect();
});
 
Route::get('/google-callback', function () {
    return redirect('/');
});

Route::get('/errorFound/{error}', ShowError::class)->name('errorFound');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard-aliado', AliadoDashboard::class)->name('aliado.index');
    Route::get('/dashboard-aliadoSmartData', AliadoDashboard::class)->name('aliadoSmartData.index');

    Route::middleware(['auth', 'is_admin'])->group(function () {
        Route::get('/admin/panel', AdminDashboard::class)->name('admin.index');
    });
});

Route::middleware(['auth', 'role:admin'])->prefix('admin/mikrotik')->group(function () {
    Route::get('/routers', \App\Http\Livewire\Mikrotik\Router\ListRouters::class)->name('admin.routers.index');
    Route::get('/router/{router}/planes', \App\Http\Livewire\Mikrotik\Router\AdminPlanManager::class)->name('admin.router.planes');
    Route::get('/router/{id}/tickets', \App\Http\Livewire\Mikrotik\Router\AdminTicketList::class)->name('admin.router.tickets');
    Route::get('/router/{id}/usuarios', ListUsersRouter::class)->name('mikrotik.router.usuarios');
    
    // RUTA DE AUDITORÍA DEL BRIDGE
    Route::get('/bridge-auditor', RouterAuditor::class)->name('admin.bridge.auditor');
});

Route::middleware(['role:admin,aliado'])->group(function () {
    Route::get('/planes-comerciales', PackageManagement::class)->name('packages.index');
    Route::get('/configurar-antenas/{router_id}', AntennaMappingManager::class)->name('aliado.antenas.config');
    Route::get('/hour-analysis', HourAnalysis::class)->name('aliado.hour.analysis');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/subscriptions', SubscriptionManager::class)->name('admin.subscriptions');
    Route::get('/hotspot-versions', HotspotVersions::class)->name('hotspot.versions');
});

Route::get('mikrotik/router/all-sales', AllSales::class)
    ->name('mikrotik.router.all-sales')
    ->middleware(['auth']);