<div>
    <div class="card card-outline card-primary">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-users mr-2"></i> Usuarios Hotspot (Nuevos Registros)</h5>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-4">
                    <label>Buscar</label>
                    <input type="text" class="form-control" placeholder="Nombre, teléfono o email..." wire:model="search">
                </div>
                @if($isAdmin)
                <div class="col-md-3">
                    <label>Aliado</label>
                    <select class="form-control" wire:model="selectedAliado">
                        <option value="">Todos los Aliados</option>
                        @foreach($aliados as $aliado)
                            <option value="{{ $aliado->id }}">{{ $aliado->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-3">
                    <label>Router</label>
                    <select class="form-control" wire:model="selectedRouter">
                        <option value="">Todos los Routers</option>
                        @foreach($routers as $router)
                            <option value="{{ $router->id }}">{{ $router->identity }} ({{ $router->location }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>Registro</th>
                            <th>Nombre Completo</th>
                            @if($isAdmin && !$selectedAliado)
                                <th>Aliado</th>
                            @endif
                            <th>Router / Server</th>
                            <th>Teléfono</th>
                            <th>Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $userMikrotik)
                        <tr>
                            <td>{{ $userMikrotik->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <strong>{{ $userMikrotik->full_name ?? 'N/A' }}</strong>
                                <br>
                                <small class="text-muted"><i class="fas fa-id-card mr-1"></i>{{ $userMikrotik->name }}</small>
                            </td>
                            @if($isAdmin && !$selectedAliado)
                                <td>{{ $userMikrotik->router->user->name ?? 'Sistema' }}</td>
                            @endif
                            <td>
                                <span class="badge badge-info">{{ $userMikrotik->server }}</span>
                                <br>
                                <small>{{ $userMikrotik->router->identity ?? 'N/A' }}</small>
                            </td>
                            <td>
                                @if($userMikrotik->cellphone)
                                    +{{ $userMikrotik->cellphonecode }} {{ $userMikrotik->cellphone }}
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>{{ $userMikrotik->email ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ ($isAdmin && !$selectedAliado) ? 6 : 5 }}" class="text-center py-4">
                                <div class="text-muted">No se encontraron usuarios registrados.</div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3">
                {{ $users->links() }}
            </div>
        </div>
    </div>
</div>