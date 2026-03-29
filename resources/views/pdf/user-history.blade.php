<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Conexiones</title>
    <style>
        @page {
            margin: 1.5cm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.5;
        }
        .header {
            width: 100%;
            margin-bottom: 25px;
            border-bottom: 1px solid #333;
            padding-bottom: 15px;
        }
        .header table {
            width: 100%;
        }
        .main-title {
            font-size: 18px;
            font-weight: bold;
            color: #000;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .report-info {
            text-align: right;
            font-size: 11px;
            color: #555;
        }
        .info-bar {
            background-color: #fcfcfc;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 4px;
            border: 1px solid #eee;
        }
        .info-bar table {
            width: 100%;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .table th {
            background-color: #f2f2f2;
            color: #000;
            text-align: left;
            padding: 10px 8px;
            text-transform: uppercase;
            font-size: 9px;
            border-bottom: 2px solid #333;
        }
        .table td {
            padding: 10px 8px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        .table tr:nth-child(even) {
            background-color: #fafafa;
        }
        .text-end {
            text-align: right;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            font-size: 9px;
            text-align: center;
            color: #777;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
        .badge {
            display: inline-block;
            padding: 2px 5px;
            background-color: #eee;
            color: #333;
            border-radius: 3px;
            font-size: 9px;
        }
        .mac {
            font-family: 'Courier', monospace;
            font-size: 10px;
        }
    </style>
</head>
<body>

    <div class="header">
        <table>
            <tr>
                <td>
                    <div class="main-title">Sistema de Gestión de Tickets WiFi</div>
                </td>
                <td class="report-info">
                    <strong>Reporte de Historial</strong><br>
                    {{ now()->format('d/m/Y h:i A') }}
                </td>
            </tr>
        </table>
    </div>

    <div class="info-bar">
        <table>
            <tr>
                <td>
                    <strong>Generado por:</strong> {{ $user->name }}<br>
                    <strong>Identificador:</strong> {{ $user->email }}
                </td>
                <td class="text-end">
                    <strong>Periodo de consulta:</strong> <br>
                    {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}
                </td>
            </tr>
        </table>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Ticket / Usuario</th>
                <th>Nodo Emisor</th>
                <th>Ubicación</th>
                <th>Fecha / Hora</th>
                <th>Tiempo de Uso</th>
                <th class="text-end">Dirección MAC</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
            <tr>
                <td><strong>{{ $log->username }}</strong></td>
                <td>{{ $log->router->identity ?? 'Router' }}</td>
                <td>{{ $log->ubicacion_fisica }}</td>
                <td>
                    {{ $log->created_at->format('d/m/Y') }}<br>
                    <small style="color: #888;">{{ $log->created_at->format('h:i:s A') }}</small>
                </td>
                <td>
                    <span class="badge">{{ $log->duracion_formateada }}</span>
                </td>
                <td class="text-end">
                    <span class="mac">{{ $log->mac_address }}</span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center; padding: 40px; color: #999;">
                    No se han registrado conexiones en el periodo seleccionado.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Documento generado automáticamente por el Sistema de Gestión de Tickets WiFi &copy; {{ date('Y') }}
    </div>

</body>
</html>