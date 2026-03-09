<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WIFI EXPRES - Conexión Gratuita</title>
    <style>
        :root {
            --purple-main: #6200ea;
            --orange-accent: #ff6d00;
            --bg-light: #f4f7f6;
            --gray-text: #555;
        }

        body { 
            font-family: 'Segoe UI', Roboto, sans-serif; 
            margin: 0; padding: 0; 
            background-color: var(--bg-light);
            overflow-x: hidden;
        }

        /* --- VISTA 1: PUBLICIDAD --- */
        #view-ads {
            display: flex; /* Se muestra por defecto */
            flex-direction: column; 
            align-items: center;
            justify-content: center; 
            height: 100vh; 
            background: #000;
        }
        .ads-frame {
            width: 90%; height: 70vh; 
            background: #fff; border-radius: 15px; overflow: hidden;
            box-shadow: 0 0 20px rgba(0,0,0,0.5);
        }
        .ads-frame img { width: 100%; height: 100%; object-fit: cover; }

        /* --- VISTA 2: FORMULARIO --- */
        #view-form { 
            display: none; /* Oculto al inicio */
            padding: 20px; 
            min-height: 100vh; 
            flex-direction: column; 
            align-items: center;
            background-color: var(--bg-light);
        }
        .logo-comercio { 
            max-width: 150px; max-height: 80px; 
            object-fit: contain; margin-bottom: 10px; 
        }
        .welcome-title { 
            font-size: 26px; font-weight: 800; color: var(--purple-main); margin: 0; 
        }
        .instruction-text {
            text-align: center; color: var(--gray-text); font-size: 14px;
            margin: 15px 0; line-height: 1.4; max-width: 320px;
        }
        .logo-wifiexpres { width: 140px; margin: 10px 0; }

        .form-card {
            width: 100%; max-width: 350px; background: white;
            padding: 20px; border-radius: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            box-sizing: border-box;
        }
        .form-group { margin-bottom: 15px; }
        .form-group label { 
            display: block; font-weight: bold; font-size: 11px; 
            color: var(--purple-main); text-transform: uppercase; margin-bottom: 5px;
        }
        .input-control {
            width: 100%; padding: 12px; border: 2px solid #eee;
            border-radius: 10px; box-sizing: border-box; font-size: 16px;
            outline: none; transition: 0.3s;
        }
        .input-control:focus { border-color: var(--purple-main); }

        .terms-box {
            display: flex; align-items: flex-start; gap: 8px;
            font-size: 13px; color: var(--gray-text); margin: 15px 0;
        }
        .terms-link { color: var(--purple-main); font-weight: bold; text-decoration: underline; cursor: pointer; }

        /* --- VISTA 3: ÉXITO --- */
        #view-success {
            display: none; /* Oculto al inicio */
            height: 100vh; flex-direction: column;
            align-items: center; justify-content: center; text-align: center;
            padding: 20px;
            background-color: white;
        }
        .success-icon { 
            width: 80px; height: 80px; background: #4caf50; color: white; 
            border-radius: 50%; display: flex; align-items: center; 
            justify-content: center; font-size: 40px; margin-bottom: 20px; 
        }

        /* --- BOTONES --- */
        .btn-main {
            background-color: var(--purple-main); color: white;
            width: 100%; padding: 16px; border: none; border-radius: 50px;
            font-size: 16px; font-weight: bold; text-transform: uppercase;
            cursor: pointer; transition: 0.3s;
        }
        .btn-main:active { transform: scale(0.95); }
        .btn-main:disabled { background-color: #ccc; cursor: not-allowed; }
        
        /* --- MODAL --- */
        #modal-terms {
            position: fixed; top:0; left:0; width:100%; height:100%;
            background: rgba(0,0,0,0.8); z-index: 1000;
            display: none; justify-content: center; align-items: center;
        }
        .modal-body {
            background: white; width: 85%; max-width: 400px;
            padding: 25px; border-radius: 15px; max-height: 60vh; overflow-y: auto;
        }

        /* --- CLASE ACTIVA (Controlada por JS) --- */
        .screen-active { display: flex !important; animation: fadeIn 0.4s ease forwards; }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        @media (min-width: 768px) {
            .ads-frame { width: 400px; }
            .form-card { max-width: 400px; }
        }
    </style>
</head>
<body>

    <div id="view-ads">
        <div class="ads-frame">
            <img id="promo-img" src="banner/WIFIEXPRES_banner_01.jpg" alt="Publicidad">
        </div>
        <div style="width: 90%; max-width: 400px; margin-top: 20px;">
            <button class="btn-main" onclick="navigateTo('view-form')">Siguiente</button>
        </div>
    </div>

    <div id="view-form">
        <img id="comercio-logo" src="img/logo_placeholder.png" class="logo-comercio">
        <h1 class="welcome-title">Bienvenido</h1>
        
        <p class="instruction-text">
            Llena el formulario para disfrutar de la conexión a Internet por cortesía de:
        </p>
        
        <div class="logo-wifiexpres">
            <img src="img/logo_wifiexpres_color.png" style="width: 100%;" alt="WiFi Expres">
        </div>

        <div class="form-card">
            <div class="form-group">
                <label>Nombres y Apellidos</label>
                <input type="text" id="name" class="input-control" placeholder="Ej. Juan Pérez">
            </div>
            <div class="form-group">
                <label>Correo Electrónico</label>
                <input type="email" id="email" class="input-control" placeholder="correo@ejemplo.com">
            </div>
            <div class="form-group">
                <label>Teléfono</label>
                <input type="tel" id="phone" class="input-control" placeholder="04121234567">
            </div>

            <div class="terms-box">
                <input type="checkbox" id="terms-check" style="width:18px; height:18px;">
                <span>Acepto los <span class="terms-link" onclick="toggleModal(true)">Términos y Condiciones</span></span>
            </div>

            <button id="btn-connect" class="btn-main" onclick="processLead()">Enviar y Conectar</button>
        </div>
    </div>

    <div id="view-success">
        <div class="success-icon">✔</div>
        <h2 style="color: var(--purple-main);">¡Conexión Exitosa!</h2>
        <p style="color: var(--gray-text);">Ya puedes navegar libremente.</p>
        <div style="margin-top: 20px;">
            <p id="countdown" style="font-weight: bold; color: var(--orange-accent); font-size: 1.2rem;">Conectando en 3...</p>
        </div>
    </div>

    <div id="modal-terms" onclick="toggleModal(false)">
        <div class="modal-body" onclick="event.stopPropagation()">
            <h3 style="color: var(--purple-main);">Términos del Servicio</h3>
            <p style="font-size: 14px; line-height: 1.5; color: #444;">
                Al registrarse, usted acepta que sus datos sean tratados para fines estadísticos y promocionales por parte de WIFI EXPRES y el establecimiento. La conexión es gratuita por tiempo limitado.
            </p>
            <button class="btn-main" onclick="toggleModal(false)">Cerrar</button>
        </div>
    </div>

    <form name="sendin" action="/login" method="post" style="display:none">
        <input type="hidden" name="username" />
        <input type="hidden" name="password" />
        <input type="hidden" name="dst" value="http://www.google.com" />
    </form>

    <script>
        const routerMac = '48:A9:8A:91:CD:AE'; 
        const API_BASE = "https://wifiexpres.com/api"; 

        // 1. CARGA INICIAL
        async function init() {
            try {
                const res = await fetch(`${API_BASE}/v1/get-plans?mac=${encodeURIComponent(routerMac)}`);
                const data = await res.json();
                if(data.success && data.router) {
                    if(data.router.comercio_banner) document.getElementById('promo-img').src = data.router.comercio_banner;
                    if(data.router.comercio_logo) document.getElementById('comercio-logo').src = data.router.comercio_logo;
                }
            } catch (e) { console.error("Error cargando configuración inicial"); }
        }

        // 2. NAVEGACIÓN ENTRE VISTAS
        function navigateTo(viewId) {
            // Ocultar todas
            document.getElementById('view-ads').style.display = 'none';
            document.getElementById('view-form').style.display = 'none';
            document.getElementById('view-success').style.display = 'none';

            // Mostrar la deseada
            const target = document.getElementById(viewId);
            if(target) {
                target.style.display = 'flex';
                target.classList.add('screen-active');
            }
        }

        // 3. MODAL
        function toggleModal(show) {
            document.getElementById('modal-terms').style.display = show ? 'flex' : 'none';
        }

        // 4. REGISTRO Y CONEXIÓN FINAL
        async function processLead() {
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const checked = document.getElementById('terms-check').checked;

            if(!name || !email || !phone) { alert("Por favor, llena todos los campos."); return; }
            if(!checked) { alert("Debes aceptar los términos."); return; }

            const btn = document.getElementById('btn-connect');
            btn.disabled = true;
            btn.innerText = "PROCESANDO...";

            try {
                // Registro en base de datos (API V3)
                const response = await fetch(`${API_BASE}/v3/leads/add`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name, email, phone, router_mac: routerMac })
                });

                const result = await response.json();

                if(result.success) {
                    // Pasar a vista de éxito
                    navigateTo('view-success');

                    // Credenciales MikroTik (Usuario es el teléfono)
                    document.forms.sendin.username.value = phone;
                    document.forms.sendin.password.value = "wifi123";

                    // Contador de 3 segundos
                    let seg = 3;
                    const timer = setInterval(() => {
                        seg--;
                        document.getElementById('countdown').innerText = `Conectando en ${seg}...`;
                        if(seg <= 0) {
                            clearInterval(timer);
                            document.forms.sendin.submit(); // LOGIN MIKROTIK
                        }
                    }, 1000);
                } else {
                    alert("Error: " + result.message);
                    btn.disabled = false;
                    btn.innerText = "Enviar y Conectar";
                }
            } catch (e) {
                console.error(e);
                alert("Error de conexión con el servidor.");
                btn.disabled = false;
                btn.innerText = "Enviar y Conectar";
            }
        }

        window.onload = init;
    </script>
</body>
</html>