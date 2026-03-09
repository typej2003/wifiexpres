<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h3 class="fw-bold"><i class="bi bi-person-check me-2 text-primary"></i>Manager de Suscripciones</h3>
            <p class="text-muted">Gestión de planes, capacidad y activación de routers.</p>
        </div>
        <div class="col-md-4">
            <div class="input-group shadow-sm rounded-pill overflow-hidden">
                <span class="input-group-text bg-white border-0"><i class="bi bi-search text-muted"></i></span>
                <input wire:model.debounce.500ms="search" type="text" class="form-control border-0 shadow-none" placeholder="Buscar aliado o correo...">
            </div>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4 animate__animated animate__fadeIn">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('message') }}
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table align-middle mb-0 table-hover">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Aliado</th>
                        <th>Plan Seleccionado</th>
                        <th class="text-center">Capacidad Routers</th>
                        <th>Vencimiento</th>
                        <th>Estado</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($usuarios as $user)
                        @foreach($user->packages as $package)
                            <tr wire:key="user-pkg-{{ $user->id }}-{{ $package->id }}">
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary-subtle rounded-circle p-2 me-3">
                                            <i class="bi bi-person text-primary"></i>
                                        </div>
                                        <div>
                                            <span class="fw-bold d-block text-dark">{{ $user->names }}</span>
                                            <small class="text-muted">{{ $user->email }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="p-2 bg-light rounded-3 border me-2">
                                            <i class="bi bi-box-seam text-secondary"></i>
                                        </div>
                                        <div>
                                            <span class="fw-bold text-dark d-block small">{{ $package->name }}</span>
                                            <button wire:click="openChangePlanModal({{ $user->id }}, {{ $package->id }})" 
                                                    class="btn btn-sm p-0 btn-link text-primary text-decoration-none" style="font-size: 0.75rem;">
                                                Cambiar Plan
                                            </button>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    @php
                                        $limit = (int) $package->pivot->allowed_routers;
                                        $used = (int) $package->routers_count; 
                                        $percent = $limit > 0 ? ($used / $limit) * 100 : 0;
                                        
                                        $color = 'primary';
                                        if($used >= $limit && $limit > 0) $color = 'danger';
                                        elseif($percent >= 80) $color = 'warning';
                                    @endphp
                                    <div style="width: 150px; margin: 0 auto;">
                                        <div class="d-flex justify-content-between mb-1">
                                            <small class="fw-bold text-{{ $color }}">{{ $used }} / {{ $limit }}</small>
                                            <small class="text-muted small" style="font-size: 0.7rem;">{{ number_format($percent, 0) }}%</small>
                                        </div>
                                        <div class="progress" style="height: 6px; background-color: #e9ecef;">
                                            <div class="progress-bar bg-{{ $color }} progress-bar-striped progress-bar-animated" 
                                                 role="progressbar" 
                                                 style="width: {{ min($percent, 100) }}%">
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="fw-bold small">{{ \Carbon\Carbon::parse($package->pivot->end_date)->format('d/m/Y') }}</span>
                                        <small class="text-muted" style="font-size: 0.7rem;">Expira en {{ \Carbon\Carbon::parse($package->pivot->end_date)->diffForHumans() }}</small>
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $statusStyles = [
                                            'active' => 'bg-success-subtle text-success',
                                            'pending' => 'bg-warning-subtle text-warning',
                                            'suspended' => 'bg-danger-subtle text-danger'
                                        ];
                                        $currentStyle = $statusStyles[$package->pivot->status] ?? 'bg-secondary-subtle text-secondary';
                                        $label = $package->pivot->status == 'active' ? 'Activo' : ($package->pivot->status == 'pending' ? 'Pendiente' : 'Suspendido');
                                    @endphp
                                    <span class="badge {{ $currentStyle }} px-3 rounded-pill">
                                        {{ $label }}
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-2">
                                        @if($package->pivot->status == 'pending')
                                            <button wire:click="approveSubscription({{ $user->id }}, {{ $package->id }})" 
                                                    class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm">
                                                <i class="bi bi-check-circle me-1"></i> Aprobar
                                            </button>
                                        @else
                                            <button wire:click="toggleStatus({{ $user->id }}, {{ $package->id }}, '{{ $package->pivot->status }}')" 
                                                    class="btn {{ $package->pivot->status == 'active' ? 'btn-outline-danger' : 'btn-outline-success' }} btn-sm rounded-pill px-3">
                                                <i class="bi {{ $package->pivot->status == 'active' ? 'bi-pause-fill' : 'bi-play-fill' }}"></i>
                                                {{ $package->pivot->status == 'active' ? 'Suspender' : 'Activar' }}
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white border-0 py-3">
            {{ $usuarios->links() }}
        </div>
    </div>

    {{-- MODAL PARA CAMBIAR PLAN --}}
    @if($isChangePlanModalOpen)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); z-index: 1050; backdrop-filter: blur(4px);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg border-0 rounded-4">
                <div class="modal-header border-0 p-4 pb-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-arrow-left-right me-2 text-primary"></i>Migrar Suscripción</h5>
                    <button wire:click="closeChangePlanModal" class="btn-close shadow-none"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="bg-light p-3 rounded-4 mb-3 border">
                        <small class="d-block text-muted">Plan Actual:</small>
                        <span class="fw-bold">
                            @php
                                $currentPkg = App\Models\Package::find($selectedPackageId);
                                echo $currentPkg ? $currentPkg->name : 'N/A';
                            @endphp
                        </span>
                    </div>

                    <label class="form-label small fw-bold text-muted text-uppercase">Seleccionar Nuevo Plan</label>
                    <select wire:model="newPackageId" class="form-select rounded-3 border-2 shadow-none py-2">
                        <option value="">Seleccione un paquete...</option>
                        @foreach($allPackages as $pkg)
                            <option value="{{ $pkg->id }}">{{ $pkg->name }} (Capacidad: {{ $pkg->limit_routers }} Routers) - ${{ number_format($pkg->cost, 2) }}</option>
                        @endforeach
                    </select>
                    @error('newPackageId') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button wire:click="closeChangePlanModal" class="btn btn-light rounded-pill px-4">Cancelar</button>
                    <button wire:click="updatePlan" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
                        CONFIRMAR CAMBIO
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>