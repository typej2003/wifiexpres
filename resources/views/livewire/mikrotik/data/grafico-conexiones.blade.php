<div class="container-fluid py-4">
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="small fw-bold text-muted mb-1 text-uppercase">Router</label>
                    <select wire:model="router_id" class="form-select border-secondary-subtle shadow-none">
                        <option value="">📊 Comparativa Todos</option>
                        @foreach($routers as $r)
                            <option value="{{ $r->id }}">📍 {{ $r->identity }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold text-muted mb-1 text-uppercase">Periodo</label>
                    <select wire:model="periodo" class="form-select border-secondary-subtle shadow-none text-primary fw-bold">
                        <option value="dia">Hoy</option>
                        <option value="semana">Última Semana</option>
                        <option value="mes">Último Mes</option>
                        <option value="personalizado">Personalizado</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold text-muted mb-1 text-uppercase">Desde</label>
                    <input type="date" wire:model="fecha_desde" class="form-control border-secondary-subtle shadow-none" {{ $periodo != 'personalizado' ? 'disabled' : '' }}>
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold text-muted mb-1 text-uppercase">Hasta</label>
                    <input type="date" wire:model="fecha_hasta" class="form-control border-secondary-subtle shadow-none" {{ $periodo != 'personalizado' ? 'disabled' : '' }}>
                </div>
                <div class="col-md-3 text-end">
                    <div class="p-2 bg-primary-subtle rounded-3 d-inline-block">
                        <small class="text-primary d-block fw-bold">TOTAL SESIONES</small>
                        <h4 class="text-primary mb-0 fw-bold">{{ number_format($totalPeriodo) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-4">
                    <div style="position: relative; height:500px;" wire:ignore>
                        <canvas id="multiBarChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let multiChart;

    function renderMultiChart(labels, datasets) {
        const ctx = document.getElementById('multiBarChart').getContext('2d');
        if (multiChart) multiChart.destroy();

        multiChart = new Chart(ctx, {
            type: 'bar',
            data: { labels: labels, datasets: datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 25 } },
                    tooltip: { mode: 'index', intersect: false, padding: 15 }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f5f5f5' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => renderMultiChart(@json($labels ?? []), @json($datasets ?? [])));
    window.addEventListener('updateMultiChart', event => renderMultiChart(event.detail.labels, event.detail.datasets));
</script>
@endpush