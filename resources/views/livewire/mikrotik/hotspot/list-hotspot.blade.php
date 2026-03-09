<div>
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">Hotspot Mikrotik</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/admin/dashboard">Escritorio</a></li>
                        <li class="breadcrumb-item active">Hotspot Mikrotik</li>
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
                    <div class="d-flex justify-content-between mb-2">
                        <button wire:click.prevent="addNew" class="btn btn-primary"><i class="fa fa-plus-circle mr-1"></i> Nuevo Hotspot</button>
                        <x-search-input wire:model="searchTerm" />
                    </div>
                    <div class="card">
                        <div class="card-header">Host: <strong>{{$datos['host']}}</strong> Total de Hotspot: <strong>{{count($result)}}</strong></div>
                        <div class="card-body">
                            <div class="row ">
                                @foreach ($result as $key => $array) 
                                    <div class="col-md-3 col-4">
                                        <div class="card w-100 shadow">
                                            <div class="card-body">
                                                @foreach ($array as $clave => $element)                         
                                                    <strong><span class="fw-bold">{{ $clave }}:</span></strong>  {{ $element }} <br>
                                                @endforeach
                                            </div>
                                            <div class="card-footer d-flex justify-content-between">
                                                <button wire:click.prevent="addNewUserHotspot('{{ $array['name'] }}')" class="btn btn-primary"><i class="fa fa-users"></i> Nuevo ({{$this->cantUsershotspots($array['name'])}})</button>
                                                <button class="btn btn-success">Activos</button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="card-footer">
                            
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->

    <!-- Modal -->
    <div class="modal fade" id="formHotspot" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog" role="document">
            <form autocomplete="off" wire:submit.prevent="{{ $showEditModal ? 'updateHotspot' : 'createHotspot' }}">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">
                            @if($showEditModal)
                            <span>Editar Hotspot</span>
                            @else
                            <span>Nuevo Hotspot</span>
                            @endif
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="name">Hotspot</label>
                            <input type="text" wire:model.defer="state.name" class="form-control @error('name') is-invalid @enderror" id="name" aria-describedby="nameHelp" placeholder="Enter full name">
                            @error('name')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="interfaceH">Interface</label>
                            <select name="interfaceH" wire:model.defer="state.interfaceH" class="form-control @error('interfaceH') is-invalid @enderror" id="interfaceH" wire:ignore.self>
                            </select>
                            @error('interfaceH')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="profileH">Perfil</label>
                            <select name="profileH" wire:model.defer="state.profileH" class="form-control @error('profileH') is-invalid @enderror" id="profileH" wire:ignore>
                            </select>
                            @error('profileH')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
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
    <div class="modal fade" id="formUserHotspot" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog" role="document">
            <form autocomplete="off" wire:submit.prevent="{{ $showEditModal ? 'updateUserHotspot' : 'createUserHotspot' }}">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">
                            @if($showEditModal)
                            <span>Editar Usuario</span>
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
                            <label for="serverUH">Server</label>
                            <select name="serverUH" wire:model.defer="state.serverUH" class="form-control @error('serverUH') is-invalid @enderror" id="serverUH" wire:ignore>
                            </select>
                            @error('serverUH')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="nameUH">Usuario</label>
                            <input type="text" wire:model.defer="state.nameUH" class="form-control @error('nameUH') is-invalid @enderror" id="nameUH" aria-describedby="nameHelp" placeholder="Nombre">
                            @error('nameUH')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="profileUH">Perfil</label>
                            <select name="profileUH" wire:model.defer="state.profileUH" class="form-control @error('profileUH') is-invalid @enderror" id="profileUH" wire:ignore>
                            </select>
                            @error('profileUH')
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

    <script>
        window.onload = function() {
            
            window.addEventListener('hide-formHotspot', function (event) {
                $('#formHotspot').modal('hide');
                toastr.success(event.detail.message, 'Success!');
            });
            
            window.addEventListener('show-formHotspot', function (event) {

                let namesInterfaces = event.detail.namesInterfaces
                let namesProfiles = event.detail.namesProfiles

                let interfaceH = document.getElementById('interfaceH')
                let profileH = document.getElementById('profileH')
                
                let options = '';                
                
                options = '<option value="0">SELECCIONE..</option>'
                namesInterfaces.forEach(element => {
                    options += `<option value="${element}">${element}</option>`
                });
                interfaceH.innerHTML = options

                options = '<option value="0">SELECCIONE..</option>'
                namesProfiles.forEach(element => {
                    options += `<option value="${element}">${element}</option>`
                });
                profileH.innerHTML = options

                $('#formHotspot').modal('show');            

            });

            window.addEventListener('hide-formUserHotspot', function (event) {
                $('#formUserHotspot').modal('hide');
                toastr.success(event.detail.message, 'Success!');
            });

            window.addEventListener('show-formUserHotspot', function (event) {

                let namesProfilesUser = event.detail.namesProfilesUser
                let hotspots = event.detail.hotspots
                let nameserverUH = event.detail.nameserverUH

                let serverUH = document.getElementById('serverUH')
                let profileUH = document.getElementById('profileUH')
                while (serverUH.options.length > 0) {
                    serverUH.remove(0);
                }
                while (profileUH.options.length > 0) {
                    profileUH.remove(0);
                }
                let options = '';

                options = '<option value="all">Todos</option>'
                hotspots.forEach(element => {
                    options += `<option value="${element}">${element}</option>`
                });
                serverUH.innerHTML = options

                options = '<option value="0">SELECCIONE..</option>'
                namesProfilesUser.forEach(element => {
                    options += `<option value="${element}">${element}</option>`
                });
                profileUH.innerHTML = options

                $('#formUserHotspot').modal('show');
                
                for (let i = 0; i < serverUH.options.length; i++) {
                    if (serverUH.options[i].value === nameserverUH) {
                    serverUH.options[i].selected = true;
                    break; // Termina la iteración una vez encontrada la opción
                    }
                }

            });

            
        }
    </script>
</div>
