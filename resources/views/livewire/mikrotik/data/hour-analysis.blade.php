<div class="p-4">
    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold text-primary"><i class="fas fa-filter me-2"></i>Filtros de Análisis</h5>
        </div>
        <div class="card-body bg-light">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="small fw-bold text-muted">FECHA INICIO</label>
                    <input type="date" wire:model="fromDate" class="form-control border-0 shadow-sm">
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold text-muted">FECHA FIN</label>
                    <input type="date" wire:model="toDate" class="form-control border-0 shadow-sm">
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold text-muted">ROUTER</label>
                    <select wire:model="selectedRouter" class="form-select border-0 shadow-sm">
                        <option value="">-- Seleccionar --</option>
                        @foreach($routers as $r) <option value="{{ $r->id }}">{{ $r->identity }}</option> @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold text-muted">ZONA (ÁREA)</label>
                    <select wire:model="selectedZona" class="form-select border-0 shadow-sm" {{ !$selectedRouter ? 'disabled' : '' }}>
                        <option value="">-- Todas las zonas --</option>
                        @foreach($zonas as $z) <option value="{{ $z->id }}">{{ $z->location_name }}</option> @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold text-muted">RANGO DE EDAD</label>
                    <select wire:model="selectedEdad" class="form-select border-0 shadow-sm">
                        <option value="">-- Todos --</option>
                        <option value="menor18">Menores de 18</option>
                        <option value="18-24">18-24 años</option>
                        <option value="25-35">25-35 años</option>
                        <option value="mayor35">Mayores de 35</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold text-muted">GÉNERO</label>
                    <select wire:model="selectedGenero" class="form-select border-0 shadow-sm">
                        <option value="">-- Todos --</option>
                        <option value="F">Femenino</option>
                        <option value="M">Masculino</option>
                    </select>
                </div>
                <div class="col-md-6 d-flex align-items-end justify-content-end">
                    <button wire:click="consultar" wire:loading.attr="disabled" class="btn btn-primary px-5 fw-bold rounded-pill shadow">
                        <span wire:loading wire:target="consultar" class="spinner-border spinner-border-sm me-2"></span>
                        CONSULTAR
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- RESUMEN DETALLADO POR FILTROS --}}
    @if(count($summaries) > 0)
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
        <div class="card-header bg-primary text-white py-3">
            <h6 class="mb-0 fw-bold text-uppercase small"><i class="fas fa-chart-pie me-2"></i>Resumen de Impacto por Filtro</h6>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="bg-light text-muted small">
                    <tr class="text-uppercase">
                        <th class="ps-4">Segmento / Filtro</th>
                        <th class="text-center">Total Conexiones</th>
                        <th class="text-center">Usuarios Únicos</th>
                        <th class="text-center">Promedio Conex. x Usuario</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($summaries as $label => $data)
                    <tr class="{{ $label == 'General' ? 'bg-light fw-bold' : '' }}">
                        <td class="ps-4">
                            @if($label == 'General') <i class="fas fa-globe text-primary me-2"></i>
                            @elseif(str_contains($label, 'Edad')) <i class="fas fa-birthday-cake text-warning me-2"></i>
                            @else <i class="fas fa-user-tag text-info me-2"></i> @endif
                            {{ $label }}
                        </td>
                        <td class="text-center"><span class="badge bg-primary rounded-pill">{{ number_format($data['conexiones']) }}</span></td>
                        <td class="text-center text-dark">{{ number_format($data['usuarios']) }}</td>
                        <td class="text-center text-muted">
                            {{ $data['usuarios'] > 0 ? number_format($data['conexiones'] / $data['usuarios'], 2) : 0 }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- TABLAS DE OCUPACIÓN POR SEGMENTO --}}
    @foreach($reports as $label => $matrix)
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-5">
        <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-uppercase">Ocupación: {{ $label }}</h6>
            <span class="badge bg-light text-dark">{{ count($matrix) }} días con actividad</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead class="bg-light text-center small text-uppercase fw-bold">
                        <tr>
                            <th style="min-width: 120px;" class="bg-white">Fecha</th>
                            @for($h=0; $h<24; $h++) <th style="font-size: 10px;">{{ str_pad($h, 2, '0', STR_PAD_LEFT) }}h</th> @endfor
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dates as $date)
                            <tr>
                                <td class="fw-bold bg-light small text-center">{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</td>
                                @for($h=0; $h<24; $h++)
                                    @php $count = $matrix[$date][$h] ?? 0; @endphp
                                    <td class="text-center small {{ $count > 0 ? 'bg-primary text-white fw-bold' : 'text-muted' }}" 
                                        style="{{ $count > 0 ? 'border: 1px solid #fff !important;' : '' }}">
                                        {{ $count ?: '-' }}
                                    </td>
                                @endfor
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endforeach
    @endif

    @if(count($summaries) == 0 && $selectedRouter)
        <div class="alert alert-info rounded-4 shadow-sm">
            <i class="fas fa-info-circle me-2"></i> No se encontraron registros para los criterios seleccionados.
        </div>
    @endif

    <script>
        window.addEventListener('reportUpdated', event => {
            console.log('Datos actualizados correctamente');
            // Aquí puedes añadir lógica de scroll o resaltado con Vanilla JS
        });
    </script>
</div>