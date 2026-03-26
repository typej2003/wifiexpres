<div class="container-fluid py-4">

    @if (session()->has('message'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('message') }}
        </div>
    @endif

    {{-- HEADER Y FILTROS --}}
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h2 class="fw-bold text-dark mb-0">Monitor de Red</h2>
            <p class="text-muted small">Visualización de conexiones {{ $period == 'today' ? 'por hora' : 'por router' }}.</p>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group bg-white p-1 rounded-pill border shadow-sm">
                <button wire:click="setPeriod('today')" class="btn btn-sm rounded-pill px-3 {{ $period == 'today' ? 'btn-dark' : 'btn-white border-0' }}">Hoy</button>
                <button wire:click="setPeriod('weekly')" class="btn btn-sm rounded-pill px-3 {{ $period == 'weekly' ? 'btn-dark' : 'btn-white border-0' }}">Semana</button>
                <button wire:click="setPeriod('month')" class="btn btn-sm rounded-pill px-3 {{ $period == 'month' ? 'btn-dark' : 'btn-white border-0' }}">Mes</button>
            </div>
        </div>
    </div>

    {{-- STATS --}}
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
                <small class="text-muted fw-bold">ROUTERS</small>
                <h3 class="fw-bold mb-0">{{ $stats['total_routers'] }}</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
                <small class="text-muted fw-bold">CONEXIONES ({{ strtoupper($period) }})</small>
                <h3 class="fw-bold mb-0 text-primary">{{ $stats['conexiones_periodo'] }}</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
                <small class="text-muted fw-bold">ONLINE AHORA</small>
                <h3 class="fw-bold mb-0 text-success">{{ $stats['tickets_activos'] }}</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
                <small class="text-muted fw-bold">TASA BCV</small>
                <h3 class="fw-bold mb-0 text-dark">Bs.{{ number_format($dollarRate, 2) }}</h3>
            </div>
        </div>
    </div>

    {{-- GRÁFICO --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0">Actividad del Sistema</h5>
                    <span class="badge bg-light text-dark rounded-pill px-3 border">
                        {{ $period == 'today' ? 'Escala: Horas (00-23)' : 'Escala: Routers' }}
                    </span>
                </div>
                <div style="height: 400px;">
                    <canvas id="trafficChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- TABLA DE EQUIPOS --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white border-0 py-3">
            <h6 class="fw-bold mb-0">Estado de los Equipos</h6>
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
                        <td class="ps-4 fw-bold">{{ $r->identity }}</td>
                        <td>{{ $r->comercio_nombre }}</td>
                        <td class="text-center">
                            <span class="badge {{ $r->status == 'Habilitado' ? 'bg-success' : 'bg-danger' }} rounded-pill px-3">
                                {{ strtoupper($r->status) }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- MODAL DE PLANES --}}
    @if($showPlanModal)
        <div class="modal fade show d-block" style="background: rgba(0,0,0,0.6); z-index: 2000;">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow rounded-4">
                    <div class="modal-header bg-dark text-white rounded-top-4">
                        <h5 class="fw-bold mb-0">Planes Disponibles</h5>
                        @if($activePlans->isNotEmpty()) <button wire:click="closeModal" class="btn-close btn-close-white"></button> @endif
                    </div>
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            @foreach($availablePackages as $pkg)
                            <div class="col-md-6">
                                <div class="card p-3 border rounded-4 text-center">
                                    <h5 class="fw-bold">{{ $pkg->name }}</h5>
                                    <h2 class="text-primary fw-bold">${{ $pkg->cost }}</h2>
                                    <p class="text-muted small">Límite: {{ $pkg->limit_routers }} Routers</p>
                                    <button wire:click="selectPlan({{ $pkg->id }})" class="btn btn-outline-dark rounded-pill">Seleccionar</button>
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
        let myChart;
        const ctx = document.getElementById('trafficChart').getContext('2d');

        function initChart(chartData) {
            if(myChart) myChart.destroy();

            // Cálculo dinámico del eje Y para aproximar a la centena o decena superior
            const maxData = Math.max(...chartData.datasets[0].data, 0);
            let suggestedMax = 10;
            let stepSize = 1;

            if (maxData > 100) {
                suggestedMax = Math.ceil(maxData / 100) * 100;
                stepSize = 100;
            } else if (maxData > 10) {
                suggestedMax = Math.ceil(maxData / 10) * 10;
                stepSize = 10;
            } else {
                suggestedMax = 10;
                stepSize = 2;
            }

            myChart = new Chart(ctx, {
                type: 'bar',
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            suggestedMax: suggestedMax,
                            ticks: { stepSize: stepSize },
                            grid: { borderDash: [5, 5] }
                        },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        initChart(@json($chartInitialData));
        window.livewire.on('updateChart', data => initChart(data));
    });
</script>
@endpush