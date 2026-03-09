<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WifiExpres</title>
    <!-- Enlace a Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Enlace a Font Awesome para iconos (necesario para el chat y redes sociales) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- Enlace a CSS Personalizado -->
    <link rel="stylesheet" href="css/styles_welcome.css">
    <link rel="icon" type="image/png" sizes="16x16"  href="/favicon-16x16.png">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="theme-color" content="#ffffff">
    <style>
        @media (min-width: 992px) {
  .navbar-toggler {
    display: none !important;
  }
}

@media (min-width: 768px) {
  .navbar-toggler {
    display: none !important;
  }
}
.footer-autorizacion {
  background-color: #dd751a !important;
  height: 45px !important;
  color: white !important;
  font-size: 14px;
}

    </style>
</head>
<body>

    <!-- 1. BARRA DE NAVEGACIÓN (NAVBAR) -->
    <header>
        <div class="container-fluid p-0 fixed-top"> 

            <div class="row d-flex justify-content-between align-items-center py-2 px-3 m-0 bg-white border-bottom">
                
                <div class="col-12 d-flex align-items-center justify-content-between">
                    <a class="navbar-brand" href="/" data-page="inicio">
                        <img src="img/logo-wifiexpres.png" class="img-responsive" onerror="this.onerror=null; this.src='https://placehold.co/120x45/007bff/white?text=WiFiExpres';" alt="Logo WiFiExpres">
                    </a>
                    <button class="navbar-toggler me-3 botonX" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"><span class="botonX">X</span></span>
                    </button>
                    <ul class="navbar-nav flex-row">
                        <li class="nav-item dropdown">
                            <a class="nav-purple dropdown-toggle" href="/" id="miRednetDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" data-page="mi-rednet">
                                <img src="img/icono_mirednet.svg" alt="" class="nav-icon" onerror="this.onerror=null;this.src='https://placehold.co/20x20/007bff/fff?text=R';">
                                WifiExpres
                                
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="miRednetDropdown">
                                <li><a class="dropdown-item" href="/login">Login</a></li>
                                <hr class="my-0 bg-secondary">
                            </ul>
                        </li>
                    </ul>
                </div>
                
                
            </div>

            <nav class="row m-0 bg-orange-dark p-0 navbar navbar-expand-lg border-top border-bottom border-light ">
                <div class="col-12 p-0">
                    <div class="collapse navbar-collapse" id="navbarNav">
                        <div class="container-fluid">
                            
                            <ul class="navbar-nav w-100 d-flex justify-content-center">
                                <li class="nav-item">
                                    <a class="nav-link active px-3" aria-current="page" href="/#inicio" data-page="inicio">Inicio</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link px-3" href="/#nosotros" data-page="nosotros">Nosotros</a>
                                </li>
                                
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle px-3" href="/#servicios" id="serviciosDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" data-page="servicios">
                                        Servicios
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="serviciosDropdown">
                                        <li><a class="dropdown-item" href="#servicio1">Servicio 1</a></li>
                                        <li><a class="dropdown-item" href="#servicio2">Servicio 2</a></li>
                                    </ul>
                                </li>

                                <li class="nav-item">
                                    <a class="nav-link px-3" href="/#ventajas" data-page="ventajas">Ventajas</a>
                                </li>

                                <li class="nav-item">
                                    <a class="nav-link px-3" href="/#portal-cautivo" data-page="portal-cautivo">Portal Cautivo</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>
        </div>
    </header>

    <!-- 2. CONTENIDO PRINCIPAL (MAIN) -->
    <main class="mt-5 pt-5">
        <!-- Sección 1: INICIO (Carrusel Bootstrap) -->
        <section id="inicio" class="vh-70 w-100 position-relative bg-section-1 pt-4">
            <div id="carouselInicio" class="carousel slide h-100" data-bs-ride="carousel">
                <div class="carousel-indicators">
                    <button type="button" data-bs-target="#carouselInicio" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                    <button type="button" data-bs-target="#carouselInicio" data-bs-slide-to="1" aria-label="Slide 2"></button>
                    <button type="button" data-bs-target="#carouselInicio" data-bs-slide-to="2" aria-label="Slide 3"></button>
                    <button type="button" data-bs-target="#carouselInicio" data-bs-slide-to="3" aria-label="Slide 4"></button>
                    <button type="button" data-bs-target="#carouselInicio" data-bs-slide-to="4" aria-label="Slide 5"></button>
                    
                </div>
                <div class="carousel-inner h-100">
                    <div class="carousel-item active h-100">
                        <img src="img/carrusel1.jpg" class="d-block w-100 h-100" style="object-fit:cover;" alt="Carrusel 1" onerror="this.onerror=null; this.src='https://placehold.co/1200x600';">
                    </div>
                    <div class="carousel-item h-100">
                        <img src="img/carrusel2.jpg" class="d-block w-100 h-100" style="object-fit:cover;" alt="Carrusel 2" onerror="this.onerror=null; this.src='https://placehold.co/1200x600';">
                    </div>
                    <div class="carousel-item h-100">
                        <img src="img/carrusel3.jpg" class="d-block w-100 h-100" style="object-fit:cover;" alt="Carrusel 3" onerror="this.onerror=null; this.src='https://placehold.co/1200x600';">
                    </div>
                    <div class="carousel-item h-100">
                        <img src="img/carrusel4.jpg" class="d-block w-100 h-100" style="object-fit:cover;" alt="Carrusel 4" onerror="this.onerror=null; this.src='https://placehold.co/1200x600';">
                    </div>
                    <div class="carousel-item h-100">
                        <img src="img/carrusel5.jpg" class="d-block w-100 h-100" style="object-fit:cover;" alt="Carrusel 5" onerror="this.onerror=null; this.src='https://placehold.co/1200x600';">
                    </div>
                    <div class="carousel-item h-100">
                        <img src="img/carrusel6.jpg" class="d-block w-100 h-100" style="object-fit:cover;" alt="Carrusel 6" onerror="this.onerror=null; this.src='https://placehold.co/1200x600';">
                    </div>
                    <div class="carousel-item h-100">
                        <img src="img/carrusel7.jpg" class="d-block w-100 h-100" style="object-fit:cover;" alt="Carrusel 7" onerror="this.onerror=null; this.src='https://placehold.co/1200x600';">
                    </div>
                    <div class="carousel-item h-100">
                        <img src="img/carrusel8.jpg" class="d-block w-100 h-100" style="object-fit:cover;" alt="Carrusel 8" onerror="this.onerror=null; this.src='https://placehold.co/1200x600';">
                    </div>
                    <div class="carousel-item h-100">
                        <img src="img/carrusel9.jpg" class="d-block w-100 h-100" style="object-fit:cover;" alt="Carrusel 9" onerror="this.onerror=null; this.src='https://placehold.co/1200x600';">
                    </div>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#carouselInicio" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Anterior</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#carouselInicio" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Siguiente</span>
                </button>
            </div>
        </section>

        <!-- Sección 2: NOSOTROS -->
        <section id="nosotros" class="vh-70 w-100 d-flex justify-content-center align-items-center text-center p-0 bg-section-2  pt-4">
            <img src="img/nosotros.jpg" onerror="this.onerror=null; this.src='https://placehold.co/1200x600/cccccc/333?text=Nosotros';" alt="Nosotros" class="img-fluid section-img section-img--contain">
        </section>

        <!-- Sección 3: SERVICIOS -->
        <section id="servicios" class="vh-70 w-100 position-relative bg-section-1">
            <img src="img/servicios.jpg" onerror="this.onerror=null; this.src='https://placehold.co/1200x600/cccccc/333?text=Servicios';" alt="Servicios" class="img-fluid section-img">
        </section>

        <!-- Sección 4: VENTAJAS -->
        <section id="ventajas" class="vh-70 w-100 position-relative bg-section-1">
            <img src="img/ventajas.jpg" onerror="this.onerror=null; this.src='https://placehold.co/1200x600/cccccc/333?text=Ventajas';" alt="Ventajas" class="img-fluid section-img">
        </section>

        <!-- Sección 5: PORTAL CAUTIVO -->
        <section id="portal-cautivo" class="vh-70 w-100 position-relative bg-section-1">
            <img src="img/portalcautivo.jpg" onerror="this.onerror=null; this.src='https://placehold.co/1200x600/cccccc/333?text=Portal+Cautivo';" alt="Portal Cautivo" class="img-fluid section-img">
        </section>

    </main>

    <!-- 3. PIE DE PÁGINA (FOOTER) -->
    <footer class="text-white">
        <div class="container-fluid px-0">
            <div class="row text-center text-md-start footer-top py-4">

                <!-- Columna 1: Logo + Dirección -->
                <div class="col-12 col-md-3 text-center text-md-start mb-3 mb-md-0 px-3">
                    <img width="400" src="img/logo-wifiexpres.png" class="img-responsive footer-logo1" onerror="this.onerror=null; this.src='';" alt="Logo WiFiExpres Footer">
                </div>

                <!-- Columna 2: Horario de Atención -->
                <div class="col-12 col-md-3 text-center text-md-start mb-3 mb-md-0 px-3">
                    <h5 class="mb-2 font-weight-bold text-warning mt-2"><i class="fas fa-map-marker-alt me-2" aria-hidden="true"></i>Dirección</h5>
                    <p class="mb-0">Av. Vollmer - Edif. Normandie - Piso 6 Ofic. 612<br>San Bernardino - Caracas<br>Venezuela</p>
                </div>

                <!-- Columna 3: Menú -->
                <div class="col-12 col-md-3 text-center text-md-start mb-3 mb-md-0 px-3">
                    <h5 class="mb-2 font-weight-bold text-warning"><i class="fas fa-clock me-2" aria-hidden="true"></i>Horario de atención</h5>
                    <p class="mb-1">Lunes - Viernes: 8:00 a.m. - 5:00 p.m.<br>Sábados: 8:00 a.m. - 2:00 p.m.</p>
                    <h5 class="mb-1 mt-3 font-weight-bold text-warning"><i class="fas fa-comments me-2" aria-hidden="true"></i>Atención por Redes sociales</h5>
                    <p class="mb-0">Lunes - Sábados: 8:00 a.m. - 8:00 p.m.</p>
                </div>

                <!-- Columna 4: Reserva para futuro o contacto adicional -->
                <div class="col-12 col-md-3 text-center text-md-start mb-3 mb-md-0 px-3">
                    <h5 class="mb-2 font-weight-bold text-warning"><i class="fas fa-list me-2" aria-hidden="true"></i>Menú</h5>
                    <p class="mb-1"><a href="/#inicio" class="text-white text-decoration-none">Inicio</a></p>
                    <p class="mb-1"><a href="/#nosotros" class="text-white text-decoration-none">Nosotros</a></p>
                    <h5 class="mb-2 mt-3 font-weight-bold text-warning"><i class="fas fa-envelope me-2" aria-hidden="true"></i>Correo</h5>
                    <p class="mb-0"><a href="mailto:ddrsistemas@gmail.com" class="text-white text-decoration-none">ddrsistemas@gmail.com</a></p>
                </div>
            </div>
            <div class="row py-3 align-items-center footer-autorizacion text-center">
                <span class="h6">El Servicio de internet es provisto por la empresa INVERSIONES RED NET 2030, C.A. habilitada por CONATEL con el número HGTS-00490</span>
            </div>

            <!-- FILA ADICIONAL: DDR (Logo, Copyright, Redes) -->
            <div class="row py-3 align-items-center footer-ddr">
                <div class="col-md-3 text-center text-md-start">
                    <img src="img/logo-ddr.png" class="img-responsive footer-logo ddr" onerror="this.onerror=null; this.src='https://placehold.co/140x40/ffffff/000?text=DDR';" alt="Logo DDR">
                </div>
                <div class="col-md-5 text-center">
                    <p class="text-white mb-0">COPYRIGTH © DDR SISTEMAS C.A. RIF: J-31512955-8 </p>
                    <p>V1.0.0.PV2</p>
                </div>
                <div class="col-md-4 text-center text-md-end">
                    <a href="#" class="social-circle" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-circle" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                    <a href="#" class="social-circle" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
                    <a href="#" class="social-circle" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-circle" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
        </div>
    </footer>

    
    <!-- Enlace a Bootstrap JS (Popper y jQuery incluidos en el bundle) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Enlace a JavaScript Personalizado -->
    <script src="js/scripts_welcome.js"></script>
</body>
</html>