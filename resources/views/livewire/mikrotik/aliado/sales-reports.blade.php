<div class="container-fluid py-4">
    {{-- CABECERA Y FILTROS --}}
    <div class="row mb-4 align-items-center d-print-none">
        <div class="col-xl-4 col-lg-5 mb-3 mb-xl-0">
            <h2 class="fw-bold text-dark mb-0">Balance de Ingresos</h2>
            <p class="text-muted small mb-0">Reporte bimoneda detallado por equipo y método.</p>
        </div>
        
        <div class="col-xl-8 col-lg-7">
            <div class="d-flex flex-wrap justify-content-xl-end align-items-center gap-2">
                {{-- TASA BCV --}}
                <div class="bg-gradient-dark rounded-pill px-3 py-2 shadow-sm d-flex align-items-center me-2">
                    <i class="bi bi-bank text-warning me-2"></i>
                    <span class="text-white small fw-bold text-uppercase me-2" style="font-size: 0.65rem; letter-spacing: 1px;">Tasa BCV:</span>
                    <span class="text-white fw-bold">Bs. {{ number_format($currentRate, 2, ',', '.') }}</span>
                </div>

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

                <button onclick="window.print()" class="btn btn-primary rounded-pill px-3 shadow-sm">
                    <i class="bi bi-printer me-1"></i> PDF
                </button>
            </div>
        </div>
    </div>

    {{-- CABECERA EXCLUSIVA PARA IMPRESIÓN --}}
    <div class="d-none d-print-block mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="fw-bold mb-1">REPORTE DE VENTAS</h1>
                <p class="mb-0 text-uppercase">Aliado: {{ auth()->user()->name }}</p>
                <p class="small">Periodo: {{ \Carbon\Carbon::parse($fromDate)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($toDate)->format('d/m/Y') }}</p>
            </div>
            <div class="text-end">
                <p class="mb-0 fw-bold">Tasa Referencial BCV</p>
                <h3>Bs. {{ number_format($currentRate, 2, ',', '.') }}</h3>
            </div>
        </div>
        <hr>
    </div>

    {{-- INDICADORES --}}
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-dark text-white h-100 position-relative overflow-hidden">
                <div class="position-relative z-index-2">
                    <h6 class="small fw-bold opacity-75 text-uppercase mb-1">Total Estimado USD</h6>
                    <h2 class="fw-bold mb-0">${{ number_format($stats['total_usd'], 2) }}</h2>
                    <p class="small opacity-50 mb-0 mt-2"><i class="bi bi-info-circle me-1"></i>Venta bruta acumulada</p>
                </div>
                <i class="bi bi-currency-dollar position-absolute end-0 bottom-0 display-1 opacity-10 mb-n3 me-n2"></i>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-primary text-white h-100 position-relative overflow-hidden">
                <div class="position-relative z-index-2">
                    <h6 class="small fw-bold opacity-75 text-uppercase mb-1">Recaudación Bs. (Pasarela)</h6>
                    <h2 class="fw-bold mb-0">Bs. {{ number_format($stats['total_bs'], 2, ',', '.') }}</h2>
                    <p class="small opacity-50 mb-0 mt-2"><i class="bi bi-bank me-1"></i>Ingresos por BioPago / Pago Móvil</p>
                </div>
                <i class="bi bi-wallet2 position-absolute end-0 bottom-0 display-1 opacity-10 mb-n3 me-n2"></i>
            </div>
        </div>
    </div>

    {{-- TABLA DE VENTAS --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center d-print-none">
            <h6 class="fw-bold mb-0 text-dark">
                <i class="bi bi-receipt-cutoff text-primary me-2"></i>Historial de Movimientos 
                <span class="badge bg-light text-dark ms-2">{{ $stats['conteo'] }} registros</span>
            </h6>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="bg-light small fw-bold text-muted">
                    <tr>
                        <th class="ps-4">FECHA / HORA</th>
                        <th>CONCEPTO / REFERENCIA</th>
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
                            <span class="fw-bold text-dark d-block" style="font-size: 0.85rem;">{{ $sale->description }}</span>
                            <span class="text-muted font-monospace d-print-none" style="font-size: 0.65rem;">REF: {{ $sale->reference_id }}</span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border-0 rounded px-2" style="font-size: 0.7rem;">
                                {{ $sale->router->identity ?? 'N/A' }}
                            </span>
                        </td>
                        <td>
                            @if($sale->type == 'pasarela')
                                <span class="badge bg-info-soft text-info rounded-pill px-3 small" style="font-size: 0.65rem;">PASARELA</span>
                            @else
                                <span class="badge bg-success-soft text-success rounded-pill px-3 small" style="font-size: 0.65rem;">TICKET</span>
                            @endif
                        </td>
                        <td class="text-end fw-bold text-primary" style="font-size: 0.9rem;">
                            {{ $sale->amount_bs > 0 ? 'Bs. '.number_format($sale->amount_bs, 2, ',', '.') : '-' }}
                        </td>
                        <td class="text-end pe-4 fw-bold text-dark" style="font-size: 0.9rem;">
                            ${{ number_format($sale->amount_usd, 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <i class="bi bi-inbox display-4 text-light d-block mb-2"></i>
                            <span class="text-muted">No se encontraron movimientos en este rango de fechas.</span>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .bg-gradient-dark { background: linear-gradient(135deg, #191c1f 0%, #3a4149 100%); }
    .bg-info-soft { background-color: rgba(13, 202, 240, 0.12); }
    .bg-success-soft { background-color: rgba(25, 135, 84, 0.12); }
    .table thead th { font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.8px; padding-top: 1rem; padding-bottom: 1rem; }
    
    @media print {
        @page { size: portrait; margin: 1cm; }
        .bg-gradient-dark { background: #f8f9fa !important; border: 1px solid #000 !important; }
        .bg-gradient-dark span { color: #000 !important; }
        .d-print-none { display: none !important; }
        .card { border: 1px solid #eee !important; box-shadow: none !important; }
        .table td { padding: 6px !important; }
    }
</style>