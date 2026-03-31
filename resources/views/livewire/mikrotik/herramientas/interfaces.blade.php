<div class="container-fluid py-4" wire:poll.3s="checkStatus">
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Selección de Dispositivo</h6>
                    <button wire:click="refreshStatus" class="btn btn-sm btn-outline-light border-0">
                        <i class="fas fa-sync-alt" wire:loading.class="fa-spin" wire:target="refreshStatus"></i>
                    </button>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small uppercase">Aliado Comercial</label>
                        <select wire:model="selectedAliado" class="form-select form-select-sm shadow-none">
                            <option value="">-- Seleccione un Aliado --</option>
                            @foreach($aliados as $aliado)
                                <option value="{{ $aliado->id }}">{{ $aliado->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small uppercase">Router Activo</label>
                        <select wire:model="router_id" class="form-select form-select-sm shadow-none" wire:change="cargarInterfaces">
                            <option value="">-- Routers Online --</option>
                            @foreach($routers as $r)
                                <option value="{{ $r->id }}">
                                    🟢 {{ $r->identity }} ({{ $r->macAddress }})
                                </option>
                            @endforeach
                        </select>
                        @if($selectedAliado && $routers->isEmpty())
                            <div class="form-text text-danger small mt-1">
                                <i class="fas fa-exclamation-triangle"></i> No hay routers online para este aliado.
                            </div>
                        @endif
                    </div>

                    <div class="d-grid gap-2">
                        <button class="btn btn-primary btn-sm" wire:click="cargarInterfaces" 
                                wire:loading.attr="disabled" @if(!$router_id) disabled @endif>
                            <i class="fas fa-plug me-1"></i> Leer Interfaces
                        </button>
                    </div>

                    <hr class="my-4">

                    <label class="form-label fw-bold text-muted small">CONSOLA DE EVENTOS</label>
                    <div class="bg-dark text-success p-2 rounded shadow-inner" 
                         style="height: 180px; overflow-y: auto; font-family: 'Courier New', Courier, monospace; font-size: 0.75rem;">
                        @forelse(array_reverse($logs) as $log)
                            <div class="mb-1">
                                <span class="text-muted">[{{ now()->format('H:i') }}]</span> {{ $log }}
                            </div>
                        @empty
                            <div class="text-muted">Esperando acciones...</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 text-dark fw-bold">Interfaces de Red</h6>
                    <div wire:loading wire:target="cargarInterfaces, toggleInterface">
                        <span class="badge bg-warning text-dark"><i class="fas fa-spinner fa-spin me-1"></i> Procesando...</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr class="small text-uppercase">
                                    <th class="ps-3" style="width: 150px;">Estado</th>
                                    <th>Nombre</th>
                                    <th>Tipo</th>
                                    <th>MAC Address</th>
                                    <th class="text-center">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($interfaces as $int)
                                    @php 
                                        $isDisabled = ($int['disabled'] == 'true' || $int['disabled'] == 'yes');
                                    @endphp
                                    <tr class="{{ $isDisabled ? 'table-light opacity-75' : '' }}">
                                        <td class="ps-3">
                                            @if($isDisabled)
                                                <span class="badge rounded-pill bg-danger shadow-sm"><i class="fas fa-times-circle"></i> Disabled</span>
                                            @else
                                                <span class="badge rounded-pill bg-success shadow-sm"><i class="fas fa-check-circle"></i> Running</span>
                                            @endif
                                        </td>
                                        <td class="fw-bold text-dark">{{ $int['name'] }}</td>
                                        <td><span class="badge bg-secondary opacity-50">{{ $int['type'] ?? 'ether' }}</span></td>
                                        <td><code class="text-primary small">{{ $int['mac-address'] ?? '00:00:00:00:00:00' }}</code></td>
                                        <td class="text-center">
                                            <button 
                                                wire:click="toggleInterface('{{ $int['name'] }}', '{{ $isDisabled ? 'true' : 'false' }}')"
                                                wire:loading.attr="disabled"
                                                class="btn btn-sm {{ $isDisabled ? 'btn-success' : 'btn-danger' }} rounded-circle shadow-sm"
                                                title="{{ $isDisabled ? 'Habilitar' : 'Deshabilitar' }}"
                                                style="width: 32px; height: 32px; padding: 0;">
                                                <i class="fas {{ $isDisabled ? 'fa-play' : 'fa-power-off' }}" style="font-size: 0.7rem;"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5">
                                            <div class="py-4">
                                                <i class="fas fa-network-wired fa-3x text-light mb-3"></i>
                                                <p class="text-muted">No se han cargado datos. Seleccione un router activo para listar sus interfaces.</p>
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