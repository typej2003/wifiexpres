<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-11 col-lg-10">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="card-header bg-primary text-white p-4 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-uppercase tracking-wider">
                        <i class="bi bi-printer-fill me-2"></i>Centro de Impresión de Tickets
                    </h5>
                    <button wire:click="refreshStatus" class="btn btn-sm btn-light rounded-pill px-3 fw-bold">
                        <i class="bi bi-arrow-clockwise me-1"></i> REFRESCAR ESTADOS
                    </button>
                </div>

                <div class="card-body p-4 p-lg-5">
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Aliado</label>
                            <select wire:model="selectedAliado" class="form-select border-0 bg-light rounded-3 shadow-sm py-2">
                                <option value="">Todos los Aliados</option>
                                @foreach($aliados as $aliado)
                                    <option value="{{ $aliado->id }}">{{ $aliado->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Router MikroTik</label>
                            <select wire:model="selectedRouter" class="form-select border-0 bg-light rounded-3 shadow-sm py-2">
                                <option value="">Seleccione...</option>
                                @foreach($routersList as $r)
                                    @php $online = $routerStatus[$r->id] ?? false; @endphp
                                    <option value="{{ $r->id }}">
                                        {{ $online ? '🟢' : '🔴' }} {{ $r->identity }} ({{ $r->macAddress }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @if($selectedRouter)
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="bg-light p-4 rounded-4 border border-info" style="border-style: dashed !important;">
                                    <h6 class="fw-bold text-info text-uppercase mb-4">
                                        <i class="bi bi-gear-wide-connected me-1"></i> Parámetros de Impresión
                                    </h6>
                                    
                                    @if(session()->has('error'))
                                        <div class="alert alert-danger border-0 shadow-sm">{{ session('error') }}</div>
                                    @endif

                                    <div class="row g-3 align-items-end">
                                        <div class="col-md-3">
                                            <label class="small fw-bold">Modo de Selección</label>
                                            <select wire:model="tipo_impresion" class="form-select border-0 shadow-sm">
                                                <option value="lote">Por Lote</option>
                                                <option value="rango">Por Rango Personalizado</option>
                                            </select>
                                        </div>

                                        @if($tipo_impresion == 'lote')
                                            <div class="col-md-4">
                                                <label class="small fw-bold">Número de Lote</label>
                                                <input type="number" wire:model="lote_imprimir" class="form-control border-0 shadow-sm" placeholder="Ej: 5">
                                            </div>
                                        @else
                                            <div class="col-md-3">
                                                <label class="small fw-bold">Desde (Identity)</label>
                                                <input type="text" wire:model="desde_ticket" class="form-control border-0 shadow-sm" placeholder="R1-L5-001">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="small fw-bold">Hasta (Identity)</label>
                                                <input type="text" wire:model="hasta_ticket" class="form-control border-0 shadow-sm" placeholder="R1-L5-100">
                                            </div>
                                        @endif

                                        <div class="col-md-2">
                                            <button wire:click="printRange" class="btn btn-primary w-100 fw-bold shadow-sm py-2">
                                                <i class="bi bi-file-pdf"></i> PDF
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive rounded-4 shadow-sm border">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-dark text-white">
                                    <tr>
                                        <th class="py-3 ps-4">ID</th>
                                        <th>Identity</th>
                                        <th>Username</th>
                                        <th>Password</th>
                                        <th>Plan</th>
                                        <th class="pe-4 text-end">Creado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($tickets as $t)
                                        <tr>
                                            <td class="ps-4 fw-bold text-muted">{{ $t->id }}</td>
                                            <td><span class="badge bg-soft-primary text-primary">{{ $t->identity }}</span></td>
                                            <td><code class="fw-bold">{{ $t->username }}</code></td>
                                            <td><code>{{ $t->password }}</code></td>
                                            <td>{{ $t->plan_name }}</td>
                                            <td class="pe-4 text-end small">{{ $t->created_at->format('d/m/Y H:i') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-5 text-muted">No hay tickets generados en este router.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            {{ $tickets->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-printer text-muted display-1 opacity-25"></i>
                            <h4 class="mt-3 text-muted fw-light">Seleccione un router para comenzar</h4>
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