<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="card-header bg-dark text-white p-4">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-gear-fill me-2"></i>CONFIGURACIÓN REMOTA (PROVISIÓN)</h5>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted small">Esta herramienta ejecutará un script base: Usuario soporte, Bridge LAN, IP Address y DHCP Server.</p>
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-uppercase">Selecciona el Router Destino</label>
                        <select wire:model="router_id" class="form-select form-select-lg rounded-3 shadow-sm border-primary">
                            <option value="">-- Seleccionar Equipo --</option>
                            @foreach($routers as $r)
                                <option value="{{ $r->id }}">{{ $r->identity ?? 'MikroTik' }} ({{ $r->macAddress }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="d-grid">
                        <button 
                            wire:click="ejecutarConfiguracion" 
                            wire:loading.attr="disabled"
                            @if(!$router_id) disabled @endif
                            class="btn btn-primary btn-lg rounded-pill fw-bold shadow">
                            <span wire:loading wire:target="ejecutarConfiguracion" class="spinner-border spinner-border-sm me-2"></span>
                            <i class="bi bi-lightning-charge-fill"></i> EFECTUAR OPERACIÓN
                        </button>
                    </div>

                    <hr class="my-4">

                    {{-- CONSOLA DE LOGS --}}
                    <div class="bg-dark rounded-3 p-3 shadow-inner" style="min-height: 200px; max-height: 400px; overflow-y: auto;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-secondary">Consola de Salida</span>
                            @if($isConfiguring) <span class="spinner-grow spinner-grow-sm text-warning"></span> @endif
                        </div>
                        <div class="text-monospace">
                            @foreach($logs as $log)
                                <div class="text-light small mb-1 border-bottom border-secondary pb-1">
                                    <span class="text-success fw-bold">>>></span> {{ $log }}
                                </div>
                            @endforeach
                            @if(empty($logs))
                                <div class="text-muted small italic">Esperando interacción...</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>