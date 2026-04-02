<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Impresión de Tickets</title>
    <style>
        @page { margin: 0.4cm; }
        body { font-family: 'Helvetica', Arial, sans-serif; margin: 0; background: #f4f4f4; }
        .no-print { background: #333; color: #fff; padding: 20px; text-align: center; }
        .container { width: 100%; display: block; background: #fff; padding: 10px; }
        
        .ticket { 
            width: 18.2%; 
            height: 8.8cm; 
            border: 0.5pt solid #000; 
            margin: 2px; 
            display: inline-block; 
            vertical-align: top; 
            text-align: center; 
            padding: 25px 5px 5px 5px; /* Margen superior ampliado sustancialmente */
            box-sizing: border-box; 
            overflow: hidden; 
        }

        .logo-img { max-height: 48px; max-width: 90%; margin-bottom: 8px; }
        .comercio-nombre { font-size: 11px; font-weight: bold; text-transform: uppercase; margin-bottom: 2px; }
        
        /* Dirección: Solo se muestra si tiene contenido */
        .comercio-direccion { 
            font-size: 7px; 
            color: #444; 
            text-transform: uppercase; 
            margin-bottom: 5px; 
            line-height: 1.1;
            padding: 0 5px;
        }

        .ticket-id { color: #666; font-size: 8px; margin-bottom: 10px; display: block; border-top: 0.5pt solid #eee; padding-top: 3px; }
        
        /* Credenciales alineadas a la izquierda */
        .creds-container { 
            width: 100%; 
            text-align: left;
            padding-left: 8px;
            margin-top: 5px;
        }
        
        .label-text { 
            font-size: 7px; 
            color: #555; 
            text-transform: uppercase; 
            font-weight: bold;
        }
        
        .value-text { 
            font-size: 19px; /* Valor ampliado significativamente */
            font-weight: bold;
            font-family: 'Courier New', Courier, monospace;
            display: block;
            margin-bottom: 6px;
            color: #000;
        }

        .plan-box { 
            background: #000; 
            color: #fff; 
            font-size: 11px; 
            padding: 5px; 
            margin: 12px 0 5px 0; 
            font-weight: bold; 
            text-transform: uppercase;
        }

        .precio { font-size: 20px; font-weight: bold; color: #000; margin-bottom: 8px; }
        
        /* QR Suprimido */
        .qr { display: none; } 

        .footer-info { 
            margin-top: 15px;
            font-size: 7px; 
            border-top: 0.5pt dashed #ccc;
            padding-top: 6px;
        }
        
        .fecha-hora { font-size: 7px; color: #444; margin-top: 2px; }

        @media print { 
            .no-print { display: none; } 
            body { background: #fff; } 
            .ticket { border: 0.5pt solid #000; } 
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" style="padding: 12px 40px; cursor:pointer; font-weight: bold; font-size: 16px; border-radius: 8px;">IMPRIMIR LOTE</button>
    </div>

    <div class="container">
        @foreach($tickets as $t)
            <div class="ticket">
                <div class="logo-container">
                    @if($router->comercio_logo)
                        <img src="{{ asset('storage/' . $router->comercio_logo) }}" class="logo-img">
                    @endif
                </div>
                
                <div class="comercio-nombre">{{ $router->comercio_nombre ?? 'WIFI EXPRES' }}</div>
                
                @if($router->comercio_direccion)
                    <div class="comercio-direccion">{{ $router->comercio_direccion }}</div>
                @endif

                <span class="ticket-id"># {{ $t->identity }}</span>
                
                <div class="creds-container">
                    <span class="label-text">USUARIO</span>
                    <span class="value-text">{{ $t->username }}</span>
                    
                    @if($t->password != $t->username)
                        <span class="label-text">CONTRASEÑA</span>
                        <span class="value-text">{{ $t->password }}</span>
                    @endif
                </div>

                <div class="plan-box">{{ strtoupper($t->plan_name ?? $t->plan) }}</div>
                <div class="precio">{{ number_format($t->costo, 2) }} BS</div>
                
                <div class="footer-info">
                    <div style="font-weight:bold;">{{ $router->hotspot_url ?? 'portal.wifi' }}</div>
                    <div class="fecha-hora">{{ $t->created_at->format('d/m/Y H:i') }}</div>
                </div>
            </div>
        @endforeach
    </div>
</body>
</html>