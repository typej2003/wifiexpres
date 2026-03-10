<div class="container-fluid py-4">
    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <button wire:click="backToRouters" class="btn btn-outline-secondary rounded-circle me-3 p-2 shadow-sm"><i class="bi bi-arrow-left"></i></button>
            <div>
                <h4 class="fw-bold text-dark mb-0">Gestión de Planes</h4>
                <p class="text-muted small mb-0">Socket MAC: <span class="fw-bold text-primary">{{ $router->macAddress }}</span></p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button wire:click="create" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm"><i class="bi bi-plus-lg me-1"></i> NUEVO PLAN</button>
        </div>
    </div>

    {{-- ALERTAS --}}
    @if(session()->has('message')) <div class="alert alert-success border-0 rounded-4 shadow-sm mb-4">{{ session('message') }}</div> @endif
    @if(session()->has('error')) <div class="alert alert-danger border-0 rounded-4 shadow-sm mb-4">{{ session('error') }}</div> @endif

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
                    <tr>
                        <td class="px-4 fw-bold text-dark">{{ $p->name }}</td>
                        <td class="small text-muted">S.Timeout: {{ $p->session_timeout }} | Limit: {{ $p->rate_limit ?? 'Full' }}</td>
                        <td class="fw-bold text-success">{{ number_format((float)$p->price, 0) }} Bs</td>
                        <td class="px-4 text-end">
                            <button wire:click="edit({{ $p->id }})" class="btn btn-link text-info p-0 shadow-none"><i class="bi bi-pencil-square h5"></i></button>
                            <button onclick="confirm('¿Eliminar?') || event.stopImmediatePropagation()" wire:click="destroy({{ $p->id }})" class="btn btn-link text-danger p-0 shadow-none"><i class="bi bi-trash3 h5"></i></button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center py-5 text-muted">No hay planes.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- MODAL --}}
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

                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-primary">Session Timeout</label>
                            <input type="text" wire:model.defer="session_timeout" class="form-control rounded-3 shadow-sm">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-primary">Idle Timeout</label>
                            <input type="text" wire:model.defer="idle_timeout" class="form-control rounded-3 shadow-sm">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-primary">Keepalive Timeout</label>
                            <input type="text" wire:model.defer="keepalive_timeout" class="form-control rounded-3 shadow-sm">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-success">Address Pool</label>
                            <input type="text" wire:model.defer="address_pool" class="form-control rounded-3 shadow-sm">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-success">MAC Cookie Timeout</label>
                            <input type="text" wire:model.defer="mac_cookie_timeout" class="form-control rounded-3 shadow-sm" placeholder="3d 00:00:00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-success">Status Autorefresh</label>
                            <input type="text" wire:model.defer="status_autorefresh" class="form-control rounded-3 shadow-sm">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Shared Users</label>
                            <input type="number" wire:model.defer="shared_users" class="form-control rounded-3 shadow-sm">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Rate Limit</label>
                            <input type="text" wire:model.defer="rate_limit" class="form-control rounded-3 shadow-sm" placeholder="1M/1M">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" wire:model.defer="transparent_proxy" id="pxS">
                                <label class="form-check-label small fw-bold" for="pxS">Proxy Transparente</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light p-4">
                    <button wire:click="closeModal" class="btn btn-light px-4 rounded-pill border">Cerrar</button>
                    <button wire:click="store" wire:loading.attr="disabled" class="btn btn-primary px-4 rounded-pill fw-bold">
                        <span wire:loading wire:target="store" class="spinner-border spinner-border-sm me-1"></span> GUARDAR
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>