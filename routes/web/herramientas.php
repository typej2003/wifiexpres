<?php

use Illuminate\Support\Facades\Route;

use App\Http\Livewire\Mikrotik\Herramientas\Interfaces;

// Dentro de tu grupo de rutas con middleware 'auth' y 'admin'
Route::get('/mikrotik/herramientas/interfaces', Interfaces::class)->name('mikrotik.herramientas.interfaces');