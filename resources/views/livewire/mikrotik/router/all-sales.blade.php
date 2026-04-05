<div class="container-fluid py-4">
    {{-- FILTROS DE ADMINISTRACIÓN --}}
    <div class="row mb-4 align-items-center d-print-none">
        <div class="col-xl-4 col-lg-12">
            <h2 class="fw-bold text-dark mb-0">Consolidado de Ventas</h2>
            <p class="text-muted small">Vista administrativa de todos los aliados.</p>
        </div>
        
        <div class="col-xl-8 col-lg-12">
            <div class="d-flex flex-wrap justify-content-xl-end align-items-center gap-2">
                
                {{-- Selector de Aliado --}}
                <div class="me-2">
                    <select wire:model="selectedAliado" class="form-select form-select-sm rounded-pill shadow-sm border-0 px-3">
                        <option value="">Todos los Aliados</option>
                        @foreach($aliados as $aliado)
                            <option value="{{ $aliado->id }}">{{ $aliado->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Rango de Fechas --}}
                <div class="d-flex align-items-center bg-white border rounded-pill px-3 py-1 shadow-sm me-2">
                    <div class="d-flex align-items-center me-2">
                        <label class="small text-muted me-2 mb-0">Desde:</label>
                        <input type="date" wire:model="fromDate" class="form-control form-control-sm border-0 p-0 shadow-none" style="width: 115px;">
                    </div>
                    <div class="d-flex align-items-center border-start ps-2">
                        <label class="small text-muted me-2 mb-0">Hasta:</label>
                        <input type="date" wire:model="toDate" class="form-control form-control-sm border-0 p-0 shadow-none" style="width: 115px;">
                    </div>
                </div>

                <div class="btn-group bg-white p-1 rounded-pill border shadow-sm me-2">
                    <button wire:click="setPeriod('today')" class="btn btn-sm rounded-pill px-3 {{ $period == 'today' ? 'btn-dark' : 'btn-white border-0' }}">Hoy</button>
                    <button wire:click="setPeriod('weekly')" class="btn btn-sm rounded-pill px-3 {{ $period == 'weekly' ? 'btn-dark' : 'btn-white border-0' }}">Semana</button>
                    <button wire:click="setPeriod('month')" class="btn btn-sm rounded-pill px-3 {{ $period == 'month' ? 'btn-dark' : 'btn-white border-0' }}">Mes</button>
                </div>

                <button onclick="window.print()" class="btn btn-primary rounded-pill px-4 shadow-sm">
                    <i class="bi bi-printer me-2"></i>Imprimir
                </button>
            </div>
        </div>
    </div>

    {{-- TARJETAS DE TOTALES GLOBALES --}}
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-dark text-white">
                <h6 class="small fw-bold opacity-75 text-uppercase">Total Red (USD)</h6>
                <h3 class="fw-bold mb-0">${{ number_format($stats['total_usd'], 2) }}</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-primary text-white">
                <h6 class="small fw-bold opacity-75 text-uppercase">Total Red (Bs)</h6>
                <h3 class="fw-bold mb-0">Bs. {{ number_format($stats['total_bs'], 2, ',', '.') }}</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border">
                <h6 class="small fw-bold text-muted text-uppercase">Transacciones</h6>
                <h3 class="fw-bold mb-0 text-dark">{{ $stats['conteo'] }}</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border">
                <h6 class="small fw-bold text-muted text-uppercase">Aliados con Venta</h6>
                <h3 class="fw-bold mb-0 text-dark">{{ $stats['aliados_activos'] }}</h3>
            </div>
        </div>
    </div>

    {{-- TABLA GLOBAL --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="bg-light small fw-bold text-muted">
                    <tr>
                        <th class="ps-4">FECHA</th>
                        <th>ALIADO</th>
                        <th>CONCEPTO</th>
                        <th>ROUTER</th>
                        <th class="text-end">BS. PAGADOS</th>
                        <th class="text-end pe-4">VALOR USD</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $sale)
                    <tr>
                        <td class="ps-4">
                            <span class="small d-block fw-bold text-dark">{{ $sale->created_at->format('d/m/Y') }}</span>
                            <span class="text-muted" style="font-size: 0.7rem;">{{ $sale->created_at->format('h:i A') }}</span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 25px; height: 25px; font-size: 0.7rem;">
                                    {{ substr($sale->user->name, 0, 1) }}
                                </div>
                                <span class="fw-bold text-dark" style="font-size: 0.85rem;">{{ $sale->user->name }}</span>
                            </div>
                        </td>
                        <td>
                            <span class="small d-block text-truncate" style="max-width: 150px;">{{ $sale->description }}</span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border rounded-pill px-2">
                                {{ $sale->router->identity ?? 'N/A' }}
                            </span>
                        </td>
                        <td class="text-end fw-bold text-primary">
                            {{ $sale->amount_bs > 0 ? number_format($sale->amount_bs, 4, ',', '.') : '-' }}
                        </td>
                        <td class="text-end pe-4 fw-bold text-dark">
                            ${{ number_format($sale->amount_usd, 4) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">No hay registros de ventas globales en este rango.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .table thead th { font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.8px; }
    @media print {
        .d-print-none, .btn, .avatar, select { display: none !important; }
        .card { border: none !important; box-shadow: none !important; }
        body { background: white !important; }
    }
</style>