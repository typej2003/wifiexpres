<div class="p-4">
    <div class="card shadow-sm border-0 mb-4 bg-light rounded-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <label class="small fw-bold text-muted text-uppercase mb-1">Aliado</label>
                    <select wire:model="selectedAliado" class="form-select border-0 shadow-sm rounded-3">
                        <option value="">-- Seleccionar Aliado --</option>
                        @foreach($aliados as $aliado)
                            <option value="{{ $aliado->id }}">{{ $aliado->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="small fw-bold text-muted text-uppercase mb-1">Router MikroTik</label>
                    <select wire:model="router_id" class="form-select border-0 shadow-sm rounded-3" {{ !$selectedAliado ? 'disabled' : '' }}>
                        <option value="">-- Seleccionar Router --</option>
                        @foreach($routers as $router)
                            <option value="{{ $router->id }}">{{ $router->identity }} ({{ $router->macAddress }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 text-end">
                    @if(count($interfaces) > 0 && !$isSearching)
                        <button wire:click="descubrirInterfaces" class="btn btn-outline-secondary btn-sm fw-bold rounded-3">
                            <i class="fas fa-sync-alt me-1"></i> Refrescar Interfaces
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($isSearching)
        <div class="text-center py-5">
            <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
            <h5 class="text-dark fw-bold">Interrogando al dispositivo...</h5>
            <p class="text-muted small">Esto puede tardar hasta 15 segundos. Por favor, espere.</p>
            <div class="badge bg-info-subtle text-info px-3 py-2 rounded-pill">{{ $logs['global'] ?? '' }}</div>
        </div>
    @endif

    @if($showRetry && !$isSearching)
        <div class="text-center py-5 border rounded-4 bg-white shadow-sm">
            <div class="mb-3"><i class="fas fa-exclamation-triangle text-warning fa-3x"></i></div>
            <h5 class="fw-bold text-dark">No se pudo obtener la información</h5>
            <p class="text-muted">{{ $logs['global'] ?? 'El router no respondió a la solicitud de descubrimiento.' }}</p>
            <button wire:click="descubrirInterfaces" class="btn btn-primary px-5 fw-bold rounded-pill">
                <i class="fas fa-redo me-2"></i> REINTENTAR ESCANEO
            </button>
        </div>
    @endif

    @if(!$isSearching && count($interfaces) > 0)
        <div class="mb-4">
            <h5 class="fw-bold text-dark"><i class="fas fa-th-large text-primary me-2"></i>Interfaces Disponibles</h5>
            <hr class="mt-2 mb-4 opacity-25">
        </div>

        <div class="row">
            @foreach($interfaces as $index => $iface)
                @if($iface != 'ether1') {{-- Omitimos ether1 por seguridad WAN --}}
                    <div class="col-md-4 mb-4">
                        <div class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden">
                            <div class="card-header bg-dark text-white py-3 border-0 d-flex justify-content-between align-items-center">
                                <span class="fw-bold small"><i class="fas fa-ethernet me-2"></i>{{ strtoupper($iface) }}</span>
                                <span class="badge bg-primary">Segmento .{{ ($index + 2) * 10 }}</span>
                            </div>
                            <div class="card-body">
                                <button 
                                    wire:click="configurarPuerto('{{ $iface }}', {{ $index }})" 
                                    wire:loading.attr="disabled"
                                    class="btn w-100 fw-bold py-2 rounded-3 {{ ($status[$iface] ?? 'idle') == 'success' ? 'btn-success' : 'btn-primary' }}">
                                    
                                    <span wire:loading wire:target="configurarPuerto('{{ $iface }}', {{ $index }})">
                                        <i class="fas fa-spinner fa-spin me-2"></i> PROCESANDO...
                                    </span>
                                    <span wire:loading.remove wire:target="configurarPuerto('{{ $iface }}', {{ $index }})">
                                        {{ ($status[$iface] ?? 'idle') == 'success' ? 'ACTUALIZAR' : 'CONFIGURAR LAN' }}
                                    </span>
                                </button>

                                <div class="mt-3 p-2 bg-light rounded-3 font-monospace" style="font-size: 11px;">
                                    <span class="text-muted">> Status:</span> 
                                    <span class="{{ ($status[$iface] ?? '') == 'success' ? 'text-success' : 'text-primary' }}">
                                        {{ $logs[$iface] ?? 'Pendiente de acción' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    @endif
</div>