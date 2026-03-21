<div class="p-4" @if($isWaitingResponse) wire:poll.2s="checkStatus" @endif>
    
    <style>
        .text-success-neon { color: #00C851 !important; font-weight: 800; text-shadow: 0px 0px 5px rgba(0, 200, 81, 0.2); }
        .btn-success-neon { background-color: #00C851 !important; border-color: #00C851 !important; color: white !important; font-weight: bold; }
    </style>

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
                        <button wire:click="iniciarDescubrimiento" wire:loading.attr="disabled" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm fw-bold">
                            <span wire:loading wire:target="iniciarDescubrimiento" class="spinner-border spinner-border-sm me-1"></span>
                            REFRESCAR HARDWARE
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if(count($interfaces) > 0)
        <div class="row">
            @foreach($interfaces as $index => $iface)
                @if($iface != 'ether1')
                <div class="col-md-6 col-lg-4 mb-4" wire:key="card-iface-{{ $iface }}">
                    <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden bg-white">
                        <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-uppercase">{{ $iface }}</span>
                            <button wire:click="scanearInterfaz('{{ $iface }}', {{ $index }})" 
                                    wire:loading.attr="disabled" {{ $isProcessing ? 'disabled' : '' }}
                                    class="btn btn-info btn-xs rounded-pill px-2 fw-bold text-white shadow-sm" style="font-size: 10px;">
                                SCAN AUTO
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                @php $tareas = ['bridge'=>'BRIDGE','address'=>'ADDRESS','pool'=>'POOL','dhcp'=>'DHCP','hotspot'=>'HOTSPOT']; @endphp
                                @foreach($tareas as $key => $label)
                                    <li class="list-group-item py-2 d-flex justify-content-between align-items-center" wire:key="task-{{ $iface }}-{{ $key }}">
                                        <div class="flex-grow-1">
                                            <span class="small fw-bold text-muted text-uppercase d-block">{{ $label }}</span>
                                            @if(isset($taskResult[$iface][$key]))
                                                <small class="{{ ($taskStatus[$iface][$key] ?? '') == 'success' ? 'text-success-neon' : 'text-danger fw-bold' }}">
                                                    {{ ($taskStatus[$iface][$key] ?? '') == 'loading' ? '⏳ Procesando...' : $taskResult[$iface][$key] }}
                                                </small>
                                            @endif
                                        </div>
                                        <button wire:click="ejecutarTarea('{{ $iface }}', {{ $index }}, '{{ $key }}')"
                                            wire:loading.attr="disabled" {{ $isProcessing ? 'disabled' : '' }}
                                            class="btn btn-sm rounded-pill px-3 {{ ($taskStatus[$iface][$key] ?? '') == 'success' ? 'btn-success-neon' : 'btn-primary' }}">
                                            @if(($taskStatus[$iface][$key] ?? '') == 'loading') 
                                                <span class="spinner-border spinner-border-sm"></span> 
                                            @else 
                                                <i class="fas fa-plus-circle" style="font-size: 10px;"></i>
                                            @endif
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
                @endif
            @endforeach
        </div>

        <div class="card border-0 shadow-sm rounded-4 bg-white mb-5 overflow-hidden" wire:key="card-global-settings">
            <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="fas fa-globe me-2"></i>WALLED GARDEN / PORTAL</h6>
                <button wire:click="scanGlobal" wire:loading.attr="disabled" {{ $isProcessing || !$version_id ? 'disabled' : '' }} class="btn btn-warning btn-sm rounded-pill fw-bold text-dark shadow-sm">
                    INSTALAR TODO
                </button>
            </div>
            <div class="card-body p-0">
                <div class="p-3 bg-light border-bottom">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <label class="small fw-bold text-muted text-uppercase">Versión del Portal</label>
                            <select wire:model="version_id" class="form-select border-0 shadow-sm rounded-3">
                                <option value="">-- Seleccionar --</option>
                                @foreach($hotspot_versions as $v) <option value="{{ $v->id }}">{{ $v->name }}</option> @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <ul class="list-group list-group-flush">
                    @php 
                        $globals = [
                            'wg_servidor' => ['label' => 'SERVIDOR & BRIDGE'],
                            'wg_bdv' => ['label' => 'BANCO DE VENEZUELA'],
                            'wg_push' => ['label' => 'NOTIFICACIONES PUSH'],
                            'portal' => ['label' => 'PORTAL CAUTIVO'],
                            'reboot' => ['label' => 'REINICIAR SISTEMA']
                        ];
                    @endphp
                    @foreach($globals as $key => $info)
                        <li class="list-group-item py-3" wire:key="global-task-{{ $key }}">
                            <div class="d-flex justify-content-between align-items-center px-3">
                                <div class="flex-grow-1">
                                    <span class="small fw-bold text-primary text-uppercase d-block">{{ $info['label'] }}</span>
                                    @if(isset($taskResult['global'][$key]))
                                        <div class="mt-1 {{ ($taskStatus['global'][$key] ?? '') == 'success' ? 'text-success-neon' : 'text-danger fw-bold' }}" style="font-size: 11px;">
                                            RESULTADO: {{ $taskResult['global'][$key] }}
                                        </div>
                                    @endif
                                </div>
                                <button wire:click="ejecutarTarea('global', 0, '{{ $key }}')"
                                    wire:loading.attr="disabled" 
                                    {{ $isProcessing ? 'disabled' : '' }}
                                    class="btn btn-sm rounded-pill px-4 shadow-sm fw-bold {{ ($taskStatus['global'][$key] ?? '') == 'success' ? 'btn-success-neon' : 'btn-outline-primary' }}">
                                    @if(($taskStatus['global'][$key] ?? '') == 'loading')
                                        <span class="spinner-border spinner-border-sm"></span>
                                    @else
                                        ENVIAR
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