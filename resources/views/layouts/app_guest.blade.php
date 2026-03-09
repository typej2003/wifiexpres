<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" sizes="16x16"  href="/favicon-16x16.png">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="theme-color" content="#ffffff">
    <title>@yield('title', 'WifiExprés - Bienvenidos')</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    {{-- Si usas Mix o Vite, asegúrate de que estas rutas sean correctas --}}
    <link rel="stylesheet" href="{{ asset('css/wifiexpres.css') }}">
    
    
    @livewireStyles
    <style>
        html { scroll-behavior: smooth; }
        body { margin: 0; padding: 0; overflow-x: hidden; }
        
        /* Ajuste para que cada sección ocupe el alto de pantalla menos el navbar */
        .full-page-section {
            min-height: calc(100vh - 110px); 
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .section-img-full {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Espacio para el navbar fijo */
        main { margin-top: 60px !important; }
    </style>
</head>
<body>
    @livewire('layouts.navbar')

    <main>
        {{ $slot }}
    </main>

    @livewire('layouts.footer')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function initCarousel() {
            var myCarouselElement = document.querySelector('#carouselInicio');
            if (myCarouselElement) {
                // Eliminar instancia previa si existe para evitar conflictos
                var existingCarousel = bootstrap.Carousel.getInstance(myCarouselElement);
                if (existingCarousel) {
                    existingCarousel.dispose();
                }
                // Crear nueva instancia
                new bootstrap.Carousel(myCarouselElement, {
                    interval: 3000, // 3 segundos para probar que se mueve
                    ride: 'carousel',
                    wrap: true
                });
            }
        }

        // Ejecutar al cargar la página
        document.addEventListener('DOMContentLoaded', initCarousel);

        // Ejecutar cada vez que Livewire actualice el DOM (por si acaso)
        document.addEventListener('livewire:navigated', initCarousel);
        document.addEventListener('livewire:load', initCarousel);
    </script>

    @livewireScripts
</body>
</html>