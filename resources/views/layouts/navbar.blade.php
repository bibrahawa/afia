{{--
    Barre du haut.
    CORRIGÉ : la recherche ne cherchait rien (champ sans formulaire), la cloche affichait
    une fausse notification « Admin, 17 minutes ago », les raccourcis menaient à « # »,
    et tout le monde avait la même photo de profil. Tout est en français et réel.
    data-background-color="orange2" : valeur attendue par KaiAdmin, recolorée par admin-theme.css (ne pas renommer).
--}}
@php
    $utilisateur = auth()->user();
    $etabNav = \App\Support\EtablissementContext::current();
    $nomAffiche = (string) $utilisateur?->name;
    $initialesNav = collect(preg_split('/\s+/u', trim(preg_replace('/^(dr\.?|docteur)\s+/iu', '', $nomAffiche))))
        ->filter()->take(2)->map(fn ($m) => mb_strtoupper(mb_substr($m, 0, 1)))->implode('') ?: 'U';
    $role = $utilisateur?->getRoleNames()->first();
    $libellesRoles = ['super-admin' => 'Super administrateur', 'admin' => 'Administrateur', 'medecin' => 'Médecin', 'infirmier' => 'Infirmier',
                      'secretaire' => 'Secrétariat', 'caissier' => 'Caisse', 'comptable' => 'Comptabilité', 'laborantin' => 'Laboratoire', 'biologiste' => 'Biologiste'];
    $caisse = \Illuminate\Support\Facades\Route::has('caisse.index') ? 'caisse.index' : 'account.facture';
    $raccourcisNav = collect([
        ['parcours.accueil', 'parcours.accueil.index', 'fa-door-open', 'Accueil du jour'],
        ['parcours.file', 'parcours.file.index', 'fa-user-md', 'Ma file'],
        ['appointment.view', 'appointment.index', 'fa-calendar-alt', 'Rendez-vous'],
        ['account.facture', $caisse, 'fa-cash-register', 'Caisse'],
        ['patient.view', 'patient.index', 'fa-users', 'Patients'],
        ['labo.demande.view', 'labo.demandes.index', 'fa-flask', 'Laboratoire'],
        ['rapports.view', 'rapports.index', 'fa-chart-pie', 'Rapports'],
    ])->filter(fn ($r) => $utilisateur?->can($r[0]) && \Illuminate\Support\Facades\Route::has($r[1]));

    // Cloche (lot S4) : seulement pour ceux qui peuvent recevoir une alerte.
    $clocheActive = \Illuminate\Support\Facades\Route::has('cloche') && $utilisateur && collect([
        'labo.validation.technique', 'labo.validation.biologique', 'labo.tableau_bord',
        'parcours.accueil', 'parcours.file', 'assurance.reclamation.gerer',
    ])->contains(fn ($p) => $utilisateur->can($p));
    $alertesCloche = $clocheActive ? app(\App\Services\Cloche\ClocheService::class)->alertes($utilisateur) : collect();
    $clocheDanger = $alertesCloche->where('niveau', 'danger')->count();
@endphp
<nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom hl-nav" data-background-color="orange2">
    <div class="container-fluid">
        @can('patient.view')
            <form class="navbar navbar-header-left navbar-expand-lg navbar-form nav-search p-0 d-none d-lg-flex hl-nav-recherche"
                  method="GET" action="{{ route('patient.index') }}" role="search">
                <i class="fa fa-search" aria-hidden="true"></i>
                <input type="search" name="search" value="{{ request()->routeIs('patient.index') ? request('search') : '' }}"
                       placeholder="Rechercher un patient : nom, téléphone, identifiant…" aria-label="Rechercher un patient" autocomplete="off">
                <kbd title="Raccourci clavier">/</kbd>
            </form>
        @endcan

        <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
            @if($etabNav)
                <li class="nav-item d-none d-xl-flex">
                    <span class="hl-nav-etab" title="Établissement actif"><i class="fas fa-hospital" aria-hidden="true"></i> {{ $etabNav->nom }}</span>
                </li>
            @endif

            @can('patient.view')
                <li class="nav-item topbar-icon dropdown hidden-caret d-flex d-lg-none">
                    <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false" aria-label="Rechercher un patient">
                        <i class="fa fa-search"></i>
                    </a>
                    <div class="dropdown-menu dropdown-search animated fadeIn">
                        <form class="navbar-left navbar-form nav-search" method="GET" action="{{ route('patient.index') }}">
                            <input type="search" name="search" placeholder="Rechercher un patient…" class="form-control" aria-label="Rechercher un patient">
                        </form>
                    </div>
                </li>
            @endcan

            @if($clocheActive)
                <li class="nav-item topbar-icon dropdown hidden-caret" id="hlCloche" data-url="{{ route('cloche') }}">
                    <a class="nav-link hl-cl-bouton" data-bs-toggle="dropdown" href="#" aria-expanded="false" title="À traiter"
                       aria-label="À traiter : {{ $alertesCloche->count() }} alerte{{ $alertesCloche->count() > 1 ? 's' : '' }}">
                        <i class="fa fa-bell"></i>
                        <span class="hl-cl-compte {{ $clocheDanger ? 'est-danger' : '' }}" @if($alertesCloche->isEmpty()) hidden @endif>{{ $alertesCloche->count() }}</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end hl-cl-menu animated fadeIn">
                        <p class="hl-nav-titre">À traiter</p>
                        <div class="hl-cl-liste" id="hlClocheListe">@include('layouts.partials.cloche-liste', ['alertes' => $alertesCloche])</div>
                    </div>
                </li>
            @endif

            @if($raccourcisNav->isNotEmpty())
                <li class="nav-item topbar-icon dropdown hidden-caret">
                    <a class="nav-link" data-bs-toggle="dropdown" href="#" aria-expanded="false" aria-label="Raccourcis" title="Raccourcis">
                        <i class="fas fa-th-large"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end hl-nav-raccourcis animated fadeIn">
                        <p class="hl-nav-titre">Aller à</p>
                        <div class="hl-nav-grille">
                            @foreach($raccourcisNav as [$permission, $route, $icone, $libelle])
                                <a href="{{ route($route) }}"><i class="fas {{ $icone }}" aria-hidden="true"></i><span>{{ $libelle }}</span></a>
                            @endforeach
                        </div>
                    </div>
                </li>
            @endif

            <li class="nav-item topbar-user dropdown hidden-caret">
                <a class="dropdown-toggle profile-pic hl-nav-profil" data-bs-toggle="dropdown" href="#" aria-expanded="false">
                    <span class="hl-nav-avatar" aria-hidden="true">{{ $initialesNav }}</span>
                    <span class="profile-username d-none d-md-inline">
                        <span class="hl-nav-nom">{{ $nomAffiche }}</span>
                        @if($role)<span class="hl-nav-role">{{ $libellesRoles[$role] ?? ucfirst($role) }}</span>@endif
                    </span>
                </a>
                <ul class="dropdown-menu dropdown-user dropdown-menu-end animated fadeIn">
                    <li>
                        <div class="hl-nav-carte">
                            <span class="hl-nav-avatar hl-nav-avatar-grand" aria-hidden="true">{{ $initialesNav }}</span>
                            <div style="min-width:0">
                                <strong>{{ $nomAffiche }}</strong>
                                <span>{{ $utilisateur?->email ?: $utilisateur?->phone }}</span>
                                @if($etabNav)<span>{{ $etabNav->nom }}</span>@endif
                            </div>
                        </div>
                    </li>
                    <li><div class="dropdown-divider"></div></li>
                    @if(\Illuminate\Support\Facades\Route::has('employee.profile'))
                        <li><a class="dropdown-item" href="{{ route('employee.profile') }}"><i class="fas fa-user-circle me-2" aria-hidden="true"></i> Mon profil et mot de passe</a></li>
                    @endif
                    <li>
                        <form action="{{ route('logout') }}" method="POST" class="m-0">
                            @csrf
                            <button type="submit" class="dropdown-item hl-nav-deconnexion"><i class="fas fa-sign-out-alt me-2" aria-hidden="true"></i> Se déconnecter</button>
                        </form>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
</nav>

@once
<style>
    .hl-nav .hl-nav-recherche { position: relative; display: flex; align-items: center; width: min(480px, 42vw); height: 42px; padding: 0 12px !important; border-radius: 10px; background: rgba(255, 255, 255, .14); transition: background-color .15s ease; }
    .hl-nav .hl-nav-recherche:focus-within { background: #fff; }
    .hl-nav .hl-nav-recherche i { color: rgba(255, 255, 255, .8); }
    .hl-nav .hl-nav-recherche:focus-within i { color: var(--hali-discret); }
    .hl-nav .hl-nav-recherche input { flex: 1; min-width: 0; height: 100%; padding: 0 10px; border: 0; outline: 0; background: transparent; color: #fff; font-size: .9rem; }
    .hl-nav .hl-nav-recherche input::placeholder { color: rgba(255, 255, 255, .75); }
    .hl-nav .hl-nav-recherche:focus-within input { color: var(--hali-encre); }
    .hl-nav .hl-nav-recherche:focus-within input::placeholder { color: #9ca3af; }
    .hl-nav .hl-nav-recherche kbd { padding: 1px 7px; border-radius: 5px; background: rgba(255, 255, 255, .2); color: #fff; font-size: .75rem; box-shadow: none; }
    .hl-nav .hl-nav-recherche:focus-within kbd { display: none; }
    .hl-nav-etab { display: inline-flex; align-items: center; gap: 7px; margin-right: 10px; padding: 6px 12px; border-radius: 999px; background: rgba(255, 255, 255, .14); color: #fff; font-size: .82rem; font-weight: 600; white-space: nowrap; }
    .hl-nav-profil { display: flex !important; align-items: center; gap: 10px; }
    .hl-nav-avatar { display: grid; place-items: center; width: 38px; height: 38px; flex: none; border-radius: 11px; background: #fff; color: var(--hali-primaire-fonce); font-size: .85rem; font-weight: 800; letter-spacing: .02em; }
    .hl-nav-avatar-grand { width: 48px; height: 48px; border-radius: 13px; background: var(--hali-primaire-pale); font-size: 1rem; }
    .hl-nav .profile-username { display: grid; line-height: 1.2; text-align: left; }
    .hl-nav-nom { color: #fff; font-size: .88rem; font-weight: 650; }
    .hl-nav-role { color: rgba(255, 255, 255, .75); font-size: .74rem; }
    .hl-nav-carte { display: flex; gap: 12px; align-items: center; padding: 12px 16px 6px; }
    .hl-nav-carte strong { display: block; color: var(--hali-encre); }
    .hl-nav-carte span { display: block; color: var(--hali-discret); font-size: .8rem; overflow: hidden; text-overflow: ellipsis; }
    .hl-nav-deconnexion { color: var(--hali-danger) !important; }
    .hl-nav-raccourcis { width: 300px; padding: 12px !important; border: 0; border-radius: 14px; box-shadow: var(--hali-ombre-forte); }
    .hl-nav-titre { margin: 0 4px 8px; color: var(--hali-discret); font-size: .72rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; }
    .hl-nav-grille { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; }
    .hl-nav-grille a { display: grid; justify-items: center; gap: 6px; padding: 12px 4px; border-radius: 10px; color: var(--hali-encre); font-size: .76rem; font-weight: 600; text-align: center; text-decoration: none; }
    .hl-nav-grille a:hover { background: var(--hali-primaire-pale); color: var(--hali-primaire-fonce); }
    .hl-nav-grille i { display: grid; place-items: center; width: 38px; height: 38px; border-radius: 11px; background: var(--hali-primaire-pale); color: var(--hali-primaire); font-size: 1rem; }

    /* Cloche (lot S4) */
    .hl-cl-bouton { position: relative; }
    .hl-cl-compte { position: absolute; top: 4px; right: 2px; min-width: 18px; height: 18px; padding: 0 5px; border: 2px solid var(--hali-primaire); border-radius: 999px; background: #f59e0b; color: #fff; font-size: .66rem; font-weight: 800; line-height: 14px; text-align: center; }
    .hl-cl-compte.est-danger { background: var(--hali-danger); animation: hl-cl-pouls 2s ease-in-out infinite; }
    @keyframes hl-cl-pouls { 50% { box-shadow: 0 0 0 5px rgba(220, 38, 38, .25); } }
    @media (prefers-reduced-motion: reduce) { .hl-cl-compte.est-danger { animation: none; } }
    .hl-cl-menu { width: 360px; max-width: calc(100vw - 24px); padding: 12px !important; border: 0; border-radius: 14px; box-shadow: var(--hali-ombre-forte); }
    .hl-cl-liste { display: grid; gap: 4px; max-height: 420px; overflow-y: auto; }
    .hl-cl-item { display: flex; align-items: flex-start; gap: 10px; padding: 10px; border-radius: 10px; color: var(--hali-encre); text-decoration: none; }
    a.hl-cl-item:hover { background: #f9fafb; color: var(--hali-encre); text-decoration: none; }
    .hl-cl-icone { display: grid; place-items: center; width: 34px; height: 34px; flex: none; border-radius: 10px; background: var(--hali-alerte-pale); color: #b45309; }
    .hl-cl-item.est-danger .hl-cl-icone { background: var(--hali-danger-pale); color: var(--hali-danger); }
    .hl-cl-texte { flex: 1; min-width: 0; }
    .hl-cl-texte strong { display: block; font-size: .86rem; }
    .hl-cl-item.est-danger .hl-cl-texte strong { color: var(--hali-danger); }
    .hl-cl-texte span { display: block; color: var(--hali-discret); font-size: .78rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .hl-cl-quand { flex: none; color: #9ca3af; font-size: .72rem; }
    .hl-cl-vide { display: grid; justify-items: center; gap: 4px; padding: 18px 10px; text-align: center; }
    .hl-cl-vide i { color: var(--hali-succes); font-size: 1.6rem; }
    .hl-cl-vide strong { color: var(--hali-encre); }
    .hl-cl-vide span { color: var(--hali-discret); font-size: .8rem; }
</style>
<script>
    // Cloche : rafraîchie toutes les 90 s, seulement quand l'onglet est visible.
    (function () {
        var cloche = document.getElementById('hlCloche');
        if (!cloche) return;
        var titre = document.title.replace(/^\(\d+\)\s*/, '');
        function afficher(total, danger, html) {
            var compte = cloche.querySelector('.hl-cl-compte');
            compte.textContent = total; compte.hidden = total === 0;
            compte.classList.toggle('est-danger', danger > 0);
            cloche.querySelector('.hl-cl-bouton').setAttribute('aria-label', 'À traiter : ' + total + ' alerte' + (total > 1 ? 's' : ''));
            if (html !== null) document.getElementById('hlClocheListe').innerHTML = html;
            document.title = (danger > 0 ? '(' + danger + ') ' : '') + titre;
        }
        function rafraichir() {
            if (document.hidden) return;
            fetch(cloche.dataset.url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (d) { if (d) afficher(d.total, d.danger, d.html); })
                .catch(function () {});
        }
        var c = cloche.querySelector('.hl-cl-compte');
        afficher(c.hidden ? 0 : parseInt(c.textContent, 10), c.classList.contains('est-danger') ? 1 : 0, null);
        setInterval(rafraichir, 90000);
        document.addEventListener('visibilitychange', function () { if (!document.hidden) rafraichir(); });
    })();

    // « / » place le curseur dans la recherche de patient (hors champ de saisie).
    document.addEventListener('keydown', function (e) {
        if (e.key !== '/' || /INPUT|TEXTAREA|SELECT/.test(document.activeElement.tagName) || document.activeElement.isContentEditable) return;
        var champ = document.querySelector('.hl-nav-recherche input');
        if (champ) { e.preventDefault(); champ.focus(); }
    });
</script>
@endonce
