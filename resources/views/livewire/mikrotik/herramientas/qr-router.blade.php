<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="card-header bg-primary text-white p-4">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-qr-code-scan me-2"></i> Generador de Acceso WiFi
                    </h5>
                </div>
                
                <div class="card-body p-4 p-lg-5">
                    <div class="row g-4 mb-5">
                        @if(Auth::user()->role === 'admin')
                            <div class="col-12">
                                <label class="form-label fw-bold small text-muted text-uppercase">Aliado Comercial</label>
                                <select wire:model="selectedAliado" class="form-select border-0 bg-light rounded-3 shadow-sm py-2">
                                    <option value="">Seleccione Aliado...</option>
                                    @foreach($aliados as $aliado)
                                        <option value="{{ $aliado->id }}">{{ $aliado->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="col-12">
                            <label class="form-label fw-bold small text-muted text-uppercase">Router MikroTik</label>
                            <select wire:model="router_id" class="form-select border-0 bg-light rounded-3 shadow-sm py-2" {{ !$selectedAliado ? 'disabled' : '' }}>
                                <option value="">Seleccione Router...</option>
                                @foreach($routers as $r)
                                    <option value="{{ $r->id }}">{{ $r->identity }} ({{ $r->macAddress }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @if($ssid)
                        <div class="text-center animate__animated animate__fadeIn">
                            <div class="bg-white p-4 d-inline-block rounded-4 shadow-sm border mb-4">
                                {{-- Generamos el código QR para red abierta: WIFI:S:SSID;; --}}
                                {!! QrCode::size(280)->margin(2)->generate("WIFI:S:$ssid;;") !!}
                            </div>
                            <h3 class="fw-bold text-dark mb-1">{{ $ssid }}</h3>
                            <p class="text-muted">Escanee para conectarse automáticamente a la red y acceder al portal.</p>
                            
                            <button onclick="window.print()" class="btn btn-outline-primary rounded-pill px-4 mt-3">
                                <i class="bi bi-printer me-2"></i> Imprimir Código
                            </button>
                        </div>
                    @else
                        <div class="text-center py-5 opacity-50">
                            <i class="bi bi-wifi-off display-1 text-muted"></i>
                            <p class="mt-3">Seleccione un equipo para generar el QR de conexión.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
