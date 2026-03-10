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
        <div class="alert alert-success border-0 rounded-4 shadow-sm mb-4">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('message') }}
        </div> 
    @endif
    @if(session()->has('error')) 
        <div class="alert alert-danger border-0 rounded-4 shadow-sm mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
        </div> 
    @endif

    {{-- TABLA LOCAL --}}
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
                    <tr>
                        <td class="px-4 fw-bold">{{ $p->name }}</td>
                        <td class="small text-muted">S: {{ $p->session_timeout }} | Limit: {{ $p->rate_limit ?? 'Infinito' }}</td>
                        <td class="fw-bold text-success">{{ number_format((float)$p->price, 0) }} Bs</td>
                        <td class="px-4 text-end">
                            <button wire:click="edit({{ $p->id }})" class="btn btn-link text-info"><i class="bi bi-pencil-square h5"></i></button>
                            <button onclick="confirm('¿Eliminar?') || event.stopImmediatePropagation()" wire:click="destroy({{ $p->id }})" class="btn btn-link text-danger"><i class="bi bi-trash3 h5"></i></button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center py-5">No hay planes registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- MODAL NUEVO/EDITAR --}}
    @if($isModalOpen)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.6); z-index: 1060; backdrop-filter: blur(5px);">
        <div class="modal-dialog modal-lg" style="margin-top: 5rem;">
            <div class="modal-content border-0 rounded-4 shadow-lg">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">{{ $plan_id ? 'Editar Plan' : 'Nuevo Plan' }}</h5>
                    <button wire:click="closeModal" class="btn-close btn-close-white"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Nombre (Ej: 1 Hora)</label>
                            <input type="text" wire:model.defer="tiempo_display" class="form-control rounded-3">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Precio (Bs)</label>
                            <input type="number" wire:model.defer="price" class="form-control rounded-3">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Session Timeout</label>
                            <input type="text" wire:model.defer="session_timeout" class="form-control rounded-3">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-bold small">Rate Limit (Bajada/Subida)</label>
                            <input type="text" wire:model.defer="rate_limit" class="form-control rounded-3" placeholder="2M/2M">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button wire:click="closeModal" class="btn btn-light rounded-pill border">Cerrar</button>
                    <button wire:click="store" wire:loading.attr="disabled" class="btn btn-primary rounded-pill fw-bold">
                        <span wire:loading wire:target="store" class="spinner-border spinner-border-sm"></span> GUARDAR
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- MODAL DE SINCRONIZACIÓN --}}
    @if($isSyncModalOpen)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.7); z-index: 1070; backdrop-filter: blur(8px);">
        <div class="modal-dialog modal-xl" style="margin-top: 3rem;">
            <div class="modal-content border-0 rounded-4 shadow-lg">
                <div class="modal-header bg-info text-white p-4">
                    <h5 class="modal-title fw-bold"><i class="bi bi-cloud-download me-2"></i>PERFILES DETECTADOS EN MIKROTIK</h5>
                    <button wire:click="closeModal" class="btn-close btn-close-white"></button>
                </div>
                <div class="modal-body p-4">
                    {{-- DEBUG INFO --}}
                    <div class="alert alert-dark p-2 rounded-3 shadow-sm mb-4 overflow-auto" style="max-height: 100px;">
                        <small class="fw-bold d-block text-uppercase border-bottom border-secondary mb-1">Respuesta Cruda del Router:</small>
                        <code class="text-info small" style="word-break: break-all;">{{ $debugRaw }}</code>
                    </div>

                    <div class="table-responsive rounded-3 border">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr class="small fw-bold">
                                    <th>PERFIL</th>
                                    <th>SESIÓN</th>
                                    <th>LIMIT</th>
                                    <th>COOKIE</th>
                                    <th>PRECIO CALC.</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($mikrotikProfiles as $mp)
                                <tr>
                                    <td class="fw-bold text-dark">{{ $mp['name'] }}</td>
                                    <td>{{ $mp['session_timeout'] }}</td>
                                    <td><span class="badge bg-secondary">{{ $mp['rate_limit'] }}</span></td>
                                    <td>{{ $mp['add_mac_cookie'] }}</td>
                                    <td class="fw-bold text-success">{{ $mp['price'] }} Bs</td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center py-4 text-muted">No se procesaron perfiles. Revisa el "Respuesta Cruda" arriba.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light p-4">
                    <button wire:click="closeModal" class="btn btn-secondary px-4 rounded-pill">CANCELAR</button>
                    @if(count($mikrotikProfiles) > 0)
                    <button wire:click="syncDatabase" class="btn btn-success px-5 rounded-pill fw-bold shadow-sm">
                        <i class="bi bi-check-all me-1"></i> ACEPTAR Y SINCRONIZAR DB
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>