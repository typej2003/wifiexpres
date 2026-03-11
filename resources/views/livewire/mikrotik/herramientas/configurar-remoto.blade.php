<div class="container-fluid py-4" @if($esperandoRespuesta) wire:poll.1s="checkStatus" @endif>
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="card-header bg-primary text-white p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 fw-bold text-uppercase tracking-wider">
                            <i class="bi bi-terminal-fill me-2"></i>Provisionamiento Maestro
                        </h5>
                    </div>
                    <button wire:click="refreshStatus" class="btn btn-sm btn-light rounded-pill px-3 fw-bold">
                        <i class="bi bi-arrow-clockwise me-1"></i> REFRESCAR
                    </button>
                </div>
                
                <div class="card-body p-4 p-lg-5">
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Aliado</label>
                            <select wire:model="selectedAliado" wire:change="refreshStatus" class="form-select border-0 bg-light rounded-3 shadow-sm py-2">
                                <option value="">Todos los Aliados</option>
                                @foreach($aliados as $aliado)
                                    <option value="{{ $aliado->id }}">{{ $aliado->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Router MikroTik</label>
                            <select wire:model="router_id" class="form-select border-0 bg-light rounded-3 shadow-sm py-2" @if($isConfiguring) disabled @endif>
                                <option value="">Seleccione...</option>
                                @foreach($routers as $r)
                                    @php $online = $routerStatus[$r->id] ?? false; @endphp
                                    <option value="{{ $r->id }}" {{ !$online ? 'disabled' : '' }}>
                                        {{ $online ? '🟢' : '🔴' }} {{ $r->identity }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @if($isConfiguring || $progreso > 0)
                        <div class="mb-4">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small fw-bold">ESTADO DE LA TAREA: {{ $progreso }}%</span>
                                @if($esperandoRespuesta)
                                    <span class="badge bg-primary animate__animated animate__pulse animate__infinite">
                                        <i class="bi bi-cpu-fill me-1"></i> PROCESANDO ({{ $intentos }}s)
                                    </span>
                                @endif
                            </div>
                            <div class="progress" style="height: 12px; border-radius: 10px; background-color: #eee;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" style="width: {{ $progreso }}%; transition: width 0.4s ease;"></div>
                            </div>
                        </div>
                    @endif

                    <div class="row g-3 mb-5">
                        <div class="col-md-6">
                            <button wire:click="ejecutarConfiguracion" @if(!$router_id || $isConfiguring) disabled @endif
                                class="btn btn-primary btn-lg rounded-pill fw-bold w-100 py-3 shadow-sm">
                                <i class="bi bi-rocket-takeoff-fill me-2"></i> INICIAR CONFIG
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button wire:click="ejecutarResetSelectivo" @if(!$router_id || $isConfiguring) disabled @endif
                                class="btn btn-outline-warning btn-lg rounded-pill fw-bold w-100 py-3 shadow-sm">
                                <i class="bi bi-trash-fill me-1"></i> RESET
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button wire:click="detenerProceso" @if(!$isConfiguring) disabled @endif
                                class="btn btn-danger btn-lg rounded-pill fw-bold w-100 py-3 shadow-sm">
                                <i class="bi bi-stop-circle-fill"></i>
                            </button>
                        </div>
                    </div>

                    <div class="terminal-box bg-dark rounded-4 p-4 shadow-inner">
                        <div id="logs-container" class="console-text" style="height: 300px; overflow-y: auto; font-family: 'Courier New', monospace; font-size: 0.85rem; scroll-behavior: smooth;">
                            @foreach($logs as $log)
                                <div class="mb-1 text-light border-start border-success border-2 ps-3">
                                    <span class="text-success fw-bold">mikrotik@wifi:~$</span> 
                                    <span class="ms-2 opacity-90">{{ $log }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('livewire:load', function () {
        // Auto-scroll cada vez que Livewire actualiza
        window.addEventListener('logUpdated', event => {
            const container = document.getElementById('logs-container');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        });

        // Observador por si el evento no llega
        const target = document.getElementById('logs-container');
        if (target) {
            new MutationObserver(() => {
                target.scrollTop = target.scrollHeight;
            }).observe(target, { childList: true });
        }
    });
</script>