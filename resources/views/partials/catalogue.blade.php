{{--
    Commun aux écrans du catalogue médical : navigation entre les listes et styles partagés.
    @include('partials.catalogue')   — à placer en tête du contenu.
--}}
@php
    $ongletsCatalogue = collect([
        ['department.view', 'department.index', 'fa-sitemap', 'Départements'],
        ['service.view', 'service.index', 'fa-stethoscope', 'Actes'],
        ['test.view', 'test.index', 'fa-vial', 'Examens'],
        ['medicament.view', 'medicaments.index', 'fa-pills', 'Médicaments'],
        ['package.view', 'package.index', 'fa-box-open', 'Forfaits'],
        ['motif_rdv.view', 'motifs-rdv.index', 'fa-calendar-plus', 'Motifs de RDV'],
    ])->filter(fn ($o) => auth()->user()?->can($o[0]) && Route::has($o[1]));
    $prefixeActif = fn ($route) => \Illuminate\Support\Str::before($route, '.');
@endphp
@php($peutImporter = Route::has('catalogue.import') && (auth()->user()?->can('service.create') || auth()->user()?->can('test.create') || auth()->user()?->can('medicament.create')))
@if($ongletsCatalogue->count() > 1 || $peutImporter)
    <nav class="cat-onglets" aria-label="Catalogue médical">
        @foreach($ongletsCatalogue as [$permission, $route, $icone, $libelle])
            <a href="{{ route($route) }}" class="{{ request()->routeIs($prefixeActif($route) . '.*') ? 'est-actif' : '' }}"><i class="fas {{ $icone }}" aria-hidden="true"></i> {{ $libelle }}</a>
        @endforeach
        @if($peutImporter)
            <a href="{{ route('catalogue.import') }}" class="{{ request()->routeIs('catalogue.import*') ? 'est-actif' : '' }}"><i class="fas fa-file-import" aria-hidden="true"></i> Importer</a>
        @endif
    </nav>
@endif

@once
<style>
    .cat-onglets { display: flex; gap: 4px; overflow-x: auto; margin-bottom: 18px; padding: 4px; border-radius: 12px; background: #f3f4f6; width: fit-content; max-width: 100%; }
    .cat-onglets a { display: inline-flex; align-items: center; gap: 7px; flex: none; min-height: 38px; padding: 0 14px; border-radius: 9px; color: var(--hali-texte); font-size: .86rem; font-weight: 650; text-decoration: none; white-space: nowrap; }
    .cat-onglets a i { color: #9ca3af; }
    .cat-onglets a:hover { color: var(--hali-primaire-fonce); }
    .cat-onglets a.est-actif { background: #fff; color: var(--hali-primaire-fonce); box-shadow: 0 1px 3px rgba(0, 0, 0, .08); }
    .cat-onglets a.est-actif i { color: var(--hali-primaire); }

    .cat-outils { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; padding: 14px 18px; border-bottom: 1px solid var(--hali-bordure); }
    .cat-recherche { position: relative; flex: 1 1 260px; }
    .cat-recherche i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #9ca3af; }
    .cat-recherche input { width: 100%; min-height: 42px; padding-left: 40px; }
    .cat-outils select { width: auto; min-width: 190px; min-height: 42px; }
    .cat-table { width: 100%; border-collapse: collapse; font-size: .88rem; }
    .cat-table th { padding: 10px 16px; background: #fafbfc; border-bottom: 1px solid var(--hali-bordure); color: var(--hali-discret); font-size: .78rem; font-weight: 600; text-align: left; white-space: nowrap; }
    .cat-table td { padding: 12px 16px; border-top: 1px solid #f3f4f6; vertical-align: middle; }
    .cat-table tbody tr:first-child td { border-top: 0; }
    .cat-table tbody tr:hover > td { background: var(--hali-primaire-pale); }
    .cat-table .cat-n { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
    .cat-nom { color: var(--hali-encre); font-weight: 650; }
    .cat-sous { display: block; color: var(--hali-discret); font-size: .78rem; font-weight: 500; }
    .cat-prix { color: var(--hali-encre); font-weight: 700; }
    .cat-prix small { color: var(--hali-discret); font-weight: 600; }
    .cat-zero { color: var(--hali-danger); font-weight: 700; }
    .cat-actions { text-align: right; white-space: nowrap; }
    .cat-actions form { display: inline; margin: 0; }
    .cat-icone { display: inline-grid; place-items: center; width: 34px; height: 34px; margin-left: 4px; border: 1px solid var(--hali-bordure); border-radius: 8px; background: #fff; color: var(--hali-texte); cursor: pointer; text-decoration: none; }
    .cat-icone:hover { border-color: var(--hali-primaire); color: var(--hali-primaire-fonce); background: var(--hali-primaire-pale); text-decoration: none; }
    .cat-icone.est-risque:hover { border-color: var(--hali-danger); color: var(--hali-danger); background: var(--hali-danger-pale); }
    .cat-etiquette { display: inline-block; padding: 2px 9px; border-radius: 999px; background: #f3f4f6; color: var(--hali-texte); font-size: .74rem; font-weight: 600; }

    .cat-modal .modal-content { border: 0; border-radius: 14px; }
    .cat-modal .modal-header { padding: 18px 22px 6px; border: 0; }
    .cat-modal .modal-title { color: var(--hali-encre); font-weight: 700; }
    .cat-modal .modal-body { display: grid; gap: 14px; padding: 8px 22px 14px; }
    .cat-modal label.cat-l { display: block; margin-bottom: 5px; color: var(--hali-encre); font-size: .83rem; font-weight: 650; }
    .cat-modal .modal-footer { padding: 10px 22px 18px; border: 0; }
    .cat-deux { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .cat-aide { margin: 4px 0 0; color: var(--hali-discret); font-size: .78rem; }
    .cat-montant { position: relative; }
    .cat-montant input { padding-right: 56px; font-weight: 700; font-variant-numeric: tabular-nums; }
    .cat-montant span { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: var(--hali-discret); font-size: .82rem; font-weight: 600; }
    .cat-danger { background: var(--hali-danger); border-color: var(--hali-danger); color: #fff; }
    .cat-danger:hover { background: #991b1b; color: #fff; }
    .cat-table tr[data-masque="1"] > td { background: #fafafa; }
    .cat-table tr[data-masque="1"] .cat-nom { color: var(--hali-discret); text-decoration: line-through; text-decoration-color: #d1d5db; }
    .cat-masques { display: inline-flex; align-items: center; gap: 8px; margin: 0; color: var(--hali-texte); font-size: .84rem; font-weight: 600; cursor: pointer; white-space: nowrap; }
    @media (max-width: 767.98px) { .cat-deux { grid-template-columns: 1fr; } }
</style>
<script>
    // Filtre instantané des listes du catalogue (champ .js-cat-filtre, lignes [data-recherche]).
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.js-cat-filtre').forEach(function (champ) {
            var cible = document.querySelector(champ.dataset.cible);
            var vide = document.querySelector(champ.dataset.vide);
            var norm = function (s) { return (s || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase(); };
            var masques = document.getElementById('catMasques');
            var appliquer = function () {
                var t = norm(champ.value.trim()), filtre = document.querySelector(champ.dataset.filtre || '#_'), f = filtre ? filtre.value : '', n = 0;
                var voirMasques = masques ? masques.checked : true;
                cible.querySelectorAll('[data-recherche]').forEach(function (l) {
                    var ok = (!t || norm(l.dataset.recherche).indexOf(t) !== -1) && (!f || l.dataset.groupe === f) && (voirMasques || l.dataset.masque !== '1');
                    l.hidden = !ok; if (ok) n++;
                });
                if (vide) vide.hidden = n > 0;
            };
            champ.addEventListener('input', appliquer);
            var filtre = document.querySelector(champ.dataset.filtre || '#_');
            if (filtre) filtre.addEventListener('change', appliquer);
            if (masques) masques.addEventListener('change', appliquer);
            appliquer();   // les éléments masqués sont cachés au départ
        });
    });
</script>
@endonce
