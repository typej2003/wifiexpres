<div>
    <div class="card card-outline card-primary">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-users mr-2 text-primary"></i> Usuarios Hotspot (Nuevos Registros)</h5>
            <div>
                <button wire:click="exportExcel" wire:loading.attr="disabled" class="btn btn-outline-success btn-sm rounded-pill px-4 fw-bold shadow-sm">
                    <span wire:loading wire:target="exportExcel" class="spinner-border spinner-border-sm me-1"></span>
                    <i wire:loading.remove wire:target="exportExcel" class="bi bi-file-earmark-spreadsheet me-1"></i> EXCEL
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-3">
                    <label>Buscar</label>
                    <input type="text" class="form-control" placeholder="Nombre, teléfono o email..." wire:model="search">
                </div>
                <div class="col-md-2">
                    <label>Filtro Tiempo</label>
                    <select class="form-control" wire:model="periodo">
                        <option value="ultimos_50">Últimos 50</option>
                        <option value="hoy">Hoy</option>
                        <option value="semana">Semana</option>
                        <option value="mes">Mes</option>
                        <option value="personalizado">Personalizado</option>
                    </select>
                </div>
                @if($periodo == 'personalizado')
                <div class="col-md-2">
                    <label>Desde</label>
                    <input type="date" class="form-control" wire:model="fecha_desde">
                </div>
                <div class="col-md-2">
                    <label>Hasta</label>
                    <input type="date" class="form-control" wire:model="fecha_hasta">
                </div>
                @endif

                @if($isAdmin)
                <div class="col-md-2">
                    <label>Aliado</label>
                    <select class="form-control" wire:model="selectedAliado">
                        <option value="">Todos los Aliados</option>
                        @foreach($aliados as $aliado)
                            <option value="{{ $aliado->id }}">{{ $aliado->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-2">
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