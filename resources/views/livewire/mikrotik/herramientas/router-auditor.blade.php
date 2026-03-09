<div wire:poll.2s="refreshData">
    <div class="container-fluid py-4">
        
        @if (session()->has('message'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> {{ session('message') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card shadow-lg border-0">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-cpu-fill text-info me-2"></i> 
                    Monitor de Tráfico en Tiempo Real (Bridge v2.5)
                </h5>
                <div>
                    <span class="badge bg-primary me-2">Reintento: 60s</span>
                    {{-- FIX: Se agregó wire:click para que el botón funcione --}}
                    <button wire:click="refreshData" class="btn btn-sm btn-outline-light">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>
            </div>
            
            <div class="card-body p-0">
                @if($error)
                    <div class="alert alert-danger m-3 border-0 shadow-sm">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ $error }}
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-bordered mb-0" style="table-layout: fixed;">
                        <thead class="table-dark small text-center text-uppercase">
                            <tr>
                                <th style="width: 18%;">Router / Acciones</th>
                                <th style="width: 27%;" class="bg-warning text-dark border-warning">📥 1. Salida (Pendientes)</th>
                                <th style="width: 28%;" class="bg-info text-dark border-info">🚀 2. En Tránsito (Enviado)</th>
                                <th style="width: 27%;" class="bg-success text-white border-success">📤 3. Entrada (Confirmado)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($routersOnline as $router)
                                @php $routerMac = $router['mac'] ?? ''; @endphp
                                <tr>
                                    <td class="bg-light align-top">
                                        <div class="p-2">
                                            <strong class="d-block text-truncate" title="{{ $router['identity'] ?? 'N/A' }}">
                                                {{ $router['identity'] ?? 'N/A' }}
                                            </strong>
                                            <code class="small text-primary d-block mb-2" style="font-size: 0.7rem;">{{ $routerMac }}</code>
                                            
                                            <button wire:click="sendTestCommand('{{ $routerMac }}')" 
                                                    class="btn btn-xs btn-outline-primary w-100 py-1" 
                                                    style="font-size: 0.65rem;">
                                                <i class="bi bi-play-fill"></i> Test Script
                                            </button>

                                            <div class="mt-2 pt-2 border-top small text-muted">
                                                IP: {{ $router['ip'] ?? 'N/A' }}
                                            </div>
                                        </div>
                                    </td>
                                    
                                    {{-- COLUMNA 1: PENDIENTES --}}
                                    <td class="p-0 align-top">
                                        <div style="max-height: 350px; overflow-y: auto; font-family: 'Courier New', monospace; font-size: 0.7rem;">
                                            @forelse($router['comandosDetalle'] ?? [] as $index => $cmd)
                                                <div class="list-group-item list-group-item-warning border-bottom p-2">
                                                    <span class="badge bg-dark me-1">{{ $index + 1 }}</span> 
                                                    {{ Str::limit($cmd, 120) }}
                                                </div>
                                            @empty
                                                <div class="p-4 text-center text-muted small italic opacity-50">Vacío</div>
                                            @endforelse
                                        </div>
                                    </td>

                                    {{-- COLUMNA 2: EN TRÁNSITO --}}
                                    <td class="p-0 align-top bg-light">
                                        <div style="max-height: 350px; overflow-y: auto; font-family: 'Courier New', monospace; font-size: 0.7rem;">
                                            @forelse($router['transitoDetalle'] ?? [] as $item)
                                                <div class="list-group-item list-group-item-info border-bottom p-2 shadow-sm">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <span class="badge bg-primary">TID: {{ $item['tid'] }}</span>
                                                        <span class="text-primary fw-bold"><i class="bi bi-stopwatch"></i> {{ $item['age'] }}</span>
                                                    </div>
                                                    <div class="text-muted small" style="line-height: 1;">{{ Str::limit($item['cmd'], 80) }}</div>
                                                </div>
                                            @empty
                                                <div class="p-4 text-center text-muted small italic opacity-50">Esperando...</div>
                                            @endforelse
                                        </div>
                                    </td>

                                    {{-- COLUMNA 3: RESULTADOS (Limitado a los últimos 5) --}}
                                    <td class="p-0 align-top">
                                        <div style="max-height: 350px; overflow-y: auto; font-family: 'Courier New', monospace; font-size: 0.7rem;">
                                            {{-- Se usa array_reverse para mostrar el más nuevo arriba y array_slice para limitar a 5 --}}
                                            @php 
                                                $resultados = $router['resultadosDetalle'] ?? [];
                                                $ultimosResultados = array_slice(array_reverse($resultados), 0, 5);
                                            @endphp

                                            @forelse($ultimosResultados as $res)
                                                <div class="list-group-item list-group-item-success border-bottom p-2">
                                                    <div class="d-flex justify-content-between">
                                                        <b class="text-success">TID: {{ $res['tid'] }}</b>
                                                        <i class="bi bi-check-all text-success"></i>
                                                    </div>
                                                    <div class="text-dark bg-white p-1 mt-1 border rounded">{{ $res['data'] }}</div>
                                                </div>
                                            @empty
                                                <div class="p-4 text-center text-muted small italic opacity-50">Sin datos</div>
                                            @endforelse
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-5">
                                        <div class="spinner-border text-primary mb-2" role="status"></div>
                                        <p class="mb-0 text-muted">Buscando Routers activos en el puerto 3000...</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="card-footer bg-light d-flex justify-content-between align-items-center py-3">
                <div class="small">
                    <strong>Estados:</strong> 
                    <span class="ms-2"><i class="bi bi-circle-fill text-warning"></i> En Cola</span>
                    <span class="ms-2"><i class="bi bi-circle-fill text-info"></i> En MikroTik</span>
                    <span class="ms-2"><i class="bi bi-circle-fill text-success"></i> Confirmado (Top 5)</span>
                </div>
                <div class="text-muted small fw-bold">
                    <i class="bi bi-clock me-1"></i> Sync: {{ now()->format('H:i:s') }}
                </div>
            </div>
        </div>
    </div>
</div>