<div class="container-fluid py-4" wire:poll.5s>
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 mb-3" role="alert">
            {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 p-4">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <h4 class="fw-bold mb-0">
                        <i class="bi bi-bell-fill text-warning me-2"></i>Notificaciones
                    </h4>
                </div>
                <div class="col-md-8 d-flex gap-2 justify-content-end">
                    <div class="col-md-6">
                        <input type="text" wire:model="search" class="form-control rounded-pill" placeholder="Buscar app o título...">
                    </div>
                    <button wire:click="clearAll" wire:confirm="¿Seguro que deseas vaciar todo el historial?" class="btn btn-outline-danger rounded-pill px-4">
                        <i class="bi bi-trash3-fill"></i> Vaciar
                    </button>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light small fw-bold text-uppercase">
                    <tr>
                        <th class="px-4 py-3">App Origen</th>
                        <th class="py-3">Título</th>
                        <th class="py-3">Mensaje</th>
                        <th class="py-3 text-center">Fecha/Hora</th>
                        <th class="py-3 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($notificaciones as $item)
                    <tr>
                        <td class="px-4">
                            <span class="badge bg-soft-primary text-primary rounded-pill px-3">
                                {{ $item->app_name }}
                            </span>
                        </td>
                        <td class="fw-bold text-dark">{{ $item->title }}</td>
                        <td class="text-muted small" style="max-width: 300px; white-space: normal;">
                            {{ $item->body }}
                        </td>
                        <td class="text-center small">
                            {{ $item->created_at->format('d/m/Y h:i A') }}
                        </td>
                        <td class="text-center">
                            <button wire:click="delete({{ $item->id }})" 
                                    wire:confirm="¿Eliminar esta notificación?"
                                    class="btn btn-sm btn-light text-danger rounded-circle">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">No hay notificaciones registradas aún.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="card-footer bg-white border-0 p-3">
            {{ $notificaciones->links() }}
        </div>
    </div>

    <style>
        .bg-soft-primary { background-color: rgba(13, 110, 253, 0.1); }
        .btn-light:hover { background-color: #fee2e2; border-color: #fecaca; }
    </style>
</div>