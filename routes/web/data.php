<?php

use Illuminate\Support\Facades\Route;

use App\Http\Livewire\Mikrotik\Data\UserHistory;

use App\Http\Livewire\Mikrotik\Data\GraficoRouters;

use App\Http\Livewire\Mikrotik\Data\GraficoConexiones;

use App\Http\Livewire\Mikrotik\Data\TicketsHistory;

use App\Http\Livewire\Mikrotik\Data\ListNotificacionesApp;

use App\Http\Livewire\Mikrotik\Data\MetricaCampaign;

use App\Http\Livewire\Mikrotik\Data\ListUsersMikrotik;

Route::get('/mikrotik/user-history/{username?}', UserHistory::class)->name('mikrotik.user-history');

Route::get('/mikrotik/grafico-uso', GraficoRouters::class)->name('mikrotik.grafico');

Route::get('/mikrotik/data/rendimiento-aliado', \App\Http\Livewire\Mikrotik\Data\GraficoConexiones::class)->name('mikrotik.grafico-conexiones');

// Usamos una coma para indicar que CUALQUIERA de los dos roles tiene acceso
Route::middleware(['auth', 'role:admin,aliado,aliadoSmartData'])->prefix('admin/mikrotik')->group(function () {
    
    // La URL final será: /admin/mikrotik/history
    Route::get('/history', \App\Http\Livewire\Mikrotik\Data\TicketsHistory::class)->name('mikrotik.history');

    // Ruta para generar el reporte de impresión
    Route::get('/tickets-report', [\App\Http\Livewire\Mikrotik\Data\TicketsHistory::class, 'printReport'])->name('tickets.report');

    Route::get('/mikrotik/data/notificaciones-app', ListNotificacionesApp::class)->name('mikrotik.data.notificaciones');

    Route::get('/metrica-campana', MetricaCampaign::class)->name('mikrotik.metrica-campana');

    // Ruta para el listado de nuevos usuarios registrados
    Route::get('/list-users-mikrotik', ListUsersMikrotik::class)->name('mikrotik.data.list-users');
});