<div class="container-fluid py-4"> {{-- ÚNICO DIV RAÍZ - NADA puede ir fuera de aquí --}}

    @if (session()->has('message'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('message') }}
        </div>
    @endif

    @forelse($pendingPlans as $p)
        <div class="alert alert-info border-0 shadow-sm rounded-4 mb-3 d-flex align-items-center">
            <div class="spinner-border spinner-border-sm me-3"></div>
            <span>El plan <strong>{{ $p->name }}</strong> está pendiente de activación.</span>
        </div>
    @empty
    @endforelse

    @if($activePlans->isEmpty() && $pendingPlans->isEmpty())
        <div class="row justify-content-center my-5">
            <div class="col-md-8 text-center">
                <div class="card border-0 shadow-lg rounded-4 p-5">
                    <i class="bi bi-shield-lock display-1 text-primary mb-4"></i>
                    <h2 class="fw-bold">Acceso Restringido</h2>
                    <p class="text-muted fs-5">Debe adquirir un plan comercial para continuar.</p>
                    <button wire:click="openModal" class="btn btn-primary btn-lg rounded-pill px-5 mt-3 fw-bold">VER PLANES</button>
                </div>
            </div>
        </div>
    @else
        {{-- HEADER Y FILTROS --}}
        <div class="row mb-4 align-items-center">
            <div class="col-md-6">
                <h2 class="fw-bold text-dark mb-0">Dashboard Aliado</h2>
                <div class="d-flex align-items-center gap-2 mt-1">
                    <span class="badge bg-light text-primary border border-primary rounded-pill px-3">
                        Tasa BCV: Bs. {{ number_format($dollarRate ?? 0, 2, ',', '.') }}
                    </span>
                </div>
            </div>
            <div class="col-md-6 text-end">
                <div class="btn-group bg-white p-1 rounded-pill border shadow-sm">
                    <button wire:click="setPeriod('today')" class="btn btn-sm rounded-pill px-3 {{ $period == 'today' ? 'btn-dark' : 'btn-white border-0' }}">Hoy</button>
                    <button wire:click="setPeriod('weekly')" class="btn btn-sm rounded-pill px-3 {{ $period == 'weekly' ? 'btn-dark' : 'btn-white border-0' }}">Semana</button>
                    <button wire:click="setPeriod('month')" class="btn btn-sm rounded-pill px-3 {{ $period == 'month' ? 'btn-dark' : 'btn-white border-0' }}">Mes</button>
                </div>
            </div>
        </div>

        {{-- TARJETAS DE ESTADÍSTICAS --}}
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
                    <small class="text-muted fw-bold">ROUTERS</small>
                    <h3 class="fw-bold mb-0">{{ $stats['total_routers'] }} / {{ $stats['limit_routers'] }}</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
                    <small class="text-muted fw-bold">TICKETS</small>
                    <h3 class="fw-bold mb-0 text-info">{{ $stats['total_tickets'] }}</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
                    <small class="text-muted fw-bold">ONLINE</small>
                    <h3 class="fw-bold mb-0 text-success">{{ $stats['tickets_activos'] }}</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
                    <small class="text-muted fw-bold">CONEXIONES</small>
                    <h3 class="fw-bold mb-0 text-warning">{{ $stats['conexiones_periodo'] }}</h3>
                </div>
            </div>
        </div>

        {{-- SECCIÓN DEL GRÁFICO (IMPORTANTE: WIRE:IGNORE) --}}
        <div class="row">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                    <h6 class="fw-bold mb-4">Distribución de Conexiones</h6>
                    <div style="position: relative; height: 350px;" wire:ignore>
                        <canvas id="chartRoutersAliado"></canvas>
                    </div>
                    <div class="mt-4 text-center">
                        <h6 class="text-muted small text-uppercase">Total General</h6>
                        <h3 class="fw-bold text-primary">{{ number_format($totalGeneral) }}</h3>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 h-100 p-4">
                    <h6 class="fw-bold">Actividad Reciente</h6>
                    <div class="list-group list-group-flush mt-3" style="max-height: 400px; overflow-y: auto;">
                        @foreach($ultimosLogs as $log)
                            <div class="list-group-item border-0 px-0 py-2 small">
                                <span class="fw-bold d-block">{{ $log->username }}</span>
                                <span class="text-muted">{{ $log->router->identity ?? 'Router' }} - {{ $log->created_at->diffForHumans() }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL (DENTRO DEL DIV RAÍZ) --}}
    @if($showPlanModal)
        <div class="modal fade show d-block" style="background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); z-index: 2000;">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content border-0 shadow rounded-4">
                    <div class="modal-header bg-dark text-white p-4">
                        <h5 class="modal-title">Planes Disponibles</h5>
                        @if(!$activePlans->isEmpty())
                            <button wire:click="closeModal" type="button" class="btn-close btn-close-white"></button>
                        @endif
                    </div>
                    <div class="modal-body p-4 bg-light">
                        <div class="row g-3">
                            @foreach($availablePackages as $package)
                                <div class="col-md-4">
                                    <div class="card border-0 shadow-sm rounded-4 h-100 text-center">
                                        <div class="card-body p-4">
                                            <h5 class="fw-bold">{{ $package->name }}</h5>
                                            <h3 class="fw-bold text-primary my-3">${{ number_format($package->cost, 2) }}</h3>
                                            <button wire:click="selectPlan({{ $package->id }})" class="btn btn-dark w-100 rounded-pill">Seleccionar</button>
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

    <style>
        .list-group::-webkit-scrollbar { width: 4px; }
        .list-group::-webkit-scrollbar-thumb { background: #ccc; border-radius: 10px; }
    </style>

</div> {{-- AQUÍ TERMINA EL ÚNICO DIV RAÍZ --}}

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Usamos una función autoejecutable para aislar el scope y evitar errores de "already declared"
    (function() {
        let chartAliadoInstance = null;

        function drawChart() {
            const canvas = document.getElementById('chartRoutersAliado');
            if (!canvas) return;

            if (chartAliadoInstance) {
                chartAliadoInstance.destroy();
            }

            const ctx = canvas.getContext('2d');
            chartAliadoInstance = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: @json($labels),
                    datasets: [{
                        data: @json($values),
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
            drawChart();
            Livewire.on('chartUpdated', () => {
                setTimeout(drawChart, 100);
            });
        });
    })();
</script>
@endpush