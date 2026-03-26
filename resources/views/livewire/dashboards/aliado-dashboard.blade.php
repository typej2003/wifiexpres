<div class="container-fluid py-4">

    @if (session()->has('message'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('message') }}
        </div>
    @endif

    {{-- HEADER Y FILTROS --}}
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h2 class="fw-bold text-dark mb-0">Dashboard Aliado</h2>
            <p class="text-muted small mb-0">Tasa BCV: <strong>Bs. {{ number_format($dollarRate, 2, ',', '.') }}</strong></p>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group bg-white p-1 rounded-pill border shadow-sm">
                <button wire:click="setPeriod('today')" class="btn btn-sm rounded-pill px-3 {{ $period == 'today' ? 'btn-dark text-white' : 'btn-white border-0' }}">Hoy</button>
                <button wire:click="setPeriod('weekly')" class="btn btn-sm rounded-pill px-3 {{ $period == 'weekly' ? 'btn-dark text-white' : 'btn-white border-0' }}">Semana</button>
                <button wire:click="setPeriod('month')" class="btn btn-sm rounded-pill px-3 {{ $period == 'month' ? 'btn-dark text-white' : 'btn-white border-0' }}">Mes</button>
            </div>
        </div>
    </div>

    {{-- ESTADÍSTICAS --}}
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                <h6 class="text-muted small fw-bold mb-2">EQUIPOS</h6>
                <h2 class="fw-bold mb-0 text-dark">{{ $stats['total_routers'] }} / {{ $stats['limit_routers'] }}</h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                <h6 class="text-muted small fw-bold mb-2">TICKETS TOTALES</h6>
                <h2 class="fw-bold mb-0 text-primary">{{ $stats['total_tickets'] }}</h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                <h6 class="text-muted small fw-bold mb-2">USUARIOS ONLINE</h6>
                <h2 class="fw-bold mb-0 text-success">{{ $stats['tickets_activos'] }}</h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                <h6 class="text-muted small fw-bold mb-2">PLANES ACTIVOS</h6>
                <h2 class="fw-bold mb-0 text-info">{{ $activePlans->count() }}</h2>
            </div>
        </div>
    </div>

    {{-- SECCIÓN CENTRAL: TRÁFICO DE RED (BARRAS) --}}
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h6 class="fw-bold mb-0">Tráfico de Red por Router</h6>
            <span class="badge bg-light text-dark rounded-pill px-3 py-2 small">Eje Y: Cantidad de Conexiones</span>
        </div>
        <div style="height: 380px;">
            <canvas id="aliadoTrafficChart"></canvas>
        </div>
    </div>

    <div class="row g-4">
        {{-- LISTADO DE ROUTERS --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold mb-0">Mis Equipos MikroTik</h6>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="bg-light small fw-bold">
                            <tr>
                                <th class="ps-4">IDENTITY</th>
                                <th>UBICACIÓN</th>
                                <th class="text-center">ESTADO</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($routers as $r)
                            <tr>
                                <td class="ps-4">
                                    <span class="fw-bold text-primary">{{ $r->identity }}</span>
                                    <small class="d-block text-muted">{{ $r->ip_address }}</small>
                                </td>
                                <td>{{ $r->comercio_nombre }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $r->status == 'Habilitado' ? 'bg-success' : 'bg-danger' }} rounded-pill px-3">
                                        {{ $r->status }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ÚLTIMOS LOGS --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
                <h6 class="fw-bold mb-3 px-2">Actividad Reciente</h6>
                @foreach($ultimosLogs as $log)
                <div class="d-flex justify-content-between align-items-center mb-3 p-2 border-bottom">
                    <div>
                        <span class="fw-bold d-block small">{{ $log->username }}</span>
                        <small class="text-muted">{{ $log->router->identity }}</small>
                    </div>
                    <div class="text-end">
                        <span class="badge {{ is_null($log->disconnected_at) ? 'bg-success' : 'bg-light text-muted' }} small d-block mb-1">
                            {{ is_null($log->disconnected_at) ? 'ONLINE' : $log->duracion_formateada }}
                        </span>
                        <small class="text-muted" style="font-size: 0.7rem;">{{ $log->created_at->diffForHumans() }}</small>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- MODAL DE PLANES --}}
    @if($showPlanModal)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); z-index: 2050;">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-0 p-4">
                    <h5 class="fw-bold mb-0">Planes de Suscripción</h5>
                    @if($activePlans->isNotEmpty() || $pendingPlans->isNotEmpty())
                        <button type="button" wire:click="closeModal" class="btn-close shadow-none"></button>
                    @endif
                </div>
                <div class="modal-body p-4 bg-light">
                    <div class="row g-4">
                        @foreach($availablePackages as $package)
                            <div class="col-md-4">
                                <div class="card border-0 shadow-sm rounded-4 h-100 text-center p-4">
                                    <h4 class="fw-bold">{{ $package->name }}</h4>
                                    <div class="display-6 fw-bold my-3 text-dark">${{ number_format($package->cost, 2) }}</div>
                                    <ul class="list-unstyled text-start mb-4">
                                        <li><i class="bi bi-check text-success me-2"></i>{{ $package->limit_routers }} Routers</li>
                                        <li><i class="bi bi-check text-success me-2"></i>{{ $package->duration_months }} Meses</li>
                                    </ul>
                                    <button wire:click="selectPlan({{ $package->id }})" class="btn btn-primary w-100 rounded-pill fw-bold">ELEGIR</button>
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

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('livewire:load', function () {
        const ctx = document.getElementById('aliadoTrafficChart').getContext('2d');
        let chart;

        function renderChart(data) {
            if (chart) chart.destroy();
            chart = new Chart(ctx, {
                type: 'bar',
                data: data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } },
                        tooltip: { mode: 'index', intersect: false }
                    },
                    scales: {
                        x: { stacked: true, grid: { display: false } },
                        y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } }
                    }
                }
            });
        }

        renderChart(@json($chartInitialData));
        window.livewire.on('updateChart', data => renderChart(data));
    });
</script>
@endpush