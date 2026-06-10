<div class="container-fluid py-4" wire:poll.10s="refreshStatus">
    {{-- ALERTAS GLOBALES --}}
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

    {{-- HEADER Y FILTROS --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <h4 class="fw-bold mb-1 text-dark">Infraestructura Global</h4>
                    <p class="text-muted small mb-0">Gestión de nodos MikroTik por Aliado</p>
                </div>
                <div class="col-md-3">
                    <select wire:model="selectedAliado" class="form-select rounded-pill border-2 border-primary border-opacity-25 shadow-none">
                        <option value="">-- Todos los Aliados --</option>
                        @foreach($aliados as $aliado)
                            <option value="{{ $aliado->id }}">{{ $aliado->name }} ({{ $aliado->email }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5 text-end">
                    <span class="badge {{ $connectionMode === 1 ? 'bg-info' : 'bg-dark' }} rounded-pill px-3 py-2 me-2">
                        <i class="bi bi-hdd-network-fill me-1"></i> MODO: {{ $connectionMode === 1 ? 'REMOTO (DNS)' : 'LOCAL (IP)' }}
                    </span>
                    <button wire:click="refreshStatus" wire:loading.attr="disabled" class="btn btn-outline-primary rounded-pill px-4 fw-bold shadow-sm me-2">
                        <span wire:loading wire:target="refreshStatus" class="spinner-border spinner-border-sm me-1"></span>
                        <i wire:loading.remove wire:target="refreshStatus" class="bi bi-arrow-clockwise me-1"></i> REFRESCAR ESTADOS
                    </button>
                    @if($selectedAliado && ($routers->count() < $packages->sum('pivot.allowed_routers')))
                        <button wire:click="create" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
                            <i class="bi bi-plus-lg me-1"></i> NUEVO ROUTER
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- LISTADO DE CARDS --}}
    <div class="row">
        @forelse($routers as $r)
            @php $statusReal = $routerStatus[$r->id] ?? null; @endphp
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden position-relative border-top border-4 {{ $statusReal === true ? 'border-success' : ($statusReal === false ? 'border-danger' : 'border-secondary') }}">
                    
                    {{-- Botón Eliminar --}}
                    <div class="position-absolute top-0 start-0 m-3" style="z-index: 10;">
                        <button wire:click="destroy({{ $r->id }})" 
                                onclick="confirm('¡ADVERTENCIA! ¿Estás seguro de eliminar este Router? Al eliminar este router se perderán datos o quedarán datos huérfanos asociados a este equipo.') || event.stopImmediatePropagation()"
                                class="btn btn-link text-danger p-0 shadow-none" title="Eliminar Router">
                            <i class="bi bi-trash-fill"></i>
                        </button>
                    </div>

                    <div class="position-absolute top-0 end-0 m-3 text-end">
                        <div class="mb-1">
                            @if($r->status === 'Habilitado')
                                <span class="badge bg-success-soft text-success border border-success border-opacity-25 rounded-pill" style="font-size: 0.65rem;">SISTEMA: OK</span>
                            @elseif($r->status === 'Mantenimiento')
                                <span class="badge bg-warning-soft text-warning border border-warning border-opacity-25 rounded-pill" style="font-size: 0.65rem;">SISTEMA: MANTENIMIENTO</span>
                            @else
                                <span class="badge bg-danger-soft text-danger border border-danger border-opacity-25 rounded-pill" style="font-size: 0.65rem;">SISTEMA: SUSPENDIDO</span>
                            @endif
                        </div>
                        <span class="badge {{ $statusReal === true ? 'bg-success' : ($statusReal === false ? 'bg-danger' : 'bg-secondary') }} rounded-pill">
                            {{ $statusReal === true ? 'ONLINE' : ($statusReal === false ? 'OFFLINE' : 'SIN VERIFICAR') }}
                        </span>
                    </div>

                    <div class="card-body p-4 pt-5">
                        <div class="mb-3">
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2 py-1" style="font-size: 0.75rem;">
                                <i class="bi bi-box-seam-fill me-1"></i> PLAN: {{ $r->package->name ?? 'NO ASIGNADO' }}
                            </span>
                        </div>

                        <div class="d-flex align-items-center mb-3 mt-2"> 
                            <div class="bg-primary bg-opacity-10 p-3 rounded-4 me-3">
                                <i class="bi bi-router h3 text-primary mb-0"></i>
                            </div>
                            <div class="overflow-hidden">
                                <h5 class="fw-bold mb-0 text-truncate text-uppercase text-dark">{{ $r->identity }}</h5>
                                <div class="d-flex align-items-center gap-1">
                                    <small class="text-muted d-block">Versión: <b>{{ $r->hotspotVersion->name ?? 'No asignada' }}</b></small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="p-3 bg-light rounded-4 mb-3" style="font-size: 0.85rem;">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">{{ $connectionMode === 1 ? 'DNS Cloud:' : 'IP Local:' }}</span>
                                <span class="fw-bold text-dark text-truncate ms-2">{{ $connectionMode === 1 ? ($r->dns ?: $r->ip) : $r->ip }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Puerto API:</span>
                                <span class="fw-bold text-primary">{{ $r->api_port }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">MAC:</span>
                                <span class="fw-bold text-dark">{{ $r->macAddress }}</span>
                            </div>
                        </div>

                        <div class="row g-2">
                            @php $disabled = $statusReal !== true ? 'disabled opacity-50' : ''; @endphp
                            <div class="col-6">
                                <button wire:click="edit({{ $r->id }})" class="btn btn-outline-secondary btn-sm w-100 rounded-pill fw-bold">
                                    <i class="bi bi-gear me-1"></i> CONFIG
                                </button>
                            </div>
                            <div class="col-6">
                                <a href="{{ $statusReal === true ? route('admin.router.planes', $r->id) : '#' }}" class="btn btn-outline-primary btn-sm w-100 rounded-pill fw-bold {{ $disabled }}">
                                    <i class="bi bi-tags me-1"></i> PLANES
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="{{ route('mikrotik.hotspot.config', $r->id) }}" class="btn btn-outline-info btn-sm w-100 rounded-pill fw-bold">
                                    <i class="bi bi-broadcast me-1"></i> HOTSPOT
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="{{ $statusReal === true ? route('admin.router.tickets', $r->id) : '#' }}" class="btn btn-primary btn-sm w-100 rounded-pill fw-bold {{ $disabled }}">
                                    <i class="bi bi-ticket-perforated me-1"></i> TICKETS
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="{{ route('aliado.antenas.config', $r->id) }}" class="btn btn-outline-warning btn-sm w-100 rounded-pill fw-bold">
                                    <i class="bi bi-broadcast me-1"></i> ANTENAS
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="{{ route('mikrotik.herramientas.qr', $r->id) }}" class="btn btn-outline-dark btn-sm w-100 rounded-pill fw-bold">
                                    <i class="bi bi-qr-code me-1"></i> QR WIFI
                                </a>
                            </div>
                            <div class="col-12">
                                <a href="{{ $statusReal === true ? route('mikrotik.router.usuarios', $r->id) : '#' }}" class="btn btn-outline-dark btn-sm w-100 rounded-pill fw-bold {{ $disabled }}">
                                    <i class="bi bi-people-fill me-1"></i> USUARIOS HOTSPOT
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center py-5">
                <div class="bg-white rounded-4 p-5 shadow-sm d-inline-block">
                    <i class="bi bi-search text-muted opacity-25" style="font-size: 3rem;"></i>
                    <h5 class="text-muted mt-3">No se encontraron routers vinculados.</h5>
                </div>
            </div>
        @endforelse
    </div>

    {{-- MODAL --}}
    @if($isModalOpen)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); z-index: 1050; backdrop-filter: blur(4px);">
        <div class="modal-dialog modal-lg" style="margin-top: 6rem; margin-bottom: 5rem;">
            <div class="modal-content shadow-lg border-0 rounded-4">
                <div class="modal-header bg-dark text-white p-4">
                    <h5 class="modal-title fw-bold"><i class="bi bi-cpu-fill me-2"></i>DATOS TÉCNICOS DEL NODO</h5>
                    <div class="d-flex align-items-center gap-3 ms-auto">
                        @if($router_id)
                            <a href="{{ route('mikrotik.hotspot.config', $router_id) }}" class="btn btn-outline-info btn-sm rounded-pill px-3 fw-bold">
                                <i class="bi bi-broadcast me-1"></i> CONFIG. HOTSPOT
                            </a>
                        @endif
                        <button wire:click="closeModal" class="btn-close btn-close-white ms-0"></button>
                    </div>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="bg-primary bg-opacity-10 p-3 rounded-4 border-start border-4 border-primary mb-2">
                                <label class="form-label small fw-bold text-primary mb-1">PLAN DE MEMBRESÍA DEL NODO</label>
                                
                                {{-- INPUT GROUP CON EL BOTÓN DE ELIMINAR --}}
                                <div class="input-group">
                                    <select wire:model="package_id" id="package_select" class="form-select border-0 shadow-sm">
                                        <option value="">-- Seleccionar Plan --</option>
                                        @foreach($packages as $p)
                                            <option value="{{ $p->id }}">{{ $p->name }} (Límite: {{ $p->limit_routers }} routers)</option>
                                        @endforeach
                                    </select>
                                    @if($package_id)
                                        <button 
                                            type="button" 
                                            wire:click="removePackage" 
                                            onclick="confirm('¿Estás seguro de quitar el plan? Se liberará un cupo del aliado.') || event.stopImmediatePropagation()"
                                            class="btn btn-danger border-0 shadow-sm px-3" 
                                            title="Desvincular Plan"
                                        >
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    @endif
                                </div>

                                @if($packages->isEmpty())
                                    <small class="text-danger d-block mt-1">Este aliado no posee planes activos.</small>
                                @endif
                                @error('package_id') <small class="text-danger">Debe asignar un plan.</small> @enderror
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="bg-light p-3 rounded-4 border-start border-4 {{ $status === 'Habilitado' ? 'border-success' : ($status === 'Mantenimiento' ? 'border-warning' : 'border-danger') }}">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-dark mb-1">ESTADO OPERATIVO</label>
                                        <select wire:model="status" class="form-select border-0 shadow-sm">
                                            <option value="Habilitado">🟢 Habilitado (Online)</option>
                                            <option value="Mantenimiento">🟠 Mantenimiento (Offline)</option>
                                            <option value="Suspendido">🔴 Suspendido (Offline)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 text-center">
                                        <span class="badge {{ $status === 'Habilitado' ? 'bg-success' : ($status === 'Mantenimiento' ? 'bg-warning' : 'bg-danger') }} px-3 py-2 rounded-pill text-uppercase">
                                            {{ $status }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mt-4">
                            <label class="form-label small fw-bold text-muted">ALIADO PROPIETARIO</label>
                            <select wire:model="user_id" class="form-select bg-light">
                                <option value="">Seleccione...</option>
                                @foreach($aliados as $a) <option value="{{ $a->id }}">{{ $a->name }}</option> @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mt-4">
                            <label class="form-label small fw-bold text-muted">IDENTIDAD MK</label>
                            <input type="text" wire:model.defer="identity" class="form-control">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">IP / HOST</label>
                            <input type="text" wire:model.defer="ip" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">MAC ADDRESS</label>
                            <input type="text" wire:model.defer="macAddress" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">API PORT</label>
                            <input type="number" wire:model.defer="api_port" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted text-primary">UBICACIÓN</label>
                            <input type="text" wire:model.defer="location" class="form-control border-primary border-opacity-25">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">ADMIN API/FTP</label>
                            <input type="text" wire:model.defer="admin" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">PASSWORD API/FTP</label>
                            <div class="input-group">
                                <input type="{{ $showPassword ? 'text' : 'password' }}" wire:model.defer="password" class="form-control">
                                <button class="btn btn-outline-secondary" type="button" wire:click="togglePassword">
                                    <i class="bi bi-{{ $showPassword ? 'eye-slash' : 'eye' }}"></i>
                                </button>
                            </div>
                        </div>

                        <hr class="my-3">
                        
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-primary">VERSIÓN DEL PORTAL</label>
                            <select wire:model.defer="hotspot_version_id" class="form-select border-primary border-opacity-50 shadow-sm">
                                <option value="">-- Seleccionar Versión --</option>
                                @foreach($hotspotVersions as $version)
                                    <option value="{{ $version->id }}">{{ $version->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-primary">NOMBRE COMERCIAL</label>
                            <input type="text" wire:model.defer="comercio_nombre" class="form-control border-primary border-opacity-50 shadow-sm">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">URL PORTAL</label>
                            <input type="text" wire:model.defer="hotspot_url" class="form-control bg-light">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">DNS CLOUD (OPCIONAL)</label>
                            <input type="text" wire:model.defer="dns" class="form-control bg-light">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 p-4">
                    <button wire:click="closeModal" class="btn btn-secondary rounded-pill px-4">Cancelar</button>
                    <button wire:click.prevent="store" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold">GUARDAR CAMBIOS</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
    Livewire.on('updatePackageList', data => {
        const planes = data.packages || (Array.isArray(data) ? data[0].packages : null);
        const selectedId = data.selected || (Array.isArray(data) ? data[0].selected : null);
        
        const select = document.getElementById('package_select');
        
        if (select && planes) {
            select.innerHTML = '<option value="">-- Seleccionar Plan --</option>';
            planes.forEach(plan => {
                const option = document.createElement('option');
                option.value = plan.id;
                option.text = plan.name + ' (Límite: ' + plan.limit_routers + ' routers)';
                if (plan.id == selectedId) option.selected = true;
                select.appendChild(option);
            });
        }
    });
</script>
@endpush