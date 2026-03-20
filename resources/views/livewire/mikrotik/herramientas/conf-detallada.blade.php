<div class="p-4">
    <div class="card shadow-sm border-0 mb-4 bg-light rounded-4">
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-4">
                    <label class="small fw-bold text-muted text-uppercase mb-1">Aliado</label>
                    <select wire:model="selectedAliado" class="form-select border-0 shadow-sm">
                        <option value="">-- Seleccionar Aliado --</option>
                        @foreach($aliados as $aliado)
                            <option value="{{ $aliado->id }}">{{ $aliado->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="small fw-bold text-muted text-uppercase mb-1">Router</label>
                    <select wire:model="router_id" class="form-select border-0 shadow-sm" {{ !$selectedAliado ? 'disabled' : '' }}>
                        <option value="">-- Seleccionar Router --</option>
                        @foreach($routers as $router)
                            <option value="{{ $router->id }}">{{ $router->identity }} ({{ $router->macAddress }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 text-end">
                    @if(count($interfaces) > 0 && !$isWaitingResponse)
                        <button wire:click="descubrirInterfaces" class="btn btn-sm btn-outline-primary fw-bold">
                            <i class="fas fa-sync-alt"></i> Redescubrir
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($isSearching || $isWaitingResponse)
        <div class="card border-0 shadow-sm rounded-4 py-5">
            <div class="card-body text-center">
                <div class="mb-4">
                    <div class="spinner-border text-primary" role="status" style="width: 4rem; height: 4rem; border-width: 0.25em;">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
                <h4 class="fw-bold text-dark">Estableciendo comunicación</h4>
                <p class="text-muted mx-auto" style="max-width: 400px;">
                    {{ $logs['global'] ?? 'Por favor espera un momento mientras interrogamos al dispositivo.' }}
                </p>
                <div class="progress mt-4 mx-auto" style="height: 6px; max-width: 300px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
                </div>
            </div>
        </div>
    @endif

    @if($showRetry && !$isWaitingResponse)
        <div class="card border-0 shadow-sm rounded-4 py-5 bg-white">
            <div class="card-body text-center">
                <div class="text-warning mb-3"><i class="fas fa-exclamation-circle fa-4xl"></i></div>
                <h5 class="fw-bold">Sin respuesta del Router</h5>
                <p class="text-muted mb-4">{{ $logs['global'] }}</p>
                <button wire:click="descubrirInterfaces" class="btn btn-primary btn-lg px-5 rounded-pill fw-bold">
                    <i class="fas fa-redo-alt me-2"></i> INTENTAR DE NUEVO
                </button>
            </div>
        </div>
    @endif

    @if(!$isSearching && !$isWaitingResponse && count($interfaces) > 0)
        <div class="row">
            @foreach($interfaces as $index => $iface)
                @if($iface != 'ether1')
                <div class="col-md-4 mb-4">
                    <div class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden">
                        <div class="card-header bg-dark text-white py-3 border-0">
                            <span class="fw-bold"><i class="fas fa-ethernet me-2 text-info"></i>{{ strtoupper($iface) }}</span>
                        </div>
                        <div class="card-body">
                            <button wire:click="configurarPuerto('{{ $iface }}', {{ $index }})" 
                                    class="btn btn-primary w-100 fw-bold py-2 rounded-3 shadow-sm">
                                CONFIGURAR LAN
                            </button>
                        </div>
                    </div>
                </div>
                @endif
            @endforeach
        </div>
    @endif
</div>