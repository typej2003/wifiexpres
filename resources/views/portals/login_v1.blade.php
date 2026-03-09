<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WIFI EXPRES - Acceso</title>
    <style>
        :root {
            --purple-main: #6200ea;
            --orange-accent: #ff6d00;
            --bg-light: #f4f7f6;
            --gray-text: #888;
        }

        body { 
            font-family: 'Segoe UI', Roboto, sans-serif; 
            margin: 0; padding: 0; 
            background-color: var(--bg-light);
            display: flex; flex-direction: column; min-height: 100vh;
        }

        /* --- MODAL CON CARRUSEL --- */
        #ads-modal { 
            position: fixed; top:0; left:0; width:100%; height:100%; 
            background:rgba(0,0,0,0.9); z-index:2000; 
            display:none; justify-content:center; align-items:center; 
        }
        .ads-content { 
            width:90%; max-width:400px; background:white; 
            border-radius:20px; overflow:hidden; position:relative; 
        }
        
        .carousel-container { width: 100%; height: 250px; overflow: hidden; position: relative; }
        .carousel-slide { 
            display: none; width: 100%; height: 100%; 
            object-fit: cover; animation: fadeEffect 0.8s;
        }
        .carousel-slide.active { display: block; }

        @keyframes fadeEffect { from {opacity: 0.4} to {opacity: 1} }

        #close-ads { 
            position:absolute; top:10px; right:10px; 
            background: var(--orange-accent); 
            color: white; width: 35px; height: 35px; border-radius: 50%; 
            display: none; justify-content: center; align-items: center; 
            cursor: pointer; font-weight: bold; border: 2px solid white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
            z-index: 2001;
        }

        /* --- DISEÑO GENERAL --- */
        .header {
            background-color: var(--purple-main);
            color: white; padding: 12px 20px; text-align: center;
            border-bottom-left-radius: 20px; border-bottom-right-radius: 20px;
            z-index: 30; position: relative;
        }
        .logo-text { font-size: 24px; font-weight: 800; font-style: italic; margin: 0; }
        .logo-text span { color: var(--orange-accent); }
        .welcome-msg { font-size: 11px; letter-spacing: 1px; margin-top: 2px; text-transform: uppercase; }

        .hero-banner {
            width: 100%; height: 220px;
            background: url('banner/WIFIEXPRES_banner_01.jpg') center/cover no-repeat;
            margin-top: -10px;
            transition: all 0.3s ease;
        }

        .content {
            padding: 25px; flex-grow: 1; margin-top: -30px;
            background: white; border-top-left-radius: 30px; border-top-right-radius: 30px;
            box-shadow: 0 -5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        /* --- AJUSTE MODO ESCRITORIO --- */
        @media (min-width: 768px) {
            .hero-banner {
                height: 520px; 
                margin-top: 2px; 
                border-radius: 25px;
                width: 96%;
                margin-left: auto;
                margin-right: auto;
                box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            }
            .content {
                width: 60%; 
                max-width: 1200px;
                min-width: 600px; 
                margin: 5px auto 40px auto; 
                border-radius: 25px;
                box-shadow: 0 20px 50px rgba(0,0,0,0.2);
                padding: 40px;
                position: relative;
                z-index: 20;
            }
            .footer-actions {
                width: 60%;
                max-width: 800px;
                min-width: 500px;
                margin: 0 auto 40px auto;
            }
        }

        /* --- AJUSTE INPUTS FORMULARIO --- */
        .manual-input {
            width: 100%;
            padding: 18px 15px; 
            border: 3px solid #e0e0e0; 
            border-radius: 15px;
            box-sizing: border-box;
            font-size: 22px; 
            font-weight: 800; 
            color: #1a1a1a; 
            outline: none;
            transition: all 0.3s ease;
            background-color: #fafafa;
        }
        .manual-input:focus {
            border-color: var(--purple-main);
            background-color: #fff;
            box-shadow: 0 0 10px rgba(98, 0, 234, 0.1);
        }

        .screen { display: none; animation: fadeIn 0.3s ease; }
        .screen.active { display: block; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .option-card {
            display: flex; align-items: center; padding: 18px; border: 2px solid #e0e0e0;
            border-radius: 18px; margin-bottom: 15px; cursor: pointer;
            transition: all 0.2s ease;
        }
        .option-card:hover { border-color: var(--purple-main); transform: translateY(-2px); }
        .option-card.selected { border-color: var(--purple-main); background-color: #f6f0ff; }
        .option-card.hidden { display: none; }

        .icon-wrapper { width: 50px; height: 50px; margin-right: 20px; display: flex; align-items: center; justify-content: center; }
        .icon-img { width: 100%; height: 100%; object-fit: contain; }
        
        .time-icon-svg { width: 32px; height: 32px; fill: var(--purple-main); }

        .title-text { font-weight: bold; color: var(--purple-main); font-size: 18px; }
        .direct-access { text-align: center; margin-top: 20px; font-size: 15px; color: #666; }
        .direct-access span { color: var(--purple-main); font-weight: bold; text-decoration: underline; cursor: pointer; }

        #iframe-container { width: 100%; height: 550px; border: none; border-radius: 15px; overflow: hidden; margin-top: 10px; background: #f9f9f9; }
        iframe { width: 100%; height: 100%; border: none; }

        .footer-actions { padding: 15px 25px; text-align: center; }
        .btn-main { background-color: var(--purple-main); color: white; width: 100%; padding: 16px; border: none; border-radius: 50px; font-size: 16px; font-weight: bold; text-transform: uppercase; cursor: pointer; transition: all 0.2s; }
        .btn-main:hover { background-color: #5000ca; box-shadow: 0 5px 15px rgba(98,0,234,0.3); }
        .btn-main:disabled { background-color: #ccc; cursor: default; box-shadow: none; }
        .btn-link { background: none; border: none; color: var(--purple-main); margin-top: 15px; font-weight: bold; text-transform: uppercase; cursor: pointer; font-size: 14px; }

        .bottom-bar { 
            background-color: var(--purple-main); color: white; 
            text-align: center; padding: 18px; font-weight: bold; 
            font-style: italic; border-top-left-radius: 20px; border-top-right-radius: 20px; 
            font-size: 15px; margin-top: auto; 
        }

        .password-wrapper { position: relative; display: flex; align-items: center; }
        .password-wrapper .manual-input { padding-right: 60px; } 
        .toggle-icon { position: absolute; right: 20px; width: 28px; height: 28px; cursor: pointer; opacity: 0.7; }
    </style>
</head>
<body>

    <div id="ads-modal">
        <div class="ads-content">
            <div id="close-ads" onclick="document.getElementById('ads-modal').style.display='none'">✕</div>
            <div class="carousel-container" id="carousel-ads"></div>
            <div id="timer-text" style="padding:15px; text-align:center; font-size: 14px; color: #444;">
                Espera <span id="seconds" style="font-weight: bold; color: var(--orange-accent);">5</span> segundos...
            </div>
        </div>
    </div>

    <div class="header">
        <div class="logo-text" id="header-logo-text">WIFI<span>EXPRES</span>™</div>
        <div class="welcome-msg">¡Hola, Bienvenido!</div>
    </div>

    <div class="hero-banner" id="main-banner"></div>

    <div class="content">
        <div id="screen-payment" class="screen active">
            <h2>Forma de Pago</h2>
            <p style="color:#666; font-size:14px; margin-bottom:25px;">Selecciona tu método de acceso</p>
            
            <div class="option-card" onclick="selectPayment('pasarela')">
                <div class="icon-wrapper"><img id="img-pasarela" class="icon-img" src="img/icono_pasarela_gris.png"></div>
                <div class="info-box"><div class="title-text">Pasarela</div><div style="font-size:14px;color:#888;">BioPago / BDV</div></div>
            </div>

            <div id="option-kiosko" class="option-card" onclick="selectPayment('kiosko')">
                <div class="icon-wrapper"><img id="img-kiosko" class="icon-img" src="img/icono_kiosko_gris.png"></div>
                <div class="info-box"><div class="title-text">Kiosko</div><div style="font-size:14px;color:#888;">Ticket físico en tienda</div></div>
            </div>

            <div class="direct-access">¿Ya tienes un ticket? <span onclick="goToManualLogin()">Ingresa aquí</span></div>
        </div>

        <div id="screen-manual-login" class="screen">
            <h2 style="font-size: 24px;">Tu Ticket de Acceso</h2>
            <div style="margin-bottom:20px;">
                <label style="display:block;color:var(--purple-main);font-weight:800;font-size:16px;margin-bottom:8px;text-transform:uppercase;">Usuario</label>
                <input type="text" id="manual-user" class="manual-input" placeholder="Ej: 10001">
            </div>
            <div style="margin-bottom:20px;">
                <label style="display:block;color:var(--purple-main);font-weight:800;font-size:16px;margin-bottom:8px;text-transform:uppercase;">Contraseña</label>
                <div class="password-wrapper">
                    <input type="password" id="manual-pass" class="manual-input" placeholder="••••••••">
                    <img src="img/eyeclosed.png" id="eye-icon" class="toggle-icon" onclick="togglePassword()">
                </div>
            </div>
        </div>

        <div id="screen-kiosko-info" class="screen">
            <h2>Ubicación</h2>
            <div style="background:#f8f9fa;border-radius:12px;padding:15px;border-left:5px solid var(--purple-main);font-size:15px; margin-bottom: 20px;">
                <p><strong>Tienda:</strong> <span id="info-store-name">Cargando...</span></p>
                <p><strong>Ubicación:</strong> <span id="info-store-address">Cargando...</span></p>
            </div>
            <div style="background:#fff3e0;border:2px dashed var(--orange-accent);padding:18px;border-radius:15px;text-align:center;margin:15px 0;color:#e65100;font-weight:bold;font-size:15px;">
                ⚠️ UNA VEZ TENGAS TU TICKET, PULSA CONTINUAR.
            </div>
        </div>

        <div id="screen-plans" class="screen">
            <h2>Planes Disponibles</h2>
            <div id="list-plans"></div>
        </div>

        <div id="screen-iframe" class="screen">
            <h2>Finalizar BioPago BDV</h2>
            <div id="iframe-container">
                <iframe id="pasarela-frame" src=""></iframe>
            </div>
        </div>
    </div>

    <div class="footer-actions">
        <button id="btn-next" class="btn-main" disabled onclick="handleNext()">Confirmar Selección</button>
        <button id="btn-back" class="btn-link" style="display: none;" onclick="goBack()">Volver</button>
    </div>

    <div class="bottom-bar">INTERNET <span style="color:var(--orange-accent)">A TU LADO</span></div>

    <form name="sendin" action="/login" method="post" style="display:none">
        <input type="hidden" name="username" />
        <input type="hidden" name="password" />
    </form>

    <script>
        let routerIdentity = 'MikroTik_01'; 
        let routerMac = '48:A9:8A:91:CD:AE'; 
        const API_BASE = "https://wifiexpres.com/api/v1"; 

        let slideIndex = 0;
        let currentStep = 1; 
        let paymentType = null; 
        let planSelected = null; 
        let planAmount = null;

        // --- CAPTURA DE MENSAJE, LLAMADA A API E INICIO DE SESIÓN ---
        window.addEventListener('message', async (event) => {
            if (event.data && event.data.status === 'pago_exitoso') {
                const telefonoUsuario = event.data.user; 
                const passwordGenerado = Math.floor(10000 + Math.random() * 90000).toString();
                const perfilMikrotik = planSelected; 

                try {
                    // 1. Llamada a la API para crear el usuario en el servidor
                    const res = await fetch(`${API_BASE}/users/add`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ 
                            username: telefonoUsuario, 
                            password: passwordGenerado, 
                            profile: perfilMikrotik 
                        })
                    });

                    const result = await res.json();
                    
                    if (result.success) {
                        // 2. Procedimiento de inicio de sesión: Insertar datos y enviar al Hotspot
                        document.forms.sendin.username.value = telefonoUsuario;
                        document.forms.sendin.password.value = passwordGenerado;
                        
                        setTimeout(() => { 
                            document.forms.sendin.submit(); 
                        }, 1500); 
                    } else {
                        alert("Error de activación: " + (result.error || "No se pudo procesar el usuario."));
                    }
                } catch (e) {
                    alert("Error de conexión con el servidor de activación.");
                }
            }
        });

        async function initPortal() {
            try {
                const res = await fetch(`${API_BASE}/get-plans?mac=${encodeURIComponent(routerMac)}&t=${new Date().getTime()}`);
                const data = await res.json();
                
                if(data.success && data.router) {
                    const r = data.router;
                    routerIdentity = r.identity || routerIdentity;
                    
                    if(r.is_promotion == 1 && r.path_imgs && r.path_imgs.length > 0) {
                        renderCarousel(r.path_imgs);
                        document.getElementById('ads-modal').style.display = 'flex';
                        startAdsTimer();
                    } else {
                        document.getElementById('ads-modal').style.display = 'none';
                    }

                    if(r.is_store == 0) {
                        document.getElementById('option-kiosko').classList.add('hidden');
                    }

                    if(r.comercio_nombre) {
                        document.getElementById('header-logo-text').innerHTML = r.comercio_nombre.replace("EXPRES", "<span>EXPRES</span>");
                    }

                    const finalBanner = (r.comercio_banner && r.comercio_banner.trim() !== "") 
                        ? r.comercio_banner 
                        : 'banner/WIFIEXPRES_banner_01.jpg';
                    
                    document.getElementById('main-banner').style.background = `url('${finalBanner}') center/cover`;
                    
                    document.getElementById('info-store-name').innerText = r.store || 'WIFI EXPRES';
                    document.getElementById('info-store-address').innerText = r.address || 'Ubicación General';
                }
            } catch (e) { 
                console.error("❌ Error en initPortal:", e);
                document.getElementById('ads-modal').style.display = 'none';
            }
        }

        function renderCarousel(imgs) {
            const container = document.getElementById('carousel-ads');
            container.innerHTML = "";
            imgs.forEach((src, i) => {
                const img = document.createElement('img');
                img.src = src;
                img.className = (i === 0) ? "carousel-slide active" : "carousel-slide";
                container.appendChild(img);
            });
            showSlides();
        }

        function showSlides() {
            let slides = document.getElementsByClassName("carousel-slide");
            if(!slides.length) return;
            for (let i = 0; i < slides.length; i++) slides[i].classList.remove("active");
            slideIndex++;
            if (slideIndex > slides.length) slideIndex = 1;
            slides[slideIndex-1].classList.add("active");
            setTimeout(showSlides, 3000);
        }

        function startAdsTimer() {
            let timeLeft = 5;
            const timer = setInterval(() => {
                timeLeft--;
                document.getElementById('seconds').innerText = timeLeft;
                if(timeLeft <= 0) {
                    clearInterval(timer);
                    document.getElementById('close-ads').style.display = 'flex';
                    document.getElementById('timer-text').innerHTML = "¡Ya puedes continuar!";
                }
            }, 1000);
        }

        function selectPayment(type) {
            paymentType = type;
            document.querySelectorAll('#screen-payment .option-card').forEach(c => c.classList.remove('selected'));
            event.currentTarget.classList.add('selected');
            document.getElementById('img-pasarela').src = (type === 'pasarela') ? 'img/icono_pasarela_azul.png' : 'img/icono_pasarela_gris.png';
            const imgK = document.getElementById('img-kiosko');
            if(imgK) imgK.src = (type === 'kiosko') ? 'img/icono_kiosko_azul.png' : 'img/icono_kiosko_gris.png';
            document.getElementById('btn-next').disabled = false;
        }

        function handleNext() {
            if (currentStep === 1) {
                if (paymentType === 'kiosko') { 
                    showScreen('screen-kiosko-info'); 
                    document.getElementById('btn-next').innerText = "CONTINUAR"; 
                    currentStep = 1.5; 
                } else { 
                    showPlansScreen(); 
                }
            } else if (currentStep === 1.5) { 
                goToManualLogin(); 
            } else if (currentStep === 1.8) { 
                submitLogin(); 
            } else if (currentStep === 2) { 
                processPaymentStep();
            }
        }

        async function loadPlans() {
            const list = document.getElementById('list-plans'); 
            list.innerHTML = "<div style='text-align:center; padding:20px;'>Cargando planes...</div>";
            try {
                const res = await fetch(`${API_BASE}/get-plans?mac=${encodeURIComponent(routerMac)}`);
                const data = await res.json();
                if(data.success && data.plans) {
                    list.innerHTML = "";
                    data.plans.forEach(p => {
                        const d = document.createElement('div');
                        d.className = 'option-card';
                        d.innerHTML = `
                            <div class="icon-wrapper">
                                <svg class="time-icon-svg" viewBox="0 0 24 24"><path d="M12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22C6.47,22 2,17.5 2,12A10,10 0 0,1 12,2M12.5,7V12.25L17,14.92L16.25,16.15L11,13V7H12.5Z" /></svg>
                            </div>
                            <div class="info-box">
                                <div class="title-text">${p.name}</div>
                                <div style="font-size:14px;color:#888;">Bs. ${p.price} • ${p.uptime}</div>
                            </div>`;
                        d.onclick = () => {
                            document.querySelectorAll('#list-plans .option-card').forEach(x => x.classList.remove('selected'));
                            d.classList.add('selected'); 
                            planSelected = p.mikrotik_profile; 
                            planAmount = p.price;
                            document.getElementById('btn-next').disabled = false;
                        };
                        list.appendChild(d);
                    });
                }
            } catch (e) { list.innerHTML = "Error al conectar."; }
        }

        function showPlansScreen() {
            currentStep = 2; 
            showScreen('screen-plans');
            document.getElementById('btn-next').innerText = "Confirmar Selección";
            document.getElementById('btn-next').disabled = true; 
            loadPlans();
        }

        function processPaymentStep() {
            currentStep = 3;
            showScreen('screen-iframe');
            document.getElementById('btn-next').style.display = 'none';
            document.getElementById('btn-back').style.display = 'none'; 
            document.getElementById('pasarela-frame').src = `pasarela.html?plan=${encodeURIComponent(planSelected)}&amount=${planAmount}&identity=${encodeURIComponent(routerIdentity)}`;
        }

        function goToManualLogin() { 
            currentStep = 1.8; 
            showScreen('screen-manual-login'); 
            document.getElementById('btn-next').innerText = "INICIAR SESIÓN"; 
        }

        function showScreen(id) {
            document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
            document.getElementById(id).classList.add('active');
            document.getElementById('btn-back').style.display = 'inline-block';
        }

        function goBack() {
            if(currentStep === 3) {
                document.getElementById('btn-next').style.display = 'block';
                document.getElementById('btn-back').style.display = 'inline-block';
            }
            showScreen('screen-payment'); 
            currentStep = 1;
            document.getElementById('btn-back').style.display = 'none';
            document.getElementById('btn-next').innerText = "Confirmar Selección";
            document.getElementById('btn-next').disabled = (paymentType === null);
        }

        function togglePassword() {
            const passInput = document.getElementById('manual-pass');
            const eyeIcon = document.getElementById('eye-icon');
            if (passInput.type === 'password') {
                passInput.type = 'text'; eyeIcon.src = 'img/eyeopen.png';
            } else {
                passInput.type = 'password'; eyeIcon.src = 'img/eyeclosed.png';
            }
        }

        function submitLogin() {
            const u = document.getElementById('manual-user').value;
            const p = document.getElementById('manual-pass').value;
            if(u && p) {
                document.forms.sendin.username.value = u;
                document.forms.sendin.password.value = p;
                document.forms.sendin.submit();
            } else {
                alert("Por favor ingresa usuario y contraseña.");
            }
        }

        window.onload = initPortal;
    </script>
</body>
</html>