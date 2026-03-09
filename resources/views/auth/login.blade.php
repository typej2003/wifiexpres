<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>WifiExpres - Iniciar Sesión</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        :root {
            --primary-orange: #ff572f;
            --primary-purple: #6500da;
        }

        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
        }

        .login-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            background: #fff;
        }

        .login-header {
            padding: 40px 20px 20px;
            text-align: center;
        }

        .logo-login {
            max-width: 220px;
            height: auto;
            transition: transform 0.3s ease;
        }

        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 1px solid #dee2e6;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-purple);
            box-shadow: 0 0 0 0.25rem rgba(101, 0, 218, 0.1);
        }

        label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .btn-login {
            background-color: var(--primary-orange);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: bold;
            color: white;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            background-color: #e44d2a;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 87, 47, 0.3);
            color: white;
        }

        /* ESTILO PARA EL ACCESO DE TICKETS */
        .ticket-access-section {
            background-color: #f8f0ff;
            border-radius: 12px;
            padding: 15px;
            border: 1px dashed var(--primary-purple);
            margin-top: 25px;
        }

        .btn-ticket {
            background-color: var(--primary-purple);
            color: white;
            border-radius: 10px;
            padding: 10px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            width: 100%;
            transition: 0.3s;
        }

        .btn-ticket:hover {
            background-color: #5200b3;
            color: white;
            box-shadow: 0 4px 10px rgba(101, 0, 218, 0.2);
        }

        .register-link {
            color: var(--primary-purple);
            text-decoration: none;
            font-weight: 700;
        }

        .top-bar {
            height: 5px;
            background: linear-gradient(to right, var(--primary-orange), var(--primary-purple));
            width: 100%;
        }

        .input-group-text {
            background: transparent;
            border-radius: 0 10px 10px 0;
            border-left: none;
            cursor: pointer;
            color: #6c757d;
        }

        .password-input {
            border-right: none;
            border-radius: 10px 0 0 10px !important;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-md-6 col-lg-5">
            <div class="card login-card">
                <div class="top-bar"></div>
                <div class="card-body p-4 p-md-5">
                    
                    <div class="login-header">
                        <a href="/">
                            <img class="logo-login mb-4" src="/img/logo-wifiexpres.png" alt="WifiExpres" onerror="this.src='https://placehold.co/220x60?text=WifiXpress'">
                        </a>
                        <p class="text-muted small">¿Eres Aliado o Administrador?<br>
                            Ingresa tus credenciales abajo.
                        </p>
                    </div>

                    <form action="{{ route('autenticar') }}" method="POST">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="email"><i class="bi bi-envelope me-2"></i>Correo Electrónico</label>
                            <input type="email" name="email" class="form-control" placeholder="nombre@ejemplo.com" id="email" required>
                        </div>

                        <div class="mb-4">
                            <label for="password"><i class="bi bi-lock me-2"></i>Contraseña</label>
                            <div class="input-group">
                                <input type="password" name="password" id="password" value="12345678" class="form-control password-input" placeholder="••••••••" required>
                                <span class="input-group-text" id="togglePassword">
                                    <i class="bi bi-eye" id="eyeIcon"></i>
                                </span>
                            </div>
                        </div>

                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-login">
                                <i class="bi bi-box-arrow-in-right me-2"></i> Iniciar Sesión
                            </button>
                        </div>

                        <div class="text-center">
                            <a href="{{ route('password.request') }}" class="forgot-password text-muted small text-decoration-none">¿Olvidaste tu contraseña?</a>
                        </div>
                    </form>

                    <div class="ticket-access-section text-center">
                        <p class="small mb-2 fw-bold" style="color: var(--primary-purple);">
                            <i class="bi bi-ticket-perforated me-1"></i> ¿TIENES UN TICKET?
                        </p>
                        <a href="{{ route('ticket.login') }}" class="btn-ticket">
                            CONSULTAR MI CONSUMO
                        </a>
                        <p class="mt-2 mb-0" style="font-size: 0.75rem; color: #777;">
                            Usa tu usuario y clave del ticket para entrar.
                        </p>
                    </div>

                    <div class="text-center mt-4">
                        <p class="text-muted small">¿No tienes cuenta? <a href="/register" class="register-link">Regístrate</a></p>
                    </div>
                </div>
            </div>
            
            <p class="text-center mt-4 text-muted small">
                &copy; {{ date('Y') }} DDR SISTEMAS C.A. - Todos los derechos reservados.
            </p>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const togglePassword = document.querySelector('#togglePassword');
        const passwordInput = document.querySelector('#password');
        const eyeIcon = document.querySelector('#eyeIcon');

        togglePassword.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            eyeIcon.classList.toggle('bi-eye');
            eyeIcon.classList.toggle('bi-eye-slash');
        });
    });
</script>

</body>
</html>