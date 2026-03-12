<div class="container-fluid py-4">
    {{-- SELECTOR DE PESTAÑAS --}}
    <div class="row mb-4">
        <div class="col-12 text-center">
            <div class="btn-group bg-white p-1 rounded-pill shadow-sm border">
                <button wire:click="setTab('ranking')" class="btn {{ $activeTab === 'ranking' ? 'btn-primary' : 'btn-light text-muted' }} rounded-pill px-4 fw-bold shadow-none">
                    <i class="bi bi-bar-chart-line-fill me-2"></i> RANKING DE FIDELIDAD
                </button>
                <button wire:click="setTab('locations')" class="btn {{ $activeTab === 'locations' ? 'btn-primary' : 'btn-light text-muted' }} rounded-pill px-4 fw-bold shadow-none">
                    <i class="bi bi-geo-alt-fill me-2"></i> RASTREO POR ANTENA
                </button>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            {{-- CABECERA Y FILTROS UNIFICADOS --}}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold mb-0 text-dark">
                        {{ $activeTab === 'ranking' ? 'Top Usuarios Recurrentes' : 'Historial de Ubicaciones' }}
                    </h4>
                    <p class="text-muted small mb-0">
                        Mostrando registros de los routers bajo su administración.
                    </p>
                </div>
                
                <div class="d-flex align-items-center gap-4">
                    {{-- Switch de Filtro MAC (Visible en ambas Tabs) --}}
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" wire:model="soloTickets" id="swTicketsGlobal">
                        <label class="form-check-label small fw-bold text-muted" for="swTicketsGlobal">SOLO TICKETS</label>
                    </div>

                    {{-- Buscador --}}
                    <div class="input-group" style="width: 280px;">
                        <span class="input-group-text bg-light border-0 rounded-start-pill"><i class="bi bi-search text-muted"></i></span>
                        <input wire:model="search" type="text" class="form-control bg-light border-0 rounded-end-pill" placeholder="Buscar...">
                    </div>
                </div>
            </div>

            @if($activeTab === 'ranking')
                {{-- TABLA RANKING --}}
                <div class="table-responsive">
                    <table class="table align-middle table-hover">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4 border-0">USUARIO</th>
                                <th class="border-0">ROUTER / COMERCIO</th>
                                <th class="text-center border-0">
                                    <button wire:click="toggleSort" class="btn btn-link p-0 fw-bold text-muted text-decoration-none small shadow-none">
                                        CONEXIONES <i class="bi {{ $sortDirection === 'desc' ? 'bi-arrow-down' : 'bi-arrow-up' }}"></i>
                                    </button>
                                </th>
                                <th class="text-center border-0">TIEMPO TOTAL</th>
                                <th class="text-end pe-4 border-0">ESTADO</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($results as $u)
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-primary bg-opacity-10 p-2 me-2">
                                            <i class="bi bi-person text-primary"></i>
                                        </div>
                                        <span class="fw-bold">{{ $u->username }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold small">{{ $u->identity }}</div>
                                    <div class="text-muted" style="font-size: 0.75rem;">{{ $u->comercio_nombre }}</div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-dark rounded-pill px-3">{{ $u->total_conexiones }}</span>
                                </td>
                                <td class="text-center fw-medium">
                                    {{ floor($u->tiempo_total / 3600) }}h {{ floor(($u->tiempo_total / 60) % 60) }}m
                                </td>
                                <td class="text-end pe-4">
                                    @php
                                        $label = $u->total_conexiones > 20 ? 'VIP GOLD' : ($u->total_conexiones > 10 ? 'FRECUENTE' : 'REGULAR');
                                        $color = $u->total_conexiones > 20 ? 'warning' : ($u->total_conexiones > 10 ? 'info' : 'light text-muted border');
                                    @endphp
                                    <span class="badge bg-{{ $color }}">{{ $label }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center py-5 text-muted">No se encontraron datos.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                {{-- TABLA RASTREO POR ANTENA --}}
                <div class="table-responsive">
                    <table class="table align-middle table-hover">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4 border-0">USUARIO / TICKET</th>
                                <th class="border-0">UBICACIÓN (ANTENA)</th>
                                <th class="border-0">IP ASIGNADA</th>
                                <th class="text-center border-0">CONEXIÓN</th>
                                <th class="text-end pe-4 border-0">DURACIÓN</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($results as $log)
                            <tr>
                                <td class="ps-4">
                                    <span class="fw-bold text-primary">{{ $log->username }}</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-broadcast text-danger me-2"></i>
                                        <span class="fw-bold text-dark">{{ $log->ubicacion_fisica }}</span>
                                    </div>
                                </td>
                                <td>
                                    <code class="bg-light px-2 py-1 rounded text-muted small">
                                        {{ $log->mac_address }}
                                    </code>
                                </td>
                                <td class="text-center">
                                    <div class="small fw-bold">{{ $log->created_at->format('d/m/Y') }}</div>
                                    <div class="text-muted" style="font-size: 0.7rem;">{{ $log->created_at->format('h:i:s A') }}</div>
                                </td>
                                <td class="text-end pe-4">
                                    <span class="badge bg-light text-dark fw-bold border">
                                        {{ $log->duracion_formateada }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center py-5 text-muted">No hay registros de ubicación recientes.</td></tr>
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