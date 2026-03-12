<div class="container-fluid py-4">
    {{-- SELECTOR DE PESTAÑAS --}}
    <div class="row mb-4">
        <div class="col-12">
            <ul class="nav nav-pills nav-fill bg-white p-2 rounded-4 shadow-sm border">
                <li class="nav-item">
                    <button wire:click="setTab('ranking')" class="nav-link rounded-3 fw-bold {{ $activeTab === 'ranking' ? 'active bg-primary' : 'text-muted' }}">
                        <i class="bi bi-trophy-fill me-2"></i> RANKING DE FIDELIDAD
                    </button>
                </li>
                <li class="nav-item">
                    <button wire:click="setTab('locations')" class="nav-link rounded-3 fw-bold {{ $activeTab === 'locations' ? 'active bg-primary' : 'text-muted' }}">
                        <i class="bi bi-geo-fill me-2"></i> RASTREO POR ANTENA
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            {{-- CABECERA Y FILTROS --}}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold mb-0">
                        {{ $activeTab === 'ranking' ? 'Análisis de Recurrencia' : 'Ubicación de Usuarios' }}
                    </h3>
                    <p class="text-muted mb-0">
                        {{ $activeTab === 'ranking' ? 'Usuarios con más conexiones registradas.' : 'Seguimiento en tiempo real por segmento de red.' }}
                    </p>
                </div>
                
                <div class="d-flex align-items-center gap-3">
                    @if($activeTab === 'ranking')
                    <div class="form-check form-switch mb-0 me-2">
                        <input class="form-check-input" type="checkbox" wire:model="soloTickets" id="switchSoloTickets">
                        <label class="form-check-label small fw-bold text-muted" for="switchSoloTickets">OCULTAR MACs</label>
                    </div>
                    @endif

                    <div style="min-width: 280px;">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 rounded-start-pill"><i class="bi bi-search"></i></span>
                            <input wire:model="search" type="text" class="form-control border-start-0 rounded-end-pill" placeholder="Buscar...">
                        </div>
                    </div>
                </div>
            </div>

            {{-- CONTENIDO: RANKING --}}
            @if($activeTab === 'ranking')
            <div class="table-responsive">
                <table class="table align-middle table-hover">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Usuario</th>
                            <th>Router / Local</th>
                            <th class="text-center">
                                <button wire:click="toggleSort" class="btn btn-link text-decoration-none text-muted p-0 small fw-bold shadow-none">
                                    CONEXIONES <i class="bi {{ $sortDirection === 'desc' ? 'bi-arrow-down' : 'bi-arrow-up' }}"></i>
                                </button>
                            </th>
                            <th class="text-center">Tiempo Total</th>
                            <th>Última Visita</th>
                            <th class="text-end pe-4">Estatus</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($results as $u)
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-3">
                                        <i class="bi bi-person-badge"></i>
                                    </div>
                                    <span class="fw-bold">{{ $u->username }}</span>
                                </div>
                            </td>
                            <td>
                                <small class="fw-bold text-dark d-block">{{ $u->identity }}</small>
                                <small class="text-muted">{{ $u->comercio_nombre }}</small>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-dark rounded-pill px-3">{{ $u->total_conexiones }}</span>
                            </td>
                            <td class="text-center fw-medium text-dark">
                                {{ floor($u->tiempo_total / 3600) }}h {{ floor(($u->tiempo_total / 60) % 60) }}m
                            </td>
                            <td><small>{{ \Carbon\Carbon::parse($u->ultima_conexion)->diffForHumans() }}</small></td>
                            <td class="text-end pe-4">
                                @php
                                    $vip = $u->total_conexiones > 20 ? ['GOLD', 'warning'] : ($u->total_conexiones > 10 ? ['FRECUENTE', 'info text-white'] : ['REGULAR', 'light text-muted border']);
                                @endphp
                                <span class="badge bg-{{ $vip[1] }} shadow-sm">{{ $vip[0] }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center py-5">Sin datos disponibles.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- CONTENIDO: UBICACIONES --}}
            @else
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Usuario</th>
                            <th>Ubicación Detectada</th>
                            <th>IP / Segmento</th>
                            <th>Router</th>
                            <th>Conexión</th>
                            <th class="text-end pe-4">Duración</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($results as $log)
                        <tr>
                            <td class="ps-4"><span class="fw-bold text-primary">{{ $log->username }}</span></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-geo-alt-fill text-danger me-2 small"></i>
                                    <span class="fw-bold text-dark">{{ $log->ubicacion_fisica }}</span>
                                </div>
                            </td>
                            <td><code class="text-muted small">{{ $log->mac_address }}</code></td>
                            <td><small class="badge bg-light text-dark border">{{ $log->router->identity }}</small></td>
                            <td><small>{{ $log->created_at->format('d/m h:i A') }}</small></td>
                            <td class="text-end pe-4"><span class="small fw-bold">{{ $log->duracion_formateada }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center py-5">No hay movimientos registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @endif

            <div class="mt-4">
                {{ $results->links() }}
            </div>
        </div>
    </div>
</div>