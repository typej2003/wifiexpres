<?php

use Illuminate\Support\Facades\Route;

// Importaciones existentes
use App\Http\Livewire\Mikrotik\Aliado\ListRouters as AliadoRouters;
use App\Http\Livewire\Mikrotik\Aliado\PlanManager;
use App\Http\Livewire\Mikrotik\Aliado\TicketHistory;
use App\Http\Livewire\Mikrotik\Aliado\ListTicketsAliado;
use App\Http\Livewire\Mikrotik\Aliado\HotspotConfig;

// NUEVAS IMPORTACIONES
use App\Http\Livewire\Mikrotik\Aliado\AliadoRanking;
use App\Http\Livewire\Mikrotik\Aliado\MonitorAccounts; // <--- NUEVA IMPORTACIÓN
use App\Http\Livewire\Mikrotik\Aliado\ListAgeRanges;
use App\Http\Livewire\Hablador\HabladorManager;
use App\Models\Pantalla;
use App\Http\Livewire\Mikrotik\Aliado\SalesReports;
use App\Http\Livewire\Mikrotik\Aliado\ListAdvertisingCampaign;

// Rutas accesibles para ambos roles (Admin y Aliado)
Route::middleware(['auth'])->group(function () {
    
    Route::middleware(['role:admin,aliado, aliadoSmartData'])->group(function () {
        // Gestión de Tickets
        Route::get('/mis-tickets/{id?}', ListTicketsAliado::class)->name('aliado.tickets');

        // Gestión de Planes
        Route::get('/mikrotik/router/{router}/planes', PlanManager::class)->name('aliado.router.planes');

        // Historial de conexiones
        Route::get('/aliado/router/{router}/historial', TicketHistory::class)->name('aliado.router.historial');

        // Impresión (PDF)
        Route::get('imprimir-tickets/{router_id}', [ListTicketsAliado::class, 'imprimirPDF'])->name('imprimir.directo');
        
        // Configuración de Hotspot
        Route::get('/mikrotik/hotspot-config/{id}', HotspotConfig::class)->name('mikrotik.hotspot.config');

        // MONITOR DE CUENTAS (Ubicación por IP/Antena)
        // Se coloca aquí para que el Admin pueda ver a todos y el Aliado lo use en su red
        Route::get('/monitor-cuentas', MonitorAccounts::class)->name('aliado.monitor');

        // Gestión de Rangos de Edad
        Route::get('/aliado/age-ranges', ListAgeRanges::class)->name('aliado.age-ranges');
    });

    // Rutas exclusivas del aliado
    
    Route::middleware(['role:aliadoSmartData,aliado'])->group(function () {
        Route::get('/mis-routers', AliadoRouters::class)->name('aliado.routers');
    });

    Route::middleware(['role:admin,aliado,aliadoSmartData'])->group(function () {
        Route::get('/planes-comerciales', PackageManagement::class)->name('packages.index');
        Route::get('/configurar-antenas/{router_id}', AntennaMappingManager::class)->name('aliado.antenas.config');
        Route::get('/hour-analysis', HourAnalysis::class)->name('aliado.hour.analysis');
    });
});

// DASHBOARD Y RANKING DEL ALIADO
Route::middleware(['auth', 'role:aliado'])->prefix('aliado')->name('aliado.')->group(function () {
    Route::get('/ranking', AliadoRanking::class)->name('ranking');
});

// Rutas de Wifiexprés

// 1. RUTA DEL MANAGER (Para el Aliado/Administrador)
Route::get('/admin/habladores', HabladorManager::class)
    ->middleware(['auth'])
    ->name('habladores.index');

// 2. RUTA DE LA TV (Para el televisor físico)
Route::get('/tv/{slug_pantalla}', function ($slug_pantalla) {
    $pantalla = Pantalla::where('slug_pantalla', $slug_pantalla)->firstOrFail();
    return view('tv.receptor', [
        'pantalla' => $pantalla
    ]);
})->name('tv.receptor');

Route::middleware(['role:aliado'])->group(function () {
    Route::get('/mis-ventas', SalesReports::class)->name('aliado.ventas');
});

Route::middleware(['auth'])->group(function () {
    // ... otras rutas
    
    Route::get('/mikrotik/aliado/campaigns', ListAdvertisingCampaign::class)
        ->name('mikrotik.aliado.campaigns');
});