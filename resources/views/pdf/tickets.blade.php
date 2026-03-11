<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page { 
            size: legal portrait; 
            margin: 0.5cm; 
        }
        body { 
            font-family: 'Helvetica', sans-serif; 
            margin: 0; 
            padding: 0;
            color: #000;
        }
        .container { width: 100%; }

        .ticket { 
            width: 19%; /* Ajustado para mejor margen en legal */
            height: 7.8cm; /* Altura óptima para 20 por página */
            border: 0.5pt solid #333; 
            margin: 0.4%; 
            padding: 8px 3px 5px 3px; 
            float: left;
            box-sizing: border-box; 
            text-align: center;
            overflow: hidden;
            page-break-inside: avoid; 
            background-color: #fff;
            position: relative;
        }

        /* LOGO */
        .logo-container { 
            height: 40px; 
            margin-bottom: 3px; 
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden; 
        }
        .logo-img { max-height: 40px; max-width: 90%; object-fit: contain; }
        
        .comercio-nombre { 
            font-size: 9px; 
            font-weight: bold; 
            text-transform: uppercase; 
            margin-bottom: 2px;
            height: 22px;
            line-height: 11px;
            overflow: hidden;
            display: block;
        }

        .ticket-id { font-size: 7px; color: #555; margin-bottom: 2px; display: block; font-weight: bold; }

        .creds-box { 
            background: #f4f4f4; 
            padding: 3px 0; 
            margin: 4px 0;
            border-radius: 4px;
            border: 0.5pt solid #ddd;
        }
        .label { font-size: 6px; color: #666; display: block; text-transform: uppercase; }
        .text-value { 
            font-size: 12px; 
            font-weight: bold; 
            display: block;
            font-family: 'Courier', monospace;
        }

        .plan-box { 
            background: #000; 
            color: #fff; 
            font-size: 9px; 
            padding: 3px; 
            font-weight: bold; 
            margin: 4px 0; 
            text-transform: uppercase;
        }
        
        .precio { font-size: 16px; font-weight: bold; margin: 3px 0; }

        /* QR ACTIVADO PARA FACILIDAD DEL USUARIO */
        .qr-container { 
            margin: 5px auto;
            width: 50px;
            height: 50px;
        }
        .qr-img { width: 50px; height: 50px; }

        .footer { font-size: 7px; margin-top: 5px; }
        .hotspot { 
            font-weight: bold; 
            border-top: 0.5pt dashed #aaa; 
            padding-top: 4px; 
            white-space: nowrap;
            overflow: hidden;
        }
        
        .clearfix { clear: both; }
    </style>
</head>
<body>
    <div class="container">
        @foreach($tickets as $index => $t)
            <div class="ticket">
                <div class="logo-container">
                    @if($t->router->comercio_logo && file_exists(storage_path('app/public/' . $t->router->comercio_logo)))
                        <img src="{{ public_path('storage/' . $t->router->comercio_logo) }}" class="logo-img">
                    @else
                        <div style="font-size: 14px; font-weight: bold; color: #000; padding-top: 5px;">WIFI</div>
                    @endif
                </div>
                
                <div class="comercio-nombre">{{ $t->router->comercio_nombre ?? 'WIFI EXPRES' }}</div>
                <span class="ticket-id">PIN #{{ $t->identity }}</span>
                
                <div class="creds-box">
                    <span class="label">USUARIO</span>
                    <span class="text-value">{{ $t->username }}</span>
                    <span class="label">CONTRASEÑA</span>
                    <span class="text-value">{{ $t->password }}</span>
                </div>
                
                <div class="plan-box">{{ $t->plan }}</div>
                
                <div class="precio">
                    {{ $t->costo > 0 ? 'Bs' . number_format($t->costo, 2) : 'GRATIS' }}
                </div>
                
                <div class="qr-container">
                    @php
                        // Construcción de URL para Auto-Login si el dispositivo soporta escaneo
                        $url = "http://" . ($t->router->hotspot_url ?? $t->router->ip) . "/login?username=" . $t->username . "&password=" . $t->password;
                        // Generación del QR en formato PNG (más compatible con PDF que SVG directo)
                        $qrCode = base64_encode(QrCode::format('png')->size(100)->margin(0)->generate($url));
                    @endphp
                    <img src="data:image/png;base64,{{ $qrCode }}" class="qr-img">
                </div>
                
                <div class="footer hotspot">
                    {{ substr($t->router->hotspot_url ?? 'portal.wifi', 0, 25) }}
                </div>
            </div>

            {{-- Forzar limpieza de fila cada 5 tickets --}}
            @if(($index + 1) % 5 == 0)
                <div class="clearfix"></div>
            @endif

            {{-- Salto de página cada 20 tickets (4 filas de 5) --}}
            @if(($index + 1) % 20 == 0 && !$loop->last)
                <div style="page-break-after: always;"></div>
            @endif
        @endforeach
    </div>
</body>
</html>