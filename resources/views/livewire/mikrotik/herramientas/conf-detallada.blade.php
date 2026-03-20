<div class="p-4">
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body bg-light">
            <div class="row align-items-end">
                <div class="col-md-4">
                    <label class="small fw-bold text-muted">SELECCIONAR ALIADO</label>
                    <select wire:model.live="aliado_id" class="form-select shadow-none">
                        <option value="">-- Seleccione un Aliado --</option>
                        @foreach($aliados as $aliado)
                            <option value="{{ $aliado->id }}">{{ $aliado->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="small fw-bold text-muted">SELECCIONAR ROUTER</label>
                    <select wire:model.live="router_id" class="form-select shadow-none" {{ empty($routers) ? 'disabled' : '' }}>
                        <option value="">-- Seleccione un Router --</option>
                        @foreach($routers as $router)
                            <option value="{{ $router->id }}">{{ $router->identity }} ({{ $router->ip }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 text-end">
                    <div wire:loading wire:target="router_id" class="text-primary small fw-bold">
                        <span class="spinner-border spinner-border-sm me-2"></span>Conectando con MikroTik...
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($router_id && !empty($interfaces))
        <h4 class="fw-bold text-dark mb-4">
            <i class="fas fa-microchip me-2 text-primary"></i>Panel de Control: {{ $identity }}
        </h4>

        <div class="row">
            @foreach($interfaces as $index => $iface)
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card shadow-sm h-100 border-0">
                        <div class="card-header bg-dark text-white py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold small text-uppercase"><i class="fas fa-ethernet me-2"></i>{{ $iface }}</span>
                                <span class="badge bg-secondary">192.168.{{ ($index + 2) * 10 }}.0/24</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <button 
                                wire:click="configurarPuerto('{{ $iface }}', {{ $index }})" 
                                wire:loading.attr="disabled"
                                class="btn w-100 fw-bold {{ $status[$iface] == 'success' ? 'btn-success' : 'btn-outline-primary' }}">
                                
                                <span wire:loading wire:target="configurarPuerto('{{ $iface }}', {{ $index }})">
                                    <span class="spinner-border spinner-border-sm"></span>
                                </span>

                                <span wire:loading.remove wire:target="configurarPuerto('{{ $iface }}', {{ $index }})">
                                    @if($status[$iface] == 'success')
                                        ACTUALIZAR PUERTO
                                    @else
                                        CONFIGURAR PUERTO
                                    @endif
                                </span>
                            </button>

                            <div class="mt-3">
                                <div class="p-2 border rounded bg-light font-monospace" style="font-size: 11px; min-height: 45px; border-left: 4px solid #0d6efd !important;">
                                    <strong>LOG:</strong> {{ $logs[$iface] }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row mt-2">
            <div class="col-12">
                <div class="card border-0 shadow-sm border-start border-4 border-primary">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fw-bold mb-0">Perfiles Globales (Hotspot & Walled Garden)</h6>
                            <p class="small text-muted mb-0">{{ $logs['profiles'] }}</p>
                        </div>
                        <button wire:click="configurarPerfiles" class="btn btn-primary px-4 fw-bold shadow-sm">
                            <span wire:loading wire:target="configurarPerfiles" class="spinner-border spinner-border-sm me-2"></span>
                            {{ $status['profiles'] == 'success' ? 'ACTUALIZAR PERFILES' : 'CONFIGURAR PERFILES' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @elseif($router_id)
        <div class="text-center py-5">
            <div class="spinner-border text-primary mb-3"></div>
            <p class="text-muted">Leyendo configuración del MikroTik...</p>
        </div>
    @endif
</div>