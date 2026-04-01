<div class="container-fluid py-4" wire:poll.15s="refreshStatus">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h4 class="fw-bold mb-0 text-dark">Mis Equipos MikroTik</h4>
                @if($connectionMode === 1)
                    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-3">
                        <i class="bi bi-cloud-check me-1"></i> MODO REMOTO (DNS)
                    </span>
                @else
                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-3">
                        <i class="bi bi-hdd-network me-1"></i> MODO LOCAL (IP)
                    </span>
                @endif
            </div>
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

                    <div class="position-absolute top-0 start-0 m-3">
                        <a href="{{ route('aliado.router.historial', $r->id) }}" class="text-decoration-none">
                            <span class="badge bg-dark bg-opacity-75 text-white rounded-pill" style="font-size: 0.65rem;">
                                <i class="bi bi-clock-history me-1"></i> HISTORIAL
                            </span>
                        </a>
                    </div>

                    <div class="card-body p-4 pt-5">
                        <div class="d-flex align-items-center mb-3 mt-2"> 
                            <div class="bg-primary bg-opacity-10 p-3 rounded-4 me-3">
                                <i class="bi bi-router h3 text-primary mb-0"></i>
                            </div>
                            <div class="overflow-hidden">
                                <h5 class="fw-bold mb-0 text-truncate text-uppercase">{{ $r->identity }}</h5>
                                
                                {{-- BADGE DE PLAN (NUEVO) --}}
                                <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill mb-1" style="font-size: 0.7rem;">
                                    <i class="bi bi-box-seam me-1"></i> {{ $r->package->name ?? 'Sin Plan' }}
                                </span>

                                @if($connectionMode === 1)
                                    <small class="text-primary fw-bold d-block text-truncate">
                                        <i class="bi bi-cloud-fill me-1"></i>{{ $r->dns ?: 'Sin DNS' }}
                                    </small>
                                @else
                                    <code class="text-muted d-block text-truncate">
                                        <i class="bi bi-ethernet me-1"></i>{{ $r->ip }}
                                    </code>
                                @endif
                            </div>
                        </div>
                        
                        <div class="p-3 bg-light rounded-4 mb-3">
                            <div class="d-flex justify-content-between mb-1 small">
                                <span class="text-muted">Comercio:</span>
                                <span class="fw-bold text-dark text-truncate ms-2">{{ $r->comercio_nombre }}</span>
                            </div>
                            <div class="d-flex justify-content-between small">
                                <span class="text-muted">Ubicación:</span>
                                <span class="fw-bold text-truncate text-dark ms-2">{{ $r->location ?: 'N/A' }}</span>
                            </div>
                        </div>

                        <div class="row g-2">
                            <div class="col-6">
                                <button wire:click="edit({{ $r->id }})" class="btn btn-outline-secondary btn-sm w-100 rounded-pill fw-bold">
                                    <i class="bi bi-gear me-1"></i> CONFIG
                                </button>
                            </div>
                            <div class="col-6">
                                <a href="{{ $online ? route('aliado.router.planes', $r->id) : '#' }}" 
                                class="btn btn-outline-primary btn-sm w-100 rounded-pill fw-bold {{ !$online ? 'disabled opacity-50' : '' }}">
                                    <i class="bi bi-tags me-1"></i> PLANES
                                </a>
                            </div>
                            <div class="col-12">
                                <a href="{{ $online ? route('aliado.tickets', $r->id) : '#' }}" 
                                class="btn btn-primary btn-sm w-100 rounded-pill fw-bold {{ !$online ? 'disabled opacity-50' : '' }}">
                                    <i class="bi bi-ticket-perforated me-1"></i> GENERAR TICKETS
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
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); z-index: 9999; backdrop-filter: blur(4px);">
        <div class="modal-dialog modal-lg modal-dialog-centered" style="margin-top: 6rem; margin-bottom: 5rem;"> 
            <div class="modal-content shadow-lg border-0 rounded-4">
                <div class="modal-header bg-dark text-white p-4">
                    <h5 class="modal-title fw-bold"><i class="bi bi-gear-fill me-2"></i>DATOS TÉCNICOS DEL NODO</h5>
                    <button type="button" wire:click="closeModal" class="btn-close btn-close-white"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        {{-- SECTOR DE PLANES (NUEVO) --}}
                        <div class="col-12 mb-2">
                            <div class="bg-primary bg-opacity-10 p-3 rounded-4 border-start border-4 border-primary shadow-sm">
                                <label class="form-label small fw-bold text-primary mb-1">PLAN DE SERVICIO PARA ESTE EQUIPO</label>
                                <select wire:model="package_id" class="form-select border-0">
                                    <option value="">-- Seleccionar Plan --</option>
                                    @foreach($planesDisponibles as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->limit_routers }} routers máx.)</option>
                                    @endforeach
                                </select>
                                @error('package_id') <small class="text-danger">Seleccione un plan para continuar.</small> @enderror
                            </div>
                        </div>

                        {{-- Conexión --}}
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">IP / HOST LOCAL</label>
                            <input type="text" wire:model.defer="ip" class="form-control rounded-3" placeholder="192.168.88.1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">DNS / DDNS CLOUD</label>
                            <input type="text" wire:model.defer="dns" class="form-control rounded-3 border-primary" placeholder="sn.mynetname.net">
                        </div>

                        {{-- Datos de Red --}}
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">PUERTO API</label>
                            <input type="number" wire:model.defer="api_port" class="form-control rounded-3">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">MAC ADDRESS</label>
                            <input type="text" wire:model.defer="macAddress" class="form-control rounded-3" placeholder="AA:BB:CC:DD:EE:FF">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">UBICACIÓN</label>
                            <input type="text" wire:model.defer="location" class="form-control rounded-3">
                        </div>

                        {{-- Credenciales --}}
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">USUARIO API</label>
                            <input type="text" wire:model.defer="admin" class="form-control rounded-3">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">PASSWORD API</label>
                            <div class="input-group">
                                <input type="{{ $showPassword ? 'text' : 'password' }}" wire:model.defer="password" class="form-control rounded-3">
                                <button type="button" class="btn btn-outline-secondary" wire:click="togglePassword">
                                    <i class="bi bi-{{ $showPassword ? 'eye-slash' : 'eye' }}"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="button" wire:click="testConnection" class="btn btn-info w-100 fw-bold rounded-3 text-white shadow-sm">
                                <i class="bi bi-lightning-charge me-1"></i> TEST API
                            </button>
                        </div>
                        
                        <hr class="my-4 text-muted">

                        {{-- Datos Comerciales --}}
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-primary">NOMBRE COMERCIO</label>
                            <input type="text" wire:model.defer="comercio_nombre" class="form-control rounded-3 border-primary border-opacity-25">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">IDENTIDAD (MK)</label>
                            <input type="text" wire:model="identity" class="form-control bg-light rounded-3 fw-bold" readonly>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-bold text-muted">URL PORTAL (Hotspot DNS)</label>
                            <input type="text" wire:model.defer="hotspot_url" class="form-control rounded-3" placeholder="portal.wifi">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-primary">VERSIÓN HOTSPOT</label>
                            <select wire:model.defer="hotspot_version_id" class="form-select border-primary border-opacity-25">
                                <option value="">Seleccione...</option>
                                @foreach($hotspotVersions as $v)
                                    <option value="{{ $v->id }}">{{ $v->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @if(session()->has("test_message"))
                        <div class="alert alert-success mt-3 mb-0 py-2 small fw-bold">{{ session("test_message") }}</div>
                    @endif
                    @if(session()->has("test_error"))
                        <div class="alert alert-danger mt-3 mb-0 py-2 small fw-bold">{{ session("test_error") }}</div>
                    @endif
                </div>
                <div class="modal-footer bg-light p-4">
                    <button type="button" wire:click="closeModal" class="btn btn-light rounded-pill px-4 fw-bold">Cerrar</button>
                    <button type="button" wire:click="store" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold">
                        {{ $router_id ? 'ACTUALIZAR ROUTER' : 'GUARDAR ROUTER' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>