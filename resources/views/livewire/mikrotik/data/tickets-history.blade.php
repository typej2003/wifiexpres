<div class="container-fluid py-4">
    {{-- OVERLAY --}}
    @if($showOverlay)
    <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
        <div class="text-center text-white">
            <div class="spinner-grow text-primary mb-3" style="width: 3rem; height: 3rem;"></div>
            <h4 class="fw-bold">ACTUALIZANDO HISTORIAL</h4>
            <p>Esto puede tardar según la cantidad de registros...</p>
        </div>
    </div>
    @endif

    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="fw-800 mb-0"><i class="bi bi-clock-history text-primary me-2"></i>Historial Global de Tickets</h4>
                        <button wire:click="openSyncModal" class="btn btn-warning rounded-pill px-4 fw-bold shadow-sm">
                            <i class="bi bi-arrow-repeat me-1"></i> SINCRONIZAR SMART
                        </button>
                    </div>

                    <div class="row g-3">
                        {{-- Buscador --}}
                        <div class="col-md-3">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                                <input type="text" wire:model="search" class="form-control border-start-0" placeholder="Buscar PIN o Usuario...">
                            </div>
                        </div>

                        {{-- Aliados (Solo Admin) --}}
                        @if(auth()->user()->role === 'admin')
                        <div class="col-md-2">
                            <select wire:model="filterAliado" class="form-select">
                                <option value="">Todos los Aliados</option>
                                @foreach($aliados as $a) <option value="{{ $a->id }}">{{ $a->name }}</option> @endforeach
                            </select>
                        </div>
                        @endif

                        {{-- Routers --}}
                        <div class="col-md-2">
                            <select wire:model="filterRouter" class="form-select">
                                <option value="">Todos los Routers</option>
                                @foreach($routers as $r) 
                                    <option value="{{ $r->id }}">
                                        {{ $r->active ? '🟢' : '🔴' }} {{ $r->identity }}
                                    </option> 
                                @endforeach
                            </select>
                        </div>

                        {{-- Planes --}}
                        <div class="col-md-2">
                            <select wire:model="filterPlan" class="form-select">
                                <option value="">Todos los Planes</option>
                                @foreach($planes as $p) <option value="{{ $p->name }}">{{ $p->name }}</option> @endforeach
                            </select>
                        </div>

                        {{-- Estados --}}
                        <div class="col-md-2">
                            <select wire:model="filterEstado" class="form-select">
                                <option value="">Cualquier Estado</option>
                                <option value="disponible">DISPONIBLE</option>
                                <option value="en_uso">EN USO (ACTIVOS)</option>
                                <option value="agotado">AGOTADO</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small fw-bold">
                    <tr>
                        <th class="px-4 py-3">IDENTIDAD</th>
                        <th class="py-3">ROUTER / ALIADO</th>
                        <th class="py-3">PLAN</th>
                        <th class="py-3 text-center">ESTADO</th>
                        <th class="py-3 cursor-pointer" wire:click="toggleSort">
                            CONSUMO 
                            @if($sortDirection === 'asc') <i class="bi bi-sort-up text-primary"></i> @else <i class="bi bi-sort-down text-primary"></i> @endif
                        </th>
                        <th class="text-end px-4">FECHA</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $t)
                    <tr>
                        <td class="px-4">
                            <span class="fw-bold text-dark d-block">{{ $t->username }}</span>
                            <small class="text-muted">{{ $t->identity }}</small>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $t->router->identity }}</span>
                            <div class="small text-muted">{{ $t->router->user->name }}</div>
                        </td>
                        <td><span class="fw-bold">{{ $t->plan }}</span></td>
                        <td class="text-center">
                            @php $color = ['disponible'=>'success','en_uso'=>'info','agotado'=>'secondary','anulado'=>'danger'][$t->estado] ?? 'dark'; @endphp
                            <span class="badge bg-{{ $color }} rounded-pill px-3">{{ strtoupper($t->estado) }}</span>
                        </td>
                        <td><code class="text-primary fw-bold">{{ $t->tiempo_consumido ?: '0s' }}</code></td>
                        <td class="text-end px-4 text-muted small">{{ $t->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center py-5">No se encontraron registros con los filtros aplicados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white border-0 p-3">
            {{ $tickets->links() }}
        </div>
    </div>

    {{-- MODAL DE SINCRONIZACIÓN SMART --}}
    @if($isSyncModalOpen)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5); z-index: 1055;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header border-0 p-4 pb-0">
                    <h5 class="fw-bold">Sincronización Selectiva</h5>
                    <button wire:click="closeSyncModal" class="btn-close"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <div class="display-5 text-warning mb-3"><i class="bi bi-cloud-download"></i></div>
                    <p class="text-muted">Seleccione cuántos registros recientes del MikroTik desea actualizar en la base de datos local.</p>
                    
                    <div class="px-5 mb-4">
                        <label class="small fw-bold text-muted mb-2 d-block">CANTIDAD DE USUARIOS</label>
                        <select wire:model.defer="syncAmount" class="form-select form-select-lg text-center fw-bold border-2 border-warning">
                            <option value="50">50 Registros</option>
                            <option value="100">100 Registros</option>
                            <option value="250">250 Registros</option>
                            <option value="500">500 Registros (Lento)</option>
                        </select>
                    </div>

                    <div class="alert alert-light border small text-start">
                        <i class="bi bi-info-circle-fill text-info me-2"></i>
                        Esta acción actualizará el tiempo consumido y el estado de los tickets seleccionando el router activo en el filtro.
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button wire:click="closeSyncModal" class="btn btn-light rounded-pill px-4">Cancelar</button>
                    <button wire:click="syncData" class="btn btn-warning rounded-pill px-4 fw-bold shadow">
                        INICIAR PROCESO
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>