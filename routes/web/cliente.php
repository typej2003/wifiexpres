<?php

use Illuminate\Support\Facades\Route;

use App\Http\Livewire\Dashboards\ClienteDashboard;

Route::middleware(['auth'])->group(function () {
    // Ruta para Clientes
    
    Route::get('/dashboard-cliente', ClienteDashboard::class)->name('cliente.index');

});