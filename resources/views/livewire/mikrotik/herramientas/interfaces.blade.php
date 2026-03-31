<div class="container-fluid py-4" @if($esperandoRespuesta) wire:poll.2s="checkStatus" @endif>
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-dark text-white fw-bold">
                    <i class="fas fa-terminal me-2"></i>Panel de Control
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="small fw-bold text-muted">Aliado Comercial</label>
                        <select wire:model="selectedAliado" wire:change="refreshStatus" class="form-select shadow-none border-secondary-subtle">
                            <option value="">-- Todos los Aliados --</option>
                            @foreach($aliados as $aliado)
                                <option value="{{ $aliado->id }}">{{ $aliado->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold text-muted">Router (Online)</label>
                        <select wire:model="router_id" class="form-select shadow-none border-secondary-subtle">
                            <option value="">-- Seleccione Equipo --</option>
                            @foreach($routers as $r)
                                <option value="{{ $r->id }}">🟢 {{ $r->identity }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button class="btn btn-primary w-100 shadow-sm fw-bold mb-4" wire:click="cargarInterfaces" wire:loading.attr="disabled">
                        <i class="fas fa-sync-alt me-1" wire:loading.class="fa-spin" wire:target="cargarInterfaces"></i> 
                        LEER INTERFACES
                    </button>

                    <label class="small fw-bold text-muted text-uppercase mb-2">Historial de Proceso</label>
                    <div class="bg-dark text-success p-3 rounded shadow-inner" 
                         style="height: 250px; overflow-y: auto; font-family: 'Consolas', 'Monaco', monospace; font-size: 0.75rem; border-left: 4px solid #0d6efd;">
                        
                        @forelse(array_reverse($logs) as $log)
                            <div class="border-bottom border-secondary py-1 text-break">
                                <span class="text-muted small">[{{ now()->format('H:i:s') }}]</span> 
                                <span class="ms-1">> {{ $log }}</span>
                            </div>
                        @empty
                            <div class="text-muted small italic opacity-50">Esperando ejecución...</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-network-wired text-primary me-2"></i>Interfaces Detectadas</h6>
                    @if($loading) 
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    @endif
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small text-uppercase text-muted">
                                <tr>
                                    <th class="ps-4">Estado</th>
                                    <th>Nombre</th>
                                    <th>Tipo</th>
                                    <th>MAC</th>
                                    <th class="text-center">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($interfaces as $int)
                                    @php $isDisabled = ($int['disabled'] == 'true'); @endphp
                                    <tr class="{{ $isDisabled ? 'bg-light' : '' }}">
                                        <td class="ps-4">
                                            @if($isDisabled)
                                                <span class="badge bg-danger-subtle text-danger px-3 rounded-pill border border-danger-subtle">OFF</span>
                                            @else
                                                <span class="badge bg-success-subtle text-success px-3 rounded-pill border border-success-subtle">ON</span>
                                            @endif
                                        </td>
                                        <td class="fw-bold">{{ $int['name'] }}</td>
                                        <td><span class="badge bg-secondary opacity-50">{{ $int['type'] }}</span></td>
                                        <td><code>{{ $int['mac-address'] }}</code></td>
                                        <td class="text-center">
                                            <button wire:click="toggleInterface('{{ $int['name'] }}', '{{ $int['disabled'] }}')" 
                                                    wire:loading.attr="disabled"
                                                    class="btn btn-sm {{ $isDisabled ? 'btn-success' : 'btn-danger' }} rounded-circle shadow-sm"
                                                    style="width: 34px; height: 34px; padding: 0;">
                                                <i class="fas {{ $isDisabled ? 'fa-play' : 'fa-power-off' }}" style="font-size: 0.8rem;"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <div class="py-3">
                                                <i class="fas fa-search fa-3x mb-3 opacity-25"></i>
                                                <p class="mb-0">Seleccione un router para listar sus interfaces de red.</p>
                                            </div>
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