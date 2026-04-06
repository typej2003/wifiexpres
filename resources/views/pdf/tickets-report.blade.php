<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Tickets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: white !important; font-size: 10pt; }
        .table th { background-color: #f8f9fa !important; color: black !important; }
        @media print {
            .no-print { display: none !important; }
            @page { margin: 1cm; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold">REPORTE DE TICKETS</h2>
            <div class="text-end">
                <p class="mb-0 text-muted">Generado: {{ now()->format('d/m/Y h:i A') }}</p>
                <p class="mb-0 text-muted">Registros totales: {{ count($tickets) }}</p>
            </div>
        </div>

        <table class="table table-bordered align-middle">
            <thead>
                <tr class="text-center">
                    <th>IDENTIDAD / PIN</th>
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
                        <span class="fw-bold">{{ $t->username }}</span><br>
                        <small class="text-muted">{{ $t->identity }}</small>
                    </td>
                    <td>{{ $t->router->comercio_nombre }}
                        <br>
                        <small class="text-muted">{{ $t->router->identity }}</small>
                    </td>
                    <td class="text-center">{{ $t->plan }}</td>
                    <td class="text-center">{{ $costo }}</td>
                    <td class="text-center"><code>{{ $t->tiempo_consumido ?: '0s' }}</code></td>
                    <td class="text-center">{{ strtoupper($t->estado) }}</td>
                    <td class="text-end">{{ $t->created_at->format('d/m/Y H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4 no-print text-center">
            <button onclick="window.print()" class="btn btn-primary">Reintentar Impresión</button>
            <button onclick="window.close()" class="btn btn-secondary">Cerrar Ventana</button>
        </div>
    </div>
</body>
</html>