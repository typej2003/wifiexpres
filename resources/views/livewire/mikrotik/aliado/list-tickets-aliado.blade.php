<div class="container-fluid py-4">
    {{-- OVERLAY DE CARGA --}}
    @if($showOverlay)
    <div class="loading-overlay">
        <div class="text-center">
            <div class="spinner-border text-white mb-3" role="status" style="width: 3.5rem; height: 3.5rem;"></div>
            <h5 class="text-white fw-bold">SINCRONIZANDO CON ROUTER</h5>
            <p class="text-white-50">Por favor, no cierre esta ventana...</p>
        </div>
    </div>
    @endif

    {{-- HEADER --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <button wire:click="backToRouters" class="btn btn-light rounded-circle me-3 border shadow-sm"><i class="bi bi-arrow-left"></i></button>
                <div>
                    <h4 class="fw-bold mb-0">Tickets: <span class="text-primary">{{ $router_name }}</span></h4>
                    <small class="text-muted">Administración de usuarios Hotspot</small>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button wire:click="openConfigModal" class="btn btn-white border shadow-sm rounded-pill px-3 fw-bold">
                    <i class="bi bi-palette text-secondary me-1"></i> DISEÑO
                </button>
                <button wire:click="openPrintModal" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm text-white">
                    <i class="bi bi-printer me-1"></i> IMPRIMIR
                </button>
                <button wire:click="syncPendingTickets" wire:loading.attr="disabled" class="btn btn-light border shadow-sm rounded-pill px-3 fw-bold">
                    <i class="bi bi-arrow-repeat text-warning me-1"></i> SINCRONIZAR
                </button>
                <button wire:click="openBulkModal" class="btn btn-dark rounded-pill px-4 fw-bold shadow-sm">
                    <i class="bi bi-layers me-1"></i> GENERAR LOTE
                </button>
            </div>
        </div>
    </div>

    {{-- TABLA --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4 py-3">IDENTIDAD / PIN</th>
                        <th>PLAN / PERFIL</th>
                        <th class="text-center">ESTADO</th>
                        <th>CONSUMO</th>
                        <th class="text-end px-4">ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $t)
                        <tr>
                            <td class="px-4">
                                <span class="fw-bold d-block">{{ $t->identity }}</span>
                                <small class="text-muted font-monospace">User: {{ $t->username }}</small>
                            </td>
                            <td><span class="badge bg-light text-dark border">{{ $t->plan }}</span></td>
                            <td class="text-center">
                                <span class="badge rounded-pill bg-{{ $t->estado == 'disponible' ? 'success' : 'info' }} px-3">{{ strtoupper($t->estado) }}</span>
                            </td>
                            <td><code class="text-dark">{{ $t->tiempo_consumido }}</code></td>
                            <td class="text-end px-4">
                                <button class="btn btn-sm btn-outline-primary rounded-circle"><i class="bi bi-pencil"></i></button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-5">No hay registros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">{{ $tickets->links() }}</div>
    </div>

    {{-- MODAL GENERAR LOTE --}}
    @if($isBulkModalOpen)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.6); z-index: 1050;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0">
                <div class="modal-header bg-dark text-white p-4 border-0">
                    <h5 class="fw-bold mb-0">Generar Lote Masivo</h5>
                    <button wire:click="closeBulkModal" class="btn-close btn-close-white"></button>
                </div>
                <div class="modal-body p-4">
                    @if($bulk_step === 'input')
                        <div class="mb-3">
                            <label class="form-label small fw-bold">CANTIDAD</label>
                            <input type="number" wire:model.defer="bulk_count" class="form-control form-control-lg rounded-3 border-0 bg-light fw-bold">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">PERFIL MIKROTIK</label>
                            <select wire:model.defer="bulk_plan" class="form-select form-select-lg rounded-3 border-0 bg-light">
                                <option value="">Seleccione...</option>
                                @foreach($mikrotik_profiles as $p)
                                    <option value="{{ $p['name'] }}">{{ $p['display'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <div class="text-center py-3">
                            <h6 class="fw-bold">Progreso: {{ $bulk_current_count }} / {{ $bulk_total_requested }}</h6>
                            <div class="progress rounded-pill my-3" style="height: 15px;">
                                <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated" style="width: {{ ($bulk_current_count/$bulk_total_requested)*100 }}%"></div>
                            </div>
                            @if(session()->has('chunk_message') && $bulk_current_count < $bulk_total_requested)
                                <button wire:click="processNextChunk" wire:loading.attr="disabled" class="btn btn-primary w-100 rounded-pill py-3 fw-bold">
                                    <span wire:loading.remove>CREAR SIGUIENTES {{ session('next_amount') }}</span>
                                    <span wire:loading><span class="spinner-border spinner-border-sm me-2"></span>PROCESANDO...</span>
                                </button>
                            @endif
                        </div>
                    @endif
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    @if($bulk_step === 'input')
                        <button wire:click="startBulkGeneration" class="btn btn-dark w-100 rounded-pill py-3 fw-bold">INICIAR CREACIÓN</button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- MODAL DISEÑO --}}
    @if($isConfigModalOpen)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.6); z-index: 1050;">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 border-0">
                <div class="modal-header p-4 border-bottom">
                    <h5 class="fw-bold mb-0">Diseño del Ticket</h5>
                    <button wire:click="closeConfigModal" class="btn-close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3 text-center">
                                @if($nuevo_logo) <img src="{{ $nuevo_logo->temporaryUrl() }}" class="rounded-circle shadow-sm mb-2" style="width: 80px; height: 80px; object-fit: cover;">
                                @elseif($logo_actual) <img src="{{ asset('storage/'.$logo_actual) }}" class="rounded-circle shadow-sm mb-2" style="width: 80px; height: 80px; object-fit: cover;">
                                @endif
                                <input type="file" wire:model="nuevo_logo" class="form-control form-control-sm">
                            </div>
                            <div class="mb-3">
                                <label class="small fw-bold">NOMBRE DEL COMERCIO</label>
                                <input type="text" wire:model="comercio_nombre" class="form-control rounded-3">
                            </div>
                            <div class="mb-3">
                                <label class="small fw-bold">URL PORTAL</label>
                                <input type="text" wire:model="hotspot_url" class="form-control rounded-3">
                            </div>
                            <button wire:click="saveConfig" class="btn btn-primary w-100 rounded-pill fw-bold">GUARDAR CAMBIOS</button>
                        </div>
                        <div class="col-md-6 d-flex justify-content-center bg-light p-4 rounded-4">
                            {{-- PREVIEW TICKET --}}
                            <div class="real-ticket-preview shadow-sm">
                                <div class="preview-logo-container">
                                    <div class="logo-wrapper">
                                        @if($nuevo_logo) <img src="{{ $nuevo_logo->temporaryUrl() }}" class="preview-logo-img">
                                        @elseif($logo_actual) <img src="{{ asset('storage/'.$logo_actual) }}" class="preview-logo-img">
                                        @endif
                                    </div>
                                </div>
                                <div class="preview-comercio-nombre">{{ $comercio_nombre ?: 'WIFI EXPRES' }}</div>
                                <div class="preview-creds-box">
                                    <small>PIN: 1-001-0001</small><br>
                                    <strong>CLAVE: 82736</strong>
                                </div>
                                <div class="preview-plan-box">PLAN 1 HORA</div>
                                <div class="preview-precio">$1.00</div>
                                <div class="preview-footer">{{ $hotspot_url ?: 'portal.wifi' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ESTILOS --}}
    <style>
        .loading-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px); }
        .real-ticket-preview { width: 140px; height: 350px; background: #fff; border: 1px solid #ddd; padding: 15px 10px; text-align: center; display: flex; flex-direction: column; }
        .logo-wrapper { width: 50px; height: 50px; border-radius: 50%; overflow: hidden; margin: 0 auto; border: 1px solid #eee; }
        .preview-logo-img { width: 100%; height: 100%; object-fit: cover; }
        .preview-comercio-nombre { font-size: 9px; font-weight: bold; margin: 5px 0; }
        .preview-creds-box { background: #f8f9fa; padding: 5px; margin: 10px 0; border: 1px dashed #ccc; font-size: 10px; }
        .preview-plan-box { background: #000; color: #fff; font-size: 10px; padding: 3px; font-weight: bold; }
        .preview-precio { font-size: 20px; font-weight: bold; margin-top: 5px; }
        .preview-footer { font-size: 8px; margin-top: auto; border-top: 1px solid #eee; padding-top: 5px; }
    </style>
</div>