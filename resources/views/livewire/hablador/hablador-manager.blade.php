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
                                    <button type="button" wire:click="editHablador({{ $h->id }})" class="btn btn-link text-muted p-0">
                                        <i class="bi bi-pencil-square h5"></i>
                                    </button>
                                </div>

                                <h5 class="fw-bold text-dark text-uppercase mb-1 text-truncate">{{ $h->nombre }}</h5>
                                
                                @if(auth()->user()->role == 'admin')
                                <p class="text-primary small mb-2" style="font-size: 0.7rem;">
                                    <i class="bi bi-person-circle"></i> {{ $h->user->names ?? 'Desconocido' }}
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

    </div>