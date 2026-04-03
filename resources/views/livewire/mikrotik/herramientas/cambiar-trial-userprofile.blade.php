<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-header bg-gradient-primary p-4 d-flex justify-content-between align-items-center text-white">
                    <h5 class="mb-0"><i class="bi bi-shield-lock me-2"></i> Ajuste Trial (hsprof1)</h5>
                    <button wire:click="refreshStatus" class="btn btn-sm btn-outline-light rounded-pill">
                        <i class="bi bi-arrow-clockwise"></i> Refrescar
                    </button>
                </div>
                
                <div class="card-body p-4">
                    @if($message)
                        <div class="alert {{ str_contains($message, '✅') ? 'alert-success' : 'alert-danger' }} shadow-sm mb-4 border-0">
                            {{ $message }}
                        </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">1. Aliado</label>
                            <select wire:model="selectedAliado" class="form-select">
                                <option value="">Seleccione Aliado...</option>
                                @foreach($aliados as $aliado)
                                    <option value="{{ $aliado->id }}">{{ $aliado->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">2. Router</label>
                            <select wire:model="router_id" class="form-select">
                                <option value="">Seleccione Router...</option>
                                @foreach($routers as $r)
                                    <option value="{{ $r->id }}">
                                        {{ ($routerStatus[$r->id] ?? false) ? '🟢' : '🔴' }} {{ $r->identity }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <hr class="my-4">

                        @if($router_id)
                        <div class="col-12 mb-4">
                            <div class="p-3 border rounded bg-light d-flex justify-content-around text-center">
                                <div>
                                    <small class="text-muted d-block text-uppercase">Perfil Actual</small>
                                    <strong class="h5 text-primary">{{ $perfil_actual ?? '---' }}</strong>
                                </div>
                                <div class="border-start ps-4">
                                    <small class="text-muted d-block text-uppercase">Uptime Actual</small>
                                    <strong class="h5 text-danger">{{ $uptime_actual ?? '---' }}</strong>
                                </div>
                                <div class="align-self-center">
                                    <button wire:click="consultarPerfilActual" class="btn btn-sm btn-primary" wire:loading.attr="disabled">
                                        <i class="bi bi-search"></i> Consultar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nuevo Perfil Trial</label>
                            <div class="input-group">
                                <select wire:model="perfil_seleccionado" class="form-select" {{ empty($perfiles) ? 'disabled' : '' }}>
                                    <option value="">-- Seleccione --</option>
                                    @foreach($perfiles as $p)
                                        <option value="{{ $p }}">{{ $p }}</option>
                                    @endforeach
                                </select>
                                <button wire:click="obtenerListaPerfiles" class="btn btn-secondary">
                                    <i class="bi bi-list" wire:loading.remove wire:target="obtenerListaPerfiles"></i>
                                    <span class="spinner-border spinner-border-sm" wire:loading wire:target="obtenerListaPerfiles"></span>
                                </button>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nuevo Uptime Limit</label>
                            <select wire:model="uptime_seleccionado" class="form-select">
                                <option value="00:00:00">Sin tiempo</option>
                                <option value="00:01:00">1 Minuto</option>
                                <option value="00:05:00">5 Minutos</option>
                                <option value="00:15:00">15 Minutos</option>
                                <option value="00:30:00">30 Minutos</option>
                                <option value="01:00:00">1 Hora</option>
                            </select>
                        </div>

                        <div class="col-12 mt-4">
                            <button wire:click="aplicarCambio" class="btn btn-dark w-100 py-3 fw-bold" 
                                    wire:loading.attr="disabled" {{ !$perfil_seleccionado ? 'disabled' : '' }}>
                                <span wire:loading.remove wire:target="aplicarCambio">
                                    <i class="bi bi-cloud-arrow-up me-2"></i> ACTUALIZAR MIKROTIK
                                </span>
                                <span wire:loading wire:target="aplicarCambio">
                                    <span class="spinner-border spinner-border-sm me-2"></span> PROCESANDO...
                                </span>
                            </button>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>