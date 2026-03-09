<div class="container-fluid py-4">
    {{-- OVERLAY DE CARGA CONTROLADO --}}
    @if($showOverlay)
    <div class="loading-overlay">
        <div class="text-center">
            <div class="spinner-border text-white mb-3" role="status" style="width: 3.5rem; height: 3.5rem;"></div>
            <h5 class="text-white fw-bold">PROCESANDO SOLICITUD</h5>
            <p class="text-white-50 small">Esto puede tardar un poco dependiendo de la cantidad de tickets...</p>
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
                        <th class="py-3 text-muted small fw-bold border-0">TIEMPO</th>
                        <th class="text-end px-4 border-0">ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $t)
                        <tr wire:key="ticket-{{ $t->id }}" class="{{ $t->anulado ? 'opacity-50' : '' }}">
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
                                    <button wire:click="edit({{ $t->id }})" class="btn btn-white border-0"><i class="bi bi-pencil text-primary"></i></button>
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

    {{-- MODAL ADVERTENCIA DE DEPURACIÓN --}}
    @if($showSyncWarning)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5); z-index: 1060;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header bg-warning text-dark border-0 p-4">
                    <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>Inconsistencia Detectada</h5>
                </div>
                <div class="modal-body p-4 text-center">
                    <i class="bi bi-database-dash text-warning" style="font-size: 3rem;"></i>
                    <p class="mt-3 mb-0">Se han detectado <strong>{{ count($ticketsToDelete) }}</strong> tickets en la base de datos que no existen en el MikroTik.</p>
                </div>
                <div class="modal-footer border-0 p-4 pt-0 d-flex justify-content-between">
                    <button wire:click="cancelCleaning" class="btn btn-light rounded-pill px-4">IGNORAR</button>
                    <button wire:click="proceedToCleanDatabase" class="btn btn-danger rounded-pill px-4 fw-bold shadow">DEPURAR BD</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- MODAL GENERAR LOTE (ADAPTADO PARA PROGRESO POR BLOQUES) --}}
    @if($isBulkModalOpen)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5); z-index: 1050;">
        <div class="modal-dialog" style="margin-top: 8rem !important;" wire:init="loadMikrotikProfiles">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header bg-dark text-white border-0 p-4">
                    <h5 class="modal-title fw-bold">Generar Lote</h5>
                    <button wire:click="closeBulkModal" class="btn-close btn-close-white"></button>
                </div>
                
                <div class="modal-body p-4 text-center">
                    @if(count($mikrotik_profiles) == 0)
                        <div class="py-3">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="text-muted small mt-2">Obteniendo perfiles de MikroTik...</p>
                        </div>
                    @else
                        @if($bulk_step === 'input')
                            {{-- FASE 1: ENTRADA DE DATOS --}}
                            <div class="mb-3 text-start">
                                <label class="form-label small fw-bold">CANTIDAD DE TICKETS</label>
                                <input type="number" wire:model.defer="bulk_count" class="form-control rounded-3 border-0 bg-light fw-bold fs-5">
                            </div>
                            <div class="mb-3 text-start">
                                <label class="form-label small fw-bold">PLAN ASOCIADO</label>
                                <select wire:model.defer="bulk_plan" class="form-select rounded-3 border-0 bg-light">
                                    <option value="">Seleccione un plan...</option>
                                    @foreach($mikrotik_profiles as $p)
                                        <option value="{{ $p['name'] }}">{{ $p['display'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            {{-- FASE 2: PROGRESO CONTROLADO POR EL USUARIO --}}
                            <div class="py-3">
                                <h6 class="fw-bold mb-3">Progreso de Creación</h6>
                                <div class="progress rounded-pill mb-3" style="height: 15px;">
                                    <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated" 
                                         role="progressbar" 
                                         style="width: {{ ($bulk_current_count / $bulk_total_requested) * 100 }}%">
                                    </div>
                                </div>
                                <p class="small text-muted mb-0">
                                    Has creado <strong>{{ $bulk_current_count }}</strong> de <strong>{{ $bulk_total_requested }}</strong> tickets.
                                </p>

                                @if(session()->has('chunk_message'))
                                    <div class="alert alert-info py-2 mt-3 small rounded-3 border-0">
                                        <i class="bi bi-info-circle-fill me-1"></i> {{ session('chunk_message') }}
                                    </div>
                                @endif
                            </div>
                        @endif
                    @endif
                </div>

                <div class="modal-footer border-0 p-4 pt-0 d-flex justify-content-center">
                    @if($bulk_step === 'input')
                        <button wire:click="closeBulkModal" class="btn btn-light rounded-pill px-4">Cancelar</button>
                        <button wire:click="startBulkGeneration" 
                                wire:loading.attr="disabled"
                                class="btn btn-dark rounded-pill px-4 fw-bold shadow" 
                                @if(count($mikrotik_profiles) == 0) disabled @endif>
                            <span wire:loading.remove wire:target="startBulkGeneration">INICIAR PROCESO</span>
                            <span wire:loading wire:target="startBulkGeneration">
                                <i class="bi bi-arrow-repeat spin-icon me-1"></i> ESPERE...
                            </span>
                        </button>
                    @else
                        @if(session()->has('chunk_message'))
                            <div class="alert alert-success alert-dismissible fade show">
                                {{ session('chunk_message') }}
                            </div>
                            
                            <button type="button" 
                                    class="btn btn-primary btn-lg w-100 d-flex align-items-center justify-content-center" 
                                    wire:click="processNextChunk" 
                                    wire:loading.attr="disabled">
                                
                                <span wire:loading.remove wire:target="processNextChunk">
                                    Crear siguientes {{ session('next_amount', 30) }}
                                </span>

                                <span wire:loading wire:target="processNextChunk">
                                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                    Procesando comando en MikroTik...
                                </span>
                            </button>
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
        <div class="modal-dialog" style="margin-top: 8rem !important;">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header bg-success text-white border-0 p-4">
                    <h5 class="modal-title fw-bold"><i class="bi bi-printer me-2"></i>Imprimir Tickets</h5>
                    <button wire:click="closePrintModal" class="btn-close btn-close-white"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted d-block mb-2 text-uppercase">Modalidad de Impresión</label>
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
                            <div class="col-6 text-center">
                                <label class="form-label small fw-bold text-muted text-uppercase">Desde</label>
                                <input type="text" wire:model.defer="desde_ticket" class="form-control text-center rounded-3 bg-light border-0 fw-bold" placeholder="1-0001">
                            </div>
                            <div class="col-6 text-center">
                                <label class="form-label small fw-bold text-muted text-uppercase">Hasta</label>
                                <input type="text" wire:model.defer="hasta_ticket" class="form-control text-center rounded-3 bg-light border-0 fw-bold" placeholder="1-0010">
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button wire:click="closePrintModal" class="btn btn-light rounded-pill px-4">Cerrar</button>
                    <button wire:click="printRange" class="btn btn-success rounded-pill px-4 fw-bold text-white shadow">
                        <i class="bi bi-printer-fill me-1"></i> GENERAR PDF
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- MODAL DISEÑO --}}
    @if($isConfigModalOpen)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5); z-index: 1050;">
        <div class="modal-dialog modal-lg" style="margin-top: 8rem !important;">
            <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
                <div class="modal-header border-bottom p-4">
                    <h5 class="modal-title fw-bold"><i class="bi bi-palette2 me-2"></i>Diseño del Ticket</h5>
                    <button wire:click="closeConfigModal" class="btn-close"></button>
                </div>
                <div class="modal-body p-0" style="max-height: 60vh; overflow-y: auto;">
                    <div class="row g-0">
                        <div class="col-md-7 p-4 border-end">
                            <div class="mb-4">
                                <label class="form-label small fw-bold text-muted">LOGO DEL COMERCIO</label>
                                <div class="d-flex align-items-center gap-3 p-3 border rounded-4 bg-light">
                                    <div class="bg-white p-2 rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                        @if($nuevo_logo)
                                            <img src="{{ $nuevo_logo->temporaryUrl() }}" style="max-width: 100%; max-height: 100%; border-radius: 50%;">
                                        @elseif($logo_actual)
                                            <img src="{{ asset('storage/'.$logo_actual) }}" style="max-width: 100%; max-height: 100%; border-radius: 50%;">
                                        @else
                                            <i class="bi bi-image text-muted fs-3"></i>
                                        @endif
                                    </div>
                                    <div class="flex-grow-1">
                                        <input type="file" wire:model="nuevo_logo" class="form-control form-control-sm border-0 bg-transparent shadow-none">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">NOMBRE COMERCIAL</label>
                                <input type="text" wire:model="comercio_nombre" class="form-control rounded-3 border-0 bg-light shadow-none">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">URL HOTSPOT (PORTAL)</label>
                                <input type="text" wire:model="hotspot_url" class="form-control rounded-3 border-0 bg-light shadow-none">
                            </div>
                        </div>
                        <div class="col-md-5 bg-secondary bg-opacity-10 d-flex justify-content-center py-5">
                            <div class="real-ticket-preview shadow-lg">
                                <div class="preview-logo-container">
                                    <div class="logo-wrapper shadow-sm">
                                        @if($nuevo_logo) <img src="{{ $nuevo_logo->temporaryUrl() }}" class="preview-logo-img">
                                        @elseif($logo_actual) <img src="{{ asset('storage/'.$logo_actual) }}" class="preview-logo-img">
                                        @else <i class="bi bi-wifi text-primary"></i> @endif
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
                                <div class="preview-qr"><i class="bi bi-qr-code" style="font-size: 85px;"></i></div>
                                <div class="preview-footer preview-hotspot text-uppercase">{{ $hotspot_url ?: 'portal.wifi' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4">
                    <button wire:click="saveConfig" class="btn btn-primary rounded-pill w-100 fw-bold py-3 shadow">GUARDAR CAMBIOS</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- MODAL EDICIÓN --}}
    @if($isModalOpen)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5); z-index: 1050;">
        <div class="modal-dialog" style="margin-top: 8rem !important;">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header p-4 border-bottom">
                    <h5 class="modal-title fw-bold">Editar Ticket</h5>
                    <button wire:click="closeModal" class="btn-close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">PIN / IDENTIDAD</label>
                        <input type="text" wire:model.defer="identity" class="form-control rounded-3 border-0 bg-light">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">USUARIO</label>
                            <input type="text" wire:model.defer="username" class="form-control rounded-3 border-0 bg-light">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">CLAVE</label>
                            <input type="text" wire:model.defer="password" class="form-control rounded-3 border-0 bg-light">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button wire:click="closeModal" class="btn btn-light rounded-pill px-4">Cerrar</button>
                    <button wire:click="closeModal" class="btn btn-primary rounded-pill px-4 fw-bold shadow">ACTUALIZAR</button>
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
        .loading-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.75); z-index: 9999; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(5px); }
        .spin-icon { animation: spin 1s linear infinite; display: inline-block; }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        .btn-white { background-color: #fff; }
        .real-ticket-preview { width: 145px; height: 400px; border: 1px solid #333; background-color: #fff; text-align: center; padding: 20px 5px 10px 5px; box-sizing: border-box; display: flex; flex-direction: column; font-family: 'Helvetica', sans-serif; position: relative; }
        .preview-logo-container { height: 65px; margin-bottom: 10px; display: flex; align-items: center; justify-content: center; }
        .logo-wrapper { width: 60px; height: 60px; border-radius: 50%; overflow: hidden; background: #fff; border: 1px solid #eee; display: flex; align-items: center; justify-content: center; }
        .preview-logo-img { max-width: 90%; max-height: 90%; object-fit: contain; }
        .preview-comercio-nombre { font-size: 8px; font-weight: bold; text-transform: uppercase; height: 22px; line-height: 11px; overflow: hidden; color: #000; }
        .preview-ticket-id { font-size: 7px; color: #666; display: block; }
        .preview-creds-box { background: #f4f4f4; padding: 6px 0; margin: 8px 0; border-radius: 4px; border: 1px solid #ddd; }
        .preview-label { font-size: 6px; color: #888; display: block; }
        .preview-text-value { font-size: 11px; font-weight: bold; display: block; font-family: monospace; }
        .preview-plan-box { background: #000; color: #fff; font-size: 9px; padding: 4px; font-weight: bold; margin: 8px 0; text-transform: uppercase; }
        .preview-precio { font-size: 19px; font-weight: bold; margin: 5px 0; color: #000; }
        .preview-qr { margin: 10px 0; flex-grow: 1; }
        .preview-footer { font-size: 7px; margin-top: auto; }
        .preview-hotspot { font-weight: bold; border-top: 1px dashed #ccc; padding-top: 5px; color: #444; }
    </style>
</div>