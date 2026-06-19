<div>
    <style>
        @media only screen and (min-width: 768px) and (max-width: 991px) {
            .navbar-brand img {
                width: 150px;
            }
        }
        @media (max-width: 991px) {
            .navbar-brand img {
                width: 150px;
            }
        }
    </style>

    <div style="z-index: 20000; position: relative;">
        <nav class="navbar fixed-top bg-white border-bottom shadow-sm" style="height: 110px;">
            <div class="container-fluid px-3 d-flex align-items-center justify-content-between h-100">
                
                <div class="d-flex align-items-center">
                    <button class="btn btn-light d-lg-none me-2 btn-hamburguesa" 
                            onclick="window.dispatchEvent(new CustomEvent('toggleSidebar'))">
                        <i class="bi bi-list fs-3"></i>
                    </button>
                    
                    <a class="navbar-brand" href="/">
                        <img src="{{ asset('img/logo-wifiexpres.png') }}" style="max-height: 50px;" alt="Logo">
                    </a>
                </div>

                <div class="d-flex align-items-center">
                    <div class="dropdown">
                        <button class="nav-link dropdown-toggle d-flex align-items-center p-0 border-0 bg-transparent text-dark" 
                                type="button" id="userMenuButton" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            
                            <img src="{{ auth()->user()->avatar_url ?? asset('img/default-avatar.png') }}" 
                                class="rounded-circle me-2 shadow-sm" 
                                alt="User Image" 
                                style="width: 35px; height: 35px; object-fit: cover; border: 2px solid var(--purple-wifiexpres);">
                            
                            <span class="d-none d-sm-inline fw-bold">{{ auth()->user()->name }}</span>
                        </button>

                        <ul class="dropdown-menu dropdown-menu-end shadow custom-dropdown" aria-labelledby="userMenuButton">
                            <li class="dropdown-header text-purple fw-bold small">SESIÓN INICIADA</li>
                            
                            @if(auth()->user()->role == 'root' || auth()->user()->role == 'admin')
                                <li>
                                    <a class="dropdown-item" href="/admin/panel">
                                        <i class="bi bi-speedometer2 me-2"></i> Escritorio
                                    </a>
                                </li>
                            @endif

                            @if(auth()->user()->role == 'aliado' || auth()->user()->role == 'aliadoSmartData')
                                <li>
                                    <a class="dropdown-item" href="/dashboard-aliadoSmartData">
                                        <i class="bi bi-speedometer2 me-2"></i> Escritorio 2
                                    </a>
                                </li>
                            @endif

                            <li><hr class="dropdown-divider"></li>
                            
                            <li>
                                <form method="POST" action="{{ route('logout') }}" id="logout-form-nav">
                                    @csrf 
                                    <button type="submit" class="dropdown-item text-danger d-flex align-items-center w-100">
                                        <i class="bi bi-box-arrow-right me-2"></i> Salir
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
    </div>
</div>