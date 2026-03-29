{{-- En la sección de Tráfico de Red --}}
<div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
    <div style="position: relative; height:350px;" wire:ignore>
        <canvas id="chartRouters"></canvas>
    </div>
    <div class="mt-4 text-center">
        <h6 class="text-muted small text-uppercase fw-bold">Total de Sesiones</h6>
        <h3 class="fw-bold text-primary">{{ number_format($totalGeneral) }}</h3>
    </div>
</div>

@push('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    function initChart() {
        const el = document.getElementById('chartRouters');
        if (!el) return;

        const existingChart = Chart.getChart("chartRouters");
        if (existingChart) { existingChart.destroy(); }

        new Chart(el.getContext('2d'), {
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
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', initChart);
    document.addEventListener('livewire:load', initChart);
</script>
@endpush