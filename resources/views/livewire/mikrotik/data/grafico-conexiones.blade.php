<div class="container-fluid py-4">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h2 class="fw-bold text-dark mb-0">Rendimiento de Conexiones</h2>
            <p class="text-muted">Análisis diario de sesiones activas</p>
        </div>
        <div class="col-md-6 d-flex justify-content-md-end gap-2">
            <div class="flex-grow-1" style="max-width: 250px;">
                <label class="small fw-bold text-muted text-uppercase">Filtrar por Router</label>
                <select wire:model="router_id" class="form-select shadow-none border-secondary-subtle">
                    <option value="">-- Todos los Equipos --</option>
                    @foreach($routers as $r)
                        <option value="{{ $r->id }}">🟢 {{ $r->identity }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0">Conexiones en los últimos {{ $days }} días</h5>
                        @if($maxConexiones > 0)
                            <span class="badge bg-primary-subtle text-primary rounded-pill px-3">
                                Pico máximo: {{ $maxConexiones }}
                            </span>
                        @endif
                    </div>
                </div>
                <div class="card-body p-4">
                    <div style="position: relative; height:400px;" wire:ignore>
                        <canvas id="barChartConexiones"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let myChart;

    function initBarChart(labels, values) {
        const ctx = document.getElementById('barChartConexiones').getContext('2d');
        
        if (myChart) {
            myChart.destroy();
        }

        myChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Cantidad de Conexiones',
                    data: values,
                    backgroundColor: 'rgba(13, 110, 253, 0.8)',
                    borderColor: '#0d6efd',
                    borderWidth: 1,
                    borderRadius: 8, // Barras redondeadas
                    barThickness: 30
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { drawBorder: false, color: '#f0f0f0' },
                        ticks: { stepSize: 1 }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }

    // Al cargar
    document.addEventListener('DOMContentLoaded', () => {
        initBarChart(@json($labels), @json($values));
    });

    // Al actualizar filtros desde Livewire
    window.addEventListener('updateChart', event => {
        initBarChart(event.detail.labels, event.detail.values);
    });
</script>
@endpush