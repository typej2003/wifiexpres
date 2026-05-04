<div class="container-fluid py-4 bg-gray-100 min-h-screen">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0 text-dark">
                <i class="bi bi-layers-half me-2 text-primary"></i>Panel de Habladores
            </h4>
            <p class="text-muted small mb-0">Sesión: <span class="badge bg-primary">{{ auth()->user()->role }}</span> <b>{{ auth()->user()->names }}</b></p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" wire:click="createPantalla" class="btn btn-dark shadow-sm rounded-pill px-4 fw-bold">
                <i class="bi bi-tv"></i> PANTALLAS
            </button>
            <button type="button" wire:click="createHablador" class="btn btn-primary shadow-sm rounded-pill px-4 fw-bold">
                <i class="bi bi-plus-lg"></i> NUEVO HABLADOR
            </button>
        </div>
    </div>

    @if(auth()->user()->role == 'admin')
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
        <div class="card-body p-3 d-flex align-items-center gap-3">
            <i class="bi bi-filter-circle-fill text-primary h4 mb-0"></i>
            <div class="flex-grow-1">
                <label class="small fw-bold text-muted uppercase" style="font-size: 0.6rem;">Filtrar por Aliado</label>
                <select wire:model="selectedAliado" class="form-select border-0 bg-light rounded-pill shadow-none">
                    <option value="">Todos los habladores del sistema</option>
                    @foreach($aliados as $aliado)
                        <option value="{{ $aliado->id }}">{{ $aliado->names }} ({{ $aliado->email }})</option>
                    @endforeach
                </select>
            </div>
            @if($selectedAliado)
            <button wire:click="$set('selectedAliado', '')" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                Limpiar Filtro
            </button>
            @endif
        </div>
    </div>
    @endif

    <div class="row">
        <div class="col-lg-9">
            <div class="row">
                @forelse($habladores as $h)
                    <div class="col-md-6 col-xl-4 mb-4" wire:key="hab-{{ $h->id }}">
                        <div class="card border-0 shadow-sm rounded-4 h-100 border-top border-4 {{ auth()->user()->id == $h->user_id ? 'border-primary' : 'border-secondary' }}">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="badge bg-light text-dark border px-3 rounded-pill uppercase" style="font-size: 0.65rem;">
                                        {{ $h->tipo }}
                                    </div>
                                    <div class="badge {{ $h->activo ? 'bg-success' : 'bg-danger' }} text-white px-2 rounded-pill ms-1" style="font-size: 0.6rem;">
                                        {{ $h->activo ? 'ACTIVO' : 'INACTIVO' }}
                                    </div>
                                    <button type="button" wire:click="editHablador({{ $h->id }})" class="btn btn-link text-muted p-0">
                                        <i class="bi bi-pencil-square h5"></i>
                                    </button>
                                </div>

                                <h5 class="fw-bold text-dark text-uppercase mb-1 text-truncate">{{ $h->nombre }}</h5>
                                
                                @if(auth()->user()->role == 'admin')
                                <p class="text-primary small mb-2" style="font-size: 0.7rem;">
                                    <i class="bi bi-person-circle"></i> {{ $h->aliado->names ?? 'Desconocido' }}
                                </p>
                                @endif

                                <p class="text-muted small mb-3">{{ count($h->caracteristicas ?? []) }} productos</p>

                                <div class="mb-3">
                                    @if(!empty($h->recursos) && isset($h->recursos[0]))
                                        <a href="{{ asset('storage/'.$h->recursos[0]) }}" target="_blank" class="btn btn-sm btn-outline-secondary w-100 rounded-pill">
                                            <i class="bi bi-eye"></i> Ver Multimedia
                                        </a>
                                    @endif
                                </div>

                                <div class="dropdown">
                                    <button class="btn btn-primary w-100 rounded-pill fw-bold dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        TRANSMITIR
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-dark shadow-lg border-0 rounded-3 w-100">
                                        @foreach($pantallas as $p)
                                            <li>
                                                <a class="dropdown-item d-flex justify-content-between py-2" href="#" wire:click.prevent="lanzarAPantalla({{ $h->id }}, {{ $p->id }})">
                                                    <span>{{ $p->nombre }}</span>
                                                    <i class="bi bi-play-fill text-warning"></i>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center py-5">
                        <i class="bi bi-layers text-muted h1"></i>
                        <p class="text-muted">No se encontraron habladores creados.</p>
                    </div>
                    @endforelse
            </div>
        </div>

        <div class="col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white sticky-top" style="top: 20px;">
                <h6 class="fw-bold text-muted mb-3 small uppercase tracking-wider">Mis Monitores</h6>
                @foreach($pantallas as $p)
                    <div class="p-3 rounded-4 bg-dark text-white mb-2 shadow-sm d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-0 fw-bold small uppercase text-warning">{{ $p->nombre }}</p>
                            <small class="opacity-50" style="font-size: 0.7rem;">/tv/{{ $p->slug_pantalla }}</small>
                        </div>
                        <i class="bi bi-circle-fill text-success" style="font-size: 0.5rem;"></i>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- MODAL DINÁMICO -->
    @if($isModalOpen)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog {{ $modalMode == 'hablador' ? 'modal-lg' : '' }}" style="margin-top: 8rem;">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-bold">{{ $modalMode == 'hablador' ? 'Configurar Hablador' : 'Nueva Pantalla' }}</h5>
                    <button type="button" wire:click="closeModal" class="btn-close"></button>
                </div>
                <div class="modal-body">
                    @if($modalMode == 'hablador')
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="small fw-bold">Nombre del Hablador</label>
                                <input type="text" wire:model="nombre" class="form-control rounded-pill">
                                @error('nombre') <span class="text-danger small ms-2">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="small fw-bold">Estado</label>
                                <select wire:model="activo" class="form-select rounded-pill">
                                    <option value="1">Activo</option>
                                    <option value="0">Inactivo</option>
                                </select>
                            </div>
                        </div>
                        
                        @if(auth()->user()->role == 'admin')
                        <div class="mb-3 mt-3">
                            <label class="small fw-bold">Asignar a Aliado</label>
                            <select wire:model="assigned_user_id" class="form-select rounded-pill">
                                <option value="">Seleccione un aliado...</option>
                                @foreach($aliados as $aliado)
                                    <option value="{{ $aliado->id }}">{{ $aliado->names }} ({{ $aliado->email }})</option>
                                @endforeach
                            </select>
                            @error('assigned_user_id') <span class="text-danger small ms-2">{{ $message }}</span> @enderror
                        </div>
                        @endif

                        <div class="mb-3 mt-3">
                            <label class="small fw-bold">Tipo de Contenido</label>
                            <select wire:model="tipo" class="form-select rounded-pill">
                                <option value="imagen">Imagen Estática</option>
                                <option value="carrusel">Carrusel de Imágenes</option>
                                <option value="video">Video</option>
                            </select>
                            @error('tipo') <span class="text-danger small ms-2">{{ $message }}</span> @enderror
                            <div class="alert alert-info py-1 px-2 mt-2 border-0 rounded-4" style="font-size: 0.7rem;">
                                <i class="bi bi-info-circle me-1"></i>
                                Define cómo se visualizarán los productos en la pantalla receptora.
                            </div>
                        </div>

                        <h6 class="fw-bold mt-4 mb-3 small text-muted text-uppercase">Productos / Características</h6>
                        @foreach($productos as $index => $prod)
                        <div class="card bg-light border-0 rounded-4 mb-2 p-3" wire:key="prod-item-{{ $index }}">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <input type="text" wire:model="productos.{{$index}}.nombre" class="form-control form-control-sm rounded-pill" placeholder="Nombre">
                                </div>
                                <div class="col-md-2">
                                    <input type="text" wire:model="productos.{{$index}}.precio" class="form-control form-control-sm rounded-pill" placeholder="Precio">
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="flex-shrink-0" style="width: 45px; height: 45px;">
                                            @if (isset($productos[$index]['imagen']) && $productos[$index]['imagen'])
                                                @if (is_object($productos[$index]['imagen']) && method_exists($productos[$index]['imagen'], 'temporaryUrl'))
                                                    {{-- Previsualización de nueva imagen cargada --}}
                                                    <img src="{{ $productos[$index]['imagen']->temporaryUrl() }}" class="img-thumbnail rounded-3 w-100 h-100 shadow-sm" style="object-fit: cover;">
                                                @elseif (is_string($productos[$index]['imagen']))
                                                    {{-- Previsualización de imagen existente en el servidor --}}
                                                    <img src="{{ asset('storage/' . $productos[$index]['imagen']) }}" class="img-thumbnail rounded-3 w-100 h-100 shadow-sm" style="object-fit: cover;">
                                                @endif
                                            @else
                                                <div class="bg-white rounded-3 d-flex align-items-center justify-content-center w-100 h-100 border border-dashed text-muted">
                                                    <i class="bi bi-image small"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="flex-grow-1">
                                            <label class="small text-muted mb-0" style="font-size: 0.65rem;">Sube JPG o PNG (Máx 2MB)</label>
                                            <input type="file" wire:model="productos.{{$index}}.imagen" class="form-control form-control-sm rounded-pill">
                                            <div wire:loading wire:target="productos.{{$index}}.imagen" class="text-primary mt-1 fw-bold" style="font-size: 0.6rem;">
                                                <i class="bi bi-arrow-repeat spin"></i> Subiendo...
                                            </div>
                                            @error("productos.$index.imagen") <span class="text-danger d-block mt-1" style="font-size: 0.6rem;">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2 text-end">
                                    @if(count($productos) > 1)
                                    <button wire:click="removerProducto({{$index}})" class="btn btn-sm btn-outline-danger rounded-circle">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                        
                        <button wire:click="agregarProducto" class="btn btn-sm btn-link text-primary fw-bold p-0 mt-2">
                            <i class="bi bi-plus-circle"></i> Agregar otro producto
                        </button>
                    @else
                        <div class="mb-3">
                            @if(auth()->user()->role == 'admin')
                            <div class="mb-3">
                                <label class="small fw-bold">Asignar a Aliado</label>
                                <select wire:model="assigned_user_id" class="form-select rounded-pill">
                                    <option value="">Seleccione un aliado...</option>
                                    @foreach($aliados as $aliado)
                                        <option value="{{ $aliado->id }}">{{ $aliado->names }} ({{ $aliado->email }})</option>
                                    @endforeach
                                </select>
                                @error('assigned_user_id') <span class="text-danger small ms-2">{{ $message }}</span> @enderror
                            </div>
                            @endif
                            <label class="small fw-bold">Nombre de la Pantalla (Referencia)</label>
                            <input type="text" wire:model="pantalla_nombre" class="form-control rounded-pill">
                        </div>
                        <div class="mb-3">
                            <label class="small fw-bold">Slug / Identificador (Debe ser único)</label>
                            <input type="text" wire:model="slug_pantalla" class="form-control rounded-pill" placeholder="ej: pantalla-recepcion">
                        </div>
                        <div class="mb-3">
                            <label class="small fw-bold">Orientación</label>
                            <select wire:model="orientation" class="form-select rounded-pill">
                                <option value="landscape">Horizontal (Landscape)</option>
                                <option value="portrait">Vertical (Portrait)</option>
                            </select>
                        </div>
                    @endif
                </div>
                <div class="modal-footer border-0">
                    <button wire:click="closeModal" class="btn btn-light rounded-pill px-4">Cancelar</button>
                    @if($modalMode == 'hablador')
                        <button wire:click="storeHablador" class="btn btn-primary rounded-pill px-4">Guardar Hablador</button>
                    @else
                        <button wire:click="storePantalla" class="btn btn-dark rounded-pill px-4">Guardar Pantalla</button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>