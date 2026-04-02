<?php

use Illuminate\Support\Facades\Route;

use App\Http\Livewire\Mikrotik\Herramientas\Interfaces;

use App\Http\Livewire\Mikrotik\Herramientas\UsersOnline;

Route::middleware(['auth', 'admin'])->group(function () {
    
    // ... otras rutas ...

    Route::prefix('mikrotik')->group(function () {
        Route::prefix('herramientas')->group(function () {
            Route::get('/interfaces', Interfaces::class)->name('mikrotik.herramientas.interfaces');
        });
    });

});

Route::middleware(['role:admin,aliado'])->group(function () {
   
    Route::get('/mikrotik/herramientas/users-online', UsersOnline::class)
        ->name('mikrotik.users-online');
});