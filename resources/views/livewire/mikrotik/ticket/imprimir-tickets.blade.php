<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-11">
            <div class="card border-0 shadow-lg rounded-4">
                <div class="card-header bg-primary text-white p-4 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-printer-fill me-2"></i>CENTRO DE IMPRESIÓN MASIVA</h5>
                    <button wire:click="refreshStatus" class="btn btn-sm btn-light rounded-pill px-3 fw-bold">
                        <i class="bi bi-arrow-clockwise me-1"></i> STATUS CLOUD
                    </button>
                </div>

                <div class="card-body p-4">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Filtrar por Aliado</label>
                            <select wire:model="selectedAliado" class="form-select border-0 bg-light rounded-3 shadow-sm py-2">
                                <option value="">Todos los Aliados</option>
                                @foreach($aliados as $aliado)
                                    <option value="{{ $aliado->id }}">{{ $aliado->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Router MikroTik</label>
                            <select wire:model="selectedRouter" class="form-select border-0 bg-light rounded-3 shadow-sm py-2">
                                <option value="">Seleccione un equipo...</option>
                                @foreach($routersList as $r)
                                    <option value="{{ $r->id }}">
                                        {{ ($routerStatus[$r->id] ?? false) ? '🟢' : '🔴' }} {{ $r->identity }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @if($selectedRouter)
                        <div class="bg-light p-4 rounded-4 border border-primary mb-4" style="border-style: dashed !important;">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="small fw-bold">Modo</label>
                                    <select wire:model="tipo_impresion" class="form-select border-0 shadow-sm">
                                        <option value="lote">Por Lote</option>
                                        <option value="rango">Rango Manual</option>
                                    </select>
                                </div>
                                @if($tipo_impresion == 'lote')
                                    <div class="col-md-6">
                                        <label class="small fw-bold">Número de Lote</label>
                                        <input type="number" wire:model="lote_imprimir" class="form-control border-0 shadow-sm" placeholder="Ej: 1">
                                    </div>
                                @else
                                    <div class="col-md-3">
                                        <label class="small fw-bold">Desde</label>
                                        <input type="text" wire:model="desde_ticket" class="form-control border-0 shadow-sm" placeholder="R1-L1-001">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="small fw-bold">Hasta</label>
                                        <input type="text" wire:model="hasta_ticket" class="form-control border-0 shadow-sm" placeholder="R1-L1-100">
                                    </div>
                                @endif
                                <div class="col-md-3">
                                    <button wire:click="printRange" class="btn btn-primary w-100 fw-bold py-2 shadow">
                                        <i class="bi bi-file-pdf-fill me-1"></i> GENERAR PDF
                                    </button>
                                </div>
                            </div>
                            @if(session()->has('error')) <div class="text-danger small mt-2 fw-bold">{{ session('error') }}</div> @endif
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm align-middle border rounded-3 overflow-hidden">
                                <thead class="bg-dark text-white">
                                    <tr>
                                        <th class="ps-3">Identity</th>
                                        <th>Usuario</th>
                                        <th>Clave</th>
                                        <th>Plan</th>
                                        <th class="text-end pe-3">Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($tickets as $t)
                                        <tr>
                                            <td class="ps-3"><span class="badge bg-soft-primary text-primary">{{ $t->identity }}</span></td>
                                            <td><code>{{ $t->username }}</code></td>
                                            <td><code>{{ $t->password }}</code></td>
                                            <td>{{ $t->plan_name }}</td>
                                            <td class="text-end pe-3 small text-muted">{{ $t->created_at->format('d/m/y') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            {{ $tickets->links() }}
                        </div>
                    @else
                        <div class="text-center py-5 border rounded-4 bg-light opacity-50">
                            <i class="bi bi-printer display-1 text-muted"></i>
                            <p class="mt-2 fw-bold text-muted">Seleccione un router para habilitar el centro de impresión.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.addEventListener('abrirImpresion', event => {
        window.open(event.detail.url, '_blank');
    });
</script>