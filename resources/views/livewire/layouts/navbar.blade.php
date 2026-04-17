<div id="navbar-wrapper">
    <nav class="navbar fixed-top navbar-custom shadow-sm p-0 bg-white" wire:ignore>
        <div class="container-fluid py-2 border-bottom">
            <div class="row align-items-center w-100 g-0">
                <div class="col-6 col-md-2 text-start px-3">
                    <a class="navbar-brand p-0 m-0" href="/">
                        <img src="{{ asset('img/logo-wifiexpres.png') }}" class="img-fluid" style="max-width:140px;" alt="Logo">
                    </a>
                </div>
                <div class="col-6 col-md-10 d-flex justify-content-end align-items-center px-3">
                    @auth
                        <div class="dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center text-dark" href="#" data-bs-toggle="dropdown">
                                <img src="{{ auth()->user()->avatar_url }}" class="rounded-circle me-2" width="30" height="30">
                                <span class="d-none d-md-inline">{{ auth()->user()->name }}</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                @if(auth()->user()->role == 'root' || auth()->user()->role == 'admin')
                                    <a class="dropdown-item" href="/admin/panel">
                                        <i class="bi bi-speedometer2 me-2"></i> Escritorio
                                    </a>
                                @endif
                                @if(auth()->user()->role == 'aliado')
                                    <a class="dropdown-item" href="/dashboard-aliado">
                                        <i class="bi bi-speedometer2 me-2"></i> Escritorio
                                    </a>
                                @endif
                                <li><form method="POST" action="{{ route('logout') }}" id="logout-form">@csrf <button type="submit" class="dropdown-item text-danger">Salir</button></form></li>
                            </ul>
                        </div>
                    @else                            
                        <a class="nav-link text-Orange fw-bold p-0" href="/login">
                            <img src="{{ asset('img/icono_wifiexpres.svg') }}" class="me-2" style="max-width:20px;">
                            <span>Mi WifiExprés</span>
                        </a>
                    @endauth
                </div>
            </div>
        </div>

        <div class="menu-desktop w-100 bg-orange-dark border-top border-bottom border-light">
            <div class="container">
                <nav class="navbar navbar-expand-lg p-0">
                    <div class="collapse navbar-collapse justify-content-center" id="navbarNav">
                        <ul class="navbar-nav" id="main-nav">
                            <li class="nav-item"><a class="nav-link px-3 active" href="#inicio">Inicio</a></li>
                            <li class="nav-item"><a class="nav-link px-3" href="#nosotros">Nosotros</a></li>
                            <li class="nav-item"><a class="nav-link px-3" href="#servicios">Servicios</a></li>
                            <li class="nav-item"><a class="nav-link px-3" href="#ventajas">Ventajas</a></li>
                            <li class="nav-item"><a class="nav-link px-3" href="#portal-cautivo">Portal Cautivo</a></li>
                            
                            <li class="nav-item dropdown">
                                <a class="nav-link px-3 dropdown-toggle" href="#" id="navbarDescargas" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Descargas
                                </a>
                                <ul class="dropdown-menu shadow border-0" aria-labelledby="navbarDescargas">
                                    <li>
                                        <a class="dropdown-item d-flex align-items-center" href="{{ route('apk.download') }}">
                                            <i class="bi bi-android2 me-2 text-success"></i> WifiExpres v1 (APK)
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </div>

                    <div class="d-lg-none d-flex justify-content-between align-items-center w-100 py-2">
                        <span class="text-white small fw-bold ps-3">MENÚ</span>
                        <button id="btn-hamburguesa" class="btn text-white border-0 shadow-none px-3" type="button">
                            <i class="bi bi-list fs-1"></i>
                        </button>
                    </div>
                </nav>

                <div id="menu-movil-manual" class="d-none d-lg-none pb-2 text-center bg-orange-dark">
                    <nav class="d-flex flex-column" id="mobile-links">
                        <a href="#inicio" class="text-white py-3 border-top border-white border-opacity-25 text-decoration-none">INICIO</a>
                        <a href="#nosotros" class="text-white py-3 border-top border-white border-opacity-25 text-decoration-none">NOSOTROS</a>
                        <a href="#servicios" class="text-white py-3 border-top border-white border-opacity-25 text-decoration-none">SERVICIOS</a>
                        <a href="#ventajas" class="text-white py-3 border-top border-white border-opacity-25 text-decoration-none">VENTAJAS</a>
                        <a href="#portal-cautivo" class="text-white py-3 border-top border-white border-opacity-25 text-decoration-none">PORTAL CAUTIVO</a>
                        
                        <div class="bg-black bg-opacity-10">
                            <span class="text-white-50 small d-block pt-2">DESCARGAS</span>
                            <a href="{{ route('apk.download') }}" class="text-white py-3 d-block text-decoration-none fw-bold">
                                <i class="bi bi-android2 me-1"></i> WifiExpres v1
                            </a>
                        </div>
                    </nav>
                </div>
            </div>
        </div>    
    </nav>

    <style>
        .bg-orange-dark { background-color: #ff572f !important; }
        .text-Orange { color: #ff572f !important; }
        .menu-desktop .nav-link { color: #ffffff !important; font-weight: 500; position: relative; transition: 0.3s; }
        .menu-desktop .nav-link::after { content: ''; position: absolute; width: 0; height: 3px; bottom: 2px; left: 0; background-color: #ffffff; transition: width 0.3s ease; }
        .menu-desktop .nav-link.active::after { width: 100% !important; }
        
        /* Ajuste para que la flecha del dropdown sea blanca en desktop */
        .menu-desktop .dropdown-toggle::after { border-top-color: #ffffff; }
        .menu-desktop .dropdown-item:active { background-color: #ff572f; }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const btn = document.getElementById('btn-hamburguesa');
            const menu = document.getElementById('menu-movil-manual');
            const mobileLinks = document.querySelectorAll('#mobile-links a');
            const sections = document.querySelectorAll('section[id]');
            const desktopLinks = document.querySelectorAll('#main-nav .nav-link:not(.dropdown-toggle)');

            // 1. Abrir/Cerrar menú móvil
            btn?.addEventListener('click', () => {
                menu.classList.toggle('d-none');
            });

            // 2. Cerrar menú automáticamente al hacer clic en un link (Móvil)
            mobileLinks.forEach(link => {
                link.addEventListener('click', () => {
                    menu.classList.add('d-none');
                });
            });

            // 3. Raya blanca dinámica con Intersection Observer
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        desktopLinks.forEach(l => l.classList.remove('active'));
                        const activeLink = document.querySelector(`#main-nav a[href="#${entry.target.id}"]`);
                        if (activeLink) activeLink.classList.add('active');
                    }
                });
            }, { rootMargin: '-30% 0px -60% 0px' });

            sections.forEach(s => observer.observe(s));
        });
    </script>
</div>