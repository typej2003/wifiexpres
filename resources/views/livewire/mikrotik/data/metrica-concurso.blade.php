<div>
    {{-- Nothing in the world is as soft and yielding as water. --}}
<div class="container-fluid py-4">
    {{-- FILTROS --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <h4 class="fw-bold mb-4"><i class="bi bi-trophy text-primary me-2"></i>Métricas de Concursos</h4>
            <div class="row g-3 align-items-end">
                @if(auth()->user()->role === 'admin' || auth()->user()->role === 'root')
                <div class="col-md-3">
                    <label class="small fw-bold text-muted mb-1 text-uppercase">Aliado</label>
                    <select wire:model="selectedAliado" class="form-select border-0 bg-light rounded-3 shadow-none">
                        <option value="">-- Seleccionar Aliado --</option>
                        @foreach($aliados as $aliado)
                            <option value="{{ $aliado->id }}">{{ $aliado->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-3">
                    <label class="small fw-bold text-muted mb-1 text-uppercase">Concurso</label>
                    <select wire:model="selectedConcurso" class="form-select border-0 bg-light rounded-3 shadow-none">
                        <option value="">-- Seleccionar Concurso --</option>
                        @foreach($concursos as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                    @if($selectedConcurso)
                        <button wire:click="openEditModal({{ $selectedConcurso }})" class="btn btn-link btn-sm p-0 mt-1" title="Editar Concurso">
                            <i class="bi bi-pencil-square me-1"></i> Editar Concurso
                        </button>
                    @endif
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold text-muted mb-1 text-uppercase">Router</label>
                    <select wire:model="selectedRouter" class="form-select border-0 bg-light rounded-3 shadow-none">
                        <option value="">📍 Todos los Routers</option>
                        @foreach($routers as $r)
                            <option value="{{ $r->id }}">{{ $r->identity }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold text-muted mb-1 text-uppercase">Desde</label>
                    <input type="date" wire:model="fromDate" class="form-control border-0 bg-light rounded-3 shadow-none">
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold text-muted mb-1 text-uppercase">Hasta</label>
                    <input type="date" wire:model="toDate" class="form-control border-0 bg-light rounded-3 shadow-none">
                </div>
                <div class="col-md-2">
                    <button wire:click="consultar" class="btn btn-primary w-100 rounded-pill fw-bold shadow-sm">
                        <i class="bi bi-search me-1"></i> FILTRAR
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if($stats)
    {{-- STATS --}}
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center h-100">
                <h6 class="text-muted small fw-bold text-uppercase">Total Participaciones</h6>
                <h2 class="fw-bold mb-0 text-primary">{{ number_format($stats['total_participantes']) }}</h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center h-100">
                <h6 class="text-muted small fw-bold text-uppercase">Usuarios Únicos</h6>
                <h2 class="fw-bold mb-0 text-dark">{{ number_format($stats['usuarios_unicos']) }}</h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center h-100 border-start border-4 border-success">
                <h6 class="text-muted small fw-bold text-uppercase">Usuarios Acertaron Etapa</h6>
                <h2 class="fw-bold mb-0 text-success">{{ number_format($stats['usuarios_acertaron_etapa']) }}</h2>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- GRÁFICO --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                <h6 class="fw-bold mb-4 text-uppercase small">Distribución de Respuestas</h6>
                <div style="position: relative; height:300px;" wire:ignore>
                    <canvas id="concursoChart"></canvas>
                </div>
            </div>
        </div>

        {{-- TABLA DETALLE --}}
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <h6 class="fw-bold mb-0 text-uppercase small">Últimos Participantes</h6>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="bg-light small fw-bold text-muted text-uppercase">
                            <tr>
                                <th class="ps-4">Participante</th>
                                <th>Respuesta</th>
                                <th>Router</th>
                                <th class="pe-4 text-end">Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tableData as $data)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark">{{ $data->full_name ?: 'Anónimo' }}</div>
                                    <small class="text-muted">{{ $data->cellphonecode }}{{ $data->cellphone }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill">
                                        {{ $data->answer }}
                                    </span>
                                </td>
                                <td><small class="fw-bold">{{ $data->router_identity }}</small></td>
                                <td class="pe-4 text-end small text-muted">{{ $data->created_at->format('d/m/y h:i A') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center py-5">No hay respuestas registradas</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif

    @push('js')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let concursoChart;
        function initConcursoChart(labels, values) {
            const ctx = document.getElementById('concursoChart').getContext('2d');
            if (concursoChart) concursoChart.destroy();
            concursoChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Votos',
                        data: values,
                        backgroundColor: '#6700da',
                        borderRadius: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                }
            });
        }
        window.addEventListener('updateConcursoChart', event => {
            initConcursoChart(event.detail.labels, event.detail.values);
        });
    </script>
    @endpush
</div>

{{-- MODAL DE EDICIÓN/CREACIÓN DE CONCURSO --}}
@if($isEditModalOpen)
<div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); z-index: 1050; backdrop-filter: blur(4px);">
    <div class="modal-dialog modal-lg" style="margin-top: 5rem;">
        <div class="modal-content shadow-lg border-0 rounded-4">
            <div class="modal-header bg-dark text-white p-4">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-trophy me-2"></i>
                    {{ $editingConcursoId ? 'Editar Concurso' : 'Crear Nuevo Concurso' }}
                </h5>
                <button wire:click="closeEditModal" class="btn-close btn-close-white"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Nombre del Concurso</label>
                        <input type="text" wire:model.defer="name" class="form-control bg-light border-0">
                        @error('name') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Etapa / Fase</label>
                        <input type="text" wire:model.defer="etapa" class="form-control bg-light border-0">
                        @error('etapa') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-bold text-muted">Descripción</label>
                        <textarea wire:model.defer="description" class="form-control bg-light border-0" rows="2"></textarea>
                        @error('description') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Router Asociado</label>
                        <select wire:model.defer="router_identity_modal" class="form-select bg-light border-0">
                            <option value="">-- Seleccionar Router --</option>
                            @foreach($routers as $r)
                                <option value="{{ $r->identity }}">{{ $r->identity }}</option>
                            @endforeach
                        </select>
                        @error('router_identity_modal') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Público Objetivo (Género)</label>
                        <select wire:model.defer="target_gender" class="form-select bg-light border-0">
                            <option value="todos">Todos</option>
                            <option value="M">Masculino</option>
                            <option value="F">Femenino</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Rango de Edad</label>
                        <select wire:model.defer="age_range_id" class="form-select bg-light border-0">
                            <option value="0">Todos los rangos</option>
                            @foreach($ageRanges as $range)
                                <option value="{{ $range->id }}">{{ $range->name }} ({{ $range->min_age }}-{{ $range->max_age }})</option>
                            @endforeach
                        </select>
                        @error('age_range_id') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Tipo de Contenido Multimedia</label>
                        <select wire:model.defer="media_type" class="form-select bg-light border-0">
                            <option value="imagen">Imagen</option>
                            <option value="video">Video</option>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-bold text-muted">Contenido Multimedia (Imagen/Video)</label>
                        <input type="file" wire:model="media" class="form-control bg-light border-0">
                        @if($current_media_path && !$media)
                            <small class="text-muted">Archivo actual: {{ basename($current_media_path) }}</small>
                        @endif
                        @error('media') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-bold text-muted">Pregunta del Concurso</label>
                        <input type="text" wire:model.defer="question_text" class="form-control bg-light border-0">
                        @error('question_text') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-bold text-muted">Tipo de Pregunta</label>
                        <select wire:model="question_type" class="form-select bg-light border-0">
                            <option value="simple">Respuesta Abierta</option>
                            <option value="multiple">Selección Múltiple (Texto)</option>
                            <option value="multiple_image">Selección Múltiple (Imágenes)</option>
                        </select>
                    </div>

                    @if($question_type != 'simple')
                    <div class="col-md-12 mt-3">
                        <h6 class="fw-bold text-muted">Opciones de Respuesta</h6>
                        @foreach($options as $index => $option)
                            <div class="input-group mb-2">
                                <input type="text" wire:model.defer="options.{{ $index }}.text" class="form-control bg-light border-0" placeholder="Texto de la opción">
                                @if($question_type == 'multiple_image')
                                    <input type="file" wire:model="temp_option_images.{{ $index }}" class="form-control bg-light border-0">
                                    @if(isset($option['image']) && $option['image'] && !isset($temp_option_images[$index]))
                                        <span class="input-group-text bg-light border-0">Actual: {{ basename($option['image']) }}</span>
                                    @endif
                                @endif
                                <input type="text" wire:model.defer="options.{{ $index }}.grupo" class="form-control bg-light border-0" placeholder="Grupo (opcional)">
                                <button type="button" wire:click="removeOption({{ $index }})" class="btn btn-outline-danger">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                            @error('options.'.$index.'.text') <span class="text-danger small">{{ $message }}</span> @enderror
                            @error('temp_option_images.'.$index) <span class="text-danger small">{{ $message }}</span> @enderror
                        @endforeach
                        <button type="button" wire:click="addOption" class="btn btn-outline-primary btn-sm mt-2">
                            <i class="bi bi-plus-lg me-1"></i> Añadir Opción
                        </button>
                        @error('options') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    @endif
                </div>
            </div>
            <div class="modal-footer bg-light border-0 p-4">
                <button wire:click="closeEditModal" class="btn btn-secondary rounded-pill px-4">Cancelar</button>
                <button wire:click.prevent="saveConcurso" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold">GUARDAR CAMBIOS</button>
            </div>
        </div>
    </div>
</div>
@endif
</div>
