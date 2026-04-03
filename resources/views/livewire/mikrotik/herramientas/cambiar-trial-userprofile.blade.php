<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-9 col-lg-8">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-header bg-gradient-primary p-4 d-flex justify-content-between align-items-center">
                    <h5 class="text-white mb-0"><i class="bi bi-gear-wide-connected me-2"></i> Configuración Hotspot Trial</h5>
                    <button wire:click="refreshStatus" class="btn btn-sm btn-outline-light rounded-pill px-3">
                        <i class="bi bi-arrow-clockwise"></i> Refrescar Router
                    </button>
                </div>
                
                <div class="card-body p-4">
                    @if($message)
                        <div class="alert {{ str_contains($message, '✅') ? 'alert-success' : 'alert-danger' }} alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                            {{ $message }}
                            <button type="button" class="btn-close" wire:click="$set('message', null)"></button>
                        </div>
                    @endif

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase text-muted">Filtrar por Aliado</label>
                            <select wire:model="selectedAliado" class="form-select border-2">
                                <option value="">Todos los Aliados</option>
                                @foreach($aliados as $aliado)
                                    <option value="{{ $aliado->id }}">{{ $aliado->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase text-muted">Router Objetivo</label>
                            <select wire:model="router_id" class="form-select border-2">
                                <option value="">Seleccione un router...</option>
                                @foreach($routers as $r)
                                    <option value="{{ $r->id }}">
                                        {{ ($routerStatus[$r->id] ?? false) ? '🟢' : '🔴' }} {{ $r->identity }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @if($router_id)
                    <div class="bg-light rounded-4 p-4 border shadow-sm">
                        <div class="row text-center mb-4">
                            <div class="col-md-6 border-end">
                                <span class="text-muted small fw-bold d-block text-uppercase">Perfil Actual</span>
                                <div class="h4 fw-bold text-primary mb-0">{{ $perfil_actual ?? '---' }}</div>
                            </div>
                            <div class="col-md-6">
                                <span class="text-muted small fw-bold d-block text-uppercase">Uptime Limit Actual</span>
                                <div class="h4 fw-bold text-danger mb-0">{{ $uptime_actual ?? '---' }}</div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nuevo Perfil Trial</label>
                                <div class="input-group">
                                    <select wire:model="perfil_seleccionado" class="form-select border-2">
                                        <option value="">-- Seleccionar --</option>
                                        @foreach($perfiles as $p)
                                            <option value="{{ $p }}">{{ $p }}</option>
                                        @endforeach
                                    </select>
                                    <button wire:click="obtenerListaPerfiles" class="btn btn-secondary shadow-sm">
                                        <i class="bi bi-arrow-repeat" wire:loading.remove wire:target="obtenerListaPerfiles"></i>
                                        <span class="spinner-border spinner-border-sm" wire:loading wire:target="obtenerListaPerfiles"></span>
                                    </button>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nuevo Uptime (HH:MM:SS)</label>
                                <select wire:model="uptime_seleccionado" class="form-select border-2">
                                    <option value="00:00:00">Sin tiempo</option>
                                    <option value="00:01:00">1 Minuto (Pruebas)</option>
                                    <option value="00:05:00">5 Minutos</option>
                                    <option value="00:10:00">10 Minutos</option>
                                    <option value="00:15:00">15 Minutos</option>
                                    <option value="00:20:00">20 Minutos</option>
                                    <option value="00:30:00">30 Minutos</option>
                                    <option value="01:00:00">1 Hora</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button wire:click="aplicarCambio" 
                                    class="btn btn-dark w-100 py-3 fw-bold rounded-pill shadow"
                                    wire:loading.attr="disabled"
                                    {{ !$perfil_seleccionado ? 'disabled' : '' }}>
                                <span wire:loading.remove wire:target="aplicarCambio">
                                    <i class="bi bi-cloud-check me-2"></i> GUARDAR CAMBIOS EN MIKROTIK
                                </span>
                                <span wire:loading wire:target="aplicarCambio">
                                    <span class="spinner-border spinner-border-sm me-2"></span> SINCRONIZANDO...
                                </span>
                            </button>
                        </div>
                    </div>
                    @else
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-router h1 d-block opacity-25"></i>
                        Seleccione un router para cargar la configuración
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>