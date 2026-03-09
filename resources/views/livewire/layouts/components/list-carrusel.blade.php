<div>
    <style>
        .custom-modal-margin { margin-top: 130px !important; }
        .modal { z-index: 2000 !important; }
        .modal-backdrop { z-index: 1999 !important; }
        .modal-content {
            box-shadow: 0 1rem 3rem rgba(101, 0, 218, 0.15) !important;
            border-radius: 15px !important;
            border: none;
        }
        .text-purple { color: #6500da !important; }
        .btn-primary { background-color: #6500da !important; border-color: #6500da !important; }
        .badge-device { font-size: 0.75rem; }
    </style>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="h4 mb-0 text-purple fw-bold">Administración de Carrusel</h2>
                    <button wire:click.prevent="addNew" class="btn btn-primary shadow-sm rounded-pill px-4">
                        <i class="bi bi-plus-lg me-2"></i> Nuevo Banner
                    </button>
                </div>

                <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 15px;">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">#</th>
                                        <th>Previsualización</th>
                                        <th>Título / Dispositivo</th>
                                        <th>Orden</th>
                                        <th>Estado</th>
                                        <th class="text-end pe-4">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($imagenes as $index => $item)
                                    <tr>
                                        <td class="ps-4 text-muted small">{{ $imagenes->firstItem() + $index }}</td>
                                        <td>
                                            <img src="{{ $item->avatar_url }}" 
                                                class="rounded shadow-sm" 
                                                style="width: 100px; height: 50px; object-fit: cover;">
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark">{{ $item->title }}</div>
                                            
                                            @if($item->device == 'd')
                                                <span class="badge bg-primary badge-device">
                                                    <i class="bi bi-display me-1"></i> Escritorio
                                                </span>
                                            @elseif($item->device == 't')
                                                <span class="badge bg-warning text-dark badge-device">
                                                    <i class="bi bi-tablet me-1"></i> Tablet
                                                </span>
                                            @elseif($item->device == 'm')
                                                <span class="badge bg-info text-dark badge-device">
                                                    <i class="bi bi-smartphone me-1"></i> Móvil
                                                </span>
                                            @endif
                                        </td>
                                        <td><span class="badge bg-light text-purple border">{{ $item->order }}</span></td>
                                        <td>
                                            <span class="badge rounded-pill {{ $item->active == 'active' ? 'bg-success' : 'bg-danger' }}">
                                                {{ $item->active == 'active' ? 'Visible' : 'Oculto' }}
                                            </span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <button wire:click.prevent="edit({{ $item->id }})" class="btn btn-sm btn-outline-primary border-0 rounded-circle">
                                                <i class="bi bi-pencil-fill"></i>
                                            </button>
                                            <button wire:click.prevent="confirmCarruselRemoval({{ $item->id }})" class="btn btn-sm btn-outline-danger border-0 rounded-circle">
                                                <i class="bi bi-trash3-fill"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="6" class="text-center py-5 text-muted">No hay registros en el carrusel.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="formCarrusel" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog custom-modal-margin">
            <form class="modal-content" wire:submit.prevent="{{ $showEditModal ? 'updateCarrusel' : 'createCarrusel' }}">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-purple">{{ $showEditModal ? 'Editar Banner' : 'Crear Banner' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Título</label>
                        <input type="text" wire:model.defer="state.title" class="form-control rounded-3 @error('title') is-invalid @enderror">
                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold small">Dispositivo</label>
                            <select wire:model.defer="state.device" class="form-select rounded-3">
                                <option value="d">Escritorio</option>
                                <option value="m">Móvil</option>
                                <option value="t">Tablet</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold small">Orden</label>
                            <input type="number" wire:model.defer="state.order" class="form-control rounded-3">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold small">Visibilidad</label>
                            <select wire:model.defer="state.active" class="form-select rounded-3">
                                <option value="active">Activo</option>
                                <option value="notactive">Inactivo</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3" wire:key="container-photo-{{ $iteration }}">
                        <label class="form-label fw-bold small">Imagen (Máx. 2MB)</label>
                        <input type="file" wire:model="photo" id="upload-{{ $iteration }}" class="form-control rounded-3 @error('photo') is-invalid @enderror">
                        @error('photo') <div class="text-danger small mt-1"><b>{{ $message }}</b></div> @enderror

                        <div class="mt-3 text-center" wire:key="preview-{{ $iteration }}">
                            @if ($photo && !$errors->has('photo'))
                                <p class="small text-muted mb-1">Nueva imagen para subir:</p>
                                <img src="{{ $photo->temporaryUrl() }}" class="img-fluid rounded shadow-sm border border-purple-subtle" style="max-height: 150px;">
                            @elseif($showEditModal && isset($carrusel) && $carrusel->avatar)
                                <p class="small text-muted mb-1">Imagen actual en el servidor:</p>
                                <img src="{{ $carrusel->avatar_url }}" class="img-fluid rounded shadow" style="max-height: 150px;">
                            @endif
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalDelete" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog custom-modal-margin modal-sm text-center">
            <div class="modal-content border-0 shadow">
                <div class="modal-body py-4">
                    <div class="text-danger mb-3"><i class="bi bi-trash3 fs-1"></i></div>
                    <h6 class="fw-bold">¿Deseas eliminarlo?</h6>
                </div>
                <div class="modal-footer border-0 justify-content-center pt-0 pb-4">
                    <button type="button" class="btn btn-light rounded-pill px-3 me-2" data-bs-dismiss="modal">No</button>
                    <button type="button" wire:click.prevent="deleteCarrusel" class="btn btn-danger rounded-pill px-4">Sí, eliminar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modalForm = new bootstrap.Modal(document.getElementById('formCarrusel'));
        const modalDelete = new bootstrap.Modal(document.getElementById('modalDelete'));
        const Toast = Swal.mixin({
            toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true
        });

        window.addEventListener('abrir-modal', () => modalForm.show());
        window.addEventListener('abrir-modal-eliminar', () => modalDelete.show());
        window.addEventListener('cerrar-modal', event => {
            modalForm.hide();
            Toast.fire({ icon: event.detail.type, title: event.detail.message });
        });
        window.addEventListener('cerrar-modal-eliminar', event => {
            modalDelete.hide();
            Toast.fire({ icon: event.detail.type, title: event.detail.message });
        });
    });
</script>