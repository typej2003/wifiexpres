<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Livewire\Admin\Appointments\CreateAppointmentForm;
use App\Http\Livewire\Admin\Appointments\ListAppointments;
use App\Http\Livewire\Admin\Appointments\UpdateAppointmentForm;
use App\Http\Livewire\Admin\Messages\ListConversationAndMessages;
use App\Http\Livewire\Admin\Profile\UpdateProfile;
use App\Http\Livewire\Admin\Settings\UpdateSetting;
use App\Http\Livewire\Admin\Settings\ListAreas;
use App\Http\Livewire\Mikrotik\Herramientas\Diagnostico; // Importante añadir esta
use App\Http\Livewire\Analytics;

// Rutas Protegidas
Route::middleware(['auth'])->group(function () {
    
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('appointments', ListAppointments::class)->name('appointments');
    Route::get('appointments/create', CreateAppointmentForm::class)->name('appointments.create');
    Route::get('appointments/{appointment}/edit', UpdateAppointmentForm::class)->name('appointments.edit');

    Route::get('profile', UpdateProfile::class)->name('profile.edit');
    Route::get('analytics', Analytics::class)->name('analytics');

    
    Route::get('listAreas', ListAreas::class)->name('listAreas');
    Route::get('messages', ListConversationAndMessages::class)->name('messages');

});
Route::middleware(['auth'])->group(function () {
    // Rutas sin prefijo manual (acceso directo)
    Route::get('diagnostico', Diagnostico::class)->name('diagnostico');
    Route::get('/configuraciones', UpdateSetting::class)->name('configuraciones');
    
    // Si esta ruta es la que usas para el panel:
    Route::get('admin/panel', DashboardController::class)->name('admin.index');
});
