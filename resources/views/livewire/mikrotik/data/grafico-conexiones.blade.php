<div class="container-fluid py-4">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h3 class="fw-bold text-dark mb-1">Comparativa de Routers</h3>
            <p class="text-muted small">Conexiones por equipo en los últimos {{ $days }} días</p>
        </div>
        <div class="col-md-6 d-flex justify-content-md-end">
            <div class="card border-0 shadow-sm p-2" style="min-width: 280px;">
                <select wire:model="router_id" class="form-select border-0 shadow-none">
                    <option value="">📊 Comparar todos los Routers</option>
                    @foreach($routers as $r)
                        <option value="{{ $r->id }}">📍 {{ $r->identity }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between">
                    <h5 class="fw-bold mb-0">Distribución de Carga</h5>
                    <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2">
                        Total Periodo: {{ number_format($totalGeneral) }}
                    </span>
                </div>
                <div class="card-body p-4">
                    <div style="position: relative; height:450px;" wire:ignore>
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
        
        if (multiChart) {
            multiChart.destroy();
        }

        multiChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { 
                        position: 'bottom',
                        labels: { usePointStyle: true, padding: 20, font: { size: 12, weight: 'bold' } }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        padding: 12,
                        backgroundColor: 'rgba(33, 37, 41, 0.9)',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f0f0f0', drawBorder: false },
                        ticks: { stepSize: 5 }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { weight: 'bold' } }
                    }
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        renderMultiChart(@json($labels), @json($datasets));
    });

    window.addEventListener('updateMultiChart', event => {
        renderMultiChart(event.detail.labels, event.detail.datasets);
    });
</script>
@endpush