<div class="container-fluid py-4">

    @if (session()->has('message'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('message') }}
        </div>
    @endif

    {{-- HEADER --}}
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h2 class="fw-bold text-dark mb-0"></h2>
            <div class="d-flex align-items-center gap-2 mt-1">
                <span class="badge bg-primary-soft text-primary border border-primary rounded-pill px-3">
                    <i class="bi bi-currency-exchange me-1"></i> BCV: <strong>Bs. {{ number_format($dollarRate, 2, ',', '.') }}</strong>
                </span>
            </div>
        </div>
        <div class="col-md-6 text-end">
            
        </div>
    </div>

    {{-- 1. STATS CARDS --}}
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center bg-white h-100">
                <h6 class="text-muted small fw-bold text-uppercase mb-3">
                    <i class="bi bi-broadcast text-primary me-1"></i> Routers Online
                </h6>
                <h2 class="fw-bold mb-0 {{ $stats['routers_online'] > 0 ? 'text-success' : 'text-danger' }}">
                    {{ $stats['routers_online'] }} <span class="text-muted fs-5">/ {{ $stats['total_routers'] }}</span>
                </h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center bg-white h-100">
                <h6 class="text-muted small fw-bold text-uppercase mb-2">Suscripciones / Planes</h6>
                <div class="mb-2" style="max-height: 120px; overflow-y: auto;">
                    @forelse($userPackages as $pkg)
                        <div class="d-flex justify-content-between align-items-center bg-light p-2 rounded-3 mb-1 border-start border-4 {{ $pkg->pivot->status === 'active' ? 'border-success' : ($pkg->pivot->status === 'pending' ? 'border-warning' : 'border-secondary') }} text-start">
                            <span class="fw-bold small text-truncate" style="max-width: 140px;" title="{{ $pkg->name }}">
                                {{ $pkg->name }}
                            </span>
                            @php
                                $statusColor = match($pkg->pivot->status) {
                                    'active' => 'bg-success',
                                    'pending' => 'bg-warning text-dark',
                                    'expired' => 'bg-danger',
                                    default => 'bg-secondary'
                                };
                            @endphp
                            <div class="d-flex align-items-center">
                                <span class="badge {{ $statusColor }} rounded-pill" style="font-size: 0.6rem;">
                                    {{ strtoupper($pkg->pivot->status) }}
                                </span>
                                @if($pkg->pivot->status === 'pending')
                                    <button wire:click="cancelSubscription({{ $pkg->id }})" class="btn btn-link text-danger p-0 ms-2" title="Eliminar solicitud" onclick="confirm('¿Estás seguro de que deseas cancelar esta solicitud?') || event.stopImmediatePropagation()">
                                        <i class="bi bi-trash-fill" style="font-size: 0.8rem;"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @empty
                        <h2 class="fw-bold mb-0 text-muted fs-4">Sin Plan</h2>
                    @endforelse
                </div>
                <button wire:click="openModal" class="btn btn-sm btn-link text-decoration-none p-0 fw-bold mt-auto">
                    <i class="bi bi-plus-circle me-1"></i> GESTIONAR
                </button>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center bg-white h-100">
                <h6 class="text-muted small fw-bold text-uppercase">Usuarios Online</h6>
                <h2 class="fw-bold mb-0 text-info">{{ $stats['usuarios_online'] }}</h2>
            </div>
        </div>
    </div>

    {{-- 2. FILTROS DINÁMICOS --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="small fw-bold text-muted mb-1 text-uppercase">Equipo / Router</label>
                    <select wire:model="router_id" class="form-select border-0 bg-light rounded-3 shadow-none">
                        @if(count($routers) > 1)
                            <option value="">📊 Todos los Routers</option>
                        @elseif(count($routers) == 0)
                            <option value="">Sin routers configurados</option>
                        @endif
                        @foreach($routers as $r)
                            <option value="{{ $r->id }}">{{ $r->identity }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold text-muted mb-1 text-uppercase">Rango</label>
                    <select wire:model="periodo" class="form-select border-0 bg-light rounded-3 shadow-none">
                        <option value="dia">Hoy</option>
                        <option value="semana">Semana</option>
                        <option value="mes">Mes</option>
                        <option value="personalizado">Personalizado</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold text-muted mb-1 text-uppercase">Desde</label>
                    <input type="date" wire:model="fecha_desde" class="form-control border-0 bg-light rounded-3 shadow-none" {{ $periodo != 'personalizado' ? 'disabled' : '' }}>
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold text-muted mb-1 text-uppercase">Hasta</label>
                    <input type="date" wire:model="fecha_hasta" class="form-control border-0 bg-light rounded-3 shadow-none" {{ $periodo != 'personalizado' ? 'disabled' : '' }}>
                </div>
            </div>
        </div>
    </div>

    {{-- 3. GRÁFICA Y TOP USUARIOS --}}
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                <h6 class="fw-bold mb-4">Concurrencia de Usuarios</h6>
                <div style="position: relative; height:400px;" wire:ignore>
                    <canvas id="multiBarChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <h6 class="fw-bold mb-3"><i class="bi bi-award text-warning me-2"></i>Top Usuarios</h6>
                @foreach($topUsuarios as $u)
                <div class="d-flex justify-content-between align-items-start mb-3 pb-2 border-bottom border-light">
                    <div class="d-flex">
                        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 38px; height: 38px;">
                            <i class="bi bi-person-badge text-secondary"></i>
                        </div>
                        <div>
                            @if($u->full_name)
                                <strong class="text-dark d-block">{{ $u->full_name }}</strong>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <small class="text-muted" style="font-size: 0.7rem;"><i class="bi bi-card-text me-1"></i>{{ $u->profile_name }}</small>
                                    <small class="text-info" style="font-size: 0.65rem;" title="Tiempo desde registro">
                                        <i class="bi bi-clock-history me-1"></i>{{ \Carbon\Carbon::parse($u->registered_at)->diffForHumans() }}
                                    </small>
                                </div>
                            @else
                                <strong class="text-dark d-block">{{ $u->username }}</strong>
                                <small class="text-muted" style="font-size: 0.7rem;">Sin registro detallado</small>
                            @endif
                            <div class="mt-1">
                                <small class="text-muted" style="font-size: 0.7rem;">
                                    <i class="bi bi-arrow-repeat me-1"></i>{{ number_format($u->total_conexiones) }} conex.
                                </small>
                            </div>
                        </div>
                    </div>
                    <span class="badge bg-primary-soft text-primary border border-primary rounded-pill small">
                        {{ round($u->tiempo_total/3600, 1) }}h
                    </span>
                </div>
                @endforeach
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden" style="height: calc(100% - 215px);">
                <div class="card-header bg-white border-0 pt-3">
                    <h6 class="fw-bold mb-0">Actividad Reciente</h6>
                </div>
                <div class="list-group list-group-flush" style="max-height: 250px; overflow-y: auto;">
                    @foreach($ultimosLogs as $log)
                        <div class="list-group-item border-0 px-4 py-3 small d-flex justify-content-between align-items-center border-bottom">
                            <div>
                                @if($log->full_name)
                                    <strong class="text-dark d-block mb-0">{{ $log->full_name }}</strong>
                                    <small class="text-muted d-block" style="font-size: 0.7rem;">
                                        <i class="bi bi-person me-1"></i>{{ $log->profile_name }}
                                    </small>
                                @else
                                    <strong class="text-dark d-block mb-0">{{ $log->username }}</strong>
                                    <small class="text-muted d-block" style="font-size: 0.7rem;">Sin perfil registrado</small>
                                @endif
                                <small class="text-primary" style="font-size: 0.65rem;">
                                    <i class="bi bi-router me-1"></i>{{ $log->router->identity ?? 'N/A' }}
                                </small>
                            </div>
                            <div class="text-end">
                                <span class="text-muted d-block" style="font-size: 0.7rem;">
                                    <i class="bi bi-clock-history me-1"></i>{{ $log->created_at->diffForHumans() }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL PLANES --}}
    @if($showPlanModal)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.7); backdrop-filter: blur(8px); z-index: 2050;">
        <div class="modal-dialog modal-xl" style="margin-top: 8rem;">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-dark text-white p-4">
                    <h5 class="fw-bold mb-0">Suscripciones WiFiExpres</h5>
                    <button wire:click="closeModal" class="btn-close btn-close-white shadow-none"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <div class="row g-4">
                        @foreach($availablePackages as $package)
                            <div class="col-md-4">
                                <div class="card border-0 shadow-sm rounded-4 h-100 text-center">
                                    <div class="card-body p-4">
                                        <h4 class="fw-bold">{{ $package->name }}</h4>
                                        <div class="display-6 fw-bold my-3 text-primary">${{ $package->cost }}</div>
                                        <ul class="list-unstyled text-start small mb-4">
                                            <li><i class="bi bi-check2 text-success me-2"></i>{{ $package->limit_routers }} Router(s)</li>
                                            <li><i class="bi bi-check2 text-success me-2"></i>{{ $package->duration_months }} Mes(es) de servicio</li>
                                        </ul>
                                        <button wire:click="selectPlan({{ $package->id }})" class="btn btn-dark w-100 rounded-pill fw-bold">ADQUIRIR</button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@push('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let multiChart;
    function renderMultiChart(labels, datasets) {
        const canvas = document.getElementById('multiBarChart');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        if (multiChart) multiChart.destroy();
        multiChart = new Chart(ctx, {
            type: 'bar',
            data: { labels: labels, datasets: datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true } } },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f0f0f0' },
                        ticks: { precision: 0 }
                    },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    document.addEventListener('livewire:load', () => { renderMultiChart(@json($labels), @json($datasets)); });
    window.addEventListener('updateMultiChart', event => { renderMultiChart(event.detail.labels, event.detail.datasets); });
</script>
@endpush