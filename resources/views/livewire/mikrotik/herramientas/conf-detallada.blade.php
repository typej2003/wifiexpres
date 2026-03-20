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
                            <button wire:click="scanearInterfaz('{{ $iface }}', {{ $index }})" class="btn btn-info btn-xs rounded-pill px-2 fw-bold text-white shadow-sm" style="font-size: 0.6rem;">
                                <i class="fas fa-search"></i> SCAN AUTO
                            </button>
                        </div>
                        
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                @php 
                                    $tareas = [
                                        'bridge'  => 'Configurar Bridge',
                                        'address' => 'IP Address (.'.(($index+1)*10).'.1)',
                                        'pool'    => 'Crear Pool de IPs',
                                        'dhcp'    => 'Servidor DHCP',
                                        'hotspot' => 'Hotspot & Profiles'
                                    ];
                                @endphp

                                @foreach($tareas as $key => $label)
                                    <li class="list-group-item py-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="small fw-bold text-uppercase" style="font-size: 0.75rem;">{{ $label }}</span>
                                            
                                            <button 
                                                wire:click="ejecutarTarea('{{ $iface }}', {{ $index }}, '{{ $key }}')"
                                                wire:loading.attr="disabled"
                                                class="btn btn-sm px-3 rounded-pill fw-bold shadow-sm {{ ($taskStatus[$iface][$key] ?? '') == 'success' ? 'btn-success' : (($taskStatus[$iface][$key] ?? '') == 'error' ? 'btn-danger' : 'btn-primary') }}">
                                                
                                                @if(($taskStatus[$iface][$key] ?? '') == 'loading')
                                                    <span class="spinner-border spinner-border-sm"></span>
                                                @else
                                                    Config
                                                @endif
                                            </button>
                                        </div>

                                        @if(isset($taskResult[$iface][$key]))
                                            <div class="mt-1">
                                                <small class="font-monospace {{ ($taskStatus[$iface][$key] ?? '') == 'success' ? 'text-success' : (($taskStatus[$iface][$key] ?? '') == 'error' ? 'text-danger' : 'text-muted') }}" style="font-size: 0.65rem;">
                                                    <i class="fas fa-reply me-1"></i> {{ $taskResult[$iface][$key] }}
                                                </small>
                                            </div>
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
    @endif
</div>