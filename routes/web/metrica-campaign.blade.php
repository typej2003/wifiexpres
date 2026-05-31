<div class="container-fluid py-4">
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <h4 class="fw-bold mb-4">Métricas de Campañas</h4>
            <div class="row g-3">
                @if($isAdmin)
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Seleccionar Aliado</label>
                    <select wire:model="aliadoId" class="form-select">
                        <option value="">-- Seleccione --</option>
                        @foreach($aliados as $a)
                            <option value="{{ $a->id }}">{{ $a->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Seleccionar Campaña</label>
                    <select wire:model="campaignId" class="form-select" {{ !$aliadoId ? 'disabled' : '' }}>
                        <option value="">-- Seleccione --</option>
                        @foreach($campaigns as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    @if($stats)
    <div class="row">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body text-center p-5">
                    <h1 class="display-3 fw-bold text-primary">{{ $stats['total'] }}</h1>
                    <p class="text-muted text-uppercase fw-bold mb-0">Total de Respuestas</p>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <h5 class="fw-bold mb-0">{{ $stats['question'] }}</h5>
                    <span class="badge bg-light text-dark rounded-pill">{{ $stats['type'] }}</span>
                </div>
                <div class="card-body p-4">
                    @if($stats['type'] !== 'simple')
                        <canvas id="campaignChart" height="200"></canvas>
                    @else
                        <div class="alert alert-info border-0 rounded-4">
                            Esta campaña tiene preguntas abiertas. Las respuestas se visualizan en el listado general.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let myChart;
        window.addEventListener('updateChart', event => {
            const data = event.detail;
            const ctx = document.getElementById('campaignChart');
            if (!ctx) return;

            if (myChart) {
                myChart.destroy();
            }

            myChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Votos',
                        data: data.values,
                        backgroundColor: '#6700da',
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
                }
            });
        });
    </script>
</div>