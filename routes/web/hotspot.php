<?php

use Illuminate\Support\Facades\Route;
use App\Http\Livewire\Mikrotik\Hotspot\ListTicketsVendidos;
use App\Http\Livewire\Mikrotik\Hotspot\ShowQr;
use Illuminate\Support\Facades\Redirect;

Route::get('/listTicketsVendidos', ListTicketsVendidos::class)->name('listTicketsVendidos')->middleware('auth');

Route::get('/showQr/{ticket_id}', ShowQr::class)->name('showQr')->middleware('auth');

Route::get('/portal_nuevo', function () {
    return Redirect::to('portal_nuevo.html');
})->name('portal_nuevo');