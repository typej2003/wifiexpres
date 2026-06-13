<?php

use Illuminate\Support\Facades\Route;
use App\Http\Livewire\Mikrotik\Smartdata\UsersVisits;
use App\Http\Livewire\Mikrotik\Smartdata\Permanencia;
use App\Http\Livewire\Mikrotik\Smartdata\PromocionesOfertas;
use App\Http\Livewire\Mikrotik\Smartdata\Monitoreo;

Route::middleware(['auth', 'role:aliadoSmartData,admin'])->group(function () {
    Route::get('/smartdata/users-visits/{userId?}', UsersVisits::class)->name('smartdata.users-visits');
    Route::get('/smartdata/permanencia', Permanencia::class)->name('smartdata.permanencia');
    Route::get('/smartdata/promociones', PromocionesOfertas::class)->name('smartdata.promociones');
    Route::get('/smartdata/monitoreo', Monitoreo::class)->name('smartdata.monitoreo');
});
