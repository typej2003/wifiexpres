<div class="container-fluid py-4">
    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <button wire:click="backToRouters" class="btn btn-outline-secondary rounded-circle me-3 p-2 shadow-sm">
                <i class="bi bi-arrow-left"></i>
            </button>
            <div>
                <h4 class="fw-bold text-dark mb-0">Gestión de Planes</h4>
                <p class="text-muted small mb-0">Socket MAC: <span class="fw-bold text-primary">{{ $router->macAddress }}</span></p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button wire:click="solicitarIdentity" wire:loading.attr="disabled" class="btn btn-outline-dark rounded-pill px-4 fw-bold shadow-sm">
                <span wire:loading wire:target="solicitarIdentity" class="spinner-border spinner-border-sm me-1"></span>
                <i wire:loading.remove wire:target="solicitarIdentity" class="bi bi-info-circle me-1"></i> VER IDENTITY
            </button>

            <button wire:click="openSyncModal" wire:loading.attr="disabled" class="btn btn-outline-info rounded-pill px-4 fw-bold shadow-sm">
                <span wire:loading wire:target="openSyncModal" class="spinner-border spinner-border-sm me-1"></span>
                <i wire:loading.remove wire:target="openSyncModal" class="bi bi-arrow-repeat me-1"></i> SINCRONIZAR
            </button>
            <button wire:click="create" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
                <i class="bi bi-plus-lg me-1"></i> NUEVO PLAN
            </button>
        </div>
    </div>

    {{-- ALERTAS --}}
    @if(session()->has('message')) 
        <div class="alert alert-success border-0 rounded-4 shadow-sm mb-4 d-flex align-items-center">
            <span wire:loading wire:target="store" class="spinner-border spinner-border-sm me-3 text-success"></span>
            <i wire:loading.remove wire:target="store" class="bi bi-check-circle-fill me-2 h5 mb-0"></i> 
            <div>{{ session('message') }}</div>
        </div> 
    @endif
    @if(session()->has('error')) 
        <div class="alert alert-danger border-0 rounded-4 shadow-sm mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2 h5 mb-0"></i> {{ session('error') }}
        </div> 
    @endif

    {{-- INDICADOR DE CARGA GLOBAL --}}
    <div wire:loading wire:target="store" class="w-100 mb-4">
        <div class="progress rounded-pill shadow-sm" style="height: 10px;">
            <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 100%"></div>
        </div>
        <p class="text-center small text-primary fw-bold mt-2 animate__animated animate__pulse animate__infinite">Comunicando con MikroTik...</p>
    </div>

    {{-- TABLA --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4 py-3">Perfil</th>
                        <th class="py-3">Configuración</th>
                        <th class="py-3">Precio</th>
                        <th class="px-4 py-3 text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($plans as $p)
                    <tr wire:loading.class="opacity-50" wire:target="store">
                        <td class="px-4 fw-bold text-dark">{{ $p->name }}</td>
                        <td class="small text-muted">
                            Session: {{ $p->session_timeout }} | 
                            Idle: {{ $p->idle_timeout ?? 'none' }} | 
                            Limit: {{ $p->rate_limit ?? 'Full' }}
                        </td>
                        <td class="fw-bold text-success">{{ number_format((float)$p->price, 0) }} Bs</td>
                        <td class="px-4 text-end">
                            <div class="d-flex justify-content-end gap-2">
                                <button wire:click="edit({{ $p->id }})" class="btn btn-link text-info p-0 shadow-none">
                                    <i class="bi bi-pencil-square h5"></i>
                                </button>

                                <button onclick="confirm('¿Estás seguro de eliminar este plan?') || event.stopImmediatePropagation()" 
                                        wire:click="destroy({{ $p->id }})" 
                                        class="btn btn-link text-danger p-0 shadow-none">
                                    <span wire:loading wire:target="destroy({{ $p->id }})" class="spinner-border spinner-border-sm"></span>
                                    <i wire:loading.remove wire:target="destroy({{ $p->id }})" class="bi bi-trash3 h5"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center py-5 text-muted">No hay planes registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- MODAL NUEVO/EDITAR --}}
    @if($isModalOpen)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.6); z-index: 1060; backdrop-filter: blur(5px);">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg">
                <div class="modal-header bg-dark text-white p-4">
                    <h5 class="modal-title fw-bold text-uppercase">{{ $plan_id ? 'Editar Plan' : 'Nuevo Plan' }}</h5>
                    <button wire:click="closeModal" class="btn-close btn-close-white shadow-none"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nombre/Tiempo</label>
                            <input type="text" wire:model.defer="tiempo_display" class="form-control rounded-3 shadow-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Precio (Bs)</label>
                            <input type="number" wire:model.defer="price" class="form-control rounded-3 shadow-sm">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Session Timeout</label>
                            <input type="text" wire:model.defer="session_timeout" class="form-control rounded-3 shadow-sm">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Idle Timeout</label>
                            <input type="text" wire:model.defer="idle_timeout" class="form-control rounded-3 shadow-sm">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Shared Users</label>
                            <input type="number" wire:model.defer="shared_users" class="form-control rounded-3 shadow-sm">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Rate Limit</label>
                            <input type="text" wire:model.defer="rate_limit" class="form-control rounded-3 shadow-sm" placeholder="1M/1M">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light p-4">
                    <button wire:click="closeModal" class="btn btn-light px-4 rounded-pill border shadow-sm">Cerrar</button>
                    <button wire:click="store" wire:loading.attr="disabled" class="btn btn-primary px-4 rounded-pill fw-bold shadow-sm">
                        <span wire:loading wire:target="store" class="spinner-border spinner-border-sm me-1"></span>
                        GUARDAR EN MIKROTIK
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- MODAL SINCRONIZAR --}}
    @if($isSyncModalOpen)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.6); z-index: 1060; backdrop-filter: blur(5px);">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg">
                <div class="modal-header bg-info text-white p-4">
                    <h5 class="modal-title fw-bold text-uppercase">Sincronización</h5>
                    <button wire:click="closeModal" class="btn-close btn-close-white shadow-none"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive" style="max-height: 400px;">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light sticky-top">
                                <tr>
                                    <th class="px-4 py-3 border-0">Perfil</th>
                                    <th class="border-0">Shared</th>
                                    <th class="border-0">S. Timeout</th>
                                    <th class="border-0">I. Timeout</th>
                                    <th class="px-4 text-end border-0">Rate Limit</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($mikrotikProfiles as $mp)
                                <tr>
                                    <td class="px-4">
                                        <span class="fw-bold text-dark">{{ $mp['name'] }}</span>
                                    </td>
                                    <td>{{ $mp['shared_users'] }}</td>
                                    <td>{{ $mp['session_timeout'] }}</td>
                                    <td>{{ $mp['idle_timeout'] }}</td>
                                    <td class="px-4 text-end">
                                        <span class="badge bg-secondary rounded-pill">{{ $mp['rate_limit'] }}</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light p-4 d-flex justify-content-between">
                    <button wire:click="closeModal" class="btn btn-light px-4 rounded-pill border shadow-sm">CANCELAR</button>
                    <button wire:click="syncDatabase(false)" class="btn btn-info px-4 rounded-pill fw-bold text-white shadow-sm">GUARDAR EN BD</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>