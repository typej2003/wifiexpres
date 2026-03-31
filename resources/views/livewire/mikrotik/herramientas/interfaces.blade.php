<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-dark text-white">
                    <h6 class="mb-0"><i class="fas fa-microchip me-2"></i>Equipos</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Aliado</label>
                        <select wire:model="selectedAliado" wire:change="refreshStatus" class="form-select shadow-none">
                            <option value="">-- Todos --</option>
                            @foreach($aliados as $aliado)
                                <option value="{{ $aliado->id }}">{{ $aliado->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Router</label>
                        <select wire:model="router_id" class="form-select shadow-none">
                            <option value="">-- Seleccione --</option>
                            @foreach($routers as $r)
                                <option value="{{ $r->id }}">🟢 {{ $r->identity }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="btn btn-primary w-100 shadow-sm" wire:click="cargarInterfaces" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="cargarInterfaces"><i class="fas fa-sync-alt me-1"></i> Leer Interfaces</span>
                        <span wire:loading wire:target="cargarInterfaces"><i class="fas fa-spinner fa-spin me-1"></i> Consultando...</span>
                    </button>

                    <div class="mt-4 bg-dark text-success p-2 rounded small" style="height: 150px; overflow-y: auto; font-family: monospace;">
                        @foreach(array_reverse($logs) as $log)
                            <div class="border-bottom border-secondary mb-1">> {{ $log }}</div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold">Interfaces Detectadas</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small text-uppercase">
                                <tr>
                                    <th class="ps-3">Estado</th>
                                    <th>Nombre</th>
                                    <th>Tipo</th>
                                    <th>MAC</th>
                                    <th class="text-center">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($interfaces as $int)
                                    <tr>
                                        <td class="ps-3">
                                            @if($int['disabled'] == 'true')
                                                <span class="badge bg-danger">Disabled</span>
                                            @else
                                                <span class="badge bg-success">Active</span>
                                            @endif
                                        </td>
                                        <td class="fw-bold">{{ $int['name'] }}</td>
                                        <td><small class="text-muted">{{ $int['type'] }}</small></td>
                                        <td><code>{{ $int['mac-address'] }}</code></td>
                                        <td class="text-center">
                                            <button wire:click="toggleInterface('{{ $int['name'] }}', '{{ $int['disabled'] }}')" 
                                                    class="btn btn-sm {{ $int['disabled'] == 'true' ? 'btn-success' : 'btn-danger' }} rounded-circle shadow-sm"
                                                    style="width: 35px; height: 35px;">
                                                <i class="fas {{ $int['disabled'] == 'true' ? 'fa-play' : 'fa-power-off' }}"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            No hay datos. Presione "Leer Interfaces".
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