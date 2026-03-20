<div class="p-4">
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body bg-light rounded shadow-sm">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="fw-bold small text-secondary">ALIADO</label>
                    <select wire:model.live="aliado_id" class="form-select border-0 shadow-sm">
                        <option value="">Seleccione un Aliado...</option>
                        @foreach($lista_aliados as $aliado)
                            <option value="{{ $aliado->id }}">{{ $aliado->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="fw-bold small text-secondary">ROUTER (MIKROTIK)</label>
                    <select wire:model.live="router_id" class="form-select border-0 shadow-sm" {{ !$aliado_id ? 'disabled' : '' }}>
                        <option value="">Seleccione un Router...</option>
                        @foreach($lista_routers as $router)
                            <option value="{{ $router->id }}">{{ $router->identity }} ({{ $router->ip }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3 d-flex align-items-end justify-content-center">
                    <div wire:loading wire:target="router_id" class="text-primary fw-bold small">
                        <div class="spinner-border spinner-border-sm me-2"></div> Sincronizando...
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($router_id && !empty($interfaces))
        <h5 class="fw-bold mb-4 text-dark"><i class="fas fa-server me-2"></i>Panel de Configuración: {{ $identity }}</h5>
        
        <div class="row">
            @foreach($interfaces as $index => $iface)
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-header bg-dark text-white fw-bold small py-3 d-flex justify-content-between">
                            <span>PORT: {{ strtoupper($iface) }}</span>
                            <span class="text-info">192.168.{{ ($index + 2) * 10 }}.1</span>
                        </div>
                        <div class="card-body">
                            <button wire:click="configurarPuerto('{{ $iface }}', {{ $index }})" 
                                    wire:loading.attr="disabled"
                                    class="btn w-100 fw-bold {{ $status[$iface] == 'success' ? 'btn-success' : 'btn-primary' }}">
                                <span wire:loading wire:target="configurarPuerto('{{ $iface }}', {{ $index }})" class="spinner-border spinner-border-sm"></span>
                                <span wire:loading.remove wire:target="configurarPuerto('{{ $iface }}', {{ $index }})">
                                    {{ $status[$iface] == 'success' ? 'ACTUALIZAR' : 'CONFIGURAR' }}
                                </span>
                            </button>
                            <div class="mt-3 p-2 bg-dark text-success rounded font-monospace" style="font-size: 10px; min-height: 40px;">
                                > {{ $logs[$iface] }}
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm border-start border-primary border-4">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fw-bold mb-1">Perfiles de Usuario & Walled Garden</h6>
                            <p class="small text-muted mb-0">{{ $logs['profiles'] }}</p>
                        </div>
                        <button wire:click="configurarPerfiles" class="btn btn-dark fw-bold px-4">
                            <span wire:loading wire:target="configurarPerfiles" class="spinner-border spinner-border-sm me-2"></span>
                            {{ $status['profiles'] == 'success' ? 'ACTUALIZAR GLOBAL' : 'CONFIGURAR GLOBAL' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>