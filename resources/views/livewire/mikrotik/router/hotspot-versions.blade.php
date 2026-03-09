<div class="container py-4">
    <style>
        .modal-custom-margin {
            margin-top: 8rem !important;
        }
        /* Editor expandido para código HTML extenso */
        .code-editor {
            font-size: 13px; 
            min-height: 650px; /* Mayor altura inicial */
            font-family: 'Fira Code', 'Consolas', 'Monaco', monospace;
            line-height: 1.6;
            tab-size: 4;
            resize: vertical; /* Permite al usuario estirarlo más si lo desea */
        }
        /* Estilo para mejorar el contraste del editor oscuro */
        .bg-editor {
            background-color: #1e1e1e !important;
            color: #9cdcfe !important;
        }
    </style>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="col">
            <h4 class="fw-bold text-dark mb-1">Librería de Versiones Hotspot</h4>
            <p class="text-muted small mb-0">Gestión de plantillas de código para portales cautivos.</p>
        </div>
        <button wire:click="openModal" class="btn btn-primary rounded-pill px-4 shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Nueva Plantilla
        </button>
    </div>

    @if (session()->has('message'))
        <div class="alert alert-success border-0 shadow-sm rounded-3">
            <i class="bi bi-check-circle me-2"></i>{{ session('message') }}
        </div>
    @endif

    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light text-muted small fw-bold">
                    <tr>
                        <th class="ps-4 text-uppercase">Nombre</th>
                        <th class="text-uppercase">Descripción</th>
                        <th class="text-end pe-4 text-uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="border-top-0">
                    @forelse($versions as $v)
                    <tr class="align-middle">
                        <td class="ps-4">
                            <div class="fw-bold text-dark">{{ $v->name }}</div>
                            <div class="text-muted" style="font-size: 0.7rem;">ID #{{ $v->id }}</div>
                        </td>
                        <td class="text-muted small">
                            {{ \Illuminate\Support\Str::limit($v->description, 100) }}
                        </td>
                        <td class="text-end pe-4">
                            <div class="btn-group shadow-sm rounded">
                                <button wire:click="edit({{ $v->id }})" class="btn btn-sm btn-white border" title="Editar">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <button wire:click="delete({{ $v->id }})" 
                                        onclick="confirm('¿Eliminar esta plantilla?') || event.stopImmediatePropagation()" 
                                        class="btn btn-sm btn-white border text-danger" title="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-center py-5 text-muted">
                            No hay plantillas registradas en la librería.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($isOpen)
    <div class="modal fade show d-block" 
         wire:click.self="closeModal" 
         style="background: rgba(0,0,0,0.4); backdrop-filter: blur(2px); z-index: 1050;" 
         role="dialog">
        
        <div class="modal-dialog modal-xl modal-custom-margin">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 p-4 pb-0">
                    <h5 class="fw-bold text-dark mb-0">{{ $version_id ? 'Editar' : 'Nueva' }} Plantilla</h5>
                    <button wire:click="closeModal" class="btn-close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold small text-muted">NOMBRE DE LA VERSIÓN</label>
                            <input type="text" wire:model.defer="name" class="form-control bg-light border-0 p-3 rounded-3" placeholder="Ej: Portal Navidad 2026">
                            @error('name') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold small text-muted">DESCRIPCIÓN</label>
                            <textarea wire:model.defer="description" class="form-control bg-light border-0 p-3 rounded-3" rows="2" placeholder="Notas sobre el diseño o funcionalidades..."></textarea>
                            @error('description') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold small text-primary">CÓDIGO FUENTE (HTML COMPLETADO)</label>
                            <textarea wire:model.defer="code" 
                                      class="form-control bg-editor font-monospace border-0 p-4 code-editor" 
                                      rows="25"
                                      placeholder="<!DOCTYPE html>&#10;<html>&#10;..."></textarea>
                            @error('code') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button wire:click="closeModal" class="btn btn-light rounded-pill px-4">Cancelar</button>
                    <button wire:click="save" class="btn btn-primary rounded-pill px-5 shadow">
                        <i class="bi bi-save me-2"></i>Guardar Plantilla
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>