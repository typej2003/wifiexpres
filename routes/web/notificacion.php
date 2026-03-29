<?php

use Illuminate\Support\Facades\Route;

// Notificaciones

use App\Http\Livewire\Notificacion\EmailExample;
use App\Http\Livewire\Notificacion\EmailFile;
use App\Http\Livewire\Notificacion\EmailController;
use App\Http\Controllers\SmsTwilioController;
use App\Http\Livewire\Notificacion\ListNotificaciones;
use App\Http\Controllers\PdfController;

Route::get('/sendemail/{index}', EmailController::class)->name('sendemail');

Route::get('/emailexample', EmailExample::class)->name('emailexample');

Route::get('/emailFiles', EmailFile::class)->name('emailFiles');

Route::get('sms/send', [SmsTwilioController::class, 'sendSms']);

Route::get('/listNotificaciones/{comercioId}', ListNotificaciones::class)->name('listNotificaciones')->middleware('auth');

Route::post('saveNotificacion', [ListNotificaciones::class, 'saveNotificacion'])->middleware('auth');

Route::get('/admin/history/pdf', [PdfController::class, 'exportHistory'])->name('admin.history.pdf');