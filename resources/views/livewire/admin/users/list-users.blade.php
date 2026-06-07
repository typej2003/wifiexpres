<div class="container-fluid py-4">
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input wire:model="search" type="text" class="form-control border-start-0 ps-0" placeholder="Buscar por nombre o correo...">
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <button wire:click="create" class="btn btn-primary shadow-sm">
                        <i class="bi bi-person-plus-fill me-1"></i> Nuevo Usuario
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold">Gestión de Usuarios</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm me-3 bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="bi bi-person fs-4 text-secondary"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 fw-bold">{{ $user->names }} {{ $user->surnames }}</h6>
                                        <small class="text-muted">{{ $user->email }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge rounded-pill 
                                    @if($user->role == 'admin') bg-danger 
                                    @elseif($user->role == 'aliado') bg-info text-dark 
                                    @else bg-secondary @endif">
                                    {{ strtoupper($user->role) }}
                                </span>
                            </td>
                            <td>
                                @if($user->active)
                                    <span class="text-success small fw-bold"><i class="bi bi-circle-fill me-1" style="font-size: 8px;"></i> Activo</span>
                                @else
                                    <span class="text-danger small fw-bold"><i class="bi bi-circle-fill me-1" style="font-size: 8px;"></i> Inactivo</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <button wire:click="edit({{ $user->id }})" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                    <i class="bi bi-pencil-square"></i> Editar
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">No se encontraron usuarios.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">
            {{ $users->links() }}
        </div>
    </div>

    @if($isModalOpen)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);">
        <div class="modal-dialog modal-lg modal-dialog-centered" style="margin-top: 100px;">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white p-4">
                    <h5 class="modal-title fw-bold">
                        <i class="bi {{ $user_id ? 'bi-person-gear' : 'bi-person-plus' }} me-2"></i>
                        {{ $user_id ? 'Editar Perfil de Usuario' : 'Registrar Nuevo Usuario' }}
                    </h5>
                    <button wire:click="closeModal" class="btn-close btn-close-white"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nombres</label>
                            <input type="text" wire:model.defer="names" class="form-control @error('names') is-invalid @enderror">
                            @error('names') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Apellidos</label>
                            <input type="text" wire:model.defer="surnames" class="form-control @error('surnames') is-invalid @enderror">
                            @error('surnames') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Correo Electrónico</label>
                            <input type="email" wire:model.defer="email" class="form-control @error('email') is-invalid @enderror">
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Rol de Usuario</label>
                            <select wire:model.defer="role" class="form-select @error('role') is-invalid @enderror">
                                <option value="">Seleccione un rol...</option>
                                <option value="admin">Administrador</option>
                                <option value="aliado">Aliado (Gestor de Routers)</option>
                                <option value="aliadoSmartData">Aliado (SmartData)</option>
                                <option value="cliente">Cliente</option>
                                <option value="vendedor">Vendedor</option>
                            </select>
                            @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Contraseña {{ $user_id ? '(Opcional)' : '' }}</label>
                            <input type="password" wire:model.defer="password" class="form-control @error('password') is-invalid @enderror">
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-switch" type="checkbox" wire:model.defer="active" id="activeSwitch" style="width: 40px; height: 20px;">
                                <label class="form-check-label fw-bold ms-2" for="activeSwitch">Usuario Activo</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light p-3">
                    <button wire:click="closeModal" class="btn btn-outline-secondary px-4">Cancelar</button>
                    <button wire:click.prevent="store" class="btn btn-primary px-4 shadow">
                        {{ $user_id ? 'Actualizar Datos' : 'Crear Usuario' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>