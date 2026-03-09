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
            width: 18.5%; 
            height: 10.5cm; 
            border: 0.1pt solid #444; 
            margin: 0.4%; 
            padding: 20px 3px 10px 3px; /* Aumentamos el padding-top para bajar el logo */
            float: left;
            box-sizing: border-box; 
            text-align: center;
            overflow: hidden;
            page-break-inside: avoid; 
            background-color: #fff;
        }

        /* LOGO: Mantenemos tamaño pero con margen superior interno */
        .logo-container { 
            height: 60px; 
            margin-bottom: 10px; 
            display: block;
            overflow: hidden; 
        }
        .logo-img { max-height: 60px; max-width: 95%; }
        
        .comercio-nombre { 
            font-size: 8px; 
            font-weight: bold; 
            text-transform: uppercase; 
            margin-bottom: 5px;
            height: 22px;
            line-height: 11px;
            overflow: hidden;
        }

        .ticket-id { font-size: 7px; color: #555; margin-bottom: 5px; display: block; }

        .creds-box { 
            background: #f1f1f1; 
            padding: 6px 0; 
            margin: 8px 0;
            border-radius: 4px;
            border: 0.2pt solid #ccc;
        }
        .label { font-size: 6px; color: #666; display: block; }
        .text-value { 
            font-size: 11px; 
            font-weight: bold; 
            display: block;
            font-family: 'Courier New', monospace;
        }

        .plan-box { 
            background: #000; 
            color: #fff; 
            font-size: 9px; 
            padding: 4px; 
            font-weight: bold; 
            margin: 8px 0; 
        }
        
        .precio { font-size: 18px; font-weight: bold; margin: 8px 0; }

        /* QR GIGANTE: 85px (Oculto por solicitud) */
        .qr { display: none; margin: 15px 0; }
        .qr img { width: 85px; height: 85px; border: 0.1pt solid #eee; }

        .footer { font-size: 7px; margin-top: 10px; }
        .hotspot { font-weight: bold; border-top: 0.5pt dashed #aaa; padding-top: 5px; }
        
        .clearfix { clear: both; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="container">
        @foreach($tickets as $index => $t)
            <div class="ticket">
                <div class="logo-container">
                    @if($t->router->comercio_logo)
                        <img src="{{ public_path('storage/' . $t->router->comercio_logo) }}" class="logo-img">
                    @else
                        <div style="font-size: 10px; color: #ccc; padding-top: 20px;">WIFI</div>
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
                
                <div class="plan-box">{{ strtoupper($t->plan) }}</div>
                
                <div class="precio">Bs{{ number_format($t->costo, 2) }}</div>
                
                <div class="qr">
                    @php
                        $url = "http://" . ($t->router->hotspot_url ?? $t->router->ip) . "/login?username=" . $t->username . "&password=" . $t->password;
                        $qrCode = base64_encode(QrCode::format('svg')->size(150)->margin(0)->generate($url));
                    @endphp
                    <img src="data:image/svg+xml;base64,{{ $qrCode }}">
                </div>
                
                <div class="footer hotspot">{{ substr($t->router->hotspot_url ?? 'portal.wifi', 0, 30) }}</div>
            </div>

            @if(($index + 1) % 5 == 0)
                <div class="clearfix"></div>
            @endif

            @if(($index + 1) % 15 == 0)
                <div class="page-break"></div>
            @endif
        @endforeach
    </div>
</body>
</html>