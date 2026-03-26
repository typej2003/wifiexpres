<div class="container-fluid py-4">
    {{-- HEADER --}}
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h2 class="fw-bold text-dark mb-0">Dashboard Aliado</h2>
            <span class="badge bg-light text-primary border rounded-pill px-3 mt-2">
                Tasa BCV: <strong>Bs. {{ number_format($dollarRate, 2, ',', '.') }}</strong>
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

    {{-- STATS --}}
    <div class="row g-4 mb-4">
        @foreach([['ROUTERS', $stats['total_routers'], 'primary'], ['TICKETS', $stats['total_tickets'], 'info'], ['ONLINE', $stats['tickets_activos'], 'success'], ['LOGINS', $stats['conexiones_periodo'], 'warning']] as $stat)
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                <h6 class="text-muted small fw-bold">{{ $stat[0] }}</h6>
                <h2 class="fw-bold mb-0 text-{{ $stat[2] }}">{{ $stat[1] }}</h2>
            </div>
        </div>
        @endforeach
    </div>

    {{-- GRAFICO Y ACTIVIDAD --}}
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                <h6 class="fw-bold mb-4">Conexiones por Hora</h6>
                <div style="height: 350px;" wire:ignore>
                    <canvas id="aliadoTrafficChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
                    <h6 class="fw-bold mb-0">Actividad Reciente</h6>
                </div>
                <div class="card-body p-0">
                    {{-- SECCIÓN CON SCROLL --}}
                    <div style="max-height: 380px; overflow-y: auto;">
                        <div class="list-group list-group-flush">
                            @forelse($ultimosLogs as $log)
                                <div class="list-group-item border-0 px-4 py-3 d-flex justify-content-between align-items-center border-bottom">
                                    <div>
                                        <span class="fw-bold d-block text-dark small">{{ $log->username }}</span>
                                        <span class="text-muted" style="font-size: 0.65rem;">{{ $log->router->identity ?? 'MikroTik' }}</span>
                                    </div>
                                    <span class="text-muted fw-light" style="font-size: 0.65rem;">{{ $log->created_at->diffForHumans() }}</span>
                                </div>
                            @empty
                                <div class="text-center py-5 text-muted">Sin actividad</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- TABLA EQUIPOS --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mt-4">
        <div class="card-header bg-white border-0 py-3"><h6 class="fw-bold mb-0">Detalle de Equipos</h6></div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="bg-light small fw-bold">
                    <tr><th class="ps-4">IDENTITY</th><th>COMERCIO</th><th class="text-center">ESTADO</th></tr>
                </thead>
                <tbody>
                    @foreach($routers as $r)
                    <tr>
                        <td class="ps-4"><code>{{ $r->identity }}</code></td>
                        <td>{{ $r->comercio_nombre }}</td>
                        <td class="text-center"><span class="badge {{ $r->status == 'Habilitado' ? 'bg-success' : 'bg-danger' }} rounded-pill px-3">{{ strtoupper($r->status) }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- MODAL PLANES --}}
    @if($showPlanModal)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.8); backdrop-filter: blur(5px); z-index: 2050;">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 p-4 text-center">
                <h2 class="fw-bold mb-4">Seleccione un Plan</h2>
                <div class="row g-4">
                    @foreach($availablePackages as $package)
                        <div class="col-md-4">
                            <div class="card border-0 shadow-sm rounded-4 p-4">
                                <h4 class="fw-bold">{{ $package->name }}</h4>
                                <h1 class="text-primary fw-bold my-3">${{ $package->cost }}</h1>
                                <button wire:click="selectPlan({{ $package->id }})" class="btn btn-dark w-100 rounded-pill fw-bold">ADQUIRIR</button>
                            </div>
                        </div>
                    @endforeach
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
        let chart;
        const ctx = document.getElementById('aliadoTrafficChart').getContext('2d');

        function drawChart(labels, data) {
            if (chart) chart.destroy();
            chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Logins',
                        data: data,
                        backgroundColor: '#0d6efd',
                        borderRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            ticks: { color: '#000', font: { weight: 'bold' } },
                            grid: { color: '#eee' }
                        },
                        x: { 
                            ticks: { color: '#000', font: { weight: 'bold' } },
                            grid: { display: false }
                        }
                    },
                    plugins: { legend: { display: false } }
                }
            });
        }

        // Dibujar al inicio
        drawChart(@json($chartLabels), @json($chartData));

        // Redibujar cuando Livewire actualice cualquier cosa
        Livewire.hook('message.processed', (message, component) => {
            drawChart(@json($chartLabels), @json($chartData));
        });
    });
</script>
@endpush