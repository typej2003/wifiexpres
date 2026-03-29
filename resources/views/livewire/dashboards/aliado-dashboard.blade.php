<div class="container-fluid py-4">
    @if (session()->has('message'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('message') }}
        </div>
    @endif

    {{-- ALERTAS --}}
    @foreach($pendingPlans as $p)
        <div class="alert alert-info border-0 shadow-sm rounded-4 mb-3 d-flex align-items-center">
            <div class="spinner-border spinner-border-sm me-3"></div>
            <span>El plan <strong>{{ $p->name }}</strong> está pendiente ({{ $p->limit_routers }} routers).</span>
        </div>
    @endforeach

    @if($activePlans->isEmpty() && $pendingPlans->isEmpty())
        <div class="row justify-content-center my-5">
            <div class="col-md-8 text-center">
                <div class="card border-0 shadow-lg rounded-4 p-5">
                    <i class="bi bi-shield-lock display-1 text-primary mb-4"></i>
                    <h2 class="fw-bold">Acceso Restringido</h2>
                    <p class="text-muted fs-5">Debe adquirir un plan comercial para continuar.</p>
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
                    <span class="badge bg-primary-soft text-primary border border-primary rounded-pill px-3" style="font-size: 0.7rem;">
                        Tasa BCV: <strong>Bs. {{ number_format($dollarRate, 2, ',', '.') }}</strong>
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

        {{-- CONTENIDO PRINCIPAL --}}
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                    <h6 class="fw-bold mb-4">Distribución de Conexiones</h6>
                    <div style="height: 350px;" wire:ignore>
                        <canvas id="chartRouters"></canvas>
                    </div>
                </div>

                {{-- TABLA DE EQUIPOS --}}
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="bg-light small fw-bold">
                                <tr><th class="ps-4">IDENTITY</th><th>COMERCIO</th><th class="text-center">ESTADO</th></tr>
                            </thead>
                            <tbody>
                                @forelse($routers as $r)
                                    <tr>
                                        <td class="ps-4"><code>{{ $r->identity }}</code></td>
                                        <td>{{ $r->comercio_nombre }}</td>
                                        <td class="text-center">
                                            <span class="badge {{ $r->status == 'Habilitado' ? 'bg-success' : 'bg-danger' }} rounded-pill px-3">{{ strtoupper($r->status) }}</span>
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
                {{-- STATS RESUMEN --}}
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 text-center">
                    <h6 class="text-muted small fw-bold">LOGINS ({{ strtoupper($period) }})</h6>
                    <h2 class="fw-bold mb-0 text-warning">{{ $stats['conexiones_periodo'] }}</h2>
                </div>

                <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                    <div class="card-header bg-white border-0 pt-4 px-4"><h5 class="fw-bold mb-0">Actividad Reciente</h5></div>
                    <div class="list-group list-group-flush mt-3" style="max-height: 400px; overflow-y: auto;">
                        @foreach($ultimosLogs as $log)
                            <div class="list-group-item border-0 px-4 py-3 small d-flex justify-content-between">
                                <div><span class="fw-bold d-block text-dark">{{ $log->username }}</span><span class="text-muted x-small">{{ $log->router->identity ?? 'MikroTik' }}</span></div>
                                <span class="text-muted" style="font-size: 0.7rem;">{{ $log->created_at->diffForHumans() }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL --}}
    @if($showPlanModal)
        <div class="modal fade show d-block" style="background: rgba(0,0,0,0.7); backdrop-filter: blur(8px); z-index: 2050;">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="modal-header bg-dark text-white p-4">
                        <h5 class="modal-title fw-bold">Planes Disponibles</h5>
                        <button type="button" wire:click="closeModal" class="btn-close btn-close-white shadow-none"></button>
                    </div>
                    <div class="modal-body p-4 bg-light">
                        <div class="row g-4">
                            @foreach($availablePackages as $package)
                                <div class="col-md-4">
                                    <div class="card border-0 shadow-sm rounded-4 h-100 text-center transition-card">
                                        <div class="p-2 {{ $package->service_type == 'cortesia' ? 'bg-success' : 'bg-primary' }} text-white small fw-bold">{{ strtoupper($package->service_type) }}</div>
                                        <div class="card-body p-4">
                                            <h4 class="fw-bold">{{ $package->name }}</h4>
                                            <div class="display-6 fw-bold my-3 text-dark">${{ number_format($package->cost, 2) }}</div>
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

    <style>
        .bg-primary-soft { background-color: rgba(13, 110, 253, 0.1); }
        .transition-card:hover { transform: translateY(-5px); transition: 0.3s; }
    </style>
</div>

@push('scripts')
<script>
    (function() {
        let chart = null;

        function runChart() {
            const ctx = document.getElementById('chartRouters');
            if (!ctx) return;
            if (chart) chart.destroy();
            chart = new Chart(ctx.getContext('2d'), {
                type: 'pie',
                data: {
                    labels: @json($labels),
                    datasets: [{
                        data: @json($values),
                        backgroundColor: ['#0d6efd', '#212529', '#0dcaf0', '#198754', '#ffc107', '#6610f2']
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
        }

        document.addEventListener('livewire:load', () => {
            runChart();
            Livewire.on('chartUpdated', () => setTimeout(runChart, 200));
        });
    })();
</script>
@endpush