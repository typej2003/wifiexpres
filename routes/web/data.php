<?php

use Illuminate\Support\Facades\Route;

use App\Http\Livewire\Mikrotik\Data\UserHistory;

use App\Http\Livewire\Mikrotik\Data\GraficoRouters;

Route::get('/mikrotik/user-history/{username?}', UserHistory::class)->name('mikrotik.user-history');

Route::get('/mikrotik/grafico-uso', GraficoRouters::class)->name('mikrotik.grafico');