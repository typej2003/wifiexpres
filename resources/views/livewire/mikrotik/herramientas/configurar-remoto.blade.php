<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="card-header bg-primary text-white p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 fw-bold text-uppercase tracking-wider">
                            <i class="bi bi-terminal-fill me-2"></i>Reset Maestro Selectivo
                        </h5>
                    </div>
                    <button wire:click="refreshStatus" class="btn btn-sm btn-light rounded-pill px-3 fw-bold">
                        <i class="bi bi-arrow-clockwise me-1"></i> REFRESCAR
                    </button>
                </div>
                
                <div class="card-body p-4 p-lg-5">
                    {{-- SELECTOR DE ROUTER --}}
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted">Seleccionar MikroTik</label>
                        <select wire:model="router_id" class="form-select border-0 bg-light rounded-3 shadow-sm py-3" @if($isConfiguring) disabled @endif>
                            <option value="">Seleccione...</option>
                            @foreach($routers as $r)
                                <option value="{{ $r->id }}">{{ ($routerStatus[$r->id] ?? false) ? '🟢' : '🔴' }} {{ $r->identity }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- BARRA DE PROGRESO --}}
                    @if($isConfiguring || $progreso > 0)
                        <div class="mb-4">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted small fw-bold">Progreso del Reset</span>
                                <span class="text-primary small fw-bold">{{ $progreso }}%</span>
                            </div>
                            <div class="progress" style="height: 12px; border-radius: 10px; background-color: #e9ecef;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" 
                                     style="width: {{ $progreso }}%; transition: width 0.5s ease;"></div>
                            </div>
                        </div>
                    @endif

                    {{-- BOTONES DE ACCIÓN --}}
                    <div class="row g-3 mb-5">
                        <div class="col-md-8">
                            <button 
                                wire:click="ejecutarResetSelectivo" 
                                wire:loading.attr="disabled"
                                @if(!$router_id || $isConfiguring) disabled @endif
                                class="btn btn-warning btn-lg rounded-pill fw-bold shadow-sm w-100 py-3">
                                <i class="bi bi-trash-fill me-2"></i> INICIAR RESET SELECTIVO
                            </button>
                        </div>
                        <div class="col-md-4">
                            <button 
                                wire:click="detenerProceso" 
                                @if(!$isConfiguring || $abortar) disabled @endif
                                class="btn btn-danger btn-lg rounded-pill fw-bold shadow-sm w-100 py-3">
                                <i class="bi bi-x-circle-fill me-2"></i> ABORTAR
                            </button>
                        </div>
                    </div>

                    {{-- CONSOLA DE LOGS --}}
                    <div class="terminal-box bg-dark rounded-4 p-4 shadow-inner">
                        <div class="console-text" style="height: 300px; overflow-y: auto; font-family: 'Courier New', monospace; font-size: 0.85rem;">
                            @forelse($logs as $log)
                                <div class="mb-1">
                                    <span class="text-success fw-bold">mikrotik@system:~$</span> 
                                    <span class="text-light ms-2">{{ $log }}</span>
                                </div>
                            @empty
                                <div class="text-secondary text-center mt-5">Esperando órdenes...</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>