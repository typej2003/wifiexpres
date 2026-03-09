<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1 text-align: center;">
    <title>Recuperar Contraseña - WifiExpres</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --primary-orange: #ff572f; --primary-purple: #6500da; }
        body { background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: none; }
        .btn-login { background-color: var(--primary-orange); color: white; border-radius: 10px; font-weight: bold; }
        .btn-login:hover {background-color: #ce4522ff; color: #ffff;}
        .top-bar { height: 5px; background: linear-gradient(to right, var(--primary-orange), var(--primary-purple)); border-radius: 20px 20px 0 0; }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card login-card">
                <div class="top-bar"></div>
                <div class="card-body p-4 p-md-5">
                    <h4 class="text-center fw-bold mb-4">Recuperar Contraseña</h4>
                    <p class="text-muted small text-center mb-4">Ingresa tu correo y te enviaremos un enlace para que cambies tu contraseña.</p>

                    @if (session('status'))
                        <div class="alert alert-success small">{{ session('status') }}</div>
                    @endif

                    <form action="{{ route('password.email') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Correo Electrónico</label>
                            <input type="email" name="email" class="form-control" required placeholder="ejemplo@correo.com">
                            @error('email') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-login py-2">Enviar Enlace</button>
                        </div>
                        <div class="text-center mt-3">
                            <a href="/login" class="text-muted small text-decoration-none">Volver al inicio de sesión</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>