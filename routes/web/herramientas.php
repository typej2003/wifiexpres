<?php

use Illuminate\Support\Facades\Route;

use App\Http\Livewire\Mikrotik\Herramientas\Interfaces;

use App\Http\Livewire\Mikrotik\Herramientas\UsersOnline;

use App\Http\Livewire\Mikrotik\Herramientas\CambiarTrialUserprofile;

use App\Http\Controllers\ApkController;

Route::get('/descargar-apk', [ApkController::class, 'download'])->name('apk.download');

Route::middleware(['auth', 'admin'])->group(function () {
    
    // ... otras rutas ...

    Route::prefix('mikrotik')->group(function () {
        Route::prefix('herramientas')->group(function () {
            Route::get('/interfaces', Interfaces::class)->name('mikrotik.herramientas.interfaces');
        });
    });

    Route::get('/mikrotik/herramientas/cambiar-trial', CambiarTrialUserprofile::class)
        ->name('mikrotik.cambiar-trial');

});

Route::middleware(['role:admin,aliado'])->group(function () {
   
    Route::get('/mikrotik/herramientas/users-online', UsersOnline::class)
        ->name('mikrotik.users-online');
});