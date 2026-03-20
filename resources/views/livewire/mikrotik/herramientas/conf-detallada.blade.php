<div class="p-4" @if($isWaitingResponse || $activeTask) wire:poll.1s="checkStatus" @endif>
    
    @if($isWaitingResponse)
        <div class="text-center py-5">
            <div class="spinner-border text-primary"></div>
            <h5 class="mt-3">Buscando interfaces...</h5>
        </div>
    @endif

    @if(count($interfaces) > 0 && !$isWaitingResponse)
        <div class="row">
            @foreach($interfaces as $index => $iface)
                @if($iface != 'ether1')
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden border-top border-4 {{ ($taskStatus[$iface]['hotspot'] ?? '') == 'success' ? 'border-success' : 'border-primary' }}">
                        
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-dark"><i class="fas fa-ethernet me-2"></i>{{ strtoupper($iface) }}</span>
                            <button wire:click="consultarEstadoInterfaz('{{ $iface }}')" class="btn btn-sm btn-light text-primary rounded-pill">
                                <i class="fas fa-sync-alt {{ ($taskStatus[$iface]['loading_all'] ?? false) ? 'fa-spin' : '' }}"></i>
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
                                            <div class="icon-box me-3 text-center" style="width: 30px;">
                                                <i class="fas {{ $info['icon'] }} {{ ($taskStatus[$iface][$key] ?? '') == 'success' ? 'text-success' : 'text-muted opacity-50' }}"></i>
                                            </div>
                                            <div>
                                                <div class="small fw-bold">{{ $info['label'] }}</div>
                                                <span class="badge {{ ($taskStatus[$iface][$key] ?? '') == 'success' ? 'bg-soft-success text-success' : 'bg-soft-danger text-danger' }}" style="font-size: 0.65rem;">
                                                    {{ ($taskStatus[$iface][$key] ?? '') == 'success' ? 'CONFIGURADO' : 'PENDIENTE' }}
                                                </span>
                                            </div>
                                        </div>

                                        <button 
                                            wire:click="ejecutarTarea('{{ $iface }}', {{ $index }}, '{{ $key }}')"
                                            class="btn btn-sm rounded-pill shadow-sm px-3 {{ ($taskStatus[$iface][$key] ?? '') == 'success' ? 'btn-success disabled' : 'btn-primary' }}"
                                            style="min-width: 90px;">
                                            @if(($taskStatus[$iface][$key] ?? '') == 'loading')
                                                <span class="spinner-border spinner-border-sm"></span>
                                            @else
                                                <i class="fas fa-tools me-1"></i> Config
                                            @endif
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        
                        <div class="card-footer bg-light border-0 text-center py-2">
                            <small class="text-muted font-monospace">Red sugerida: 192.168.{{ ($index + 2) * 10 }}.0/24</small>
                        </div>
                    </div>
                </div>
                @endif
            @endforeach
        </div>
    @endif
</div>

<style>
    .bg-soft-success { background-color: #e8fadf; }
    .bg-soft-danger { background-color: #fde8e8; }
    .icon-box i { font-size: 1.1rem; transition: all 0.3s; }
</style>