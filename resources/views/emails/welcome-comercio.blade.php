<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: sans-serif; line-height: 1.6; color: #333; }
        .card { background: #f9f9f9; padding: 20px; border-radius: 8px; border: 1px solid #ddd; margin: 20px 0; }
        .label { font-weight: bold; color: #6200ea; }
        .footer { font-size: 12px; color: #777; margin-top: 30px; }
    </style>
</head>
<body>
    <h1>¡Hola, {{ $names }} {{ $surnames }}!</h1>
    
    <p>{{ $body }}</p>

    <div class="card">
        <p><span class="label">Usuario:</span> {{ $username }}</p>
        <p><span class="label">Contraseña:</span> {{ $password }}</p>
    </div>

    <p>Te invitamos a disfrutar de todos nuestros productos y servicios de conexión.</p>
     
    <p>Gracias por confiar en nosotros,</p>
    <strong>{{ config('app.name') }}</strong>

    <div class="footer">
        Este es un correo automático, por favor no responda a este mensaje.
    </div>
</body>
</html>