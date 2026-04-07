<div class="container-fluid py-4">
    {{-- MENSAJES --}}
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold mb-0"><i class="bi bi-megaphone text-primary me-2"></i>Campañas de Encuestas</h4>
                <button wire:click="openModal" class="btn btn-primary rounded-pill px-4 shadow-sm">
                    <i class="bi bi-plus-lg me-1"></i> Nueva Campaña
                </button>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <input type="text" wire:model="search" class="form-control" placeholder="Buscar campaña...">
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

    {{-- TABLA --}}
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
                            @if($isAdmin) <small class="text-primary">{{ $camp->user->name }}</small> @endif
                        </td>
                        <td>
                            <span class="badge bg-soft-info text-info rounded-pill px-3">
                                {{ strtoupper($camp->target_gender) }} | {{ $camp->age_min }}-{{ $camp->age_max }} años
                            </span>
                        </td>
                        <td class="text-center">
                            @if($camp->media_type == 'imagen')
                                <i class="bi bi-image text-primary fs-5"></i>
                            @else
                                <i class="bi bi-play-circle-fill text-danger fs-5"></i>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="form-check form-switch d-inline-block">
                                <input class="form-check-input" type="checkbox" {{ $camp->active ? 'checked' : '' }}>
                            </div>
                        </td>
                        <td class="text-end px-4">
                            <button class="btn btn-sm btn-light border shadow-sm"><i class="bi bi-pencil"></i></button>
                            <button class="btn btn-sm btn-light border shadow-sm text-danger"><i class="bi bi-trash"></i></button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center py-5 text-muted">No se encontraron campañas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white border-0 p-3">
            {{ $campaigns->links() }}
        </div>
    </div>

    {{-- MODAL --}}
    @if($isModalOpen)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-0 p-4">
                    <h5 class="fw-bold mb-0">{{ $selected_id ? 'Editar Campaña' : 'Crear Nueva Campaña' }}</h5>
                    <button type="button" class="btn-close" wire:click="closeModal"></button>
                </div>
                <div class="modal-body p-4 pt-0">
                    <div class="row g-3">
                        @if($isAdmin)
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Aliado Responsable</label>
                            <select wire:model="user_id" class="form-select">
                                <option value="">Seleccione un Aliado</option>
                                @foreach($aliados as $aliado)
                                    <option value="{{ $aliado->id }}">{{ $aliado->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        <div class="col-md-8">
                            <label class="form-label small fw-bold">Nombre de la Campaña</label>
                            <input type="text" wire:model="name" class="form-control" placeholder="Ej: Promo Verano">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Género Objetivo</label>
                            <select wire:model="target_gender" class="form-select">
                                <option value="todos">Todos</option>
                                <option value="masculino">Masculino</option>
                                <option value="femenino">Femenino</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Edad Mínima</label>
                            <input type="number" wire:model="age_min" class="form-control">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Edad Máxima</label>
                            <input type="number" wire:model="age_max" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Tipo de Multimedia</label>
                            <select wire:model="media_type" class="form-select">
                                <option value="imagen">Imagen (JPG, PNG)</option>
                                <option value="video">Video (MP4)</option>
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Archivo Multimedia</label>
                            <input type="file" wire:model="media" class="form-control">
                            <div wire:loading wire:target="media" class="text-primary small mt-1">Cargando archivo...</div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Pregunta de la Encuesta</label>
                            <input type="text" wire:model="question" class="form-control" placeholder="¿Le interesa nuestra nueva oferta?">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4">
                    <button wire:click="closeModal" class="btn btn-light rounded-pill px-4">Cancelar</button>
                    <button wire:click="save" class="btn btn-primary rounded-pill px-4 shadow-sm">
                        {{ $selected_id ? 'Actualizar' : 'Guardar Campaña' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<style>
    .bg-soft-info { background-color: rgba(13, 202, 240, 0.1); }
</style>