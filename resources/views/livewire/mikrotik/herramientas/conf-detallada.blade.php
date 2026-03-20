<div class="p-4">
    <div class="card shadow-sm border-0 mb-4 bg-light rounded-4">
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-4">
                    <label class="small fw-bold text-muted">Aliado</label>
                    <select wire:model="selectedAliado" class="form-select border-0">
                        <option value="">-- Seleccionar --</option>
                        @foreach($aliados as $aliado)
                            <option value="{{ $aliado->id }}">{{ $aliado->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="small fw-bold text-muted">Router</label>
                    <select wire:model="router_id" class="form-select border-0 shadow-sm">
                        <option value="">-- Seleccionar --</option>
                        @foreach($routers as $router)
                            <option value="{{ $router->id }}">{{ $router->identity }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    @if($isWaitingResponse)
        <div class="text-center py-5">
            <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;"></div>
            <h5 class="fw-bold">Interrogando al MikroTik...</h5>
        </div>
    @endif

    @if(!$isWaitingResponse && count($interfaces) > 0)
        <div class="row">
            @foreach($interfaces as $index => $iface)
                @if($iface != 'ether1')
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                        <div class="card-header bg-dark text-white py-3 border-0 d-flex justify-content-between">
                            <span class="fw-bold"><i class="fas fa-network-wired me-2 text-info"></i>{{ strtoupper($iface) }}</span>
                            <span class="badge bg-secondary">IP .{{ ($index + 2) * 10 }}.1</span>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                @php $tareas = [
                                    'bridge' => 'Configurar Bridge & Port',
                                    'address' => 'Asignar IP Address',
                                    'pool' => 'Crear Pool de IPs',
                                    'dhcp' => 'Servidor DHCP & Network',
                                    'hotspot' => 'Servidor Hotspot'
                                ]; @endphp

                                @foreach($tareas as $key => $label)
                                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                        <span class="small fw-bold {{ ($taskStatus[$iface][$key] ?? '') == 'success' ? 'text-success' : '' }}">
                                            @if(($taskStatus[$iface][$key] ?? '') == 'success') 
                                                <i class="fas fa-check-circle me-2"></i>
                                            @endif
                                            {{ $label }}
                                        </span>
                                        
                                        <button 
                                            wire:click="ejecutarTarea('{{ $iface }}', {{ $index }}, '{{ $key }}')"
                                            wire:loading.attr="disabled"
                                            class="btn btn-sm {{ ($taskStatus[$iface][$key] ?? '') == 'success' ? 'btn-outline-success' : 'btn-outline-primary' }} px-3">
                                            
                                            <span wire:loading wire:target="ejecutarTarea('{{ $iface }}', {{ $index }}, '{{ $key }}')">
                                                <span class="spinner-border spinner-border-sm"></span>
                                            </span>
                                            <span wire:loading.remove wire:target="ejecutarTarea('{{ $iface }}', {{ $index }}, '{{ $key }}')">
                                                {{ ($taskStatus[$iface][$key] ?? '') == 'success' ? 'Listo' : 'Ejecutar' }}
                                            </span>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="card-footer bg-light border-0 py-2">
                            <small class="text-muted font-monospace" style="font-size: 10px;">
                                > Red: 192.168.{{ ($index + 2) * 10 }}.0/24
                            </small>
                        </div>
                    </div>
                </div>
                @endif
            @endforeach
        </div>
    @endif
</div>