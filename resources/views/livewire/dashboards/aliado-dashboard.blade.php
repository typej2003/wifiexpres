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
            <span class="badge bg-primary-soft text-primary border border-primary rounded-pill px-3 mt-2">
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

    {{-- SECCIÓN DE PLANES ACTIVOS --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0">Suscripciones Activas</h6>
            <button wire:click="openModal" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold">+ ADQUIRIR PLAN</button>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="bg-light small fw-bold text-muted">
                    <tr>
                        <th class="ps-4">PLAN</th>
                        <th>CAPACIDAD</th>
                        <th>VENCIMIENTO</th>
                        <th class="text-end pe-4">ESTADO</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($activePlans as $plan)
                    <tr>
                        <td class="ps-4 fw-bold">{{ $plan->name }}</td>
                        <td class="fw-bold text-dark">{{ $plan->limit_routers }} Router(s)</td>
                        <td>{{ \Carbon\Carbon::parse($plan->pivot->end_date)->format('d/m/Y') }}</td>
                        <td class="text-end pe-4"><span class="badge bg-success rounded-pill px-3">Activo</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center py-3 text-muted">No tienes planes activos</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- GRÁFICAS --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                <h6 class="fw-bold mb-4">Conexiones por Tiempo</h6>
                <div style="height: 300px;" wire:ignore>
                    <canvas id="chartLineas"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                <h6 class="fw-bold mb-4">Distribución por Router</h6>
                <div style="height: 300px;" wire:ignore>
                    <canvas id="chartTorta"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL DE PLANES --}}
    @if($showPlanModal)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.7); backdrop-filter: blur(8px); z-index: 2050;">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-dark text-white p-4">
                    <h5 class="modal-title fw-bold">Planes de Monitoreo</h5>
                    @if($activePlans->isNotEmpty())
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
                                    <h2 class="fw-bold text-primary my-3">${{ number_format($package->cost, 2) }}</h2>
                                    <ul class="list-unstyled text-start small mb-4">
                                        <li><i class="bi bi-check2 text-success me-2"></i>{{ $package->limit_routers }} Router(s)</li>
                                        <li><i class="bi bi-check2 text-success me-2"></i>Soporte Técnico</li>
                                    </ul>
                                    <button wire:click="selectPlan({{ $package->id }})" class="btn btn-dark w-100 rounded-pill">SOLICITAR</button>
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
    let lineChart, pieChart;

    function renderCharts() {
        const ctxLine = document.getElementById('chartLineas');
        const ctxPie = document.getElementById('chartTorta');

        if (!ctxLine || !ctxPie) return;

        // Destruir instancias previas
        if (lineChart) lineChart.destroy();
        if (pieChart) pieChart.destroy();

        // Gráfico de Líneas
        lineChart = new Chart(ctxLine, {
            type: 'line',
            data: {
                labels: @json($chartLabels),
                datasets: [{
                    label: 'Logins',
                    data: @json($chartData),
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.05)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });

        // Gráfico de Torta
        pieChart = new Chart(ctxPie, {
            type: 'pie',
            data: {
                labels: @json($pieLabels),
                datasets: [{
                    data: @json($pieValues),
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

    // Inicialización
    document.addEventListener('livewire:load', () => {
        renderCharts();
        
        // Escuchar cambios de periodo desde el controlador
        Livewire.on('periodUpdated', () => {
            // Un pequeño delay para esperar que los datos @json se actualicen en el DOM
            setTimeout(() => { renderCharts(); }, 100);
        });
    });
</script>
@endpush