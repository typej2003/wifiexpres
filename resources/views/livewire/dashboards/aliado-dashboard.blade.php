<div class="container-fluid py-4">

    @if (session()->has('message'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('message') }}
        </div>
    @endif

    {{-- ALERTAS DE PLANES PENDIENTES --}}
    @foreach($pendingPlans as $p)
        <div class="alert alert-info border-0 shadow-sm rounded-4 mb-3 d-flex align-items-center">
            <div class="spinner-border spinner-border-sm me-3"></div>
            <span>El plan <strong>{{ $p->name }}</strong> está pendiente de activación ({{ $p->limit_routers }} routers).</span>
        </div>
    @endforeach

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
                <span class="badge bg-primary-soft text-primary border border-primary rounded-pill px-3 mt-1">
                    <i class="bi bi-currency-exchange me-1"></i> Tasa BCV: <strong>Bs. {{ number_format($dollarRate, 2, ',', '.') }}</strong>
                </span>
            </div>
            <div class="col-md-6 text-end">
                <div class="btn-group bg-white p-1 rounded-pill border shadow-sm">
                    <button wire:click="setPeriod('today')" class="btn btn-sm rounded-pill px-3 {{ $period == 'today' ? 'btn-dark' : 'btn-white border-0' }}">Hoy</button>
                    <button wire:click="setPeriod('weekly')" class="btn btn-sm rounded-pill px-3 {{ $period == 'weekly' ? 'btn-dark' : 'btn-white border-0' }}">Semana</button>
                    <button wire:click="setPeriod('month')" class="btn btn-sm rounded-pill px-3 {{ $period == 'month' ? 'btn-dark' : 'btn-white border-0' }}">Mes</button>
                </div>
            </div>
        </div>

        {{-- STATS CARDS --}}
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                    <h6 class="text-muted small fw-bold">ROUTERS</h6>
                    <h2 class="fw-bold mb-0 text-dark">{{ $stats['total_routers'] }} / {{ $stats['limit_routers'] }}</h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                    <h6 class="text-muted small fw-bold">ONLINE</h6>
                    <h2 class="fw-bold mb-0 text-success">{{ $stats['tickets_activos'] }}</h2>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                    <h6 class="text-muted small fw-bold text-uppercase">Logins en este periodo ({{ $period }})</h6>
                    <h2 class="fw-bold mb-0 text-primary">{{ number_format($stats['conexiones_periodo']) }}</h2>
                </div>
            </div>
        </div>

        {{-- GRÁFICA Y ACTIVIDAD --}}
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                    <h6 class="fw-bold text-center mb-4 text-uppercase small text-muted">Distribución de Conexiones por Router</h6>
                    <div style="position: relative; height:350px;" wire:ignore>
                        <canvas id="chartRoutersAliado"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                    <div class="card-header bg-white border-0 pt-4 px-4">
                        <h5 class="fw-bold mb-0">Actividad Reciente</h5>
                    </div>
                    <div class="list-group list-group-flush mt-3" style="max-height: 400px; overflow-y: auto;">
                        @forelse($ultimosLogs as $log)
                            <div class="list-group-item border-0 px-4 py-3 small d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="fw-bold d-block text-dark">{{ $log->username }}</span>
                                    <span class="text-muted x-small">{{ $log->router->identity ?? 'MikroTik' }}</span>
                                </div>
                                <span class="text-muted" style="font-size: 0.7rem;">{{ $log->created_at->diffForHumans() }}</span>
                            </div>
                        @empty
                            <div class="text-center py-5 text-muted small">Sin actividad</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL DE PLANES --}}
    @if($showPlanModal)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.7); backdrop-filter: blur(8px); z-index: 2050;">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-dark text-white p-4">
                    <h5 class="modal-title fw-bold">Planes Disponibles</h5>
                    @if($activePlans->isNotEmpty() || $pendingPlans->isNotEmpty())
                        <button type="button" wire:click="closeModal" class="btn-close btn-close-white"></button>
                    @endif
                </div>
                <div class="modal-body p-4 bg-light">
                    <div class="row g-4">
                        @foreach($availablePackages as $package)
                            <div class="col-md-4">
                                <div class="card border-0 shadow-sm rounded-4 h-100 text-center">
                                    <div class="card-body p-4">
                                        <h4 class="fw-bold">{{ $package->name }}</h4>
                                        <div class="display-6 fw-bold my-3 text-dark">${{ number_format($package->cost, 2) }}</div>
                                        <p class="small text-muted mb-4">{{ $package->limit_routers }} Router(s) por {{ $package->duration_months }} mes(es)</p>
                                        <button wire:click="selectPlan({{ $package->id }})" class="btn btn-primary w-100 rounded-pill fw-bold">ADQUIRIR</button>
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

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let myChart;

    function initChart() {
        const el = document.getElementById('chartRoutersAliado');
        if (!el) return;

        if (myChart) myChart.destroy();

        const ctx = el.getContext('2d');
        myChart = new Chart(ctx, {
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
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }

    document.addEventListener('livewire:load', () => {
        initChart();
        Livewire.on('chartUpdated', () => {
            setTimeout(() => { initChart(); }, 100);
        });
    });
</script>
@endpush