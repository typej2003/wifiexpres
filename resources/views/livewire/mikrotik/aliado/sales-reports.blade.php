<div class="container-fluid py-4">
    {{-- CABECERA Y FILTROS --}}
    <div class="row mb-4 align-items-center d-print-none">
        <div class="col-xl-4 col-lg-12 mb-3 mb-xl-0">
            <h2 class="fw-bold text-dark mb-0">Balance de Ingresos</h2>
            <p class="text-muted small">Reporte bimoneda detallado por equipo y método.</p>
        </div>
        
        <div class="col-xl-8 col-lg-12">
            <div class="d-flex flex-wrap justify-content-xl-end align-items-center gap-3">
                {{-- Rango de Fechas --}}
                <div class="d-flex align-items-center bg-white border rounded-pill px-3 py-1 shadow-sm">
                    <div class="d-flex align-items-center me-2">
                        <label class="small text-muted me-2 mb-0">Desde:</label>
                        <input type="date" wire:model="fromDate" class="form-control form-control-sm border-0 p-0 shadow-none" style="width: 115px;">
                    </div>
                    <div class="d-flex align-items-center border-start ps-2">
                        <label class="small text-muted me-2 mb-0">Hasta:</label>
                        <input type="date" wire:model="toDate" class="form-control form-control-sm border-0 p-0 shadow-none" style="width: 115px;">
                    </div>
                </div>

                {{-- Botones Rápidos --}}
                <div class="btn-group bg-white p-1 rounded-pill border shadow-sm">
                    <button wire:click="setPeriod('today')" class="btn btn-sm rounded-pill px-3 {{ $period == 'today' ? 'btn-dark' : 'btn-white border-0' }}">Hoy</button>
                    <button wire:click="setPeriod('weekly')" class="btn btn-sm rounded-pill px-3 {{ $period == 'weekly' ? 'btn-dark' : 'btn-white border-0' }}">Semana</button>
                    <button wire:click="setPeriod('month')" class="btn btn-sm rounded-pill px-3 {{ $period == 'month' ? 'btn-dark' : 'btn-white border-0' }}">Mes</button>
                </div>

                {{-- Botón Imprimir --}}
                <button onclick="window.print()" class="btn btn-primary rounded-pill px-4 shadow-sm">
                    <i class="bi bi-printer me-2"></i>Imprimir PDF
                </button>
            </div>
        </div>
    </div>

    {{-- CABECERA EXCLUSIVA PARA IMPRESIÓN --}}
    <div class="d-none d-print-block mb-4">
        <div class="text-center">
            <h1 class="fw-bold mb-1">REPORTE DE VENTAS</h1>
            <p class="mb-0 text-uppercase">Aliado: {{ auth()->user()->name }}</p>
            <p class="small">Periodo: {{ \Carbon\Carbon::parse($fromDate)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($toDate)->format('d/m/Y') }}</p>
        </div>
        <hr>
    </div>

    {{-- INDICADORES --}}
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-dark text-white h-100">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="small fw-bold opacity-75 text-uppercase">Total Estimado USD</h6>
                        <h2 class="fw-bold mb-0">${{ number_format($stats['total_usd'], 2) }}</h2>
                        <small class="opacity-50">Venta bruta (Físico + Digital)</small>
                    </div>
                    <i class="bi bi-currency-dollar display-5 opacity-25"></i>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-primary text-white h-100">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="small fw-bold opacity-75 text-uppercase">Recaudación Bs. (Pasarela)</h6>
                        <h2 class="fw-bold mb-0">Bs. {{ number_format($stats['total_bs'], 2, ',', '.') }}</h2>
                        <small class="opacity-50">Ingresos liquidados en Banco</small>
                    </div>
                    <i class="bi bi-bank display-5 opacity-25"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- TABLA DE VENTAS --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden border-print-0">
        <div class="card-header bg-white border-bottom py-3 d-print-none">
            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-receipt-cutoff me-2"></i>Historial de Movimientos ({{ $stats['conteo'] }})</h6>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="bg-light small fw-bold text-muted">
                    <tr>
                        <th class="ps-4">FECHA / HORA</th>
                        <th>CONCEPTO</th>
                        <th>ROUTER</th>
                        <th>MÉTODO</th>
                        <th class="text-end">BOLÍVARES</th>
                        <th class="text-end pe-4">DÓLARES</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $sale)
                    <tr>
                        <td class="ps-4">
                            <span class="small d-block fw-bold text-dark">{{ $sale->created_at->format('d/m/Y') }}</span>
                            <span class="text-muted" style="font-size: 0.75rem;">{{ $sale->created_at->format('h:i A') }}</span>
                        </td>
                        <td>
                            <span class="fw-bold text-dark d-block" style="font-size: 0.9rem;">{{ $sale->description }}</span>
                            <code class="text-muted d-print-none" style="font-size: 0.7rem;">Ref: {{ $sale->reference_id }}</code>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border rounded-pill px-2">
                                <i class="bi bi-cpu me-1 d-print-none"></i>{{ $sale->router->identity ?? 'N/A' }}
                            </span>
                        </td>
                        <td>
                            @if($sale->type == 'pasarela')
                                <span class="badge bg-info-soft text-info rounded-pill px-3 small">PASARELA</span>
                            @else
                                <span class="badge bg-success-soft text-success rounded-pill px-3 small">TICKET</span>
                            @endif
                        </td>
                        <td class="text-end fw-bold text-primary">
                            {{ $sale->amount_bs > 0 ? 'Bs. '.number_format($sale->amount_bs, 2, ',', '.') : '-' }}
                        </td>
                        <td class="text-end pe-4 fw-bold text-dark">
                            ${{ number_format($sale->amount_usd, 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <span class="text-muted">No hay movimientos registrados.</span>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .bg-info-soft { background-color: rgba(13, 202, 240, 0.1); }
    .bg-success-soft { background-color: rgba(25, 135, 84, 0.1); }
    .table thead th { font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.8px; }
    
    /* ESTILOS PARA IMPRESIÓN */
    @media print {
        @page { size: portrait; margin: 1cm; }
        body { background: white !important; font-size: 10pt; }
        .sidebar-rednet, #toggle-sidebar, .navbar, .d-print-none, .btn { display: none !important; }
        .container-fluid { width: 100% !important; padding: 0 !important; margin: 0 !important; }
        .card { border: none !important; box-shadow: none !important; }
        .card-header { padding: 0 !important; }
        .table { width: 100% !important; border-collapse: collapse !important; }
        .table th, .table td { border-bottom: 1px solid #ddd !important; padding: 8px !important; }
        .badge { border: 1px solid #ccc !important; background: transparent !important; color: black !important; padding: 2px 5px !important; }
        .bg-dark, .bg-primary { background-color: #f8f9fa !important; color: black !important; border: 1px solid #ddd !important; }
        .text-white { color: black !important; }
    }
</style>