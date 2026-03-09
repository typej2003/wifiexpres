<?php

use Illuminate\Support\Facades\Route;
use App\Http\Livewire\Mikrotik\Hotspot\ListTicketsVendidos;
use App\Http\Livewire\Mikrotik\Hotspot\ShowQr;

Route::get('/listTicketsVendidos', ListTicketsVendidos::class)->name('listTicketsVendidos')->middleware('auth');

Route::get('/showQr/{ticket_id}', ShowQr::class)->name('showQr')->middleware('auth');