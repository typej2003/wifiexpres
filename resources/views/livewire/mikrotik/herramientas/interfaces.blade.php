<div class="container-fluid py-4" wire:poll.3s="checkStatus">
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-server me-2"></i>Equipos Online</h6>
                    <span wire:loading wire:target="refreshStatus" class="spinner-border spinner-border-sm"></span>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Aliado</label>
                        <select wire:model="selectedAliado" class="form-select shadow-none border-secondary-subtle">
                            <option value="">-- Todos los Aliados --</option>
                            @foreach($aliados as $aliado)
                                <option value="{{ $aliado->id }}">{{ $aliado->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Router (Sólo Activos)</label>
                        <select wire:model="router_id" class="form-select shadow-none border-secondary-subtle">
                            <option value="">-- Seleccione Router --</option>
                            @foreach($routers as $r)
                                <option value="{{ $r->id }}">🟢 {{ $r->identity }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="d-grid">
                        <button class="btn btn-primary shadow-sm fw-bold" 
                                wire:click="cargarInterfaces" 
                                wire:loading.attr="disabled"
                                @if(!$router_id) disabled @endif>
                            <i class="fas fa-sync-alt me-2" wire:loading.class="fa-spin" wire:target="cargarInterfaces"></i>
                            LEER INTERFACES
                        </button>
                    </div>

                    <hr class="my-4 text-muted">

                    <label class="form-label small fw-bold text-muted text-uppercase">Log de Sistema</label>
                    <div class="bg-dark text-success p-3 rounded" style="height: 200px; overflow-y: auto; font-family: 'Consolas', monospace; font-size: 0.8rem; border-left: 4px solid #0d6efd;">
                        @forelse(array_reverse($logs) as $log)
                            <div class="mb-1 border-bottom border-secondary pb-1">> {{ $log }}</div>
                        @empty
                            <div class="text-muted">Esperando comando manual...</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-network-wired me-2 text-primary"></i>Configuración de Interfaces</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr class="small text-muted text-uppercase">
                                    <th class="ps-4">Estado</th>
                                    <th>Interface</th>
                                    <th>Tipo</th>
                                    <th>MAC Address</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($interfaces as $int)
                                    @php $disabled = ($int['disabled'] == 'true' || $int['disabled'] == 'yes'); @endphp
                                    <tr class="{{ $disabled ? 'bg-light' : '' }}">
                                        <td class="ps-4">
                                            @if($disabled)
                                                <span class="badge bg-danger-subtle text-danger px-3">DISABLED</span>
                                            @else
                                                <span class="badge bg-success-subtle text-success px-3">RUNNING</span>
                                            @endif
                                        </td>
                                        <td class="fw-bold">{{ $int['name'] }}</td>
                                        <td><small class="text-muted">{{ $int['type'] ?? 'ether' }}</small></td>
                                        <td><code>{{ $int['mac-address'] ?? 'N/A' }}</code></td>
                                        <td class="text-center">
                                            @if($disabled)
                                                <button wire:click="toggleInterface('{{ $int['name'] }}', 'true')" class="btn btn-sm btn-success shadow-sm" title="Habilitar">
                                                    <i class="fas fa-play"></i>
                                                </button>
                                            @else
                                                <button wire:click="toggleInterface('{{ $int['name'] }}', 'false')" class="btn btn-sm btn-danger shadow-sm" title="Deshabilitar">
                                                    <i class="fas fa-power-off"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="fas fa-mouse-pointer fa-3x mb-3 opacity-25"></i>
                                            <p>Seleccione un router y presione "Leer Interfaces" para comenzar.</p>
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