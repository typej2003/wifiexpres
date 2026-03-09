<div class="container-fluid py-4">
    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-lg border-0 overflow-hidden">
        <div class="card-header bg-white py-3">
            <div class="row g-3 align-items-center">
                <div class="col-md-3">
                    <h5 class="m-0 fw-bold text-dark d-flex align-items-center">
                        <i class="bi bi-database-fill-down me-2 text-primary"></i> 
                        Logs @if($router_id) <span class="badge bg-light text-primary ms-2 border">Local DB</span> @endif
                        <span wire:loading class="spinner-border spinner-border-sm ms-2 text-primary"></span>
                    </h5>
                </div>
                <div class="col-md-9 text-md-end">
                    <div class="d-flex flex-wrap gap-2 justify-content-md-end">
                        <select wire:model="router_id" class="form-select form-select-sm w-auto shadow-none border-secondary">
                            <option value="">Seleccionar Router...</option>
                            @foreach($routers as $r)
                                <option value="{{ $r->id }}">{{ strtoupper($r->identity) }}</option>
                            @endforeach
                        </select>

                        <select wire:model="type_filter" class="form-select form-select-sm w-auto shadow-none border-secondary">
                            <option value="all">Tipos: Todos</option>
                            <option value="Hotspot">Hotspot</option>
                            <option value="Seguridad">Seguridad</option>
                            <option value="Sistema">Sistema</option>
                            <option value="Crítico">Crítico</option>
                        </select>

                        <select wire:model="status_filter" class="form-select form-select-sm w-auto shadow-none border-secondary">
                            <option value="all">Estados: Todos</option>
                            <option value="success">Éxitos</option>
                            <option value="warning">Avisos</option>
                            <option value="danger">Alertas</option>
                        </select>

                        <div class="btn-group shadow-sm">
                            <button wire:click="syncLogs" class="btn btn-sm btn-primary">
                                <i class="bi bi-arrow-repeat"></i> Sync
                            </button>
                            
                            {{-- BOTÓN PARA LIMPIAR HARDWARE MIKROTIK --}}
                            <button onclick="confirm('¿Deseas vaciar el buffer de memoria del MikroTik? Esto NO borrará los logs guardados en esta tabla.') || event.stopImmediatePropagation()" 
                                    wire:click="clearMikrotikBuffer" class="btn btn-sm btn-warning fw-bold text-dark">
                                <i class="bi bi-cpu me-1"></i> Limpiar Router
                            </button>

                            <button onclick="confirm('¿Borrar historial local de este sistema?') || event.stopImmediatePropagation()" 
                                    wire:click="clearLocalHistory" class="btn btn-sm btn-danger">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive" style="min-height: 400px;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small">
                        <tr>
                            <th class="ps-4" style="width: 200px;">TIEMPO MIKROTIK</th>
                            <th>ESTADO</th>
                            <th>TIPO</th>
                            <th>MENSAJE</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td class="ps-4 font-monospace small text-secondary">
                                    {{ $log->time_mikrotik }}
                                </td>
                                <td style="width: 140px;">
                                    <span class="badge rounded-pill bg-{{ $log->category }} bg-opacity-10 text-{{ $log->category }} px-3 py-2 border border-{{ $log->category }} border-opacity-25 w-100">
                                        {{ strtoupper($log->category) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary bg-opacity-10 text-dark border px-2 py-1" style="font-size: 0.7rem;">
                                        {{ $log->type }}
                                    </span>
                                </td>
                                <td>
                                    <div class="p-2 bg-light rounded-3 border-start border-4 border-{{ $log->category }} small text-dark">
                                        {{ $log->message }}
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-5 text-center text-muted">
                                    <i class="bi bi-inbox display-4 d-block mb-3 opacity-25"></i>
                                    No hay registros disponibles para mostrar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-footer bg-white py-3">
            <div class="d-flex justify-content-center">
                {{ $logs->links() }}
            </div>
        </div>
    </div>

    <style>
        .bg-opacity-10 { --bs-bg-opacity: 0.1; }
        .table > :not(caption) > * > * { padding: 0.8rem 0.5rem; }
        .pagination { margin-bottom: 0; }
        .page-link { color: #6500da; border-radius: 5px !important; margin: 0 2px; }
        .active > .page-link { background-color: #6500da !important; border-color: #6500da !important; }
    </style>
</div>