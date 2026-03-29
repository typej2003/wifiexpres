<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-6 text-center">
            <h4 class="fw-bold mb-3">Distribución de Conexiones</h4>
            
            {{-- SELECTOR DE PERIODO --}}
            <div class="btn-group mb-4 shadow-sm">
                <button wire:click="setPeriod('today')" class="btn btn-sm {{ $period == 'today' ? 'btn-dark' : 'btn-outline-dark' }}">Hoy</button>
                <button wire:click="setPeriod('weekly')" class="btn btn-sm {{ $period == 'weekly' ? 'btn-dark' : 'btn-outline-dark' }}">Semana</button>
                <button wire:click="setPeriod('month')" class="btn btn-sm {{ $period == 'month' ? 'btn-dark' : 'btn-outline-dark' }}">Mes</button>
            </div>

            {{-- GRÁFICO --}}
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <div style="height: 350px;" wire:ignore>
                    <canvas id="chartTortaAliado"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let myPieChart;

    function renderPie() {
        const el = document.getElementById('chartTortaAliado');
        if (!el) return;

        // Destruir si ya existe
        if (myPieChart) {
            myPieChart.destroy();
        }

        const ctx = el.getContext('2d');
        myPieChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: @json($pieLabels),
                datasets: [{
                    data: @json($pieValues),
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

    // Al cargar por primera vez
    document.addEventListener('livewire:load', () => {
        renderPie();
    });

    // Cuando Livewire actualiza el componente por el cambio de periodo
    Livewire.on('chartDataUpdated', () => {
        // Un pequeño delay para que @json reciba los datos nuevos del servidor
        setTimeout(() => {
            renderPie();
        }, 50);
    });
</script>
@endpush