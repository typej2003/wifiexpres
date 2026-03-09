<div class="container-fluid py-4 bg-gray-100 min-h-screen">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0 text-dark"><i class="bi bi-layers-half me-2"></i>Gestión de Habladores</h4>
            <p class="text-muted small mb-0">Usuario: <b>{{ auth()->user()->names }}</b></p>
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

    <div class="row">
        <div class="col-lg-9">
            <div class="row">
                @forelse($habladores as $h)
                    <div class="col-md-6 col-xl-4 mb-4" wire:key="hab-{{ $h->id }}">
                        <div class="card border-0 shadow-sm rounded-4 h-100 border-top border-4 border-primary">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="badge bg-light text-dark border px-3 rounded-pill uppercase" style="font-size: 0.65rem;">
                                        <i class="bi bi-tag-fill me-1 text-primary"></i> {{ $h->tipo }}
                                    </div>
                                    <button type="button" wire:click="editHablador({{ $h->id }})" class="btn btn-link text-muted p-0">
                                        <i class="bi bi-pencil-square h5"></i>
                                    </button>
                                </div>

                                <h5 class="fw-bold text-dark text-uppercase mb-1 text-truncate">{{ $h->nombre }}</h5>
                                <p class="text-muted small mb-3">{{ count($h->caracteristicas ?? []) }} elementos configurados</p>

                                <div class="mb-3">
                                    @if(!empty($h->recursos) && isset($h->recursos[0]))
                                        <a href="{{ asset('storage/'.$h->recursos[0]) }}" target="_blank" class="btn btn-sm btn-outline-secondary w-100 rounded-pill shadow-sm">
                                            <i class="bi bi-eye me-1"></i> Ver Multimedia
                                        </a>
                                    @else
                                        <span class="badge bg-light text-muted w-100 py-2 rounded-pill border">Sin archivos</span>
                                    @endif
                                </div>

                                <div class="dropdown">
                                    <button class="btn btn-primary w-100 rounded-pill fw-bold dropdown-toggle d-flex align-items-center justify-content-center gap-2" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-broadcast"></i> TRANSMITIR A...
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
                    <div class="col-12 text-center py-5 bg-white rounded-4 shadow-sm border border-dashed">
                        <i class="bi bi-megaphone text-muted opacity-25" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-2">No hay habladores creados aún.</p>
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

    @if($isModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.8); backdrop-filter: blur(5px); z-index: 9999; overflow-y: auto;">
            <div class="modal-dialog {{ $modalMode == 'hablador' ? 'modal-lg' : '' }}" style="margin-top: 8rem; margin-bottom: 5rem;">
                <div class="modal-content border-0 rounded-4 shadow-lg">
                    <div class="modal-header {{ $modalMode == 'hablador' ? 'bg-primary' : 'bg-dark' }} text-white p-4">
                        <h5 class="fw-bold mb-0 text-uppercase">Configurar {{ $modalMode }}</h5>
                        <button type="button" wire:click="closeModal" class="btn-close btn-close-white shadow-none"></button>
                    </div>
                    <div class="modal-body p-4">
                        @if($modalMode == 'hablador')
                            <div class="row g-3 mb-4">
                                <div class="col-md-8">
                                    <label class="form-label small fw-bold">Nombre Campaña</label>
                                    <input type="text" wire:model.defer="nombre" class="form-control rounded-3 border-2 shadow-sm">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Tipo</label>
                                    <select wire:model="tipo" class="form-select rounded-3 border-2 shadow-sm">
                                        <option value="imagen">IMAGEN / CARRUSEL</option>
                                        <option value="video">VIDEO ÚNICO</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-12 bg-light p-3 rounded-4 border">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="fw-bold text-primary small uppercase tracking-tighter">Items (Foto, Nombre, Precio, Oferta)</span>
                                    <button type="button" wire:click="agregarProducto" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm">+ Añadir</button>
                                </div>

                                @foreach($productos as $index => $p)
                                    <div class="card mb-2 border-0 shadow-sm rounded-3 overflow-hidden" wire:key="prod-{{ $index }}">
                                        <div class="card-body bg-white p-2">
                                            <div class="row g-2 align-items-center">
                                                <div class="col-auto">
                                                    <div class="position-relative border rounded bg-light d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; overflow: hidden;">
                                                        @if(isset($productos[$index]['imagen']) && !is_string($productos[$index]['imagen']))
                                                            <img src="{{ $productos[$index]['imagen']->temporaryUrl() }}" class="w-100 h-100 object-fit-cover">
                                                        @elseif(isset($p['imagen']) && is_string($p['imagen']))
                                                            <img src="{{ asset('storage/'.$p['imagen']) }}" class="w-100 h-100 object-fit-cover">
                                                        @else
                                                            <i class="bi bi-camera text-muted"></i>
                                                        @endif
                                                        <input type="file" wire:model="productos.{{$index}}.imagen" class="position-absolute top-0 start-0 opacity-0 w-100 h-100" style="cursor: pointer;">
                                                    </div>
                                                </div>
                                                <div class="col">
                                                    <input type="text" wire:model.defer="productos.{{$index}}.nombre" placeholder="Producto" class="form-control form-control-sm border-0 bg-light">
                                                </div>
                                                <div class="col-2">
                                                    <input type="text" wire:model.defer="productos.{{$index}}.precio" placeholder="Precio" class="form-control form-control-sm border-0 bg-light text-center">
                                                </div>
                                                <div class="col-3">
                                                    <input type="text" wire:model.defer="productos.{{$index}}.oferta" placeholder="Oferta" class="form-control form-control-sm border-0 bg-light text-center">
                                                </div>
                                                <div class="col-auto">
                                                    <button type="button" wire:click="removerProducto({{$index}})" class="btn btn-link text-danger p-0"><i class="bi bi-trash-fill"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Nombre Monitor (TV)</label>
                                <input type="text" wire:model.defer="pantalla_nombre" class="form-control rounded-3 border-2" placeholder="Ej: Barra Principal">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Slug URL de Acceso</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">/tv/</span>
                                    <input type="text" wire:model.defer="slug_pantalla" class="form-control rounded-3 border-2" placeholder="ej: pantalla-1">
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer bg-light p-4">
                        <button type="button" wire:click="closeModal" class="btn btn-light rounded-pill px-4 fw-bold shadow-sm">Cerrar</button>
                        <button type="button" wire:click="{{ $modalMode == 'hablador' ? 'storeHablador' : 'storePantalla' }}" class="btn btn-primary rounded-pill px-5 shadow fw-bold">GUARDAR</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <script src="https://cdn.socket.io/4.5.4/socket.io.min.js"></script>
    <script>
        const socket = io('http://localhost:3000');
        window.addEventListener('send-to-socket', e => { 
            socket.emit('lanzar-hablador', e.detail); 
        });
    </script>
</div>