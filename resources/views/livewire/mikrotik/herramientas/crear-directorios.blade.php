<div class="container-fluid py-4" wire:poll.2s="checkStatus">
    <div class="row">
        <div class="col-md-5">
            <div class="card bg-dark text-white border-secondary shadow mb-4">
                <div class="card-header border-secondary bg-transparent">
                    <h5 class="mb-0 text-info"><i class="fas fa-folder-plus me-2"></i>Gestor de Archivos Remoto</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="small text-white-50">Filtrar por Aliado</label>
                        <select wire:model="selectedAliado" class="form-select bg-dark text-white border-secondary shadow-none">
                            <option value="">Todos los Aliados</option>
                            @foreach($aliados as $a) <option value="{{$a->id}}">{{$a->name}}</option> @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="small text-white-50">Router Objetivo</label>
                        <select wire:model="router_id" class="form-select bg-dark text-white border-secondary shadow-none @error('router_id') is-invalid @enderror">
                            <option value="">Seleccione Router</option>
                            @foreach($routers as $r)
                                <option value="{{$r->id}}">{{ $r->identity }} ({{ $r->macAddress }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="d-grid gap-2">
                        <button wire:click="ejecutarTodo" wire:loading.attr="disabled" class="btn btn-info fw-bold py-3 shadow">
                            <i class="fas fa-play-circle me-2"></i> INSTALACIÓN AUTOMÁTICA COMPLETA
                        </button>
                        
                        <hr class="border-secondary my-3">
                        
                        <div class="row g-2">
                            <div class="col-6">
                                <button wire:click="crearCarpetas" wire:loading.attr="disabled" class="btn btn-outline-light btn-sm w-100">
                                    <i class="fas fa-folder me-1"></i> Solo Carpetas
                                </button>
                            </div>
                            <div class="col-6">
                                <button wire:click="instalarArchivos" wire:loading.attr="disabled" class="btn btn-outline-light btn-sm w-100">
                                    <i class="fas fa-file-html me-1"></i> Solo HTML
                                </button>
                            </div>
                            <div class="col-12">
                                <button wire:click="instalarCSS" wire:loading.attr="disabled" class="btn btn-outline-light btn-sm w-100">
                                    <i class="fas fa-css3-alt me-1"></i> Instalar CSS (Bootstrap/FontAwesome)
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card bg-black border-secondary shadow" style="min-height: 500px;">
                <div class="card-header border-secondary d-flex justify-content-between align-items-center bg-transparent">
                    <span class="text-white-50 font-monospace small">System Logs</span>
                    <span class="badge bg-info px-3">{{ $progreso }}%</span>
                </div>
                <div class="card-body p-0 d-flex flex-column">
                    <div class="progress rounded-0" style="height: 4px; background: #111;">
                        <div class="progress-bar bg-info progress-bar-striped progress-bar-animated" style="width: {{ $progreso }}%"></div>
                    </div>
                    
                    <div id="log-container" class="p-4 font-monospace small flex-grow-1" style="max-height: 450px; overflow-y: auto; color: #00ff41; background: #000; line-height: 1.6;">
                        @foreach($logs as $log)
                            <div class="mb-1 border-start border-secondary ps-3">
                                <span class="text-white-50">[{{ date('H:i:s') }}]</span> {{ $log }}
                            </div>
                        @endforeach
                        
                        @if($esperandoRespuesta)
                            <div class="text-info mt-2">
                                <span class="spinner-border spinner-border-sm me-2"></span>
                                Enviando comando al Router... (Intento {{ $intentos }}/30)
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>