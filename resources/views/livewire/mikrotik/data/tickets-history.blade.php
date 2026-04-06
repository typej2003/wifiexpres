<div class="container-fluid py-4">
    {{-- OVERLAY DE CARGA --}}
    @if($showOverlay)
    <div class="d-print-none" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
        <div class="text-center text-white">
            <div class="spinner-grow text-primary mb-3" style="width: 3rem; height: 3rem;"></div>
            <h4 class="fw-bold">ACTUALIZANDO HISTORIAL</h4>
            <p>Sincronizando registros con el MikroTik...</p>
        </div>
    </div>
    @endif

    {{-- CABECERA EXCLUSIVA PARA IMPRESIÓN --}}
    <div class="d-none d-print-block mb-4 text-center">
        <h2 class="fw-bold">REPORTE HISTORIAL DE TICKETS</h2>
        <p class="text-muted">
            Generado el: {{ now()->format('d/m/Y h:i A') }} 
            @if($filterRouter) | Router: {{ \App\Models\Router::find($filterRouter)->identity }} @endif
        </p>
        <hr>
    </div>

    {{-- FILTROS Y BOTONES (Ocultos en impresión) --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4 d-print-none">
        <div class="card-body p-4">
            <div class="row align-items-center mb-4">
                <div class="col-12 col-md-6">
                    <h4 class="fw-800 mb-0">
                        <i class="bi bi-clock-history text-primary me-2"></i>Historial de Tickets
                    </h4>
                </div>
                <div class="col-12 col-md-6 text-md-end mt-3 mt-md-0">
                    <div class="d-flex gap-2 justify-content-md-end">
                        <button type="button" onclick="window.print()" class="btn btn-danger rounded-pill px-4 fw-bold shadow-sm">
                            <i class="bi bi-printer me-1"></i> IMPRIMIR
                        </button>
                        <button type="button" wire:click="openSyncModal" class="btn btn-warning rounded-pill px-4 fw-bold shadow-sm">
                            <i class="bi bi-arrow-repeat me-1"></i> SINCRONIZAR SMART
                        </button>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" wire:model="search" class="form-control border-start-0" placeholder="PIN o Identidad...">
                    </div>
                </div>

                <div class="col-md-2">
                    <select wire:model="filterOrigen" class="form-select border-2 border-primary-soft">
                        <option value="">Todos los Orígenes</option>
                        <option value="tickets">🎫 Lotes (Tickets)</option>
                        <option value="pasarela">💳 Pasarela (Venta)</option>
                        <option value="trial">🎁 Trial (Gratis)</option>
                    </select>
                </div>

                @if(auth()->user()->role === 'admin')
                <div class="col-md-2">
                    <select wire:model="filterAliado" class="form-select">
                        <option value="">Todos los Aliados</option>
                        @foreach($aliados as $a) <option value="{{ $a->id }}">{{ $a->name }}</option> @endforeach
                    </select>
                </div>
                @endif

                <div class="col-md-2">
                    <select wire:model="filterRouter" class="form-select">
                        <option value="">Todos los Routers</option>
                        @foreach($routers as $r) 
                            <option value="{{ $r->id }}">{{ $r->active ? '🟢' : '🔴' }} {{ $r->identity }}</option> 
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <select wire:model="filterEstado" class="form-select">
                        <option value="">Cualquier Estado</option>
                        <option value="disponible">DISPONIBLE</option>
                        <option value="en_uso">EN USO</option>
                        <option value="agotado">AGOTADO</option>
                        <option value="anulado">ANULADO</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- TABLA - AQUÍ ESTÁ EL CAMBIO DE CLASES PARA IMPRESIÓN --}}
    <div class="card border-0 shadow-sm rounded-4 card-print-flat">
        <div class="table-responsive table-print-visible">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small fw-bold">
                    <tr>
                        <th class="px-4 py-3">IDENTIDAD / TIPO</th>
                        <th class="py-3">ROUTER / ALIADO</th>
                        <th class="py-3 text-center">PLAN / COSTO</th>
                        <th class="py-3 text-center cursor-pointer" wire:click="toggleSort">
                            CONSUMO 
                            <span class="d-print-none">
                                @if($sortDirection === 'asc') <i class="bi bi-sort-numeric-down"></i> 
                                @else <i class="bi bi-sort-numeric-up-alt"></i> @endif
                            </span>
                        </th>
                        <th class="py-3 text-center">ESTADO</th>
                        <th class="text-end px-4">FECHA</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $t)
                    @php 
                        $isTrial = str_contains($t->identity, 'IMP-T-');
                        $isLote = str_contains($t->identity, 'Lote') || 
                                  str_contains($t->identity, '2026-04-02') || 
                                  str_contains($t->identity, '2026-04-03') || 
                                  str_contains($t->identity, '2026-04-04');
                        $isVenta = str_contains($t->identity, 'IMP-') && !$isTrial;
                        $planLower = strtolower($t->plan);
                        $isGratis = (str_contains($planLower, 'neutro') || str_contains($planLower, 'cortesia') || str_contains($planLower, 'trial'));
                        $costo = $isGratis ? 0 : 1;
                    @endphp
                    <tr>
                        <td class="px-4">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm me-2 bg-light rounded d-flex align-items-center justify-content-center d-print-none" style="width: 32px; height: 32px;">
                                    @if($isTrial) <i class="bi bi-gift-fill text-success"></i>
                                    @elseif($isLote) <i class="bi bi-layers-fill text-primary"></i> 
                                    @elseif($isVenta) <i class="bi bi-credit-card-fill text-warning"></i>
                                    @else <i class="bi bi-person-fill text-secondary"></i> @endif
                                </div>
                                <div>
                                    <span class="fw-bold text-dark d-block">{{ $t->username }}</span>
                                    <small class="text-muted" style="font-size: 0.7rem;">{{ $t->identity }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border border-print-0">{{ $t->router->identity }}</span>
                            <div class="small text-muted">{{ $t->router->user->name }}</div>
                        </td>
                        <td class="text-center">
                            <span class="fw-bold d-block">{{ $t->plan }}</span>
                            <span class="badge {{ $costo > 0 ? 'bg-soft-primary text-primary' : 'bg-soft-success text-success' }} small border-print-0">
                                Costo: {{ $costo }}
                            </span>
                        </td>
                        <td class="text-center">
                            <code class="text-primary fw-bold" style="font-size: 1.1rem;">{{ $t->tiempo_consumido ?: '0s' }}</code>
                        </td>
                        <td class="text-center">
                            @php $color = ['disponible'=>'success','en_uso'=>'info','agotado'=>'secondary','anulado'=>'danger'][$t->estado] ?? 'dark'; @endphp
                            <span class="badge bg-{{ $color }} rounded-pill px-3 shadow-sm border-print-0">{{ strtoupper($t->estado) }}</span>
                        </td>
                        <td class="text-end px-4 text-nowrap">
                            <span class="text-muted small d-block">{{ $t->created_at->format('d/m/Y') }}</span>
                            <span class="text-muted small">{{ $t->created_at->format('H:i') }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center py-5 text-muted">No se encontraron registros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="card-footer bg-white border-0 p-3 d-print-none">
            {{ $tickets->links() }}
        </div>
    </div>
</div>

<style>
    .bg-soft-primary { background-color: rgba(13, 110, 253, 0.1); }
    .bg-soft-success { background-color: rgba(25, 135, 84, 0.1); }
    .cursor-pointer { cursor: pointer; }
    .fw-800 { font-weight: 800; }

    @media print {
        /* Reset de márgenes de página */
        @page { 
            size: portrait; 
            margin: 0.5cm; 
        }

        /* Forzar visibilidad de todo el contenido */
        body { 
            background: white !important; 
            overflow: visible !important;
        }

        .container-fluid { 
            padding: 0 !important; 
            width: 100% !important;
        }

        /* Quitar scroll de la tabla y sombras del card */
        .card-print-flat { 
            box-shadow: none !important; 
            border: none !important; 
            overflow: visible !important;
        }

        .table-print-visible { 
            overflow: visible !important; 
            display: block !important;
        }

        /* Ajustes de tabla */
        .table { 
            width: 100% !important; 
            border-collapse: collapse !important;
            font-size: 8.5pt !important; /* Texto un poco más pequeño para que quepa todo */
        }

        .table td, .table th {
            padding: 4px !important;
            border: 1px solid #eee !important;
        }

        /* Badges y estilos visuales en blanco y negro para mejor lectura */
        .badge { 
            border: 1px solid #ddd !important; 
            background: transparent !important; 
            color: black !important; 
            text-shadow: none !important;
        }

        .text-primary, .text-success, .text-warning, code { 
            color: black !important; 
            font-weight: bold !important;
        }

        /* Ocultar elementos innecesarios */
        .d-print-none, .card-footer, .avatar-sm { 
            display: none !important; 
        }
    }
</style>