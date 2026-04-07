<div class="container-fluid py-4">
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold mb-0">
                    <i class="bi bi-megaphone text-primary me-2"></i>Campañas de Encuestas
                </h4>
                <button class="btn btn-primary rounded-pill px-4 shadow-sm">
                    <i class="bi bi-plus-lg me-1"></i> Nueva Campaña
                </button>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <input type="text" wire:model="search" class="form-control" placeholder="Buscar campaña...">
                </div>

                {{-- SELECTOR SOLO PARA ADMIN --}}
                @if($isAdmin)
                <div class="col-md-3">
                    <select wire:model="filterAliado" class="form-select">
                        <option value="">Todos los Aliados</option>
                        @foreach($aliados as $aliado)
                            <option value="{{ $aliado->id }}">{{ $aliado->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small fw-bold">
                    <tr>
                        <th class="px-4 py-3">CAMPAÑA</th>
                        <th class="py-3">SEGMENTACIÓN</th>
                        <th class="py-3 text-center">CONTENIDO</th>
                        <th class="py-3 text-center">ESTADO</th>
                        <th class="text-end px-4">ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($campaigns as $camp)
                    <tr>
                        <td class="px-4">
                            <span class="fw-bold d-block">{{ $camp->name }}</span>
                            @if($isAdmin)
                                <small class="text-muted">Aliado: {{ $camp->user->name }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-soft-info text-info">
                                {{ ucfirst($camp->target_gender) }} ({{ $camp->age_min }}-{{ $camp->age_max }} años)
                            </span>
                        </td>
                        <td class="text-center">
                            @if($camp->media_type == 'imagen')
                                <i class="bi bi-image text-primary fs-5"></i>
                            @else
                                <i class="bi bi-play-circle-fill text-danger fs-5"></i>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge {{ $camp->active ? 'bg-success' : 'bg-secondary' }} rounded-pill">
                                {{ $camp->active ? 'Activa' : 'Pausada' }}
                            </span>
                        </td>
                        <td class="text-end px-4">
                            <button class="btn btn-sm btn-light border shadow-sm"><i class="bi bi-pencil"></i></button>
                            <button class="btn btn-sm btn-light border shadow-sm text-danger"><i class="bi bi-trash"></i></button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center py-5">No hay campañas configuradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white border-0 p-3">
            {{ $campaigns->links() }}
        </div>
    </div>
</div>

<style>
    .bg-soft-info { background-color: rgba(13, 202, 240, 0.1); }
</style>