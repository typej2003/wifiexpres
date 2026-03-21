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
                    @if($router_id)
                        <button wire:click="iniciarDescubrimiento" 
                                wire:loading.attr="disabled"
                                class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm fw-bold">
                            <span wire:loading wire:target="iniciarDescubrimiento" class="spinner-border spinner-border-sm me-1"></span>
                            REFRESCAR HARDWARE
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($isWaitingResponse && count($interfaces) == 0)
        <div class="text-center py-5">
            <div class="spinner-border text-primary"></div>
            <p class="mt-2 text-muted small fw-bold">COMUNICANDO CON EL EQUIPO...</p>
        </div>
    @endif

    @if(count($interfaces) > 0)
        <div class="row">
            @foreach($interfaces as $index => $iface)
                @if($iface != 'ether1')
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden bg-white">
                        <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-uppercase">{{ $iface }}</span>
                            <button wire:click="scanearInterfaz('{{ $iface }}', {{ $index }})" 
                                    wire:loading.attr="disabled"
                                    class="btn btn-info btn-xs rounded-pill px-2 fw-bold text-white shadow-sm" style="font-size: 10px;"
                                    {{ $isProcessing ? 'disabled' : '' }}>
                                <span wire:loading wire:target="scanearInterfaz('{{ $iface }}', {{ $index }})" class="spinner-border spinner-border-sm"></span>
                                <span wire:loading.remove wire:target="scanearInterfaz('{{ $iface }}', {{ $index }})">SCAN AUTO</span>
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                @php 
                                    $tareas = ['bridge' => 'BRIDGE', 'address' => 'ADDRESS', 'pool' => 'POOL', 'dhcp' => 'DHCP', 'hotspot' => 'HOTSPOT'];
                                @endphp
                                @foreach($tareas as $key => $label)
                                    <li class="list-group-item py-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="flex-grow-1">
                                                <span class="small fw-bold text-muted text-uppercase d-block">{{ $label }}</span>
                                                @if(isset($taskResult[$iface][$key]))
                                                    <div class="mt-1 {{ ($taskStatus[$iface][$key] ?? '') == 'success' ? 'text-success' : 'text-danger' }}" style="font-size: 11px;">
                                                        {{ $taskResult[$iface][$key] }}
                                                    </div>
                                                @endif
                                            </div>
                                            <button wire:click="ejecutarTarea('{{ $iface }}', {{ $index }}, '{{ $key }}')"
                                                wire:loading.attr="disabled"
                                                {{ $isProcessing ? 'disabled' : '' }}
                                                class="btn btn-sm rounded-pill px-3 shadow-sm 
                                                {{ ($taskStatus[$iface][$key] ?? '') == 'success' ? 'btn-success' : (($taskStatus[$iface][$key] ?? '') == 'error' ? 'btn-danger' : 'btn-primary') }}">
                                                @if(($taskStatus[$iface][$key] ?? '') == 'loading')
                                                    <span class="spinner-border spinner-border-sm"></span>
                                                @else
                                                    Instalar
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
            <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">CONFIGURACIÓN GLOBAL</h6>
                <button wire:click="scanGlobal" 
                        wire:loading.attr="disabled"
                        class="btn btn-warning btn-sm rounded-pill fw-bold text-dark" 
                        {{ $isProcessing || !$version_id ? 'disabled' : '' }}>
                    <span wire:loading wire:target="scanGlobal" class="spinner-border spinner-border-sm me-1"></span>
                    PROCESAR TODO EL PORTAL
                </button>
            </div>
            <div class="card-body p-0">
                <div class="p-3 bg-light border-bottom">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <label class="small fw-bold text-muted text-uppercase">Versión del Portal</label>
                            <select wire:model="version_id" class="form-select border-0 shadow-sm rounded-3">
                                <option value="">-- Seleccionar --</option>
                                @foreach($hotspot_versions as $version)
                                    <option value="{{ $version->id }}">{{ $version->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-8 text-muted small">
                            Seleccione la versión del portal para habilitar la descarga y configuración del Walled Garden.
                        </div>
                    </div>
                </div>

                <ul class="list-group list-group-flush">
                    @php 
                        $globals = [
                            'walledgarden' => 'Walled Garden (Hosts)', 
                            'walledgardenip' => 'Walled Garden (IPs)', 
                            'portal' => 'Portal Cautivo (Download)', 
                            'reboot' => 'Reiniciar Equipo (Reboot)'
                        ];
                    @endphp
                    @foreach($globals as $key => $label)
                        <li class="list-group-item py-3">
                            <div class="d-flex justify-content-between align-items-center px-3">
                                <div class="flex-grow-1">
                                    <span class="small fw-bold text-muted text-uppercase d-block">{{ $label }}</span>
                                    @if(isset($taskResult['global'][$key]))
                                        <div class="mt-1 {{ ($taskStatus['global'][$key] ?? '') == 'success' ? 'text-success' : 'text-danger' }}" style="font-size: 11px;">
                                            {{ $taskResult['global'][$key] }}
                                        </div>
                                    @endif
                                </div>
                                <button wire:click="ejecutarTarea('global', 0, '{{ $key }}')"
                                    wire:loading.attr="disabled"
                                    {{ $isProcessing || (!$version_id && $key != 'reboot') ? 'disabled' : '' }}
                                    class="btn btn-sm rounded-pill px-4 shadow-sm 
                                    {{ ($taskStatus['global'][$key] ?? '') == 'success' ? 'btn-success' : (($taskStatus['global'][$key] ?? '') == 'error' ? 'btn-danger' : 'btn-primary') }}">
                                    @if(($taskStatus['global'][$key] ?? '') == 'loading')
                                        <span class="spinner-border spinner-border-sm"></span>
                                    @else
                                        Instalar
                                    @endif
                                </button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif
</div>