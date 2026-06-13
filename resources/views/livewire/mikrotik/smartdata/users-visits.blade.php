<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header pb-0">
                    <h5 class="mb-2">Clientes y Visitas</h5>
                    <p class="text-sm">Busca un cliente para ver su historial de visitas y comportamiento.</p>
                </div>
                <div class="card-body">
                    <!-- Buscador -->
                    <div class="row mb-4">
                        <div class="col-md-6 position-relative">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control border-start-0 ps-0" placeholder="Escriba nombre o correo del cliente..." wire:model.debounce.500ms="search">
                            </div>
                            @if(strlen($search) > 2 && count($usersSearch) > 0)
                                <ul class="list-group mt-1 position-absolute w-100 shadow" style="z-index: 1000;">
                                    @foreach($usersSearch as $u)
                                        <li class="list-group-item list-group-item-action cursor-pointer" wire:click="selectUser({{ $u->id }})" style="cursor: pointer;">
                                            <i class="bi bi-person me-2"></i> {{ $u->name }} <small class="text-muted">({{ $u->email ?? 'Sin correo' }})</small>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>

                    @if($selectedUser)
                        <!-- Ficha del Cliente -->
                        <div class="p-3 bg-light border-radius-lg mb-4">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <div class="avatar avatar-xl position-relative bg-gradient-info border-radius-lg d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                        <i class="bi bi-person-check-fill text-white fs-2"></i>
                                    </div>
                                </div>
                                <div class="col my-auto">
                                    <div class="h-100">
                                        <h5 class="mb-1 text-dark">{{ $selectedUser->name }}</h5>
                                        <p class="mb-0 font-weight-bold text-sm">
                                            <span class="text-info">{{ $selectedUser->name }}</span>, ha venido <span class="text-dark">{{ $totalVisits }}</span> veces en total.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tabla de Visitas -->
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Fecha y Hora</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Cuánto tiempo estuvo</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Periodicidad</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($visits as $index => $visit)
                                        <tr>
                                            <td class="align-middle">
                                                <span class="text-secondary text-xs font-weight-bold px-3">{{ $visit->created_at->format('d/m/Y h:i A') }}</span>
                                            </td>
                                            <td class="align-middle">
                                                <span class="text-secondary text-xs font-weight-bold">{{ $visit->uptime ?? '00:00:00' }}</span>
                                            </td>
                                            <td class="align-middle">
                                                <span class="text-secondary text-xs font-weight-bold">
                                                    @if(isset($visits[$index + 1]))
                                                        Vuelve cada {{ $visit->created_at->diffInDays($visits[$index + 1]->created_at) }} días
                                                    @else
                                                        Primera visita registrada
                                                    @endif
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center py-4 text-sm text-secondary">No se encontraron registros de visitas para este usuario.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-people text-secondary opacity-3" style="font-size: 4rem;"></i>
                            <p class="mt-3 text-secondary">Inicie una búsqueda para ver los detalles y el historial de visitas del cliente.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
