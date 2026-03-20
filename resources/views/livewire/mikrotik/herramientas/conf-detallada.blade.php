<div class="p-4" @if($isWaitingResponse || $activeTask) wire:poll.1s="checkStatus" @endif>
    
    <div class="card shadow-sm border-0 mb-4 bg-light rounded-4">
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-5">
                    <label class="small fw-bold text-muted">ALIADO</label>
                    <select wire:model="selectedAliado" class="form-select border-0 shadow-sm rounded-3">
                        <option value="">-- Seleccionar --</option>
                        @foreach($aliados as $aliado)
                            <option value="{{ $aliado->id }}">{{ $aliado->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="small fw-bold text-muted">ROUTER</label>
                    <select wire:model="router_id" class="form-select border-0 shadow-sm rounded-3" {{ !$selectedAliado ? 'disabled' : '' }}>
                        <option value="">-- Seleccionar --</option>
                        @foreach($routers as $router)
                            <option value="{{ $router->id }}">{{ $router->identity }} ({{ $router->macAddress }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 text-end">
                    @if(count($interfaces) > 0)
                        <button wire:click="iniciarDescubrimiento" class="btn btn-outline-primary btn-sm rounded-pill fw-bold shadow-sm">
                            <i class="fas fa-sync-alt"></i> REFRESCAR
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($isWaitingResponse)
        <div class="text-center py-5">
            <div class="spinner-grow text-primary mb-3"></div>
            <h5 class="fw-bold">Interrogando Hardware...</h5>
        </div>
    @endif

    @if(count($interfaces) > 0 && !$isWaitingResponse)
        <div class="row">
            @foreach($interfaces as $index => $iface)
                @if($iface != 'ether1')
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                        <div class="card-header bg-dark text-white py-3 border-0 d-flex justify-content-between align-items-center">
                            <span class="fw-bold"><i class="fas fa-ethernet me-2 text-info"></i>{{ strtoupper($iface) }}</span>
                            <button wire:click="scanearInterfaz('{{ $iface }}', {{ $index }})" 
                                    class="btn btn-info btn-xs rounded-pill px-2 fw-bold text-white shadow-sm" 
                                    style="font-size: 0.6rem;" 
                                    {{ $activeTask ? 'disabled' : '' }}>
                                <i class="fas fa-search"></i> SCAN AUTO
                            </button>
                        </div>
                        
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                @php 
                                    $tareasInterface = [
                                        'bridge'  => 'Configurar Bridge',
                                        'address' => 'IP Address (.'.(($index+1)*10).'.1)',
                                        'pool'    => 'Crear Pool de IPs',
                                        'dhcp'    => 'Servidor DHCP',
                                        'hotspot' => 'Servidor Hotspot'
                                    ];
                                @endphp

                                @foreach($tareasInterface as $key => $label)
                                    <li class="list-group-item py-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="small fw-bold text-uppercase" style="font-size: 0.75rem;">{{ $label }}</span>
                                            <button wire:click="ejecutarTarea('{{ $iface }}', {{ $index }}, '{{ $key }}')"
                                                class="btn btn-sm px-3 rounded-pill fw-bold shadow-sm {{ ($taskStatus[$iface][$key] ?? '') == 'success' ? 'btn-success' : (($taskStatus[$iface][$key] ?? '') == 'error' ? 'btn-danger' : 'btn-primary') }}">
                                                {{ ($taskStatus[$iface][$key] ?? '') == 'loading' ? '...' : 'Config' }}
                                            </button>
                                        </div>
                                        @if(isset($taskResult[$iface][$key]))
                                            <small class="font-monospace {{ ($taskStatus[$iface][$key] ?? '') == 'success' ? 'text-success' : 'text-danger' }}" style="font-size: 0.65rem;">
                                                <i class="fas fa-terminal me-1"></i> {{ $taskResult[$iface][$key] }}
                                            </small>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
                @endif
            @endforeach
        </div>

        <div class="card shadow-sm border-0 rounded-4 bg-white mb-5">
            <div class="card-header bg-primary text-white py-3 border-0 rounded-top-4">
                <h6 class="mb-0 fw-bold"><i class="fas fa-globe-americas me-2"></i> PUESTA EN MARCHA GLOBAL</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    @php 
                        $tareasGlobales = [
                            'walledgarden' => ['label' => 'Walled Garden', 'icon' => 'fa-shield-alt'],
                            'portal'       => ['label' => 'Descargar Portal', 'icon' => 'fa-download'],
                            'reboot'       => ['label' => 'Reiniciar Router', 'icon' => 'fa-power-off']
                        ];
                    @endphp
                    @foreach($tareasGlobales as $key => $info)
                    <div class="col-md-4 mb-3">
                        <div class="p-3 border rounded-3 bg-light shadow-sm text-center">
                            <i class="fas {{ $info['icon'] }} mb-2 text-primary" style="font-size: 1.5rem;"></i>
                            <p class="small fw-bold text-uppercase mb-2" style="font-size: 0.7rem;">{{ $info['label'] }}</p>
                            
                            <button wire:click="ejecutarTarea('global', 0, '{{ $key }}')"
                                class="btn btn-sm w-100 rounded-pill fw-bold shadow-sm {{ ($taskStatus['global'][$key] ?? '') == 'success' ? 'btn-success' : (($taskStatus['global'][$key] ?? '') == 'error' ? 'btn-danger' : 'btn-primary') }}">
                                {{ ($taskStatus['global'][$key] ?? '') == 'loading' ? 'Procesando...' : 'EJECUTAR' }}
                            </button>

                            @if(isset($taskResult['global'][$key]))
                                <div class="mt-2">
                                    <small class="font-monospace {{ ($taskStatus['global'][$key] ?? '') == 'success' ? 'text-success' : 'text-danger' }}" style="font-size: 0.65rem;">
                                        {{ $taskResult['global'][$key] }}
                                    </small>
                                </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>