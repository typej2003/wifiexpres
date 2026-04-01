@push('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let myChart;

    function renderBarChart(labels, datasets) {
        const el = document.getElementById('chartRouters');
        if (!el) return;

        if (myChart) {
            myChart.destroy();
        }

        myChart = new Chart(el.getContext('2d'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: { 
                        position: 'bottom',
                        labels: { usePointStyle: true, padding: 15 }
                    }
                },
                scales: {
                    y: { 
                        beginAtZero: true,
                        ticks: { stepSize: 1 },
                        grid: { color: '#f8f9fa' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }

    document.addEventListener('livewire:load', function () {
        renderBarChart(@json($labels), @json($datasets));
    });

    window.addEventListener('refreshChart', event => {
        renderBarChart(event.detail.labels, event.detail.datasets);
    });
</script>
@endpush