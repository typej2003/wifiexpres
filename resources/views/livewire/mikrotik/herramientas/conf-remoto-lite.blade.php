<div class="container-fluid py-4" wire:poll.2s="checkStatus">
    <div class="row">
        <div class="col-md-4">
            <div class="card bg-dark text-white border-secondary shadow">
                <div class="card-header border-secondary bg-transparent">
                    <h5 class="mb-0 text-info">Configuración Remota Lite</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="small text-white-50">Filtrar por Aliado</label>
                        <select wire:model="selectedAliado" class="form-select bg-dark text-white border-secondary shadow-none">
                            <option value="">Todos los Aliados</option>
                            @foreach($aliados as $a) <option value="{{$a->id}}">{{$a->name}}</option> @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="small text-white-50">Router Objetivo</label>
                        <select wire:model="router_id" class="form-select bg-dark text-white border-secondary shadow-none">
                            <option value="">Seleccione Router</option>
                            @foreach($routers as $r)
                                <option value="{{$r->id}}">
                                    [{{ ($routerStatus[$r->id] ?? false) ? '🟢 ONLINE' : '🔴 OFF' }}] {{ $r->identity }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="small text-white-50">Portal Hotspot</label>
                        <select wire:model="version_id" class="form-select bg-dark text-white border-secondary shadow-none">
                            <option value="">Seleccione Versión</option>
                            @foreach($versiones as $v) <option value="{{$v->id}}">{{$v->name}}</option> @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="small text-white-50">Contraseña de Soporte</label>
                        <input type="password" wire:model="soporte_pass" class="form-control bg-dark text-white border-secondary shadow-none" placeholder="Min 4 caracteres">
                    </div>

                    <button wire:click="provisionarHapLite" 
                            wire:loading.attr="disabled"
                            class="btn btn-info w-100 fw-bold py-2 shadow-sm">
                        <i class="fas fa-magic me-2"></i> PROVISIONAR HAP LITE
                    </button>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card bg-black border-secondary shadow" style="min-height: 500px;">
                <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                    <span class="text-white-50 font-monospace small">Consola de Ejecución</span>
                    <span class="badge bg-{{ $isConfiguring ? 'primary' : 'success' }} px-3">{{ $progreso }}%</span>
                </div>
                <div class="card-body p-0 d-flex flex-column">
                    <div class="progress rounded-0" style="height: 4px; background: #111;">
                        <div class="progress-bar bg-info progress-bar-striped progress-bar-animated" style="width: {{ $progreso }}%"></div>
                    </div>
                    
                    <div id="log-container" class="p-3 font-monospace small flex-grow-1" style="max-height: 400px; overflow-y: auto; color: #0f0; background: #000;">
                        @foreach($logs as $log)
                            <div class="mb-1 border-start border-secondary ps-2">{{ $log }}</div>
                        @endforeach
                        @if($esperandoRespuesta)
                            <div class="text-info animate-pulse">📡 Esperando respuesta del RouterOS ({{ $intentos }}/35)...</div>
                        @endif
                    </div>
                </div>
                @if($isConfiguring)
                <div class="card-footer border-secondary text-center">
                    <button wire:click="detenerProceso" class="btn btn-sm btn-outline-danger px-4">DETENER PROCESO</button>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>