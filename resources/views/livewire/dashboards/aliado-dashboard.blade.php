<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                <h5 class="fw-bold mb-4">Distribución de Conexiones</h5>
                
                {{-- Selector de Periodo --}}
                <div class="btn-group mb-4 shadow-sm">
                    <button wire:click="setPeriod('today')" class="btn btn-sm {{ $period == 'today' ? 'btn-dark' : 'btn-outline-dark' }}">Hoy</button>
                    <button wire:click="setPeriod('weekly')" class="btn btn-sm {{ $period == 'weekly' ? 'btn-dark' : 'btn-outline-dark' }}">Semana</button>
                    <button wire:click="setPeriod('month')" class="btn btn-sm {{ $period == 'month' ? 'btn-dark' : 'btn-outline-dark' }}">Mes</button>
                </div>

                {{-- Contenedor del Gráfico --}}
                <div style="height: 350px;" wire:ignore>
                    <canvas id="chartTorta"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let pieChart;

    function initPieChart() {
        const ctx = document.getElementById('chartTorta');
        if (!ctx) return;

        if (pieChart) pieChart.destroy();

        pieChart = new Chart(ctx, {
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

    document.addEventListener('livewire:load', () => {
        initPieChart();
        
        Livewire.on('updateChart', () => {
            setTimeout(() => { initPieChart(); }, 100);
        });
    });
</script>
@endpush