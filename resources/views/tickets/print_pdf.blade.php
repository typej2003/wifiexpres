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
        .creds-table { margin: 0 auto; width: 85%; font-size: 11px; font-weight: bold; }
        .plan-box { background: #000; color: #fff; font-size: 9px; padding: 2px; margin: 5px 0; font-weight: bold; }
        .precio { font-size: 14px; font-weight: bold; }
        .qr svg { width: 64px; height: 64px; margin: 5px 0; }
        .footer { font-size: 7px; }
        @media print { .no-print { display: none; } body { background: #fff; } .ticket { border: 0.5pt solid #000; } }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" style="padding: 10px 30px; cursor:pointer;">IMPRIMIR AHORA</button>
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
                    <tr><td>PIN:</td><td>{{ $t->username }}</td></tr>
                    @if($t->password != $t->username)
                        <tr><td>KEY:</td><td>{{ $t->password }}</td></tr>
                    @endif
                </table>
                <div class="plan-box">{{ strtoupper($t->plan) }}</div>
                <div class="precio">${{ number_format($t->costo, 2) }}</div>
                <div class="qr">
                    {!! QrCode::size(64)->margin(0)->generate("http://".$router->hotspot_url."/login?username=".$t->username."&password=".$t->password) !!}
                </div>
                <div class="footer">{{ $t->created_at->format('d/m/y H:i') }}</div>
                <div class="footer" style="font-weight:bold;">{{ $router->hotspot_url ?? 'portal.wifi' }}</div>
            </div>
        @endforeach
    </div>
</body>
</html>