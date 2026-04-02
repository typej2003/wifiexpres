<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-header bg-gradient-primary p-4 d-flex justify-content-between align-items-center">
                    <h5 class="text-white mb-0"><i class="bi bi-gear-wide-connected me-2"></i> Ajuste de Perfil Trial (hsprof1)</h5>
                    @if($router_id)
                        <button wire:click="consultarEstadoActual" class="btn btn-sm btn-outline-light rounded-pill px-3" wire:loading.attr="disabled">
                            <i class="bi bi-search me-1" wire:loading.remove wire:target="consultarEstadoActual"></i>
                            <span class="spinner-border spinner-border-sm me-1" wire:loading wire:target="consultarEstadoActual"></span>
                            Consultar Actual
                        </button>
                    @endif
                </div>
                <div class="card-body p-4">
                    
                    @if($message)
                        <div class="alert alert-info alert-dismissible fade show border-0 shadow-sm" role="alert">
                            {{ $message }}
                            <button type="button" class="btn-close" wire:click="$set('message', null)"></button>
                        </div>
                    @endif

                    @if($perfil_actual)
                        <div class="alert bg-light border-start border-primary border-4 mb-4 d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted small text-uppercase fw-bold d-block">Perfil Trial Actual:</span>
                                <span class="h5 mb-0 text-dark fw-bold">{{ $perfil_actual }}</span>
                            </div>
                            <i class="bi bi-info-circle-fill text-primary opacity-50 h2 mb-0"></i>
                        </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">1. Filtrar Aliado</label>
                            <select wire:model="selectedAliado" class="form-select border-2">
                                <option value="">Seleccione Aliado...</option>
                                @foreach($aliados as $aliado)
                                    <option value="{{ $aliado->id }}">{{ $aliado->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">2. Router Objetivo</label>
                            <select wire:model="router_id" class="form-select border-2 {{ ($routerStatus[$router_id] ?? false) ? 'border-success' : '' }}">
                                <option value="">Seleccione Router...</option>
                                @foreach($routers as $r)
                                    <option value="{{ $r->id }}">
                                        {{ ($routerStatus[$r->id] ?? false) ? '🟢' : '🔴' }} {{ $r->identity }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <hr class="my-4">

                        <div class="col-12">
                            <label class="form-label fw-bold small text-uppercase">3. Nuevo Perfil para Trial</label>
                            <div class="input-group">
                                <select wire:model="perfil_seleccionado" class="form-select border-2" {{ empty($perfiles) ? 'disabled' : '' }}>
                                    <option value="">-- Elija el perfil de destino --</option>
                                    @foreach($perfiles as $p)
                                        <option value="{{ $p }}">{{ $p }}</option>
                                    @endforeach
                                </select>
                                <button wire:click="obtenerPerfiles" class="btn btn-primary" wire:loading.attr="disabled" {{ !$router_id ? 'disabled' : '' }}>
                                    <i class="bi bi-arrow-repeat" wire:loading.remove wire:target="obtenerPerfiles"></i>
                                    <span class="spinner-border spinner-border-sm" wire:loading wire:target="obtenerPerfiles"></span>
                                    Actualizar Lista
                                </button>
                            </div>
                            <small class="text-muted mt-2 d-block">
                                <i class="bi bi-info-circle me-1"></i> Esto cambiará el comportamiento de los usuarios de cortesía.
                            </small>
                        </div>

                        <div class="col-12 mt-4">
                            <button wire:click="aplicarCambio" 
                                    class="btn btn-dark w-100 py-3 fw-bold shadow-sm"
                                    wire:loading.attr="disabled"
                                    {{ !$perfil_seleccionado ? 'disabled' : '' }}>
                                <span wire:loading.remove wire:target="aplicarCambio">
                                    <i class="bi bi-cloud-upload me-2"></i> APLICAR CAMBIO EN MIKROTIK
                                </span>
                                <span wire:loading wire:target="aplicarCambio">
                                    <i class="bi bi-hourglass-split me-2"></i> COMUNICANDO CON BRIDGE...
                                </span>
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>