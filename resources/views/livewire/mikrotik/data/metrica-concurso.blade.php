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
                    <label class="small fw-bold text-muted mb-1 text-uppercase">Router</label>
                    <select wire:model="selectedRouter" class="form-select border-0 bg-light rounded-3 shadow-none">
                        <option value="">📍 Seleccionar Router</option>
                        @foreach($routers as $r)
                            <option value="{{ $r->id }}">{{ $r->identity }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold text-muted mb-1 text-uppercase">Concurso</label>
                    <select wire:model="selectedConcurso" class="form-select border-0 bg-light rounded-3 shadow-none" {{ !$selectedRouter ? 'disabled' : '' }}>
                        <option value="">-- Seleccionar Concurso --</option>
                        @foreach($concursos as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                    @if($selectedConcurso)
                        <div class="d-flex gap-2">
                            <button wire:click="openSetEventResultModal({{ $selectedConcurso }})" class="btn btn-link btn-sm p-0 mt-1 text-success text-decoration-none" title="Resultados Reales">
                                <i class="bi bi-check-all me-1"></i> Resultados Reales
                            </button>
                        </div>
                    @endif
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
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center h-100">
                <h6 class="text-muted small fw-bold text-uppercase">Total Participaciones</h6>
                <h2 class="fw-bold mb-0 text-primary">{{ number_format($stats['total_participantes']) }}</h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center h-100">
                <h6 class="text-muted small fw-bold text-uppercase">Usuarios Únicos</h6>
                <h2 class="fw-bold mb-0 text-dark">{{ number_format($stats['usuarios_unicos']) }}</h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center h-100 border-start border-4 border-success">
                <h6 class="text-muted small fw-bold text-uppercase">Usuarios con al menos 1 acierto</h6>
                <h2 class="fw-bold mb-0 text-success">{{ number_format($stats['usuarios_acertaron_etapa']) }}</h2>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- GRÁFICO --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                <h6 class="fw-bold mb-4 text-uppercase small">Mejores promedios (Top 10)</h6>
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
                <div class="table-responsive" style="max-height: 450px; overflow-y: auto;">
                    <table class="table align-middle mb-0">
                        <thead class="bg-light small fw-bold text-muted text-uppercase">
                            <tr>
                                <th class="ps-4">Participante</th>
                                <th>Respuesta</th>
                                <th class="text-center">Puntos</th>
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
                                    <div class="d-flex flex-column gap-2">
                                        @php
                                            $userAnswers = json_decode($data->answer, true);
                                            if (!is_array($userAnswers)) $userAnswers = [$data->answer];
                                            $concursoOptions = is_array($data->concurso_options) ? $data->concurso_options : [];

                                            $groupedAnswers = [];
                                            foreach($userAnswers as $ans) {
                                                $match = collect($concursoOptions)->firstWhere('text', $ans);
                                                $gName = $match['grupo'] ?? 'Sin Grupo';
                                                $groupedAnswers[$gName][] = ['text' => $ans, 'image' => $match['image'] ?? null];
                                            }
                                        @endphp
                                        @foreach($groupedAnswers as $grupo => $resps)
                                            <div class="d-flex align-items-center gap-2">
                                                <small class="text-muted fw-bold border-end pe-2" style="font-size: 0.7rem; min-width: 60px;">GRUPO {{ $grupo }}:</small>
                                                <div class="d-flex gap-1">
                                                    @foreach($resps as $r)
                                                        @if(!empty($r['image']))
                                                            <img src="{{ Storage::disk('public')->url($r['image']) }}" 
                                                                 class="rounded shadow-sm border border-1 border-white" 
                                                                 style="width: 28px; height: 28px; object-fit: cover;" 
                                                                 title="{{ $r['text'] }}">
                                                        @else
                                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill" style="font-size: 0.75rem;">{{ $r['text'] }}</span>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                                @php
                                    $puntuacion = \App\Models\PuntuacionConcurso::where('concurso_id', $this->selectedConcurso)
                                        ->where('cellphone', $data->cellphone)
                                        ->first();
                                @endphp
                                <td class="text-center">
                                    <span class="badge {{ ($puntuacion->puntaje ?? 0) > 0 ? 'bg-success' : 'bg-light text-muted' }} rounded-pill px-3">
                                        {{ $puntuacion->puntaje ?? 0 }} pts
                                    </span>
                                </td>
                                <td><small class="fw-bold">{{ $data->router_identity }}</small></td>
                                <td class="pe-4 text-end small text-muted">{{ $data->created_at->format('d/m/y h:i A') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center py-5">No hay respuestas registradas</td></tr>
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
                        label: 'Puntos',
                        data: values,
                        backgroundColor: '#f78634',
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
@if($isModalOpen)
<div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); z-index: 1050; backdrop-filter: blur(4px);">
    <div class="modal-dialog modal-lg" style="margin-top: 8rem;">
        <div class="modal-content shadow-lg border-0 rounded-4">
            <div class="modal-header bg-dark text-white p-4">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-trophy me-2"></i>
                    @if($modalMode === 'setEventResult') 
                        Resultados Reales: {{ $name }}
                    @endif
                </h5>
                <button wire:click="closeEditModal" class="btn-close btn-close-white"></button>
            </div>
            <div class="modal-body p-4">
                @if($modalMode === 'setEventResult')
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <div class="p-3 bg-light rounded-3 border">
                                        <label class="form-label small fw-bold text-muted text-uppercase mb-1">Nombre del Evento</label>
                                        <div class="h6 mb-0 fw-bold text-dark">{{ $name }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-3 bg-light rounded-3 border">
                                        <label class="form-label small fw-bold text-muted text-uppercase mb-1">Etapa / Fase</label>
                                        <div class="h6 mb-0 fw-bold text-primary">{{ $etapa }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-12 mt-2">
                            <p class="text-muted small">Seleccione exactamente <strong>2 equipos</strong> clasificados por cada grupo.</p>
                            
                            <div class="row g-4">
                                @foreach($eventResultGroups as $grupo => $optionsInGroup)
                                    <div class="col-md-6">
                                        <div class="card border-0 shadow-sm bg-light">
                                            <div class="card-header bg-secondary text-white small fw-bold">GRUPO {{ $grupo }}</div>
                                            <div class="card-body">
                                                @foreach($optionsInGroup as $opt)
                                                    <div class="form-check d-flex align-items-center mb-2">
                                                        <input class="form-check-input" type="checkbox" 
                                                               value="{{ $opt['text'] }}" 
                                                               wire:model="selectedWinners.{{ $grupo }}"
                                                               id="winner-{{ $loop->parent->index }}-{{ $loop->index }}">
                                                        <label class="form-check-label d-flex align-items-center cursor-pointer ms-2" for="winner-{{ $loop->parent->index }}-{{ $loop->index }}">
                                                            @if(isset($opt['image']) && !empty($opt['image']))
                                                                <img src="{{ Storage::disk('public')->url($opt['image']) }}" class="rounded me-2" style="width: 30px; height: 30px; object-fit: cover;">
                                                            @endif
                                                            <span>{{ $opt['text'] }}</span>
                                                        </label>
                                                    </div>
                                                @endforeach
                                                @error("selectedWinners.$grupo") <div class="text-danger x-small mt-1" style="font-size: 0.7rem;">{{ $message }}</div> @enderror
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>
            <div class="modal-footer bg-light border-0 p-4">
                <button wire:click="closeEditModal" class="btn btn-secondary rounded-pill px-4">Cancelar</button>
                @if($modalMode === 'setEventResult')
                    <button wire:click="procesarResultados" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold">PROCESAR RESULTADOS</button>
                    <button wire:click.prevent="saveEventResult" class="btn btn-success rounded-pill px-4 shadow-sm fw-bold">GUARDAR RESULTADOS</button>
                @endif
            </div>
        </div>
    </div>
</div>
@endif
</div>
