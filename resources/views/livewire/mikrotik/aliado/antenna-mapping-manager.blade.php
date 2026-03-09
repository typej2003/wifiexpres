<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark m-0">
                <i class="bi bi-broadcast text-primary"></i> Configuración de Antenas e IPs
            </h4>
            <p class="text-muted small mb-0">Asocie segmentos de red con nombres de ubicaciones físicas.</p>
        </div>
        <div>
            <a href="{{ route('aliado.monitor') }}" class="btn btn-dark shadow-sm">
                <i class="bi bi-arrow-left-circle"></i> Volver al Monitor
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-header {{ $isEditing ? 'bg-info' : 'bg-primary' }} text-white fw-bold">
                    <i class="bi {{ $isEditing ? 'bi-pencil-square' : 'bi-plus-circle' }}"></i>
                    {{ $isEditing ? 'Editar Antena' : 'Registrar Nueva Antena' }}
                </div>
                <div class="card-body">
                    @if (session()->has('message'))
                        <div class="alert alert-success border-0 shadow-sm small">
                            <i class="bi bi-check-circle-fill"></i> {{ session('message') }}
                        </div>
                    @endif

                    @if(Auth::user()->role === 'admin')
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase">Aliado</label>
                        <select wire:model="aliadoId" class="form-select border-primary">
                            <option value="">Seleccione Aliado</option>
                            @foreach($aliados as $aliado)
                                <option value="{{ $aliado->id }}">{{ $aliado->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase">Router MikroTik</label>
                        <select wire:model="router_id" class="form-select border-primary">
                            <option value="">Seleccione Router</option>
                            @foreach($routers as $router)
                                <option value="{{ $router->id }}">{{ $router->comercio_nombre ?? $router->identity }}</option>
                            @endforeach
                        </select>
                        @error('router_id') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase">Dirección IP de la Antena</label>
                        <input type="text" wire:model="ip_address" class="form-control border-primary" placeholder="Ej: 10.0.5.1">
                        <div class="form-text small">La IP que el MikroTik asigna al usuario en esta zona.</div>
                        @error('ip_address') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase">Nombre Ubicación</label>
                        <input type="text" wire:model="location_name" class="form-control border-primary" placeholder="Ej: Pasillo Norte / PB">
                        @error('location_name') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase">Notas Adicionales</label>
                        <textarea wire:model="description" class="form-control border-primary" rows="2" placeholder="Opcional..."></textarea>
                    </div>

                    <div class="d-grid gap-2">
                        <button wire:click="save" class="btn btn-primary shadow-sm py-2 fw-bold">
                            <i class="bi bi-save"></i> {{ $isEditing ? 'Actualizar Cambios' : 'Guardar Antena' }}
                        </button>
                        @if($isEditing)
                            <button wire:click="resetInput" class="btn btn-light border py-2">Cancelar Edición</button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-bold py-3">
                    <i class="bi bi-list-ul text-primary"></i> Antenas Mapeadas para el Aliado Seleccionado
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Router / Identidad</th>
                                    <th>Dirección IP</th>
                                    <th>Ubicación Asignada</th>
                                    <th class="text-end pe-3">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($mappings as $map)
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-bold">{{ $map->router->comercio_nombre ?? 'N/A' }}</div>
                                            <div class="small text-muted font-monospace">{{ $map->router->identity }}</div>
                                        </td>
                                        <td><code class="text-primary fw-bold fs-6">{{ $map->ip_address }}</code></td>
                                        <td>
                                            <span class="badge bg-light text-dark border border-secondary px-3 py-2">
                                                <i class="bi bi-geo-alt-fill text-danger"></i> {{ $map->location_name }}
                                            </span>
                                        </td>
                                        <td class="text-end pe-3">
                                            <div class="btn-group shadow-sm">
                                                <button wire:click="edit({{ $map->id }})" class="btn btn-sm btn-outline-info" title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button wire:click="delete({{ $map->id }})" 
                                                        onclick="confirm('¿Está seguro de eliminar este mapeo?') || event.stopImmediatePropagation()" 
                                                        class="btn btn-sm btn-outline-danger" title="Eliminar">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-5">
                                            <i class="bi bi-info-circle fs-2 text-muted"></i>
                                            <p class="text-muted mt-2">No hay antenas registradas para los routers de este aliado.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>