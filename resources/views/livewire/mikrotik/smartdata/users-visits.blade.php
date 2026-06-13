<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header pb-0">
                    <h5 class="mb-2">Clientes y Visitas</h5>
                    <p class="text-sm">Busca un cliente para ver su historial de visitas y comportamiento.</p>
                </div>
                <div class="card-body">
                    @if(!$selectedUser)
                        <!-- Buscador -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                                    <input type="text" class="form-control border-start-0 ps-0" 
                                        placeholder="Nombre o correo + Enter para buscar..." 
                                        wire:model.defer="search" 
                                        wire:keydown.enter="$refresh">
                                    <button class="btn btn-primary mb-0" type="button" wire:click="$refresh">Buscar</button>
                                </div>
                            </div>
                        </div>

                        <!-- Listado de Clientes -->
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Cliente</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Email</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($users as $user)
                                        <tr>
                                            <td class="px-4">
                                                <div class="d-flex flex-column">
                                                    <h6 class="mb-0 text-sm">{{ $user->name }}</h6>
                                                </div>
                                            </td>
                                            <td>
                                                <p class="text-xs font-weight-bold mb-0">{{ $user->email ?? 'N/A' }}</p>
                                            </td>
                                            <td class="align-middle text-center">
                                                <button class="btn btn-link text-info text-gradient px-3 mb-0" wire:click="selectUser({{ $user->id }})">
                                                    <i class="bi bi-eye-fill me-2"></i>Ver Historial
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center py-4 text-sm text-secondary">No se encontraron clientes.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 px-3">
                            {{ $users->links() }}
                        </div>
                    @else
                        <!-- Detalle del Cliente Seleccionado -->
                        <div class="mb-3">
                            <button class="btn btn-sm btn-link text-secondary ps-0" wire:click="deselectUser">
                                <i class="bi bi-arrow-left"></i> Volver al listado completo
                            </button>
                        </div>

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
                                            El cliente ha venido <span class="text-info">{{ $totalVisits }}</span> veces en total.
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
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
