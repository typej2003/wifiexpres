<div class="container-fluid py-4">

    @if (session()->has('message'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('message') }}
        </div>
    @endif

    {{-- HEADER --}}
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h2 class="fw-bold text-dark mb-0">Dashboard Aliado</h2>
            <div class="d-flex align-items-center gap-2 mt-1">
                <span class="badge bg-primary-soft text-primary border border-primary rounded-pill px-3">
                    <i class="bi bi-currency-exchange me-1"></i> BCV: <strong>Bs. {{ number_format($dollarRate, 2, ',', '.') }}</strong>
                </span>
            </div>
        </div>
        <div class="col-md-6 text-end">
            <div class="bg-white p-2 px-3 rounded-4 shadow-sm border d-inline-block text-start me-2">
                <small class="text-muted d-block fw-bold text-uppercase" style="font-size: 0.6rem;">Capacidad Routers</small>
                <span class="fw-bold {{ $stats['total_routers'] >= $stats['limit_routers'] ? 'text-danger' : 'text-primary' }}">
                    {{ $stats['total_routers'] }} / {{ $stats['limit_routers'] }}
                </span>
            </div>
        </div>
    </div>

    {{-- FILTROS DINÁMICOS --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="small fw-bold text-muted mb-1">EQUIPO</label>
                    <select wire:model="router_id" class="form-select border-0 bg-light rounded-3 shadow-none">
                        <option value="">📊 Todos los Routers</option>
                        @foreach($routers as $r)
                            <option value="{{ $r->id }}">{{ $r->identity }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold text-muted mb-1">RANGO</label>
                    <select wire:model="periodo" class="form-select border-0 bg-light rounded-3 shadow-none">
                        <option value="dia">Hoy</option>
                        <option value="semana">Semana</option>
                        <option value="mes">Mes</option>
                        <option value="personalizado">Personalizado</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold text-muted mb-1">DESDE</label>
                    <input type="date" wire:model="fecha_desde" class="form-control border-0 bg-light rounded-3 shadow-none" {{ $periodo != 'personalizado' ? 'disabled' : '' }}>
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold text-muted mb-1">HASTA</label>
                    <input type="date" wire:model="fecha_hasta" class="form-control border-0 bg-light rounded-3 shadow-none" {{ $periodo != 'personalizado' ? 'disabled' : '' }}>
                </div>
                <div class="col-md-3 text-end">
                    <button wire:click="openModal" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm w-100">
                        <i class="bi bi-plus-circle me-1"></i> GESTIONAR PLANES
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- TABLA DE PLANES ACTIVOS --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="bg-light small fw-bold">
                    <tr>
                        <th class="ps-4">PLAN</th>
                        <th>TIPO</th>
                        <th>CAPACIDAD</th>
                        <th>VENCIMIENTO</th>
                        <th class="text-end pe-4">ESTADO</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($activePlans as $plan)
                    <tr>
                        <td class="ps-4 fw-bold">{{ $plan->name }}</td>
                        <td><span class="badge bg-primary rounded-pill">{{ strtoupper($plan->service_type) }}</span></td>
                        <td class="fw-bold">{{ $plan->pivot->allowed_routers }} Routers</td>
                        <td>{{ \Carbon\Carbon::parse($plan->pivot->end_date)->format('d/m/Y') }}</td>
                        <td class="text-end pe-4"><span class="badge bg-success rounded-pill px-3">Activo</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- STATS CARDS --}}
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center bg-white">
                <h6 class="text-muted small fw-bold">ROUTERS</h6>
                <h2 class="fw-bold mb-0">{{ $stats['total_routers'] }}</h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center bg-white">
                <h6 class="text-muted small fw-bold">TICKETS TOTAL</h6>
                <h2 class="fw-bold mb-0 text-info">{{ $stats['total_tickets'] }}</h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center bg-white">
                <h6 class="text-muted small fw-bold">ONLINE</h6>
                <h2 class="fw-bold mb-0 text-success">{{ $stats['tickets_activos'] }}</h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center bg-white">
                <h6 class="text-muted small fw-bold">SESIONES FILTRO</h6>
                <h2 class="fw-bold mb-0 text-primary">{{ number_format($stats['conexiones_periodo']) }}</h2>
            </div>
        </div>
    </div>

    {{-- GRÁFICA Y TOP USUARIOS --}}
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                <h6 class="fw-bold mb-4">Comparativa de Carga por Router</h6>
                <div style="position: relative; height:400px;" wire:ignore>
                    <canvas id="multiBarChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <h6 class="fw-bold mb-3">Top Usuarios</h6>
                @foreach($topUsuarios as $u)
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <span class="fw-bold d-block">{{ $u->username }}</span>
                        <small class="text-muted">{{ number_format($u->total_conexiones) }} conexiones</small>
                    </div>
                    <span class="badge bg-light text-dark border rounded-pill">{{ round($u->tiempo_total/3600, 1) }}h</span>
                </div>
                @endforeach
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-50">
                <div class="card-header bg-white border-0 pt-3">
                    <h6 class="fw-bold mb-0">Actividad Reciente</h6>
                </div>
                <div class="list-group list-group-flush" style="max-height: 250px; overflow-y: auto;">
                    @foreach($ultimosLogs as $log)
                        <div class="list-group-item border-0 px-4 py-2 small d-flex justify-content-between border-bottom">
                            <span>{{ $log->username }}</span>
                            <span class="text-muted" style="font-size: 0.7rem;">{{ $log->created_at->diffForHumans() }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL PLANES --}}
    @if($showPlanModal)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.7); backdrop-filter: blur(8px); z-index: 2050;">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-dark text-white p-4">
                    <h5 class="fw-bold mb-0">Suscripciones WiFiExpres</h5>
                    <button wire:click="closeModal" class="btn-close btn-close-white shadow-none"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <div class="row g-4">
                        @foreach($availablePackages as $package)
                            <div class="col-md-4">
                                <div class="card border-0 shadow-sm rounded-4 h-100 text-center">
                                    <div class="card-body p-4">
                                        <h4 class="fw-bold">{{ $package->name }}</h4>
                                        <div class="display-6 fw-bold my-3 text-primary">${{ $package->cost }}</div>
                                        <ul class="list-unstyled text-start small mb-4">
                                            <li><i class="bi bi-check2 text-success me-2"></i>{{ $package->limit_routers }} Router(s)</li>
                                            <li><i class="bi bi-check2 text-success me-2"></i>{{ $package->duration_months }} Mes(es) de servicio</li>
                                        </ul>
                                        <button wire:click="selectPlan({{ $package->id }})" class="btn btn-dark w-100 rounded-pill fw-bold">ADQUIRIR</button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@push('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let multiChart;
    function renderMultiChart(labels, datasets) {
        const ctx = document.getElementById('multiBarChart').getContext('2d');
        if (multiChart) multiChart.destroy();
        multiChart = new Chart(ctx, {
            type: 'bar',
            data: { labels: labels, datasets: datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true } } },
                scales: { 
                    y: { beginAtZero: true, grid: { color: '#f0f0f0' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    document.addEventListener('livewire:load', () => { renderMultiChart(@json($labels), @json($datasets)); });
    window.addEventListener('updateMultiChart', event => { renderMultiChart(event.detail.labels, event.detail.datasets); });
</script>
@endpush