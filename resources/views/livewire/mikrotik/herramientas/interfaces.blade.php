<div class="container-fluid py-4" @if($esperandoRespuesta) wire:poll.2s="checkStatus" @endif>
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-dark text-white fw-bold">Panel de Control</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="small fw-bold">Aliado Comercial</label>
                        <select wire:model="selectedAliado" wire:change="refreshStatus" class="form-select shadow-none">
                            <option value="">-- Todos --</option>
                            @foreach($aliados as $aliado)
                                <option value="{{ $aliado->id }}">{{ $aliado->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">Router (Online)</label>
                        <select wire:model="router_id" class="form-select shadow-none">
                            <option value="">-- Seleccione Equipo --</option>
                            @foreach($routers as $r)
                                <option value="{{ $r->id }}">🟢 {{ $r->identity }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="btn btn-primary w-100 shadow-sm fw-bold" wire:click="cargarInterfaces" wire:loading.attr="disabled">
                        <i class="fas fa-search me-1" wire:loading.class="fa-spin"></i> LEER INTERFACES
                    </button>

                    <div class="mt-4 bg-dark text-success p-2 rounded shadow-inner" 
                         style="height: 200px; overflow-y: auto; font-family: 'Consolas', monospace; font-size: 0.8rem; border-left: 3px solid #0d6efd;">
                        @foreach(array_reverse($logs) as $log)
                            <div class="border-bottom border-secondary py-1 text-break">
                                <span class="text-muted small">[{{ now()->format('H:i:s') }}]</span> > {{ $log }}
                            </div>
                        @empty
                            <div class="text-muted italic">Esperando instrucción...</div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-network-wired text-primary me-2"></i>Interfaces de Red</h6>
                    @if($loading) 
                        <span class="badge bg-warning text-dark"><i class="fas fa-sync fa-spin"></i> Sincronizando...</span> 
                    @endif
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th class="ps-3">Estado</th>
                                    <th>Nombre</th>
                                    <th>Tipo</th>
                                    <th>MAC</th>
                                    <th class="text-center">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($interfaces as $int)
                                    <tr>
                                        <td class="ps-3">
                                            @if($int['disabled'] == 'true')
                                                <span class="badge bg-danger rounded-pill px-3">OFF</span>
                                            @else
                                                <span class="badge bg-success rounded-pill px-3">ON</span>
                                            @endif
                                        </td>
                                        <td class="fw-bold">{{ $int['name'] }}</td>
                                        <td><span class="badge bg-secondary opacity-75">{{ $int['type'] }}</span></td>
                                        <td><code>{{ $int['mac-address'] }}</code></td>
                                        <td class="text-center">
                                            <button wire:click="toggleInterface('{{ $int['name'] }}', '{{ $int['disabled'] }}')" 
                                                    class="btn btn-sm {{ $int['disabled'] == 'true' ? 'btn-success' : 'btn-danger' }} rounded-circle shadow-sm"
                                                    style="width: 32px; height: 32px;">
                                                <i class="fas {{ $int['disabled'] == 'true' ? 'fa-play' : 'fa-power-off' }}"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="fas fa-info-circle fa-2x mb-2 opacity-25"></i><br>
                                            Seleccione un router y presione el botón de lectura.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>