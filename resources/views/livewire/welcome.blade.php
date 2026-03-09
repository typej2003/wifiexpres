<div>
    <style>
        /* --- CORRECCIÓN DE ANCHO TOTAL SOLO PARA EL BANNER --- */
        #inicio {
            width: 100vw;
            position: relative;
            left: 50%;
            right: 50%;
            margin-left: -50vw;
            margin-right: -50vw;
            overflow: hidden;
            background-color: #6500da; 
            /* Evitamos que la sección colapse mientras carga */
            min-height: calc(100vh - 110px);
        }

        /* Ocultamos el carrusel inicialmente para evitar mostrar la imagen errónea */
        .carousel-placeholder {
            display: none;
        }
        
        /* Solo mostramos el contenido cuando Livewire termine de cargar los banners */
        .banners-ready {
            display: block !important;
        }

        .section-img-full {
            width: 100%;
            height: calc(100vh - 110px);
            object-fit: cover;
            object-position: center;
            display: block;
        }

        /* --- ESTILOS BASE --- */
        .full-page-section {
            min-height: calc(100vh - 110px);
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .franja-morada-inferior {
            background-color: #6500da;
            color: white;
            padding: 15px 0;
            width: 100%;
            z-index: 3;
            margin-top: auto; 
        }

        .franja-morada-inferior h2 {
            letter-spacing: 3px;
            font-size: 2rem;
            text-transform: uppercase;
            margin: 0;
            font-weight: bold;
        }

        /* Nosotros */
        .nosotros-bg-wrapper {
            position: absolute;
            top: 0; right: 0; width: 100%; height: 100%;
            z-index: 1; display: flex; justify-content: flex-end;
        }
        .img-adaptada { width: 100%; height: 100%; object-fit: contain; object-position: right center; }
        .nosotros-content-wide { border-left: 6px solid #ff572f; padding-left: 30px; }
        .reseña-parrafo { font-size: 1.25rem; line-height: 1.7; color: #333 !important; }
        .capitular-purple { float: left; font-size: 2.5rem; line-height: 0.8; padding: 5px 5px 0 0; font-weight: 900; color: #6500da; }

        /* Servicios & Ventajas */
        .section-naranja { background-color: #f58634 !important; }
        .bg-purple-box { background-color: #6500da; border-radius: 15px; display: inline-block; }
        .icon-circle-purple { width: 50px; height: 50px; background-color: #6500da; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0; }
        .text-Orange { color: #ff572f !important; }

        /* --- OPTIMIZACIÓN RESPONSIVE --- */
        @media (max-width: 768px) {
            #inicio {
                margin-top: 110px; 
                min-height: 70vh;
            }
            .section-img-full { 
                height: 70vh; 
            }
            .full-page-section {
                height: auto !important;
                min-height: auto;
                padding-top: 30px;
            }
            #inicio.full-page-section { padding-top: 0; }
            .nosotros-bg-wrapper { opacity: 0.2; }
            .nosotros-content-wide { background: rgba(255,255,255,0.85); margin: 0; padding: 20px; border-left-width: 4px; }
            .franja-morada-inferior h2 { font-size: 1.5rem; text-align: center; }
            .display-4 { font-size: 2.2rem !important; }
        }
    </style>

    {{-- SECCIÓN 1: INICIO (BANNER) --}}
    <section id="inicio" class="full-page-section">
        {{-- Solo se muestra si hay banners cargados para evitar la imagen por defecto flash --}}
        <div id="carouselInicio" class="carousel slide w-100 h-100 carousel-placeholder @if($banners->count() > 0) banners-ready @endif" data-bs-ride="carousel" data-bs-interval="5000">
            <div class="carousel-inner h-100">
                @foreach($banners as $index => $banner)
                    <div class="carousel-item h-100 {{ $index === 0 ? 'active' : '' }}">
                        <img src="{{ $banner->avatar_url }}" class="section-img-full" alt="{{ $banner->title }}">
                    </div>
                @endforeach
            </div>
            @if($banners->count() > 1)
                <button class="carousel-control-prev" type="button" data-bs-target="#carouselInicio" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
                <button class="carousel-control-next" type="button" data-bs-target="#carouselInicio" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
            @endif
        </section>

    {{-- SECCIÓN 2: NOSOTROS --}}
    <section id="nosotros" class="full-page-section bg-white p-0">
        <div class="nosotros-bg-wrapper">
            <img src="{{ asset('img/nosotros.jpg') }}" class="img-adaptada" alt="Nosotros">
        </div>
        <div class="container d-flex align-items-center flex-grow-1 py-4 position-relative" style="z-index: 2;">
            <div class="row w-100 m-0">
                <div class="col-md-10 col-lg-8 p-0"> 
                    <div class="nosotros-content-wide">
                        <p class="text-dark reseña-parrafo">
                            <span class="capitular-purple">E</span>n WifiExprés, transformamos la conectividad. 
                            Ofrecemos soluciones robustas para eventos y espacios públicos, 
                            con un equipo técnico apasionado que garantiza que siempre estés en línea, 
                            sin importar el tamaño de tu desafío tecnológico.
                        </p>
                        <p class="text-dark reseña-parrafo mt-4">
                            Nuestra misión es simplificar la tecnología para que tú te enfoques en lo importante. 
                            Acompañamos cada proyecto con equipos de última generación y una atención 
                            personalizada que nos convierte en el aliado estratégico ideal para tus ferias, 
                            congresos y lanzamientos de marca.
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <div class="franja-morada-inferior">
            <div class="container px-md-3"><h2>NOSOTROS</h2></div>
        </div>
    </section>

    {{-- SECCIÓN 3: SERVICIOS --}}
    <section id="servicios" class="full-page-section section-naranja p-0">
        <div class="container d-flex align-items-center flex-grow-1 py-4">
            <div class="row w-100 align-items-center">
                <div class="col-md-6 mb-5 mb-md-0">
                    <h1 class="display-4 fw-normal text-white lh-1">Disfruta<br>de una mejor<br>experiencia de</h1>
                    <div class="bg-purple-box mt-3 shadow">
                        <h2 class="m-0 fw-bold text-white py-2 px-4">CONECTIVIDAD SIN LÍMITE</h2>
                    </div>
                </div>
                <div class="col-md-6 ps-md-5">
                    <div class="d-flex flex-column gap-4">
                        <div class="d-flex align-items-center text-white">
                            <div class="icon-circle-purple me-3 shadow-sm"><i class="bi bi-geo-fill"></i></div>
                            <h5 class="m-0 fw-bold">PUNTOS URBANOS Y EXTRAURBANOS</h5>
                        </div>
                        <div class="d-flex align-items-center text-white">
                            <div class="icon-circle-purple me-3 shadow-sm"><i class="bi bi-cart-fill"></i></div>
                            <h5 class="m-0 fw-bold">SUPERMERCADOS Y CENTROS COMERCIALES</h5>
                        </div>
                        <div class="d-flex align-items-center text-white">
                            <div class="icon-circle-purple me-3 shadow-sm"><i class="bi bi-megaphone-fill"></i></div>
                            <h5 class="m-0 fw-bold">CONGRESOS Y FERIAS</h5>
                        </div>
                        <div class="d-flex align-items-center text-white">
                            <div class="icon-circle-purple me-3 shadow-sm"><i class="bi bi-people-fill"></i></div>
                            <h5 class="m-0 fw-bold">EVENTOS SOCIALES</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="franja-morada-inferior">
            <div class="container-fluid px-md-5"><h2>SERVICIOS</h2></div>
        </div>
    </section>

    {{-- SECCIÓN 4: VENTAJAS --}}
    <section id="ventajas" class="full-page-section bg-white p-0">
        <div class="container d-flex align-items-center flex-grow-1 py-4">
            <div class="row g-4 g-md-5">
                <div class="col-md-6 d-flex align-items-start">
                    <div class="icon-box me-3"><i class="bi bi-geo-alt-fill text-Orange fs-1"></i></div>
                    <div>
                        <h5 class="fw-bold text-Orange">COBERTURA PARA TODO TIPO DE EVENTOS</h5>
                        <p class="text-muted mb-0 small">Ya sea un congreso de un día, una feria de fin de semana o un tour de eventos durante un mes, tenemos planes adaptados. La conectividad está asegurada sin importar la duración, el tamaño ni la ubicación de tu evento.</p>
                    </div>
                </div>
                <div class="col-md-6 d-flex align-items-start">
                    <div class="icon-box me-3"><i class="bi bi-calendar-check-fill text-Orange fs-1"></i></div>
                    <div>
                        <h5 class="fw-bold text-Orange">PLANES DE ALQUILER FLEXIBLES</h5>
                        <p class="text-muted mb-0 small">Contrata nuestros dispositivos por el tiempo que necesites: por día, por evento, por semana o por mes. Paga solo por lo que uses, con la tranquilidad de que podrás extender el servicio si tu agenda cambia.</p>
                    </div>
                </div>
                <div class="col-md-6 d-flex align-items-start">
                    <div class="icon-box me-3"><i class="bi bi-person-check-fill text-Orange fs-1"></i></div>
                    <div>
                        <h5 class="fw-bold text-Orange">SOPORTE TÉCNICO DEDICADO</h5>
                        <p class="text-muted mb-0 small">Nuestro equipo estará pendiente de tu evento, ofreciendo asistencia antes, durante y después del mismo. Podemos desplegar personal in situ para monitorear la red y resolver cualquier incidencia al momento.</p>
                    </div>
                </div>
                <div class="col-md-6 d-flex align-items-start">
                    <div class="icon-box me-3"><i class="bi bi-shield-check text-Orange fs-1"></i></div>
                    <div>
                        <h5 class="fw-bold text-Orange">CONEXIÓN GARANTIZADA</h5>
                        <p class="text-muted mb-0 small">Utilizamos equipos de última generación y SIMs multioperador para asegurar la máxima cobertura y velocidad. Incluso con cientos de asistentes conectados a la vez, evitamos saturaciones.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="franja-morada-inferior">
            <div class="container-fluid px-md-5"><h2>VENTAJAS</h2></div>
        </div>
    </section>

    {{-- SECCIÓN 5: PORTAL CAUTIVO --}}
    <section id="portal-cautivo" class="full-page-section bg-white p-0">
        <div class="container d-flex align-items-center flex-grow-1 py-4">
            <div class="row g-3 justify-content-center w-100 m-0">
                @for ($i = 1; $i <= 5; $i++)
                    <div class="col-6 col-md-4 col-lg-2">
                        <img src="{{ asset('img/portalcautivo'.$i.'.jpg') }}" class="img-fluid rounded shadow-sm" alt="Portal {{ $i }}">
                    </div>
                @endfor
            </div>
        </div>
        <div class="franja-morada-inferior">
            <div class="container-fluid px-md-5 d-flex flex-column flex-md-row align-items-md-center justify-content-between">
                <h2 class="m-0 fw-bold">PORTAL CAUTIVO</h2>
                <span class="text-white fw-light opacity-75 mt-1 mt-md-0" style="letter-spacing: 1px;">CUSTOMIZADO CON PASARELA DE PAGO</span>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            function detectDevice() {
                let width = window.innerWidth;
                let type = width < 768 ? 'm' : (width < 992 ? 't' : 'd');
                
                // Solo emitimos si Livewire está listo
                if (window.livewire) { 
                    window.livewire.emit('setDevice', type); 
                }
            }
            
            // Ejecución inmediata y retardada para asegurar captura
            detectDevice();
            setTimeout(detectDevice, 50);
            
            window.addEventListener('resize', detectDevice);
        });
    </script>
    @livewire('layouts.components.whatsapp')
</div>