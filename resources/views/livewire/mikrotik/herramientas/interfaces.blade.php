<div class="container-fluid py-4" wire:poll.2s="checkStatus">
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-network-wired me-2"></i>Control de Interfaces</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Filtrar por Aliado</label>
                        <select wire:model="selectedAliado" class="form-select shadow-none" wire:change="refreshStatus">
                            <option value="">-- Todos los Aliados --</option>
                            @foreach($aliados as $aliado)
                                <option value="{{ $aliado->id }}">{{ $aliado->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Seleccionar Router</label>
                        <select wire:model="router_id" class="form-select shadow-none" wire:change="cargarInterfaces">
                            <option value="">-- Elija un Router --</option>
                            @foreach($routers as $r)
                                <option value="{{ $r->id }}">
                                    {{ $this->routerStatus[$r->id] ?? false ? '🟢' : '🔴' }} {{ $r->identity }} ({{ $r->macAddress }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button class="btn btn-primary w-100 shadow-sm" wire:click="cargarInterfaces" wire:loading.attr="disabled">
                        <i class="fas fa-sync-alt me-1" wire:loading.class="fa-spin"></i> Cargar Interfaces
                    </button>

                    <hr>

                    <div class="bg-light p-2 rounded border" style="height: 150px; overflow-y: auto; font-family: monospace; font-size: 0.85rem;">
                        @foreach(array_reverse($logs) as $log)
                            <div class="border-bottom py-1">{{ $log }}</div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Listado de Interfaces</h5>
                    <span class="badge bg-light text-dark">{{ count($interfaces) }} Detectadas</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Estado</th>
                                    <th>Nombre</th>
                                    <th>Tipo</th>
                                    <th>MAC</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($interfaces as $index => $int)
                                    <tr>
                                        <td>
                                            @if($int['disabled'] == 'true' || $int['disabled'] == 'yes')
                                                <span class="badge bg-danger">Deshabilitada (X)</span>
                                            @else
                                                <span class="badge bg-success">Activa (R)</span>
                                            @endif
                                        </td>
                                        <td class="fw-bold">{{ $int['name'] }}</td>
                                        <td><small class="text-muted">{{ $int['type'] ?? 'N/A' }}</small></td>
                                        <td><code class="small">{{ $int['mac-address'] ?? 'N/A' }}</code></td>
                                        <td class="text-center">
                                            @if($int['disabled'] == 'true' || $int['disabled'] == 'yes')
                                                <button class="btn btn-sm btn-outline-success px-3" 
                                                    wire:click="toggleInterface('{{ $int['name'] }}', 'true')"
                                                    wire:loading.attr="disabled">
                                                    <i class="fas fa-play me-1"></i> Habilitar
                                                </button>
                                            @else
                                                <button class="btn btn-sm btn-outline-danger px-3" 
                                                    wire:click="toggleInterface('{{ $int['name'] }}', 'false')"
                                                    wire:loading.attr="disabled">
                                                    <i class="fas fa-stop me-1"></i> Deshabilitar
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="fas fa-info-circle me-1"></i> Selecciona un router y presiona "Cargar Interfaces"
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

    <div wire:loading wire:target="toggleInterface, cargarInterfaces">
        <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.2); z-index: 9999; display: flex; align-items: center; justify-content: center;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Procesando...</span>
            </div>
        </div>
    </div>
</div>