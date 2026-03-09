<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WIFI EXPRES - Mi Cuenta</title>
    <style>
        :root {
            --purple-main: #6200ea;
            --orange-accent: #ff6d00;
            --bg-light: #f4f7f6;
        }

        body { 
            font-family: 'Segoe UI', Roboto, sans-serif; 
            margin: 0; background-color: var(--bg-light);
            display: flex; justify-content: center; align-items: center; min-height: 100vh;
        }

        .auth-card {
            background: white; width: 90%; max-width: 400px;
            padding: 30px; border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .header-auth { text-align: center; margin-bottom: 25px; }
        .logo-text { font-size: 28px; font-weight: 800; font-style: italic; color: var(--purple-main); }
        .logo-text span { color: var(--orange-accent); }

        .tabs { display: flex; margin-bottom: 20px; border-bottom: 2px solid #eee; }
        .tab { 
            flex: 1; text-align: center; padding: 10px; cursor: pointer; 
            font-weight: bold; color: #888; transition: 0.3s;
        }
        .tab.active { color: var(--purple-main); border-bottom: 3px solid var(--purple-main); }

        .form-group { margin-bottom: 15px; position: relative; }
        .form-group label { display: block; font-size: 13px; font-weight: bold; color: #555; margin-bottom: 5px; }
        
        .input-field {
            width: 100%; padding: 12px; border: 2px solid #e0e0e0;
            border-radius: 10px; box-sizing: border-box; outline: none; transition: 0.3s;
        }
        .input-field:focus { border-color: var(--purple-main); }

        /* Estilo para el ojo de contraseña */
        .password-wrapper { position: relative; }
        .toggle-icon {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            width: 20px; height: 20px; cursor: pointer; opacity: 0.5;
        }

        .btn-auth {
            width: 100%; padding: 14px; background: var(--purple-main);
            color: white; border: none; border-radius: 50px;
            font-weight: bold; font-size: 16px; cursor: pointer; margin-top: 10px;
        }
        .btn-auth:hover { background: #4b00b4; }

        .screen-auth { display: none; }
        .screen-auth.active { display: block; animation: fadeIn 0.4s; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(10s); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

    <div class="auth-card">
        <div class="header-auth">
            <div class="logo-text">WIFI<span>EXPRES</span></div>
            <p style="font-size: 14px; color: #666;">Gestiona tu navegación</p>
        </div>

        <div class="tabs">
            <div class="tab active" onclick="switchTab('login')">Entrar</div>
            <div class="tab" onclick="switchTab('register')">Registrarse</div>
        </div>

        <div id="login-form" class="screen-auth active">
            <div class="form-group">
                <label>Usuario (Ticket o Email)</label>
                <input type="text" id="login-user" class="input-field" placeholder="Ej: 10001">
            </div>
            <div class="form-group">
                <label>Contraseña</label>
                <div class="password-wrapper">
                    <input type="password" id="login-pass" class="input-field" placeholder="******">
                    <img src="img/ojo-cerrado.svg" class="toggle-icon" onclick="togglePass('login-pass', this)">
                </div>
            </div>
            <button class="btn-auth" onclick="processLogin()">INGRESAR</button>
        </div>

        <div id="register-form" class="screen-auth">
            <div style="display: flex; gap: 10px;">
                <div class="form-group">
                    <label>Nombres</label>
                    <input type="text" class="input-field" placeholder="Juan">
                </div>
                <div class="form-group">
                    <label>Apellidos</label>
                    <input type="text" class="input-field" placeholder="Pérez">
                </div>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" class="input-field" placeholder="juan@correo.com">
            </div>
            <div class="form-group">
                <label>Teléfono</label>
                <input type="tel" class="input-field" placeholder="04121234567">
            </div>
            <div class="form-group">
                <label>Crea una Contraseña</label>
                <div class="password-wrapper">
                    <input type="password" id="reg-pass" class="input-field" placeholder="Mín. 6 caracteres">
                    <img src="img/ojo-cerrado.svg" class="toggle-icon" onclick="togglePass('reg-pass', this)">
                </div>
            </div>
            <button class="btn-auth" style="background: var(--orange-accent);" onclick="processRegister()">CREAR CUENTA</button>
        </div>
    </div>

    <script>
        function switchTab(type) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.screen-auth').forEach(s => s.classList.remove('active'));
            
            if(type === 'login') {
                document.querySelectorAll('.tab')[0].classList.add('active');
                document.getElementById('login-form').classList.add('active');
            } else {
                document.querySelectorAll('.tab')[1].classList.add('active');
                document.getElementById('register-form').classList.add('active');
            }
        }

        function togglePass(id, el) {
            const input = document.getElementById(id);
            if(input.type === 'password') {
                input.type = 'text';
                el.src = 'img/ojo-abierto.svg';
            } else {
                input.type = 'password';
                el.src = 'img/ojo-cerrado.svg';
            }
        }

        function processLogin() {
            // Aquí iría la validación contra tu API
            alert("Validando credenciales...");
        }

        function processRegister() {
            // Aquí iría el POST a tu base de datos de usuarios
            alert("Registrando nuevo cliente...");
        }
    </script>
</body>
</html>