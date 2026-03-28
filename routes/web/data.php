<?php

use Illuminate\Support\Facades\Route;

use App\Http\Livewire\Mikrotik\UserHistory;

Route::get('/mikrotik/user-history/{username?}', UserHistory::class)->name('mikrotik.user-history');