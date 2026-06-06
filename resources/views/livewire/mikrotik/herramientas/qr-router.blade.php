<div class="container-fluid py-4">
    <div class="mb-4 d-print-none">
        <button wire:click="back" class="btn btn-outline-secondary rounded-pill shadow-sm px-4">
            <i class="bi bi-arrow-left me-1"></i> VOLVER A EQUIPOS
        </button>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden d-print-none">
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

                            <button wire:click="downloadQr" class="btn btn-outline-success rounded-pill px-4 mt-3 ms-2">
                                <i class="bi bi-download me-2"></i> Descargar JPG
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

    @if($ssid)
        {{-- DISEÑO PARA IMPRESIÓN --}}
        <div class="d-none d-print-block print-container">
            <div class="print-content">
                <div class="qr-wrapper">
                    {!! QrCode::size(450)->margin(1)->generate("WIFI:S:$ssid;;") !!}
                </div>
                
                <div class="print-text">
                    <h2 class="label-wifi">Escanea para conectarte al Wifi</h2>
                    <h1 class="comercio-title">{{ $comercio_nombre }}</h1>

                    <div class="steps-box">
                        <p>1. Conecta tu dispositivo a la WiFi: <strong>{{ $ssid }}</strong></p>
                        <p>2. Llena el formulario y conectate</p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <style>
        @media print {
            @page { size: letter; margin: 0; }
            html, body { height: 100%; overflow: hidden; }
            body { background: white !important; -webkit-print-color-adjust: exact; margin: 0 !important; padding: 0 !important; }
            
            /* Ocultar todo excepto el bloque de impresión */
            body * { visibility: hidden; }
            .d-print-block, .d-print-block * { visibility: visible; }
            
            .d-print-block {
                position: fixed;
                top: 0; left: 0; right: 0; bottom: 0;
                display: flex !important;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                background: white !important;
                page-break-after: avoid;
            }

            .print-content { text-align: center; width: 85%; margin: 0 auto; }
            
            /* QR enmarcado en un cuadrado */
            .qr-wrapper {
                display: inline-block;
                border: 12px solid #000;
                padding: 25px;
                margin-bottom: 1.5cm;
                background: white;
            }

            .label-wifi { font-size: 26pt; font-weight: bold; margin-bottom: 0.5cm; color: #000; }
            .comercio-title { font-size: 44pt; font-weight: 900; margin-bottom: 2cm; color: #000; text-transform: uppercase; }
            
            .steps-box { text-align: left; display: inline-block; font-size: 22pt; line-height: 1.4; color: #000; }
            .steps-box p { margin: 15px 0; }
        }
    </style>
</div>
