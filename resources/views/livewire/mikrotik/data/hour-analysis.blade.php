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

    {{-- INFORMACIÓN DE FILTROS APLICADOS --}}
    @if($selectedRouter)
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3 bg-light">
            <h6 class="mb-2 fw-bold text-muted small text-uppercase"><i class="fas fa-filter me-2"></i>Filtros Aplicados</h6>
            <div class="d-flex flex-wrap gap-2">
                <span class="badge bg-primary text-white border border-primary px-3 py-2 fw-normal">
                    <i class="fas fa-calendar-alt me-1"></i> {{ \Carbon\Carbon::parse($fromDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($toDate)->format('d/m/Y') }}
                </span>
                <span class="badge bg-dark text-white border border-dark px-3 py-2 fw-normal">
                    <i class="fas fa-network-wired me-1"></i> Router: {{ $routers->find($selectedRouter)->identity ?? 'N/A' }}
                </span>
                @if($selectedZona)
                <span class="badge bg-info bg-opacity-10 text-info border border-info px-3 py-2 fw-normal">
                    <i class="fas fa-map-marker-alt me-1"></i> Zona: {{ $zonas->find($selectedZona)->location_name ?? 'N/A' }}
                </span>
                @endif
                @if($selectedEdad)
                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning px-3 py-2 fw-normal">
                    <i class="fas fa-user-tag me-1"></i> Edad: 
                    @if($selectedEdad == 'menor18') Menores de 18
                    @elseif($selectedEdad == '18-24') 18-24 años
                    @elseif($selectedEdad == '25-35') 25-35 años
                    @elseif($selectedEdad == 'mayor35') Mayores de 35
                    @endif
                </span>
                @endif
                @if($selectedGenero)
                <span class="badge bg-success bg-opacity-10 text-success border border-success px-3 py-2 fw-normal">
                    <i class="fas fa-venus-mars me-1"></i> Género: 
                    @if($selectedGenero == 'F') Femenino
                    @elseif($selectedGenero == 'M') Masculino
                    @endif
                </span>
                @endif
                @if(!$selectedZona && !$selectedEdad && !$selectedGenero) <span class="badge bg-secondary text-white px-3 py-2 fw-normal">Sin filtros adicionales</span> @endif
            </div>
        </div>
    </div>
    @endif

    {{-- TABLA DE RESUMEN DE IMPACTO --}}
    @if(count($summaries) > 0)
    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold text-dark text-uppercase"><i class="fas fa-chart-pie me-2"></i>Resumen de Impacto por Segmento</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light small text-uppercase fw-bold">
                        <tr>
                            <th class="ps-4">Segmento / Filtro</th>
                            <th class="text-center">Total Conexiones</th>
                            <th class="text-center">Usuarios Únicos</th>
                            <th class="text-center">Alcance %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($summaries as $label => $data)
                        <tr>
                            <td class="ps-4 fw-bold text-primary">{{ $label }}</td>
                            <td class="text-center"><span class="badge bg-light text-dark border px-3">{{ number_format($data['conexiones']) }}</span></td>
                            <td class="text-center fw-bold">{{ number_format($data['usuarios']) }}</td>
                            <td class="text-center">
                                @php $perc = $summaries['General']['usuarios'] > 0 ? ($data['usuarios'] / $summaries['General']['usuarios']) * 100 : 0; @endphp
                                <div class="progress" style="height: 8px; width: 100px; margin: 0 auto;">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $perc }}%"></div>
                                </div>
                                <small class="text-muted" style="font-size: 10px;">{{ number_format($perc, 1) }}% del total</small>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- TABLAS DE OCUPACIÓN POR SEGMENTO --}}
    @if(count($reports) > 0)
    @php 
        // Ordenamos para que General siempre sea la primera tabla
        $orderedReports = collect($reports)->sortByDesc(fn($v, $k) => $k === 'General');
    @endphp

    @foreach($orderedReports as $label => $matrix)
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">
        <div class="card-header {{ $label == 'General' ? 'bg-primary' : 'bg-dark' }} text-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-uppercase">Ocupación: {{ $label }}</h6>
            <span class="badge bg-white text-dark">{{ $summaries[$label]['usuarios'] }} Usuarios</span>
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

    <script>
        window.addEventListener('reportUpdated', event => {
            // Aquí puedes añadir lógica de scroll o resaltado con Vanilla JS
        });
    </script>
</div>

<style>
    .bg-primary.bg-opacity-10 { background-color: rgba(13, 110, 253, 0.1) !important; }
</style>