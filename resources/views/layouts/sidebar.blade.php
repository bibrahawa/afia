<!-- Sidebar -->
<div class="sidebar" data-background-color="white-color">
    <div class="sidebar-logo">
        <!-- Logo Header -->
        <div class="logo-header" data-background-color="orange2">
            <a href="{{ url('/') }}" class="logo">
                <img src="assets/img/kaiadmin/logo_light.svg"
                    alt="navbar brand"
                    class="navbar-brand"
                    height="20"/>
            </a>
            <div class="nav-toggle">
                <button class="btn btn-toggle toggle-sidebar">
                    <i class="gg-menu-right"></i>
                </button>
                <button class="btn btn-toggle sidenav-toggler">
                    <i class="gg-menu-left"></i>
                </button>
            </div>
            <button class="topbar-toggler more">
                <i class="gg-more-vertical-alt"></i>
            </button>
        </div>
        <!-- End Logo Header -->
    </div>
    <div class="sidebar-wrapper scrollbar scrollbar-inner">
        <div class="sidebar-content">
            <ul class="nav nav-secondary">
                <li class="nav-item {{ request()->is('/') ? 'active' : '' }}">
                    <a href="{{ url('/') }}" class="collapsed" aria-expanded="false">
                        <i class="fas fa-home"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <li class="nav-section">
                    <span class="sidebar-mini-icon">
                        <i class="fa fa-ellipsis-h"></i>
                    </span>
                    <h4 class="text-section">Patiente</h4>
                </li>

                <li class="nav-item {{ request()->routeIs('patient.*') ? 'active' : '' }}">
                    <a href="{{ route('patient.index') }}">
                        <i class="fas fa-user-injured"></i>
                        <p>Patiente</p>
                    </a>
                </li>

                <li class="nav-item {{ request()->routeIs('consultation.*') ? 'active' : '' }}">
                    <a href="{{ route('consultation.index') }}">
                        <i class="fas fa-stethoscope"></i>
                        <p>Consultation</p>
                    </a>
                </li>

                <li class="nav-section">
                    <span class="sidebar-mini-icon">
                        <i class="fa fa-ellipsis-h"></i>
                    </span>
                    <h4 class="text-section">Comptabilite</h4>
                </li>

                <li class="nav-item {{ request()->routeIs('account.*') ? 'active' : '' }}">
                    <a href="{{ route('account.facture') }}">
                        <i class="fas fa-credit-card"></i>
                        <p>Paiement en attente</p>
                    </a>
                </li>

                <li class="nav-section">
                    <span class="sidebar-mini-icon">
                        <i class="fa fa-ellipsis-h"></i>
                    </span>
                    <h4 class="text-section">Administration</h4>
                </li>

                <li class="nav-item {{ request()->routeIs('department.*') ? 'active' : '' }}">
                    <a href="{{ route('department.index') }}">
                        <i class="fas fa-building"></i>
                        <p>Departements</p>
                    </a>
                </li>

                <li class="nav-item {{ request()->routeIs('service.*') ? 'active' : '' }}">
                    <a href="{{ route('service.index') }}">
                        <i class="fas fa-cogs"></i>
                        <p>Services</p>
                    </a>
                </li>

                <li class="nav-item {{ request()->routeIs('test.*') ? 'active' : '' }}">
                    <a href="{{ route('test.index') }}">
                        <i class="fas fa-microscope"></i>
                        <p>Examens</p>
                    </a>
                </li>

                <li class="nav-item {{ request()->routeIs('package.*') ? 'active' : '' }}">
                    <a href="{{ route('package.index') }}">
                        <i class="fas fa-box"></i>
                        <p>Packages</p>
                    </a>
                </li>

                <li class="nav-item {{ request()->routeIs('medicaments.*') ? 'active' : '' }}">
                    <a href="{{ route('medicaments.index') }}">
                        <i class="fas fa-pills"></i>
                        <p>Medicaments</p>
                    </a>
                </li>

                <li class="nav-item {{ request()->routeIs('employee.*') ? 'active' : '' }}">
                    <a href="{{ route('employee.index') }}">
                        <i class="fas fa-users"></i>
                        <p>Employes</p>
                    </a>
                </li>

                <li class="nav-item {{ request()->routeIs('chambres.*') ? 'active' : '' }}">
                    <a href="{{ route('chambres.index') }}">
                        <i class="fas fa-bed"></i>
                        <p>Chambres</p>
                    </a>
                </li>

                <li class="nav-item {{ request()->routeIs('hospitalisations.*') ? 'active' : '' }}">
                    <a href="{{ route('hospitalisations.index') }}">
                        <i class="fas fa-hospital"></i>
                        <p>Hospitalisations</p>
                    </a>
                </li>

                <li class="nav-section">
                    <span class="sidebar-mini-icon">
                        <i class="fa fa-ellipsis-h"></i>
                    </span>
                    <h4 class="text-section">Users</h4>
                </li>

                <li class="nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
                    <a href="{{ route('users.index') }}">
                        <i class="fas fa-user-cog"></i>
                        <p>Users</p>
                    </a>
                </li>

                <li class="nav-section">
                    <span class="sidebar-mini-icon">
                        <i class="fa fa-ellipsis-h"></i>
                    </span>
                    <h4 class="text-section">Rapport</h4>
                </li>

                <li class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                    <a href="{{ route('reports.index') }}">
                        <i class="fas fa-chart-bar"></i>
                        <p>Rapport</p>
                    </a>
                </li>

            </ul>
        </div>
    </div>
    </div>
    <!-- End Sidebar -->

    <style>
    /* Styles pour le menu actif */
    .nav-item.active a {
        background: linear-gradient(90deg, rgba(255, 149, 0, 0.1) 0%, transparent 100%);
        color: #ff9500 !important;
        border-left: 3px solid #ff9500;
        font-weight: 600;
    }

    .nav-item a {
        border-left: 3px solid transparent;
        transition: all 0.3s ease;
    }

    .nav-item a:hover {
        background: #f8f9fa;
        color: #ff9500;
        border-left-color: #ff9500;
    }
    </style>
