<div class="container-fluid py-4">
    {{-- HEADER --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <h4 class="fw-bold mb-1 text-dark">
                        <i class="bi bi-printer-fill text-success me-2"></i>Centro de Impresión
                    </h4>
                    <p class="text-muted small mb-0">Seleccione un aliado y router para gestionar tickets</p>
                </div>
                
                {{-- SELECTORES --}}
                <div class="col-md-8">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="small fw-bold text-muted ms-2">ALIADO REGISTRADO</label>
                            <select wire:model="selectedAliado" class="form-select rounded-pill border-0 bg-light shadow-sm">
                                <option value="">-- Seleccionar Aliado --</option>
                                @foreach($aliados as $aliado)
                                    <option value="{{ $aliado->id }}">{{ $aliado->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold text-muted ms-2">ROUTER / NODO</label>
                            <select wire:model="selectedRouter" class="form-select rounded-pill border-0 bg-light shadow-sm" {{ empty($routers) ? 'disabled' : '' }}>
                                <option value="">-- Seleccionar Router --</option>
                                @foreach($routers as $router)
                                    @php $isInactive = $router->state !== 'active' && $router->state !== 'activo'; @endphp
                                    <option value="{{ $router->id }}" {{ $isInactive ? 'disabled' : '' }}>
                                        {{ $router->identity }} {{ $isInactive ? '(INACTIVO)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- PANEL DE CONFIGURACIÓN DE IMPRESIÓN --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <h6 class="fw-bold mb-0">Opciones de Impresión</h6>
                </div>
                <div class="card-body p-4">
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted d-block mb-2">MÉTODO</label>
                        <div class="btn-group w-100 shadow-sm rounded-pill overflow-hidden border">
                            <input type="radio" class="btn-check" wire:model="tipo_impresion" value="lote" id="printLote" checked>
                            <label class="btn btn-outline-success border-0 fw-bold" for="printLote">POR LOTE</label>
                            
                            <input type="radio" class="btn-check" wire:model="tipo_impresion" value="intervalo" id="printIntervalo">
                            <label class="btn btn-outline-success border-0 fw-bold" for="printIntervalo">RANGO</label>
                        </div>
                    </div>

                    @if($tipo_impresion == 'lote')
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted">NÚMERO DE LOTE</label>
                            <input type="number" wire:model.defer="lote_imprimir" class="form-control form-control-lg text-center rounded-3 bg-light border-0 fw-bold" placeholder="Ej: 5">
                        </div>
                    @else
                        <div class="row g-2 mb-4">
                            <div class="col-6">
                                <label class="form-label small fw-bold text-muted">DESDE (PIN)</label>
                                <input type="text" wire:model.defer="desde_ticket" class="form-control text-center rounded-3 bg-light border-0 fw-bold" placeholder="1-1-0001">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold text-muted">HASTA (PIN)</label>
                                <input type="text" wire:model.defer="hasta_ticket" class="form-control text-center rounded-3 bg-light border-0 fw-bold" placeholder="1-1-0050">
                            </div>
                        </div>
                    @endif

                    <button wire:click="printRange" wire:loading.attr="disabled" class="btn btn-success w-100 rounded-pill py-3 fw-bold shadow-sm">
                        <span wire:loading.remove wire:target="printRange">
                            <i class="bi bi-file-earmark-pdf-fill me-2"></i>GENERAR PDF
                        </span>
                        <span wire:loading wire:target="printRange">
                            <span class="spinner-border spinner-border-sm me-2"></span>PROCESANDO...
                        </span>
                    </button>
                    
                    @if (session()->has('error'))
                        <div class="alert alert-danger border-0 small mt-3 mb-0 rounded-3">
                            {{ session('error') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- TABLA DE VISTA PREVIA --}}
        <div class="col-md-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-4 py-3 text-muted small fw-bold border-0">IDENTIDAD / PIN</th>
                                <th class="py-3 text-muted small fw-bold border-0">PLAN</th>
                                <th class="py-3 text-muted small fw-bold border-0 text-center">ESTADO</th>
                                <th class="px-4 py-3 text-muted small fw-bold border-0 text-end">COSTO</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($selectedRouter)
                                @forelse($tickets as $t)
                                    <tr>
                                        <td class="px-4">
                                            <span class="fw-bold d-block text-dark">{{ $t->username }}</span>
                                            <small class="text-muted font-monospace">Pass: {{ $t->password }}</small>
                                        </td>
                                        <td><span class="badge bg-light text-dark border fw-bold">{{ $t->plan }}</span></td>
                                        <td class="text-center">
                                            @php $color = ['disponible'=>'success','en_uso'=>'info','anulado'=>'danger'][$t->estado] ?? 'secondary'; @endphp
                                            <span class="badge bg-{{ $color }} rounded-pill px-3" style="font-size: 0.7rem;">{{ strtoupper($t->estado) }}</span>
                                        </td>
                                        <td class="text-end px-4 fw-bold text-primary">${{ number_format($t->costo, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center py-5 text-muted">No se encontraron tickets para este router.</td></tr>
                                @endforelse
                            @else
                                <tr><td colspan="4" class="text-center py-5 text-muted">Seleccione un router para ver los tickets disponibles.</td></tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                @if($selectedRouter && $tickets->hasPages())
                    <div class="card-footer bg-white p-3 border-0">
                        {{ $tickets->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            window.livewire.on('abrirImpresion', data => {
                window.open(data.url, '_blank');
            });
        });
    </script>
</div>