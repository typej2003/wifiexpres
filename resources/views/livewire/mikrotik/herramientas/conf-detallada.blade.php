<div class="p-4" @if($isWaitingResponse || $activeTask) wire:poll.1s="checkStatus" @endif>
    
    <div class="card shadow-sm border-0 mb-4 bg-light rounded-4">
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-4">
                    <label class="small fw-bold text-muted">ALIADO</label>
                    <select wire:model="selectedAliado" class="form-select border-0 shadow-sm rounded-3">
                        <option value="">-- Seleccionar --</option>
                        @foreach($aliados as $aliado)
                            <option value="{{ $aliado->id }}">{{ $aliado->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="small fw-bold text-muted">ROUTER</label>
                    <select wire:model="router_id" class="form-select border-0 shadow-sm rounded-3" {{ !$selectedAliado ? 'disabled' : '' }}>
                        <option value="">-- Seleccionar --</option>
                        @foreach($routers as $router)
                            <option value="{{ $router->id }}">{{ $router->identity }} ({{ $router->macAddress }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 text-end">
                    @if(count($interfaces) > 0)
                        <button wire:click="iniciarDescubrimiento" class="btn btn-outline-primary btn-sm rounded-pill fw-bold shadow-sm">
                            <i class="fas fa-sync-alt"></i> REFRESCAR HARDWARE
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

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
                                        'address' => 'IP Address',
                                        'pool'    => 'Crear Pool de IPs',
                                        'dhcp'    => 'Servidor DHCP',
                                        'hotspot' => 'Servidor Hotspot'
                                    ];
                                @endphp
                                @foreach($tareasInterface as $key => $label)
                                    <li class="list-group-item py-2 d-flex justify-content-between align-items-center">
                                        <span class="small fw-bold" style="font-size: 0.7rem;">{{ $label }}</span>
                                        <button wire:click="ejecutarTarea('{{ $iface }}', {{ $index }}, '{{ $key }}')"
                                            class="btn btn-xs rounded-pill {{ ($taskStatus[$iface][$key] ?? '') == 'success' ? 'btn-success' : 'btn-primary' }}" style="font-size: 0.6rem;">
                                            {{ ($taskStatus[$iface][$key] ?? '') == 'loading' ? '...' : 'Config' }}
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

        <div class="card shadow-sm border-0 rounded-4 bg-white mb-5">
            <div class="card-header bg-primary text-white py-3 border-0 rounded-top-4 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="fas fa-globe-americas me-2"></i> PUESTA EN MARCHA GLOBAL</h6>
                <button wire:click="scanGlobal" class="btn btn-warning btn-sm rounded-pill fw-bold shadow-sm" {{ $activeTask ? 'disabled' : '' }}>
                    <i class="fas fa-magic"></i> EJECUTAR CIERRE TOTAL
                </button>
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-4 mb-3">
                        <label class="small fw-bold text-muted">SELECCIONE PORTAL</label>
                        <select wire:model="version_id" class="form-select border shadow-sm rounded-3">
                            <option value="">-- Versión de Portal --</option>
                            @foreach($hotspot_versions as $version)
                                <option value="{{ $version->id }}">{{ $version->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted" style="font-size: 0.65rem;">Indispensable para el botón "Descargar Portal"</small>
                    </div>

                    <div class="col-md-8">
                        <div class="row">
                            @php 
                                $tareasGlobales = [
                                    'walledgarden' => ['label' => 'Walled Garden', 'icon' => 'fa-shield-alt'],
                                    'portal'       => ['label' => 'Descargar Portal', 'icon' => 'fa-download'],
                                    'reboot'       => ['label' => 'Reiniciar Router', 'icon' => 'fa-power-off']
                                ];
                            @endphp
                            @foreach($tareasGlobales as $key => $info)
                                <div class="col-4 text-center">
                                    <button wire:click="ejecutarTarea('global', 0, '{{ $key }}')"
                                        class="btn btn-outline-dark btn-sm w-100 rounded-pill mb-1 {{ ($taskStatus['global'][$key] ?? '') == 'success' ? 'bg-success text-white' : '' }}" style="font-size: 0.65rem;">
                                        <i class="fas {{ $info['icon'] }}"></i> {{ $info['label'] }}
                                    </button>
                                    @if(isset($taskResult['global'][$key]))
                                        <div style="font-size: 0.6rem;" class="{{ ($taskStatus['global'][$key] ?? '') == 'success' ? 'text-success' : 'text-danger' }}">
                                            {{ $taskResult['global'][$key] }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>