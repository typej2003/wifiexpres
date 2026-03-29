<div class="container-fluid py-4" @if($esperandoRespuesta) wire:poll.1s="checkStatus" @endif>
    <div class="row">
        <div class="col-md-5">
            <div class="card bg-dark text-white border-secondary shadow mb-4">
                <div class="card-header border-secondary bg-transparent d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-info font-monospace"><i class="fas fa-microchip me-2"></i>Herramientas HTML</h5>
                    <button wire:click="refreshStatus" class="btn btn-sm btn-outline-info">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="small text-white-50 fw-bold">ALIADO</label>
                        <select wire:model="selectedAliado" wire:change="refreshStatus" class="form-select bg-dark text-white border-secondary shadow-sm">
                            <option value="">Todos</option>
                            @foreach($aliados as $a)
                                <option value="{{$a->id}}">{{$a->name}}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="small text-white-50 fw-bold">ROUTER</label>
                        <select wire:model="router_id" class="form-select bg-dark text-white border-secondary shadow-sm" @if($isConfiguring) disabled @endif>
                            <option value="">Seleccione...</option>
                            @foreach($routers as $r)
                                @php $online = $routerStatus[$r->id] ?? false; @endphp
                                <option value="{{ $r->id }}" {{ !$online ? 'disabled' : '' }}>
                                    {{ $online ? '🟢' : '🔴' }} {{ $r->identity }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="small text-white-50 fw-bold">VERSIÓN PORTAL</label>
                        <select wire:model="version_id" class="form-select bg-dark text-white border-info shadow-sm" @if($isConfiguring) disabled @endif>
                            <option value="">Seleccione...</option>
                            @foreach($versiones as $v)
                                <option value="{{ $v->id }}">{{ $v->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="d-grid gap-3">
                        <button wire:click="resetHotspot" 
                            @if(!$router_id || $isConfiguring) disabled @endif
                            class="btn btn-outline-warning btn-sm fw-bold">
                            <i class="fas fa-history me-2"></i> RESET NATIVO (DEFAULT)
                        </button>

                        <button wire:click="ejecutarTodo" 
                            @if(!$router_id || !$version_id || $isConfiguring) disabled @endif
                            class="btn btn-info fw-bold py-3 shadow">
                            <i class="fas fa-cloud-download-alt me-2"></i> INSTALACIÓN AUTOMÁTICA
                        </button>

                        <button wire:click="descargarLoginIndependiente" class="btn btn-warning" wire:loading.attr="disabled">
                            <i class="fas fa-file-download"></i> Forzar Login.html
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card bg-black border-secondary shadow" style="min-height: 520px;">
                <div class="card-header border-secondary d-flex justify-content-between">
                    <span class="text-info font-monospace small">Terminal Console</span>
                    <span class="text-white small">{{ $progreso }}%</span>
                </div>
                <div class="card-body p-0">
                    <div class="progress rounded-0" style="height: 2px; background: #111;">
                        <div class="progress-bar bg-info" style="width: {{ $progreso }}%"></div>
                    </div>
                    <div id="logs-container" class="p-4 font-monospace" style="color: #00ff41; height: 460px; overflow-y: auto; font-size: 13px;">
                        @foreach($logs as $log)
                            <div class="mb-1 fw-light">> {{ $log }}</div>
                        @endforeach
                        @if($esperandoRespuesta)
                            <div class="text-info mt-2"><i class="fas fa-sync fa-spin me-2"></i> Procesando en MikroTik... ({{ $intentos }}s)</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>