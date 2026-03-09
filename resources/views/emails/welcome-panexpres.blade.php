<!DOCTYPE html>
<html>
<head>
    <title>{{ $title }}</title>
</head>
<body>
    <h1>Hola, {{ $names }} {{ $surnames }}</h1>
    <p>{{ $body }}</p>
    <p>Te invitamos a disfrutar todos nuestros productos</p>
     
    <p>Gracias,</p>
    {{ config('app.name') }}<br>
</body>
</html>