<?php

use Illuminate\Support\Facades\Route;

use App\Http\Livewire\Mikrotik\Herramientas\Interfaces;

Route::middleware(['auth', 'admin'])->group(function () {
    
    // ... otras rutas ...

    Route::prefix('mikrotik')->group(function () {
        Route::prefix('herramientas')->group(function () {
            Route::get('/interfaces', Interfaces::class)->name('mikrotik.herramientas.interfaces');
        });
    });

});