<div class="container-fluid py-4">
    {{-- OVERLAY DE CARGA --}}
    @if($showOverlay)
    <div class="d-print-none" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
        <div class="text-center text-white">
            <div class="spinner-grow text-primary mb-3" style="width: 3rem; height: 3rem;"></div>
            <h4 class="fw-bold">ACTUALIZANDO HISTORIAL</h4>
            <p>Sincronizando registros con el MikroTik...</p>
        </div>
    </div>
    @endif

    {{-- CABECERA PARA IMPRESIÓN (Usa ticketsPrint) --}}
    <div class="d-none d-print-block mb-4">
        <div class="text-center">
            <h2 class="fw-bold">REPORTE DE TICKETS FILTRADOS</h2>
            <p>Total de registros: {{ count($ticketsPrint) }} | Generado: {{ now()->format('d/m/Y h:i A') }}</p>
        </div>
        <table class="table table-bordered small">
            <thead class="bg-light">
                <tr>
                    <th>USUARIO</th>
                    <th>IDENTIDAD</th>
                    <th>ROUTER</th>
                    <th>PLAN</th>
                    <th>COSTO</th>
                    <th>CONSUMO</th>
                    <th>ESTADO</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ticketsPrint as $tp)
                    @php 
                        $planLower = strtolower($tp->plan);
                        $costo = (str_contains($planLower, 'neutro') || str_contains($planLower, 'cortesia') || str_contains($planLower, 'trial')) ? 0 : 1;
                    @endphp
                    <tr>
                        <td>{{ $tp->username }}</td>
                        <td>{{ $tp->identity }}</td>
                        <td>{{ $tp->router->identity }}</td>
                        <td>{{ $tp->plan }}</td>
                        <td>{{ $costo }}</td>
                        <td>{{ $tp->tiempo_consumido ?: '0s' }}</td>
                        <td>{{ strtoupper($tp->estado) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- CONTENIDO WEB (Oculto en impresión) --}}
    <div class="d-print-none">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-800 mb-0"><i class="bi bi-clock-history text-primary me-2"></i>Historial</h4>
                    <div class="d-flex gap-2">
                        <button onclick="window.print()" class="btn btn-danger rounded-pill px-4 fw-bold shadow-sm">
                            <i class="bi bi-printer me-1"></i> IMPRIMIR CONSULTA
                        </button>
                        <button wire:click="openSyncModal" class="btn btn-warning rounded-pill px-4 fw-bold shadow-sm">
                            <i class="bi bi-arrow-repeat me-1"></i> SINCRONIZAR
                        </button>
                    </div>
                </div>

                {{-- FILTROS --}}
                <div class="row g-3">
                    <div class="col-md-3">
                        <input type="text" wire:model="search" class="form-control" placeholder="Buscar PIN...">
                    </div>
                    <div class="col-md-2">
                        <select wire:model="filterOrigen" class="form-select">
                            <option value="">Orígenes</option>
                            <option value="tickets">Lotes</option>
                            <option value="pasarela">Pasarela</option>
                            <option value="trial">Trial</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select wire:model="filterRouter" class="form-select">
                            <option value="">Routers</option>
                            @foreach($routers as $r) <option value="{{ $r->id }}">{{ $r->identity }}</option> @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select wire:model="filterEstado" class="form-select">
                            <option value="">Estados</option>
                            <option value="disponible">Disponible</option>
                            <option value="en_uso">En Uso</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- TABLA WEB --}}
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small fw-bold">
                        <tr>
                            <th class="px-4">IDENTIDAD</th>
                            <th>ROUTER</th>
                            <th class="text-center">PLAN</th>
                            <th class="text-center" wire:click="toggleSort" style="cursor:pointer">CONSUMO</th>
                            <th class="text-center">ESTADO</th>
                            <th class="text-end px-4">FECHA</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tickets as $t)
                        <tr>
                            <td class="px-4">
                                <strong>{{ $t->username }}</strong><br>
                                <small class="text-muted">{{ $t->identity }}</small>
                            </td>
                            <td>{{ $t->router->identity }}</td>
                            <td class="text-center">
                                {{ $t->plan }}<br>
                                @php 
                                    $pL = strtolower($t->plan);
                                    $c = (str_contains($pL, 'neutro') || str_contains($pL, 'cortesia') || str_contains($pL, 'trial')) ? 0 : 1;
                                @endphp
                                <span class="badge {{ $c > 0 ? 'bg-primary' : 'bg-success' }}">Costo: {{ $c }}</span>
                            </td>
                            <td class="text-center"><code>{{ $t->tiempo_consumido ?: '0s' }}</code></td>
                            <td class="text-center">
                                <span class="badge bg-info">{{ strtoupper($t->estado) }}</span>
                            </td>
                            <td class="text-end px-4 small text-muted">{{ $t->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center py-4">No hay resultados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white">{{ $tickets->links() }}</div>
        </div>
    </div>
</div>

<style>
    @media print {
        .d-print-none { display: none !important; }
        .d-print-block { display: block !important; }
        body { background: white !important; font-size: 9pt; }
        .table { width: 100% !important; border-collapse: collapse !important; }
        .table-bordered th, .table-bordered td { border: 1px solid #dee2e6 !important; padding: 5px; }
    }
</style>