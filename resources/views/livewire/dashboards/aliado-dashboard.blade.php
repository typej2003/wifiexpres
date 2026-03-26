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
        {{-- HEADER --}}
        <div class="row mb-4 align-items-center">
            <div class="col-md-6">
                <h2 class="fw-bold text-dark mb-0">Dashboard Aliado</h2>
                <div class="d-flex align-items-center gap-2 mt-1">
                    <p class="text-muted mb-0 small">Rendimiento y Tráfico de Red.</p>
                    <span class="badge bg-primary-soft text-primary border border-primary rounded-pill px-3" style="font-size: 0.7rem;">
                        <i class="bi bi-currency-exchange me-1"></i> Tasa BCV: <strong>Bs. {{ number_format($dollarRate, 2, ',', '.') }}</strong>
                    </span>
                </div>
            </div>
            <div class="col-md-6 text-end">
                <div class="bg-white p-2 px-3 rounded-4 shadow-sm border d-inline-block text-start me-2">
                    <small class="text-muted d-block fw-bold text-uppercase" style="font-size: 0.6rem;">Cupos Utilizados</small>
                    <span class="fw-bold {{ $stats['total_routers'] >= $stats['limit_routers'] ? 'text-danger' : 'text-primary' }}">
                        {{ $stats['total_routers'] }} / {{ $stats['limit_routers'] }}
                    </span>
                </div>
                <div class="btn-group bg-white p-1 rounded-pill border shadow-sm">
                    <button wire:click="setPeriod('today')" class="btn btn-sm rounded-pill px-3 {{ $period == 'today' ? 'btn-dark text-white' : 'btn-white border-0' }}">Hoy</button>
                    <button wire:click="setPeriod('weekly')" class="btn btn-sm rounded-pill px-3 {{ $period == 'weekly' ? 'btn-dark text-white' : 'btn-white border-0' }}">Semana</button>
                    <button wire:click="setPeriod('month')" class="btn btn-sm rounded-pill px-3 {{ $period == 'month' ? 'btn-dark text-white' : 'btn-white border-0' }}">Mes</button>
                </div>
            </div>
        </div>

        {{-- KPI CARDS --}}
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                    <h6 class="text-muted small fw-bold mb-2">EQUIPOS</h6>
                    <h2 class="fw-bold mb-0 text-dark">{{ $stats['total_routers'] }}</h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                    <h6 class="text-muted small fw-bold mb-2">TICKETS EMITIDOS</h6>
                    <h2 class="fw-bold mb-0 text-info">{{ $stats['total_tickets'] }}</h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                    <h6 class="text-muted small fw-bold mb-2">USUARIOS NAVEGANDO</h6>
                    <h2 class="fw-bold mb-0 text-success">{{ $stats['tickets_activos'] }}</h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                    <h6 class="text-muted small fw-bold mb-2">CONEXIONES ({{ strtoupper($period) }})</h6>
                    <h2 class="fw-bold mb-0 text-warning">{{ $stats['conexiones_periodo'] }}</h2>
                </div>
            </div>
        </div>

        {{-- GRÁFICA DE BARRAS --}}
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h6 class="fw-bold mb-0">Tráfico de Red por Dispositivo</h6>
                <small class="text-muted small">Eje Y: Cantidad de Conexiones</small>
            </div>
            <div style="height: 400px;">
                <canvas id="aliadoTrafficChart"></canvas>
            </div>
        </div>

        <div class="row g-4">
            {{-- TABLA DE EQUIPOS --}}
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white border-0 py-3">
                        <h6 class="fw-bold mb-0">Equipos Registrados</h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="bg-light small fw-bold">
                                <tr>
                                    <th class="ps-4">IDENTITY</th>
                                    <th>INFO</th>
                                    <th class="text-center">ESTADO</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($routers as $r)
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-light rounded-3 p-2 me-3">
                                                <i class="bi bi-cpu text-primary"></i>
                                            </div>
                                            <code class="fw-bold">{{ $r->identity }}</code>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="d-block fw-bold text-dark small">{{ $r->comercio_nombre }}</span>
                                        <small class="text-muted" style="font-size: 0.65rem;">Actualizado: {{ $r->updated_at->diffForHumans() }}</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $r->status == 'Habilitado' ? 'bg-success' : 'bg-danger' }} rounded-pill px-3">
                                            {{ strtoupper($r->status) }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="text-center py-5 text-muted">Sin equipos en el sistema.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- LOGS EN VIVO --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                    <div class="card-header bg-white border-0 pt-4 px-4">
                        <h6 class="fw-bold mb-0">Últimos Inicios de Sesión</h6>
                    </div>
                    <div class="list-group list-group-flush mt-2">
                        @forelse($ultimosLogs as $log)
                            <div class="list-group-item border-0 px-4 py-3 d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <div class="bg-light rounded-circle p-2 me-3" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                                        <i class="bi bi-lightning-fill text-warning" style="font-size: 0.8rem;"></i>
                                    </div>
                                    <div>
                                        <span class="fw-bold d-block text-dark" style="font-size: 0.75rem;">{{ $log->username }}</span>
                                        <span class="text-muted d-block" style="font-size: 0.65rem;">
                                            {{ $log->router->identity ?? 'Router' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="badge {{ is_null($log->disconnected_at) ? 'bg-success' : 'bg-light text-muted' }} rounded-pill mb-1 d-block" style="font-size: 0.6rem;">
                                        {{ is_null($log->disconnected_at) ? 'ONLINE' : $log->duracion_formateada }}
                                    </span>
                                    <small class="text-muted" style="font-size: 0.6rem;">{{ $log->created_at->format('h:i A') }}</small>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-5 text-muted small">Esperando tráfico...</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL PLANES --}}
    @if($showPlanModal)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.6); backdrop-filter: blur(5px); z-index: 2050;">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 p-4">
                    <h5 class="fw-bold mb-0"><i class="bi bi-box-seam me-2"></i>Planes Disponibles</h5>
                    @if($activePlans->isNotEmpty() || $pendingPlans->isNotEmpty())
                        <button type="button" wire:click="closeModal" class="btn-close shadow-none"></button>
                    @endif
                </div>
                <div class="modal-body p-4 bg-light">
                    <div class="row g-4">
                        @foreach($availablePackages as $package)
                            <div class="col-md-4">
                                <div class="card border-0 shadow-sm rounded-4 h-100 transition-card">
                                    <div class="card-body p-4 text-center">
                                        <span class="badge bg-primary-soft text-primary mb-3">{{ strtoupper($package->service_type) }}</span>
                                        <h4 class="fw-bold text-dark">{{ $package->name }}</h4>
                                        <div class="display-6 fw-bold my-3 text-dark">${{ number_format($package->cost, 2) }}</div>
                                        <hr class="my-4 opacity-25">
                                        <ul class="list-unstyled text-start mb-4">
                                            <li class="mb-2 small"><i class="bi bi-hdd-network text-success me-2"></i>{{ $package->limit_routers }} Router(s)</li>
                                            <li class="mb-2 small"><i class="bi bi-clock-history text-success me-2"></i>{{ $package->duration_months }} meses</li>
                                        </ul>
                                        <button wire:click="selectPlan({{ $package->id }})" class="btn btn-dark w-100 rounded-pill fw-bold">SOLICITAR PLAN</button>
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

<style>
    .bg-primary-soft { background-color: rgba(13, 110, 253, 0.1); }
    .transition-card:hover { transform: translateY(-5px); transition: 0.3s ease; }
    code { font-size: 0.85rem; color: #d63384; }
</style>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('livewire:load', function () {
        let chart;
        const ctx = document.getElementById('aliadoTrafficChart');

        function renderChart(chartData) {
            if(!ctx) return;
            if(chart) chart.destroy();

            chart = new Chart(ctx, {
                type: 'bar',
                data: chartData,
                options: { 
                    responsive: true, 
                    maintainAspectRatio: false,
                    plugins: { 
                        legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 6, font: { size: 11 } } },
                        tooltip: { backgroundColor: '#0f172a', padding: 12 }
                    },
                    scales: {
                        x: { stacked: true, grid: { display: false } },
                        y: { 
                            stacked: true, 
                            beginAtZero: true, 
                            grid: { color: '#f3f4f6' },
                            ticks: { precision: 0 }
                        }
                    }
                }
            });
        }

        renderChart(@json($chartInitialData));
        window.livewire.on('updateChart', data => renderChart(data));
    });
</script>
@endpush