<div class="p-4" @if($isWaitingResponse || $activeTask) wire:poll.1s="checkStatus" @endif>
    
    <div class="card shadow-sm border-0 mb-4 bg-light rounded-4">
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-4">
                    <label class="small fw-bold text-muted text-uppercase">Aliado</label>
                    <select wire:model="selectedAliado" class="form-select border-0 shadow-sm rounded-3">
                        <option value="">-- Seleccionar --</option>
                        @foreach($aliados as $aliado)
                            <option value="{{ $aliado->id }}">{{ $aliado->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="small fw-bold text-muted text-uppercase">Router MikroTik</label>
                    <select wire:model="router_id" class="form-select border-0 shadow-sm rounded-3" {{ !$selectedAliado ? 'disabled' : '' }}>
                        <option value="">-- Seleccionar --</option>
                        @foreach($routers as $router)
                            <option value="{{ $router->id }}">{{ $router->identity }} ({{ $router->macAddress }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 text-end">
                    @if(count($interfaces) > 0)
                        <button wire:click="iniciarDescubrimiento" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm fw-bold">
                            <i class="fas fa-sync-alt me-2"></i> REFRESCAR HARDWARE
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($isWaitingResponse)
        <div class="text-center py-5">
            <div class="spinner-border text-primary mb-3"></div>
            <h5 class="fw-bold text-muted text-uppercase">Interrogando Hardware...</h5>
        </div>
    @endif

    @if(count($interfaces) > 0 && !$isWaitingResponse)
        <div class="row">
            @foreach($interfaces as $index => $iface)
                @if($iface != 'ether1')
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden bg-white">
                        <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-uppercase"><i class="fas fa-wifi me-2 text-info"></i>{{ $iface }}</span>
                            <button wire:click="scanearInterfaz('{{ $iface }}', {{ $index }})" 
                                    class="btn btn-info btn-xs rounded-pill px-2 fw-bold text-white shadow-sm" style="font-size: 10px;"
                                    {{ $activeTask ? 'disabled' : '' }}>
                                <i class="fas fa-play me-1"></i> SCAN AUTO
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                @php 
                                    $tareas = [
                                        'bridge' => 'Bridge', 
                                        'address' => 'IP Address', 
                                        'pool' => 'Pool IPs', 
                                        'dhcp' => 'DHCP Server', 
                                        'hotspot' => 'Hotspot Server'
                                    ];
                                @endphp
                                @foreach($tareas as $key => $label)
                                    <li class="list-group-item py-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="flex-grow-1">
                                                <span class="small fw-bold text-muted text-uppercase d-block">{{ $label }}</span>
                                                @if(isset($taskResult[$iface][$key]))
                                                    <div class="mt-1 {{ ($taskStatus[$iface][$key] ?? '') == 'success' ? 'text-success' : 'text-danger' }}" style="font-size: 11px; font-weight: 500;">
                                                        <i class="fas fa-chevron-right me-1"></i> {{ $taskResult[$iface][$key] }}
                                                    </div>
                                                @endif
                                            </div>
                                            
                                            <button wire:click="ejecutarTarea('{{ $iface }}', {{ $index }}, '{{ $key }}')"
                                                class="btn btn-sm rounded-pill px-3 shadow-sm 
                                                {{ ($taskStatus[$iface][$key] ?? '') == 'success' ? 'btn-success' : (($taskStatus[$iface][$key] ?? '') == 'error' ? 'btn-danger' : 'btn-primary') }}"
                                                wire:loading.attr="disabled">
                                                @if(($taskStatus[$iface][$key] ?? '') == 'loading')
                                                    <span class="spinner-border spinner-border-sm"></span>
                                                @else
                                                    Config
                                                @endif
                                            </button>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
                @endif
            @endforeach
        </div>

        <div class="card border-0 shadow-sm rounded-4 bg-white mb-5 overflow-hidden">
            <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center border-0">
                <h6 class="mb-0 fw-bold text-uppercase"><i class="fas fa-rocket me-2"></i> Configuración Global</h6>
                <button wire:click="scanGlobal" class="btn btn-warning btn-sm rounded-pill fw-bold shadow-sm px-4" 
                    {{ $activeTask || !$version_id ? 'disabled' : '' }}>
                    <i class="fas fa-bolt me-1"></i> FINALIZAR TODO
                </button>
            </div>
            <div class="card-body bg-light">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <label class="small fw-bold text-muted mb-2">VERSIÓN DE PORTAL</label>
                        <select wire:model="version_id" class="form-select border-0 shadow-sm rounded-3 py-2">
                            <option value="">-- Seleccionar Portal --</option>
                            @foreach($hotspot_versions as $version)
                                <option value="{{ $version->id }}">{{ $version->name }} ({{ $version->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-8">
                        <div class="row g-2">
                            @php 
                                $globals = [
                                    'walledgarden' => ['l' => 'Walled Garden', 'i' => 'fa-shield-alt'],
                                    'portal'       => ['l' => 'Bajar Portal', 'i' => 'fa-download'],
                                    'reboot'       => ['l' => 'Reiniciar', 'i' => 'fa-power-off']
                                ];
                            @endphp
                            @foreach($globals as $key => $info)
                                <div class="col-4">
                                    <div class="bg-white p-2 rounded-3 border shadow-sm">
                                        <button wire:click="ejecutarTarea('global', 0, '{{ $key }}')"
                                            class="btn btn-sm w-100 rounded-3 py-1 fw-bold mb-1 
                                            {{ ($taskStatus['global'][$key] ?? '') == 'success' ? 'btn-success' : (($taskStatus['global'][$key] ?? '') == 'error' ? 'btn-danger' : 'btn-outline-primary') }}">
                                            <i class="fas {{ $info['i'] }} me-1"></i> {{ $info['l'] }}
                                        </button>
                                        @if(isset($taskResult['global'][$key]))
                                            <div style="font-size: 10px;" class="text-center {{ ($taskStatus['global'][$key] ?? '') == 'success' ? 'text-success' : 'text-danger' }}">
                                                {{ $taskResult['global'][$key] }}
                                            </div>
                                        @endif
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