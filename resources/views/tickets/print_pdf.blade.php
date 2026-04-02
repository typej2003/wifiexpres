<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Impresión de Tickets</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        @page { margin: 0.4cm; }
        body { font-family: 'Helvetica', Arial, sans-serif; margin: 0; background: #f4f4f4; }
        .no-print { background: #333; color: #fff; padding: 20px; text-align: center; }
        .container { width: 100%; display: block; background: #fff; padding: 10px; }
        
        .ticket { 
            width: 18.2%; height: 8.8cm; border: 0.5pt solid #444; margin: 2px; 
            display: inline-block; vertical-align: top; text-align: center; 
            padding: 5px; box-sizing: border-box; overflow: hidden; 
        }

        .logo-img { max-height: 42px; max-width: 95%; margin-bottom: 5px; }
        .comercio-nombre { font-size: 9px; font-weight: bold; text-transform: uppercase; border-bottom: 0.5pt solid #eee; margin-bottom: 3px; }
        .ticket-id { color: #666; font-size: 8px; margin: 2px 0; display: block; }
        
        /* Estilos de Credenciales Actualizados */
        .creds-table { 
            margin-top: 5px;
            width: 100%; 
            border-collapse: collapse;
        }
        .creds-table td { 
            text-align: left; /* Alineado a la izquierda */
            padding: 1px 2px;
            vertical-align: bottom;
        }
        .label-text { 
            font-size: 7px; /* Etiqueta disminuida */
            color: #555;
            font-weight: normal;
            text-transform: uppercase;
            width: 30%;
        }
        .value-text { 
            font-size: 15px; /* Valor ampliado */
            font-weight: bold;
            font-family: 'Courier New', Courier, monospace; /* Fuente mono para legibilidad */
        }

        .plan-box { background: #000; color: #fff; font-size: 10px; padding: 3px; margin: 8px 0; font-weight: bold; }
        .precio { font-size: 16px; font-weight: bold; margin-bottom: 5px; }
        
        /* QR Oculto */
        .qr { display: none; } 

        .footer { font-size: 7px; line-height: 1.2; }
        
        @media print { 
            .no-print { display: none; } 
            body { background: #fff; } 
            .ticket { border: 0.5pt solid #000; } 
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" style="padding: 10px 30px; cursor:pointer; font-weight: bold;">IMPRIMIR AHORA</button>
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
                <span class="ticket-id"># {{ $t->identity }}</span>
                
                <table class="creds-table">
                    <tr>
                        <td class="label-text">USUARIO:</td>
                    </tr>
                    <tr>
                        <td class="value-text">{{ $t->username }}</td>
                    </tr>
                    @if($t->password != $t->username)
                        <tr>
                            <td class="label-text">CONTRASEÑA:</td>
                        </tr>
                        <tr>
                            <td class="value-text">{{ $t->password }}</td>
                        </tr>
                    @endif
                </table>

                <div class="plan-box">{{ strtoupper($t->plan) }}</div>
                <div class="precio">{{ number_format($t->costo, 2) }} BS</div>
                
                <div class="footer">{{ $t->created_at->format('d/m/y H:i') }}</div>
                <div class="footer" style="font-weight:bold;">{{ $router->hotspot_url ?? 'portal.wifi' }}</div>
            </div>
        @endforeach
    </div>
</body>
</html>