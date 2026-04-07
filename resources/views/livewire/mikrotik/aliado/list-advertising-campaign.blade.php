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
                    <i class="bi bi-megaphone text-primary me-2"></i>Campañas de Encuestas
                </h4>
                <button wire:click="openModal" class="btn btn-primary rounded-pill px-4 shadow-sm">
                    <i class="bi bi-plus-lg me-1"></i> Nueva Campaña
                </button>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" wire:model="search" class="form-control border-start-0" placeholder="Buscar campaña...">
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
                        <th class="px-4 py-3">Campaña</th>
                        <th class="py-3">Segmentación</th>
                        <th class="py-3 text-center">Contenido</th>
                        <th class="py-3 text-center">Estado</th>
                        <th class="text-end px-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($campaigns as $camp)
                    <tr>
                        <td class="px-4">
                            <span class="fw-bold d-block text-dark">{{ $camp->name }}</span>
                            @if($isAdmin) 
                                <small class="text-primary fw-semibold">
                                    <i class="bi bi-person me-1"></i>{{ $camp->user->name }}
                                </small> 
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-soft-info text-info rounded-pill px-3">
                                {{ strtoupper($camp->target_gender) }} | {{ $camp->age_min }}-{{ $camp->age_max }} años
                            </span>
                        </td>
                        <td class="text-center">
                            @if($camp->media_type == 'imagen')
                                <i class="bi bi-image text-primary fs-5" title="Imagen"></i>
                            @else
                                <i class="bi bi-play-circle-fill text-danger fs-5" title="Video"></i>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="form-check form-switch d-inline-block">
                                <input class="form-check-input" type="checkbox" role="switch" {{ $camp->active ? 'checked' : '' }}>
                            </div>
                        </td>
                        <td class="text-end px-4">
                            <div class="btn-group shadow-sm rounded-3">
                                <button wire:click="edit({{ $camp->id }})" class="btn btn-sm btn-white border" title="Editar">
                                    <i class="bi bi-pencil text-primary"></i>
                                </button>
                                <button onclick="confirm('¿Estás seguro de eliminar esta campaña? Esta acción no se puede deshacer.') || event.stopImmediatePropagation()" 
                                        wire:click="delete({{ $camp->id }})" 
                                        class="btn btn-sm btn-white border" title="Eliminar">
                                    <i class="bi bi-trash text-danger"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            No se encontraron campañas configuradas.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white border-0 p-3">
            {{ $campaigns->links() }}
        </div>
    </div>

    {{-- MODAL DINÁMICO --}}
    @if($isModalOpen)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.6); backdrop-filter: blur(5px);">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 p-4">
                    <h5 class="fw-bold mb-0 text-dark">
                        {{ $selected_id ? 'Actualizar Campaña' : 'Nueva Campaña Publicitaria' }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="closeModal"></button>
                </div>
                
                <div class="modal-body p-4 pt-0">
                    <div class="row g-3">
                        @if($isAdmin)
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted">Aliado Propietario</label>
                            <select wire:model="user_id" class="form-select @error('user_id') is-invalid @enderror">
                                <option value="">-- Seleccionar Aliado --</option>
                                @foreach($aliados as $aliado)
                                    <option value="{{ $aliado->id }}">{{ $aliado->name }}</option>
                                @endforeach
                            </select>
                            @error('user_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        @endif

                        <div class="col-md-8">
                            <label class="form-label small fw-bold text-muted">Nombre del Proyecto</label>
                            <input type="text" wire:model="name" class="form-control @error('name') is-invalid @enderror" placeholder="Ej: Campaña Fibra Óptica">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Segmento Género</label>
                            <select wire:model="target_gender" class="form-select">
                                <option value="todos">Todos los géneros</option>
                                <option value="masculino">Masculino</option>
                                <option value="femenino">Femenino</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">Edad Min.</label>
                            <input type="number" wire:model="age_min" class="form-control">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">Edad Max.</label>
                            <input type="number" wire:model="age_max" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Formato Multimedia</label>
                            <select wire:model="media_type" class="form-select">
                                <option value="imagen">Imagen Estática (Banner)</option>
                                <option value="video">Video (Spot publicitario)</option>
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted">Cargar Archivo</label>
                            <input type="file" wire:model="media" class="form-control @error('media') is-invalid @enderror">
                            @if($selected_id && $current_media_path)
                                <div class="mt-2 small text-muted">
                                    <i class="bi bi-file-earmark-check me-1"></i> Archivo actual: <strong>{{ basename($current_media_path) }}</strong>
                                </div>
                            @endif
                            <div wire:loading wire:target="media" class="mt-2">
                                <div class="spinner-border spinner-border-sm text-primary me-1"></div>
                                <span class="text-primary small">Subiendo archivo al servidor...</span>
                            </div>
                            @error('media') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted">Pregunta Principal (Call to Action)</label>
                            <input type="text" wire:model="question" class="form-control @error('question') is-invalid @enderror" placeholder="¿Te gustaría recibir más información?">
                            @error('question') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 p-4 pt-0">
                    <button wire:click="closeModal" class="btn btn-light rounded-pill px-4 fw-bold text-muted">Cerrar</button>
                    <button wire:click="save" class="btn btn-primary rounded-pill px-5 shadow-sm fw-bold">
                        {{ $selected_id ? 'Guardar Cambios' : 'Lanzar Campaña' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<style>
    /* Estilos personalizados para el branding de PanExpres */
    .bg-soft-info { background-color: rgba(13, 202, 240, 0.12); }
    .btn-white { background-color: #fff; color: #6c757d; }
    .btn-white:hover { background-color: #f8f9fa; color: #212529; }
    .modal-header .btn-close { filter: grayscale(1) opacity(0.5); }
    .form-switch .form-check-input:checked { background-color: #198754; border-color: #198754; }
</style>