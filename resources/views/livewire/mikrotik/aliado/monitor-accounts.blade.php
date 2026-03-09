<div class="container-fluid py-4">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold text-dark m-0">
                    <i class="bi bi-geo-alt-fill text-danger"></i> Monitor de Ubicación de Clientes
                </h4>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('aliado.antenas.config') }}" class="btn btn-outline-secondary btn-sm shadow-sm">
                        <i class="bi bi-gear-fill"></i> Configurar Antenas
                    </a>
                    @if($viewMode == 'realtime')
                        <span class="badge bg-danger animate__animated animate__flash animate__infinite">MODO: VIVO</span>
                    @else
                        <span class="badge bg-primary">MODO: HISTORIAL (Incl. últimos 5 min)</span>
                    @endif
                </div>
            </div>
            
            <div class="row align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-bold small">Aliado</label>
                    <select wire:model="aliadoId" class="form-select">
                        <option value="">-- Seleccionar --</option>
                        @foreach($aliados as $aliado)
                            <option value="{{ $aliado->id }}">{{ $aliado->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-9 text-end">
                    @if($aliadoId && count($routers) > 0)
                        <div class="btn-group shadow-sm">
                            <button wire:click="loadFromDatabase" class="btn btn-outline-primary {{ $viewMode == 'database' ? 'active' : '' }}">
                                <i class="bi bi-database"></i> Ver Historial
                            </button>
                            <button wire:click="scanAllRouters" wire:loading.attr="disabled" class="btn btn-primary">
                                <span wire:loading.remove wire:target="scanAllRouters">
                                    <i class="bi bi-broadcast"></i> Escaneo en Vivo
                                </span>
                                <span wire:loading wire:target="scanAllRouters">
                                    <span class="spinner-border spinner-border-sm"></span> Escaneando...
                                </span>
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($loading)
        <div class="text-center py-5">
            <div class="spinner-grow text-primary" role="status"></div>
            <p class="mt-2 fw-bold text-primary">Obteniendo datos...</p>
        </div>
    @elseif(count($results) > 0)
        @foreach($results as $routerId => $data)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-dark text-white p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0 fw-bold">{{ $data['db_name'] }}</h5>
                            <small class="text-info">ID: {{ $data['identity'] }} | {{ $data['location'] }}</small>
                        </div>
                        <span class="badge bg-success px-3">{{ count($data['users']) }} Registros</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Status</th>
                                    <th>Cliente</th>
                                    <th>Ubicación / Antena</th>
                                    <th>IP (Detectada)</th>
                                    <th>{{ $viewMode == 'realtime' ? 'Uptime' : 'Evento' }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($data['users'] as $u)
                                    <tr class="{{ !$u['online'] ? 'table-light opacity-75' : '' }}">
                                        <td class="ps-3 text-center">
                                            @if($u['online'])
                                                <span class="badge rounded-pill bg-success p-1"><span class="visually-hidden">Online</span></span>
                                            @else
                                                <span class="badge rounded-pill bg-secondary p-1"><span class="visually-hidden">Offline</span></span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="fw-bold {{ $u['online'] ? 'text-dark' : 'text-muted' }}">{{ $u['user'] }}</div>
                                            <div class="text-muted small" style="font-size: 0.7rem;">{{ $u['mac'] }}</div>
                                        </td>
                                        <td>
                                            @if($u['antenna'] == $u['ip'] || $u['antenna'] == 'Antena no mapeada')
                                                <span class="text-muted small italic">No mapeada</span>
                                            @else
                                                <span class="fw-bold text-danger">
                                                    <i class="bi bi-geo-fill"></i> {{ $u['antenna'] }}
                                                </span>
                                            @endif
                                        </td>
                                        <td><code class="small fw-bold">{{ $u['ip'] }}</code></td>
                                        <td><i class="bi bi-clock"></i> {{ $u['uptime'] }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center py-4 text-muted">Sin actividad reciente.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
    @endif
</div>