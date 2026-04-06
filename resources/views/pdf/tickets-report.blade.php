<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Historial - {{ now()->format('d/m/Y') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: white; font-size: 9pt; }
        .table th { background: #f2f2f2 !important; color: black !important; text-align: center; }
        @media print {
            .no-print { display: none; }
            @page { size: portrait; margin: 1cm; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="container-fluid py-4">
        <div class="text-center border-bottom pb-3 mb-4">
            <h2 class="fw-bold">REPORTE DE TICKETS</h2>
            <p class="text-muted mb-0">Generado el {{ now()->format('d/m/Y h:i A') }} | Registros: {{ count($tickets) }}</p>
        </div>

        <table class="table table-bordered align-middle">
            <thead>
                <tr>
                    <th>USUARIO / PIN</th>
                    <th>ROUTER</th>
                    <th>PLAN</th>
                    <th>COSTO</th>
                    <th>CONSUMO</th>
                    <th>ESTADO</th>
                    <th>FECHA</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tickets as $t)
                @php 
                    $pL = strtolower($t->plan);
                    $costo = (str_contains($pL, 'neutro') || str_contains($pL, 'cortesia') || str_contains($pL, 'trial')) ? 0 : 1;
                @endphp
                <tr>
                    <td>
                        <strong>{{ $t->username }}</strong>
                        @if($t->activado) <span class="small text-primary">(Act.)</span> @endif
                        <br><small>{{ $t->identity }}</small>
                    </td>
                    <td>
                        {{ $t->router->comercio_name }}
                        <br><small>{{ $t->router->identity }}</small>
                    </td>
                    <td class="text-center">{{ $t->plan }}</td>
                    <td class="text-center">{{ $costo }}</td>
                    <td class="text-center"><code>{{ $t->tiempo_consumido }}</code></td>
                    <td class="text-center">{{ strtoupper($t->estado) }}</td>
                    <td class="text-end small">{{ $t->created_at->format('d/m/Y H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>