<div class="container-fluid py-4" @if($esperandoRespuesta) wire:poll.1s="checkStatus" @endif>
    <div class="row">
        <div class="col-md-5">
            <div class="card bg-dark text-white border-secondary shadow mb-4">
                <div class="card-header border-secondary bg-transparent d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-info"><i class="fas fa-tools me-2"></i>Gestor de Archivos</h5>
                    <button wire:click="refreshStatus" class="btn btn-xs btn-outline-info">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="small text-white-50 text-uppercase fw-bold">Filtrar por Aliado</label>
                        <select wire:model="selectedAliado" wire:change="refreshStatus" class="form-select bg-dark text-white border-secondary shadow-sm">
                            <option value="">Todos los Aliados</option>
                            @foreach($aliados as $a)
                                <option value="{{$a->id}}">{{$a->name}}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="small text-white-50 text-uppercase fw-bold">Router MikroTik</label>
                        <select wire:model="router_id" class="form-select bg-dark text-white border-secondary shadow-sm" @if($isConfiguring) disabled @endif>
                            <option value="">Seleccione Router...</option>
                            @foreach($routers as $r)
                                @php $online = $routerStatus[$r->id] ?? false; @endphp
                                <option value="{{ $r->id }}" {{ !$online ? 'disabled' : '' }}>
                                    {{ $online ? '🟢' : '🔴' }} {{ $r->identity }} ({{ $r->macAddress }})
                                </option>
                            @endforeach
                        </select>
                        @if($router_id && !($routerStatus[$router_id] ?? false))
                            <span class="text-danger small mt-1"><i class="fas fa-exclamation-triangle"></i> El router seleccionado está OFFLINE.</span>
                        @endif
                    </div>

                    <div class="mb-4">
                        <label class="small text-white-50 text-uppercase fw-bold">Versión de Portal (HTML)</label>
                        <select wire:model="version_id" class="form-select bg-dark text-white border-info shadow-sm" @if($isConfiguring) disabled @endif>
                            <option value="">Seleccione Versión a instalar...</option>
                            @foreach($versiones as $v)
                                <option value="{{ $v->id }}">{{ $v->name }}</option>
                            @endforeach
                        </select>
                        @error('version_id') <span class="text-danger small">Debe seleccionar una versión.</span> @enderror
                    </div>

                    <div class="d-grid gap-3">
                        <button wire:click="resetHotspot" wire:loading.attr="disabled" 
                            @if(!$router_id || !$version_id || !($routerStatus[$router_id] ?? false) || $isConfiguring) disabled @endif
                            class="btn btn-warning fw-bold shadow-sm">
                            <i class="fas fa-sync-alt me-2"></i> RESET HTML HOTSPOT
                        </button>

                        <button wire:click="ejecutarTodo" wire:loading.attr="disabled" 
                            @if(!$router_id || !$version_id || !($routerStatus[$router_id] ?? false) || $isConfiguring) disabled @endif
                            class="btn btn-info fw-bold py-3 shadow">
                            <i class="fas fa-magic me-2"></i> INSTALACIÓN AUTOMÁTICA
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card bg-black border-secondary shadow" style="min-height: 500px;">
                <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                    <span class="text-white-50 font-monospace small">Consola de Comandos</span>
                    <span class="badge bg-info">{{ $progreso }}%</span>
                </div>
                <div class="card-body p-0">
                    <div class="progress rounded-0" style="height: 4px; background: #111;">
                        <div class="progress-bar bg-info" style="width: {{ $progreso }}%"></div>
                    </div>
                    <div id="logs-container" class="p-4 font-monospace small" style="color: #00ff41; height: 450px; overflow-y: auto;">
                        @foreach($logs as $log)
                            <div class="mb-1 border-start border-success ps-2">> {{ $log }}</div>
                        @endforeach
                        @if($esperandoRespuesta)
                            <div class="text-info mt-2"><i class="fas fa-spinner fa-spin"></i> MikroTik trabajando... ({{ $intentos }}s)</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('livewire:load', function () {
        window.addEventListener('logUpdated', event => {
            const container = document.getElementById('logs-container');
            if (container) { container.scrollTop = container.scrollHeight; }
        });
    });
</script>