<div>
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><i class="fa fa-regular fa-envelope"></i> Notificaciones</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/admin/dashboard">Escritorio</a></li>
                        <li class="breadcrumb-item active"><a href="/listComercios/{{$comercio->id}}">Notificaciones</a></li>
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
                        <button wire:click.prevent="addNew" class="btn btn-primary"><i class="fa fa-plus-circle mr-1"></i> Nueva Notificación</button>
                        <x-search-input wire:model="searchTerm" />
                    </div>
                    <div class="card" style="width: 100% !important;">
                        <div class="card-body">
                            <table class="table table-hover table-bordered table-responsive">
                                <thead>
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">
                                            Medio
                                            <span wire:click="sortBy('medio')" class="float-right text-sm" style="cursor: pointer;">
                                                <i class="fa fa-arrow-up {{ $sortColumnName === 'medio' && $sortDirection === 'asc' ? '' : 'text-muted' }}"></i>
                                                <i class="fa fa-arrow-down {{ $sortColumnName === 'medio' && $sortDirection === 'desc' ? '' : 'text-muted' }}"></i>
                                            </span>
                                        </th>
                                        <th scope="col">
                                            Notificación
                                            <span wire:click="sortBy('title')" class="float-right text-sm" style="cursor: pointer;">
                                                <i class="fa fa-arrow-up {{ $sortColumnName === 'title' && $sortDirection === 'asc' ? '' : 'text-muted' }}"></i>
                                                <i class="fa fa-arrow-down {{ $sortColumnName === 'title' && $sortDirection === 'desc' ? '' : 'text-muted' }}"></i>
                                            </span>
                                        </th>
                                        <th scope="col">Adjunto</th>
                                        <th scope="col">Comercio</th>
                                        <th scope="col">Nro Clientes</th>
                                        <th scope="col">Nro Envios</th>
                                        <th scope="col">Fecha de Registro</th>
                                        <th scope="col">Opciones</th>
                                    </tr>
                                </thead>
                                <tbody wire:loading.class="text-muted">
                                    @forelse ($notificaciones as $index => $notificacion)
                                    <tr>
                                        <th scope="row">{{ $notificaciones->firstItem() + $index }}</th>
                                        <td>{{ $notificacion->title }}</td>
                                        <td>{{ $notificacion->medio }}</td>
                                        <td>{{ $notificacion->adjunto }}</td>
                                        <td>{{ $notificacion->comercio->name }}</td>
                                        <td>{{ $notificacion->nroclientes() }}</td>
                                        <td>{{ $notificacion->nrosends }}</td>
                                        <td>{{ $notificacion->created_at ?? 'N/A' }}</td>
                                        <td>
                                            <a href="" wire:click.prevent="sendNotificacion({{ $notificacion }})">
                                                <img class="mr-2" style="width: 25px;" src="/img/icon-send.png" alt="">
                                            </a>
                                            <a href="" wire:click.prevent="edit({{ $notificacion }})">
                                                <i class="fa fa-edit mr-2"></i>
                                            </a>

                                            <a href="" wire:click.prevent="confirmNotificacionRemoval({{ $notificacion->id }})">
                                                <i class="fa fa-trash text-danger"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr class="text-center">
                                        <td colspan="8">
                                            <img src="https://42f2671d685f51e10fc6-b9fcecea3e50b3b59bdc28dead054ebc.ssl.cf5.rackcdn.com/v2/assets/empty.svg" alt="No results found" style="width: 150px;">
                                            <p class="mt-2">No se encontro resultado</p>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer d-flex justify-content-end">
                            {{ $notificaciones->links() }}
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
            <!-- <form autocomplete="off" wire:submit.prevent="{{ $showEditModal ? 'updateNotificacion' : 'createNotificacion' }}"> -->
            <form wire:submit.prevent="saveNotificacion" method="POST"   enctype="multipart/form-data">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">
                            @if($showEditModal)
                            <span>Editar Notificación</span>
                            @else
                            <span>Nuevo Notificación</span>
                            @endif
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="medio">Medio</label>
                            <select wire:model.defer="state.medio" id="medio" autofocus class="form-control @error('medio') is-invalid @enderror">
                                <option value="0">Seleccione</option>
                                <option value="sms">Sms</option>
                                <option value="email">Email</option>
                                <option value="whatsapp">Whatsapp</option>
                            </select>
                            @error('medio')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                            <script>
                            let select = document.querySelector('#medio')
                            select.addEventListener('change', function(event){
                                var selectElement = event.target.value;
                                let file = document.querySelector('.file')
                                if(selectElement !== 'sms' && selectElement !== '0'){
                                    file.classList.remove("d-none");
                                    @this.d_none = ''
                                }else{
                                    file.classList.add("d-none");
                                    @this.d_none = 'd-none'
                                }                                
                            })
                        </script>
                        </div>

                        <div class="form-group">
                            <label for="title">Título</label>
                            <input type="text" wire:model.defer="state.title" id="title" autofocus class="form-control @error('title') is-invalid @enderror">
                            @error('title')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="content">Contenido</label>
                            <textarea wire:model.defer="state.content" id="content" autofocus class="form-control @error('content') is-invalid @enderror"></textarea>
                            @error('content')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div class="form-group {{ $d_none }} file">
                                                           
                                <div class="form-group {{ $d_none }} file">
                                    <label for="customFile">Archivo </label>
                                    <input wire:model="file" type="file" class="form-control" id="file" name="file">
                                </div>
                                <div class="form-group">
                                    <button type="submit" class="btn btn-success">
                                        @if($showEditModal)
                                        <span>Guardar Cambios</span>
                                        @else
                                        <span>Guardar</span>
                                        @endif
                                    </button>
                                </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-times mr-1"></i> Cancelar</button>
                        <button type="submit" class="btn btn-app"><i class="fa fa-save mr-1"></i>
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
                <div class="modal-header text-white" >
                    <h5>Eliminar Notificación</h5>
                </div>

                <div class="modal-body">
                    <h4>Esta usted seguro de querer eliminar esta Notificación?</h4>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-times mr-1"></i> Cancelar</button>
                    <button type="button" wire:click.prevent="deleteNotificacion" class="btn btn-danger"><i class="fa fa-trash mr-1"></i>Eliminar Notificación</button>
                </div>
            </div>
        </div>
    </div>
</div>
