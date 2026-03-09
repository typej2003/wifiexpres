<div>
    <div class="content-header">
        <div class="container-fluid">
            <h1>Configuraciones del Sistema</h1>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <form wire:submit.prevent="updateSetting">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card card-primary shadow-sm">
                            <div class="card-header"><h3 class="card-title">Información del Sitio</h3></div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Nombre del Sitio</label>
                                    <input wire:model.defer="state.site_name" type="text" class="form-control">
                                </div>
                                <div class="row">
                                    <div class="col-4">
                                        <label>Moneda</label>
                                        <select wire:model.defer="state.currency" class="form-control">
                                            <option value="$">$ USD</option>
                                            <option value="Bs">Bs (Digital)</option>
                                        </select>
                                    </div>
                                    <div class="col-4">
                                        <label>API BCV</label>
                                        <select wire:model.defer="state.api_bcv" class="form-control">
                                            <option value="NO">Desactivada</option>
                                            <option value="SI">Activada</option>
                                        </select>
                                    </div>
                                    <div class="col-4">
                                        <label>Tasa Manual</label>
                                        <input wire:model.defer="state.dollar_rate" type="number" step="0.01" class="form-control" placeholder="Ej: 36.50">
                                    </div>
                                </div>
                                <small class="text-muted mt-2 d-block">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Si la API está activada, la tasa manual se usará solo como respaldo en caso de error.
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card card-dark shadow-sm">
                            <div class="card-header"><h3 class="card-title">Conectividad MikroTik</h3></div>
                            <div class="card-body">
                                <label>Modo de acceso al Router:</label>
                                <div class="row text-center mb-3">
                                    <div class="col-6">
                                        <div wire:click="$set('state.mikrotik_connection_mode', 0)" 
                                             class="info-box shadow-none border {{ $state['mikrotik_connection_mode'] == 0 ? 'bg-primary border-primary' : 'bg-light' }}" 
                                             style="cursor:pointer;">
                                            <span class="info-box-icon"><i class="fas fa-network-wired"></i></span>
                                            <div class="info-box-content"><b>LOCAL (IP)</b></div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div wire:click="$set('state.mikrotik_connection_mode', 1)" 
                                             class="info-box shadow-none border {{ $state['mikrotik_connection_mode'] == 1 ? 'bg-success border-success' : 'bg-light' }}" 
                                             style="cursor:pointer;">
                                            <span class="info-box-icon"><i class="fas fa-cloud"></i></span>
                                            <div class="info-box-content"><b>REMOTO (DNS)</b></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-light border small text-muted">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Usando: <b>{{ $state['mikrotik_connection_mode'] == 1 ? 'Host/DNS (Cloud)' : 'IP Privada (Local)' }}</b>
                                </div>

                                <hr>
                                <div class="custom-control custom-switch">
                                    <input wire:model.defer="state.sidebar_collapse" type="checkbox" class="custom-control-input" id="sw1">
                                    <label class="custom-control-label" for="sw1">Colapsar Sidebar</label>
                                </div>
                            </div>
                            <div class="card-footer text-right">
                                <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save mr-2"></i>Guardar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>
</div>