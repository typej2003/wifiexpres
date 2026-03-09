<div class="container-fluid py-4">
    <style>
        .modal-backdrop { opacity: 0.6 !important; }
        .plan-card { border: 2px solid #e9ecef; border-radius: 12px; transition: all 0.3s ease; background: #fff; }
        .plan-card.active { border-color: #0d6efd; background: #f0f7ff; }
        .price-display { font-size: 2rem; font-weight: 800; color: #0d6efd; line-height: 1; }
        .preview-ticket-container { width: 190px; border: 1px solid #000; padding: 10px; text-align: center; background: #fff; margin: auto; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .bi-spin { animation: spin 1s linear infinite; }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>

    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body bg-white rounded">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-muted">1. ALIADO</label>
                    <select wire:model="selectedAliado" class="form-select shadow-sm">
                        <option value="">Seleccione...</option>
                        @foreach($aliados as $aliado) 
                            <option value="{{ $aliado->id }}">{{ $aliado->names }} {{ $aliado->surnames }}</option> 
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-muted">2. ROUTER</label>
                    <select wire:model="selectedRouter" class="form-select shadow-sm" {{ !$selectedAliado ? 'disabled' : '' }}>
                        <option value="">Seleccione...</option>
                        @foreach($routers as $router) <option value="{{ $router->id }}">{{ $router->identity }}</option> @endforeach
                    </select>
                </div>
                <div class="col-md-6 d-flex align-items-end gap-2 flex-wrap">
                    <button wire:click="create" class="btn btn-primary px-3 shadow-sm" {{ !$selectedRouter ? 'disabled' : '' }}>
                        <i class="bi bi-plus-circle"></i> Individual
                    </button>
                    <button wire:click="openBulkModal" class="btn btn-dark px-3 shadow-sm" {{ !$selectedRouter ? 'disabled' : '' }}>
                        <i class="bi bi-layers"></i> Lote
                    </button>
                    <button wire:click="syncPendingTickets" class="btn btn-info px-3 text-white shadow-sm" {{ !$selectedRouter ? 'disabled' : '' }}>
                        <i class="bi bi-arrow-repeat"></i> Sincronizar
                    </button>
                    <button wire:click="openConfigModal" class="btn btn-warning px-3 text-white shadow-sm" {{ !$selectedRouter ? 'disabled' : '' }}>
                        <i class="bi bi-palette"></i> Diseño
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if($selectedRouter)
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-center">
                <thead class="table-light">
                    <tr>
                        <th>Ticket</th>
                        <th>Credenciales</th>
                        <th>Tiempo / Costo</th>
                        <th>Estado</th>
                        <th>Consumo</th>
                        <th>Uso Primera Vez</th>
                        <th>MikroTik</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $t)
                    <tr class="{{ $t->estado == 'anulado' || $t->estado == 'agotado' ? 'table-light opacity-75' : '' }}">
                        <td class="fw-bold">{{ $t->identity }}</td>
                        <td class="text-start ps-4">
                            <span class="small">U: <b>{{ $t->username }}</b></span><br>
                            <span class="small">P: <b>{{ $t->password }}</b></span>
                        </td>
                        <td>
                            <div class="fw-bold">{{ $t->tiempo_uso }}</div>
                            <div class="badge bg-success shadow-sm">${{ number_format($t->costo, 2) }}</div>
                        </td>
                        <td>
                            @switch($t->estado)
                                @case('en_uso') <span class="badge bg-success">EN USO</span> @break
                                @case('agotado') <span class="badge bg-danger">AGOTADO</span> @break
                                @case('anulado') <span class="badge bg-secondary">ANULADO</span> @break
                                @default <span class="badge bg-primary">DISPONIBLE</span>
                            @endswitch
                        </td>
                        <td>
                            @if($t->tiempo_consumido)
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <span class="fw-bold {{ $t->estado == 'agotado' ? 'text-danger' : 'text-primary' }} mb-0">
                                        {{ $t->tiempo_consumido }}
                                    </span>
                                    @if($t->estado == 'en_uso')
                                        <button wire:click="updateSingleTicketUsage({{ $t->id }})" wire:loading.attr="disabled" class="btn btn-sm btn-outline-primary border-0 p-1">
                                            <i class="bi bi-arrow-clockwise" wire:loading.class="bi-spin" wire:target="updateSingleTicketUsage({{ $t->id }})"></i>
                                        </button>
                                    @endif
                                </div>
                            @elseif($t->estado == 'en_uso')
                                <button wire:click="updateSingleTicketUsage({{ $t->id }})" class="btn btn-sm btn-primary py-0 px-2" style="font-size: 10px;">
                                    OBTENER CONSUMO
                                </button>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td>
                            @if($t->fecha_uso)
                                @php $fechaObj = is_string($t->fecha_uso) ? \Carbon\Carbon::parse($t->fecha_uso) : $t->fecha_uso; @endphp
                                <span class="badge bg-light text-dark border small">
                                    <i class="bi bi-clock-history text-primary"></i> 
                                    {{ $fechaObj->format('d/m/y h:i A') }}
                                </span>
                            @else 
                                <span class="text-muted small italic">Pendiente...</span> 
                            @endif
                        </td>
                        <td><i class="bi {{ $t->sincronizado ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger' }}"></i></td>
                        <td>
                            <div class="btn-group">
                                @if($t->estado != 'anulado')
                                    <button onclick="confirm('¿Anular?') || event.stopImmediatePropagation()" wire:click="anularTicket({{ $t->id }})" class="btn btn-sm btn-outline-warning"><i class="bi bi-slash-circle"></i></button>
                                @else
                                    <button wire:click="restaurarTicket({{ $t->id }})" class="btn btn-sm btn-outline-success"><i class="bi bi-arrow-counterclockwise"></i></button>
                                @endif
                                <button wire:click="edit({{ $t->id }})" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                            </div>
                        </td>
                    </tr>
                    @empty
                        <tr><td colspan="8" class="p-5 text-muted">No hay datos para este router.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white border-0">{{ $tickets->links() }}</div>
    </div>
    @endif

    @if($isModalOpen)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.6); z-index: 1060;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">Nuevo Ticket</h5>
                    <button wire:click="closeModal" class="btn-close btn-close-white"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">ID TICKET</label>
                        <input type="text" wire:model.defer="identity" class="form-control" placeholder="Ej: 1-0001">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">PERFIL</label>
                        <select wire:model="plan" class="form-select">
                            <option value="">Seleccione...</option>
                            @foreach($mikrotik_profiles as $p) <option value="{{ $p['name'] }}">{{ $p['name'] }}</option> @endforeach
                        </select>
                    </div>
                    @if($plan)
                    <div class="plan-card active p-3 text-center">
                        <div class="small fw-bold text-primary">{{ $tiempo_uso }}</div>
                        <div class="price-display mt-1">${{ number_format((float)$costo, 2) }}</div>
                    </div>
                    @endif
                </div>
                <div class="modal-footer border-0">
                    <button wire:click="closeModal" class="btn btn-light">Cerrar</button>
                    <button wire:click="store" class="btn btn-primary px-4 shadow">Generar</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if($isBulkModalOpen)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.6); z-index: 1060;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold">Generar Lote</h5>
                    <button wire:click="closeBulkModal" class="btn-close btn-close-white"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">CANTIDAD DE TICKETS</label>
                        <input type="number" wire:model.defer="bulk_count" class="form-control text-center fw-bold h4 border-primary">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">PERFIL PARA EL LOTE</label>
                        <select wire:model="bulk_plan" class="form-select">
                            <option value="">Seleccione...</option>
                            @foreach($mikrotik_profiles as $p) <option value="{{ $p['name'] }}">{{ $p['name'] }}</option> @endforeach
                        </select>
                    </div>
                    @if($bulk_plan)
                    <div class="d-flex justify-content-between bg-light p-3 rounded border">
                        <div><small class="text-muted fw-bold">COSTO</small><div class="h4 fw-bold text-success">${{ number_format((float)$bulk_costo, 2) }}</div></div>
                        <div class="text-end"><small class="text-muted fw-bold">DURACIÓN</small><div class="h4 fw-bold text-dark">{{ $bulk_tiempo }}</div></div>
                    </div>
                    @endif
                </div>
                <div class="modal-footer border-0">
                    <button wire:click="closeBulkModal" class="btn btn-light">Cancelar</button>
                    <button wire:click="generateBulkTickets" class="btn btn-dark px-4 shadow">Generar Lote</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if($isConfigModalOpen)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.6); z-index: 1060;">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-palette"></i> Personalizar Ticket</h5>
                    <button wire:click="closeConfigModal" class="btn-close btn-close-white"></button>
                </div>
                <div class="modal-body p-4 row">
                    <div class="col-md-6 border-end">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Nombre del Comercio</label>
                            <input type="text" wire:model="comercio_nombre" class="form-control shadow-sm">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">URL del Hotspot</label>
                            <input type="text" wire:model="hotspot_url" class="form-control shadow-sm">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Logo</label>
                            <input type="file" wire:model="nuevo_logo" class="form-control shadow-sm">
                        </div>
                    </div>
                    <div class="col-md-6 text-center">
                        <p class="small fw-bold text-muted mb-3">VISTA PREVIA</p>
                        <div class="preview-ticket-container">
                            @if($nuevo_logo) <img src="{{ $nuevo_logo->temporaryUrl() }}" style="max-height: 45px; margin-bottom: 5px;">
                            @elseif($logo_actual) <img src="{{ asset('storage/' . $logo_actual) }}" style="max-height: 45px; margin-bottom: 5px;">
                            @endif
                            <div class="fw-bold small border-bottom pb-1">{{ $comercio_nombre ?: 'MI COMERCIO' }}</div>
                            <div class="my-2 small"><b>U: 10001</b><br><b>P: 82736</b></div>
                            <div class="bg-dark text-white py-1 small fw-bold">1 HORA</div>
                            <div class="h4 fw-bold my-1 text-primary">$1.00</div>
                            <div style="font-size: 9px;" class="text-muted">{{ $hotspot_url ?: 'portal.wifi' }}</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button wire:click="closeConfigModal" class="btn btn-light">Cerrar</button>
                    <button wire:click="saveConfig" class="btn btn-warning text-white px-4 shadow">Guardar Cambios</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>