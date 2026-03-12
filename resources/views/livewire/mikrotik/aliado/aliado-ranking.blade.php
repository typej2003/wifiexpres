<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h3 class="fw-bold mb-0">Ranking de Fidelidad</h3>
                            <p class="text-muted">Análisis detallado de usuarios recurrentes por router.</p>
                        </div>
                        
                        <div class="d-flex align-items-center gap-4">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" wire:model="soloTickets" id="switchSoloTickets">
                                <label class="form-check-label small fw-bold text-muted" for="switchSoloTickets">OCULTAR MACs</label>
                            </div>

                            <div style="min-width: 300px;">
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 rounded-start-pill"><i class="bi bi-search"></i></span>
                                    <input wire:model="search" type="text" class="form-control border-start-0 rounded-end-pill" placeholder="Buscar usuario o ticket...">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle table-hover">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Usuario</th>
                                    <th>Router / Local</th>
                                    <th class="text-center">
                                        <button wire:click="toggleSort" class="btn btn-link text-decoration-none text-muted p-0 small fw-bold shadow-none">
                                            CONEXIONES
                                            <i class="bi {{ $sortDirection === 'desc' ? 'bi-arrow-down' : 'bi-arrow-up' }} ms-1"></i>
                                        </button>
                                    </th>
                                    <th class="text-center">Tiempo Total</th>
                                    <th>Última Visita</th>
                                    <th class="text-end pe-4">Estatus</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rankings as $u)
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-3">
                                                <i class="bi bi-person-badge fs-5"></i>
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
                                    <td class="text-center text-dark fw-medium">
                                        {{ floor($u->tiempo_total / 3600) }}h {{ floor(($u->tiempo_total / 60) % 60) }}m
                                    </td>
                                    <td>
                                        <small>{{ \Carbon\Carbon::parse($u->ultima_conexion)->diffForHumans() }}</small>
                                    </td>
                                    <td class="text-end pe-4">
                                        @if($u->total_conexiones > 20)
                                            <span class="badge bg-warning text-dark border-0 shadow-sm">VIP GOLD</span>
                                        @elseif($u->total_conexiones > 10)
                                            <span class="badge bg-info text-white border-0 shadow-sm">FRECUENTE</span>
                                        @else
                                            <span class="badge bg-light text-muted border">REGULAR</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5">No se encontraron resultados.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $rankings->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>