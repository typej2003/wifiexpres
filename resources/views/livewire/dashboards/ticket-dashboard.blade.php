<div class="container py-4" style="max-width: 450px;">
    @if($ticket)
    <div class="card border-0 shadow-lg" style="border-radius: 25px; overflow: hidden;">
        <div class="text-white p-4 text-center" style="background: linear-gradient(135deg, var(--primary-purple, #6500da), var(--primary-orange, #ff572f));">
            <div class="mb-2">
                <i class="bi bi-ticket-perforated" style="font-size: 3.5rem;"></i>
            </div>
            <h4 class="fw-bold mb-0">HOLA, {{ strtoupper($ticket->username) }}</h4>
            <span class="small opacity-75">Panel de Navegación</span>
        </div>

        <div class="card-body p-4">
            @if (session()->has('message'))
                <div class="alert alert-success border-0 small shadow-sm">{{ session('message') }}</div>
            @endif
            @if (session()->has('error'))
                <div class="alert alert-danger border-0 small shadow-sm">{{ session('error') }}</div>
            @endif

            <div class="row g-3 mb-4">
                <div class="col-6">
                    <div class="p-3 border-0 rounded-4 text-center shadow-sm" style="background: #f8f9fa;">
                        <small class="text-muted d-block fw-bold mb-1" style="font-size: 10px;">TIEMPO USADO</small>
                        <span class="h5 fw-bold text-dark mb-0">{{ $stats['uptime'] }}</span>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-3 border-0 rounded-4 text-center shadow-sm" style="background: #f8f9fa;">
                        <small class="text-muted d-block fw-bold mb-1" style="font-size: 10px;">CONEXIÓN</small>
                        <span class="fw-bold {{ $stats['status'] == 'Conectado' ? 'text-success' : 'text-secondary' }}">
                            <i class="bi bi-circle-fill" style="font-size: 8px;"></i> {{ strtoupper($stats['status']) }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="p-3 rounded-4 mb-4 border" style="background: #fff;">
                <label class="form-label small fw-bold text-muted">¿QUIERES CAMBIAR TU CLAVE?</label>
                <div class="input-group mb-2 shadow-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-shield-lock text-muted"></i></span>
                    <input type="text" wire:model.defer="newPassword" class="form-control border-start-0" placeholder="Nueva clave">
                </div>
                <button wire:click="changePassword" class="btn btn-primary w-100 rounded-pill fw-bold" style="background: #6500da; border: none;">
                    ACTUALIZAR CLAVE
                </button>
            </div>

            <div class="d-grid gap-2 text-center">
                <button wire:click="refreshStats" class="btn btn-light btn-sm rounded-pill text-muted fw-bold">
                    <i class="bi bi-arrow-clockwise me-1"></i> REFRESCAR DATOS
                </button>
                <hr class="text-muted opacity-25">
                <a href="{{ route('ticket.logout') }}" class="text-danger text-decoration-none small fw-bold">
                    <i class="bi bi-door-open me-1"></i> CERRAR MI SESIÓN
                </a>
            </div>
        </div>
    </div>
    @else
    <div class="text-center p-5 card shadow-sm rounded-5">
        <div class="spinner-grow text-primary" role="status"></div>
        <p class="mt-3 text-muted">Validando tu ticket...</p>
    </div>
    @endif

    <p class="text-center mt-4 text-muted" style="font-size: 10px; letter-spacing: 1px;">
        POWERED BY <b>DDR SISTEMAS C.A.</b>
    </p>
</div>