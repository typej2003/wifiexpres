<?php

use Illuminate\Support\Facades\Route;

use App\Http\Livewire\Mikrotik\Data\UserHistory;

Route::get('/mikrotik/user-history/{username?}', UserHistory::class)->name('mikrotik.user-history');