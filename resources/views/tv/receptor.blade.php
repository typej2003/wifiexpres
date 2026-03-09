<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wifiexprés TV - {{ $pantalla->nombre }}</title>
    <script src="https://cdn.socket.io/4.5.4/socket.io.min.js"></script>
    <style>
        body, html { margin: 0; padding: 0; width: 100%; height: 100%; background-color: black; overflow: hidden; font-family: sans-serif; }
        #display-container { width: 100%; height: 100%; display: flex; justify-content: center; align-items: center; }
        img, video { max-width: 100%; max-height: 100%; object-fit: contain; transition: opacity 0.5s ease-in-out; }
        .placeholder { color: #333; text-align: center; }
    </style>
</head>
<body>

    <div id="display-container">
        <div class="placeholder" id="status-text">
            <img src="{{ asset('img/logo-wifiexpres.png') }}" style="width: 200px; opacity: 0.2;">
            <p>Esperando señal de Wifiexprés... (ID: {{ $pantalla->id }})</p>
        </div>
    </div>

    <script>
        // 1. Conexión al servidor (Asegúrate que la IP sea la correcta)
        const socket = io('http://localhost:3000'); 
        const pantallaId = "{{ $pantalla->id }}";
        const container = document.getElementById('display-container');

        socket.on('connect', () => {
            console.log('✅ Conectado al servidor de Sockets. Uniendo a sala: pantalla-' + pantallaId);
            
            // 2. UNIRSE A LA SALA (Crucial para el Contenido Independiente)
            socket.emit('join-tv', pantallaId);
        });

        // 3. ESCUCHAR LA ORDEN DE LANZAMIENTO
        socket.on('ejecutar-hablador', (config) => {
            console.log('🚀 Recibido nuevo contenido:', config);
            
            // Limpiar contenedor
            container.innerHTML = '';

            // Si hay audio, reproducirlo
            if (config.audio) {
                const audio = new Audio(config.audio);
                audio.play().catch(e => console.log("El navegador bloqueó el auto-play del audio."));
            }

            // Renderizar contenido según tipo
            if (config.tipo === 'imagen') {
                const img = document.createElement('img');
                img.src = config.recursos[0]; // Tomamos la primera imagen del array
                img.style.opacity = 0;
                container.appendChild(img);
                
                // Efecto Fade-in
                setTimeout(() => img.style.opacity = 1, 50);
            } 
            
            else if (config.tipo === 'video') {
                const video = document.createElement('video');
                video.src = config.recursos[0];
                video.autoplay = true;
                video.loop = true;
                video.muted = false;
                container.appendChild(video);
            }
        });

        socket.on('connect_error', (error) => {
            console.error('❌ Error de conexión:', error);
        });
    </script>
</body>
</html>