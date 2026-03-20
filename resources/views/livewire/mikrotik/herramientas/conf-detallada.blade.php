<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Configuración Detallada de MikroTik</h3>
        <button wire:click="cargarInterfaces" class="btn btn-outline-secondary btn-sm">
            <span wire:loading wire:target="cargarInterfaces" class="spinner-border spinner-border-sm"></span>
            Refrescar Interfaces
        </button>
    </div>

    <div class="row">
        @foreach($interfaces as $index => $iface)
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white d-flex justify-content-between">
                    <span class="fw-bold">Puerto: {{ strtoupper($iface) }}</span>
                    <span class="badge bg-primary">Segmento 192.168.{{ ($index + 2) * 10 }}.x</span>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">Configura Bridge, IP, Pool, DHCP y Hotspot para esta interfaz.</p>
                    
                    <button 
                        wire:click="configurarPuerto('{{ $iface }}', {{ $index }})" 
                        wire:loading.attr="disabled"
                        class="btn btn-{{ ($status[$iface] == 'success') ? 'success' : 'primary' }} w-100 fw-bold">
                        
                        <span wire:loading wire:target="configurarPuerto('{{ $iface }}', {{ $index }})">
                            <i class="fas fa-spinner fa-spin"></i> CARGANDO...
                        </span>
                        
                        <span wire:loading.remove wire:target="configurarPuerto('{{ $iface }}', {{ $index }})">
                            {{ ($status[$iface] == 'success') ? 'ACTUALIZAR CONFIGURACIÓN' : 'ENVIAR COMANDOS' }}
                        </span>
                    </button>

                    <div class="mt-3 p-2 bg-light border rounded shadow-inner" style="min-height: 40px; font-family: monospace; font-size: 0.85rem;">
                        <div class="{{ $status[$iface] == 'error' ? 'text-danger' : 'text-dark' }}">
                            <strong>LOG:</strong> {{ $logs[$iface] }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach

        <div class="col-md-6 mb-4">
            <div class="card shadow-sm border-0 border-start border-primary border-4">
                <div class="card-body">
                    <h5 class="fw-bold">Perfiles de Hotspot</h5>
                    <p class="small text-muted">Genera perfiles: Neutro, Cortesía y Conexión Gratis.</p>
                    
                    <button wire:click="configurarPerfiles()" class="btn btn-primary w-100">
                        <span wire:loading wire:target="configurarPerfiles">PROCESANDO...</span>
                        <span wire:loading.remove wire:target="configurarPerfiles">
                            {{ ($status['profiles'] == 'success') ? 'ACTUALIZAR PERFILES' : 'ENVIAR PERFILES' }}
                        </span>
                    </button>
                    
                    <div class="mt-2 text-muted small italic text-end">
                        {{ $logs['profiles'] ?? '' }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>