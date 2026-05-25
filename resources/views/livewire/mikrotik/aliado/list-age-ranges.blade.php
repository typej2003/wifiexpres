<div class="container-fluid py-4">
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle me-2"></i> {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold mb-0">
                    <i class="bi bi-person-badge text-primary me-2"></i>Segmentación: Rangos de Edad
                </h4>
                <button wire:click="openModal" class="btn btn-primary rounded-pill px-4 shadow-sm" {{ $isAdmin && !$filterAliado ? 'disabled' : '' }}>
                    <i class="bi bi-plus-lg me-1"></i> Nuevo Rango
                </button>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" wire:model="search" class="form-control border-start-0" placeholder="Buscar rango...">
                    </div>
                </div>
                
                @if($isAdmin)
                <div class="col-md-3">
                    <select wire:model="filterAliado" class="form-select border-primary border-opacity-25">
                        <option value="">Seleccionar Aliado...</option>
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

    <div class="card border-0 shadow-sm rounded-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small fw-bold text-uppercase">
                    <tr>
                        <th class="px-4 py-3">Nombre del Rango</th>
                        <th class="py-3">Edad Mínima</th>
                        <th class="py-3">Edad Máxima</th>
                        @if($isAdmin) <th class="py-3">Aliado</th> @endif
                        <th class="text-end px-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ageRanges as $range)
                    <tr>
                        <td class="px-4 fw-bold text-dark">{{ $range->name }}</td>
                        <td><span class="badge bg-soft-primary text-primary rounded-pill px-3">{{ $range->min_age }} años</span></td>
                        <td><span class="badge bg-soft-primary text-primary rounded-pill px-3">{{ $range->max_age }} años</span></td>
                        @if($isAdmin) <td><small class="fw-semibold text-primary">{{ $range->user->name }}</small></td> @endif
                        <td class="text-end px-4">
                            <div class="btn-group shadow-sm rounded-3">
                                <button wire:click="edit({{ $range->id }})" class="btn btn-sm btn-white border">
                                    <i class="bi bi-pencil text-primary"></i>
                                </button>
                                <button onclick="confirm('¿Estás seguro de eliminar este rango?') || event.stopImmediatePropagation()" 
                                        wire:click="delete({{ $range->id }})" 
                                        class="btn btn-sm btn-white border">
                                    <i class="bi bi-trash text-danger"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="{{ $isAdmin ? 5 : 4 }}" class="text-center py-5 text-muted">No hay rangos definidos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white border-0 p-3">
            {{ $ageRanges->links() }}
        </div>
    </div>

    @if($isModalOpen)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.6); backdrop-filter: blur(5px);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 p-4 pb-0">
                    <h5 class="fw-bold mb-0 text-dark">{{ $selected_id ? 'Editar Rango' : 'Nuevo Rango de Edad' }}</h5>
                    <button type="button" class="btn-close" wire:click="closeModal"></button>
                </div>
                
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted">Nombre Descriptivo</label>
                            <input type="text" wire:model="name" class="form-control" placeholder="Ej: Jóvenes Adultos">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Edad Mínima</label>
                            <input type="number" wire:model="min_age" class="form-control" placeholder="0">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Edad Máxima</label>
                            <input type="number" wire:model="max_age" class="form-control" placeholder="99">
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 p-4 pt-0">
                    <button wire:click="closeModal" class="btn btn-light rounded-pill px-4">Cerrar</button>
                    <button wire:click="save" class="btn btn-primary rounded-pill px-5 shadow-sm fw-bold">
                        {{ $selected_id ? 'Guardar Cambios' : 'Crear Rango' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<style>
    .bg-soft-primary { background-color: rgba(13, 110, 253, 0.1); }
    .btn-white { background-color: #fff; color: #6c757d; }
    .btn-white:hover { background-color: #f8f9fa; }
</style>
