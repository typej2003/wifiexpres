<?php

use Illuminate\Support\Facades\Route;

use App\Http\Livewire\Mikrotik\DataUserHistory;

Route::get('/mikrotik/user-history/{username?}', DataUserHistory::class)->name('mikrotik.user-history');