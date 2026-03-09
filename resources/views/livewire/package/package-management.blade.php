<div class="container-fluid py-4">
    {{-- HEADER --}}
    <div class="row mb-4 align-items-center">
        <div class="col-md-4">
            <h3 class="fw-bold text-dark mb-0">Planes Comerciales</h3>
            <p class="text-muted small">Paquetes y Portales Cautivos</p>
        </div>
        <div class="col-md-4 text-end ms-auto">
            <button wire:click="create" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
                <i class="bi bi-plus-lg me-1"></i> CREAR PLAN
            </button>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="alert alert-success rounded-4 border-0 shadow-sm mb-4 alert-dismissible fade show">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- TABLA --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4 py-3">PLAN / PORTAL (LOGIN.HTML)</th>
                        <th class="text-center py-3">TIPO / COMISIÓN</th>
                        <th class="text-center py-3">DURACIÓN</th>
                        <th class="text-center py-3">CUPO ROUTERS</th>
                        <th class="text-center py-3">VISIBILIDAD</th>
                        <th class="text-center py-3">PRECIO</th>
                        <th class="text-end pe-4 py-3">ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($packages as $p)
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold text-dark text-uppercase">{{ $p->name }}</div>
                            <small class="badge bg-info-soft text-info">
                                <i class="bi bi-code-slash me-1"></i> {{ $p->hotspotVersion->name ?? 'SIN PORTAL' }}
                            </small>
                        </td>
                        <td class="text-center">
                            <span class="badge {{ $p->service_type == 'cortesia' ? 'bg-success-subtle text-success border-success' : 'bg-primary-subtle text-primary border-primary' }} px-3 rounded-pill mb-1">
                                {{ $p->service_type == 'cortesia' ? 'Cortesía' : 'Reparto (%)' }}
                            </span>
                            @if($p->service_type == 'reparto')
                                <div class="small text-muted" style="font-size: 0.7rem;">
                                    A: <span class="fw-bold text-primary">{{ $p->commission_aliado }}%</span> | 
                                    S: <span class="fw-bold text-dark">{{ $p->commission_system }}%</span>
                                </div>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border px-2 py-1 rounded-pill small">
                                {{ $p->duration_months }} Meses
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-dark text-white rounded-pill px-3">
                                <i class="bi bi-router-fill me-1"></i> Máx: {{ $p->limit_routers }}
                            </span>
                        </td>
                        <td class="text-center">
                            <button wire:click="toggleVisibility({{ $p->id }})" class="btn btn-sm {{ $p->is_visible ? 'btn-light text-success' : 'btn-light text-muted' }} rounded-pill px-3 fw-bold border shadow-sm">
                                <i class="bi {{ $p->is_visible ? 'bi-eye-fill' : 'bi-eye-slash' }} me-1"></i>
                                {{ $p->is_visible ? 'Dashboard' : 'Privado' }}
                            </button>
                        </td>
                        <td class="text-center fw-bold text-dark">
                            @if($p->is_offer)
                                <small class="text-decoration-line-through text-danger me-1">${{ number_format($p->cost, 2) }}</small>
                                <span class="h6 mb-0 text-success">${{ number_format($p->offer_cost, 2) }}</span>
                            @else
                                <span class="h6 mb-0">${{ number_format($p->cost, 2) }}</span>
                            @endif
                        </td>
                        <td class="text-end pe-4">
                            <button wire:click="edit({{ $p->id }})" class="btn btn-sm btn-white border shadow-sm rounded-pill px-3">
                                <i class="bi bi-pencil"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center py-5 text-muted">No hay planes registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- MODAL --}}
    @if($isModalOpen)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); z-index: 2000;">
        <div class="modal-dialog modal-lg" style="margin-top: 5rem; margin-bottom: 5rem;">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-dark text-white p-4">
                    <h5 class="fw-bold mb-0">Configuración del Paquete</h5>
                    <button wire:click="closeModal" class="btn-close btn-close-white"></button>
                </div>
                <div class="modal-body p-4 bg-white">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="d-flex gap-4 p-3 bg-light rounded-4 border border-dashed">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" wire:model="is_active" id="is_active">
                                    <label class="form-check-label small fw-bold" for="is_active">PLAN ACTIVO</label>
                                </div>
                                <div class="form-check form-switch border-start ps-4">
                                    <input class="form-check-input" type="checkbox" wire:model="is_visible" id="is_visible">
                                    <label class="form-check-label small fw-bold text-primary" for="is_visible">HABILITAR EN DASHBOARD</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label small fw-bold text-muted text-uppercase">Nombre del Plan</label>
                            <input type="text" wire:model.defer="name" class="form-control shadow-sm border-light">
                            @error('name') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted text-uppercase">Tipo de Servicio</label>
                            <select wire:model="service_type" class="form-select shadow-sm border-light fw-bold text-primary">
                                <option value="cortesia">Cortesía (Aliado Paga)</option>
                                <option value="reparto">Reparto (Paga Cliente)</option>
                            </select>
                        </div>

                        <div class="col-md-12">
                            <div class="bg-dark bg-opacity-10 p-3 rounded-4 border border-dark border-opacity-10">
                                <label class="form-label small fw-bold text-dark text-uppercase mb-2"><i class="bi bi-router me-1"></i>Restricciones de Hardware</label>
                                <div class="row align-items-center">
                                    <div class="col-md-7">
                                        <p class="small text-muted mb-0">Indique la cantidad máxima de routers que el aliado podrá vincular a este plan.</p>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="input-group">
                                            <span class="input-group-text bg-white border-end-0 text-muted small">Routers Máx.</span>
                                            <input type="number" wire:model.defer="limit_routers" class="form-control text-center fw-bold border-start-0" min="1">
                                        </div>
                                        @error('limit_routers') <small class="text-danger small">{{ $message }}</small> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Portal Cautivo (Login HTML)</label>
                            <select wire:model.defer="hotspot_version_id" class="form-select shadow-sm border-light fw-bold">
                                <option value="">Seleccione el código fuente...</option>
                                @foreach($versions as $v)
                                    <option value="{{ $v->id }}">{{ $v->name }}</option>
                                @endforeach
                            </select>
                            @error('hotspot_version_id') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Duración (Meses)</label>
                            <input type="number" wire:model.defer="duration_months" class="form-control shadow-sm border-light">
                            @error('duration_months') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted text-uppercase">Costo {{ $service_type == 'cortesia' ? 'Membresía' : 'Precio Base' }}</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" wire:model.defer="cost" class="form-control shadow-sm border-light">
                            </div>
                            @error('cost') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>

                        @if($service_type == 'reparto')
                        <div class="col-12">
                            <div class="p-3 rounded-4 border bg-info bg-opacity-10 border-info">
                                <label class="form-label small fw-bold text-info text-uppercase mb-2">Distribución de Ganancias (%)</label>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="small fw-bold text-dark">Aliado (%)</label>
                                        <input type="number" wire:model="commission_aliado" class="form-control border-info text-center fw-bold">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="small fw-bold text-dark">Sistema (%)</label>
                                        <input type="number" wire:model="commission_system" class="form-control border-info text-center fw-bold">
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                        
                        <div class="col-12">
                            <div class="p-3 rounded-4 border {{ $is_offer ? 'border-danger bg-danger bg-opacity-10' : 'bg-light border-light' }}">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" wire:model="is_offer" id="is_offer">
                                    <label class="form-check-label fw-bold" for="is_offer">¿USAR PRECIO DE OFERTA?</label>
                                </div>
                                @if($is_offer)
                                    <div class="input-group">
                                        <span class="input-group-text border-danger bg-danger text-white">$</span>
                                        <input type="number" step="0.01" wire:model.defer="offer_cost" class="form-control border-danger" placeholder="Precio final">
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted text-uppercase">Descripción (Opcional)</label>
                            <textarea wire:model.defer="description" class="form-control border-light" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light p-4">
                    <button wire:click="closeModal" class="btn btn-secondary rounded-pill px-4">Cancelar</button>
                    <button wire:click.prevent="store" class="btn btn-primary rounded-pill px-5 fw-bold shadow">GUARDAR PLAN</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<style>
    .bg-info-soft { background-color: rgba(13, 202, 240, 0.1); }
    .bg-success-subtle { background-color: rgba(25, 135, 84, 0.1); }
    .bg-primary-subtle { background-color: rgba(13, 110, 253, 0.1); }
    .btn-white { background-color: #fff; color: #444; }
</style>