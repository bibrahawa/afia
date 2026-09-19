@extends('layouts.backend')

@php
    $icones = ['activite' => 'fa-hospital-user', 'recettes' => 'fa-coins', 'impayes' => 'fa-file-invoice', 'assurance' => 'fa-shield-alt',
               'medecins' => 'fa-user-md', 'diagnostics' => 'fa-notes-medical', 'patients' => 'fa-users', 'grossesses' => 'fa-baby',
               'laboratoire' => 'fa-flask', 'reseau' => 'fa-network-wired'];
    $ordreFamilles = ['Caisse', 'Parcours', 'Patients', 'Assurance', 'Laboratoire'];
    $familles = $catalogue->sortBy(fn ($r, $famille) => array_search($famille, $ordreFamilles, true) === false ? 99 : array_search($famille, $ordreFamilles, true));
    $a = today();
    $raccourcis = [
        "Aujourd'hui" => [$a, $a],
        '7 derniers jours' => [$a->copy()->subDays(6), $a],
        'Ce mois' => [$a->copy()->startOfMonth(), $a],
        'Mois dernier' => [$a->copy()->subMonthNoOverflow()->startOfMonth(), $a->copy()->subMonthNoOverflow()->endOfMonth()],
        'Cette année' => [$a->copy()->startOfYear(), $a],
    ];
@endphp

@section('style')
<style>
    .rp-periode { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 12px; padding: 16px 18px; }
    .rp-periode label { display: block; margin-bottom: 4px; color: var(--hali-encre); font-size: .8rem; font-weight: 650; }
    .rp-periode input { min-height: 40px; }
    .rp-raccourcis { display: flex; flex-wrap: wrap; gap: 6px; margin-left: auto; }
    .rp-famille { margin: 22px 0 10px; color: var(--hali-discret); font-size: .76rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; }
    .rp-grille { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 12px; }
    .rp-carte { display: flex; gap: 14px; padding: 16px; border: 1px solid var(--hali-bordure); border-radius: var(--hali-rayon); background: #fff; color: inherit; text-decoration: none; transition: border-color .15s, box-shadow .15s; }
    .rp-carte:hover { border-color: var(--hali-primaire); box-shadow: 0 0 0 3px var(--hali-primaire-pale); color: inherit; text-decoration: none; }
    .rp-icone { display: grid; place-items: center; width: 44px; height: 44px; flex: none; border-radius: 12px; background: var(--hali-primaire-pale); color: var(--hali-primaire); font-size: 1.1rem; }
    .rp-titre { display: block; color: var(--hali-encre); font-weight: 700; }
    .rp-desc { display: block; margin-top: 2px; color: var(--hali-discret); font-size: .82rem; line-height: 1.4; }
    .rp-anciens { display: flex; flex-wrap: wrap; gap: 8px; }
</style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">
    <header class="hl-entete">
        <div>
            <h1>Rapports</h1>
            <p>Choisissez une période, puis un rapport. Chaque rapport s'imprime et s'exporte vers Excel.</p>
        </div>
    </header>

    <section class="hl-bloc">
        <form class="rp-periode" id="periode" onsubmit="return false">
            <div><label for="debut">Du</label><input type="date" name="debut" id="debut" class="form-control" value="{{ $periode['debut'] }}"></div>
            <div><label for="fin">Au</label><input type="date" name="fin" id="fin" class="form-control" value="{{ $periode['fin'] }}"></div>
            <div class="rp-raccourcis hl-puces" role="group" aria-label="Périodes courantes">
                @foreach($raccourcis as $libelle => [$du, $au])
                    <button type="button" class="hl-puce js-periode {{ $du->toDateString() === $periode['debut'] && $au->toDateString() === $periode['fin'] ? 'est-actif' : '' }}"
                            data-debut="{{ $du->toDateString() }}" data-fin="{{ $au->toDateString() }}">{{ $libelle }}</button>
                @endforeach
            </div>
        </form>
    </section>

    @foreach($familles as $famille => $rapports)
        <h2 class="rp-famille">{{ $famille }}</h2>
        <div class="rp-grille">
            @foreach($rapports as $cle => $rapport)
                <a href="{{ route('rapports.show', $cle) }}" class="rp-carte js-ouvrir">
                    <span class="rp-icone" aria-hidden="true"><i class="fas {{ $icones[$cle] ?? 'fa-chart-bar' }}"></i></span>
                    <span><span class="rp-titre">{{ $rapport['titre'] }}</span><span class="rp-desc">{{ $rapport['description'] }}</span></span>
                </a>
            @endforeach
        </div>
    @endforeach

    @can('report.view')
        <h2 class="rp-famille">États comptables (ancienne présentation)</h2>
        <div class="rp-anciens">
            @if(Route::has('rapports.situation'))<a href="{{ route('rapports.situation') }}" class="hl-bouton js-ancien"><i class="fas fa-list-alt" aria-hidden="true"></i> Situation par acte</a>@endif
            @if(Route::has('rapports.actes.assurance'))<a href="{{ route('rapports.actes.assurance') }}" class="hl-bouton js-ancien"><i class="fas fa-shield-alt" aria-hidden="true"></i> Actes par assurance</a>@endif
            @if(Route::has('rapports.actes.assurance.detail'))<a href="{{ route('rapports.actes.assurance.detail') }}" class="hl-bouton js-ancien"><i class="fas fa-th-list" aria-hidden="true"></i> Actes par assurance, détail</a>@endif
        </div>
    @endcan
</div></div>
@endsection

@section('script')
<script>
(function () {
    var debut = document.getElementById('debut'), fin = document.getElementById('fin');
    document.querySelectorAll('.js-periode').forEach(function (b) {
        b.addEventListener('click', function () {
            debut.value = b.dataset.debut; fin.value = b.dataset.fin;
            document.querySelectorAll('.js-periode').forEach(function (x) { x.classList.toggle('est-actif', x === b); });
        });
    });
    [debut, fin].forEach(function (c) { c.addEventListener('change', function () { document.querySelectorAll('.js-periode').forEach(function (x) { x.classList.remove('est-actif'); }); }); });
    // Chaque lien emporte la période choisie (paramètres du module : debut/fin ; anciens états : from/to).
    function ouvrir(lien, noms) {
        lien.addEventListener('click', function (e) {
            e.preventDefault();
            var p = {}; p[noms[0]] = debut.value; p[noms[1]] = fin.value;
            window.location = lien.getAttribute('href') + '?' + new URLSearchParams(p).toString();
        });
    }
    document.querySelectorAll('.js-ouvrir').forEach(function (l) { ouvrir(l, ['debut', 'fin']); });
    document.querySelectorAll('.js-ancien').forEach(function (l) { ouvrir(l, ['from', 'to']); });
})();
</script>
@endsection
