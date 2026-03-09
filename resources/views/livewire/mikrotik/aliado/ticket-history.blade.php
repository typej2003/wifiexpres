<div class="container-fluid py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div class="d-flex align-items-center">
            <a href="{{ route('aliado.tickets', $router->id) }}" class="btn btn-outline-primary rounded-circle me-3">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <h4 class="fw-bold text-dark mb-0">Historial de Conexiones</h4>
                <p class="text-muted small mb-0">Router: <span class="fw-bold text-primary">{{ $router->identity }}</span></p>
            </div>
        </div>
        
        <div class="btn-group p-1 bg-white shadow-sm rounded-pill border">
            <button wire:click="$set('filter', 'today')" class="btn btn-sm rounded-pill px-4 {{ $filter == 'today' ? 'btn-primary shadow-sm' : 'btn-light' }}">Hoy</button>
            <button wire:click="$set('filter', 'weekly')" class="btn btn-sm rounded-pill px-4 {{ $filter == 'weekly' ? 'btn-primary shadow-sm' : 'btn-light' }}">Semana</button>
            <button wire:click="$set('filter', 'monthly')" class="btn btn-sm rounded-pill px-4 {{ $filter == 'monthly' ? 'btn-primary shadow-sm' : 'btn-light' }}">Mes</button>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-primary border-4">
                <small class="text-muted fw-bold text-uppercase" style="font-size: 0.65rem;">Conexiones Hoy</small>
                <h3 class="fw-bold mb-0 text-primary">{{ $stats['today'] }}</h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-dark border-4">
                <small class="text-muted fw-bold text-uppercase" style="font-size: 0.65rem;">Total Histórico</small>
                <h3 class="fw-bold mb-0 text-dark">{{ $stats['total'] }}</h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-success border-4">
                <small class="text-muted fw-bold text-uppercase" style="font-size: 0.65rem;">Dispositivos Únicos</small>
                <h3 class="fw-bold mb-0 text-success">{{ $stats['unique'] }}</h3>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4 py-3 text-uppercase text-xs fw-bolder text-muted">Ticket / PIN</th>
                        <th class="py-3 text-uppercase text-xs fw-bolder text-muted text-center">Dispositivo (MAC)</th>
                        <th class="py-3 text-uppercase text-xs fw-bolder text-muted text-center">Fecha y Hora</th>
                        <th class="px-4 py-3 text-end text-uppercase text-xs fw-bolder text-muted">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td class="px-4">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary bg-opacity-10 p-2 rounded-3 me-3">
                                    <i class="bi bi-ticket-perforated text-primary"></i>
                                </div>
                                <span class="fw-bold text-dark">{{ $log->username }}</span>
                            </div>
                        </td>
                        <td class="text-center">
                            <code class="px-2 py-1 bg-light rounded text-secondary small border">
                                {{ $log->mac_address ?: 'No registrada' }}
                            </code>
                        </td>
                        <td class="text-center">
                            <div class="fw-bold text-dark" style="font-size: 0.9rem;">{{ $log->created_at->format('d/m/Y') }}</div>
                            <div class="text-muted small">{{ $log->created_at->format('h:i:s A') }}</div>
                        </td>
                        <td class="px-4 text-end">
                            <span class="badge bg-success-soft text-success border border-success border-opacity-25 rounded-pill px-3 py-2">
                                <i class="bi bi-check2-circle me-1"></i> Login Exitoso
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-5">
                            <div class="opacity-50 mb-3">
                                <i class="bi bi-search" style="font-size: 3rem;"></i>
                            </div>
                            <h5 class="text-muted">No se encontraron registros</h5>
                            <p class="text-muted small">Intenta cambiando el filtro de tiempo.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
        <div class="card-footer bg-white border-0 p-4 border-top">
            {{ $logs->links() }}
        </div>
        @endif
    </div>
</div>

<style>
    .bg-success-soft { background-color: rgba(25, 135, 84, 0.1); }
    .text-xs { font-size: 0.75rem; }
</style>