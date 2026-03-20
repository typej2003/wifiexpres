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
                        <button wire:click="iniciarDescubrimiento" class="btn btn-primary btn-sm rounded-pill px-3 fw-bold">
                            <i class="fas fa-sync-alt"></i> RECARGAR
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($isWaitingResponse)
        <div class="text-center py-5">
            <div class="spinner-border text-primary"></div>
            <p class="mt-2 fw-bold">Conectando con el dispositivo...</p>
        </div>
    @endif

    @if(count($interfaces) > 0 && !$isWaitingResponse)
        <div class="row">
            @foreach($interfaces as $index => $iface)
                @if($iface != 'ether1')
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card border-0 shadow rounded-4 h-100 overflow-hidden">
                        <div class="card-header bg-dark text-white py-3 border-0">
                            <h6 class="mb-0 fw-bold"><i class="fas fa-network-wired me-2"></i>{{ strtoupper($iface) }}</h6>
                        </div>
                        
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                @php 
                                    $tareas = [
                                        'bridge'  => 'Configurar Bridge',
                                        'address' => 'IP Address (.'.(($index+1)*10).'.1)',
                                        'pool'    => 'Crear Pool de IPs',
                                        'dhcp'    => 'Servidor DHCP',
                                        'hotspot' => 'Servidor Hotspot'
                                    ];
                                @endphp

                                @foreach($tareas as $key => $label)
                                    <div class="list-group-item py-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="small fw-bold">{{ $label }}</span>
                                            
                                            <button 
                                                wire:click="ejecutarTarea('{{ $iface }}', {{ $index }}, '{{ $key }}')"
                                                wire:loading.attr="disabled"
                                                class="btn btn-sm rounded-pill px-3 fw-bold {{ ($taskStatus[$iface][$key] ?? '') == 'success' ? 'btn-success' : (($taskStatus[$iface][$key] ?? '') == 'error' ? 'btn-danger' : 'btn-primary') }}">
                                                
                                                @if(($taskStatus[$iface][$key] ?? '') == 'loading')
                                                    <span class="spinner-border spinner-border-sm"></span>
                                                @else
                                                    Config
                                                @endif
                                            </button>
                                        </div>

                                        @if(isset($taskResult[$iface][$key]))
                                            <div class="mt-2 p-1 px-2 rounded bg-light border">
                                                <small class="font-monospace {{ ($taskStatus[$iface][$key] ?? '') == 'success' ? 'text-success' : 'text-danger' }}" style="font-size: 0.7rem;">
                                                    <i class="fas fa-reply me-1"></i> {{ $taskResult[$iface][$key] }}
                                                </small>
                                            </div>
                                        @endif
                                    </li>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            @endforeach
        </div>
    @endif
</div>