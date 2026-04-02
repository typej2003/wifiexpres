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
            padding: 15px 5px 5px 5px; /* Margen superior ampliado (15px) */
            box-sizing: border-box; 
            overflow: hidden; 
            position: relative;
        }

        .logo-img { max-height: 45px; max-width: 90%; margin-bottom: 5px; }
        
        .comercio-nombre { font-size: 10px; font-weight: bold; text-transform: uppercase; margin-bottom: 2px; }
        
        /* Nueva sección para la dirección */
        .comercio-direccion { 
            font-size: 7px; 
            color: #444; 
            text-transform: uppercase; 
            margin-bottom: 4px; 
            line-height: 1;
            border-bottom: 0.5pt solid #eee;
            padding-bottom: 3px;
        }

        .ticket-id { color: #666; font-size: 8px; margin-bottom: 8px; display: block; }
        
        /* Credenciales alineadas a la izquierda y amplias */
        .creds-table { 
            width: 100%; 
            border-collapse: collapse;
            margin-top: 5px;
        }
        .creds-table td { text-align: left; padding-left: 5px; }
        
        .label-text { 
            font-size: 7px; 
            color: #555; 
            text-transform: uppercase; 
            letter-spacing: 0.5px;
        }
        
        .value-text { 
            font-size: 18px; /* Valor muy grande para fácil lectura */
            font-weight: bold;
            font-family: 'Courier New', Courier, monospace;
            display: block;
            margin-bottom: 4px;
            color: #000;
        }

        .plan-box { 
            background: #000; 
            color: #fff; 
            font-size: 11px; 
            padding: 4px; 
            margin: 10px 0 5px 0; 
            font-weight: bold; 
            text-transform: uppercase;
        }

        .precio { font-size: 18px; font-weight: bold; color: #000; }
        
        .qr { display: none; } /* QR suprimido */

        .footer-info { 
            margin-top: 10px;
            font-size: 7px; 
            border-top: 0.5pt dashed #ccc;
            padding-top: 5px;
        }
        
        .fecha-hora { font-size: 7px; color: #333; margin-top: 2px; }

        @media print { 
            .no-print { display: none; } 
            body { background: #fff; } 
            .ticket { border: 0.5pt solid #000; } 
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" style="padding: 10px 30px; cursor:pointer; font-weight: bold; font-size: 16px;">IMPRIMIR TICKETS</button>
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
                
                <div class="comercio-direccion">
                    {{ $router->comercio_direccion ?? 'CARACAS, VENEZUELA' }}
                </div>

                <span class="ticket-id"># {{ $t->identity }}</span>
                
                <div class="creds-table">
                    <div class="label-text">USUARIO</div>
                    <div class="value-text">{{ $t->username }}</div>
                    
                    @if($t->password != $t->username)
                        <div class="label-text">CONTRASEÑA</div>
                        <div class="value-text">{{ $t->password }}</div>
                    @endif
                </div>

                <div class="plan-box">{{ strtoupper($t->plan_name ?? $t->plan) }}</div>
                <div class="precio">{{ number_format($t->costo, 2) }} BS</div>
                
                <div class="footer-info">
                    <div style="font-weight:bold;">{{ $router->hotspot_url ?? 'portal.wifi' }}</div>
                    <div class="fecha-hora">EMITIDO: {{ $t->created_at->format('d/m/Y H:i') }}</div>
                </div>
            </div>
        @endforeach
    </div>
</body>
</html>