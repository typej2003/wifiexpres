<div class="p-4" @if($isWaitingResponse || $activeTask) wire:poll.1s="checkStatus" @endif>
    
    <div class="card shadow-sm border-0 mb-4 bg-light rounded-4">
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-5">
                    <label class="small fw-bold text-muted text-uppercase mb-1">Aliado</label>
                    <select wire:model="selectedAliado" class="form-select border-0 shadow-sm rounded-3">
                        <option value="">-- Seleccionar Aliado --</option>
                        @foreach($aliados as $aliado)
                            <option value="{{ $aliado->id }}">{{ $aliado->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="small fw-bold text-muted text-uppercase mb-1">Router MikroTik</label>
                    <select wire:model="router_id" class="form-select border-0 shadow-sm rounded-3" {{ !$selectedAliado ? 'disabled' : '' }}>
                        <option value="">-- Seleccionar Router --</option>
                        @foreach($routers as $router)
                            <option value="{{ $router->id }}">{{ $router->identity }} ({{ $router->macAddress }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 text-end">
                    @if(count($interfaces) > 0 && !$isWaitingResponse)
                        <button wire:click="iniciarDescubrimiento" class="btn btn-outline-primary btn-sm rounded-pill fw-bold">
                            <i class="fas fa-sync-alt"></i> Redescubrir
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($isWaitingResponse)
        <div class="card border-0 shadow-sm rounded-4 py-5 mb-4 text-center">
            <div class="card-body">
                <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;"></div>
                <h4 class="fw-bold">Escaneando Dispositivo</h4>
                <p class="text-muted small">Intento {{ $intentos }} de 20</p>
                <div class="progress mt-3 mx-auto" style="height: 6px; max-width: 300px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
                </div>
            </div>
        </div>
    @endif

    @if($showRetry && !$isWaitingResponse)
        <div class="alert alert-warning rounded-4 border-0 shadow-sm p-4 text-center">
            <i class="fas fa-exclamation-triangle fa-2x mb-2 text-warning"></i>
            <h5 class="fw-bold">Sin respuesta del MikroTik</h5>
            <p class="small">Asegúrese de que el equipo esté encendido y conectado al Bridge.</p>
            <button wire:click="iniciarDescubrimiento" class="btn btn-warning fw-bold px-4 rounded-pill mt-2">REINTENTAR</button>
        </div>
    @endif

    @if(count($interfaces) > 0 && !$isWaitingResponse)
        <div class="row">
            @foreach($interfaces as $index => $iface)
                @if($iface != 'ether1') {{-- Omitimos puerto WAN por defecto --}}
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100 border-top border-4 {{ ($taskStatus[$iface]['hotspot'] ?? '') == 'success' ? 'border-success' : 'border-primary' }}">
                        
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-dark"><i class="fas fa-ethernet me-2 text-primary"></i>{{ strtoupper($iface) }}</span>
                            <button wire:click="consultarEstadoInterfaz('{{ $iface }}')" class="btn btn-sm btn-light rounded-circle shadow-sm" title="Escanear estado actual">
                                <i class="fas fa-sync-alt text-primary {{ ($taskStatus[$iface]['loading_all'] ?? false) ? 'fa-spin' : '' }}"></i>
                            </button>
                        </div>

                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                @php 
                                    $tareas = [
                                        'bridge' => ['label' => 'Bridge & Port', 'icon' => 'fa-project-diagram'],
                                        'address' => ['label' => 'IP Address', 'icon' => 'fa-map-marker-alt'],
                                        'pool' => ['label' => 'Pool de IPs', 'icon' => 'fa-database'],
                                        'dhcp' => ['label' => 'Servidor DHCP', 'icon' => 'fa-server'],
                                        'hotspot' => ['label' => 'Hotspot Server', 'icon' => 'fa-wifi']
                                    ];
                                @endphp

                                @foreach($tareas as $key => $info)
                                    <div class="list-group-item d-flex justify-content-between align-items-center py-3 border-0">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3 text-center" style="width: 25px;">
                                                <i class="fas {{ $info['icon'] }} {{ ($taskStatus[$iface][$key] ?? '') == 'success' ? 'text-success' : 'text-muted opacity-25' }}"></i>
                                            </div>
                                            <div>
                                                <div class="small fw-bold text-uppercase" style="font-size: 0.75rem;">{{ $info['label'] }}</div>
                                                <div class="d-flex align-items-center">
                                                    @if(($taskStatus[$iface][$key] ?? '') == 'success')
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill" style="font-size: 0.6rem;">CONFIGURADO</span>
                                                    @elseif(($taskStatus[$iface][$key] ?? '') == 'missing')
                                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill" style="font-size: 0.6rem;">PENDIENTE</span>
                                                    @else
                                                        <span class="badge bg-light text-muted border rounded-pill" style="font-size: 0.6rem;">DESCONOCIDO</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <button 
                                            wire:click="ejecutarTarea('{{ $iface }}', {{ $index }}, '{{ $key }}')"
                                            wire:loading.attr="disabled"
                                            class="btn btn-sm rounded-pill shadow-sm px-3 {{ ($taskStatus[$iface][$key] ?? '') == 'success' ? 'btn-success disabled' : 'btn-primary' }}"
                                            style="min-width: 85px;">
                                            
                                            @if(($taskStatus[$iface][$key] ?? '') == 'loading')
                                                <span class="spinner-border spinner-border-sm"></span>
                                            @else
                                                <i class="fas fa-cog me-1"></i> Config
                                            @endif
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        
                        <div class="card-footer bg-light border-0 py-2">
                            <div class="text-center small text-muted font-monospace" style="font-size: 0.7rem;">
                                SUGERENCIA: 192.168.{{ ($index + 2) * 10 }}.0/24
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            @endforeach
        </div>
    @endif
</div>

<style>
    .bg-success-subtle { background-color: #d1e7dd; }
    .bg-danger-subtle { background-color: #f8d7da; }
    .list-group-item:hover { background-color: #f8f9fa; transition: 0.2s; }
</style>