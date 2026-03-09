<div>
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">Planes / Perfiles de Usuarios</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/admin/dashboard">Escritorio</a></li>
                        <li class="breadcrumb-item active">Planes</li>
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
                        <button wire:click.prevent="addNew" class="btn btn-primary"><i class="fa fa-plus-circle mr-1"></i> Nuevo Plan</button>
                        <div></div>
                    </div>
                    <div class="card">
                        <div class="card-header">Host: <strong>{{$datos['host']}}</strong> Total de Planes: <strong>{{count($profilesUser)}}</strong></div>
                        <div class="card-body">
                            <div class="row ">
                                @foreach ($profilesUser as $key => $array) 
                                    <div class="col-md-3 col-4">
                                        <div class="card w-100 shadow">
                                            <div class="card-body">
                                                @foreach ($array as $clave => $element)                         
                                                    <strong><span class="fw-bold">{{ $clave }}:</span></strong>  {{ $element }} <br>
                                                @endforeach
                                            </div>
                                            <div class="card-footer d-flex justify-content-between">
                                                <!-- <button wire:click.prevent="addNewUserHotspot('{{ $array['name'] }}')" class="btn btn-primary"><i class="fa fa-users"></i> Nuevo ()</button>
                                                <button class="btn btn-success">Activos</button> -->
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
    <div class="modal fade" id="formProfileUser" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog" role="document">
            <form autocomplete="off" wire:submit.prevent="{{ $showEditModal ? 'updateProfile' : 'createProfile' }}">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">
                            @if($showEditModal)
                            <span>Editar Plan</span>
                            @else
                            <span>Nuevo Plan</span>
                            @endif
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        
                        <div class="form-group">
                            <label for="name">Plan</label>
                            <input type="text" wire:model.defer="state.name" class="form-control @error('name') is-invalid @enderror" id="name" aria-describedby="nameHelp" placeholder="Nombre del plan">
                            @error('name')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="addressPool">Grupo de direcciones</label>
                            <select name="addressPool" wire:model.defer="state.addressPool" class="form-control @error('addressPool') is-invalid @enderror" id="addressPool" wire:ignore.self>
                            </select>
                            @error('addressPool')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="sharedUsers">Usuarios compartidos</label>
                            <input type="text" wire:model.defer="state.sharedUsers" class="form-control @error('sharedUsers') is-invalid @enderror" id="sharedUsers" aria-describedby="Usuarios compartidos" placeholder="Nombre del plan">
                            @error('sharedUsers')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="sessionTimeout">Tiempo de espera</label>
                            <input type="text" wire:model.defer="state.sessionTimeout" class="form-control @error('sessionTimeout') is-invalid @enderror" id="sessionTimeout" aria-describedby="sessionTimeoutHelp" placeholder="Tiempo de espera de sesión">
                            @error('sessionTimeout')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="idleTimeout">Tiempo de espera inactivo</label>
                            <input type="text" wire:model.defer="state.idleTimeout" class="form-control @error('idleTimeout') is-invalid @enderror" id="idleTimeout" aria-describedby="idleTimeoutHelp" placeholder="Tiempo de espera inactivo">
                            @error('idleTimeout')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="keepaliveTimeout">Tiempo de espera de mantenimiento de conexión</label>
                            <input type="text" wire:model.defer="state.keepaliveTimeout" class="form-control @error('keepaliveTimeout') is-invalid @enderror" id="keepaliveTimeout" aria-describedby="keepaliveTimeoutHelp" placeholder="tiempo de espera de mantenimiento de conexión">
                            @error('keepaliveTimeout')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="statusAutorefresh">Estado de actualización automática</label>
                            <input type="text" wire:model.defer="state.statusAutorefresh" class="form-control @error('statusAutorefresh') is-invalid @enderror" id="statusAutorefresh" aria-describedby="statusAutorefreshHelp" placeholder="Estado de actualización automática">
                            @error('statusAutorefresh')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="rateLimitRxTx">Límite de velocidad Subida/Bajada</label>
                            <input type="text" wire:model.defer="state.rateLimitRxTx" class="form-control @error('rateLimitRxTx') is-invalid @enderror" id="rateLimitRxTx" aria-describedby="rateLimitRxTxHelp" placeholder="Nombre del plan">
                            @error('rateLimitRxTx')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="costo">Costo</label>
                            <input type="text" wire:model.defer="state.costo" class="form-control @error('costo') is-invalid @enderror" id="costo" aria-describedby="costoHelp" placeholder="Costo">
                            @error('costo')
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
            
            window.addEventListener('hide-formProfileUser', function (event) {
                $('#formProfileUser').modal('hide');
                toastr.success(event.detail.message, 'Success!');
            });
            
            window.addEventListener('show-formProfileUser', function (event) {

                let address = event.detail.addressPool
                
                let addressPool = document.getElementById('addressPool')
                
                let options = '';                
                
                options = '<option value="none">none</option>'
                address.forEach(element => {
                     options += `<option value="${element}">${element}</option>`
                });
                addressPool.innerHTML = options

                $('#formProfileUser').modal('show');            

            });
            
        }
    </script>
</div>
