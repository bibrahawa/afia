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
{{--
    Menu latéral (lot Menu) — organisé comme la journée d'une clinique :
    soigner → planifier → patients → laboratoire → encaisser → piloter → paramétrer.

    Piloté par les données : chaque entrée déclare son droit, son module et les écrans
    où elle est active ; une section n'apparaît que si elle a au moins une entrée visible.
    L'établissement est lu UNE fois (avant : une requête par bloc @module, ~10 par page).
--}}
@php
    $u = auth()->user();
    $etab = \App\Support\EtablissementContext::current();
    $mod = fn (string $code) => (bool) $etab?->aModule($code);
    $peut = fn ($p) => $u && (is_array($p) ? $u->canAny($p) : $u->can($p));
    $medecin = $u?->employee?->type === 'Doctor';

    // Catalogue médical : une seule entrée, vers le premier écran autorisé (les onglets font le reste).
    $cibleCatalogue = collect([
        'service.view' => 'service.index', 'department.view' => 'department.index', 'test.view' => 'test.index',
        'medicament.view' => 'medicaments.index', 'package.view' => 'package.index', 'motif_rdv.view' => 'motifs-rdv.index',
    ])->first(fn ($route, $perm) => $peut($perm) && Route::has($route));

    // [libellé, icône, route, visible, écrans actifs (routeIs), options]
    $sections = [
        [null, [
            ['Tableau de bord', 'fa-home', null, (bool) $u, [], ['url' => url('/home'), 'actif' => request()->is('/', 'home')]],
        ]],
        ['Accueil et soins', [
            ['Accueil du jour', 'fa-door-open', 'parcours.accueil.index', $mod('consultation') && $peut('parcours.accueil'), ['parcours.accueil.*']],
            ['Ma file d\'attente', 'fa-user-md', 'parcours.file.index', $mod('consultation') && $medecin && $peut('parcours.file'), ['parcours.file.*', 'parcours.consultation.*']],
            ['Consultations', 'fa-stethoscope', 'consultation.index', $mod('consultation') && $peut('consultation.view'), ['consultation.*']],
            ['Hospitalisations', 'fa-procedures', 'hospitalisations.index', $mod('hospitalisation') && $peut('hospitalisation.view'), ['hospitalisations.*', 'hospitalisation.*']],
            ['Grossesses suivies', 'fa-female', 'parcours.grossesses.index', $mod('consultation') && $peut('parcours.dossier'), ['parcours.grossesses.*']],
            ['Écran de la salle d\'attente', 'fa-tv', 'parcours.salle-attente.index', $mod('consultation') && $peut('parcours.accueil'), ['parcours.salle-attente.*'], ['externe' => true]],
        ]],
        ['Rendez-vous', [
            ['Agenda', 'fa-calendar-alt', 'appointment.index', $mod('rdv') && $peut('appointment.view'), ['appointment.*']],
            ['Mes rendez-vous', 'fa-calendar-check', 'medecin.appointments', $mod('rdv') && $medecin && $peut('medecin.appointments'), ['medecin.appointments*']],
            ['Mes horaires', 'fa-clock', 'medecin.availabilities.index', $mod('rdv') && $medecin && $peut('medecin.availabilities'), ['medecin.availabilities.*']],
            ['Congés et pauses', 'fa-umbrella-beach', 'medecin.leaves.index', $mod('rdv') && $medecin && $peut('medecin.leaves'), ['medecin.leaves.*', 'medecin.breaks.*']],
            ['Affiche et QR code', 'fa-qrcode', 'rdv.affiche', $mod('rdv') && $peut('appointment.view'), ['rdv.affiche']],
        ]],
        ['Patients', [
            ['Patients', 'fa-users', 'patient.index', $peut('patient.view'), ['patient.*', 'parcours.dossier.*', 'consentement.*', 'assurance.droits.*']],
            ['Comptes du portail', 'fa-mobile-alt', 'comptes-patients.index', $peut('patient.edit'), ['comptes-patients.*']],
            ['Patients assurés', 'fa-user-shield', 'insurance_patient.index', $mod('assurance') && $peut('patient_insurance.view'), ['insurance_patient.*']],
        ]],
        ['Laboratoire', [
            ['Laboratoire', 'fa-flask', null, $mod('laboratoire'), ['labo.*'], ['sauf' => ['labo.reseau.*'], 'enfants' => [
                ['Tableau de bord', 'labo.tableau-bord', $peut('labo.tableau_bord'), ['labo.tableau-bord']],
                ['Demandes', 'labo.demandes.index', $peut('labo.demande.view'), ['labo.demandes.*']],
                ['Prélèvements', 'labo.prelevements.index', $peut('labo.prelevement'), ['labo.prelevements.*']],
                ['Réception', 'labo.reception.index', $peut('labo.reception'), ['labo.reception.*']],
                ['Paillasse', 'labo.paillasse.index', $peut('labo.resultat.saisir'), ['labo.paillasse.*']],
                ['Validation', 'labo.validation.index', $peut('labo.validation.technique'), ['labo.validation.*']],
                ['Déclarations (MDO)', 'labo.declarations.index', $peut('labo.validation.biologique'), ['labo.declarations.*']],
                ['Catalogue des analyses', 'labo.catalogue.index', $peut('labo.catalogue.view'), ['labo.catalogue.*']],
                ['Cliniques partenaires', 'labo.partenariats.index', $peut('labo.partenariat.gerer'), ['labo.partenariats.*']],
                ['Créances partenaires', 'labo.creances.index', $peut('labo.partenariat.facturer'), ['labo.creances.*', 'labo.releves.*']],
            ]]],
            ['Laboratoires partenaires', 'fa-project-diagram', null, true, ['labo.reseau.*'], ['enfants' => [
                ['Analyses envoyées', 'labo.reseau.index', $peut('labo.reseau.view'), ['labo.reseau.index', 'labo.reseau.create', 'labo.reseau.show', 'labo.reseau.demandes*']],
                ['Laboratoires proposés', 'labo.reseau.propositions', $peut('labo.reseau.demander'), ['labo.reseau.propositions*']],
                ['Correspondances des examens', 'labo.reseau.correspondances', $peut('labo.reseau.demander'), ['labo.reseau.correspondances*']],
                ['Factures reçues', 'labo.reseau.factures', $peut('labo.reseau.factures'), ['labo.reseau.facture*']],
            ]]],
        ]],
        ['Caisse et assurances', [
            ['Caisse', 'fa-money-bill-wave', 'caisse.index', $peut('account.facture'), ['caisse.*', 'account.*']],
            ['Créances assurance', 'fa-hand-holding-usd', 'assurance.creances.index', $mod('assurance') && $peut('assurance.creances.view'),
                ['assurance.creances.*', 'assurance.reclamations.*', 'assurance.bordereaux.*', 'assurance.reglements.*']],
            ['Contrats et conventions', 'fa-file-contract', 'assurance.contrats.index', $mod('assurance') && $peut('assurance.referentiel.view'),
                ['assurance.contrats.*', 'assurance.adhesions.*', 'assurance.entreprises.*', 'assurance.conventions.*', 'assurance.feuilles-de-soins.*']],
            ['Organismes payeurs', 'fa-building', 'insurance-companies.index', $mod('assurance') && $peut('insurance_company.view'), ['insurance-companies.*']],
        ]],
        ['Pilotage', [
            ['Rapports', 'fa-chart-pie', 'rapports.index', $peut('rapports.view'), ['rapports.*']],
            ['Statistiques médicales', 'fa-chart-line', 'parcours.statistiques.index', $mod('consultation') && $peut('parcours.statistiques'), ['parcours.statistiques.*']],
            ['Journal des SMS', 'fa-comment-dots', 'sms.journal.index', $peut('sms.journal'), ['sms.journal.*']],
        ]],
        ['Paramètres', [
            ['Catalogue médical', 'fa-notes-medical', $cibleCatalogue, (bool) $cibleCatalogue,
                ['department.*', 'service.*', 'test.*', 'medicaments.*', 'package.*', 'motifs-rdv.*', 'catalogue.import*']],
            ['Chambres', 'fa-bed', 'chambres.index', $mod('hospitalisation') && $peut('chambre.view'), ['chambres.*']],
            ['Personnel', 'fa-id-badge', 'employee.index', $peut('employee.view'), ['employee.*', 'employees.*']],
            ['Comptes et accès', 'fa-user-lock', 'users.index', $peut('users.view'), ['users.*', 'user.*']],
        ]],
        ['Plateforme', [
            ['Établissements', 'fa-hospital', 'etablissement.index', $peut('etablissement.view'), ['etablissement.*']],
            ['Modules', 'fa-puzzle-piece', 'module.index', $peut('module.view'), ['module.*']],
        ]],
    ];

    // Filtrage : entrées visibles (route existante), sous-menus avec au moins un enfant visible.
    $estActif = fn (array $motifs, array $sauf = []) => $motifs && request()->routeIs(...$motifs) && ! ($sauf && request()->routeIs(...$sauf));
    $sections = collect($sections)->map(function ($section) use ($estActif) {
        [$titre, $items] = $section;
        $items = collect($items)->map(function ($i) use ($estActif) {
            [$libelle, $icone, $route, $visible, $motifs] = $i;
            $opt = $i[5] ?? [];
            if (! $visible) {
                return null;
            }
            if (isset($opt['enfants'])) {
                $enfants = collect($opt['enfants'])->filter(fn ($e) => $e[2] && Route::has($e[1]))
                    ->map(fn ($e) => ['libelle' => $e[0], 'url' => route($e[1]), 'actif' => $estActif($e[3])])->values();
                if ($enfants->isEmpty()) {
                    return null;
                }
                return ['libelle' => $libelle, 'icone' => $icone, 'enfants' => $enfants, 'id' => 'menu-' . \Illuminate\Support\Str::slug($libelle),
                        'actif' => $estActif($motifs, $opt['sauf'] ?? [])];
            }
            $url = $opt['url'] ?? ($route && Route::has($route) ? route($route) : null);
            if (! $url) {
                return null;
            }
            return ['libelle' => $libelle, 'icone' => $icone, 'url' => $url, 'externe' => $opt['externe'] ?? false,
                    'actif' => $opt['actif'] ?? $estActif($motifs)];
        })->filter()->values();

        return ['titre' => $titre, 'items' => $items];
    })->filter(fn ($s) => $s['items']->isNotEmpty())->values();
@endphp
            <ul class="nav nav-secondary">
                @foreach($sections as $section)
                    @if($section['titre'])
                        <li class="nav-section">
                            <span class="sidebar-mini-icon"><i class="fa fa-ellipsis-h" aria-hidden="true"></i></span>
                            <h4 class="text-section">{{ $section['titre'] }}</h4>
                        </li>
                    @endif
                    @foreach($section['items'] as $item)
                        @if(isset($item['enfants']))
                            <li class="nav-item {{ $item['actif'] ? 'active submenu' : '' }}">
                                <a data-bs-toggle="collapse" href="#{{ $item['id'] }}" class="{{ $item['actif'] ? '' : 'collapsed' }}" aria-expanded="{{ $item['actif'] ? 'true' : 'false' }}">
                                    <i class="fas {{ $item['icone'] }}" aria-hidden="true"></i>
                                    <p>{{ $item['libelle'] }}</p>
                                    <span class="caret"></span>
                                </a>
                                <div class="collapse {{ $item['actif'] ? 'show' : '' }}" id="{{ $item['id'] }}">
                                    <ul class="nav nav-collapse">
                                        @foreach($item['enfants'] as $enfant)
                                            <li class="{{ $enfant['actif'] ? 'active' : '' }}">
                                                <a href="{{ $enfant['url'] }}" @if($enfant['actif']) aria-current="page" @endif><span class="sub-item">{{ $enfant['libelle'] }}</span></a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </li>
                        @else
                            <li class="nav-item {{ $item['actif'] ? 'active' : '' }}">
                                <a href="{{ $item['url'] }}" @if($item['externe']) target="_blank" rel="noopener" @endif @if($item['actif']) aria-current="page" @endif>
                                    <i class="fas {{ $item['icone'] }}" aria-hidden="true"></i>
                                    <p>{{ $item['libelle'] }}</p>
                                    @if($item['externe'])<i class="fas fa-external-link-alt hl-menu-externe" aria-label="(nouvel onglet)"></i>@endif
                                </a>
                            </li>
                        @endif
                    @endforeach
                @endforeach
            </ul>
        </div>
    </div>
</div>
<!-- End Sidebar -->

{{-- Styles du menu : dans public/assets/css/admin-theme.css (section « Menu latéral »).
     L'ancien bloc appliquait l'état actif à TOUS les liens d'un groupe ouvert :
     chaque sous-élément paraissait sélectionné. --}}
<style>
    .sidebar-wrapper.scrollbar-inner { scrollbar-width: thin; scrollbar-color: rgba(15, 118, 110, .3) transparent; }
    .sidebar-wrapper.scrollbar-inner::-webkit-scrollbar { width: 6px; }
    .sidebar-wrapper.scrollbar-inner::-webkit-scrollbar-thumb { background-color: rgba(15, 118, 110, .3); border-radius: 3px; }
    .sidebar .nav-item a .hl-menu-externe { margin-left: auto; font-size: .62rem; opacity: .5; }
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
