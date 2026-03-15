<div class="container-fluid py-4">
    <div class="card shadow-lg border-0" style="background-color: #1a1a1a;">
        <div class="card-header border-secondary d-flex justify-content-between align-items-center py-2" style="background-color: #2d2d2d;">
            <div class="d-flex align-items-center">
                <div class="rounded-circle bg-danger me-2" style="width: 12px; height: 12px;"></div>
                <div class="rounded-circle bg-warning me-2" style="width: 12px; height: 12px;"></div>
                <div class="rounded-circle bg-success me-2" style="width: 12px; height: 12px;"></div>
                <span class="ms-3 text-light font-monospace small">bridge_connector.sh — diagnósticos_v2</span>
            </div>
        </div>

        <div class="card-body p-4">
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label text-white-50 small fw-bold">ROUTER DESTINO</label>
                    <select wire:model="router_id" class="form-select bg-dark text-white border-secondary font-monospace shadow-none">
                        <option value="">Seleccione equipo...</option>
                        @foreach($routers as $r)
                            <option value="{{ $r->id }}">{{ strtoupper($r->identity) }} [{{ $r->macAddress }}]</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-white-50 small fw-bold">COMANDO MANUAL (ONE-LINE)</label>
                    <input type="text" wire:model.defer="command" class="form-control bg-black text-success border-secondary font-monospace shadow-none" placeholder="Ej: /system identity get name">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button wire:click="executeCommand()" wire:loading.attr="disabled" class="btn btn-success w-100 fw-bold shadow-sm">EJECUTAR</button>
                </div>
            </div>

            <div class="row g-3 mb-4 p-3 rounded" style="background-color: #252525; border: 1px dashed #444;">
                <div class="col-12 mt-0">
                    <label class="text-warning small fw-bold mb-2"><i class="bi bi-person-plus me-1"></i> CREAR USUARIO HOTSPOT</label>
                </div>
                <div class="col-md-3">
                    <input type="text" wire:model.defer="new_username" class="form-control form-control-sm bg-dark text-white border-secondary" placeholder="Usuario">
                </div>
                <div class="col-md-3">
                    <input type="text" wire:model.defer="new_password" class="form-control form-control-sm bg-dark text-white border-secondary" placeholder="Password">
                </div>
                <div class="col-md-3">
                    <select wire:model.defer="new_profile" class="form-select form-select-sm bg-dark text-white border-secondary">
                        <option value="neutro">neutro</option>
                        <option value="default">default</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button wire:click="createUser" class="btn btn-sm btn-warning w-100 fw-bold">ENVIAR COMANDO</button>
                </div>
            </div>

            <div class="row g-3 mb-4 p-3 rounded" style="background-color: #252525; border: 1px dashed #444;">
                <div class="col-12 mt-0">
                    <label class="text-warning small fw-bold mb-2"><i class="bi bi-person-gear me-1"></i> GESTIÓN DE USUARIO HOTSPOT</label>
                </div>
                <div class="col-md-3">
                    <input type="text" wire:model.defer="new_username" class="form-control form-control-sm bg-dark text-white border-secondary" placeholder="Usuario">
                </div>
                <div class="col-md-3">
                    <input type="text" wire:model.defer="new_password" class="form-control form-control-sm bg-dark text-white border-secondary" placeholder="Password">
                </div>
                <div class="col-md-2">
                    <select wire:model.defer="new_profile" class="form-select form-select-sm bg-dark text-white border-secondary">
                        <option value="neutro">neutro</option>
                        <option value="default">default</option>
                        <option value="1 Hora-1 (texto-1)">1 Hora-1 (texto-1)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button wire:click="createUser" class="btn btn-sm btn-warning w-100 fw-bold">CREAR</button>
                </div>
                <div class="col-md-2">
                    <button wire:click="changeProfile" class="btn btn-sm btn-info w-100 fw-bold">CAMBIAR PERFIL</button>
                </div>
            </div>

            <div class="mb-3">
                <span class="text-white-50 small me-2 font-monospace">INFO PRESETS:</span>
                <div class="btn-group shadow-sm">
                    <button wire:click="setPreset('identity')" class="btn btn-sm btn-outline-info text-light">Identity</button>
                    <button wire:click="setPreset('cpu')" class="btn btn-sm btn-outline-info text-light">CPU %</button>
                    <button wire:click="setPreset('uptime')" class="btn btn-sm btn-outline-info text-light">Uptime</button>
                    <button wire:click="setPreset('usuarios')" class="btn btn-sm btn-outline-info text-light">Users Count</button>
                </div>
            </div>

            <div class="position-relative">
                <div class="bg-black rounded border border-secondary p-3 shadow-inner" style="min-height: 400px; max-height: 500px; overflow-y: auto;">
                    <pre class="font-monospace text-success mb-0" style="white-space: pre-wrap; font-size: 0.85rem; line-height: 1.4;">{{ $terminal_output }}</pre>
                </div>
                
                <div wire:loading wire:target="executeCommand, createUser, setPreset" class="position-absolute top-50 start-50 translate-middle">
                    <div class="text-center bg-dark p-3 rounded border border-secondary shadow" style="min-width: 250px;">
                        <div class="spinner-border text-success mb-2" role="status"></div>
                        <div class="text-success font-monospace small">CONECTANDO CON MIKROTIK...</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card-footer border-secondary py-2" style="background-color: #2d2d2d;">
            <div class="row align-items-center text-muted small">
                <div class="col-md-6 font-monospace" style="font-size: 0.7rem;">
                    <i class="bi bi-terminal me-1"></i> Mode: HTTP-BRIDGE-SOCKET
                </div>
            </div>
        </div>
    </div>
</div>