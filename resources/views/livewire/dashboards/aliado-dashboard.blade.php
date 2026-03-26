<div class="container-fluid py-4">
    @if (session()->has('message'))
        <div class="alert alert-success">{{ session('message') }}</div>
    @endif

    <div class="row mb-4">
        <div class="col-md-6">
            <h2 class="fw-bold">Dashboard Aliado</h2>
            <p class="text-muted">Tasa BCV: Bs. {{ number_format($dollarRate, 2) }}</p>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group border shadow-sm rounded-pill bg-white p-1">
                <button wire:click="setPeriod('today')" class="btn btn-sm rounded-pill {{ $period == 'today' ? 'btn-dark' : '' }}">Hoy</button>
                <button wire:click="setPeriod('weekly')" class="btn btn-sm rounded-pill {{ $period == 'weekly' ? 'btn-dark' : '' }}">Semana</button>
                <button wire:click="setPeriod('month')" class="btn btn-sm rounded-pill {{ $period == 'month' ? 'btn-dark' : '' }}">Mes</button>
            </div>
        </div>
    </div>

    {{-- GRÁFICO --}}
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
        <h6 class="fw-bold">Conexiones {{ $period == 'today' ? 'por Hora' : 'por Router' }}</h6>
        <div style="height: 350px;">
            <canvas id="aliadoTrafficChart"></canvas>
        </div>
    </div>

    {{-- TABLA DE ROUTERS (TU DISEÑO ORIGINAL) --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white border-0 py-3">
            <h6 class="fw-bold mb-0">Mis Equipos</h6>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr class="bg-light small fw-bold">
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

    {{-- MODAL DE PLANES (TU DISEÑO ORIGINAL) --}}
    @if($showPlanModal)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5); z-index: 1050;">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">Planes Disponibles</h5>
                    @if($activePlans->isNotEmpty()) <button wire:click="closeModal" class="btn-close btn-close-white"></button> @endif
                </div>
                <div class="modal-body bg-light p-4">
                    <div class="row g-4">
                        @foreach($availablePackages as $package)
                        <div class="col-md-4">
                            <div class="card border-0 shadow-sm rounded-4 text-center p-4">
                                <h4 class="fw-bold">{{ $package->name }}</h4>
                                <h2 class="text-primary fw-bold">${{ $package->cost }}</h2>
                                <p class="text-muted small">{{ $package->limit_routers }} Router(s)</p>
                                <button wire:click="selectPlan({{ $package->id }})" class="btn btn-dark w-100 rounded-pill">Adquirir</button>
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
        let chart;
        const ctx = document.getElementById('aliadoTrafficChart').getContext('2d');

        function render(chartData) {
            if(chart) chart.destroy();

            // Lógica para escalar el eje Y a la centena o decena más próxima
            const maxVal = Math.max(...chartData.datasets[0].data, 0);
            let suggestedMax = 10;
            let stepSize = 1;

            if (maxVal > 100) {
                suggestedMax = Math.ceil((maxVal + 1) / 100) * 100;
                stepSize = 100;
            } else if (maxVal > 10) {
                suggestedMax = Math.ceil((maxVal + 1) / 10) * 10;
                stepSize = 10;
            }

            chart = new Chart(ctx, {
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
                            ticks: { stepSize: stepSize, precision: 0 },
                            grid: { borderDash: [5, 5] }
                        },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        render(@json($chartInitialData));
        window.livewire.on('updateChart', data => render(data));
    });
</script>
@endpush