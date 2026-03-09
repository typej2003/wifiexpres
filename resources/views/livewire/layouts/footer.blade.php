<div>
    <footer class="text-white">
        <div class="container-fluid px-0">
            
            <div class="row footer-main py-5 m-0 px-md-5 align-items-center">
                <div class="col-12 col-md-3 mb-4 mb-md-0 text-center text-md-start">
                    <img src="{{ asset('img/logo-wifiexpres.png') }}" class="img-fluid" style="max-width: 200px;" alt="Logo WiFiExpres">
                </div>
                <div class="col-12 col-md-3 mb-4 mb-md-0 text-center text-md-start">
                    <h5 class="fw-bold"><i class="bi bi-geo-alt-fill me-2"></i>Dirección</h5>
                    <p class="small mb-0">Av. Vollmer - Edif. Normandie - Piso 6 Ofic. 612<br>San Bernardino - Caracas</p>
                </div>
                <div class="col-12 col-md-3 mb-4 mb-md-0 text-center text-md-start">
                    <h5 class="fw-bold"><i class="bi bi-clock-fill me-2"></i>Horario</h5>
                    <p class="small mb-0">Lun - Vie: 8:00 a.m. - 5:00 p.m.<br>Sáb: 8:00 a.m. - 2:00 p.m.</p>
                </div>
                <div class="col-12 col-md-3 text-center text-md-start">
                    <h5 class="fw-bold"><i class="bi bi-envelope-fill me-2"></i>Correo</h5>
                    <a href="mailto:ddrsistemas@gmail.com" class="text-purpure text-decoration-none small">ddrsistemas@gmail.com</a>
                </div>
            </div>

            <div class="row py-3 footer-legal m-0 px-3">
                <div class="col-12 text-center">
                    <span class="small fw-bold text-white">El Servicio de internet es provisto por la empresa INVERSIONES RED NET 2030, C.A. habilitada por CONATEL con el número HGTS-00490</span>
                </div>
            </div>

            <div class="row py-4 align-items-center footer-branding m-0 px-md-5">
                <div class="col-md-3 text-center text-md-start mb-3 mb-md-0">
                    <img src="{{ asset('img/logo-ddr.png') }}" style="max-width: 120px;" onerror="this.src='https://placehold.co/120x40/ffffff/000?text=DDR';" alt="Logo DDR">
                </div>
                <div class="col-md-6 text-center mb-3 mb-md-0">
                    <p class="mb-0 small text-white">COPYRIGHT © DDR SISTEMAS C.A. RIF: J-31512955-8</p>
                    <p class="mb-0 opacity-75" style="font-size: 0.7rem;">V1.0.0.PV2</p>
                </div>
                <div class="col-md-3 text-center text-md-end">
                    <div class="social-icons footer-social-wrapper">
                        <a href="https://www.instagram.com/wifiexpres.ve/" class="social-circle"><i class="bi bi-instagram"></i></a>
                        <a href="https://www.facebook.com/profile.php?id=61586553973081" class="social-circle"><i class="bi bi-facebook"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <style>
        /* Fila 1: Texto Morado */
        .footer-main {
            color: #6500da !important;
        }
        .footer-main a {
            color: #6500da !important;
        }

        /* Fila 2: Naranja */
        .footer-legal {
            background-color: #ff572f !important;
        }

        /* Fila 3: Morado */
        .footer-branding {
            background-color: #6500da !important;
        }

        /* Redes Sociales */
        .social-icons .social-circle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            background-color: #ff572f; 
            color: white !important;
            border-radius: 50%;
            margin: 0 5px;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 1px solid #ff572f;
        }

        .social-icons .social-circle:hover {
            background-color: white !important;
            color: #ff572f !important;
            border-color: white;
            transform: translateY(-3px);
        }

        .footer-main h5 {
            font-size: 1.1rem;
        }

        /* --- AJUSTE DE SEPARACIÓN PARA WHATSAPP --- */
        .footer-social-wrapper {
            padding-right: 40px; /* Separación base en escritorio */
        }

        @media (max-width: 768px) {
            .footer-social-wrapper {
                /* Separación agresiva en móvil para librar el botón flotante */
                padding-right: 90px !important; 
                margin-top: 10px;
                display: block;
            }
        }
        
        @media (max-width: 480px) {
            .footer-social-wrapper {
                /* Aún más separación para pantallas muy pequeñas */
                padding-right: 100px !important;
            }
        }
    </style>
</div>