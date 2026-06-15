<div wire:poll.10s>
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="fw-bold"><i class="fas fa-satellite-dish me-2 text-primary"></i>Monitoreo en Vivo</h3>
                <p class="text-muted">Actividad en tiempo real de tus puntos de conexión..</p>
            </div>
        </div>

        <!-- 3 Números Grandes -->
        <div class="row g-3 mb-5">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm bg-primary text-white">
                    <div class="card-body text-center py-4">
                        <h6 class="text-uppercase fw-bold opacity-75">Conectados Ahora</h6>
                        <h1 class="display-3 fw-black m-0">{{ $connectedNow }}</h1>
                        <div class="mt-2 small"><i class="fas fa-circle text-success blink me-1"></i> En línea</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm bg-white">
                    <div class="card-body text-center py-4">
                        <h6 class="text-uppercase fw-bold text-muted">Entradas Hoy</h6>
                        <h1 class="display-3 fw-black text-dark m-0">{{ $entriesToday }}</h1>
                        <div class="mt-2 text-success small"><i class="fas fa-arrow-up me-1"></i> Sesiones iniciadas</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm bg-white">
                    <div class="card-body text-center py-4">
                        <h6 class="text-uppercase fw-bold text-muted">Salidas Hoy</h6>
                        <h1 class="display-3 fw-black text-dark m-0">{{ $exitsToday }}</h1>
                        <div class="mt-2 text-danger small"><i class="fas fa-arrow-down me-1"></i> Sesiones finalizadas</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Movimientos -->
        <div class="row">
            <div class="col-12 col-lg-8 mx-auto">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="mb-0 fw-bold text-secondary">
                            <i class="fas fa-list-ul me-2"></i>Historial Reciente
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush" style="max-height: 500px; overflow-y: auto;">
                            @forelse($movements as $m)
                                <div class="list-group-item list-group-item-action py-3">
                                    <div class="d-flex w-100 justify-content-between align-items-center">
                                        <div>
                                            <span class="badge bg-light text-dark border me-2">{{ $m['time'] }}</span>
                                            <span class="fs-5">
                                                <strong>{{ $m['user'] }}</strong> 
                                                <span class="text-muted">{{ $m['action'] }}</span> 
                                                en la <strong>{{ $m['location'] }}</strong>.
                                            </span>
                                        </div>
                                        <i class="fas fa-chevron-right text-light"></i>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-5">
                                    <i class="fas fa-ghost fa-3x text-light mb-3"></i>
                                    <p class="text-muted">Esperando actividad...</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>