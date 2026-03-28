<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12 text-center">
            <h2 class="fw-bold text-dark">Distribución de Conexiones</h2>
            <p class="text-muted small">Uso por cada Router asignado</p>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <div class="card-body">
                    {{-- ID ÚNICO PARA EL CANVAS --}}
                    <div style="position: relative; height:350px;" wire:ignore>
                        <canvas id="chartRouters"></canvas>
                    </div>

                    <div class="mt-4 text-center">
                        <hr class="opacity-10">
                        <h6 class="text-muted small text-uppercase fw-bold">Total de Sesiones</h6>
                        <h3 class="fw-bold text-primary">{{ number_format($totalGeneral) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Scripts --}}
@push('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    function initChart() {
        const el = document.getElementById('chartRouters');
        if (!el) return;

        // Limpiar gráfico previo si existe (evita errores al recargar)
        const existingChart = Chart.getChart("chartRouters");
        if (existingChart) {
            existingChart.destroy();
        }

        const ctx = el.getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: @json($labels),
                datasets: [{
                    data: @json($values),
                    backgroundColor: ['#0d6efd', '#212529', '#0dcaf0', '#198754', '#ffc107', '#6610f2'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }

    // Se ejecuta al cargar la página
    document.addEventListener('DOMContentLoaded', () => {
        initChart();
    });

    // Se ejecuta si Livewire vuelve a renderizar el componente
    document.addEventListener('livewire:load', () => {
        initChart();
    });
</script>
@endpush