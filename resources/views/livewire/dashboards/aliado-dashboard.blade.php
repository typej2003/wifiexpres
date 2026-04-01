<div class="container-fluid py-4">

    @if (session()->has('message'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4">
            {{ session('message') }}
        </div>
    @endif

    {{-- HEADER CON FILTROS --}}
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h2 class="fw-bold text-dark mb-0">Dashboard Aliado</h2>
            <p class="text-muted small">Monitor de red: <strong>Bs. {{ number_format($dollarRate, 2) }}</strong></p>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group bg-white p-1 rounded-pill border shadow-sm">
                <button wire:click="$set('periodo', 'dia')" class="btn btn-sm rounded-pill px-3 {{ $periodo == 'dia' ? 'btn-dark' : 'btn-white border-0' }}">Hoy</button>
                <button wire:click="$set('periodo', 'semana')" class="btn btn-sm rounded-pill px-3 {{ $periodo == 'semana' ? 'btn-dark' : 'btn-white border-0' }}">Semana</button>
                <button wire:click="$set('periodo', 'mes')" class="btn btn-sm rounded-pill px-3 {{ $periodo == 'mes' ? 'btn-dark' : 'btn-white border-0' }}">Mes</button>
            </div>
        </div>
    </div>

    {{-- STATS CARDS --}}
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center bg-white">
                <h6 class="text-muted small fw-bold text-uppercase">Routers</h6>
                <h2 class="fw-bold mb-0 text-dark">{{ $stats['total_routers'] }} / {{ $stats['limit_routers'] }}</h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center bg-white">
                <h6 class="text-muted small fw-bold text-uppercase">Total Tickets</h6>
                <h2 class="fw-bold mb-0 text-info">{{ $stats['total_tickets'] }}</h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center bg-white">
                <h6 class="text-muted small fw-bold text-uppercase">Online</h6>
                <h2 class="fw-bold mb-0 text-success">{{ $stats['tickets_activos'] }}</h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center bg-white">
                <h6 class="text-muted small fw-bold text-uppercase">Sesiones Periodo</h6>
                <h2 class="fw-bold mb-0 text-primary">{{ number_format($stats['conexiones_periodo']) }}</h2>
            </div>
        </div>
    </div>

    {{-- GRÁFICA COMPARATIVA --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <h6 class="fw-bold mb-0">Comparativa de Carga por Router</h6>
                </div>
                <div class="card-body p-4">
                    <div style="position: relative; height:450px;" wire:ignore>
                        <canvas id="multiBarChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold mb-0">Mis Equipos</h6>
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
                            @foreach($routers as $r)
                            <tr>
                                <td class="ps-4"><code>{{ $r->identity }}</code></td>
                                <td>{{ $r->comercio_nombre }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $r->status == 'Habilitado' ? 'bg-success' : 'bg-danger' }} rounded-pill px-3">{{ $r->status }}</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 pt-3">
                    <h6 class="fw-bold mb-0">Actividad Reciente</h6>
                </div>
                <div class="list-group list-group-flush" style="max-height: 400px; overflow-y: auto;">
                    @foreach($ultimosLogs as $log)
                        <div class="list-group-item border-0 px-4 py-2 small d-flex justify-content-between">
                            <span>{{ $log->username }}</span>
                            <span class="text-muted">{{ $log->created_at->diffForHumans() }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL DE PLANES --}}
    @if($showPlanModal)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.6); backdrop-filter: blur(5px); z-index: 2000;">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-0">
                    <h5 class="fw-bold">Planes Disponibles</h5>
                    <button wire:click="closeModal" class="btn-close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        @foreach($availablePackages as $package)
                        <div class="col-md-4">
                            <div class="card border text-center p-3 rounded-4">
                                <h6 class="fw-bold">{{ $package->name }}</h6>
                                <h3 class="fw-bold text-primary">${{ $package->cost }}</h3>
                                <p class="small text-muted">{{ $package->limit_routers }} Routers</p>
                                <button wire:click="selectPlan({{ $package->id }})" class="btn btn-dark btn-sm rounded-pill">Elegir</button>
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
        const canvas = document.getElementById('multiBarChart');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        
        if (multiChart) multiChart.destroy();

        multiChart = new Chart(ctx, {
            type: 'bar',
            data: { labels: labels, datasets: datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f0f0f0' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // Inicializar cuando Livewire carga
    document.addEventListener('livewire:load', () => {
        renderMultiChart(@json($labels), @json($datasets));
    });

    // Escuchar actualizaciones de filtros
    window.addEventListener('updateMultiChart', event => {
        renderMultiChart(event.detail.labels, event.detail.datasets);
    });
</script>
@endpush