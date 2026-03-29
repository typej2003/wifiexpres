<div class="container-fluid py-4">
    {{-- ALERTAS --}}
    @if (session()->has('message'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('message') }}
        </div>
    @endif

    @foreach($pendingPlans as $p)
        <div class="alert alert-info border-0 shadow-sm rounded-4 mb-3 d-flex align-items-center">
            <div class="spinner-border spinner-border-sm me-3"></div>
            <span>El plan <strong>{{ $p->name }}</strong> está pendiente de activación.</span>
        </div>
    @endforeach

    @if($activePlans->isEmpty() && $pendingPlans->isEmpty())
        <div class="row justify-content-center my-5">
            <div class="col-md-8 text-center">
                <div class="card border-0 shadow-lg rounded-4 p-5">
                    <i class="bi bi-shield-lock display-1 text-primary mb-4"></i>
                    <h2 class="fw-bold">Acceso Restringido</h2>
                    <p class="text-muted fs-5">Adquiera un plan para habilitar el monitoreo.</p>
                    <button wire:click="openModal" class="btn btn-primary btn-lg rounded-pill px-5 mt-3 shadow-lg fw-bold">VER PLANES</button>
                </div>
            </div>
        </div>
    @else
        {{-- HEADER --}}
        <div class="row mb-4 align-items-center">
            <div class="col-md-6">
                <h2 class="fw-bold text-dark mb-0">Dashboard Aliado</h2>
                <span class="badge bg-soft-primary text-primary border rounded-pill px-3">Tasa BCV: Bs. {{ number_format($dollarRate, 2) }}</span>
            </div>
            <div class="col-md-6 text-end">
                <div class="btn-group bg-white p-1 rounded-pill border shadow-sm">
                    <button wire:click="setPeriod('today')" class="btn btn-sm rounded-pill px-3 {{ $period == 'today' ? 'btn-dark' : 'btn-white border-0' }}">Hoy</button>
                    <button wire:click="setPeriod('month')" class="btn btn-sm rounded-pill px-3 {{ $period == 'month' ? 'btn-dark' : 'btn-white border-0' }}">Mes</button>
                </div>
            </div>
        </div>

        {{-- TABLA PLANES --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="bg-light small fw-bold">
                        <tr><th class="ps-4">PLAN</th><th>CAPACIDAD</th><th>ESTADO</th></tr>
                    </thead>
                    <tbody>
                        @foreach($activePlans as $plan)
                        <tr>
                            <td class="ps-4 fw-bold">{{ $plan->name }}</td>
                            <td>{{ $plan->limit_routers }} Routers</td>
                            <td><span class="badge bg-success rounded-pill">Activo</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- STATS --}}
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                    <small class="text-muted fw-bold">ROUTERS</small>
                    <h2 class="fw-bold mb-0">{{ $stats['total_routers'] }}</h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                    <small class="text-muted fw-bold">CONEXIONES</small>
                    <h2 class="fw-bold mb-0 text-info">{{ $stats['conexiones_periodo'] }}</h2>
                </div>
            </div>
        </div>

        {{-- GRÁFICA Y ACTIVIDAD --}}
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h6 class="fw-bold mb-4">Distribución de Conexiones</h6>
                    <div style="height: 350px;" wire:ignore>
                        <canvas id="chartRouters"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-header bg-white border-0 pt-4 px-4"><h6 class="fw-bold">Actividad Reciente</h6></div>
                    <div class="list-group list-group-flush" style="max-height: 400px; overflow-y: auto;">
                        @foreach($ultimosLogs as $log)
                            <div class="list-group-item border-0 px-4 py-3 small d-flex justify-content-between">
                                <strong>{{ $log->username }}</strong>
                                <span class="text-muted">{{ $log->created_at->diffForHumans() }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL --}}
    @if($showPlanModal)
        <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5); z-index: 1050;">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow rounded-4">
                    <div class="modal-header">
                        <h5 class="fw-bold">Planes Disponibles</h5>
                        <button wire:click="closeModal" class="btn-close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            @foreach($availablePackages as $package)
                                <div class="col-md-6">
                                    <div class="card border p-3 text-center rounded-4">
                                        <h5 class="fw-bold">{{ $package->name }}</h5>
                                        <p class="display-6">${{ number_format($package->cost, 2) }}</p>
                                        <button class="btn btn-dark rounded-pill">Seleccionar</button>
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
        .bg-soft-primary { background: rgba(13, 110, 253, 0.1); }
    </style>
</div>

@push('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Usar un IIFE (Immediately Invoked Function Expression) para evitar conflictos de variables globales
    (function() {
        let chartInstance = null;

        function renderChart() {
            const ctx = document.getElementById('chartRouters');
            if (!ctx) return;

            if (chartInstance) chartInstance.destroy();

            chartInstance = new Chart(ctx.getContext('2d'), {
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

        // Ejecutar al cargar Livewire
        document.addEventListener('livewire:load', () => {
            renderChart();
            Livewire.on('chartUpdated', () => setTimeout(renderChart, 100));
        });

        // Refuerzo para carga inicial normal
        window.addEventListener('load', renderChart);
    })();
</script>
@endpush