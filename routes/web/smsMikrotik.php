<?php

use Illuminate\Support\Facades\Route;

use App\Http\Livewire\Notificacion\SmsSender;

//para conectar
Route::get('/smsSender', SmsSender::class)->name('smsSender');