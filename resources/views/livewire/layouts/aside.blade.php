<style>
    /* --- CONTENEDOR PRINCIPAL DEL SIDEBAR --- */
    .sidebar-rednet {
        background: #ffffff;
        height: 100vh;
        width: 260px; 
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border-right: 1px solid #eee;
        overflow-x: hidden;
        white-space: nowrap;
        position: sticky;
        top: 0;
        z-index: 1000;
    }

    /* ESTADO MINIMIZADO (Solo aplica en Desktop) */
    .sidebar-rednet.minimized {
        width: 80px;
    }

    /* Estilo de los Enlaces */
    .sidebar-link {
        display: flex;
        align-items: center;
        padding: 12px 24px;
        color: #444;
        text-decoration: none;
        font-weight: 500;
        transition: 0.2s;
        border-left: 4px solid transparent;
        cursor: pointer;
    }

    .sidebar-link:hover {
        background-color: #f1fbfc;
        color: #009b9f;
    }

    .sidebar-link.active {
        background-color: #f1fbfc;
        color: #009b9f;
        border-left-color: #009b9f;
        font-weight: 600;
    }

    .sidebar-link i { 
        font-size: 1.3rem; 
        min-width: 30px; 
        margin-right: 12px; 
        transition: margin 0.3s;
    }

    /* Ajustes cuando está Minimizado (Desktop) */
    .sidebar-rednet.minimized .sidebar-link {
        padding: 12px 0;
        justify-content: center;
    }

    .sidebar-rednet.minimized .sidebar-link i {
        margin-right: 0;
        font-size: 1.5rem;
    }

    .sidebar-rednet.minimized .menu-text,
    .sidebar-rednet.minimized .badge {
        display: none;
    }

    /* Cabecera del Sidebar */
    .header-sidebar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 20px 24px;
        min-height: 60px;
    }

    .sidebar-rednet.minimized .header-sidebar {
        justify-content: center;
        padding: 20px 0;
    }

    #toggle-sidebar {
        background: none;
        border: none;
        color: #009b9f;
        cursor: pointer;
        padding: 5px;
        border-radius: 5px;
        transition: background 0.2s;
        display: block;
    }

    #toggle-sidebar:hover {
        background: #f1fbfc;
    }

    /* --- RESPONSIVE: MÓVIL Y TABLET --- */
    @media (max-width: 991px) {
        .sidebar-rednet, 
        .sidebar-rednet.minimized {
            width: 260px !important;
        }

        .sidebar-rednet.minimized .menu-text,
        .sidebar-rednet.minimized .badge {
            display: inline-block !important;
        }

        .sidebar-rednet.minimized .sidebar-link {
            padding: 12px 24px !important;
            justify-content: flex-start !important;
        }

        .sidebar-rednet.minimized .sidebar-link i {
            margin-right: 12px !important;
        }

        #toggle-sidebar {
            display: none !important;
        }
    }

    /* --- SUBMENÚS --- */
    .sidebar-submenu {
        background-color: #f9f9f9;
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .sidebar-submenu .sidebar-link {
        padding-left: 52px;
        font-size: 0.85rem;
        border-left: 2px solid transparent;
    }

    .sidebar-rednet.minimized .arrow-icon {
        display: none;
    }

    .sidebar-rednet.minimized .collapse.show {
        display: none !important;
    }

    .arrow-icon {
        transition: transform 0.3s;
    }
</style>

<div class="sidebar-rednet" id="sidebar">
    <div class="header-sidebar">
        <p class="text-muted small fw-bold text-uppercase m-0 menu-text" style="font-size: 0.7rem; letter-spacing: 1px;">
            Navegación
        </p>
        <button id="toggle-sidebar" title="Expandir/Contraer">
            <i class="bi bi-list" style="font-size: 1.5rem;"></i>
        </button>
    </div>

    <div class="py-2">
        
        @if(auth()->user()->role === 'admin')
            {{-- MÉTRICAS Y MONITOREO --}}
            <a href="" class="sidebar-link {{ request()->routeIs('home') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> 
                <span class="menu-text">Inicio</span>
            </a>

            <a href="{{ route('mikrotik.router.all-sales') }}" class="sidebar-link {{ request()->routeIs('mikrotik.router.all-sales') ? 'active' : '' }}">
                <i class="bi bi-graph-up-arrow text-warning"></i> 
                <span class="menu-text">Ventas Globales</span>
            </a>

            <a href="{{ route('aliado.monitor') }}" class="sidebar-link">
                <i class="bi bi-display"></i> 
                <span class="menu-text">Monitor de usuarios</span>
            </a>

            <a class="sidebar-link {{ request()->routeIs('mikrotik.users-online') ? 'active' : '' }}" 
               href="{{ route('mikrotik.users-online') }}">
                <i class="bi bi-people-fill text-success"></i>
                <span class="menu-text">USUARIOS ONLINE</span>
            </a>

            <a href="{{ route('admin.bridge.auditor') }}" class="sidebar-link {{ request()->routeIs('admin.bridge.auditor') ? 'active' : '' }}">
                <i class="bi bi-cpu-fill text-info"></i> 
                <span class="menu-text">Auditoría Bridge</span>
                <span class="badge rounded-pill bg-dark text-white ms-2">LIVE</span>
            </a>
            
            <a href="{{ route('mikrotik.history') }}" class="sidebar-link {{ request()->routeIs('mikrotik.history') ? 'active' : '' }}">
                <i class="bi bi-clock-history"></i> 
                <span class="menu-text">Historial de Tickets</span>
            </a>

            <hr class="mx-3 text-muted opacity-25">

            {{-- GESTIÓN OPERATIVA --}}
            <a href="{{ route('admin.index') }}" class="sidebar-link {{ request()->routeIs('admin.index') ? 'active' : '' }}">
                <i class="bi bi-shield-lock"></i> 
                <span class="menu-text">Panel Admin</span>
            </a>

            <a href="{{ route('habladores.index') }}" class="sidebar-link {{ request()->routeIs('habladores.index') ? 'active' : '' }}">
                <i class="bi bi-tv"></i> 
                <span class="menu-text">Habladores Digitales</span>
                <span class="badge rounded-pill bg-warning text-dark ms-auto menu-text" style="font-size: 0.6rem; font-weight: 800;">PRO</span>
            </a> 

            <a href="{{ route('listCarrusel') }}" class="sidebar-link {{ request()->routeIs('listCarrusel') ? 'active' : '' }}">
                <i class="bi bi-images"></i> 
                <span class="menu-text">Carrusel</span>
            </a>

            <a href="
            <a href="{{ route('admin.subscriptions') }}" class="sidebar-link {{ request()->routeIs('admin.subscriptions') ? 'active' : '' }}">
                <i class="bi bi-person-check"></i> 
                <span class="menu-text">Suscripciones</span>
                @php
                    $pendingSubs = \Illuminate\Support\Facades\DB::table('package_user')->where('status', 'pending')->count();
                @endphp
                @if($pendingSubs > 0)rl $pendingSubs }}</span>
                @endif
            </a>

            <a href="{{ route('packages.index') }}" class="sidebar-link {{ request()->routeIs('packages.index') ? 'active' : '' }}">
                <i class="bi bi-box-seam"></i> 
                <span class="menu-text">Planes Comerciales</span>
                @php
                    $totalPackages = \App\Models\Package::count();
                @endphp
                <span class="badge rounded-pill bg-primary ms-2">{{ $totalPackages ?? '0' }}</span>
            </a>

            <a href="" class="sidebar-link">
                <i class="bi bi-calendar-event"></i> 
                <span class="menu-text">Listar Citas</span>
                <span class="badge rounded-pill bg-info text-dark ms-2">{{ $totalCitas ?? '0' }}</span>
            </a><a href="{{ route('routers.index') }}" class="sidebar-link">
                <i class="bi bi-router me-2"></i>
                <span class="menu-text">Listar Routers</span>
                <span class="badge rounded-pill bg-info text-dark ms-2">{{ $totalRouters ?? '0' }}</span>
            </a>
            <a href="{{ route('tickets.index') }}" class="sidebar-link">
                                <span class="menu-text">Listar Tickets</span>
                <span class="badge rounded-pill bg-info text-dark ms-2">{{ $totalCitas ?? '0' }}</span>
            </a>
            <a href="{{ route('tickets.imprimir.index') }}" 
            class="sidebar-link {{ request()->routeIs('tickets.imprimir.index') ? 'active' : '' }}">
                                <span class="menu-text">CENTRO DE IMPRESIÓN</span>
            </a>
            <a href="{{ route('mikrotik.aliado.campaigns') }}" class="sidebar-link {{ request()->routeIs('mikrotik.aliado.campaigns') ? 'active' : '' }}">
                <i class="bi bi-megaphone"></i> 
                <span class="menu-text">CAMPAÑAS / ENCUESTAS</span>
            </a>            <a href="{{ route('mikrotik.data.notificaciones') }}" 
                class="sidebar-{{e.coc }mnx-pi-25">

            {{-- CONFIGURACIÓN (ACORDEÓN) --}}
            @phpar-directorios') }}" class="sidebar-link">
                        <i class="bi bi-folder-plus"></i>  
                    </a><" clt n      <a class="sidebar-link {{ request()->routeIs('m.haots / rkot)
                    <a href="{{ coi xt">Habladores Digitales</span>
                <span class="badge rounded-pill bg-warning text-dark ms-auto menu-text" style="font-size: 0.6rem; font-weight: 800;">PRO</span>
            </a>
            <a href="{{ route('aliado.hour.analysis') }}" class="sidebar-link {{ request()->routeIs('aliado.hour.analysis') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> 
ef="{{ route('mikrotik.grafico-conexiones') }}" class="sidebar-link {{ request()->routeIs('mikrotik.grafico-conexiones') ? 'active' : '' }}">
                <i class="bi bi-bar-chart-steps"></i> 
                <span class="menu-text">RENDIMIENTO POR ROUTER</span>
            </a>
="bi bi-cash-coin"></i>
                <span class="menu-text">MIS VENTAS</span>
                @php
                    $salesCount = \App\Models\Sale::where('user_id', auth()->id())
                                    ->whereDate('created_at', today())
                                    ->count();
                @endphp
                @if($salesCount > 0)
                    <span class="badge rounded-pill bg-success ms-auto menu-text" style="font-size: 0.7rem;">+{{ $salesCount }}</span>
                @endif
            </a>

            <a href="{{ route('aliado.ranking') }}" class="sidebar-link {{ request()->routeIs('aliado.ranking') ? 'active' : '' }}">
                <i class="bi bi-trophy"></i> 
                <span class="menu-text">RANKING DE USUARIOS</span>
            </a>

            <a class="sidebar-link {{ request()->routeIs('mikrotik.users-online') ? 'active' : '' }}" 
                href="{{ route('mikrotik.users-online') }}">
                <i class="bi bi-people-fill text-success"></i>
                <span class="menu-text">USUARIOS ONLINE</span>
            </a>

            <hr class="mx-3 text-muted opacity-25">

            {{-- OPERACIÓN --}}
            <a href="{{ route('aliado.routers') }}" class="sidebar-link {{ request()->routeIs('aliado.routers') ? 'active' : '' }}">
                <i class="bi bi-router"></i>
                <span class="menu-text">MIS ROUTERS</span>
            </a>

            <a href="{{ route('mikrotik.history') }}" class="sidebar-link {{ request()->routeIs('mikrotik.history') ? 'active' : '' }}">
                <i class="bi bi-clock-history"></i> 
                <span class="menu-text">HISTORIAL DE TICKETS</span>
            </a>

            <a href="{{ route('tickets.imprimir.index') }}" 
            class="sidebar-link {{ request()->routeIs('tickets.imprimir.index') ? 'active' : '' }}">
                <i class="bi bi-printer"></i>
                <span class="menu-text">CENTRO DE IMPRESIÓN</span>
            </a>

            <a href="{{ route('mikrotik.grafico') }}" class="sidebar-link {{ request()->routeIs('mikrotik.grafico') ? 'active' : '' }}">
                <i class="bi bi-bar-chart"></i> 
                <span class="menu-text">GRÁFICO POR ROUTER</span>
            </a>

            {{-- NUEVO: Enlace para Campañas de Encuestas --}}
            <a href="{{ route('mikrotik.aliado.campaigns') }}" class="sidebar-link {{ request()->routeIs('mikrotik.aliado.campaigns') ? 'active' : '' }}">
                <i class="bi bi-megaphone"></i> 
                <span class="menu-text">CAMPAÑAS / ENCUESTAS</span>
            </a>
        @endif

        @if(auth()->user()->role === 'cliente')
            <a href="" class="sidebar-link {{ request()->routeIs('home') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> 
                <span class="menu-text">Inicio</span>
            </a>

            <a href="{{ route('cliente.index') }}" class="sidebar-link {{ request()->routeIs('cliente.index') ? 'active' : '' }}">
                <i class="bi bi-person-badge"></i> 
                <span class="menu-text">Mi Perfil</span>
            </a>
            <a href="/consultafacturas" class="sidebar-link">
                <i class="bi bi-receipt"></i> 
                <span class="menu-text">Mis Facturas</span>
            </a>
        @endif

        <hr class="mx-3 text-muted opacity-25">
        
        <form method="POST" action="{{ route('logout') }}" id="logout-sidebar">
            @csrf
            <button type="submit" class="sidebar-link border-0 bg-transparent w-100 text-start">
                <i class="bi bi-box-arrow-left"></i> 
                <span class="mS
<script>
document.addEventListener('DOMContentLoaded', function () {
    const btnToggle = document.getElementById('toggle-sidebar');
    const wrapper = document.getElementById('wrapper');
    const sidebar = document.getElementById('sidebar');

    const isDesktop = () => window.innerWidth >= 992;

    if (isDesktop() && localStorage.getItem('sidebar-minimized') === 'true') {

 le.akcSien() {
        if (!isDesktop()) {
            sidebar.classList.remove('minimized');
            if (wrapper) wrapper.classList.remove('sidebar-minimized');
        } else {
            if (localStorage.getItem('sidebar-minimized') === 'true') {
                sidebar.classList.add('minimized');
                if (wrapper) wrapper.classList.add('sidebar-minimized');
            }
        }
    });
});
</script>