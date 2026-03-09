<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WIFI EXPRES - Acceso Ticket</title>
    
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

        .top-bar {
            height: 5px;
            background: linear-gradient(to right, var(--primary-purple), var(--primary-orange));
            width: 100%;
        }

        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
        }

        .form-control:focus {
            border-color: var(--primary-purple);
            box-shadow: 0 0 0 0.25rem rgba(101, 0, 218, 0.1);
        }

        .btn-ticket-login {
            background-color: var(--primary-purple);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: bold;
            color: white;
            text-transform: uppercase;
            transition: 0.3s;
        }

        .btn-ticket-login:hover {
            background-color: #5200b3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(101, 0, 218, 0.3);
            color: white;
        }

        /* Misma lógica de input group que el login principal */
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
                    
                    <div class="text-center mb-4">
                        <i class="bi bi-ticket-perforated text-primary" style="font-size: 3rem; color: var(--primary-purple) !important;"></i>
                        <h4 class="fw-bold mt-2">Consultar mi Ticket</h4>
                        <p class="text-muted small">Ingresa las credenciales que aparecen en tu ticket físico.</p>
                    </div>

                    @if(session('error'))
                        <div class="alert alert-danger small py-2">{{ session('error') }}</div>
                    @endif

                    <form action="{{ route('ticket.auth.check') }}" method="POST">
                        @csrf
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase">Usuario del Ticket</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" name="username" class="form-control" placeholder="Ej: 10001" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted text-uppercase">Contraseña</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-key"></i></span>
                                <input type="password" name="password" id="password" class="form-control password-input" placeholder="••••••••" required>
                                <span class="input-group-text" id="togglePassword">
                                    <i class="bi bi-eye" id="eyeIcon"></i>
                                </span>
                            </div>
                        </div>

                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-ticket-login">
                                <i class="bi bi-search me-2"></i> Consultar Consumo
                            </button>
                        </div>

                        <div class="text-center mt-4">
                            <a href="/" class="text-decoration-none small text-muted">
                                <i class="bi bi-arrow-left me-1"></i> Volver al inicio
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const togglePassword = document.querySelector('#togglePassword');
        const passwordInput = document.querySelector('#password');
        const eyeIcon = document.querySelector('#eyeIcon');

        togglePassword.addEventListener('click', function () {
            // Misma lógica exacta que tu login principal
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            eyeIcon.classList.toggle('bi-eye');
            eyeIcon.classList.toggle('bi-eye-slash');
        });
    });
</script>

</body>
</html>