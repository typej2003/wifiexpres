<div class="container-fluid py-4" wire:poll.2s="checkStatus">
    <div class="row">
        <div class="col-md-5">
            <div class="card bg-dark text-white border-secondary shadow mb-4">
                <div class="card-header border-secondary bg-transparent">
                    <h5 class="mb-0 text-info"><i class="fas fa-tools me-2"></i>Gestor de Archivos</h5>
                </div>
                <div class="card-body">
                    {{-- Selectores de Aliado y Router (Igual que antes) --}}
                    <div class="mb-3">
                        <label class="small text-white-50">Aliado</label>
                        <select wire:model="selectedAliado" class="form-select bg-dark text-white border-secondary">
                            <option value="">Todos</option>
                            @foreach($aliados as $a) <option value="{{$a->id}}">{{$a->name}}</option> @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="small text-white-50">Router Objetivo</label>
                        <select wire:model="router_id" class="form-select bg-dark text-white border-secondary">
                            <option value="">Seleccione Router</option>
                            @foreach($routers as $r)
                                <option value="{{$r->id}}">{{ $r->identity }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="d-grid gap-3">
                        {{-- BOTÓN RESET HTML (NUEVO) --}}
                        <button wire:click="resetHotspot" wire:loading.attr="disabled" class="btn btn-warning fw-bold shadow-sm">
                            <i class="fas fa-sync-alt me-2"></i> RESET HTML HOTSPOT
                        </button>

                        {{-- BOTÓN INSTALACIÓN COMPLETA --}}
                        <button wire:click="ejecutarTodo" wire:loading.attr="disabled" class="btn btn-info fw-bold py-3 shadow">
                            <i class="fas fa-magic me-2"></i> INSTALACIÓN AUTOMÁTICA
                        </button>
                        
                        <div class="row g-2 mt-2">
                            <div class="col-12">
                                <p class="small text-muted mb-1 text-uppercase fw-bold">Operaciones Manuales</p>
                            </div>
                            <div class="col-6">
                                <button wire:click="crearCarpetas" class="btn btn-outline-light btn-sm w-100">Carpetas</button>
                            </div>
                            <div class="col-6">
                                <button wire:click="instalarArchivos" class="btn btn-outline-light btn-sm w-100">Solo HTML</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            {{-- Consola (Igual que antes) --}}
            <div class="card bg-black border-secondary shadow" style="min-height: 500px;">
                <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                    <span class="text-white-50 font-monospace small">Consola de Comandos</span>
                    <span class="badge bg-info">{{ $progreso }}%</span>
                </div>
                <div class="card-body p-0">
                    <div class="progress rounded-0" style="height: 4px; background: #111;">
                        <div class="progress-bar bg-info" style="width: {{ $progreso }}%"></div>
                    </div>
                    <div class="p-4 font-monospace small" style="color: #00ff41;">
                        @foreach($logs as $log)
                            <div class="mb-1">>> {{ $log }}</div>
                        @endforeach
                        @if($esperandoRespuesta)
                            <div class="text-info mt-2"><i class="fas fa-spinner fa-spin"></i> Procesando en MikroTik ({{ $intentos }}/35)...</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>