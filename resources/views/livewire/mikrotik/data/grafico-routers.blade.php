<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12 text-center">
            <h2 class="fw-bold text-dark">Distribución de Conexiones</h2>
            <p class="text-muted small">Porcentaje de uso basado en tus Routers activos</p>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <div class="card-body">
                    {{-- Contenedor del Gráfico --}}
                    <div style="position: relative; height:350px;">
                        <canvas id="chartRouters"></canvas>
                    </div>

                    <div class="mt-4 text-center">
                        <hr class="opacity-10">
                        <h6 class="text-muted small text-uppercase fw-bold">Total de Sesiones Registradas</h6>
                        <h3 class="fw-bold text-primary">{{ number_format($totalGeneral) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Cargamos Chart.js solo para esta vista --}}
@push('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('livewire:load', function () {
        const ctx = document.getElementById('chartRouters').getContext('2d');
        
        const data = {
            labels: @json($labels),
            datasets: [{
                data: @json($values),
                backgroundColor: [
                    '#0d6efd', // Primary
                    '#212529', // Dark
                    '#0dcaf0', // Info
                    '#198754', // Success
                    '#ffc107', // Warning
                    '#6610f2', // Purple
                    '#fd7e14'  // Orange
                ],
                borderWidth: 2,
                borderColor: '#ffffff',
                hoverOffset: 15
            }]
        };

        new Chart(ctx, {
            type: 'pie', // Tipo Torta
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: { size: 12, family: 'sans-serif' }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                let value = context.parsed || 0;
                                let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                let percentage = ((value * 100) / total).toFixed(1);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    });
</script>
@endpush