<div class="container py-4">
    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Diseño de Ticket</h5>
                </div>
                <div class="card-body">
                    @if (session()->has('message'))
                        <div class="alert alert-success">{{ session('message') }}</div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre del Comercio</label>
                        <input type="text" wire:model="comercio_nombre" class="form-control" placeholder="Ej: Cyber Net">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Logo del Comercio</label>
                        <input type="file" wire:model="nuevo_logo" class="form-control" accept="image/*">
                        <div wire:loading wire:target="nuevo_logo" class="text-primary small">Subiendo imagen...</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Dirección Web (Hotspot)</label>
                        <input type="text" wire:model="hotspot_url" class="form-control" placeholder="Ej: wifi.login">
                    </div>

                    <button wire:click="save" class="btn btn-primary w-100">
                        <i class="bi bi-save me-1"></i> Guardar Configuración
                    </button>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <h6 class="text-muted text-center mb-3">VISTA PREVIA DEL TICKET (Aprox.)</h6>
            <div class="d-flex justify-content-center">
                <div style="width: 200px; border: 1px solid #000; padding: 10px; text-align: center; background: white;">
                    <div style="height: 50px; display: flex; align-items: center; justify-content: center; margin-bottom: 5px;">
                        @if($nuevo_logo)
                            <img src="{{ $nuevo_logo->temporaryUrl() }}" style="max-height: 50px; max-width: 100px;">
                        @elseif($logo_actual)
                            <img src="{{ asset('storage/' . $logo_actual) }}" style="max-height: 50px; max-width: 100px;">
                        @else
                            <div class="bg-light w-100 h-100 d-flex align-items-center justify-content-center small text-muted">LOGO</div>
                        @endif
                    </div>

                    <div style="font-size: 12px; font-weight: bold; text-transform: uppercase;">{{ $comercio_nombre ?: '[NOMBRE COMERCIO]' }}</div>
                    <div style="font-size: 9px; color: gray;">ID: TK-000000</div>
                    
                    <div class="my-2" style="font-size: 11px; line-height: 1.2;">
                        USUARIO: <strong>demo_user</strong><br>
                        CLAVE: <strong>123456</strong>
                    </div>

                    <div style="background: #f0f0f0; font-size: 10px; font-weight: bold; margin: 5px 0;">PLAN 5MB</div>
                    <div style="font-size: 14px; font-weight: bold;">$1.00</div>

                    <div class="bg-light mx-auto my-2" style="width: 70px; height: 70px; border: 1px solid #ddd; font-size: 8px; display: flex; align-items: center; justify-content: center;">QR CODE</div>

                    <div style="font-size: 8px; line-height: 1.1;">
                        {{ now()->format('d/m/Y H:i') }}<br>
                        <strong>{{ $hotspot_url ?: 'portal.wifi' }}</strong>
                    </div>
                    <div style="font-size: 8px; font-style: italic; border-top: 1px dashed #ccc; margin-top: 5px; padding-top: 2px;">
                        Conserve el ticket durante el servicio
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>