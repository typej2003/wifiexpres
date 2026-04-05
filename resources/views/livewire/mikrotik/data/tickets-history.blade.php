<div class="container-fluid py-4">
    {{-- OVERLAY --}}
    @if($showOverlay)
    <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
        <div class="text-center text-white">
            <div class="spinner-grow text-primary mb-3" style="width: 3rem; height: 3rem;"></div>
            <h4 class="fw-bold">ACTUALIZANDO HISTORIAL</h4>
        </div>
    </div>
    @endif

    {{-- FILTROS --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-800 mb-0"><i class="bi bi-clock-history text-primary me-2"></i>Historial de Tickets</h4>
                <button wire:click="openSyncModal" class="btn btn-warning rounded-pill px-4 fw-bold shadow-sm">
                    <i class="bi bi-arrow-repeat me-1"></i> SINCRONIZAR
                </button>
            </div>

            <div class="row g-3">
                <div class="col-md-3">
                    <input type="text" wire:model="search" class="form-control" placeholder="Buscar PIN o Identidad...">
                </div>

                <div class="col-md-2">
                    <select wire:model="filterOrigen" class="form-select border-primary-subtle">
                        <option value="">Cualquier Origen</option>
                        <option value="tickets">🎫 Lotes (Tickets)</option>
                        <option value="pasarela">💳 Pasarela (Venta)</option>
                        <option value="trial">🎁 Trial (Gratis)</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <select wire:model="filterEstado" class="form-select border-primary-subtle">
                        <option value="">Cualquier Estado</option>
                        <option value="disponible">✅ DISPONIBLE</option>
                        <option value="en_uso">⚡ EN USO</option>
                        <option value="agotado">⌛ AGOTADO</option>
                        <option value="anulado">🚫 ANULADO</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <select wire:model="filterRouter" class="form-select">
                        <option value="">Todos los Routers</option>
                        @foreach($routers as $r) 
                            <option value="{{ $r->id }}">{{ $r->identity }}</option> 
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- TABLA --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small fw-bold">
                    <tr>
                        <th class="px-4 py-3">IDENTIDAD</th>
                        <th class="py-3">ROUTER / ALIADO</th>
                        <th class="py-3 text-center">PLAN / COSTO</th>
                        <th class="py-3 text-center cursor-pointer" wire:click="toggleSort">CONSUMO</th>
                        <th class="py-3 text-center">ESTADO</th>
                        <th class="text-end px-4">REGISTRO</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $t)
                    @php 
                        $isTrial = str_contains($t->identity, 'IMP-T-');
                        $isLote = str_contains($t->identity, 'Lote');
                        $isVenta = str_contains($t->identity, 'IMP-') && !$isTrial;

                        $planLower = strtolower($t->plan);
                        $isGratis = (str_contains($planLower, 'neutro') || str_contains($planLower, 'cortesia') || str_contains($planLower, 'trial'));
                        $costo = $isGratis ? 0 : 1;
                    @endphp
                    <tr>
                        <td class="px-4">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm me-2 bg-light rounded d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                    @if($isTrial) <i class="bi bi-gift-fill text-success"></i>
                                    @elseif($isLote) <i class="bi bi-layers-fill text-primary"></i> 
                                    @elseif($isVenta) <i class="bi bi-credit-card-fill text-warning"></i>
                                    @else <i class="bi bi-person-fill text-secondary"></i> @endif
                                </div>
                                <div>
                                    <span class="fw-bold text-dark d-block">{{ $t->username }}</span>
                                    <small class="text-muted" style="font-size: 0.7rem;">{{ $t->identity }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $t->router->identity }}</span>
                            <div class="small text-muted">{{ $t->router->user->name }}</div>
                        </td>
                        <td class="text-center">
                            <span class="fw-bold d-block">{{ $t->plan }}</span>
                            <span class="badge {{ $costo > 0 ? 'bg-soft-primary text-primary' : 'bg-soft-success text-success' }} small">
                                Costo: {{ $costo }}
                            </span>
                        </td>
                        <td class="text-center">
                            <code class="text-primary fw-bold" style="font-size: 1.1rem;">{{ $t->tiempo_consumido ?: '0s' }}</code>
                        </td>
                        <td class="text-center">
                            @php 
                                $badgeColor = [
                                    'disponible' => 'success',
                                    'en_uso'     => 'info',
                                    'agotado'    => 'secondary',
                                    'anulado'    => 'danger'
                                ][$t->estado] ?? 'dark';
                            @endphp
                            <span class="badge bg-{{ $badgeColor }} rounded-pill px-3 shadow-sm">{{ strtoupper($t->estado) }}</span>
                        </td>
                        <td class="text-end px-4">
                            <span class="text-muted small d-block">{{ $t->created_at->format('d/m/Y') }}</span>
                            <span class="text-muted small">{{ $t->created_at->format('H:i') }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center py-5">No hay registros con estos criterios.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white border-0 p-3">{{ $tickets->links() }}</div>
    </div>

    {{-- MODALES --}}
    @if($isSyncModalOpen)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5); z-index: 1055;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg text-center p-4">
                <h5 class="fw-bold">Actualizar desde MikroTik</h5>
                <p class="text-muted">¿Cuántos registros desea sincronizar?</p>
                <div class="px-5 mb-4">
                    <select wire:model.defer="syncAmount" class="form-select form-select-lg text-center fw-bold border-warning">
                        <option value="50">50 Registros</option>
                        <option value="100">100 Registros</option>
                        <option value="250">250 Registros</option>
                    </select>
                </div>
                <div class="d-flex gap-2">
                    <button wire:click="closeSyncModal" class="btn btn-light rounded-pill w-100">Cancelar</button>
                    <button wire:click="syncData" class="btn btn-warning rounded-pill w-100 fw-bold">INICIAR</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if($isSummaryModalOpen)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5); z-index: 1056;">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content rounded-4 border-0 shadow-lg text-center p-4">
                <i class="bi bi-check-circle-fill text-success display-4 mb-3"></i>
                <h4 class="fw-bold">Resumen</h4>
                <div class="my-3 small text-start">
                    <div class="d-flex justify-content-between mb-1 text-success"><span>Nuevos:</span> <strong>+{{ $syncResults['nuevos'] }}</strong></div>
                    <div class="d-flex justify-content-between mb-1 text-primary"><span>Actualizados:</span> <strong>{{ $syncResults['actualizados'] }}</strong></div>
                    <div class="d-flex justify-content-between text-muted"><span>Sin cambios:</span> <strong>{{ $syncResults['sin_cambios'] }}</strong></div>
                </div>
                <button wire:click="closeSummaryModal" class="btn btn-dark rounded-pill w-100 fw-bold">OK</button>
            </div>
        </div>
    </div>
    @endif
</div>