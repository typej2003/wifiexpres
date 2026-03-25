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
                                    <td class="text-center small {{ $count > 0 ? 'fw-bold text-primary' : 'text-muted' }}" 
                                        style="{{ $count > 0 ? 'background-color: rgba(0,123,255,'.min($count/50, 0.4).')' : '' }}">
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