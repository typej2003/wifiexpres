<div class="container-fluid py-4">
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="fas fa-signal text-success me-2"></i> Monitoreo de Usuarios en Tiempo Real</h6>
            <button wire:click="refreshRouterStatus" class="btn btn-sm btn-outline-light">
                <i class="fas fa-sync-alt"></i> Actualizar Estados
            </button>
        </div>
        <div class="card-body bg-light">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="small fw-bold text-muted">Filtrar por Aliado</label>
                    <select wire:model="selectedAliado" class="form-select form-select-sm">
                        <option value="">-- Todos los aliados --</option>
                        @foreach($aliados as $aliado)
                            <option value="{{ $aliado->id }}">{{ $aliado->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="small fw-bold text-muted">Seleccionar Router</label>
                    <select wire:model="router_id" class="form-select form-select-sm" {{ !$selectedAliado ? 'disabled' : '' }}>
                        <option value="">-- Seleccione un equipo --</option>
                        @foreach($routers as $r)
                            @php $isOnline = $routerStatus[$r->id] ?? false; @endphp
                            <option value="{{ $r->id }}">
                                {{ $isOnline ? '🟢' : '🔴' }} {{ $r->identity }} ({{ $r->macAddress }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 d-flex align-items-end">
                    @php $canScan = $router_id && ($routerStatus[$router_id] ?? false); @endphp
                    <button 
                        wire:click="scanUsers" 
                        class="btn btn-sm btn-success w-100 fw-bold" 
                        wire:loading.attr="disabled"
                        {{ !$canScan ? 'disabled' : '' }}>
                        <span wire:loading.remove wire:target="scanUsers, removeUser">
                            <i class="fas fa-bolt"></i> ESCANEAR AHORA
                        </span>
                        <span wire:loading wire:target="scanUsers, removeUser">
                            <i class="fas fa-circle-notch fa-spin"></i> PROCESANDO...
                        </span>
                    </button>
                </div>
            </div>

            @if($error_message)
                <div class="alert alert-warning mt-3 mb-0 py-2 small border-0 shadow-sm">
                    <i class="fas fa-exclamation-triangle me-2"></i> {{ $error_message }}
                </div>
            @endif

            @if($success_message)
                <div class="alert alert-success mt-3 mb-0 py-2 small border-0 shadow-sm">
                    <i class="fas fa-check-circle me-2"></i> {{ $success_message }}
                </div>
            @endif
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="bg-white">
                    <tr class="text-muted small text-uppercase">
                        <th class="px-3">Usuario</th>
                        <th>IP Address</th>
                        <th>MAC Address</th>
                        <th>Tiempo Conexión</th>
                        <th>Referencia / Comentario</th>
                        <th class="text-center">Acción</th>
                    </tr>
                </thead>
                <tbody class="border-top-0">
                    @forelse($users as $user)
                        <tr>
                            <td class="px-3">
                                <span class="badge bg-soft-primary text-primary border border-primary-soft">
                                    {{ $user['username'] }}
                                </span>
                            </td>
                            <td class="font-monospace small">{{ $user['ip'] }}</td>
                            <td class="font-monospace small text-muted">{{ $user['mac'] }}</td>
                            <td>
                                <i class="far fa-clock text-success me-1"></i> {{ $user['uptime'] }}
                            </td>
                            <td class="small text-truncate" style="max-width: 200px;">
                                {{ $user['comment'] }}
                            </td>
                            <td class="text-center">
                                <button 
                                    wire:click="removeUser('{{ $user['username'] }}')" 
                                    wire:loading.attr="disabled"
                                    class="btn btn-sm btn-outline-danger border-0 rounded-pill"
                                    title="Remover Usuario"
                                    onclick="confirm('¿Expulsar a este usuario?') || event.stopImmediatePropagation()">
                                    <i class="fas fa-user-times"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                @if($loading)
                                    <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                                    <span class="text-muted">Procesando solicitud en MikroTik...</span>
                                @else
                                    <div class="text-muted">
                                        <i class="fas fa-info-circle mb-2 fa-2x"></i><br>
                                        Seleccione un router activo y presione Escanear.
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .bg-soft-primary { background-color: rgba(13, 110, 253, 0.1); }
    .border-primary-soft { border-color: rgba(13, 110, 253, 0.2) !important; }
    .btn-outline-danger:hover { background-color: #dc3545; color: white; }
</style>