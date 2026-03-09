<div class="container-fluid py-4">
    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <button wire:click="backToRouters" class="btn btn-outline-secondary rounded-circle me-3 p-2 shadow-sm">
                <i class="bi bi-arrow-left"></i>
            </button>
            <div>
                <h4 class="fw-bold text-dark mb-0">Gestión de Usuarios Hotspot</h4>
                <p class="text-muted small mb-0">
                    Router: <span class="fw-bold text-primary">{{ $router->name }}</span> | 
                    MAC: <span class="fw-bold">{{ $router->macAddress }}</span>
                </p>
            </div>
        </div>
        <button wire:click="loadUsers" wire:loading.attr="disabled" class="btn btn-primary rounded-pill px-4 shadow-sm">
            <span wire:loading wire:target="loadUsers" class="spinner-border spinner-border-sm me-1"></span>
            <i wire:loading.remove wire:target="loadUsers" class="bi bi-arrow-clockwise me-1"></i> ACTUALIZAR LISTA
        </button>
    </div>

    {{-- SELECTOR DE MODO --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="bg-white p-2 rounded-4 shadow-sm d-flex gap-2">
                <button wire:click="setViewMode('active')" 
                        class="btn flex-fill rounded-pill py-2 fw-bold {{ $viewMode === 'active' ? 'btn-dark' : 'btn-light text-muted' }}">
                    <i class="bi bi-lightning-charge-fill me-2"></i> 1. USUARIOS ACTIVOS
                </button>
                <button wire:click="setViewMode('all')" 
                        class="btn flex-fill rounded-pill py-2 fw-bold {{ $viewMode === 'all' ? 'btn-dark' : 'btn-light text-muted' }}">
                    <i class="bi bi-people-fill me-2"></i> 2. TODOS LOS USUARIOS
                </button>
            </div>
        </div>
    </div>

    @if(session()->has('error')) 
        <div class="alert alert-danger border-0 rounded-4 shadow-sm mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
        </div> 
    @endif

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table align-middle mb-0 table-hover">
                <thead class="bg-light">
                    @if($viewMode === 'active')
                        <tr>
                            <th class="px-4 py-3">Usuario</th>
                            <th>IP Address</th>
                            <th>MAC Address</th>
                            <th class="px-4 text-end">Uptime</th>
                        </tr>
                    @else
                        <tr>
                            <th class="px-4 py-3">Usuario</th>
                            <th>Perfil</th>
                            <th class="px-4 text-end">Comentario</th>
                        </tr>
                    @endif
                </thead>
                <tbody>
                    @forelse($users as $u)
                        <tr>
                            @if($viewMode === 'active')
                                <td class="px-4 fw-bold text-primary">{{ $u['username'] }}</td>
                                <td class="text-muted small">{{ $u['ip'] }}</td>
                                <td class="text-muted small">{{ $u['mac'] }}</td>
                                <td class="px-4 text-end">
                                    <span class="badge bg-success text-white rounded-pill px-3">{{ $u['uptime'] }}</span>
                                </td>
                            @else
                                <td class="px-4 fw-bold text-dark">{{ $u['username'] }}</td>
                                <td><span class="badge bg-info text-dark rounded-pill">{{ $u['profile'] }}</span></td>
                                <td class="px-4 text-end small text-muted italic">{{ $u['comment'] }}</td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                No se encontraron registros.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        {{-- PAGINACIÓN --}}
        @if($users->hasPages())
            <div class="card-footer bg-white border-top-0 p-3">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>