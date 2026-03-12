<div class="container-fluid py-4">
    {{-- OVERLAY DE CARGA CONTROLADO --}}
    @if($showOverlay)
    <div class="loading-overlay">
        <div class="text-center">
            <div class="spinner-border text-white mb-3" role="status" style="width: 3.5rem; height: 3.5rem;"></div>
            <h5 class="text-white fw-bold">PROCESANDO SOLICITUD</h5>
            <p class="text-white-50 small">Sincronizando datos con el MikroTik...</p>
        </div>
    </div>
    @endif

    {{-- ALERTAS --}}
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
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
                    <p class="text-muted small mb-0">Gestión de Pines de Acceso</p>
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
                    <span wire:loading.remove wire:target="syncPendingTickets">
                        <i class="bi bi-arrow-repeat text-warning me-1"></i> SINCRONIZAR
                    </span>
                    <span wire:loading wire:target="syncPendingTickets">
                        <i class="bi bi-arrow-repeat spin-icon text-warning me-1"></i> ESPERE...
                    </span>
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
                        <th class="py-3 text-muted small fw-bold border-0">CONSUMO</th>
                        <th class="text-end px-4 border-0">ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $t)
                        <tr wire:key="ticket-{{ $t->id }}" class="{{ $t->anulado ? 'opacity-50' : '' }}">
                            <td class="px-4">
                                <span class="fw-bold d-block text-dark">{{ $t->username }}</span>
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
                                    {{-- BOTÓN CON SPINNER --}}
                                    <button wire:click="{{ $t->anulado ? 'restaurarTicket' : 'anularTicket' }}({{ $t->id }})" 
                                            wire:loading.attr="disabled"
                                            wire:target="{{ $t->anulado ? 'restaurarTicket' : 'anularTicket' }}({{ $t->id }})"
                                            class="btn btn-white border-0" 
                                            title="{{ $t->anulado ? 'Restaurar' : 'Anular' }}">
                                        
                                        <span wire:loading.remove wire:target="{{ $t->anulado ? 'restaurarTicket' : 'anularTicket' }}({{ $t->id }})">
                                            <i class="bi {{ $t->anulado ? 'bi-arrow-counterclockwise text-success' : 'bi-x-circle text-danger' }}"></i>
                                        </span>
                                        
                                        <span wire:loading wire:target="{{ $t->anulado ? 'restaurarTicket' : 'anularTicket' }}({{ $t->id }})" class="spinner-border spinner-border-sm text-primary"></span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-5 text-muted">No hay tickets registrados en este router.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white p-3 border-0">{{ $tickets->links() }}</div>
    </div>

    {{-- MODAL GENERAR LOTE --}}
    @if($isBulkModalOpen)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5); z-index: 1050;">
        <div class="modal-dialog modal-dialog-centered" wire:init="loadMikrotikProfiles">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header bg-dark text-white border-0 p-4">
                    <h5 class="modal-title fw-bold">Generación de Tickets</h5>
                    @if($bulk_step === 'input')
                        <button wire:click="closeBulkModal" class="btn-close btn-close-white"></button>
                    @endif
                </div>
                
                <div class="modal-body p-4 text-center">
                    @if(count($mikrotik_profiles) == 0)
                        <div class="py-4">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="text-muted small mt-2">Cargando perfiles desde MikroTik...</p>
                        </div>
                    @else
                        @if($bulk_step === 'input')
                            <div class="mb-3 text-start">
                                <label class="form-label small fw-bold text-muted">CANTIDAD TOTAL A GENERAR</label>
                                <input type="number" wire:model.defer="bulk_count" class="form-control rounded-3 border-0 bg-light fw-bold fs-4 text-center">
                                <small class="text-muted d-block mt-1">Se procesarán en bloques de 30 para mayor estabilidad.</small>
                            </div>
                            <div class="mb-4 text-start">
                                <label class="form-label small fw-bold text-muted">SELECCIONE EL PLAN</label>
                                <select wire:model.defer="bulk_plan" class="form-select rounded-3 border-0 bg-light fw-bold">
                                    <option value="">Seleccione un perfil...</option>
                                    @foreach($mikrotik_profiles as $p)
                                        <option value="{{ $p['name'] }}">{{ $p['display'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button wire:click="startBulkGeneration" wire:loading.attr="disabled" class="btn btn-dark w-100 rounded-pill py-3 fw-bold shadow">
                                <span wire:loading.remove wire:target="startBulkGeneration text-white">INICIAR PROCESO</span>
                                <span wire:loading wire:target="startBulkGeneration text-white">PROCESANDO...</span>
                            </button>
                        @else
                            <div class="py-3">
                                <h6 class="fw-bold mb-3 text-uppercase text-muted">Estado del Lote</h6>
                                @php $porcentaje = $bulk_total_requested > 0 ? ($bulk_current_count / $bulk_total_requested) * 100 : 0; @endphp
                                <div class="progress rounded-pill mb-3 shadow-sm" style="height: 25px;">
                                    <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated" style="width: {{ $porcentaje }}%">{{ round($porcentaje) }}%</div>
                                </div>
                                <p class="fw-bold mb-4">
                                    <span class="text-primary fs-4">{{ $bulk_current_count }}</span> 
                                    <span class="text-muted">/ {{ $bulk_total_requested }} tickets creados</span>
                                </p>

                                @if($bulk_current_count < $bulk_total_requested)
                                    <button wire:click="processNextChunk" wire:loading.attr="disabled" class="btn btn-primary w-100 rounded-pill py-3 fw-bold shadow-lg fs-5">
                                        <span wire:loading.remove wire:target="processNextChunk"><i class="bi bi-play-fill me-1"></i> SIGUIENTE BLOQUE</span>
                                        <span wire:loading wire:target="processNextChunk"><span class="spinner-border spinner-border-sm me-2"></span> ENVIANDO...</span>
                                    </button>
                                @else
                                    <button wire:click="finishBulk" class="btn btn-dark w-100 rounded-pill py-3 fw-bold">FINALIZAR Y VER TICKETS</button>
                                @endif
                            </div>
                        @endif
                    @endif
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
                    <h5 class="modal-title fw-bold"><i class="bi bi-printer me-2"></i>Imprimir Lote</h5>
                    <button wire:click="closePrintModal" class="btn-close btn-close-white"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted d-block mb-2">MÉTODO DE SELECCIÓN</label>
                        <div class="btn-group w-100 shadow-sm rounded-pill overflow-hidden border">
                            <input type="radio" class="btn-check" wire:model="tipo_impresion" value="lote" id="printLote" autocomplete="off">
                            <label class="btn btn-outline-success border-0 fw-bold" for="printLote">POR LOTE</label>
                            <input type="radio" class="btn-check" wire:model="tipo_impresion" value="intervalo" id="printIntervalo" autocomplete="off">
                            <label class="btn btn-outline-success border-0 fw-bold" for="printIntervalo">RANGO</label>
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
                                <input type="text" wire:model.defer="hasta_ticket" class="form-control text-center rounded-3 bg-light border-0 fw-bold" placeholder="1-1-0030">
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button wire:click="closePrintModal" class="btn btn-light rounded-pill px-4">Cancelar</button>
                    <button wire:click="printRange" class="btn btn-success rounded-pill px-4 fw-bold text-white shadow">
                        <i class="bi bi-file-pdf me-1"></i> GENERAR PDF
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- MODAL CONFIGURACIÓN --}}
    @if($isConfigModalOpen)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5); z-index: 1050;">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
                <div class="modal-header border-bottom p-4">
                    <h5 class="modal-title fw-bold"><i class="bi bi-palette2 me-2"></i>Personalizar Ticket</h5>
                    <button wire:click="closeConfigModal" class="btn-close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="row g-0">
                        <div class="col-md-7 p-4 border-end">
                            <div class="mb-4">
                                <label class="form-label small fw-bold text-muted">LOGOTIPO</label>
                                <div class="d-flex align-items-center gap-3 p-3 border rounded-4 bg-light">
                                    <div class="bg-white p-1 rounded shadow-sm d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                        @if($nuevo_logo) <img src="{{ $nuevo_logo->temporaryUrl() }}" style="max-width: 100%; max-height: 100%;">
                                        @elseif($logo_actual) <img src="{{ asset('storage/'.$logo_actual) }}" style="max-width: 100%; max-height: 100%;">
                                        @else <i class="bi bi-image text-muted fs-3"></i> @endif
                                    </div>
                                    <div class="flex-grow-1">
                                        <input type="file" wire:model="nuevo_logo" class="form-control form-control-sm border-0 bg-transparent shadow-none">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted text-start d-block">NOMBRE DEL NEGOCIO</label>
                                <input type="text" wire:model="comercio_nombre" class="form-control rounded-3 border-0 bg-light shadow-none">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted text-start d-block">URL DEL PORTAL</label>
                                <input type="text" wire:model="hotspot_url" class="form-control rounded-3 border-0 bg-light shadow-none">
                            </div>
                        </div>
                        <div class="col-md-5 bg-light d-flex justify-content-center align-items-center py-5">
                            <div class="real-ticket-preview shadow-lg">
                                <div class="preview-logo-container">
                                    <div class="logo-wrapper">
                                        @if($nuevo_logo) <img src="{{ $nuevo_logo->temporaryUrl() }}" class="preview-logo-img">
                                        @elseif($logo_actual) <img src="{{ asset('storage/'.$logo_actual) }}" class="preview-logo-img">
                                        @else <i class="bi bi-wifi text-primary"></i> @endif
                                    </div>
                                </div>
                                <div class="preview-comercio-nombre">{{ $comercio_nombre ?: 'WIFI EXPRES' }}</div>
                                <span class="preview-ticket-id small text-muted">PIN #1-1-0001</span>
                                <div class="preview-creds-box">
                                    <span class="preview-label">USUARIO</span>
                                    <span class="preview-text-value">827364</span>
                                    <span class="preview-label mt-1">CONTRASEÑA</span>
                                    <span class="preview-text-value">92837</span>
                                </div>
                                <div class="preview-plan-box">PLAN 2 HORAS</div>
                                <div class="preview-precio fs-3 fw-bold">$1.50</div>
                                <div class="preview-qr mt-2"><i class="bi bi-qr-code" style="font-size: 60px;"></i></div>
                                <div class="preview-footer mt-auto border-top pt-2 small fw-bold">{{ $hotspot_url ?: 'portal.wifi' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button wire:click="saveConfig" class="btn btn-primary w-100 rounded-pill fw-bold py-3 shadow">
                        <i class="bi bi-save me-1"></i> APLICAR DISEÑO
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            window.livewire.on('abrirImpresion', url => {
                window.open(url, '_blank');
            });
        });
    </script>

    <style>
        .loading-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); z-index: 9999; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px); }
        .spin-icon { animation: spin 1s linear infinite; display: inline-block; }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        .btn-white { background-color: #fff; }
        .real-ticket-preview { width: 160px; height: 380px; background: #fff; border: 1px solid #ddd; padding: 15px 10px; display: flex; flex-direction: column; text-align: center; border-radius: 4px; }
        .logo-wrapper { width: 50px; height: 50px; margin: 0 auto; display: flex; align-items: center; justify-content: center; }
        .preview-logo-img { max-width: 100%; max-height: 100%; }
        .preview-creds-box { background: #f8f9fa; padding: 8px; margin: 10px 0; border: 1px solid #eee; border-radius: 6px; }
        .preview-label { font-size: 9px; color: #777; display: block; }
        .preview-text-value { font-size: 13px; font-weight: bold; font-family: monospace; display: block; }
        .preview-plan-box { background: #000; color: #fff; font-size: 10px; padding: 4px; font-weight: bold; margin: 5px 0; }
        .preview-comercio-nombre { font-weight: 800; font-size: 12px; margin-top: 5px; text-transform: uppercase; }
    </style>
</div>