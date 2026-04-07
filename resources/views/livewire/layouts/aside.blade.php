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
            <a href="" class="sidebar-link {{ request()->routeIs('home') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> 
                <span class="menu-text">Inicio</span>
            </a>

            <a href="{{ route('admin.index') }}" class="sidebar-link {{ request()->routeIs('admin.index') ? 'active' : '' }}">
                <i class="bi bi-shield-lock"></i> 
                <span class="menu-text">Panel Admin</span>
            </a>

            <a href="{{ route('listCarrusel') }}" class="sidebar-link {{ request()->routeIs('listCarrusel') ? 'active' : '' }}">
                <i class="bi bi-images"></i> 
                <span class="menu-text">Carrusel</span>
            </a>

            <a href="{{ route('hotspot.versions') }}" class="sidebar-link {{ request()->routeIs('hotspot.versions') ? 'active' : '' }}">
                <i class="bi bi-code-slash"></i> 
                <span class="menu-text">Versiones Hotspot</span>
            </a>
            
            <a href="/users" class="sidebar-link">
                <i class="bi bi-people"></i> 
                <span class="menu-text">Usuarios</span>
                <span class="badge rounded-pill bg-info text-dark ms-2">{{ $totalUsuarios ?? '0' }}</span>
            </a>

            <a href="{{ route('admin.subscriptions') }}" class="sidebar-link {{ request()->routeIs('admin.subscriptions') ? 'active' : '' }}">
                <i class="bi bi-person-check"></i> 
                <span class="menu-text">Gestionar Suscripciones</span>
                @php
                    $pendingSubs = \Illuminate\Support\Facades\DB::table('package_user')->where('status', 'pending')->count();
                @endphp
                @if($pendingSubs > 0)
                    <span class="badge rounded-pill bg-danger ms-2">{{ $pendingSubs }}</span>
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
            </a>

            <a href="{{ route('routers.index') }}" class="sidebar-link">
                <i class="bi bi-router me-2"></i>
                <span class="menu-text">Listar Routers</span>
                <span class="badge rounded-pill bg-info text-dark ms-2">{{ $totalRouters ?? '0' }}</span>
            </a>

            <a href="{{ route('tickets.index') }}" class="sidebar-link">
                <i class="bi bi-calendar-event"></i> 
                <span class="menu-text">Listar Tickets</span>
                <span class="badge rounded-pill bg-info text-dark ms-2">{{ $totalCitas ?? '0' }}</span>
            </a>

            <a href="{{ route('mikrotik.history') }}" class="sidebar-link {{ request()->routeIs('mikrotik.history') ? 'active' : '' }}">
                <i class="bi bi-clock-history"></i> 
                <span class="menu-text">Historial de Tickets</span>
            </a>

            <a href="{{ route('tickets.imprimir.index') }}" 
            class="sidebar-link {{ request()->routeIs('tickets.imprimir.index') ? 'active' : '' }}">
                <i class="bi bi-printer"></i>
                <span class="menu-text">CENTRO DE IMPRESIÓN</span>
            </a>

            <a href="{{ route('aliado.monitor') }}" class="sidebar-link">
                <i class="bi bi-calendar-event"></i> 
                <span class="menu-text">Monitor de usuarios</span>
            </a>

            <a class="sidebar-link {{ request()->routeIs('mikrotik.users-online') ? 'active bg-gradient-primary' : '' }}" 
            href="{{ route('mikrotik.users-online') }}">
                <div class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
                    <i class="bi bi-people-fill text-success text-sm opacity-10"></i>
                </div>
                <span class="nav-link-text ms-1">USUARIOS ONLINE</span>
            </a>

            <a href="{{ route('mikrotik.crear-directorios') }}" class="sidebar-link">
                <i class="fas fa-folder-plus"></i>  
                <span class="menu-text">Gestionar Directorios</span>
            </a>

            <a href="{{ route('mikrotik.remoto') }}" class="sidebar-link {{ request()->routeIs('mikrotik.remoto') ? 'active' : '' }}">
                <i class="bi bi-terminal"></i> 
                <span class="menu-text">Conf Remoto</span>
            </a>

            <a href="{{ route('mikrotik.confdetallada') }}" class="sidebar-link {{ request()->routeIs('mikrotik.confdetallada') ? 'active' : '' }}">
                <i class="bi bi-terminal"></i> 
                <span class="menu-text">Conf Remoto Detallada</span>
            </a>

            <a class="sidebar-link {{ request()->routeIs('mikrotik.cambiar-trial') ? 'active bg-gradient-primary text-white' : '' }}" 
            href="{{ route('mikrotik.cambiar-trial') }}">
                <div class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
                    <i class="bi bi-calendar-event {{ request()->routeIs('mikrotik.cambiar-trial') ? 'text-white' : 'text-primary' }} text-sm opacity-10"></i>
                </div>
                <span class="nav-link-text ms-1 text-uppercase">Cambiar Perfil Trial</span>
            </a>

            <a class="sidebar-link {{ request()->routeIs('mikrotik.confdetallada') ? 'active' : '' }}" 
            href="{{ route('mikrotik.herramientas.interfaces') }}">
                <i class="fas fa-network-wired"></i>
                <span>Interfaces</span>
            </a>

            <a href="{{ route('admin.diagnostico') }}" class="sidebar-link {{ request()->routeIs('admin.diagnostico') ? 'active' : '' }}">
                <i class="bi bi-terminal"></i> 
                <span class="menu-text">Diagnóstico</span>
            </a>

            <a href="{{ route('mikrotik.logs') }}" class="sidebar-link {{ request()->routeIs('mikrotik.logs') ? 'active' : '' }}">
                <i class="bi bi-journal-text me-2"></i>
                <span class="menu-text">Visor de Logs</span>
            </a>

            <a href="{{ route('admin.bridge.auditor') }}" class="sidebar-link {{ request()->routeIs('admin.bridge.auditor') ? 'active' : '' }}">
                <i class="bi bi-cpu-fill text-info"></i> 
                <span class="menu-text">Auditoría Bridge</span>
                <span class="badge rounded-pill bg-dark text-white ms-2">LIVE</span>
            </a>

            {{-- NUEVO ENLACE: REPORTE GLOBAL DE VENTAS --}}
            <a href="{{ route('mikrotik.router.all-sales') }}" class="sidebar-link {{ request()->routeIs('mikrotik.router.all-sales') ? 'active' : '' }}">
                <i class="bi bi-graph-up-arrow text-warning"></i> 
                <span class="menu-text">Ventas Globales</span>
            </a>

            {{-- NUEVO: Enlace para Campañas de Encuestas --}}
            <a href="{{ route('mikrotik.aliado.campaigns') }}" class="sidebar-link {{ request()->routeIs('mikrotik.aliado.campaigns') ? 'active' : '' }}">
                <i class="bi bi-megaphone"></i> 
                <span class="menu-text">CAMPAÑAS / ENCUESTAS</span>
            </a>

            <a href="{{ route('mikrotik.data.notificaciones') }}" 
                class="sidebar-link {{ request()->routeIs('mikrotik.data.notificaciones') ? 'active' : '' }}">
                    <i class="bi bi-bell-fill"></i> 
                    <span class="menu-text">NOTIFICACIONES APP</span>
                </a>

            <a href="{{ route('admin.configuraciones') }}" class="sidebar-link {{ request()->routeIs('admin.configuraciones') ? 'active' : '' }}">
                <i class="bi bi-gear-wide-connected"></i> 
                <span class="menu-text">Configuraciones</span>
            </a>
            
        @endif

        @if(auth()->user()->role === 'aliado')
            <a href="{{ route('aliado.index') }}" class="sidebar-link {{ request()->routeIs('aliado.index') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> 
                <span class="menu-text">ESCRITORIO</span>
            </a>

            <a href="{{ route('mikrotik.grafico') }}" class="sidebar-link {{ request()->routeIs('mikrotik.grafico') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> 
                <span class="menu-text">GRÁFICO POR ROUTER</span>
            </a>

            <a href="{{ route('aliado.hour.analysis') }}" class="sidebar-link {{ request()->routeIs('aliado.hour.analysis') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> 
                <span class="menu-text">ANÁLISIS DE HORAS</span>
            </a>

            <a href="{{ route('mikrotik.grafico-conexiones') }}" class="sidebar-link {{ request()->routeIs('mikrotik.grafico-conexiones') ? 'active' : '' }}">
                <i class="bi bi-bar-chart-steps"></i> 
                <span class="menu-text">RENDIMIENTO POR ROUTER</span>
            </a>

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

            <a href="{{ route('aliado.ventas') }}" class="sidebar-link {{ request()->routeIs('aliado.ventas') ? 'active' : '' }}">
                <i class="bi bi-cash-coin"></i>
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

            <a class="sidebar-link {{ request()->routeIs('mikrotik.users-online') ? 'active bg-gradient-primary' : '' }}" 
                href="{{ route('mikrotik.users-online') }}">
                    <div class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="bi bi-people-fill text-success text-sm opacity-10"></i>
                    </div>
                    <span class="nav-link-text ms-1">USUARIOS ONLINE</span>
                </a>

            {{-- NUEVO: Enlace para Campañas de Encuestas --}}
            <a href="{{ route('mikrotik.aliado.campaigns') }}" class="sidebar-link {{ request()->routeIs('mikrotik.aliado.campaigns') ? 'active' : '' }}">
                <i class="bi bi-megaphone"></i> 
                <span class="menu-text">CAMPAÑAS / ENCUESTAS</span>
            </a>

            <!-- <a href="{{ route('habladores.index') }}" class="sidebar-link {{ request()->routeIs('habladores.index') ? 'active' : '' }}">
                <i class="bi bi-tv"></i> 
                <span class="menu-text">Habladores Digitales</span>
                <span class="badge rounded-pill bg-warning text-dark ms-auto menu-text" style="font-size: 0.6rem; font-weight: 800;">PRO</span>
            </a> -->
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
                <span class="menu-text">Cerrar Sesión</span>
            </button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const btnToggle = document.getElementById('toggle-sidebar');
    const wrapper = document.getElementById('wrapper');
    const sidebar = document.getElementById('sidebar');

    const isDesktop = () => window.innerWidth >= 992;

    if (isDesktop() && localStorage.getItem('sidebar-minimized') === 'true') {
        sidebar.classList.add('minimized');
        if (wrapper) wrapper.classList.add('sidebar-minimized');
    }

    if (btnToggle) {
        btnToggle.addEventListener('click', function () {
            if (isDesktop()) {
                sidebar.classList.toggle('minimized');
                if (wrapper) wrapper.classList.toggle('sidebar-minimized');
                
                const minimized = sidebar.classList.contains('minimized');
                localStorage.setItem('sidebar-minimized', minimized);
            }
        });
    }

    window.addEventListener('resize', function() {
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