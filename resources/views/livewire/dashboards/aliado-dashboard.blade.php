<div class="container-fluid py-4">

    @if (session()->has('message'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4">
            {{ session('message') }}
        </div>
    @endif

    @if($activePlans->isEmpty() && $pendingPlans->isEmpty())
        <div class="text-center py-5">
            <button wire:click="openModal" class="btn btn-primary">Adquirir un Plan</button>
        </div>
    @else
        <div class="row mb-4 align-items-center">
            <div class="col-md-6">
                <h3 class="fw-bold mb-0">Monitor de Tráfico</h3>
                <small class="text-muted">Conexiones registradas por router</small>
            </div>
            <div class="col-md-6 text-end">
                <div class="btn-group bg-white p-1 rounded-pill shadow-sm border">
                    <button wire:click="setPeriod('today')" class="btn btn-sm rounded-pill px-3 {{ $period == 'today' ? 'btn-primary' : 'btn-white border-0' }}">Hoy</button>
                    <button wire:click="setPeriod('weekly')" class="btn btn-sm rounded-pill px-3 {{ $period == 'weekly' ? 'btn-primary' : 'btn-white border-0' }}">Semana</button>
                    <button wire:click="setPeriod('month')" class="btn btn-sm rounded-pill px-3 {{ $period == 'month' ? 'btn-primary' : 'btn-white border-0' }}">Mes</button>
                </div>
            </div>
        </div>

        {{-- TARJETAS DE STATS --}}
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-primary border-4">
                    <small class="text-muted fw-bold">ONLINE AHORA</small>
                    <h3 class="fw-bold mb-0">{{ $stats['tickets_activos'] }}</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-success border-4">
                    <small class="text-muted fw-bold">TOTAL TICKETS</small>
                    <h3 class="fw-bold mb-0">{{ $stats['total_tickets'] }}</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-info border-4">
                    <small class="text-muted fw-bold">ROUTERS</small>
                    <h3 class="fw-bold mb-0">{{ $stats['total_routers'] }} / {{ $stats['limit_routers'] }}</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-warning border-4">
                    <small class="text-muted fw-bold">TASA BCV</small>
                    <h3 class="fw-bold mb-0">Bs. {{ number_format($dollarRate, 2) }}</h3>
                </div>
            </div>
        </div>

        {{-- CONTENEDOR DE LAS BARRAS --}}
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
            <div style="height: 350px;">
                <canvas id="trafficChart"></canvas>
            </div>
        </div>

        <div class="row">
            {{-- TABLA --}}
            <div class="col-md-8">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white border-0 py-3 fw-bold">Equipos del Aliado</div>
                    <table class="table align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Identidad</th>
                                <th>IP</th>
                                <th class="text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($routers as $r)
                            <tr>
                                <td class="ps-4 fw-bold">{{ $r->identity }}</td>
                                <td>{{ $r->ip_address }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $r->status == 'Habilitado' ? 'bg-success' : 'bg-danger' }} rounded-pill">
                                        {{ $r->status }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ACTIVIDAD --}}
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-4 p-3">
                    <h6 class="fw-bold mb-3">Logs Recientes</h6>
                    @foreach($ultimosLogs as $log)
                    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                        <div>
                            <span class="d-block fw-bold small">{{ $log->username }}</span>
                            <small class="text-muted">{{ $log->router->identity }}</small>
                        </div>
                        <div class="text-end">
                            <span class="badge {{ is_null($log->disconnected_at) ? 'bg-success' : 'bg-light text-dark' }} small">
                                {{ is_null($log->disconnected_at) ? 'ONLINE' : $log->duracion_formateada }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL --}}
    @if($showPlanModal)
        <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5); z-index: 1050;">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 rounded-4">
                    <div class="modal-body p-4">
                        <h4 class="fw-bold mb-4">Selecciona un Plan</h4>
                        <div class="row g-3">
                            @foreach($availablePackages as $p)
                            <div class="col-md-6">
                                <div class="card p-3 border-2 shadow-none rounded-4">
                                    <h5>{{ $p->name }}</h5>
                                    <h3 class="fw-bold">${{ number_format($p->cost, 2) }}</h3>
                                    <p class="small text-muted">Máx {{ $p->limit_routers }} routers</p>
                                    <button wire:click="selectPlan({{ $p->id }})" class="btn btn-dark w-100 rounded-pill mt-2">Elegir</button>
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
        let ctx = document.getElementById('trafficChart').getContext('2d');
        let chart;

        function initChart(data) {
            if (chart) chart.destroy();
            chart = new Chart(ctx, {
                type: 'bar',
                data: data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top', labels: { usePointStyle: true } }
                    },
                    scales: {
                        x: { stacked: true }, // ESTO HACE LAS BARRAS APILADAS
                        y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } }
                    }
                }
            });
        }

        initChart(@json($chartInitialData));

        window.livewire.on('updateChart', data => {
            initChart(data);
        });
    });
</script>
@endpush