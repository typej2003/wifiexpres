<div class="container-fluid py-4">
    <div class="row">
        <!-- Formulario de Creación -->
        <div class="col-md-5">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-gradient-primary p-3">
                    <h6 class="text-white mb-0"><i class="bi bi-megaphone-fill me-2"></i>Nueva Promoción / Oferta</h6>
                </div>
                <div class="card-body">
                    @if (session()->has('message'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <span class="alert-text"><strong>¡Éxito!</strong> {{ session('message') }}</span>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="form-group mb-3">
                        <label class="form-control-label">Título de la Promoción</label>
                        <input type="text" class="form-control" wire:model="name" placeholder="Ej: ¡Hora Feliz 2x1!">
                        @error('name') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-control-label">Texto / Descripción</label>
                        <textarea class="form-control" wire:model="description" rows="3" placeholder="Escribe aquí el contenido de la oferta..."></textarea>
                        @error('description') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-control-label">Local / Router destino</label>
                        <select class="form-select" wire:model="router_identity">
                            <option value="">Seleccione Local...</option>
                            @foreach($routers as $router)
                                <option value="{{ $router->identity }}">{{ $router->identity }} ({{ $router->comercio_nombre }})</option>
                            @endforeach
                        </select>
                        @error('router_identity') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group mb-4">
                        <label class="form-control-label">Imagen Publicitaria</label>
                        <input type="file" class="form-control" wire:model="media" accept="image/*">
                        <div wire:loading wire:target="media" class="text-xs text-info mt-1">Subiendo...</div>
                        @if ($media)
                            <img src="{{ $media->temporaryUrl() }}" class="img-fluid mt-2 rounded border" style="max-height: 150px;">
                        @elseif($current_media_path)
                            <img src="{{ Storage::url($current_media_path) }}" class="img-fluid mt-2 rounded border" style="max-height: 150px;">
                        @endif
                        @error('media') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="bg-light p-3 rounded-3 mb-4">
                        <h6 class="text-xs text-uppercase text-muted mb-3">Reglas de Envío</h6>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="on_connect" wire:model="on_connect">
                            <label class="form-check-label" for="on_connect">Mandar al conectarse</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="only_new" wire:model="only_new">
                            <label class="form-check-label" for="only_new">Mandar solo a clientes nuevos</label>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button class="btn btn-primary w-100" wire:click="save" wire:loading.attr="disabled">
                            {{ $selected_id ? 'Actualizar Promoción' : 'Crear y Activar' }}
                        </button>
                        @if($selected_id)
                            <button class="btn btn-outline-secondary" wire:click="resetInputFields">Cancelar</button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Promociones -->
        <div class="col-md-7">
            <div class="card shadow-sm border-0">
                <div class="card-header p-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Promociones Activas e Historial</h6>
                    <div class="input-group input-group-sm w-40">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control border-start-0" placeholder="Buscar..." wire:model="search">
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table align-items-center mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Promoción</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Reglas</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Alcance</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Estado</th>
                                <th class="text-end px-4 py-3"></th>
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
                                        <button class="btn btn-link text-primary p-1 mb-0" wire:click="edit({{ $promo->id }})">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button class="btn btn-link text-danger p-1 mb-0" onclick="confirm('¿Eliminar esta promoción?') || event.stopImmediatePropagation()" wire:click="delete({{ $promo->id }})">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5">
                                        <p class="text-secondary text-sm mb-0">No se han creado promociones todavía.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer py-2">
                    {{ $promociones->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
