<?php

use Illuminate\Support\Facades\Route;
use App\Http\Livewire\Mikrotik\Ticket\ListTickets;
use App\Http\Controllers\TicketAuthController;
use App\Http\Livewire\Dashboards\TicketDashboard;
use App\Http\Livewire\Mikrotik\Aliado\ListTicketsAliado;
use App\Models\Ticket;
use App\Models\Router;
use Illuminate\Http\Request;

use App\Http\Livewire\Mikrotik\Ticket\ImprimirTickets;

// Gestión de Tickets para el Staff/Admin
Route::middleware(['auth'])->group(function () {
    Route::get('/tickets', ListTickets::class)->name('tickets.index');
    
    // Ruta de impresión para Aliados (Apunta al método imprimirPDF del componente)
    Route::get('/imprimir-tickets/{router_id}', [ListTicketsAliado::class, 'imprimirPDF'])->name('imprimir.directo');

    // Ruta de impresión optimizada original
    Route::get('/tickets/imprimir/{router_id}', function (Request $request, $router_id) {
        $router = Router::findOrFail($router_id);
        $desde = $request->query('desde');
        $hasta = $request->query('hasta');

        $tickets = Ticket::where('router_id', $router_id)
            ->whereBetween('identity', [$desde, $hasta])
            ->orderBy('identity', 'asc')
            ->get();

        if ($tickets->isEmpty()) return "No se encontraron tickets en el rango seleccionado.";

        return view('tickets.print_pdf', compact('tickets', 'router'));
    })->name('tickets.print');
});

// Rutas públicas del Portal Cautivo
Route::get('/acceso', function () { return view('auth.ticket-login'); })->name('ticket.login');
Route::post('/acceso/verificar', [TicketAuthController::class, 'login'])->name('ticket.auth.check');
Route::get('/acceso/salir', [TicketAuthController::class, 'logout'])->name('ticket.logout');

// Rutas protegidas para el cliente final
Route::middleware(['ticket.auth'])->group(function () {
    Route::get('/mi-cuenta', TicketDashboard::class)->name('ticket.dashboard');
});


Route::middleware(['auth'])->group(function () {
    // Nueva ruta para el módulo independiente de impresión
    Route::get('/tickets/impresion-masiva', ImprimirTickets::class)->name('tickets.imprimir.index');
});
