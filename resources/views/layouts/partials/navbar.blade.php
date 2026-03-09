<div>

{{$this->prueba()}}

<header class="container-fluid navbarwelcome">
    <nav class="row navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid align-text-center">
            <a class="navbar-brand img-logo mx-auto" href="/">
                <img class="" src="/img/logo_respuestos.png" alt="">
            
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <div class="row container-fluid">
                    <div class="col-xl-12">
                        <div class="row d-flex justify-content-between">
                            <div class="col-xl-6 border border-2">
                                <div class="row">
                                    <div class="col-xl-12">
                                    <form class="d-flex">
                                        <input class="form-control me-2 input-search" type="search" placeholder="Search" aria-label="Search">
                                        <!--       select category  -->
                                        <div class="selectCategory dropdown">
                                            <a class="nav-link dropdown-toggle d-flex justify-content-center align-items-center" href="#" id="navbarCategory" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                <div class="category mx-1"></div>
                                            </a>
                                            <ul class="dropdown-menu categoryList border-0 my-0" aria-labelledby="navbarCategory">
                                            </ul>
                                        </div>
                                        <!--       select category  -->
                                        <button class="btn btn-outline-success" type="submit">Search</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="dropdown col-xl-3 border border-2 d-flex justify-content-center align-items-center">
                                @auth
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle color_1 text_menu fw-bold" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    @if(auth()->user()->avatar)
                                        <img src="{{ auth()->user()->avatar_url }}" id="profileImage" class="img-circle elevation-1" alt="User Image" style="height: 30px; width: 30px;">
                                    @else
                                        <img src="/img/icon_miperfil.png" id="profileImage" class="nav-img img-circle elevation-1" alt="User Image" style="">
                                    @endif
                                    {{ auth()->user()->name }}
                                    </a>
                                    <ul class="dropdown-menu">
                                        <a class="dropdown-item" href="{{ route('admin.profile.edit') }}" x-ref="profileLink">Perfil</a>
                                        <a class="dropdown-item" href="/admin/dashboard">Escritorio</a>
                                        <a class="dropdown-item" href="{{ route('admin.profile.edit') }}" x-ref="changePasswordLink">Cambiar Contraseña</a>
                                        <a class="dropdown-item" href="{{ route('admin.settings') }}">Configuración</a>
                                        <div class="dropdown-divider"></div>
                                        <form method="POST" action="{{ route('logout') }}">
                                            <a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault(); this.closest('form').submit();">Salir</a>
                                        </form>
                                        
                                    </ul>
                                    </li>
                                @else
                                <!--       select category  -->
                                <div><img class="nav-img" src="/img/icon_miperfil.png" alt=""></div>
                                <div class="">
                                    <button class="dropbtn acceso">Acceso / Registrase</button>
                                    <div class="dropdown-content bg-success">
                                        <div class="card" style="width: 32rem;">
                                            <div class="card-body">
                                                <div class="col-lg-12 titulo c-a text-center h2 pt-3">Ingresa a tu PagoExprés</div>
                                                <p class="text-center textoreg">¿Todavía no te has registrado? <span><a href="/register" class="c-n">Crea tu cuenta Aquí</a></span></p>
                                                            
                                                    <form action="{{ route('login') }}" method="POST">
                                                        @csrf
                                                        <div class="form-group">
                                                            <div class="row mx-auto">
                                                                <div class="col-xs-6 col-md-4 col-sm-4 col-4">
                                                                    <label for="tipodocumento">Tipo </label>
                                                                    <select class="form-control inputForm inputType" name="" id="identificationNac" placeholder="Tipo">
                                                                        <option value="J">J-</option>
                                                                        <option value="E">E-</option>
                                                                        <option value="G">G-</option>
                                                                        <option value="P">P-</option>
                                                                        <option value="V" selected>V-</option>
                                                                    </select>
                                                                </div>
                                                                <div class="col-xs-6 col-md-8 col=sm-8 col-8">
                                                                    <label for="documento">Documento</label>
                                                                    <input type="text" id="identificationNumber" class="form-control inputForm" placeholder="Documento" autocomplete="off">
                                                                </div>
                                                            </div>
                                                            
                                                        </div>
                                                
                                                        <div class="form-group">
                                                            <div class="row mx-auto" >
                                                                <div class="col-xs-12 col-sm-12 col-md-12">
                                                                    <label for="email">Correo Electrónico</label>
                                                                    <input type="email" name="email" class="form-control inputForm" placeholder="Correo Electrónico" id="email" autocomplete="off" value="typej2003@gmail.com">
                                                                </div>
                                                            </div>
                                                            @error('email')
                                                            <span class="text-danger">{{ $message }}</span>
                                                            @enderror
                                                        </div>
                                            
                                                        <div class="form-group">
                                                            <div class="row mx-auto">
                                                                <div class="col-xs-12 col-sm-12 col-md-12">
                                                                    <label for="password">Contraseña</label>
                                                                    <input type="password" name="password" id="password-field" class="form-control inputForm" placeholder="Contraseña" value="12345678"/ autocomplete="off">
                                                                </div>
                                                            </div>                
                                                        </div>
                                                            
                                                        <div class="form-group">
                                                            <div class="row mx-auto my-3">
                                                                <div class="col-xs-12 col-sm-12 col-md-12">
                                                                    <button class="btn boton1 w-100">Iniciar Sesión Aquí</button>
                                                                </div>
                                                            </div>                
                                                        </div>
                                                    </form>

                                                <div class="modal-footer" style="background-color: #eb6c0e;">
                                                    Contactar a soporte si no puedes iniciar sesión
                                                </div>
                                                
                                                
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!--       select category  -->
                                @endif
                            </div>

                            <div class="col-xl-2 border border-2 d-flex justify-content-center align-items-center">
                                <img class="nav-img" src="/img/icon_heart.png" alt="">
                            </div>
                            <div class="col-xl-1 border border-2 d-flex justify-content-center align-items-center">
                                <img class="nav-img" src="/img/icon_carrito.png" alt="">
                                <span>$0.00</span>
                            </div>

                        </div>
                    </div>
                </div>            
            </div>  
        </div>
    </nav>
    <nav class="nav d-flex justify-content-between sectionCategory">
        <ul class="nav nav-pills nav-fill w-80">
            <li class="nav-item dropdown">            
                <a class="dropdownLink my-1 nav-link">Categorias 1</a>
                <div class="dropdown-content-link">
                    <a href="#">Link 1.1</a>
                    <a href="#">Link 1.2</a>
                    <a href="#">Link 1.3</a>
                </div>            
            </li>
            <li class="nav-item dropdown">            
                <a class="dropdownLink my-1 nav-link">Categorias 2</a>
                <div class="dropdown-content-link">
                    <a href="#">Link 2.1</a>
                    <a href="#">Link 2.2</a>
                    <a href="#">Link 2.3</a>
                </div>            
            </li>
            <li class="nav-item dropdown">            
                <a class="dropdownLink my-1 nav-link">Categorias 3</a>
                <div class="dropdown-content-link">
                    <a href="#">Link 3.1</a>
                    <a href="#">Link 3.2</a>
                    <a href="#">Link 3.3</a>
                </div>            
            </li>
            <li class="nav-item dropdown">            
                <a class="dropdownLink my-1 nav-link">Categorias 4</a>
                <div class="dropdown-content-link">
                    <a href="#">Link 4.1</a>
                    <a href="#">Link 4.2</a>
                    <a href="#">Link 4.3</a>
                </div>            
            </li>
            <li class="nav-item dropdown">            
                <a class="dropdownLink my-1 nav-link">Categorias 5</a>
                <div class="dropdown-content-link">
                    <a href="#">Link 5.1</a>
                    <a href="#">Link 5.2</a>
                    <a href="#">Link 5.3</a>
                </div>            
            </li>
            <li class="nav-item dropdown"><span class="nav-link">$: {{$dolar=1}} Bs.</span></li>
        </ul>
        <ul class="nav w-20 bg-success" wire:ignore>
            <li class="nav-item dropdown mr-5">
                <a class="nav-link dropdown-toggle d-flex justify-content-center align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span>Moneda: </span><div class="currency mx-1"></div>
                </a>
                <ul class="dropdown-menu currencyList border-0 my-0" aria-labelledby="navbarDropdown">
                </ul>
            </li>
        </ul>
    </nav>
</header> 


    


</div>