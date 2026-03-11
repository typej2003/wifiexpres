<div class="container-fluid py-4">
    {{-- OVERLAY DE CARGA --}}
    @if($showOverlay)
    <div class="loading-overlay">
        <div class="text-center">
            <div class="spinner-border text-white mb-3" role="status" style="width: 3.5rem; height: 3.5rem;"></div>
            <h5 class="text-white fw-bold">PROCESANDO SOLICITUD</h5>
            <p class="text-white-50 small">Esto puede tardar un poco dependiendo de la cantidad de tickets...</p>
        </div>
    </div>
    @endif

    {{-- HEADER --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4" style="background: linear-gradient(to right, #ffffff, #f8f9fa);">
        <div class="card-body p-4 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <button wire:click="backToRouters" class="btn btn-light rounded-circle me-3 shadow-sm border d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="bi bi-arrow-left fs-5"></i>
                </button>
                <div>
                    <h4 class="fw-bold mb-1 text-dark">
                        <i class="bi bi-ticket-perforated-fill text-primary me-2"></i>
                        Tickets: <span class="text-primary">{{ $router_name }}</span>
                    </h4>
                    <p class="text-muted small mb-0">Base de Datos Local</p>
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
                        <th class="px-4 py-3 text-muted small fw-bold border-0">IDENTIDAD / PIN</th>
                        <th class="py-3 text-muted small fw-bold border-0">PLAN / PERFIL</th>
                        <th class="py-3 text-muted small fw-bold border-0 text-center">ESTADO</th>
                        <th class="py-3 text-muted small fw-bold border-0">TIEMPO</th>
                        <th class="text-end px-4 border-0">ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $t)
                        <tr class="{{ $t->anulado ? 'opacity-50 bg-light' : '' }}">
                            <td class="px-4">
                                <span class="fw-bold d-block text-dark">{{ $t->identity }}</span>
                                <small class="text-muted font-monospace">Pass: {{ $t->password }}</small>
                            </td>
                            <td><div class="fw-bold text-dark">{{ $t->plan }}</div></td>
                            <td class="text-center">
                                @php $statusColor = ['disponible'=>'success','en_uso'=>'info','agotado'=>'secondary','anulado'=>'danger'][$t->estado] ?? 'dark'; @endphp
                                <span class="badge bg-{{ $statusColor }} rounded-pill px-3">{{ strtoupper($t->estado) }}</span>
                            </td>
                            <td><code class="text-dark fw-bold">{{ $t->tiempo_consumido ?: '0s' }}</code></td>
                            <td class="text-end px-4">
                                <div class="btn-group btn-group-sm border rounded-pill overflow-hidden shadow-sm">
                                    <button class="btn btn-white border-0"><i class="bi bi-pencil text-primary"></i></button>
                                    <button wire:click="{{ $t->anulado ? 'restaurarTicket' : 'anularTicket' }}({{ $t->id }})" class="btn btn-white border-0">
                                        <i class="bi {{ $t->anulado ? 'bi-arrow-counterclockwise text-success' : 'bi-x-circle text-danger' }}"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-5 text-muted">No hay tickets disponibles.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white p-3">{{ $tickets->links() }}</div>
    </div>

    {{-- MODAL GENERAR LOTE (ACTUALIZADO PARA AUTO-CHUNKS) --}}
    @if($isBulkModalOpen)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5); z-index: 1050;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header bg-dark text-white border-0 p-4">
                    <h5 class="modal-title fw-bold">Generador Masivo</h5>
                    @if($bulk_step === 'input')
                        <button wire:click="closeBulkModal" class="btn-close btn-close-white"></button>
                    @endif
                </div>
                <div class="modal-body p-4 text-center">
                    @if($bulk_step === 'input')
                        <div class="mb-3 text-start">
                            <label class="form-label small fw-bold text-muted">CANTIDAD TOTAL</label>
                            <input type="number" wire:model.defer="bulk_count" class="form-control rounded-3 border-0 bg-light fw-bold fs-4 text-center" placeholder="Ej: 100">
                            <small class="text-muted">Se crearán en bloques de {{ $bulk_chunk_size }} para evitar errores.</small>
                        </div>
                        <div class="mb-4 text-start">
                            <label class="form-label small fw-bold text-muted">PLAN ASOCIADO</label>
                            <select wire:model.defer="bulk_plan" class="form-select rounded-3 border-0 bg-light fw-bold">
                                <option value="">Seleccione un plan...</option>
                                @foreach($mikrotik_profiles as $p)
                                    <option value="{{ $p['name'] }}">{{ $p['display'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button wire:click="startBulkGeneration" class="btn btn-dark w-100 rounded-pill py-3 fw-bold shadow">
                            INICIAR GENERACIÓN
                        </button>
                    @else
                        {{-- PROCESO AUTOMÁTICO --}}
                        <div class="py-3">
                            <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;"></div>
                            <h5 class="fw-bold">CREANDO TICKETS...</h5>
                            <p class="text-muted small">Por favor, mantenga esta ventana abierta.</p>
                            
                            @php $porcentaje = $bulk_total_requested > 0 ? ($bulk_current_count / $bulk_total_requested) * 100 : 0; @endphp
                            
                            <div class="progress rounded-pill mb-3 shadow-sm" style="height: 20px;">
                                <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated" 
                                     role="progressbar" 
                                     style="width: {{ $porcentaje }}%; transition: width 0.4s ease;">
                                    {{ round($porcentaje) }}%
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center px-2">
                                <span class="fw-bold text-muted small">PROGRESADO: {{ $bulk_current_count }}</span>
                                <span class="fw-bold text-primary small">META: {{ $bulk_total_requested }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- MODAL DISEÑO --}}
    @if($isConfigModalOpen)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5); z-index: 1050;">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
                <div class="modal-header border-bottom p-4">
                    <h5 class="modal-title fw-bold"><i class="bi bi-palette2 me-2"></i>Diseño del Ticket</h5>
                    <button wire:click="closeConfigModal" class="btn-close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="row g-0">
                        <div class="col-md-7 p-4 border-end">
                            <div class="mb-4">
                                <label class="form-label small fw-bold text-muted">LOGO DEL COMERCIO</label>
                                <input type="file" wire:model="nuevo_logo" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">NOMBRE COMERCIAL</label>
                                <input type="text" wire:model="comercio_nombre" class="form-control bg-light border-0">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">URL PORTAL</label>
                                <input type="text" wire:model="hotspot_url" class="form-control bg-light border-0">
                            </div>
                            <button wire:click="saveConfig" class="btn btn-primary w-100 rounded-pill fw-bold py-3 shadow">GUARDAR CAMBIOS</button>
                        </div>
                        <div class="col-md-5 bg-secondary bg-opacity-10 d-flex justify-content-center py-5">
                            {{-- TICKET PREVIEW --}}
                            <div class="real-ticket-preview shadow-lg">
                                <div class="preview-logo-container">
                                    <div class="logo-wrapper">
                                        @if($nuevo_logo) <img src="{{ $nuevo_logo->temporaryUrl() }}" class="preview-logo-img">
                                        @elseif($logo_actual) <img src="{{ asset('storage/'.$logo_actual) }}" class="preview-logo-img">
                                        @endif
                                    </div>
                                </div>
                                <div class="preview-comercio-nombre">{{ $comercio_nombre ?: 'WIFI EXPRES' }}</div>
                                <span class="preview-ticket-id">PIN #1-0001</span>
                                <div class="preview-creds-box">
                                    <span class="preview-label">USUARIO</span>
                                    <span class="preview-text-value">827364</span>
                                    <span class="preview-label">CONTRASEÑA</span>
                                    <span class="preview-text-value">92837</span>
                                </div>
                                <div class="preview-plan-box text-uppercase">PLAN 2 HORAS</div>
                                <div class="preview-precio">$1.50</div>
                                <div class="preview-footer preview-hotspot text-uppercase">{{ $hotspot_url ?: 'portal.wifi' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- MODAL IMPRESIÓN --}}
    @if($isPrintModalOpen)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5); z-index: 1050;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header bg-success text-white border-0 p-4">
                    <h5 class="modal-title fw-bold"><i class="bi bi-printer me-2"></i>Imprimir Tickets</h5>
                    <button wire:click="closePrintModal" class="btn-close btn-close-white"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted d-block mb-2 text-uppercase">Modalidad</label>
                        <div class="btn-group w-100 shadow-sm rounded-pill overflow-hidden border">
                            <input type="radio" class="btn-check" wire:model="tipo_impresion" value="lote" id="printLote" autocomplete="off">
                            <label class="btn btn-outline-success border-0 fw-bold" for="printLote">POR LOTE</label>
                            <input type="radio" class="btn-check" wire:model="tipo_impresion" value="intervalo" id="printIntervalo" autocomplete="off">
                            <label class="btn btn-outline-success border-0 fw-bold" for="printIntervalo">INTERVALO</label>
                        </div>
                    </div>
                    @if($tipo_impresion == 'lote')
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">NÚMERO DE LOTE</label>
                            <input type="number" wire:model.defer="lote_imprimir" class="form-control text-center rounded-3 bg-light border-0 fs-5 fw-bold" placeholder="Ej: 1">
                        </div>
                    @else
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small fw-bold text-muted">DESDE (PIN)</label>
                                <input type="text" wire:model.defer="desde_ticket" class="form-control text-center rounded-3 bg-light border-0 fw-bold" placeholder="1-1-0001">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold text-muted">HASTA (PIN)</label>
                                <input type="text" wire:model.defer="hasta_ticket" class="form-control text-center rounded-3 bg-light border-0 fw-bold" placeholder="1-1-0020">
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button wire:click="printRange" class="btn btn-success w-100 rounded-pill px-4 fw-bold text-white shadow py-3">
                        <i class="bi bi-printer-fill me-1"></i> GENERAR PDF
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            window.addEventListener('abrirImpresion', event => {
                if(event.detail.url) {
                    window.open(event.detail.url, '_blank');
                }
            });
        });
    </script>

    <style>
        .loading-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.75); z-index: 9999; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(5px); }
        .real-ticket-preview { width: 145px; height: 380px; border: 1px solid #333; background-color: #fff; text-align: center; padding: 15px 5px; box-sizing: border-box; display: flex; flex-direction: column; position: relative; }
        .logo-wrapper { width: 60px; height: 60px; border-radius: 50%; overflow: hidden; background: #fff; border: 1px solid #eee; margin: 0 auto; display: flex; align-items: center; justify-content: center; }
        .preview-logo-img { max-width: 90%; max-height: 90%; object-fit: contain; }
        .preview-comercio-nombre { font-size: 8px; font-weight: bold; margin-top: 5px; text-transform: uppercase; }
        .preview-ticket-id { font-size: 7px; color: #666; }
        .preview-creds-box { background: #f4f4f4; padding: 6px 0; margin: 8px 0; border-radius: 4px; border: 1px solid #ddd; }
        .preview-label { font-size: 6px; color: #888; display: block; }
        .preview-text-value { font-size: 11px; font-weight: bold; display: block; font-family: monospace; }
        .preview-plan-box { background: #000; color: #fff; font-size: 9px; padding: 4px; font-weight: bold; }
        .preview-precio { font-size: 19px; font-weight: bold; margin-top: 5px; }
        .preview-footer { font-size: 7px; margin-top: auto; border-top: 1px dashed #ccc; padding-top: 5px; }
    </style>
</div>