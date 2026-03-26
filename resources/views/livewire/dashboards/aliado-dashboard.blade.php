{{-- En tu vista, busca donde tienes el canvas y asegúrate que el contenedor tenga wire:ignore --}}
<div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
    <h6 class="fw-bold mb-4">Tráfico de Red (Conexiones)</h6>
    <div style="height: 350px;" wire:ignore> {{-- <-- IMPORTANTE: wire:ignore --}}
        <canvas id="aliadoTrafficChart"></canvas>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('livewire:load', function () {
        let chart;
        const ctx = document.getElementById('aliadoTrafficChart').getContext('2d');

        function renderChart(labels, data) {
            if (chart) {
                chart.destroy();
            }

            // Lógica de escalado (Centenas/Decenas)
            const maxVal = Math.max(...data, 0);
            let suggestedMax = 10;
            let stepSize = 1;

            if (maxVal > 100) {
                suggestedMax = Math.ceil((maxVal + 10) / 100) * 100;
                stepSize = 100;
            } else if (maxVal > 10) {
                suggestedMax = Math.ceil((maxVal + 5) / 10) * 10;
                stepSize = 10;
            }

            chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Conexiones',
                        data: data,
                        backgroundColor: '#0d6efd',
                        borderRadius: 5
                    }]
                },
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

        // Carga inicial
        renderChart(@json($chartLabels), @json($chartData));

        // Escuchar cambios de periodo
        window.livewire.on('updateChart', (labels, data) => {
            renderChart(labels, data);
        });
    });
</script>
@endpush