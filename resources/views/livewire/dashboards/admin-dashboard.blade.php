<div class="container-fluid py-4">
    <div class="row align-items-center mb-4">
        <div class="col-md-6">
            <h3 class="fw-bold text-dark mb-0">Panel de Control Global</h3>
            <div class="d-flex align-items-center gap-2">
                <p class="text-muted mb-0">Viendo actividad de: <span class="badge bg-primary">{{ strtoupper($period) }}</span></p>
                {{-- INDICADOR TASA BCV --}}
                <span class="badge bg-success-soft text-success border border-success rounded-pill px-3" style="font-size: 0.75rem;">
                    <i class="bi bi-currency-exchange me-1"></i> Tasa BCV: <strong>Bs. {{ number_format($dollarRate, 2, ',', '.') }}</strong>
                </span>
            </div>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group shadow-sm rounded-pill bg-white p-1 border">
                <button wire:click="setPeriod('today')" class="btn btn-sm rounded-pill px-3 {{ $period == 'today' ? 'btn-primary' : 'btn-white border-0' }}">Hoy</button>
                <button wire:click="setPeriod('weekly')" class="btn btn-sm rounded-pill px-3 {{ $period == 'weekly' ? 'btn-primary' : 'btn-white border-0' }}">Semana</button>
                <button wire:click="setPeriod('15days')" class="btn btn-sm rounded-pill px-3 {{ $period == '15days' ? 'btn-primary' : 'btn-white border-0' }}">15 Días</button>
                <button wire:click="setPeriod('month')" class="btn btn-sm rounded-pill px-3 {{ $period == 'month' ? 'btn-primary' : 'btn-white border-0' }}">Mes</button>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <h6 class="text-muted small fw-bold">CONEXIONES EN PERIODO</h6>
                <h2 class="fw-bold text-primary mb-0">{{ number_format($stats['conexiones_periodo']) }}</h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <h6 class="text-muted small fw-bold">ALIADOS ACTIVOS</h6>
                <h2 class="fw-bold text-dark mb-0">{{ $stats['total_aliados'] }}</h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <h6 class="text-muted small fw-bold">EQUIPOS ONLINE</h6>
                <h2 class="fw-bold text-success mb-0">{{ $stats['routers_activos'] }} / {{ $stats['total_routers'] }}</h2>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <h5 class="fw-bold mb-4"><i class="bi bi-graph-up me-2 text-primary"></i>Tráfico de Red</h5>
                <div style="height: 300px;"><canvas id="adminTrafficChart"></canvas></div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white border-0 py-3"><h5 class="fw-bold mb-0">Uso por Aliado / MikroTik</h5></div>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Aliado</th>
                                <th>MikroTik (Identity)</th>
                                <th>Comercio</th>
                                <th class="text-center">Tickets</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($actividadJerarquica as $row)
                            <tr>
                                <td class="ps-4 fw-bold text-dark">{{ $row->aliado }}</td>
                                <td><code class="text-primary small">{{ $row->mikrotik }}</code></td>
                                <td><span class="small">{{ $row->comercio_nombre }}</span></td>
                                <td class="text-center fw-bold">{{ $row->total_tickets }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white border-0 py-3"><h5 class="fw-bold mb-0">Logs Recientes</h5></div>
                <div class="list-group list-group-flush">
                    @foreach($ultimosLogs as $log)
                    <div class="list-group-item p-3 border-0 border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="fw-bold d-block">Ticket: {{ $log->username }}</span>
                                <code class="text-primary x-small" style="font-size: 0.7rem;">{{ $log->router->identity ?? 'N/A' }}</code>
                            </div>
                            <small class="text-muted">{{ $log->created_at->diffForHumans() }}</small>
                        </div>
                        <div class="mt-1">
                            <span class="badge {{ $log->duration_seconds ? 'bg-light text-dark' : 'bg-success-soft text-success' }} small">
                                <i class="bi bi-clock me-1"></i>
                                {{ $log->duration_seconds ? gmdate("H:i:s", $log->duration_seconds) : 'Conectado' }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
<style>.bg-success-soft { background-color: rgba(25, 135, 84, 0.1); } .x-small { font-size: 0.7rem; }</style>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('livewire:load', function () {
        let chart;
        const ctx = document.getElementById('adminTrafficChart').getContext('2d');
        
        function initChart(labels, data) {
            if(chart) chart.destroy();
            chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Logins',
                        data: data,
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13, 110, 253, 0.1)',
                        fill: true, tension: 0.4
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
            });
        }

        initChart(@json($chartLabels), @json($chartData));
        window.livewire.on('updateChart', data => { initChart(data.labels, data.values); });
    });
</script>
@endpush