<div class="p-4">
    <div class="card shadow-sm border-0 mb-4 bg-light rounded-4">
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-6">
                    <label class="small fw-bold text-muted text-uppercase mb-1">Aliado</label>
                    <select wire:model="selectedAliado" class="form-select border-0 shadow-sm rounded-3">
                        <option value="">-- Seleccionar Aliado --</option>
                        @foreach($aliados as $aliado)
                            <option value="{{ $aliado->id }}">{{ $aliado->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="small fw-bold text-muted text-uppercase mb-1">Router</label>
                    <select wire:model="router_id" class="form-select border-0 shadow-sm rounded-3" {{ !$selectedAliado ? 'disabled' : '' }}>
                        <option value="">-- Seleccionar Router --</option>
                        @foreach($routers as $router)
                            <option value="{{ $router->id }}">{{ $router->identity }} ({{ $router->macAddress }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    @if($isWaitingResponse)
        <div wire:poll.1s="checkDiscoveryStatus" class="card border-0 shadow-sm rounded-4 py-5 mb-4">
            <div class="card-body text-center">
                <div class="mb-4">
                    <div class="spinner-border text-primary" role="status" style="width: 4rem; height: 4rem;"></div>
                </div>
                <h4 class="fw-bold">Esperando al MikroTik...</h4>
                <p class="text-muted small">Intento {{ $intentos }} de 20</p>
                <div class="progress mt-3 mx-auto" style="height: 10px; max-width: 400px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" 
                         role="progressbar" style="width: {{ ($intentos / 20) * 100 }}%"></div>
                </div>
                <p class="mt-3 text-secondary italic font-monospace small">> {{ $logs['global'] ?? '' }}</p>
            </div>
        </div>
    @endif

    @if($showRetry && !$isWaitingResponse)
        <div class="alert alert-warning rounded-4 border-0 shadow-sm p-4 text-center">
            <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
            <h5 class="fw-bold">Sin respuesta del dispositivo</h5>
            <p class="small mb-3">{{ $logs['global'] }}</p>
            <button wire:click="iniciarDescubrimiento" class="btn btn-warning fw-bold px-4 rounded-pill">
                REINTENTAR AHORA
            </button>
        </div>
    @endif

    @if(count($interfaces) > 0 && !$isWaitingResponse)
        <div class="row">
            @foreach($interfaces as $index => $iface)
                @if($iface != 'ether1')
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                        <div class="card-header bg-dark text-white py-3 border-0">
                            <span class="fw-bold"><i class="fas fa-ethernet me-2 text-info"></i>{{ strtoupper($iface) }}</span>
                        </div>
                        <div class="card-body">
                            <button class="btn btn-outline-primary w-100 fw-bold">CONFIGURAR PUERTO</button>
                        </div>
                    </div>
                </div>
                @endif
            @endforeach
        </div>
    @endif
</div>