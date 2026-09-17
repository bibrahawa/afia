<!-- Sidebar -->
<div class="sidebar" data-background-color="white-color">
    <div class="sidebar-logo">
        <!-- Logo Header -->
        <div class="logo-header" data-background-color="orange2">
            {{-- <img src="{{ asset("assets/img/logo.jpeg") }}"
                alt="Logo Aprosafe"
                class="h-12 w-auto object-contain mx-auto"/> --}}

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
                @can('parcours.accueil')
                    <li class="nav-item {{ request()->routeIs('parcours.accueil.*') ? 'active' : '' }}">
                        <a href="{{ route('parcours.accueil.index') }}">
                            <i class="fas fa-door-open"></i>
                            <p>Accueil du jour</p>
                        </a>
                    </li>
                @endcan

                @can('parcours.dossier')
                    <li class="nav-item {{ request()->routeIs('parcours.grossesses.*') ? 'active' : '' }}">
                        <a href="{{ route('parcours.grossesses.index') }}">
                            <i class="fas fa-baby"></i>
                            <p>Grossesses suivies</p>
                        </a>
                    </li>
                @endcan

                @can('parcours.statistiques')
                    <li class="nav-item {{ request()->routeIs('parcours.statistiques.*') ? 'active' : '' }}">
                        <a href="{{ route('parcours.statistiques.index') }}">
                            <i class="fas fa-chart-line"></i>
                            <p>Statistiques</p>
                        </a>
                    </li>
                @endcan

                @can('parcours.accueil')
                    <li class="nav-item {{ request()->routeIs('parcours.salle-attente.*') ? 'active' : '' }}">
                        <a href="{{ route('parcours.salle-attente.index') }}" target="_blank">
                            <i class="fas fa-tv"></i>
                            <p>Écran salle d'attente</p>
                        </a>
                    </li>
                @endcan

                @can('parcours.file')
                    <li class="nav-item {{ request()->routeIs('parcours.file.*') ? 'active' : '' }}">
                        <a href="{{ route('parcours.file.index') }}">
                            <i class="fas fa-users"></i>
                            <p>Ma file d'attente</p>
                        </a>
                    </li>
                @endcan

                @can('patient.view')
                    <li class="nav-item {{ request()->routeIs('patient.*') ? 'active' : '' }}">
                        <a href="{{ route('patient.index') }}">
                            <i class="fas fa-user"></i>
                            <p>Liste des patients</p>
                        </a>
                    </li>
                @endcan

                @can('medecin.appointments')
                <li class="nav-item {{ request()->routeIs('medecin.*') && request()->routeIs('*.appointments') ? 'active' : '' }}">
                    <a href="{{ route('medecin.appointments') }}">
                        <i class="fas fa-calendar-alt"></i>
                        <p>Rendez-vous</p>
                    </a>
                </li>
                @endcan

                @can('consultation.view')
                    <li class="nav-item {{ request()->routeIs('consultation.*') ? 'active' : '' }}">
                        <a href="{{ route('consultation.index') }}">
                            <i class="fas fa-stethoscope"></i>
                            <p>Consultations</p>
                        </a>
                    </li>
                @endcan

                @can('hospitalisation.view')
                    <li class="nav-item {{ request()->routeIs('hospitalisations.*') ? 'active' : '' }}">
                        <a href="{{ route('hospitalisations.index') }}">
                            <i class="fas fa-hospital"></i>
                            <p>Hospitalisations</p>
                        </a>
                    </li>
                @endcan

                @include('labo.partials.sidebar')

                @can('labo.reseau.view')
                    <li class="nav-item {{ request()->routeIs('labo.reseau.*') ? 'active' : '' }}">
                        <a href="{{ route('labo.reseau.index') }}">
                            <i class="fas fa-vials"></i>
                            <p>Analyses envoyées</p>
                        </a>
                    </li>
                @endcan

                @can('labo.reseau.factures')
                    <li class="nav-item {{ request()->routeIs('labo.reseau.facture*') ? 'active' : '' }}">
                        <a href="{{ route('labo.reseau.factures') }}">
                            <i class="fas fa-file-invoice"></i>
                            <p>Factures laboratoires</p>
                        </a>
                    </li>
                @endcan

                @can('employee.view')
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
                @endcan

                @can('medecin.availabilities')
                    <li class="nav-item {{ request()->routeIs('medecin.*') && request()->routeIs('*.availabilities') ? 'active' : '' }}">
                        <a href="{{ route('medecin.availabilities.index') }}">
                            <i class="fas fa-calendar-check"></i>
                            <p>Disponibilités</p>
                        </a>
                    </li>
                @endcan

                @can('medecin.leaves')
                    <li class="nav-item {{ request()->routeIs('medecin.*') && request()->routeIs('*.leaves') ? 'active' : '' }}">
                        <a href="{{ route('medecin.leaves.index') }}">
                            <i class="fas fa-plane"></i>
                            <p>Congés & Absences</p>
                        </a>
                    </li>
                @endcan

                @can('department.view')
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
                @endcan

                @can('service.view')
                    <li class="nav-item {{ request()->routeIs('service.*') ? 'active' : '' }}">
                        <a href="{{ route('service.index') }}">
                            <i class="fas fa-cogs"></i>
                            <p>Services médicaux</p>
                        </a>
                    </li>
                @endcan

                @can('chambre.view')
                    <li class="nav-item {{ request()->routeIs('chambres.*') ? 'active' : '' }}">
                        <a href="{{ route('chambres.index') }}">
                            <i class="fas fa-bed"></i>
                            <p>Chambres</p>
                        </a>
                    </li>
                @endcan

                @can('test.view')
                    <li class="nav-item {{ request()->routeIs('test.*') ? 'active' : '' }}">
                        <a href="{{ route('test.index') }}">
                            <i class="fas fa-microscope"></i>
                            <p>Examens & Tests</p>
                        </a>
                    </li>
                @endcan

                @can('package.view')
                    <li class="nav-item {{ request()->routeIs('package.*') ? 'active' : '' }}">
                        <a href="{{ route('package.index') }}">
                            <i class="fas fa-box-open"></i>
                            <p>Packages de soins</p>
                        </a>
                    </li>
                @endcan

                @can('medicament.view')
                    <li class="nav-item {{ request()->routeIs('medicaments.*') ? 'active' : '' }}">
                        <a href="{{ route('medicaments.index') }}">
                            <i class="fas fa-pills"></i>
                            <p>Médicaments</p>
                        </a>
                    </li>
                @endcan

                @can('insurance_company.view')
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
                @endcan

                @can('assurance.referentiel.view')
                    <li class="nav-item {{ request()->routeIs('assurance.contrats.*', 'assurance.adhesions.*') ? 'active' : '' }}">
                        <a href="{{ route('assurance.contrats.index') }}">
                            <i class="fas fa-file-contract"></i>
                            <p>Contrats</p>
                        </a>
                    </li>
                    <li class="nav-item {{ request()->routeIs('assurance.entreprises.*') ? 'active' : '' }}">
                        <a href="{{ route('assurance.entreprises.index') }}">
                            <i class="fas fa-industry"></i>
                            <p>Entreprises</p>
                        </a>
                    </li>
                @endcan

                @can('insurance_coverage.view')
                    <li class="nav-item {{ request()->routeIs('insurance-coverages.*', 'assurance.conventions.*') ? 'active' : '' }}">
                        <a href="{{ route('assurance.conventions.index') }}">
                            <i class="fas fa-shield-alt"></i>
                            <p>Conventions</p>
                        </a>
                    </li>
                @endcan

                {{-- @can('insurance_patient.view') --}}
                    <li class="nav-item {{ request()->routeIs('insurance_patient.*') ? 'active' : '' }}">
                        <a href="{{ route('insurance_patient.index') }}">
                            <i class="fas fa-user-shield"></i>
                            <p>Patients assurés</p>
                        </a>
                    </li>
                {{-- @endcan --}}

                @can('assurance.creances.view')
                    <li class="nav-item {{ request()->routeIs('assurance.creances.*', 'assurance.bordereaux.*', 'assurance.reclamations.*', 'assurance.reglements.*') ? 'active' : '' }}">
                        <a href="{{ route('assurance.creances.index') }}">
                            <i class="fas fa-balance-scale"></i>
                            <p>Créances et règlements</p>
                        </a>
                    </li>
                @endcan

                <!-- FACTURATION -->
                @can('invoice.view')
                <li class="nav-section">
                    <span class="sidebar-mini-icon">
                        <i class="fa fa-ellipsis-h"></i>
                    </span>
                    <h4 class="text-section">Facturation</h4>
                </li>

                @endcan

                @can('account.facture')
                    <li class="nav-item {{ request()->routeIs('account.*') ? 'active' : '' }}">
                        <a href="{{ route('account.facture') }}">
                            <i class="fas fa-clock"></i>
                            <p>Paiements en attente</p>
                        </a>
                    </li>
                @endcan

                <!-- COMMUNICATION -->
                {{-- @can('sms.access') --}}
                {{-- <li class="nav-section">
                    <span class="sidebar-mini-icon">
                        <i class="fa fa-ellipsis-h"></i>
                    </span>
                    <h4 class="text-section">Communication</h4>
                </li>

                <li class="nav-item {{ request()->routeIs('sms.lists') ? 'active' : '' }}">
                    <a href="{{ route('sms.lists') }}">
                        <i class="fas fa-list"></i>
                        <p>Sms envoyés</p>
                    </a>
                </li> --}}
                {{-- @endcan --}}

                {{-- @can('sms.new') --}}
                {{-- <li class="nav-item {{ request()->routeIs('sms.new') ? 'active' : '' }}">
                    <a href="{{ route('sms.new') }}">
                        <i class="fas fa-sms"></i>
                        <p>Nouveau SMS</p>
                    </a>
                </li> --}}
                {{-- @endcan --}}

                <!-- ADMINISTRATION -->

                <li class="nav-section">
                    <span class="sidebar-mini-icon"><i class="fa fa-minus"></i></span>
                    <h4 class="text-section">Administration plateforme</h4>
                </li>

                @can('etablissement.view')
                    <li class="nav-item {{ request()->routeIs('etablissement.*') ? 'active' : '' }}">
                        <a href="{{ route('etablissement.index') }}">
                            <i class="fas fa-hospital"></i>
                            <p>Établissements</p>
                        </a>
                    </li>
                @endcan

                @can('module.view')
                    <li class="nav-item {{ request()->routeIs('module.*') ? 'active' : '' }}">
                        <a href="{{ route('module.index') }}">
                            <i class="fas fa-th-large"></i>
                            <p>Modules</p>
                        </a>
                    </li>
                @endcan

                @can('motif_rdv.view')
                    <li class="nav-item {{ request()->routeIs('motifs-rdv.*') ? 'active' : '' }}">
                        <a href="{{ route('motifs-rdv.index') }}">
                            <i class="fas fa-stopwatch"></i>
                            <p>Motifs de rendez-vous</p>
                        </a>
                    </li>
                @endcan

                @can('patient.view')
                    <li class="nav-item {{ request()->routeIs('comptes-patients.*') ? 'active' : '' }}">
                        <a href="{{ route('comptes-patients.index') }}">
                            <i class="fas fa-user-lock"></i>
                            <p>Comptes patients</p>
                        </a>
                    </li>
                @endcan

                @can('users.view')

                <li class="nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
                    <a href="{{ route('users.index') }}">
                        <i class="fas fa-user-cog"></i>
                        <p>Utilisateurs</p>
                    </a>
                </li>
                @endcan

                @can('report.view')
                <li class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                    <a href="{{ route('reports.index') }}">
                        <i class="fas fa-chart-bar"></i>
                        <p>Rapports & Analytics</p>
                    </a>
                </li>
                @endcan
            </ul>
        </div>
    </div>
</div>
<!-- End Sidebar -->

<style>
    /* Styles pour le menu actif — recodés en teal (marque Aprosafe)
       au lieu de l'orange d'origine du template KaiAdmin */
    .nav-item.active a {
        background: linear-gradient(90deg, rgba(8, 127, 107, 0.1) 0%, transparent 100%);
        color: #087f6b !important;
        border-left: 3px solid #087f6b;
        font-weight: 600;
    }

    .nav-item a {
        border-left: 3px solid transparent;
        transition: all 0.3s ease;
        padding: 12px 20px;
    }

    .nav-item a:hover {
        background: rgba(8, 127, 107, 0.05);
        color: #087f6b;
        border-left-color: #087f6b;
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
        scrollbar-color: rgba(8, 127, 107, 0.3) transparent;
    }

    .sidebar-wrapper.scrollbar-inner::-webkit-scrollbar {
        width: 6px;
    }

    .sidebar-wrapper.scrollbar-inner::-webkit-scrollbar-track {
        background: transparent;
    }

    .sidebar-wrapper.scrollbar-inner::-webkit-scrollbar-thumb {
        background-color: rgba(8, 127, 107, 0.3);
        border-radius: 3px;
    }

    .sidebar-wrapper.scrollbar-inner::-webkit-scrollbar-thumb:hover {
        background-color: rgba(8, 127, 107, 0.5);
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