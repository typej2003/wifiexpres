<div class="container-fluid py-4">
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="row g-3 align-items-end">
                @if(auth()->user()->role === 'admin' || auth()->user()->role === 'root')
                <div class="col-md-3">
                    <label class="small fw-bold text-muted mb-1 text-uppercase">Aliado</label>
                    <select wire:model="selectedAliado" class="form-select border-0 bg-light rounded-3 shadow-none">
                        <option value="">📊 Todos los Aliados</option>
                        @foreach($aliados as $aliado)
                            <option value="{{ $aliado->id }}">{{ $aliado->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="col-md-3">
                    <label class="small fw-bold text-muted mb-1 text-uppercase">Equipo / Router</label>
                    <select wire:model="selectedRouter" class="form-select border-0 bg-light rounded-3 shadow-none">
                        <option value="">📍 Todos los Routers</option>
                        @foreach($routers as $r)
                            <option value="{{ $r->id }}">{{ $r->identity }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="small fw-bold text-muted mb-1 text-uppercase">Rango</label>
                    <select wire:model="periodo" class="form-select border-0 bg-light rounded-3 shadow-none">
                        <option value="hoy">Hoy</option>
                        <option value="semana">Semana</option>
                        <option value="mes">Mes</option>
                        <option value="personalizado">Personalizado</option>
                    </select>
                </div>

                @if($periodo == 'personalizado')
                <div class="col-md-2">
                    <label class="small fw-bold text-muted mb-1 text-uppercase">Desde</label>
                    <input type="date" wire:model="fecha_desde" class="form-control border-0 bg-light rounded-3 shadow-none">
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold text-muted mb-1 text-uppercase">Hasta</label>
                    <input type="date" wire:model="fecha_hasta" class="form-control border-0 bg-light rounded-3 shadow-none">
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="fw-bold mb-0 text-uppercase small">Sesiones por Router</h6>
                    <span class="badge bg-primary-soft text-primary px-3 rounded-pill">Total: {{ number_format($totalConexiones ?? 0) }}</span>
                </div>
                <div style="position: relative; height:300px;" wire:ignore>
                    <canvas id="mainChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                <h6 class="fw-bold mb-4 text-uppercase small">Distribución por Género</h6>
                <div style="position: relative; height:300px;" wire:ignore>
                    <canvas id="genderChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                <h6 class="fw-bold mb-4 text-uppercase small">Rangos de Edad</h6>
                <div style="position: relative; height:300px;" wire:ignore>
                    <canvas id="ageChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-primary-soft { background-color: rgba(79, 70, 229, 0.1); }
</style>

@push('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let mainChart, genderChart, ageChart;

    function initCharts(data) {
        const ctxMain = document.getElementById('mainChart').getContext('2d');
        if (mainChart) mainChart.destroy();
        mainChart = new Chart(ctxMain, {
            type: 'pie',
            data: { 
                labels: data.routerLabels, 
                datasets: [{
                    data: data.routerValues,
                    backgroundColor: ['#4F46E5', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }] 
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { 
                    legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } } 
                }
            }
        });

        const ctxGender = document.getElementById('genderChart').getContext('2d');
        if (genderChart) genderChart.destroy();
        genderChart = new Chart(ctxGender, {
            type: 'doughnut',
            data: {
                labels: data.genderLabels,
                datasets: [{
                    data: data.genderValues,
                    backgroundColor: ['#4F46E5', '#EC4899', '#94A3B8'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } }
                }
            }
        });

        const ctxAge = document.getElementById('ageChart').getContext('2d');
        if (ageChart) ageChart.destroy();
        ageChart = new Chart(ctxAge, {
            type: 'pie',
            data: {
                labels: data.ageLabels,
                datasets: [{
                    data: data.ageValues,
                    backgroundColor: ['#6366F1', '#8B5CF6', '#D946EF', '#F43F5E'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } }
                }
            }
        });
    }

    window.addEventListener('updateCharts', event => {
        initCharts(event.detail);
    });

    document.addEventListener('livewire:load', () => {
        // Pequeño delay para asegurar que el DOM esté listo
        setTimeout(() => {
            window.livewire.emit('render');
        }, 100);
    });
</script>
@endpush