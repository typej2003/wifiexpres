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

                        @if(count($antennas) > 0)
                            <div class="col-12 animate__animated animate__fadeIn">
                                <label class="form-label fw-bold small text-muted text-uppercase">Punto de Acceso / Antena</label>
                                <select wire:model="antenna_id" class="form-select border-0 bg-light rounded-3 shadow-sm py-2">
                                    <option value="main">SSID Principal del Router</option>
                                    @foreach($antennas as $antenna)
                                        <option value="{{ $antenna->id }}">
                                            {{ $antenna->location_name }} ({{ $antenna->ip_address }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>

                    @if($ssid)
                        <div class="text-center animate__animated animate__fadeIn">
                            <div class="bg-white p-4 d-inline-block rounded-4 shadow-sm border mb-4">
                                {{-- Generamos el código QR para red abierta: WIFI:S:SSID;; --}}
                                {!! QrCode::size(280)->margin(2)->generate("WIFI:S:$ssid;;") !!}
                            </div>
                            <h3 class="fw-bold text-dark mb-1">{{ $ssid }}</h3>
                            <div class="mb-3">
                                <span class="badge bg-info-subtle text-info border border-info-subtle px-3 py-2">
                                    <i class="bi bi-broadcast me-1"></i> IP: {{ $selected_ip }}
                                </span>
                            </div>
                            <div class="text-center mt-3">
                                <p class="mb-1 text-muted small">1. Escanea el QR</p>
                                <p class="mb-1 text-muted small">2. Haz clic al WiFi <strong>{{ $ssid }}</strong></p>
                                <p class="mb-0 text-muted small">3. Llena el formulario y presiona el Boton <strong>CONECTAR AHORA</strong></p>
                            </div>
                            
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
                    {!! QrCode::size(380)->margin(1)->generate("WIFI:S:$ssid;;") !!}
                </div>
                
                <div class="print-text">
                    <h2 class="label-wifi">Escanea para conectarte al Wifi</h2>
                    <h1 class="comercio-title">{{ $comercio_nombre }}</h1>

                    <div class="steps-box">
                        <p>1. Escanea el QR</p>
                        <p>2. Haz clic al WiFi: <strong>{{ $ssid }}</strong></p>
                        <p>3. Llena el formulario y presiona el Boton <strong>CONECTAR AHORA</strong></p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <style>
        @media print {
            @page { size: letter; margin: 1cm; }
            
            /* Ocultamos absolutamente toda la interfaz administrativa */
            .navbar, .sidebar, .sidebar-rednet, footer, .d-print-none, #sidebar, #toggle-sidebar, .header-sidebar { 
                display: none !important; 
            }
            
            body, html { background: white !important; margin: 0 !important; padding: 0 !important; width: 100%; }

            .d-print-block {
                display: block !important;
                text-align: center !important;
                width: 100% !important;
            }

            .print-content { display: inline-block; width: 100%; margin-top: 2cm; }
            
            /* QR enmarcado en un cuadrado */
            .qr-wrapper {
                display: inline-block;
                border: 12px solid #000;
                padding: 15px;
                margin-bottom: 0.5cm;
                background: white;
                line-height: 0;
            }

            .label-wifi { font-size: 18pt; font-weight: bold; margin-bottom: 0.2cm; color: #000; display: block; }
            .comercio-title { font-size: 22pt; font-weight: 800; margin-bottom: 1cm; color: #000; text-transform: uppercase; display: block; }
            
            .steps-box { text-align: left; display: inline-block; font-size: 16pt; line-height: 1.3; color: #000; border-top: 2px solid #000; padding-top: 0.5cm; width: 100%; max-width: 500px; }
            .steps-box p { margin: 10px 0; }
        }
    </style>
</div>
