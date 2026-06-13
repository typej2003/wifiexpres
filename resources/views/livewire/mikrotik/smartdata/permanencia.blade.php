<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header pb-0">
                    <h5 class="mb-1">Reporte de Permanencia</h5>
                    <p class="text-sm">Analiza el tiempo promedio de estancia y la fidelidad de los clientes.</p>
                </div>
                <div class="card-body">
                    
                    <!-- Filtros Rápidos -->
                    <div class="row g-3 mb-4 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label text-xs font-weight-bold">Período</label>
                            <select class="form-select form-select-sm" wire:model="selectedPeriod" wire:change="setDatesForPeriod($event.target.value)">
                                <option value="dia">Hoy</option>
                                <option value="semana">Últimos 7 días</option>
                                <option value="mes">Últimos 30 días</option>
                                <option value="trimestre">Últimos 3 meses</option>
                                <option value="custom">Personalizado</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-xs font-weight-bold">Desde</label>
                            <input type="date" class="form-control form-control-sm" wire:model.defer="fromDate" @if($selectedPeriod != 'custom') disabled @endif>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-xs font-weight-bold">Hasta</label>
                            <input type="date" class="form-control form-control-sm" wire:model.defer="toDate" @if($selectedPeriod != 'custom') disabled @endif>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-xs font-weight-bold">Local / Router</label>
                            <select class="form-select form-select-sm" wire:model="selectedRouter">
                                <option value="">Todos los locales</option>
                                @foreach($routers as $r)
                                    <option value="{{ $r->id }}">{{ $r->identity }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button class="btn btn-sm btn-dark mb-0 w-100" wire:click="consultar" wire:loading.attr="disabled">
                                <i class="bi bi-filter"></i> Filtrar
                            </button>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="btn-group btn-group-sm w-100 shadow-none" role="group">
                                <input type="radio" class="btn-check" name="btnType" id="type1" wire:model="clientType" value="todos" wire:click="consultar" autocomplete="off">
                                <label class="btn btn-outline-primary" for="type1">Todos los Clientes</label>

                                <input type="radio" class="btn-check" name="btnType" id="type2" wire:model="clientType" value="nuevos" wire:click="consultar" autocomplete="off">
                                <label class="btn btn-outline-primary" for="type2">Solo Nuevos</label>

                                <input type="radio" class="btn-check" name="btnType" id="type3" wire:model="clientType" value="recurrentes" wire:click="consultar" autocomplete="off">
                                <label class="btn btn-outline-primary" for="type3">Solo Recurrentes</label>
                            </div>
                        </div>
                    </div>

                    <!-- Cuadro de Resumen -->
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Local / Zona</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Nuevos Clientes</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Clientes Totales</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Tiempo Promedio de Estancia</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($results as $res)
                                    <tr>
                                        <td>
                                            <div class="d-flex px-3 py-1">
                                                <div class="d-flex flex-column justify-content-center">
                                                    <h6 class="mb-0 text-sm">{{ $res['zona'] }}</h6>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="align-middle text-center">
                                            <span class="badge badge-sm bg-gradient-success">{{ $res['nuevos'] }}</span>
                                        </td>
                                        <td class="align-middle text-center">
                                            <span class="text-secondary text-xs font-weight-bold">{{ $res['totales'] }}</span>
                                        </td>
                                        <td class="align-middle text-center">
                                            <div class="d-flex align-items-center justify-content-center">
                                                <i class="bi bi-clock-history me-2 text-info"></i>
                                                <span class="text-dark text-sm font-weight-bold">{{ $res['promedio'] }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-5">
                                            <p class="text-secondary mb-0">No hay datos suficientes para el rango seleccionado.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Tabla de Detalle de Clientes (Permanencia y Fidelidad) -->
                    <div class="mt-5">
                        <h6 class="mb-3"><i class="bi bi-people-fill me-2"></i>Detalle de Permanencia y Fidelidad por Cliente</h6>
                        
                        @forelse($results as $res)
                            <div class="card border shadow-none mb-4">
                                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                                    <span class="text-xs font-weight-bold text-uppercase">Zona / Local: {{ $res['zona'] }}</span>
                                    <span class="badge bg-secondary text-xxs">{{ count($detailedClients[$res['router_id']] ?? []) }} Clientes</span>
                                </div>
                                <div class="table-responsive">
                                    <table class="table align-items-center mb-0">
                                        <thead>
                                            <tr>
                                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Cliente / Contacto</th>
                                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Fidelidad</th>
                                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Visitas</th>
                                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Permanencia Total</th>
                                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Estancia Promedio</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @if(isset($detailedClients[$res['router_id']]) && count($detailedClients[$res['router_id']]) > 0)
                                                @foreach($detailedClients[$res['router_id']] as $client)
                                                    <tr>
                                                        <td class="px-4">
                                                            <div class="d-flex flex-column">
                                                                <h6 class="mb-0 text-sm">
                                                                    @if($client['user_id'])
                                                                        <a href="{{ route('aliadoSmartData.users-visits', ['userId' => $client['user_id']]) }}" class="text-primary text-decoration-underline">
                                                                            {{ $client['client_name'] }}
                                                                        </a>
                                                                    @else
                                                                        {{ $client['client_name'] }}
                                                                    @endif
                                                                </h6>
                                                                <p class="text-xs text-secondary mb-0">
                                                                    {{ $client['email'] != 'N/A' ? $client['email'] : 'Sin Email' }} | {{ $client['cellphone'] ?: 'Sin Teléfono' }}
                                                                </p>
                                                                <small class="text-xxs text-muted font-monospace">{{ $client['username'] }}</small>
                                                            </div>
                                                        </td>
                                                        <td class="align-middle text-center text-sm">
                                                            <span class="badge badge-sm {{ $client['is_new'] ? 'bg-gradient-success' : 'bg-gradient-info' }}">
                                                                {{ $client['is_new'] ? 'Nuevo' : 'Recurrente' }}
                                                            </span>
                                                        </td>
                                                        <td class="align-middle text-center">
                                                            <span class="text-secondary text-xs font-weight-bold">{{ $client['total_visits_in_period'] }}</span>
                                                        </td>
                                                        <td class="align-middle text-center text-xs">
                                                            {{ $client['total_duration_in_period'] }}
                                                        </td>
                                                        <td class="align-middle text-center">
                                                            <span class="text-dark text-xs font-weight-bold">{{ $client['avg_duration_in_period'] }}</span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @else
                                                <tr>
                                                    <td colspan="5" class="text-center py-3 text-xs text-secondary italic">No hay detalles registrados para esta zona.</td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4 border rounded bg-light">
                                <p class="text-secondary mb-0 text-sm">Realice una consulta para ver el desglose detallado.</p>
                            </div>
                        @endforelse
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
