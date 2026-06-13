<div class="container-fluid py-4">
    {{-- MENSAJES DE ÉXITO --}}
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle me-2"></i> {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- CABECERA Y FILTROS --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold mb-0 text-dark">
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
                    <select wire:model="filterAliado" class="form-select border-primary border-opacity-25">
                        <option value="">Todos los Aliados</option>
                        @foreach($aliados as $aliado)
                            <option value="{{ $aliado->id }}">{{ $aliado->name }}</option>
                        @endforeach
                    </select>
                    @if(!$filterAliado) <small class="text-danger">Seleccione un aliado para gestionar</small> @endif
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- LISTADO DE PROMOCIONES --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small fw-bold text-uppercase">
                    <tr>
                        <th class="px-4 py-3">Campaña de Promoción</th>
                        <th class="py-3 text-center">Reglas de Envío</th>
                        <th class="py-3 text-center">Alcance</th>
                        <th class="py-3 text-center">Estado</th>
                        <th class="text-end px-4">Acciones</th>
                    </tr>
                </thead>
                        <tbody>
                            @forelse($promociones as $promo)
                                <tr>
                                    <td>
                                        <div class="d-flex px-3 py-1">
                                            <div>
                                                <img src="{{ asset('storage/' . $promo->media_path) }}" class="avatar avatar-sm me-3 border-radius-lg" alt="promo">
                                            </div>
                                            <div class="d-flex flex-column justify-content-center">
                                                <h6 class="mb-0 text-sm text-dark">{{ $promo->name }}</h6>
                                                <p class="text-xs text-secondary mb-0 text-truncate" style="max-width: 200px;">
                                                    <i class="bi bi-router me-1"></i>{{ $promo->router_identity }}
                                                    @if($isAdmin) <span class="ms-1 text-primary fw-bold">| {{ $promo->user->name }}</span> @endif
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="align-middle text-center">
                                        @php $opts = $promo->options ?? []; @endphp
                                        @if($opts['on_connect'] ?? false) <span class="badge bg-soft-info text-info rounded-pill px-2 small" title="Mandar al conectarse">AL CONECTAR</span> @endif
                                        @if($opts['only_new'] ?? false) <span class="badge bg-soft-warning text-warning rounded-pill px-2 small" title="Mandar solo a clientes nuevos">SOLO NUEVOS</span> @endif
                                        @if(!($opts['on_connect'] ?? false) && !($opts['only_new'] ?? false)) <span class="text-muted text-xs">Sin reglas</span> @endif
                                    </td>
                                    <td class="align-middle text-center">
                                        <span class="text-sm font-weight-bold"><i class="bi bi-people-fill me-1 text-primary"></i>{{ $promo->responses_count }}</span>
                                    </td>
                                    <td class="align-middle text-center">
                                        <div class="form-check form-switch d-inline-block">
                                            <input class="form-check-input" type="checkbox" {{ $promo->active ? 'checked' : '' }} wire:click="toggleStatus({{ $promo->id }})">
                                        </div>
                                    </td>
                                    <td class="align-middle text-end px-4">
                                        <div class="btn-group shadow-sm rounded-3">
                                            <button wire:click="edit({{ $promo->id }})" class="btn btn-sm btn-white border">
                                                <i class="bi bi-pencil text-primary"></i>
                                            </button>
                                            <button onclick="confirm('¿Eliminar esta promoción?') || event.stopImmediatePropagation()" 
                                                    wire:click="delete({{ $promo->id }})" 
                                                    class="btn btn-sm btn-white border">
                                                <i class="bi bi-trash text-danger"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                            <tr><td colspan="5" class="text-center py-5 text-muted">No se encontraron promociones.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
        <div class="card-footer bg-white border-0 p-3">
            {{ $promociones->links() }}
        </div>
    </div>

    {{-- MODAL DINÁMICO --}}
    @if($isModalOpen)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.6); backdrop-filter: blur(5px);">
        <div class="modal-dialog modal-lg" style="margin-top: 6rem;">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 p-4 pb-0">
                    <h5 class="fw-bold mb-0 text-dark">{{ $selected_id ? 'Editar Promoción' : 'Nueva Promoción' }}</h5>
                    <button type="button" class="btn-close" wire:click="closeModal"></button>
                </div>
                
                <div class="modal-body p-4">
                    <div class="row g-3">
                        @if($isAdmin)
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted">Aliado Propietario</label>
                            <select wire:model="user_id" class="form-select @error('user_id') is-invalid @enderror">
                                <option value="">Seleccionar...</option>
                                @foreach($aliados as $aliado)
                                    <option value="{{ $aliado->id }}">{{ $aliado->name }}</option>
                                @endforeach
                            </select>
                            @error('user_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        @endif

                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted">Título de la Promoción</label>
                            <input type="text" class="form-control" wire:model="name" placeholder="Ej: ¡Hora Feliz 2x1!">
                            @error('name') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted">Texto / Descripción</label>
                            <textarea class="form-control" wire:model="description" rows="2" placeholder="Escribe aquí el contenido de la oferta..."></textarea>
                            @error('description') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted">Router de la Campaña</label>
                            <select wire:model="router_identity" class="form-select @error('router_identity') is-invalid @enderror">
                                <option value="">Seleccione un router...</option>
                                @foreach($routers as $router)
                                    <option value="{{ $router->identity }}">{{ $router->comercio_nombre }} ({{ $router->identity }})</option>
                                @endforeach
                            </select>
                            @error('router_identity') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted">Multimedia (Imagen)</label>
                            <input type="file" class="form-control" wire:model="media" accept="image/*">
                            <div wire:loading wire:target="media" class="text-xs text-info mt-1">Subiendo...</div>
                            
                            <div class="mt-3 p-3 border rounded-4 bg-light text-center" style="border-style: dashed !important;">
                                @if ($media)
                                    <img src="{{ $media->temporaryUrl() }}" class="img-fluid rounded shadow-sm" style="max-height: 150px;">
                                @elseif($current_media_path)
                                    <img src="{{ asset('storage/' . $current_media_path) }}" class="img-fluid rounded shadow-sm" style="max-height: 150px;">
                                @else
                                    <span class="text-muted small">Sin archivo seleccionado</span>
                                @endif
                            </div>
                            @error('media') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-12">
                            <div class="bg-light p-3 rounded-3">
                                <h6 class="text-xs text-uppercase text-muted mb-3">Reglas de Envío</h6>
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
                        {{ $selected_id ? 'Guardar Cambios' : 'Crear Promoción' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<style>
    .btn-white { background-color: #fff; color: #6c757d; }
    .btn-white:hover { background-color: #f8f9fa; }
    .bg-soft-info { background-color: rgba(13, 202, 240, 0.12); }
    .bg-soft-warning { background-color: rgba(255, 193, 7, 0.12); }
    .fw-800 { font-weight: 800; }
</style>
