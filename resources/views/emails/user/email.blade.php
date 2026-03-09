<p>
<img src="https://panexpres.com/logo_email.png" alt="PanExpres"><br>
</p>

<h1>Solicitud de Cambio de Contraseña</h1>

<p>
    Has recibido este mensaje porque has solicitado para cambiar la contraseña 
</p>

<p>
    Haz <a href="{{ route('clave', $token ) }}/{{ $correo }}">Click Aqui</a> o copia y pega el siguiente enlace en el navegador:
</p>

<p>
    <a href="{{ route('clave', $token) }}/{{ $correo }}">{{ route('clave', $token ) }}/{{ $correo }}</a>
</p>

<p>
    <strong>Pan Express</strong>
</p>
