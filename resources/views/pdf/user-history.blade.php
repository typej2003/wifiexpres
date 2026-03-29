<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Conexiones - Generico</title>
    <style>
        @page {
            margin: 1cm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
        }
        .header {
            width: 100%;
            margin-bottom: 20px;
            border-bottom: 2px solid #444;
            padding-bottom: 10px;
        }
        .header table {
            width: 100%;
        }
        .brand {
            font-size: 24px;
            font-weight: bold;
            color: #222;
            text-transform: uppercase;
        }
        .report-title {
            text-align: right;
            font-size: 14px;
            color: #666;
        }
        .info-bar {
            background-color: #f8f9fa;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            border: 1px solid #eee;
        }
        .info-bar table {
            width: 100%;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table th {
            background-color: #444;
            color: white;
            text-align: left;
            padding: 8px;
            text-transform: uppercase;
            font-size: 10px;
        }
        .table td {
            padding: 8px;
            border-bottom: 1px solid #dee2e6;
        }
        .table tr:nth-child(even) {
            background-color: #f2f2f2;
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
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 5px;
        }
        .badge {
            padding: 3px 6px;
            border-radius: 10px;
            font-size: 9px;
            background-color: #e9ecef;
            color: #495057;
            border: 1px solid #ced4da;
        }
        .mac {
            font-family: 'Courier', monospace;
            color: #333;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <div class="header">
        <table>
            <tr>
                <td>
                    <div class="brand">PANEXPRES</div>
                    <div style="font-size: 10px; color: #666;">Sistema de Gestión de Tickets WiFi</div>
                </td>
                <td class="report-title">
                    <strong>Reporte de Historial de Conexiones</strong><br>
                    Generado el: {{ now()->format('d/m/Y h:i A') }}
                </td>
            </tr>
        </table>
    </div>

    <div class="info-bar">
        <table>
            <tr>
                <td>
                    <strong>Aliado:</strong> {{ $user->name }}<br>
                    <strong>Email:</strong> {{ $user->email }}
                </td>
                <td class="text-end">
                    <strong>Rango de Fechas:</strong> <br>
                    {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} hasta {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}
                </td>
            </tr>
        </table>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Usuario / Ticket</th>
                <th>Router / Nodo</th>
                <th>Ubicación Física</th>
                <th>Fecha / Hora</th>
                <th>Duración</th>
                <th class="text-end">MAC Address</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
            <tr>
                <td><strong>{{ $log->username }}</strong></td>
                <td>{{ $log->router->identity ?? 'MikroTik' }}</td>
                <td>{{ $log->ubicacion_fisica }}</td>
                <td>
                    {{ $log->created_at->format('d/m/Y') }}<br>
                    <small style="color: #666;">{{ $log->created_at->format('h:i:s A') }}</small>
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
                <td colspan="6" style="text-align: center; padding: 20px;">No se encontraron registros en el rango seleccionado.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Este documento es un reporte automático generado por la plataforma PanExpres.com - &copy; {{ date('Y') }}
    </div>

</body>
</html>