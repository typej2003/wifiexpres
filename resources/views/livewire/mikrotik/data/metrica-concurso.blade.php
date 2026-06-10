<div>
    {{-- Nothing in the world is as soft and yielding as water. --}}
<div class="container-fluid py-4">
    {{-- FILTROS --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <h4 class="fw-bold mb-4"><i class="bi bi-trophy text-primary me-2"></i>Métricas de Concursos</h4>
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="small fw-bold text-muted mb-1 text-uppercase">Concurso</label>
                    <select wire:model="selectedConcurso" class="form-select border-0 bg-light rounded-3 shadow-none">
                        <option value="">-- Seleccionar Concurso --</option>
                        @foreach($concursos as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold text-muted mb-1 text-uppercase">Router</label>
                    <select wire:model="selectedRouter" class="form-select border-0 bg-light rounded-3 shadow-none">
                        <option value="">📍 Todos los Routers</option>
                        @foreach($routers as $r)
                            <option value="{{ $r->id }}">{{ $r->identity }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold text-muted mb-1 text-uppercase">Desde</label>
                    <input type="date" wire:model="fromDate" class="form-control border-0 bg-light rounded-3 shadow-none">
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold text-muted mb-1 text-uppercase">Hasta</label>
                    <input type="date" wire:model="toDate" class="form-control border-0 bg-light rounded-3 shadow-none">
                </div>
                <div class="col-md-2">
                    <button wire:click="consultar" class="btn btn-primary w-100 rounded-pill fw-bold shadow-sm">
                        <i class="bi bi-search me-1"></i> FILTRAR
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if($stats)
    {{-- STATS --}}
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center h-100">
                <h6 class="text-muted small fw-bold text-uppercase">Total Participaciones</h6>
                <h2 class="fw-bold mb-0 text-primary">{{ number_format($stats['total_participantes']) }}</h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center h-100">
                <h6 class="text-muted small fw-bold text-uppercase">Usuarios Únicos</h6>
                <h2 class="fw-bold mb-0 text-dark">{{ number_format($stats['usuarios_unicos']) }}</h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center h-100 border-start border-4 border-info">
                <h6 class="text-muted small fw-bold text-uppercase">Hombres</h6>
                <h2 class="fw-bold mb-0 text-info">{{ number_format($stats['hombres']) }}</h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center h-100 border-start border-4 border-danger">
                <h6 class="text-muted small fw-bold text-uppercase">Mujeres</h6>
                <h2 class="fw-bold mb-0 text-danger">{{ number_format($stats['mujeres']) }}</h2>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- GRÁFICO --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                <h6 class="fw-bold mb-4 text-uppercase small">Distribución de Respuestas</h6>
                <div style="position: relative; height:300px;" wire:ignore>
                    <canvas id="concursoChart"></canvas>
                </div>
            </div>
        </div>

        {{-- TABLA DETALLE --}}
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <h6 class="fw-bold mb-0 text-uppercase small">Últimos Participantes</h6>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="bg-light small fw-bold text-muted text-uppercase">
                            <tr>
                                <th class="ps-4">Participante</th>
                                <th>Respuesta</th>
                                <th>Router</th>
                                <th class="pe-4 text-end">Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tableData as $data)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark">{{ $data->full_name ?: 'Anónimo' }}</div>
                                    <small class="text-muted">{{ $data->cellphonecode }}{{ $data->cellphone }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill">
                                        {{ $data->answer }}
                                    </span>
                                </td>
                                <td><small class="fw-bold">{{ $data->router_identity }}</small></td>
                                <td class="pe-4 text-end small text-muted">{{ $data->created_at->format('d/m/y h:i A') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center py-5">No hay respuestas registradas</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif

    @push('js')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let concursoChart;
        function initConcursoChart(labels, values) {
            const ctx = document.getElementById('concursoChart').getContext('2d');
            if (concursoChart) concursoChart.destroy();
            concursoChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Votos',
                        data: values,
                        backgroundColor: '#6700da',
                        borderRadius: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                }
            });
        }
        window.addEventListener('updateConcursoChart', event => {
            initConcursoChart(event.detail.labels, event.detail.values);
        });
    </script>
    @endpush
</div>
</div>
