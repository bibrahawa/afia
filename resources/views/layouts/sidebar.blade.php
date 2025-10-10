<!-- Sidebar -->
<div class="sidebar" data-background-color="white-color">
    <div class="sidebar-logo">
        <!-- Logo Header -->
        <div class="logo-header" data-background-color="orange2">
            <img src="{{ asset("assets/img/logo.jpeg") }}"
                alt="Logo Aprosafe"
                class="h-12 w-auto object-contain mx-auto"/>

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
                <!-- DASHBOARD -->
                <li class="nav-item {{ request()->is('/') ? 'active' : '' }}">
                    <a href="{{ url('/home') }}">
                        <i class="fas fa-home"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <!-- GESTION DES PATIENTS -->
                <li class="nav-section">
                    <span class="sidebar-mini-icon">
                        <i class="fa fa-ellipsis-h"></i>
                    </span>
                    <h4 class="text-section">Patients</h4>
                </li>

                <li class="nav-item {{ request()->routeIs('patient.*') ? 'active' : '' }}">
                    <a href="{{ route('patient.index') }}">
                        <i class="fas fa-user"></i>
                        <p>Liste des patients</p>
                    </a>
                </li>

                <li class="nav-item {{ request()->routeIs('medecin.*') && request()->routeIs('*.appointments') ? 'active' : '' }}">
                    <a href="{{ route('medecin.appointments') }}">
                        <i class="fas fa-calendar-alt"></i>
                        <p>Rendez-vous</p>
                    </a>
                </li>

                <li class="nav-item {{ request()->routeIs('consultation.*') ? 'active' : '' }}">
                    <a href="{{ route('consultation.index') }}">
                        <i class="fas fa-stethoscope"></i>
                        <p>Consultations</p>
                    </a>
                </li>

                <li class="nav-item {{ request()->routeIs('hospitalisations.*') ? 'active' : '' }}">
                    <a href="{{ route('hospitalisations.index') }}">
                        <i class="fas fa-hospital"></i>
                        <p>Hospitalisations</p>
                    </a>
                </li>

                <!-- PERSONNEL MÉDICAL -->
                <li class="nav-section">
                    <span class="sidebar-mini-icon">
                        <i class="fa fa-ellipsis-h"></i>
                    </span>
                    <h4 class="text-section">Personnel</h4>
                </li>

                <li class="nav-item {{ request()->routeIs('employee.*') ? 'active' : '' }}">
                    <a href="{{ route('employee.index') }}">
                        <i class="fas fa-users"></i>
                        <p>Employés</p>
                    </a>
                </li>

                <li class="nav-item {{ request()->routeIs('medecin.*') && request()->routeIs('*.availabilities') ? 'active' : '' }}">
                    <a href="{{ route('medecin.availabilities') }}">
                        <i class="fas fa-calendar-check"></i>
                        <p>Disponibilités</p>
                    </a>
                </li>

                <li class="nav-item {{ request()->routeIs('medecin.*') && request()->routeIs('*.leaves') ? 'active' : '' }}">
                    <a href="{{ route('medecin.leaves') }}">
                        <i class="fas fa-plane"></i>
                        <p>Congés & Absences</p>
                    </a>
                </li>

                <!-- RESSOURCES & SERVICES -->
                <li class="nav-section">
                    <span class="sidebar-mini-icon">
                        <i class="fa fa-ellipsis-h"></i>
                    </span>
                    <h4 class="text-section">Ressources & Services</h4>
                </li>

                <li class="nav-item {{ request()->routeIs('department.*') ? 'active' : '' }}">
                    <a href="{{ route('department.index') }}">
                        <i class="fas fa-building"></i>
                        <p>Départements</p>
                    </a>
                </li>

                <li class="nav-item {{ request()->routeIs('service.*') ? 'active' : '' }}">
                    <a href="{{ route('service.index') }}">
                        <i class="fas fa-cogs"></i>
                        <p>Services médicaux</p>
                    </a>
                </li>

                <li class="nav-item {{ request()->routeIs('chambres.*') ? 'active' : '' }}">
                    <a href="{{ route('chambres.index') }}">
                        <i class="fas fa-bed"></i>
                        <p>Chambres</p>
                    </a>
                </li>

                <li class="nav-item {{ request()->routeIs('test.*') ? 'active' : '' }}">
                    <a href="{{ route('test.index') }}">
                        <i class="fas fa-microscope"></i>
                        <p>Examens & Tests</p>
                    </a>
                </li>

                <li class="nav-item {{ request()->routeIs('package.*') ? 'active' : '' }}">
                    <a href="{{ route('package.index') }}">
                        <i class="fas fa-box-open"></i>
                        <p>Packages de soins</p>
                    </a>
                </li>

                <li class="nav-item {{ request()->routeIs('medicaments.*') ? 'active' : '' }}">
                    <a href="{{ route('medicaments.index') }}">
                        <i class="fas fa-pills"></i>
                        <p>Médicaments</p>
                    </a>
                </li>

                <!-- ASSURANCES -->
                <li class="nav-section">
                    <span class="sidebar-mini-icon">
                        <i class="fa fa-ellipsis-h"></i>
                    </span>
                    <h4 class="text-section">Assurances</h4>
                </li>

                <li class="nav-item {{ request()->routeIs('insurance-companies.*') ? 'active' : '' }}">
                    <a href="{{ route('insurance-companies.index') }}">
                        <i class="fas fa-building"></i>
                        <p>Compagnies</p>
                    </a>
                </li>

                <li class="nav-item {{ request()->routeIs('insurance-coverages.*') ? 'active' : '' }}">
                    <a href="{{ route('insurance-coverages.index') }}">
                        <i class="fas fa-shield-alt"></i>
                        <p>Couvertures</p>
                    </a>
                </li>

                <li class="nav-item {{ request()->routeIs('insurance_patient.*') ? 'active' : '' }}">
                    <a href="{{ route('insurance_patient.index') }}">
                        <i class="fas fa-user-shield"></i>
                        <p>Patients assurés</p>
                    </a>
                </li>

                <li class="nav-item {{ request()->routeIs('insurance.balances*') ? 'active' : '' }}">
                    <a href="{{ route('insurance.balances.index') }}">
                        <i class="fas fa-balance-scale"></i>
                        <p>Soldes</p>
                    </a>
                </li>

                <!-- FACTURATION -->
                <li class="nav-section">
                    <span class="sidebar-mini-icon">
                        <i class="fa fa-ellipsis-h"></i>
                    </span>
                    <h4 class="text-section">Facturation</h4>
                </li>

                <li class="nav-item {{ request()->routeIs('invoice.*') ? 'active' : '' }}">
                    <a href="{{ route('invoice.index') }}">
                        <i class="fas fa-file-invoice-dollar"></i>
                        <p>Factures</p>
                    </a>
                </li>

                <li class="nav-item {{ request()->routeIs('account.*') ? 'active' : '' }}">
                    <a href="{{ route('account.facture') }}">
                        <i class="fas fa-clock"></i>
                        <p>Paiements en attente</p>
                    </a>
                </li>

                <!-- COMMUNICATION -->
                <li class="nav-section">
                    <span class="sidebar-mini-icon">
                        <i class="fa fa-ellipsis-h"></i>
                    </span>
                    <h4 class="text-section">Communication</h4>
                </li>

                <li class="nav-item {{ request()->routeIs('sms.lists') ? 'active' : '' }}">
                    <a href="{{ route('sms.lists') }}">
                        <i class="fas fa-list"></i>
                        <p>Listes de messages</p>
                    </a>
                </li>

                <li class="nav-item {{ request()->routeIs('sms.new') ? 'active' : '' }}">
                    <a href="{{ route('sms.new') }}">
                        <i class="fas fa-sms"></i>
                        <p>Nouveau SMS</p>
                    </a>
                </li>

                <!-- ADMINISTRATION -->
                <li class="nav-section">
                    <span class="sidebar-mini-icon">
                        <i class="fa fa-ellipsis-h"></i>
                    </span>
                    <h4 class="text-section">Administration</h4>
                </li>

                <li class="nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
                    <a href="{{ route('users.index') }}">
                        <i class="fas fa-user-cog"></i>
                        <p>Utilisateurs</p>
                    </a>
                </li>

                <li class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                    <a href="{{ route('reports.index') }}">
                        <i class="fas fa-chart-bar"></i>
                        <p>Rapports & Analytics</p>
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
        padding: 12px 20px;
    }

    .nav-item a:hover {
        background: rgba(255, 149, 0, 0.05);
        color: #ff9500;
        border-left-color: #ff9500;
    }

    /* Sections du menu */
    .nav-section {
        margin-top: 20px;
        padding-top: 15px;
        border-top: 1px solid #f0f0f0;
    }

    .nav-section:first-of-type {
        margin-top: 10px;
        border-top: none;
    }

    .text-section {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #999;
        padding: 0 20px;
        margin-bottom: 8px;
    }

    /* Icônes */
    .nav-item i {
        width: 24px;
        text-align: center;
        margin-right: 12px;
        font-size: 16px;
    }

    /* Scroll personnalisé */
    .sidebar-wrapper.scrollbar-inner {
        scrollbar-width: thin;
        scrollbar-color: rgba(255, 149, 0, 0.3) transparent;
    }

    .sidebar-wrapper.scrollbar-inner::-webkit-scrollbar {
        width: 6px;
    }

    .sidebar-wrapper.scrollbar-inner::-webkit-scrollbar-track {
        background: transparent;
    }

    .sidebar-wrapper.scrollbar-inner::-webkit-scrollbar-thumb {
        background-color: rgba(255, 149, 0, 0.3);
        border-radius: 3px;
    }

    .sidebar-wrapper.scrollbar-inner::-webkit-scrollbar-thumb:hover {
        background-color: rgba(255, 149, 0, 0.5);
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.querySelector('.sidebar-wrapper');
        
        if (!sidebar) return;
        
        // Restaurer la position du scroll
        const savedScrollPosition = sessionStorage.getItem('sidebarScrollPosition');
        if (savedScrollPosition) {
            sidebar.scrollTop = parseInt(savedScrollPosition);
        }
        
        // Sauvegarder avant de quitter
        window.addEventListener('beforeunload', function() {
            sessionStorage.setItem('sidebarScrollPosition', sidebar.scrollTop);
        });
        
        // Sauvegarder lors du clic
        const navLinks = document.querySelectorAll('.nav-item a');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                sessionStorage.setItem('sidebarScrollPosition', sidebar.scrollTop);
            });
        });
        
        // Scroller vers l'élément actif au chargement
        const activeItem = document.querySelector('.nav-item.active');
        if (activeItem) {
            setTimeout(() => {
                const itemPosition = activeItem.offsetTop;
                const sidebarHeight = sidebar.clientHeight;
                const itemHeight = activeItem.clientHeight;
                const scrollPosition = itemPosition - (sidebarHeight / 2) + (itemHeight / 2);
                
                sidebar.scrollTo({
                    top: scrollPosition,
                    behavior: 'smooth'
                });
            }, 100);
        }
    });
</script>