<div class="p-4">
    <div class="card shadow-sm border-0 mb-4 bg-light">
        <div class="card-body">
            <div class="row">
                <div class="col-md-5">
                    <label class="small fw-bold text-muted text-uppercase">Aliado</label>
                    <select wire:model="selectedAliado" class="form-select border-0 shadow-sm">
                        <option value="">-- Seleccionar Aliado --</option>
                        @foreach($aliados as $aliado)
                            <option value="{{ $aliado->id }}">{{ $aliado->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="small fw-bold text-muted text-uppercase">Router</label>
                    <select wire:model="router_id" class="form-select border-0 shadow-sm" {{ !$selectedAliado ? 'disabled' : '' }}>
                        <option value="">-- Seleccionar Router --</option>
                        @foreach($routers as $router)
                            <option value="{{ $router->id }}">{{ $router->identity }} ({{ $router->macAddress }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    @if($router_id && count($interfaces) > 0)
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold text-dark m-0"><i class="fas fa-microchip text-primary me-2"></i>{{ $identity }}</h4>
            <span class="badge bg-soft-primary text-primary px-3 py-2">Modo Configuración Individual</span>
        </div>

        <div class="row">
            @foreach($interfaces as $index => $iface)
                <div class="col-md-4 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 border-0">
                            <h6 class="fw-bold text-primary m-0">
                                <i class="fas fa-ethernet me-2"></i>PUERTO: {{ strtoupper($iface) }}
                            </h6>
                        </div>
                        <div class="card-body pt-0">
                            <p class="text-muted small mb-3">Red sugerida: 192.168.{{ ($index + 2) * 10 }}.1/24</p>
                            
                            <button 
                                wire:click="configurarPuerto('{{ $iface }}', {{ $index }})" 
                                wire:loading.attr="disabled"
                                class="btn w-100 fw-bold py-2 shadow-sm {{ ($status[$iface] ?? 'idle') == 'success' ? 'btn-success' : 'btn-primary' }}">
                                
                                <span wire:loading wire:target="configurarPuerto('{{ $iface }}', {{ $index }})">
                                    <i class="fas fa-spinner fa-spin me-2"></i> CARGANDO...
                                </span>
                                <span wire:loading.remove wire:target="configurarPuerto('{{ $iface }}', {{ $index }})">
                                    @if(($status[$iface] ?? 'idle') == 'success')
                                        <i class="fas fa-sync-alt me-2"></i> ACTUALIZAR
                                    @else
                                        <i class="fas fa-paper-plane me-2"></i> ENVIAR
                                    @endif
                                </span>
                            </button>

                            <div class="mt-3 p-2 bg-dark rounded overflow-hidden">
                                <div class="font-monospace text-success" style="font-size: 11px; min-height: 40px;">
                                    <span class="text-muted"># log_info:</span><br>
                                    {{ $logs[$iface] ?? 'Esperando...' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card border-0 shadow-sm border-start border-4 border-warning">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="fw-bold mb-1">Perfiles de Hotspot y Walled Garden</h6>
                    <span class="small text-muted font-monospace">{{ $logs['profiles'] }}</span>
                </div>
                <button wire:click="configurarGlobal" wire:loading.attr="disabled" class="btn btn-warning fw-bold px-4">
                    <span wire:loading wire:target="configurarGlobal" class="spinner-border spinner-border-sm me-2"></span>
                    {{ $status['profiles'] == 'success' ? 'ACTUALIZAR GLOBAL' : 'ENVIAR GLOBAL' }}
                </button>
            </div>
        </div>
    @endif
</div>