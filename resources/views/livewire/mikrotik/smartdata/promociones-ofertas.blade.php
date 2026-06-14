<div class="container-fluid py-4">
    {{-- MENSAJES DE ÉXITO --}}
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle me-2"></i> {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- CABECERA Y FILTROS --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold mb-0">
                    <i class="bi bi-megaphone text-primary me-2"></i>Promociones y Ofertas
                </h4>
                <button wire:click="openModal" class="btn btn-primary rounded-pill px-4 shadow-sm">
                    <i class="bi bi-plus-lg me-1"></i> Nueva Promoción
                </button>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" wire:model="search" class="form-control border-start-0" placeholder="Buscar promoción...">
                    </div>
                </div>
                
                @if($isAdmin)
                <div class="col-md-3">
                    <select wire:model="filterAliado" class="form-select">
                        <option value="">Todos los Aliados</option>
                        @foreach($aliados as $aliado)
                            <option value="{{ $aliado->id }}">{{ $aliado->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- TABLA DE DATOS --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small fw-bold text-uppercase">
                    <tr>
                        <th class="px-4 py-3">Campaña / Promoción</th>
                        <th class="py-3">Segmentación</th>
                        <th class="py-3 text-center">Reglas Envío</th>
                        <th class="py-3 text-center">Alcance</th>
                        <th class="py-3 text-center">Estado</th>
                        <th class="text-end px-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($campaigns as $camp)
                    <tr wire:key="camp-row-{{ $camp->id }}">
                        <td class="px-4">
                            <span class="fw-bold d-block text-dark">{{ $camp->name }}</span>
                            @if($isAdmin) <small class="text-primary fw-semibold">{{ $camp->user->name }}</small> @endif
                        </td>
                        <td>
                            <span class="badge bg-soft-info text-info rounded-pill px-3">
                                {{ strtoupper($camp->target_gender) }} | {{ $camp->ageRange->name ?? 'Cualquier edad' }}
                            </span>
                        </td>
                        <td class="text-center">
                            @php $opts = $camp->options ?? []; @endphp
                            @if($opts['on_connect'] ?? false) <span class="badge bg-light text-dark border small">CONECTAR</span> @endif
                            @if($opts['only_new'] ?? false) <span class="badge bg-light text-primary border small">NUEVOS</span> @endif
                        </td>
                        <td class="text-center">
                            <span class="fw-bold"><i class="bi bi-people me-1"></i>{{ $camp->responses_count ?? 0 }}</span>
                        </td>
                        <td class="text-center">
                            <div class="form-check form-switch d-inline-block">
                                {{-- SWITCH CORREGIDO --}}
                                <input class="form-check-input" type="checkbox" role="switch" 
                                    wire:click="toggleStatus({{ $camp->id }})" {{ $camp->active ? 'checked' : '' }}
                                    style="cursor: pointer;">
                            </div>
                        </td>
                        <td class="text-end px-4">
                            <div class="btn-group shadow-sm rounded-3">
                                <button wire:click="selectCampaignForSending({{ $camp->id }})" 
                                        class="btn btn-sm btn-white border {{ $camp->manualSending ? 'bg-primary text-white' : '' }}" title="Seleccionar para envío manual">
                                    <i class="bi bi-send"></i>
                                </button>
                                <button wire:click="edit({{ $camp->id }})" class="btn btn-sm btn-white border">
                                    <i class="bi bi-pencil text-primary"></i>
                                </button>
                                <button onclick="confirm('¿Estás seguro?') || event.stopImmediatePropagation()" 
                                        wire:click="delete({{ $camp->id }})" 
                                        class="btn btn-sm btn-white border">
                                    <i class="bi bi-trash text-danger"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center py-5 text-muted">No se encontraron concursos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white border-0 p-3">
            {{ $campaigns->links() }}
        </div>
    </div>

    {{-- SECCIÓN DE ENVÍO MANUAL A USUARIOS --}}
    @if($selectedCampaignForSending)
    <div class="card border-0 shadow-sm rounded-4 mt-4 animate__animated animate__fadeIn">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold mb-0">
                    <i class="bi bi-people text-info me-2"></i>Usuarios del Router: <span class="text-primary">{{ $selectedCampaignForSending->router_identity }}</span>
                </h5>
                <div>
                    <button wire:click="sendPromotions" class="btn btn-success rounded-pill px-4 shadow-sm me-2" {{ empty($selectedUsers) ? 'disabled' : '' }}>
                        <i class="bi bi-send-check me-1"></i> Enviar Promoción
                    </button>
                    <button wire:click="closeUserSelection" class="btn btn-light btn-sm rounded-circle" title="Cerrar selección"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small fw-bold text-uppercase">
                        <tr>
                            <th width="40" class="px-4"></th>
                            <th>Usuario</th>
                            <th>Contacto</th>
                            <th class="text-center">SMS</th>
                            <th class="text-center">WhatsApp</th>
                            <th class="text-center">Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($usersToNotify as $user)
                        <tr wire:key="user-notify-row-{{ $user->id }}">
                            <td class="px-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="{{ $user->id }}" wire:model="selectedUsers">
                                </div>
                            </td>
                            <td>
                                <span class="fw-bold d-block">{{ $user->full_name ?? $user->name }}</span>
                                <small class="text-muted font-monospace">{{ $user->name }}</small>
                            </td>
                            <td>
                                <div class="small">
                                    <i class="bi bi-phone me-1"></i>{{ $user->cellphonecode }}{{ $user->cellphone }}<br>
                                    <i class="bi bi-envelope me-1"></i>{{ $user->email ?? 'N/A' }}
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" wire:model="deliveryMethods.{{ $user->id }}.sms">
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" wire:model="deliveryMethods.{{ $user->id }}.whatsapp">
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" wire:model="deliveryMethods.{{ $user->id }}.email">
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center py-4 text-muted">No se encontraron usuarios vinculados a este router.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $usersToNotify->links() }}
            </div>
        </div>
    </div>
    @endif

    {{-- MODAL DINÁMICO --}}
    @if($isModalOpen)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.6); backdrop-filter: blur(5px);">
        {{-- MARGIN TOP 6REM APLICADO AQUÍ --}}
        <div class="modal-dialog modal-lg" style="margin-top: 6rem;">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 p-4 pb-0">
                    <h5 class="fw-bold mb-0 text-dark">{{ $selected_id ? 'Editar Promoción' : 'Nueva Promoción' }}</h5>
                    <button type="button" class="btn-close" wire:click="closeModal"></button>
                </div>
                
                <div class="modal-body p-4">
                    <div class="row g-3">
                        @if($isAdmin)
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Aliado Propietario</label>
                            <select wire:model="user_id" class="form-select @error('user_id') is-invalid @enderror">
                                <option value="">Seleccionar...</option>
                                @foreach($aliados as $aliado)
                                    <option value="{{ $aliado->id }}">{{ $aliado->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        {{-- Selector de Routers --}}
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Router Destino</label>
                            <select wire:model="router_identity" class="form-select @error('router_identity') is-invalid @enderror">
                                <option value="">Seleccione un router...</option>
                                @foreach($routers as $router)
                                    <option value="{{ $router->identity }}">{{ $router->comercio_nombre }} ({{ $router->identity }})</option>
                                @endforeach
                            </select>
                            @error('router_identity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        @else
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted">Router Destino</label>
                            <select wire:model="router_identity" class="form-select @error('router_identity') is-invalid @enderror">
                                <option value="">Seleccione un router...</option>
                                @foreach($routers as $router)
                                    <option value="{{ $router->identity }}">{{ $router->comercio_nombre }} ({{ $router->identity }})</option>
                                @endforeach
                            </select>
                            @error('router_identity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        @endif

                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted">Título de la Promoción</label>
                            <input type="text" wire:model="name" class="form-control" placeholder="Ej: ¡Oferta 2x1 en Almuerzos!">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted">Descripción de la Oferta</label>
                            <textarea wire:model="description" class="form-control" rows="2" placeholder="Escribe aquí los detalles..."></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Género</label>
                            <select wire:model="target_gender" class="form-select">
                                <option value="todos">Todos</option>
                                <option value="masculino">Masculino</option>
                                <option value="femenino">Femenino</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Rango de Edad</label>
                            <select wire:model="age_range_id" class="form-select">
                                <option value="0">Cualquier edad</option>
                                @foreach($ageRanges as $range)
                                    <option value="{{ $range->id }}">{{ $range->name }} ({{ $range->min_age }}-{{ $range->max_age }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted">Multimedia</label>
                            <input type="file" wire:model="media" class="form-control">
                            
                            {{-- VISTA PREVIA --}}
                            <div class="mt-3 p-3 border rounded-4 bg-light text-center" style="border-style: dashed !important;">
                                @if ($media) 
                                    @if($media_type == 'imagen')
                                        <img src="{{ $media->temporaryUrl() }}" class="img-fluid rounded shadow-sm" style="max-height: 150px;">
                                    @else
                                        <div class="small text-primary">Video: {{ $media->getClientOriginalName() }}</div>
                                    @endif
                                @elseif($selected_id && $current_media_path)
                                    @if($media_type == 'imagen')
                                        <img src="{{ asset('storage/' . $current_media_path) }}" class="img-fluid rounded shadow-sm" style="max-height: 150px;">
                                    @endif
                                @else
                                    <span class="text-muted small">Sin archivo seleccionado</span>
                                @endif
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="bg-light p-3 rounded-4 border-0">
                                <h6 class="text-xs text-uppercase text-muted fw-bold mb-3">Reglas de Envío</h6>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="on_connect" wire:model="on_connect">
                                    <label class="form-check-label small fw-bold" for="on_connect">Mandar al conectarse</label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="only_new" wire:model="only_new">
                                    <label class="form-check-label small fw-bold" for="only_new">Mandar solo a clientes nuevos</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 p-4 pt-0">
                    <button wire:click="closeModal" class="btn btn-light rounded-pill px-4">Cerrar</button>
                    <button wire:click="save" class="btn btn-primary rounded-pill px-5 shadow-sm fw-bold">
                        {{ $selected_id ? 'Guardar Cambios' : 'Activar Promoción' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<style>
    .bg-soft-info { background-color: rgba(13, 202, 240, 0.12); }
    .btn-white { background-color: #fff; color: #6c757d; }
    .btn-white:hover { background-color: #f8f9fa; }
    .form-switch .form-check-input:checked { background-color: #198754; border-color: #198754; }
</style>