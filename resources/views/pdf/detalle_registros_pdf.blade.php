<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Conexiones Detallado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-size: 8pt; background: white; }
        .table th { background: #f2f2f2 !important; text-align: center; font-size: 7pt; }
        @media print { .no-print { display: none; } @page { size: landscape; margin: 0.5cm; } }
    </style>
</head>
<body onload="window.print()">
    <div class="container-fluid py-3">
        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
            <div>
                <h4 class="fw-bold mb-0">REPORTE DETALLADO DE CONEXIONES</h4>
                <p class="text-muted mb-0 small">Periodo: {{ $desde }} al {{ $hasta }} | Aliado: {{ $user->name }}</p>
            </div>
            <div class="text-end small">
                Generado: {{ now()->format('d/m/Y H:i A') }}
            </div>
        </div>

        <table class="table table-bordered table-sm align-middle">
            <thead>
                <tr>
                    <th>USUARIO</th>
                    <th>CLIENTE</th>
                    <th>GEN.</th>
                    <th>EDAD</th>
                    <th>CONTACTO</th>
                    <th>PERFIL</th>
                    <th>ROUTER</th>
                    <th>FECHA/HORA</th>
                    <th>DURACIÓN</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tableData as $log)
                <tr>
                    <td class="fw-bold">{{ $log->username }}</td>
                    <td>{{ $log->client_name ?: 'N/A' }}<br><small class="text-muted">{{ $log->client_email }}</small></td>
                    <td class="text-center">{{ $log->gender }}</td>
                    <td class="text-center">{{ $log->birthday ? \Carbon\Carbon::parse($log->birthday)->age : '-' }}</td>
                    <td>{{ $log->cellphonecode }} {{ $log->cellphone }}<br><small class="text-truncate d-inline-block" style="max-width: 120px;">{{ $log->address }}</small></td>
                    <td class="text-center small">{{ $log->client_profile }}</td>
                    <td class="text-center small">{{ $log->router->identity ?? 'Router' }}</td>
                    <td class="text-center small">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                    <td class="text-center fw-bold">{{ $log->duracion_formateada }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>
