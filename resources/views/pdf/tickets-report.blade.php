<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Historial - {{ now()->format('d/m/Y') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: white !important; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .report-header { border-bottom: 2px solid #0d6efd; padding-bottom: 15px; margin-bottom: 25px; }
        .table { font-size: 9pt; }
        .table thead { background-color: #f8f9fa !important; }
        .badge-cost { border: 1px solid #333; padding: 2px 6px; border-radius: 4px; font-size: 8pt; font-weight: bold; }
        
        @media print {
            @page { size: portrait; margin: 1cm; }
            .no-print { display: none !important; }
            .container { max-width: 100% !important; width: 100% !important; }
            body { -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="container my-4">
        <div class="d-flex justify-content-between no-print mb-4 bg-light p-3 rounded">
            <span class="text-muted small">Vista previa de impresión. Use los controles de su navegador para guardar como PDF.</span>
            <div>
                <button onclick="window.print()" class="btn btn-primary btn-sm">Imprimir Ahora</button>
                <button onclick="window.close()" class="btn btn-secondary btn-sm">Cerrar</button>
            </div>
        </div>

        <div class="report-header d-flex justify-content-between align-items-end">
            <div>
                <h2 class="fw-bold text-primary mb-0">REPORTE DE TICKETS</h2>
                <span class="text-muted">Plataforma Administrativa PanExpres</span>
            </div>
            <div class="text-end">
                <p class="mb-0 small"><strong>Fecha:</strong> {{ now()->format('d/m/Y h:i A') }}</p>
                <p class="mb-0 small"><strong>Registros:</strong> {{ $tickets->count() }}</p>
            </div>
        </div>

        <table class="table table-bordered table-striped align-middle">
            <thead>
                <tr class="table-light">
                    <th>USUARIO</th>
                    <th>IDENTIDAD / COMENTARIO</th>
                    <th>ROUTER</th>
                    <th>PLAN / COSTO</th>
                    <th class="text-center">CONSUMO</th>
                    <th class="text-center">ESTADO</th>
                    <th class="text-end">FECHA CREACIÓN</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tickets as $t)
                @php
                    $planLower = strtolower($t->plan);
                    $isGratis = (str_contains($planLower, 'neutro') || str_contains($planLower, 'cortesia') || str_contains($planLower, 'trial'));
                    $costo = $isGratis ? 0 : 1;
                @endphp
                <tr>
                    <td class="fw-bold">{{ $t->username }}</td>
                    <td><small>{{ $t->identity }}</small></td>
                    <td>{{ $t->router->identity }}</td>
                    <td>
                        {{ $t->plan }}
                        <div class="mt-1"><span class="badge-cost">Costo: {{ $costo }}</span></div>
                    </td>
                    <td class="text-center"><code>{{ $t->tiempo_consumido ?: '0s' }}</code></td>
                    <td class="text-center text-uppercase small">
                        <strong>{{ $t->estado }}</strong>
                    </td>
                    <td class="text-end small">{{ $t->created_at->format('d/m/Y H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-5 pt-3 border-top text-center text-muted small">
            Este documento representa el historial de consumo filtrado desde el servidor central de MikroTik.
        </div>
    </div>

    <script>
        // Disparar la impresión automáticamente si se desea
        window.onload = function() {
            // setTimeout(() => { window.print(); }, 500);
        };
    </script>
</body>
</html>