<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-12">
            <h4 class="fw-bold"><i class="bi bi-graph-up-arrow text-primary me-2"></i>Análisis de Tráfico de Red</h4>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="small fw-bold text-muted mb-1 text-uppercase">Equipo / Router</label>
                    <select wire:model="router_id" class="form-select border-secondary-subtle shadow-none">
                        <option value="">📊 Todos los Equipos</option>
                        @foreach($routers as $r)
                            <option value="{{ $r->id }}">📍 {{ $r->identity }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold text-muted mb-1 text-uppercase">Rango</label>
                    <select wire:model="periodo" class="form-select border-secondary-subtle shadow-none fw-bold">
                        <option value="dia">Hoy</option>
                        <option value="semana">Última Semana</option>
                        <option value="mes">Último Mes</option>
                        <option value="personalizado">Personalizado</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold text-muted mb-1 text-uppercase">Desde</label>
                    <input type="date" wire:model="fecha_desde" class="form-control border-secondary-subtle shadow-none" {{ $periodo != 'personalizado' ? 'disabled' : '' }}>
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold text-muted mb-1 text-uppercase">Hasta</label>
                    <input type="date" wire:model="fecha_hasta" class="form-control border-secondary-subtle shadow-none" {{ $periodo != 'personalizado' ? 'disabled' : '' }}>
                </div>
                <div class="col-md-3 text-end">
                    <div class="p-2 bg-light border rounded-3 d-inline-block w-100">
                        <small class="text-muted d-block fw-bold">TOTAL SESIONES</small>
                        <h4 class="text-primary mb-0 fw-bold">{{ number_format($totalPeriodo) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-4">
                    <div style="position: relative; height:500px;" wire:ignore>
                        <canvas id="multiBarChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- TABLA DE DETALLES --}}
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white border-0 p-4">
                    <h5 class="fw-bold mb-0"><i class="bi bi-list-ul text-primary me-2"></i>Detalle de Registros</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light small fw-bold text-uppercase">
                            <tr>
                                <th class="px-4 py-3">Usuario / Ticket</th>
                                <th class="py-3">Datos del Cliente</th>
                                <th class="py-3 text-center">Género</th>
                                <th class="py-3 text-center">Edad</th>
                                <th class="py-3">Contacto / Ubicación</th>
                                <th class="py-3 text-center">Perfil</th>
                                <th class="py-3 text-center">Router</th>
                                <th class="py-3 text-center">Fecha y Hora</th>
                                <th class="py-3 text-center">Duración</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tableData as $log)
                            <tr>
                                <td class="px-4 fw-bold text-primary">{{ $log->username }}</td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $log->userMikrotik?->full_name ?: 'N/A' }}</div>
                                    <div class="small text-muted">{{ $log->userMikrotik?->email ?? '' }}</div>
                                </td>
                                <td class="text-center">
                                    @if($log->userMikrotik && $log->userMikrotik->gender)
                                        <span class="badge {{ $log->userMikrotik->gender == 'M' ? 'bg-primary' : 'bg-danger' }} bg-opacity-10 {{ $log->userMikrotik->gender == 'M' ? 'text-primary' : 'text-danger' }} rounded-pill px-2">
                                            {{ $log->userMikrotik->gender }}
                                        </span>
                                    @else - @endif
                                </td>
                                <td class="text-center fw-bold">{{ $log->userMikrotik?->age ?? '-' }}</td>
                                <td class="small">
                                    @if($log->userMikrotik)
                                        <div><i class="bi bi-telephone me-1"></i> {{ $log->userMikrotik->cellphone ?: 'N/A' }}</div>
                                        <div class="text-truncate" style="max-width: 150px;" title="{{ $log->userMikrotik->address }}">
                                            <i class="bi bi-geo-alt me-1"></i> {{ $log->userMikrotik->address ?: 'N/A' }}
                                        </div>
                                    @else N/A @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-secondary border">{{ $log->userMikrotik?->profile ?? 'N/A' }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border">{{ $log->router->identity ?? 'Mikrotik' }}</span>
                                </td>
                                <td class="text-center small">{{ $log->created_at->format('d/m/Y h:i A') }}</td>
                                <td class="text-center">
                                    <span class="badge bg-soft-info text-info rounded-pill px-3">{{ $log->duracion_formateada }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center py-4 text-muted">No hay registros para este periodo.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let multiChart;

    function renderMultiChart(labels, datasets) {
        const ctx = document.getElementById('multiBarChart').getContext('2d');
        if (multiChart) multiChart.destroy();

        multiChart = new Chart(ctx, {
            type: 'bar',
            data: { labels: labels, datasets: datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
                plugins: {
                    legend: { 
                        position: 'bottom', 
                        labels: { usePointStyle: true, padding: 25, font: { size: 12 } } 
                    },
                    tooltip: {
                        padding: 15,
                        backgroundColor: 'rgba(20, 20, 20, 0.9)',
                        titleFont: { size: 14 },
                        bodyFont: { size: 13 },
                        cornerRadius: 10
                    }
                },
                scales: {
                    y: { 
                        beginAtZero: true, 
                        grid: { color: '#f0f0f0', drawBorder: false },
                        ticks: { 
                            font: { size: 11 },
                            precision: 0
                        }
                    },
                    x: { 
                        grid: { display: false },
                        ticks: { font: { weight: 'bold' } }
                    }
                }
            }
        });
    }

    // Carga inicial al entrar a la página
    document.addEventListener('DOMContentLoaded', () => {
        renderMultiChart(@json($labels), @json($datasets));
    });

    // Actualización dinámica cuando cambian los filtros
    window.addEventListener('updateMultiChart', event => {
        renderMultiChart(event.detail.labels, event.detail.datasets);
    });
</script>
@endpush