<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-header bg-gradient-primary p-4 d-flex justify-content-between align-items-center">
                    <h5 class="text-white mb-0"><i class="bi bi-gear-wide-connected me-2"></i> Ajuste de Perfil Trial (hsprof1)</h5>
                    <button wire:click="refreshStatus" class="btn btn-sm btn-outline-light rounded-pill px-3">
                        <i class="bi bi-arrow-clockwise"></i> Refrescar
                    </button>
                </div>
                <div class="card-body p-4">
                    
                    @if($message)
                        <div class="alert {{ str_contains($message, '✅') ? 'alert-success' : 'alert-danger' }} alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                            <div class="d-flex align-items-center">
                                <span class="fs-5 me-2"></span>
                                <div>{{ $message }}</div>
                            </div>
                            <button type="button" class="btn-close" wire:click="$set('message', null)"></button>
                        </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase text-muted">1. Filtrar Aliado</label>
                            <select wire:model="selectedAliado" class="form-select border-2">
                                <option value="">Seleccione Aliado...</option>
                                @foreach($aliados as $aliado)
                                    <option value="{{ $aliado->id }}">{{ $aliado->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase text-muted">2. Router Objetivo</label>
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

                        <div class="col-12 mb-3">
                            <div class="p-3 border rounded-3 bg-light d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted small fw-bold d-block">PERFIL TRIAL ACTUAL:</span>
                                    <span class="h5 mb-0 fw-bold {{ $perfil_actual ? 'text-primary' : 'text-secondary opacity-50' }}">
                                        {{ $perfil_actual ?? 'No consultado' }}
                                    </span>
                                </div>
                                <button wire:click="consultarPerfilActual" class="btn btn-outline-primary shadow-sm" 
                                        wire:loading.attr="disabled" {{ !$router_id || !($routerStatus[$router_id] ?? false) ? 'disabled' : '' }}>
                                    <span wire:loading.remove wire:target="consultarPerfilActual">
                                        <i class="bi bi-search me-1"></i> Consultar
                                    </span>
                                    <span wire:loading wire:target="consultarPerfilActual">
                                        <span class="spinner-border spinner-border-sm me-1"></span> Buscando...
                                    </span>
                                </button>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold small text-uppercase text-muted">3. Cambiar a Perfil:</label>
                            <div class="input-group">
                                <select wire:model="perfil_seleccionado" class="form-select border-2" {{ empty($perfiles) ? 'disabled' : '' }}>
                                    <option value="">-- Elija el perfil --</option>
                                    @foreach($perfiles as $p)
                                        <option value="{{ $p }}">{{ $p }}</option>
                                    @endforeach
                                </select>
                                <button wire:click="obtenerListaPerfiles" class="btn btn-secondary" 
                                        wire:loading.attr="disabled" {{ !$router_id || !($routerStatus[$router_id] ?? false) ? 'disabled' : '' }}>
                                    <span wire:loading.remove wire:target="obtenerListaPerfiles">
                                        <i class="bi bi-list-check"></i> Cargar Lista
                                    </span>
                                    <span wire:loading wire:target="obtenerListaPerfiles">
                                        <span class="spinner-border spinner-border-sm"></span>
                                    </span>
                                </button>
                            </div>
                        </div>

                        <div class="col-12 mt-4">
                            <button wire:click="aplicarCambio" 
                                    class="btn btn-dark w-100 py-3 fw-bold shadow-sm"
                                    wire:loading.attr="disabled"
                                    {{ !$perfil_seleccionado ? 'disabled' : '' }}>
                                <span wire:loading.remove wire:target="aplicarCambio">
                                    <i class="bi bi-cloud-arrow-up me-2"></i> ACTUALIZAR MIKROTIK
                                </span>
                                <span wire:loading wire:target="aplicarCambio">
                                    <i class="bi bi-hourglass-split me-2"></i> ENVIANDO COMANDO... POR FAVOR ESPERE
                                </span>
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>