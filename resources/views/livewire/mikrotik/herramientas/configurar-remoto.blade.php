<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="card-header bg-primary text-white p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 fw-bold text-uppercase tracking-wider">
                            <i class="bi bi-terminal-fill me-2"></i>Provisionamiento Maestro
                        </h5>
                        <small class="opacity-75">Panel de Administración Global</small>
                    </div>
                    <button wire:click="refreshStatus" class="btn btn-sm btn-light rounded-pill px-3 fw-bold">
                        <i class="bi bi-arrow-clockwise me-1"></i> ACTUALIZAR ESTADOS
                    </button>
                </div>
                
                <div class="card-body p-4 p-lg-5">
                    <div class="row g-4 mb-4">
                        {{-- FILTRO POR ALIADO --}}
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Filtrar por Aliado</label>
                            <select wire:model="selectedAliado" wire:change="refreshStatus" class="form-select border-0 bg-light rounded-3 shadow-sm">
                                <option value="">Todos los aliados</option>
                                @foreach($aliados as $aliado)
                                    <option value="{{ $aliado->id }}">{{ $aliado->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- SELECCIÓN DE ROUTER ACTIVO --}}
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Router Destino (Solo Online)</label>
                            <select wire:model="router_id" class="form-select border-0 bg-light rounded-3 shadow-sm">
                                <option value="">Seleccione un router...</option>
                                @foreach($routers as $r)
                                    @php $isOnline = $routerStatus[$r->id] ?? false; @endphp
                                    <option value="{{ $r->id }}" {{ !$isOnline ? 'disabled' : '' }}>
                                        {{ $isOnline ? '🟢' : '🔴' }} {{ $r->identity ?? 'MikroTik' }} - {{ $r->macAddress }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="alert alert-info border-0 rounded-4 shadow-sm mb-4">
                        <div class="d-flex">
                            <i class="bi bi-info-circle-fill fs-4 me-3"></i>
                            <div>
                                <h6 class="fw-bold mb-1">¿Qué se configurará?</h6>
                                <ul class="small mb-0 opacity-85">
                                    <li>Usuario: <strong>soporte</strong> / Clave: <strong>123</strong></li>
                                    <li>Bridge LAN automático (Excluye ether1)</li>
                                    <li>IP: 192.168.88.1/24 + DHCP Server Activo</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    {{-- Busca la sección de botones y agrega este nuevo botón --}}
                    <div class="row g-3 mb-5">
                        <div class="col-md-8">
                            <button 
                                wire:click="ejecutarConfiguracion" 
                                wire:loading.attr="disabled"
                                @if(!$router_id || !($routerStatus[$router_id] ?? false)) disabled @endif
                                class="btn btn-primary btn-lg rounded-pill fw-bold shadow-sm w-100 py-3">
                                <span wire:loading wire:target="ejecutarConfiguracion" class="spinner-border spinner-border-sm me-2"></span>
                                <i class="bi bi-rocket-takeoff-fill me-2"></i> LANZAR CONFIGURACIÓN
                            </button>
                        </div>
                        <div class="col-md-4">
                            <button 
                                onclick="confirm('¿Estás seguro? Se borrarán Hotspots, Bridges e IPs (excepto ether1). Los scripts de automatización se mantendrán.') || event.stopImmediatePropagation()"
                                wire:click="ejecutarResetSelectivo" 
                                wire:loading.attr="disabled"
                                @if(!$router_id || !($routerStatus[$router_id] ?? false)) disabled @endif
                                class="btn btn-outline-warning btn-lg rounded-pill fw-bold shadow-sm w-100 py-3">
                                <span wire:loading wire:target="ejecutarResetSelectivo" class="spinner-border spinner-border-sm me-2"></span>
                                <i class="bi bi-trash-fill me-2"></i> RESET SELECTIVO
                            </button>
                        </div>
                    </div>

                    {{-- TERMINAL DE LOGS --}}
                    <div class="terminal-box bg-dark rounded-4 p-4 shadow-inner" style="border: 1px solid #333;">
                        <div class="d-flex justify-content-between align-items-center mb-3 border-bottom border-secondary pb-2">
                            <span class="text-secondary small fw-bold"><i class="bi bi-cpu me-1"></i> OUTPUT LOGS</span>
                            <span class="badge {{ $isConfiguring ? 'bg-warning text-dark' : 'bg-success' }} rounded-pill">
                                {{ $isConfiguring ? 'PROCESANDO...' : 'SISTEMA READY' }}
                            </span>
                        </div>
                        <div class="console-text" style="height: 250px; overflow-y: auto; font-family: 'Courier New', Courier, monospace;">
                            @forelse($logs as $log)
                                <div class="mb-2">
                                    <span class="text-primary fw-bold">root@wifi:~$</span> 
                                    <span class="text-light ms-2">{{ $log }}</span>
                                </div>
                            @empty
                                <div class="text-secondary small italic text-center mt-5">Ninguna operación iniciada.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .terminal-box { background-color: #0c0c0c !important; }
    .console-text::-webkit-scrollbar { width: 6px; }
    .console-text::-webkit-scrollbar-thumb { background: #333; border-radius: 10px; }
    .bg-success-soft { background-color: rgba(25, 135, 84, 0.1); }
</style>