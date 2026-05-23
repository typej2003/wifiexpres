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

    {{-- TABLA DE RESUMEN DE FILTROS --}}
    @if($selectedRouter)
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold text-muted small text-uppercase"><i class="fas fa-info-circle me-2"></i>Resumen de Filtros Aplicados</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="bg-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-4 py-2">Total Conexiones</th>
                        <th class="py-2">Usuarios Únicos</th>
                        <th class="py-2">Router</th>
                        <th class="py-2">Periodo</th>
                        <th class="pe-4 py-2">Filtros Activos</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="fw-bold">
                        <td class="ps-4">
                            <span class="fs-5 text-primary">{{ number_format($totalConexiones) }}</span>
                        </td>
                        <td>
                            <span class="fs-5 text-dark">{{ number_format($totalUsuarios) }}</span>
                        </td>
                        <td class="text-secondary small">{{ $routers->find($selectedRouter)->identity ?? 'N/A' }}</td>
                        <td class="text-secondary small">{{ Carbon\Carbon::parse($fromDate)->format('d/m/Y') }} al {{ Carbon\Carbon::parse($toDate)->format('d/m/Y') }}</td>
                        <td class="pe-4">
                            @if($selectedZona) <span class="badge bg-info bg-opacity-10 text-info border border-info px-2">Zona</span> @endif
                            @if($selectedEdad) <span class="badge bg-info bg-opacity-10 text-info border border-info px-2">Edad</span> @endif
                            @if($selectedGenero) <span class="badge bg-info bg-opacity-10 text-info border border-info px-2">Género</span> @endif
                            @if(!$selectedZona && !$selectedEdad && !$selectedGenero) <span class="text-muted small fw-normal">Ninguno</span> @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="card-header bg-dark text-white py-3">
            <h6 class="mb-0 fw-bold text-uppercase">Ocupación por hora - Cliente</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0" id="table-analysis">
                    <thead class="bg-primary text-white text-center">
                        <tr>
                            <th style="min-width: 120px;">FECHA</th>
                            @for($h=0; $h<24; $h++)
                                <th style="font-size: 11px;">{{ str_pad($h, 2, '0', STR_PAD_LEFT) }}:00</th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dates as $date)
                            <tr>
                                <td class="fw-bold bg-light small text-center">{{ $date }}</td>
                                @for($h=0; $h<24; $h++)
                                    @php $count = $reportData[$date][$h] ?? 0; @endphp
                                    <td class="text-center small {{ $count > 0 ? 'bg-primary text-white fw-bold' : 'text-muted' }}" 
                                        style="{{ $count > 0 ? 'border: 1px solid #fff !important;' : '' }}">
                                        {{ $count }}
                                    </td>
                                @endfor
                            </tr>
                        @empty
                            <tr>
                                <td colspan="25" class="text-center py-5 text-muted">
                                    No hay datos para mostrar. Seleccione un router y presione Consultar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        window.addEventListener('reportUpdated', event => {
            console.log('Datos actualizados correctamente');
            // Aquí puedes añadir lógica de scroll o resaltado con Vanilla JS
        });
    </script>
</div>