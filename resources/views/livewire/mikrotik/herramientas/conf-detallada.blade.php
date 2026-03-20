<div class="p-4" @if($isWaitingResponse || $activeTask) wire:poll.1s="checkStatus" @endif>
    
    <div class="card shadow-sm border-0 mb-4 bg-light rounded-4">
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-4">
                    <label class="small fw-bold text-muted">Aliado</label>
                    <select wire:model="selectedAliado" class="form-select border-0 shadow-sm rounded-3">
                        <option value="">-- Seleccionar --</option>
                        @foreach($aliados as $aliado)
                            <option value="{{ $aliado->id }}">{{ $aliado->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="small fw-bold text-muted">MikroTik</label>
                    <select wire:model="router_id" class="form-select border-0 shadow-sm rounded-3" {{ !$selectedAliado ? 'disabled' : '' }}>
                        <option value="">-- Seleccionar --</option>
                        @foreach($routers as $router)
                            <option value="{{ $router->id }}">{{ $router->identity }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 text-end">
                    <button wire:click="iniciarDescubrimiento" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold shadow-sm">REFRESCAR</button>
                </div>
            </div>
        </div>
    </div>

    @if($isWaitingResponse)
        <div class="text-center py-5"><div class="spinner-border text-primary"></div></div>
    @endif

    @if(count($interfaces) > 0 && !$isWaitingResponse)
        <div class="row">
            @foreach($interfaces as $index => $iface)
                @if($iface != 'ether1')
                <div class="col-md-4 mb-4">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                        <div class="card-header bg-dark text-white py-2 d-flex justify-content-between align-items-center">
                            <span class="small fw-bold">{{ $iface }}</span>
                            <button wire:click="scanearInterfaz('{{ $iface }}', {{ $index }})" class="btn btn-info btn-xs text-white p-1 px-2" style="font-size: 9px;">AUTO</button>
                        </div>
                        <div class="card-body p-0">
                            @php $tareas = ['bridge' => 'Bridge', 'address' => 'IP', 'pool' => 'Pool', 'dhcp' => 'DHCP', 'hotspot' => 'Hotspot']; @endphp
                            @foreach($tareas as $key => $label)
                                <div class="p-2 border-bottom d-flex justify-content-between align-items-center">
                                    <span class="small text-muted fw-bold">{{ $label }}</span>
                                    <button wire:click="ejecutarTarea('{{ $iface }}', {{ $index }}, '{{ $key }}')" 
                                        class="btn btn-xs rounded-pill {{ ($taskStatus[$iface][$key] ?? '') == 'success' ? 'btn-success' : 'btn-outline-primary' }}" style="font-size: 10px;">
                                        {{ ($taskStatus[$iface][$key] ?? '') == 'loading' ? '...' : 'OK' }}
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif
            @endforeach
        </div>

        <div class="card border-0 shadow-sm rounded-4 bg-white mb-5 overflow-hidden">
            <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-uppercase">Finalizar Configuración</h6>
                <button wire:click="scanGlobal" class="btn btn-warning btn-sm rounded-pill fw-bold" {{ $activeTask || !$version_id ? 'disabled' : '' }}>
                    EJECUTAR TODO EL CIERRE
                </button>
            </div>
            <div class="card-body bg-light">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <label class="small fw-bold text-muted">Seleccionar Portal</label>
                        <select wire:model="version_id" class="form-select border-0 shadow-sm rounded-3">
                            <option value="">-- Seleccionar --</option>
                            @foreach($hotspot_versions as $version)
                                <option value="{{ $version->id }}">{{ $version->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-8">
                        <div class="row g-2 text-center">
                            @php 
                                $globals = [
                                    'wg_clean' => 'Limpieza WG', 
                                    'wg_hosts_1' => 'Hosts P1', 
                                    'wg_hosts_2' => 'Hosts P2',
                                    'walledgardenip' => 'WG IPs', 
                                    'portal' => 'Portal', 
                                    'reboot' => 'Reiniciar'
                                ];
                            @endphp
                            @foreach($globals as $key => $label)
                                <div class="col-4 col-md-2">
                                    <div class="bg-white p-2 rounded-3 border">
                                        <div class="small fw-bold" style="font-size: 9px;">{{ $label }}</div>
                                        <div class="mt-1">
                                            @if(($taskStatus['global'][$key] ?? '') == 'success')
                                                <i class="fas fa-check-circle text-success"></i>
                                            @elseif(($taskStatus['global'][$key] ?? '') == 'loading')
                                                <span class="spinner-border spinner-border-sm text-primary"></span>
                                            @else
                                                <i class="fas fa-circle text-light"></i>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>