<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-header bg-gradient-primary p-4">
                    <h5 class="text-white mb-0"><i class="bi bi-gear-wide-connected me-2"></i> Ajuste de Perfil Trial (hsprof1)</h5>
                </div>
                <div class="card-body p-4">
                    
                    @if($message)
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            {{ $message }}
                            <button type="button" class="btn-close" wire:click="$set('message', null)"></button>
                        </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">1. Filtrar Aliado</label>
                            <select wire:model="selectedAliado" class="form-select border-2">
                                <option value="">Seleccione Aliado...</option>
                                @foreach($aliados as $aliado)
                                    <option value="{{ $aliado->id }}">{{ $aliado->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">2. Router Objetivo</label>
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
                            <label class="form-label fw-bold">3. Nuevo Perfil para Trial</label>
                            <div class="input-group">
                                <select wire:model="perfil_seleccionado" class="form-select border-2" {{ empty($perfiles) ? 'disabled' : '' }}>
                                    <option value="">-- Elija el perfil de destino --</option>
                                    @foreach($perfiles as $p)
                                        <option value="{{ $p }}">{{ $p }}</option>
                                    @endforeach
                                </select>
                                <button wire:click="obtenerPerfiles" class="btn btn-outline-primary" wire:loading.attr="disabled" {{ !$router_id ? 'disabled' : '' }}>
                                    <i class="bi bi-arrow-clockwise" wire:loading.remove wire:target="obtenerPerfiles"></i>
                                    <span class="spinner-border spinner-border-sm" wire:loading wire:target="obtenerPerfiles"></span>
                                    Cargar Perfiles
                                </button>
                            </div>
                            <small class="text-muted mt-2 d-block">
                                <i class="bi bi-info-circle me-1"></i> Se aplicará al Server Profile <strong>hsprof1</strong> del MikroTik.
                            </small>
                        </div>

                        <div class="col-12 mt-4">
                            <button wire:click="aplicarCambio" 
                                    class="btn btn-dark w-100 py-2 fw-bold shadow"
                                    wire:loading.attr="disabled"
                                    {{ !$perfil_seleccionado ? 'disabled' : '' }}>
                                <span wire:loading.remove wire:target="aplicarCambio">
                                    <i class="bi bi-check2-circle me-2"></i> ACTUALIZAR PERFIL EN ROUTER
                                </span>
                                <span wire:loading wire:target="aplicarCambio">
                                    <i class="bi bi-hourglass-split me-2"></i> PROCESANDO CAMBIO...
                                </span>
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>