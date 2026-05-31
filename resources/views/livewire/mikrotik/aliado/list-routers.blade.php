<div class="container-fluid py-4" wire:poll.20s="refreshStatus">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0 text-dark">Mis Equipos MikroTik</h4>
            <p class="text-muted small mb-0">Gestión de nodos para <b>{{ auth()->user()->names }}</b></p>
        </div>
        <button wire:click="create" class="btn btn-primary shadow-sm rounded-pill px-4 fw-bold">
            <i class="bi bi-plus-lg me-1"></i> AGREGAR ROUTER
        </button>
    </div>

    @if (session()->has('message'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4 alert-dismissible fade show">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4 alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        @forelse($routers as $r)
            @php $online = $routerStatus[$r->id] ?? false; @endphp
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden position-relative border-top border-4 {{ $online ? 'border-success' : 'border-danger' }}">
                    
                    <div class="position-absolute top-0 end-0 m-3 text-end">
                        <span class="badge {{ $online ? 'bg-success' : 'bg-danger' }} rounded-pill" style="font-size: 0.65rem;">
                            <i class="bi bi-{{ $online ? 'cloud-check' : 'cloud-slash' }} me-1"></i>
                            {{ $online ? 'ONLINE' : 'OFFLINE' }}
                        </span>
                    </div>

                    <div class="card-body p-4 pt-5">
                        <div class="d-flex align-items-center mb-3"> 
                            <div class="bg-primary bg-opacity-10 p-3 rounded-4 me-3">
                                <i class="bi bi-router h3 text-primary mb-0"></i>
                            </div>
                            <div class="overflow-hidden">
                                <h5 class="fw-bold mb-0 text-truncate text-uppercase">{{ $r->identity }}</h5>
                                <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill mb-1" style="font-size: 0.7rem;">
                                    <i class="bi bi-box-seam me-1"></i> {{ $r->package->name ?? 'Sin Plan' }}
                                </span>
                                <code class="text-muted d-block text-truncate small">MAC: {{ $r->macAddress }}</code>
                            </div>
                        </div>
                        
                        <div class="p-3 bg-light rounded-4 mb-3">
                            <div class="d-flex justify-content-between mb-1 small">
                                <span class="text-muted">Comercio:</span>
                                <span class="fw-bold text-dark text-truncate ms-2">{{ $r->comercio_nombre }}</span>
                            </div>
                            <div class="d-flex justify-content-between small">
                                <span class="text-muted">Ubicación:</span>
                                <span class="fw-bold text-truncate text-dark ms-2">{{ $r->location ?: 'No definida' }}</span>
                            </div>
                        </div>

                        <div class="row g-2">
                            <div class="col-6">
                                <button wire:click="edit({{ $r->id }})" class="btn btn-outline-secondary btn-sm w-100 rounded-pill fw-bold">
                                    <i class="bi bi-gear me-1"></i> CONFIG
                                </button>
                            </div>
                            <div class="col-6">
                                <a href="{{ route('aliado.router.planes', $r->id) }}" class="btn btn-outline-primary btn-sm w-100 rounded-pill fw-bold">
                                    <i class="bi bi-tags me-1"></i> PLANES
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="{{ route('mikrotik.hotspot.config', $r->id) }}" class="btn btn-outline-info btn-sm w-100 rounded-pill fw-bold">
                                    <i class="bi bi-broadcast me-1"></i> HOTSPOT
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="{{ $online ? route('aliado.tickets', $r->id) : '#' }}" 
                                class="btn btn-primary btn-sm w-100 rounded-pill fw-bold {{ !$online ? 'disabled opacity-50' : '' }}">
                                    <i class="bi bi-ticket-perforated me-1"></i> TICKETS
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="{{ route('aliado.antenas.config', $r->id) }}" class="btn btn-outline-warning btn-sm w-100 rounded-pill fw-bold">
                                    <i class="bi bi-broadcast me-1"></i> ANTENAS
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center py-5">
                <i class="bi bi-broadcast text-muted opacity-25" style="font-size: 4rem;"></i>
                <h5 class="text-muted mt-3 fw-bold">No hay routers registrados.</h5>
            </div>
        @endforelse
    </div>

    @if($isModalOpen)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); z-index: 1050; backdrop-filter: blur(4px);">
        <div class="modal-dialog modal-lg" style="margin-top: 5rem;">
            <div class="modal-content shadow-lg border-0 rounded-4">
                <div class="modal-header bg-dark text-white p-4">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-cpu-fill me-2"></i>
                        {{ $router_id ? 'CONFIGURACIÓN DEL NODO' : 'REGISTRAR NUEVO NODO' }}
                    </h5>
                    <button wire:click="closeModal" class="btn-close btn-close-white"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        {{-- SECCIÓN DE PLAN ADQUIRIDO --}}
                        <div class="col-12">
                            <div class="bg-primary bg-opacity-10 p-3 rounded-4 border-start border-4 border-primary mb-2">
                                <label class="form-label small fw-bold text-primary mb-1">PLAN DE MEMBRESÍA ADQUIRIDO</label>
                                <select wire:model="package_id" class="form-select border-0 shadow-sm">
                                    <option value="">-- Seleccionar Plan --</option>
                                    @foreach($packages as $p)
                                        <option value="{{ $p->id }}">
                                            {{ $p->name }} (Cupos contratados: {{ $p->pivot->allowed_routers }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6 mt-3">
                            <label class="form-label small fw-bold text-muted">IDENTIDAD MK</label>
                            <input type="text" wire:model.defer="identity" class="form-control bg-light border-0">
                        </div>

                        <div class="col-md-6 mt-3">
                            <label class="form-label small fw-bold text-muted">MAC ADDRESS</label>
                            <input type="text" wire:model.defer="macAddress" class="form-control bg-light border-0">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-primary">NOMBRE COMERCIAL</label>
                            <input type="text" wire:model.defer="comercio_nombre" class="form-control border-primary border-opacity-25 shadow-sm">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted text-uppercase" style="letter-spacing: 1px;">Link del Portal (Solo lectura)</label>
                            <input type="text" wire:model.defer="hotspot_url" class="form-control bg-light text-muted" readonly disabled>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted">UBICACIÓN FÍSICA</label>
                            <input type="text" wire:model.defer="location" class="form-control bg-light border-0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 p-4">
                    <button wire:click="closeModal" class="btn btn-secondary rounded-pill px-4">Cancelar</button>
                    <button wire:click.prevent="store" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold">
                        <i class="bi bi-save me-1"></i> GUARDAR CAMBIOS
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>