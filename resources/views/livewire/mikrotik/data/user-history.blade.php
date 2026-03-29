<div class="container-fluid py-4">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h2 class="fw-bold text-dark mb-0">Historial de Usuario</h2>
            <p class="text-muted small mb-0">Rastreo de actividad y ubicaciones por Ticket / IP.</p>
        </div>
        <div class="col-md-6 text-end">
            <div class="d-inline-flex gap-2">
                <div class="input-group bg-white border rounded-pill px-3 shadow-sm">
                    <span class="input-group-text bg-transparent border-0"><i class="bi bi-search text-muted"></i></span>
                    <input wire:model.debounce.500ms="search" type="text" class="form-control border-0 shadow-none bg-transparent" placeholder="Buscar ticket o IP...">
                </div>
                <select wire:model="selectedRouter" class="form-select border-0 bg-white shadow-sm rounded-pill px-4">
                    <option value="">Todos los Nodos</option>
                    @foreach($misRouters as $r)
                        <option value="{{ $r->id }}">{{ $r->identity }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- FILTRO DE FECHAS Y EXPORTACIÓN --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 p-3">
                <div class="row align-items-center g-3">
                    <div class="col-md-auto">
                        <span class="small fw-bold text-muted text-uppercase"><i class="bi bi-filter me-1"></i> Filtros:</span>
                    </div>
                    <div class="col-md-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-0"><small>Desde</small></span>
                            <input type="date" wire:model="fromDate" class="form-control border-0 bg-light shadow-none">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-0"><small>Hasta</small></span>
                            <input type="date" wire:model="toDate" class="form-control border-0 bg-light shadow-none">
                        </div>
                    </div>
                    <div class="col-md text-end">
                        <button wire:click="exportPDF" wire:loading.attr="disabled" class="btn btn-danger rounded-pill px-4 btn-sm shadow-sm">
                            <span wire:loading.remove wire:target="exportPDF">
                                <i class="bi bi-file-earmark-pdf-fill me-1"></i> Exportar PDF
                            </span>
                            <span wire:loading wire:target="exportPDF">
                                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Generando...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($userStats && $search)
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center bg-primary text-white">
                <h6 class="small fw-bold opacity-75 text-uppercase">Total Sesiones</h6>
                <h2 class="fw-bold mb-0">{{ $userStats['total_conexiones'] }}</h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center bg-dark text-white">
                <h6 class="small fw-bold opacity-75 text-uppercase">Tiempo Acumulado</h6>
                <h2 class="fw-bold mb-0">
                    {{ floor($userStats['tiempo_total'] / 3600) }}h {{ floor(($userStats['tiempo_total'] / 60) % 60) }}m
                </h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center bg-info text-white">
                <h6 class="small fw-bold opacity-75 text-uppercase">Nodos Visitados</h6>
                <h2 class="fw-bold mb-0">{{ $userStats['nodos_visitados'] }}</h2>
            </div>
        </div>
    </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="bg-light small fw-bold text-muted text-uppercase">
                    <tr>
                        <th class="ps-4 py-3">Usuario / Ticket</th>
                        <th>Router</th>
                        <th>Ubicación Física</th>
                        <th>Inicio de Sesión</th>
                        <th>Duración</th>
                        <th class="pe-4 text-end">MAC Address</th>
                    </tr>
                </thead>
                <tbody class="border-top-0">
                    @forelse($logs as $log)
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center">
                                <div class="p-2 bg-primary-soft rounded-circle me-3">
                                    <i class="bi bi-person-badge text-primary"></i>
                                </div>
                                <div>
                                    <span class="fw-bold text-dark d-block">{{ $log->username }}</span>
                                    <small class="text-muted" style="font-size: 0.65rem;">ID LOG: #{{ $log->id }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border rounded-pill px-3 fw-normal">
                                <i class="bi bi-hdd-network me-1"></i> {{ $log->router->identity ?? 'MikroTik' }}
                            </span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <i class="bi bi-geo-alt-fill text-danger me-2"></i>
                                <span class="text-dark">{{ $log->ubicacion_fisica }}</span>
                            </div>
                        </td>
                        <td>
                            <div class="small">
                                <span class="d-block text-dark fw-bold">{{ $log->created_at->format('d/m/Y') }}</span>
                                <span class="text-muted" style="font-size: 0.75rem;">{{ $log->created_at->format('h:i:s A') }}</span>
                            </div>
                        </td>
                        <td>
                            <span class="badge {{ $log->duration_seconds ? 'bg-success-soft text-success' : 'bg-warning-soft text-warning' }} rounded-pill px-3">
                                <i class="bi bi-clock-history me-1"></i> {{ $log->duracion_formateada }}
                            </span>
                        </td>
                        <td class="pe-4 text-end">
                            <code class="text-primary small fw-bold">{{ $log->mac_address }}</code>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">No se encontraron registros.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $logs->links() }}
        </div>
        @endif
    </div>
</div>

<style>
    .bg-primary-soft { background-color: rgba(13, 110, 253, 0.1); }
    .bg-success-soft { background-color: rgba(25, 135, 84, 0.1); }
    .bg-warning-soft { background-color: rgba(255, 193, 7, 0.1); }
</style>