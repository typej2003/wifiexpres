<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>WifiExpres | Registro de Usuario</title>

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
            font-family: 'Segoe UI', sans-serif;
            padding: 40px 0;
        }

        .register-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            background: #fff;
            max-width: 600px;
            width: 90%;
        }

        .top-bar {
            height: 6px;
            background: linear-gradient(to right, var(--primary-orange), var(--primary-purple));
            border-radius: 20px 20px 0 0;
        }

        .logo-register {
            max-width: 180px;
            margin-bottom: 1rem;
        }

        .textoreg {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .login-link {
            color: var(--primary-purple);
            font-weight: 700;
            text-decoration: none;
        }

        .form-label {
            font-weight: 600;
            font-size: 0.85rem;
            color: #495057;
            margin-bottom: 5px;
        }

        .form-control, .form-select {
            border-radius: 10px;
            padding: 10px 15px;
            border: 1px solid #dee2e6;
            font-size: 0.95rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-purple);
            box-shadow: 0 0 0 0.25rem rgba(101, 0, 218, 0.1);
            z-index: 3;
        }

        .btn-register {
            background-color: var(--primary-orange);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-register:hover {
            background-color: #e44d2a;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 87, 47, 0.3);
            color: white;
        }

        .input-group-text {
            background-color: #f8f9fa;
            border-radius: 0 10px 10px 0 !important;
            border-left: none;
            color: #6c757d;
            cursor: pointer;
            transition: all 0.2s;
        }

        .input-group-text:hover {
            color: var(--primary-purple);
            background-color: #f1f1f1;
        }

        .has-icon .form-control {
            border-right: none;
            border-radius: 10px 0 0 10px !important;
        }
        
        .section-title {
            color: var(--primary-purple);
            font-weight: 800;
            margin-bottom: 1.5rem;
            position: relative;
            display: inline-block;
        }
    </style>
</head>
<body>

<div class="card register-card">
    <div class="top-bar"></div>
    <div class="card-body p-4 p-md-5">
        
        <div class="text-center mb-4">
            <a href="/">
                <img src="/img/logo-wifiexpres.png" class="logo-register" alt="WifiExpres" onerror="this.src='https://placehold.co/180x50?text=WifiXpress'">
            </a>
            <p class="textoreg">¿Ya tienes una cuenta? <a href="/login" class="login-link">Click aquí</a></p>
            <h2 class="section-title">Crear Registro</h2>
        </div>

        <form action="{{ route('register') }}" method="post">           
            @csrf
            
            <input type="hidden" value="afiliado" id="role" name="role">

            <div class="row g-3 mb-3">
                <div class="col-4 col-md-3">
                    <label class="form-label">Tipo <span class="text-danger">*</span></label>
                    <select name="identificationNac" class="form-select">
                        <option value="V" selected>V-</option>
                        <option value="J">J-</option>
                        <option value="E">E-</option>
                        <option value="G">G-</option>
                        <option value="P">P-</option>
                    </select>
                </div>
                <div class="col-8 col-md-9">
                    <label class="form-label">Cédula o RIF <span class="text-danger">*</span></label>
                    <input type="text" name="identificationNumber" class="form-control" placeholder="12345678" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Nombre de Usuario <span class="text-danger">*</span></label>
                <div class="input-group has-icon">
                    <input type="text" name="name" class="form-control" placeholder="Ej: Juan99" required>
                    <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
                </div>
                @error('name') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                    <input type="text" name="names" class="form-control" placeholder="Nombre" required>
                    @error('names') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Apellido <span class="text-danger">*</span></label>
                    <input type="text" name="surnames" class="form-control" placeholder="Apellido" required>
                    @error('surnames') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Correo Electrónico <span class="text-danger">*</span></label>
                <div class="input-group has-icon">
                    <input type="email" name="email" class="form-control" placeholder="correo@ejemplo.com" required>
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                </div>
                @error('email') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            <div class="row g-3 mb-3">
                <div class="col-5 col-md-4">
                    <label class="form-label">Cód. Área</label>
                    <select class="form-select" name="cellphonecode">
                        <option value="0412">0412</option>
                        <option value="0414">0414</option>
                        <option value="0424">0424</option>
                        <option value="0416">0416</option>
                        <option value="0426">0426</option>
                    </select>
                </div>
                <div class="col-7 col-md-8">
                    <label class="form-label">Teléfono</label>
                    <div class="input-group has-icon">
                        <input type="text" class="form-control" name="cellphone" placeholder="7654321">
                        <span class="input-group-text"><i class="bi bi-phone"></i></span>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">Contraseña <span class="text-danger">*</span></label>
                    <div class="input-group has-icon">
                        <input type="password" name="password" id="password" class="form-control" placeholder="********" required>
                        <span class="input-group-text toggle-password" data-target="password">
                            <i class="bi bi-eye"></i>
                        </span>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Confirmar <span class="text-danger">*</span></label>
                    <div class="input-group has-icon">
                        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="********" required>
                        <span class="input-group-text toggle-password" data-target="password_confirmation">
                            <i class="bi bi-eye"></i>
                        </span>
                    </div>
                </div>
                @error('password') <div class="col-12"><small class="text-danger">{{ $message }}</small></div> @enderror
            </div>

            <div class="text-center mt-4">
                <button type="submit" class="btn btn-register">
                    <i class="bi bi-person-plus-fill me-2"></i> Crear Cuenta
                </button>
            </div>
        </form>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const toggleButtons = document.querySelectorAll('.toggle-password');

        toggleButtons.forEach(button => {
            button.addEventListener('click', function () {
                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);
                const icon = this.querySelector('i');

                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.replace('bi-eye', 'bi-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.replace('bi-eye-slash', 'bi-eye');
                }
            });
        });
    });
</script>

</body>
</html>