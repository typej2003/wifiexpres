<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="card-header bg-primary text-white p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 fw-bold text-uppercase tracking-wider">
                            <i class="bi bi-terminal-fill me-2"></i>Provisionamiento Maestro
                        </h5>
                        <small class="opacity-75">Control de Configuración y Reset</small>
                    </div>
                    <button wire:click="refreshStatus" class="btn btn-sm btn-light rounded-pill px-3 fw-bold">
                        <i class="bi bi-arrow-clockwise me-1"></i> REFRESCAR
                    </button>
                </div>
                
                <div class="card-body p-4 p-lg-5">
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Filtrar por Aliado</label>
                            <select wire:model="selectedAliado" wire:change="refreshStatus" class="form-select border-0 bg-light rounded-3 shadow-sm py-2">
                                <option value="">Todos los aliados</option>
                                @foreach($aliados as $aliado)
                                    <option value="{{ $aliado->id }}">{{ $aliado->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Router Destino</label>
                            <select wire:model="router_id" class="form-select border-0 bg-light rounded-3 shadow-sm py-2" @if($isConfiguring) disabled @endif>
                                <option value="">Seleccione un router...</option>
                                @foreach($routers as $r)
                                    @php $isOnline = $routerStatus[$r->id] ?? false; @endphp
                                    <option value="{{ $r->id }}" {{ !$isOnline ? 'disabled' : '' }}>
                                        {{ $isOnline ? '🟢' : '🔴' }} {{ $r->identity ?? 'MikroTik' }} ({{ $r->macAddress }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @if($isConfiguring || $progreso > 0)
                        <div class="mb-4 animate__animated animate__fadeIn">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small fw-bold">PROGRESO DEL PROCESO</span>
                                <span class="text-primary fw-bold">{{ $progreso }}%</span>
                            </div>
                            <div class="progress" style="height: 15px; border-radius: 10px; background-color: #eee;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" 
                                     role="progressbar" style="width: {{ $progreso }}%;"></div>
                            </div>
                        </div>
                    @endif

                    <div class="row g-3 mb-5">
                        <div class="col-md-6">
                            <button wire:click="ejecutarConfiguracion" wire:loading.attr="disabled"
                                @if(!$router_id || $isConfiguring) disabled @endif
                                class="btn btn-primary btn-lg rounded-pill fw-bold w-100 py-3 shadow-sm">
                                <i class="bi bi-rocket-takeoff-fill me-2"></i> LANZAR CONFIG
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button onclick="confirm('¿Borrar Hotspots, Bridges e IPs?') || event.stopImmediatePropagation()"
                                wire:click="ejecutarResetSelectivo" wire:loading.attr="disabled"
                                @if(!$router_id || $isConfiguring) disabled @endif
                                class="btn btn-outline-warning btn-lg rounded-pill fw-bold w-100 py-3 shadow-sm">
                                <i class="bi bi-trash-fill me-2"></i> RESET
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button wire:click="detenerProceso" @if(!$isConfiguring || $abortar) disabled @endif
                                class="btn btn-danger btn-lg rounded-pill fw-bold w-100 py-3 shadow-sm">
                                <i class="bi bi-stop-circle-fill me-2"></i> ABORTAR
                            </button>
                        </div>
                    </div>

                    <div class="terminal-box bg-dark rounded-4 p-4 shadow-inner">
                        <div class="console-text" style="height: 250px; overflow-y: auto; font-family: 'Courier New', monospace; font-size: 0.85rem;">
                            @forelse($logs as $log)
                                <div class="mb-1 text-light">
                                    <span class="text-success fw-bold">root@wifi:~$</span> 
                                    <span class="ms-2">{{ $log }}</span>
                                </div>
                            @empty
                                <div class="text-secondary text-center mt-5 italic">Esperando instrucciones...</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>