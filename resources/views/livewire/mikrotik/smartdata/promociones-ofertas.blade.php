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
            </div>
        </div>
    </div>

    {{-- LISTADO DE PROMOCIONES --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small fw-bold text-uppercase">
                    <tr>
                        <th class="px-4 py-3">Promoción</th>
                        <th class="py-3 text-center">Reglas</th>
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
                                                <img src="{{ Storage::url($promo->media_path) }}" class="avatar avatar-sm me-3 border-radius-lg" alt="promo">
                                            </div>
                                            <div class="d-flex flex-column justify-content-center">
                                                <h6 class="mb-0 text-sm">{{ $promo->name }}</h6>
                                                <p class="text-xs text-secondary mb-0 text-truncate" style="max-width: 150px;">{{ $promo->router_identity }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="align-middle text-center">
                                        @php $opts = $promo->options ?? []; @endphp
                                        @if($opts['on_connect'] ?? false) <span class="badge badge-sm bg-info" title="Al conectar">AC</span> @endif
                                        @if($opts['only_new'] ?? false) <span class="badge badge-sm bg-warning" title="Solo nuevos">SN</span> @endif
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
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted">Título de la Promoción</label>
                            <input type="text" class="form-control" wire:model="name" placeholder="Ej: ¡Hora Feliz 2x1!">
                            @error('name') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted">Texto / Descripción</label>
                            <textarea class="form-control" wire:model="description" rows="3" placeholder="Escribe aquí el contenido de la oferta..."></textarea>
                            @error('description') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted">Local / Router destino</label>
                            <select class="form-select" wire:model="router_identity">
                                <option value="">Seleccione Local...</option>
                                @foreach($routers as $router)
                                    <option value="{{ $router->identity }}">{{ $router->identity }} ({{ $router->comercio_nombre }})</option>
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
                                    <img src="{{ Storage::url($current_media_path) }}" class="img-fluid rounded shadow-sm" style="max-height: 150px;">
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
</style>
