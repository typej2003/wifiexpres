<div class="container-fluid py-4">

    @if (session()->has('message'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('message') }}
        </div>
    @endif

    {{-- ALERTAS DE PLANES PENDIENTES --}}
    @forelse($pendingPlans as $p)
        <div class="alert alert-info border-0 shadow-sm rounded-4 mb-3 d-flex align-items-center">
            <div class="spinner-border spinner-border-sm me-3"></div>
            <span>El plan <strong>{{ $p->name }}</strong> está pendiente de activación ({{ $p->limit_routers }} routers).</span>
        </div>
    @empty
    @endforelse

    @if($activePlans->isEmpty() && $pendingPlans->isEmpty())
        <div class="row justify-content-center my-5">
            <div class="col-md-8 text-center">
                <div class="card border-0 shadow-lg rounded-4 p-5">
                    <i class="bi bi-shield-lock display-1 text-primary mb-4"></i>
                    <h2 class="fw-bold">Acceso Restringido</h2>
                    <p class="text-muted fs-5">Para habilitar el monitoreo de sus equipos MikroTik, debe adquirir un plan comercial.</p>
                    <button wire:click="openModal" class="btn btn-primary btn-lg rounded-pill px-5 mt-3 shadow-lg fw-bold">VER PLANES DISPONIBLES</button>
                </div>
            </div>
        </div>
    @else
        {{-- HEADER CON TASA DE CAMBIO --}}
        <div class="row mb-4 align-items-center">
            <div class="col-md-6">
                <h2 class="fw-bold text-dark mb-0">Dashboard Aliado</h2>
                <div class="d-flex align-items-center gap-2 mt-1">
                    <p class="text-muted mb-0 small">Rendimiento de red y suscripciones.</p>
                    <span class="badge bg-primary-soft text-primary border border-primary rounded-pill px-3" style="font-size: 0.7rem;">
                        <i class="bi bi-currency-exchange me-1"></i> Tasa BCV: <strong>Bs. {{ number_format($dollarRate, 2, ',', '.') }}</strong>
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
                <div class="btn-group bg-white p-1 rounded-pill border shadow-sm">
                    <button wire:click="setPeriod('today')" class="btn btn-sm rounded-pill px-3 {{ $period == 'today' ? 'btn-dark' : 'btn-white border-0' }}">Hoy</button>
                    <button wire:click="setPeriod('weekly')" class="btn btn-sm rounded-pill px-3 {{ $period == 'weekly' ? 'btn-dark' : 'btn-white border-0' }}">Semana</button>
                    <button wire:click="setPeriod('month')" class="btn btn-sm rounded-pill px-3 {{ $period == 'month' ? 'btn-dark' : 'btn-white border-0' }}">Mes</button>
                </div>
            </div>
        </div>

        {{-- TABLA DE PLANES --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0">Suscripciones Activas</h6>
                <button wire:click="openModal" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold">+ ADQUIRIR MÁS</button>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="bg-light small fw-bold text-muted">
                        <tr>
                            <th class="ps-4">PLAN</th>
                            <th>TIPO</th>
                            <th>CAPACIDAD</th>
                            <th>FECHA VENCIMIENTO</th>
                            <th class="text-end pe-4">ESTADO</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($activePlans as $plan)
                        <tr>
                            <td class="ps-4 fw-bold">{{ $plan->name }}</td>
                            <td><span class="badge {{ $plan->service_type == 'cortesia' ? 'bg-success' : 'bg-primary' }} rounded-pill">{{ strtoupper($plan->service_type) }}</span></td>
                            <td class="fw-bold text-dark">{{ $plan->limit_routers }} Router(s)</td>
                            <td>{{ optional($plan->pivot)->end_date ? \Carbon\Carbon::parse($plan->pivot->end_date)->format('d/m/Y') : 'N/A' }}</td>
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
                <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                    <h6 class="text-muted small fw-bold">ROUTERS</h6>
                    <h2 class="fw-bold mb-0 text-dark">{{ $stats['total_routers'] }}</h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                    <h6 class="text-muted small fw-bold">TICKETS</h6>
                    <h2 class="fw-bold mb-0 text-info">{{ $stats['total_tickets'] }}</h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                    <h6 class="text-muted small fw-bold">ONLINE</h6>
                    <h2 class="fw-bold mb-0 text-success">{{ $stats['tickets_activos'] }}</h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                    <h6 class="text-muted small fw-bold">LOGINS ({{ strtoupper($period) }})</h6>
                    <h2 class="fw-bold mb-0 text-warning">{{ $stats['conexiones_periodo'] }}</h2>
                </div>
            </div>
        </div>

        {{-- GRÁFICA Y ACTIVIDAD --}}
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 text-center">
                    <h6 class="fw-bold mb-4">Distribución de Conexiones por Router</h6>
                    <div style="position: relative; height: 350px;" wire:ignore>
                        <canvas id="chartRoutersAliado"></canvas>
                    </div>
                    <div class="mt-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Total General</h6>
                        <h3 class="fw-bold text-primary">{{ number_format($totalGeneral) }}</h3>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white border-0 py-3">
                        <h6 class="fw-bold mb-0">Detalle de Equipos</h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="bg-light small fw-bold">
                                <tr>
                                    <th class="ps-4">IDENTITY</th>
                                    <th>COMERCIO</th>
                                    <th class="text-center">ESTADO</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($routers as $r)
                                <tr>
                                    <td class="ps-4"><code>{{ $r->identity }}</code></td>
                                    <td>{{ $r->comercio_nombre }}</td>
                                    <td class="text-center">
                                        <span class="badge {{ $r->status == 'Habilitado' ? 'bg-success' : 'bg-danger' }} rounded-pill px-3">
                                            {{ strtoupper($r->status) }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="text-center py-4 text-muted">Sin routers configurados.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                    <div class="card-header bg-white border-0 pt-4 px-4">
                        <h5 class="fw-bold mb-0">Actividad Reciente</h5>
                    </div>
                    <div class="list-group list-group-flush mt-3" style="max-height: 500px; overflow-y: auto;">
                        @forelse($ultimosLogs as $log)
                            <div class="list-group-item border-0 px-4 py-3 small d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="fw-bold d-block text-dark">
                                        <a href="/mikrotik/user-history/{{$log->username}}">{{ $log->username }}</a>
                                    </span>
                                    <span class="text-muted x-small">{{ $log->router->identity ?? 'MikroTik' }}</span>
                                </div>
                                <span class="text-muted" style="font-size: 0.7rem;">{{ $log->created_at->diffForHumans() }}</span>
                            </div>
                        @empty
                            <div class="text-center py-5 text-muted small">Sin actividad en este periodo</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL DE PLANES (ESTRUCTURA ORIGINAL) --}}
    @if($showPlanModal)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.7); backdrop-filter: blur(8px); z-index: 2050;">
        <div class="modal-dialog modal-xl" style="margin-top: 8rem;">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-dark text-white p-4">
                    <h5 class="modal-title fw-bold"><i class="bi bi-rocket-takeoff me-2"></i>Escala tu Negocio Hotspot</h5>
                    @if($activePlans->isNotEmpty() || $pendingPlans->isNotEmpty())
                        <button type="button" wire:click="closeModal" class="btn-close btn-close-white shadow-none"></button>
                    @endif
                </div>
                <div class="modal-body p-4 bg-light">
                    <div class="row g-4">
                        @foreach($availablePackages as $package)
                            <div class="col-md-4">
                                <div class="card border-0 shadow-sm rounded-4 h-100 text-center transition-card">
                                    <div class="p-2 {{ $package->service_type == 'cortesia' ? 'bg-success' : 'bg-primary' }} text-white small fw-bold">
                                        {{ strtoupper($package->service_type) }}
                                    </div>
                                    <div class="card-body p-4">
                                        <h4 class="fw-bold">{{ $package->name }}</h4>
                                        <div class="display-6 fw-bold my-3 text-dark">${{ number_format($package->cost, 2) }}</div>
                                        <ul class="list-unstyled text-start small mb-4">
                                            <li class="mb-2"><i class="bi bi-router-fill text-primary me-2"></i><strong>{{ $package->limit_routers }}</strong> Router(s)</li>
                                            <li class="mb-2"><i class="bi bi-calendar-check text-primary me-2"></i>{{ $package->duration_months }} Mes(es) de vigencia</li>
                                            <li class="mb-2"><i class="bi bi-display text-primary me-2"></i>Portal: {{ $package->hotspotVersion->name ?? 'Estándar' }}</li>
                                        </ul>
                                        <button wire:click="selectPlan({{ $package->id }})" class="btn btn-dark w-100 rounded-pill fw-bold">ADQUIRIR PLAN</button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                @if($activePlans->isNotEmpty() || $pendingPlans->isNotEmpty())
                <div class="modal-footer bg-white border-0 p-3">
                    <button type="button" wire:click="closeModal" class="btn btn-light rounded-pill px-4">Cerrar</button>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>

<style>
    .bg-primary-soft { background-color: rgba(13, 110, 253, 0.1); }
    .transition-card { transition: transform 0.3s ease; }
    .transition-card:hover { transform: translateY(-5px); }
    .list-group::-webkit-scrollbar { width: 4px; }
    .list-group::-webkit-scrollbar-track { background: #f1f1f1; }
    .list-group::-webkit-scrollbar-thumb { background: #888; border-radius: 10px; }
</style>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    function initChart() {
        const el = document.getElementById('chartRoutersAliado');
        if (!el) return;

        // Limpiar gráfico previo para que no queden rastros al cambiar de periodo
        const existingChart = Chart.getChart("chartRoutersAliado");
        if (existingChart) {
            existingChart.destroy();
        }

        const ctx = el.getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: @json($chartLabels),
                datasets: [{
                    data: @json($chartData),
                    backgroundColor: ['#0d6efd', '#212529', '#0dcaf0', '#198754', '#ffc107', '#6610f2'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }

    document.addEventListener('livewire:load', () => {
        initChart();
        // Escucha el evento del controlador para redibujar al cambiar el periodo
        Livewire.on('updateChart', () => {
            setTimeout(() => { initChart(); }, 100);
        });
    });
</script>
@endpush