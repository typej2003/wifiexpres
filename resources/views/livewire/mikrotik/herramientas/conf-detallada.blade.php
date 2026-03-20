<div class="p-4" @if($isWaitingResponse || $activeTask) wire:poll.1s="checkStatus" @endif>
    
    <div class="card shadow-sm border-0 mb-4 bg-light rounded-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <label class="small fw-bold text-muted">ALIADO</label>
                    <select wire:model="selectedAliado" class="form-select border-0 shadow-sm rounded-3">
                        <option value="">-- Seleccionar --</option>
                        @foreach($aliados as $aliado)
                            <option value="{{ $aliado->id }}">{{ $aliado->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="small fw-bold text-muted">ROUTER</label>
                    <select wire:model="router_id" class="form-select border-0 shadow-sm rounded-3">
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
        <div class="card border-0 shadow-sm rounded-4 py-5 mb-4 text-center">
            <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;"></div>
            <h5 class="fw-bold">Escaneando Hardware...</h5>
            <p class="text-muted small">Intento {{ $intentos }} / 20</p>
        </div>
    @endif

    @if($showRetry && !$isWaitingResponse)
        <div class="alert alert-warning rounded-4 text-center">
            <p>El router no respondió. Verifique conexión.</p>
            <button wire:click="iniciarDescubrimiento" class="btn btn-warning btn-sm fw-bold">REINTENTAR</button>
        </div>
    @endif

    @if(count($interfaces) > 0 && !$isWaitingResponse)
        <div class="row">
            @foreach($interfaces as $index => $iface)
                @if($iface != 'ether1')
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                        <div class="card-header bg-dark text-white py-3 border-0 d-flex justify-content-between">
                            <span class="fw-bold"><i class="fas fa-network-wired me-2 text-info"></i>{{ strtoupper($iface) }}</span>
                            <span class="badge bg-secondary">IP .{{ ($index + 2) * 10 }}.1</span>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                @php $tareas = [
                                    'bridge' => 'Configurar Bridge',
                                    'address' => 'Asignar IP Address',
                                    'pool' => 'Crear Pool IPs',
                                    'dhcp' => 'Servidor DHCP',
                                    'hotspot' => 'Servidor Hotspot'
                                ]; @endphp

                                @foreach($tareas as $key => $label)
                                    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                        <span class="small fw-bold {{ ($taskStatus[$iface][$key] ?? '') == 'success' ? 'text-success' : '' }}">
                                            @if(($taskStatus[$iface][$key] ?? '') == 'success') <i class="fas fa-check-circle me-1"></i> @endif
                                            {{ $label }}
                                        </span>
                                        
                                        <button 
                                            wire:click="ejecutarTarea('{{ $iface }}', {{ $index }}, '{{ $key }}')"
                                            wire:loading.attr="disabled"
                                            class="btn btn-sm {{ ($taskStatus[$iface][$key] ?? '') == 'success' ? 'btn-success disabled' : (($taskStatus[$iface][$key] ?? '') == 'error' ? 'btn-danger' : 'btn-outline-primary') }}">
                                            
                                            @if(($taskStatus[$iface][$key] ?? '') == 'loading')
                                                <span class="spinner-border spinner-border-sm"></span>
                                            @else
                                                <i class="fas fa-play small"></i>
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
    @endif
</div>