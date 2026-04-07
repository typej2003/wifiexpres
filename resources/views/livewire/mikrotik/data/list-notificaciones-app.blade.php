<div class="container-fluid py-4" wire:poll.5s>
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 p-4">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="fw-bold mb-0">
                    <i class="bi bi-bell-fill text-warning me-2"></i>Notificaciones Capturadas
                </h4>
                <div class="col-md-4">
                    <input type="text" wire:model="search" class="form-control rounded-pill" placeholder="Buscar app o título...">
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
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-5">No hay notificaciones registradas aún.</td>
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
    </style>
</div>