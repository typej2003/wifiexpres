<div class="p-4">
    <div class="card shadow-sm border-0 mb-4 bg-light">
        <div class="card-body">
            <div class="row">
                <div class="col-md-5">
                    <label class="small fw-bold text-muted">ALIADO</label>
                    <select wire:model="selectedAliado" class="form-select border-0 shadow-sm">
                        <option value="">-- Seleccionar --</option>
                        @foreach($aliados as $aliado)
                            <option value="{{ $aliado->id }}">{{ $aliado->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="small fw-bold text-muted">ROUTER</label>
                    <select wire:model="router_id" class="form-select border-0 shadow-sm" {{ !$selectedAliado ? 'disabled' : '' }}>
                        <option value="">-- Seleccionar --</option>
                        @foreach($routers as $router)
                            <option value="{{ $router->id }}">{{ $router->identity }} ({{ $router->macAddress }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    @if($isSearching)
        <div class="text-center py-5">
            <div class="spinner-grow text-primary mb-3"></div>
            <h5 class="text-muted fw-bold">Escaneando interfaces físicas...</h5>
            <p class="small text-secondary">{{ $logs['global'] ?? '' }}</p>
        </div>
    @endif

    @if(!$isSearching && count($interfaces) > 0)
        <div class="row">
            @foreach($interfaces as $index => $iface)
                @if($iface != 'ether1')
                <div class="col-md-4 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-dark text-white fw-bold small">
                            {{ strtoupper($iface) }}
                        </div>
                        <div class="card-body">
                            <button wire:click="configurarPuerto('{{ $iface }}', {{ $index }})" 
                                    wire:loading.attr="disabled"
                                    class="btn w-100 fw-bold {{ ($status[$iface] ?? 'idle') == 'success' ? 'btn-success' : 'btn-primary' }}">
                                <span wire:loading wire:target="configurarPuerto('{{ $iface }}', {{ $index }})" class="spinner-border spinner-border-sm me-2"></span>
                                {{ ($status[$iface] ?? 'idle') == 'success' ? 'CONFIGURADO' : 'LANZAR CONFIG' }}
                            </button>
                            <div class="mt-2 small text-muted font-monospace bg-light p-1">
                                > {{ $logs[$iface] ?? '' }}
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            @endforeach
        </div>
    @endif
</div>