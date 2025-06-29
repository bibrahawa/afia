<!-- Sidebar -->
<div class="sidebar" data-background-color="white-color">
<div class="sidebar-logo">
    <!-- Logo Header -->
    <div class="logo-header" data-background-color="orange2">
        <a href="index.html" class="logo">
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
            <li class="nav-item active">
                <a
                    href="{{ url('/') }}"
                    class="collapsed"
                    aria-expanded="false">
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

            <li class="nav-item">
                <a href="{{ route('patient.index') }}">
                    <i class="fas fa-pen-square"></i>
                    <p>Patiente</p>
                </a>

                <a href="{{ route('consultation.index') }}">
                    <i class="fas fa-pen-square"></i>
                    <p>Consultation</p>
                </a>

            </li>

            <li class="nav-section">
                <span class="sidebar-mini-icon">
                    <i class="fa fa-ellipsis-h"></i>
                </span>
                <h4 class="text-section">Comptabilite</h4>
            </li>

            <li class="nav-item">
                <a href="{{ route('account.facture') }}">
                    <i class="fas fa-table"></i>
                    <p>Paiement en attente</p>
                </a>
            </li>

            <li class="nav-section">
                <span class="sidebar-mini-icon">
                    <i class="fa fa-ellipsis-h"></i>
                </span>
                <h4 class="text-section">Administration</h4>
            </li>

            <li class="nav-item">

                <a href="{{ route('department.index') }}">
                    <i class="fas fa-layer-group"></i>
                    <p>Departements</p>
                </a>

                <a href="{{ route('service.index') }}">
                    <i class="fas fa-layer-group"></i>
                    <p>Services</p>
                </a>

                <a href="{{ route('test.index') }}">
                    <i class="fas fa-th-list"></i>
                    <p>Examens</p>
                </a>

                <a href="{{ route('package.index') }}">
                    <i class="fas fa-layer-group"></i>
                    <p>Packages</p>
                </a>

                <a href="{{ route('medicaments.index') }}">
                    <i class="fas fa-th-list"></i>
                    <p>Medicaments</p>
                </a>

                <a href="{{ route('employee.index') }}">
                    <i class="fas fa-layer-group"></i>
                    <p>Employes</p>
                </a>

                {{-- <a href="{{ route('doctor.index') }}">
                    <i class="fas fa-layer-group"></i>
                    <p>Docteur OPD</p>
                </a> --}}
                {{-- <a href="{{ route('report.index') }}">
                    <i class="fas fa-layer-group"></i>
                    <p>Report</p>
                </a> --}}
            </li>

            <li class="nav-section">
                <span class="sidebar-mini-icon">
                    <i class="fa fa-ellipsis-h"></i>
                </span>
                <h4 class="text-section">Users</h4>
            </li>

            <li class="nav-item">

                <a href="{{ route('users.index') }}">
                    <i class="fas fa-layer-group"></i>
                    <p>Users</p>
                </a>
            </li>

            <li class="nav-section">
                <span class="sidebar-mini-icon">
                    <i class="fa fa-ellipsis-h"></i>
                </span>
                <h4 class="text-section">Rapport</h4>
            </li>

            <li class="nav-item">
                <a href="{{ route('reports.index') }}">
                    <i class="fas fa-layer-group"></i>
                    <p>Rapport</p>
                </a>
            </li>

        </ul>
    </div>
</div>
</div>
<!-- End Sidebar -->
