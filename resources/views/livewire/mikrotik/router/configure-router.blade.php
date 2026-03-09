<div>
    <style>
        .secundary-card {
            background-color: grey !important;
            width: 200px !important;
            height: 200px !important;
            color: white !important;
        }
    </style>
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">Router: {{ $router->identity }}</h1>
                    <h4 class="m-0 text-dark">Ip: {{ $router->ip }}</h4>
                    <h4 class="m-0 text-dark">DNS: {{ $router->dns }} - ip: {{ gethostbyname($router->dns) }}</h4>
                    <h4 class="m-0 text-dark">Dirección Mac: {{ $router->macAddress }}</h4>
                    <h4 class="m-0 text-dark">Localización: {{ $router->location }}</h4>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/admin/dashboard">Escritorio</a></li>
                        <li class="breadcrumb-item active"><a href="/listRouters">Routers</a></li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>

    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <a href="/routerUsers/{{ $router->id }}">
                                        <div class="card secundary-card">
                                            <div class="card-body text-center">
                                                <h4>Usuario</h4>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="/routerHotspots/{{ $router->id }}">
                                        <div class="card secundary-card">
                                            <div class="card-body text-center">
                                                <h4>Hotspot</h4>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="/routerPlanes/{{ $router->id }}">
                                        <div class="card secundary-card">
                                            <div class="card-body text-center">
                                                <h4>Planes</h4>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="/routerPlanesAfiliado/{{ $router->id }}">
                                        <div class="card secundary-card">
                                            <div class="card-body text-center">
                                                <h4>Planes Afiliado</h4>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-end">
                            
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->

    <!-- Modal -->
    <div class="modal fade" id="form" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog" role="document">
            <form autocomplete="off" wire:submit.prevent="{{ $showEditModal ? 'updateUser' : 'createUser' }}">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">
                            @if($showEditModal)
                            <span>Editar Usuarios</span>
                            @else
                            <span>Nuevo Usuario</span>
                            @endif
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="name">Rol</label>
                            <select name="" wire:model.defer="state.role" class="form-control @error('role') is-invalid @enderror" id="">
                                <option value="0">SELECCIONE..</option>
                                <option value="admin">ADMINISTRADOR</option>
                                <option value="afiliado">AFILIADO</option>
                                <option value="user">USER</option>
                            </select>
                            @error('role')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="name">Usuario</label>
                            <input type="text" wire:model.defer="state.name" class="form-control @error('name') is-invalid @enderror" id="name" aria-describedby="nameHelp" placeholder="Enter full name">
                            @error('name')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="email">Correo Electrónico</label>
                            <input type="text" wire:model.defer="state.email" class="form-control @error('email') is-invalid @enderror" id="email" aria-describedby="emailHelp" placeholder="Enter email">
                            @error('email')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="password">Contraseña</label>
                            <input type="password" wire:model.defer="state.password" class="form-control @error('password') is-invalid @enderror" id="password" placeholder="Contraseña">
                            @error('password')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="passwordConfirmation">Confirme la Contraseña</label>
                            <input type="password" wire:model.defer="state.password_confirmation" class="form-control" id="passwordConfirmation" placeholder="Confirme la Contraseña">
                        </div>

                        
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-times mr-1"></i> Cancelar</button>
                        <button type="submit" class="btn btn-primary"><i class="fa fa-save mr-1"></i>
                            @if($showEditModal)
                            <span>Guardar Cambios</span>
                            @else
                            <span>Guardar</span>
                            @endif
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="confirmationModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5>Eliminar Usuario</h5>
                </div>

                <div class="modal-body">
                    <h4>Esta seguro de querer eliminar este usuario?</h4>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-times mr-1"></i> Cancelar</button>
                    <button type="button" wire:click.prevent="deleteUser" class="btn btn-danger"><i class="fa fa-trash mr-1"></i>Eliminar Usuario</button>
                </div>
            </div>
        </div>
    </div>
</div>
