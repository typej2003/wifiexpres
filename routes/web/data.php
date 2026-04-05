<?php

use Illuminate\Support\Facades\Route;

use App\Http\Livewire\Mikrotik\Data\UserHistory;

use App\Http\Livewire\Mikrotik\Data\GraficoRouters;

use App\Http\Livewire\Mikrotik\Data\GraficoConexiones;

Route::get('/mikrotik/user-history/{username?}', UserHistory::class)->name('mikrotik.user-history');

Route::get('/mikrotik/grafico-uso', GraficoRouters::class)->name('mikrotik.grafico');

Route::get('/mikrotik/data/rendimiento-aliado', \App\Http\Livewire\Mikrotik\Data\GraficoConexiones::class)->name('mikrotik.grafico-conexiones');

Route::middleware(['auth', 'role:admin', 'role:aliado'])->prefix('admin/mikrotik')->group(function () {
    
    Route::get('/mikrotik/history', \App\Http\Livewire\Mikrotik\Data\TicketsHistory::class)->name('mikrotik.history');

});