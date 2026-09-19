@extends('layouts.backend')

@php
    // Calculé une seule fois par suivi : le calendrier CPN interroge la base.
    $suivis = $grossesses->map(function ($g) {
        [$sa, $j] = $g->terme() ?? [0, 0];
        $prochain = $g->prochainContact();
        $joursAvantDpa = (int) today()->diffInDays($g->dpa, false);

        return [
            'g' => $g,
            'sa' => $sa,
            'avancement' => min(100, round(($sa * 7 + $j) * 100 / 280)),
            'prochain' => $prochain,
            'cpn_a_programmer' => $prochain && $prochain['statut'] === 'a_programmer',
            'jours_avant_dpa' => $joursAvantDpa,
            'depasse' => $joursAvantDpa < 0,
            'proche' => $joursAvantDpa >= 0 && $joursAvantDpa <= 28,
        ];
    });

    $nbCpn = $suivis->where('cpn_a_programmer', true)->count();
    $nbProches = $suivis->where('proche', true)->count();
    $nbDepasses = $suivis->where('depasse', true)->count();

    $trimestre = fn (int $sa) => $sa < 14 ? '1er trim.' : ($sa < 28 ? '2e trim.' : '3e trim.');
@endphp

@section('content')
<style>
    .gs-liste { margin: 0; padding: 0; list-style: none; }
    .gs-ligne {
        position: relative; display: grid; align-items: center; gap: 16px;
        grid-template-columns: minmax(180px, 1.4fr) minmax(150px, 1fr) minmax(140px, .9fr) minmax(170px, 1fr) minmax(130px, .8fr) 24px;
        padding: 14px 18px; border-bottom: 1px solid #f3f4f6; transition: background-color .12s ease;
    }
    .gs-ligne:last-child { border-bottom: 0; }
    .gs-ligne:hover { background: var(--hali-primaire-pale); }
    .gs-ligne.est-depasse { box-shadow: inset 3px 0 0 var(--hali-danger); }
    .gs-ligne.est-proche { box-shadow: inset 3px 0 0 #be185d; }

    .gs-nom { display: flex; align-items: center; gap: 12px; min-width: 0; }
    .gs-avatar { width: 38px; height: 38px; flex: 0 0 38px; display: grid; place-items: center; border-radius: 10px; background: #fdf2f8; color: #9d174d; font-size: .82rem; font-weight: 700; }
    .gs-nom a { color: var(--hali-encre); font-weight: 650; text-decoration: none; }
    .gs-nom a::after { content: ""; position: absolute; inset: 0; }
    .gs-nom a:focus-visible { outline: none; }
    .gs-nom a:focus-visible::after { outline: 2px solid var(--hali-primaire); outline-offset: -2px; }
    .gs-sous { display: block; color: var(--hali-discret); font-size: .8rem; }

    .gs-terme b { color: var(--hali-encre); font-size: 1rem; font-variant-numeric: tabular-nums; }
    .gs-terme .hl-jauge { margin-top: 6px; --hl-jauge: #be185d; --hl-jauge-fond: #fce7f3; }
    .gs-valeur { color: var(--hali-encre); font-weight: 600; font-variant-numeric: tabular-nums; }
    .gs-depasse { color: var(--hali-danger); font-weight: 600; }
    .gs-chevron { color: #9ca3af; font-size: .8rem; text-align: right; }
    .gs-ligne:hover .gs-chevron { color: var(--hali-primaire); }
    .gs-etiquette { display: none; }

    .gs-outils { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; padding: 14px 18px; border-bottom: 1px solid var(--hali-bordure); }
    .gs-recherche { position: relative; margin-left: auto; min-width: 220px; }
    .gs-recherche i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: .8rem; }
    .gs-recherche input { padding-left: 32px; min-height: 36px; }

    .gs-entetes { display: grid; gap: 16px; grid-template-columns: minmax(180px, 1.4fr) minmax(150px, 1fr) minmax(140px, .9fr) minmax(170px, 1fr) minmax(130px, .8fr) 24px; padding: 10px 18px; border-bottom: 1px solid var(--hali-bordure); background: #fafbfc; color: var(--hali-discret); font-size: .78rem; font-weight: 600; }

    @media (max-width: 991.98px) {
        .gs-entetes { display: none; }
        .gs-ligne { grid-template-columns: 1fr 1fr; gap: 10px 16px; }
        .gs-nom { grid-column: 1 / -1; }
        .gs-chevron { display: none; }
        .gs-etiquette { display: block; color: var(--hali-discret); font-size: .75rem; }
        .gs-recherche { margin-left: 0; width: 100%; }
    }
</style>

<div class="container"><div class="page-inner hl">

    <header class="hl-entete">
        <div>
            <h1>Grossesses suivies</h1>
            <p>Les patientes suivies dans la clinique, de l'accouchement le plus proche au plus lointain.</p>
        </div>
    </header>

    <div class="hl-kpis">
        <div class="hl-bloc hl-kpi">
            <span class="hl-kpi-libelle">Suivis en cours</span>
            <span class="hl-kpi-valeur">{{ $suivis->count() }}</span>
        </div>
        <div class="hl-bloc hl-kpi {{ $nbCpn ? 'est-alerte' : '' }}">
            <span class="hl-kpi-libelle">CPN à programmer</span>
            <span class="hl-kpi-valeur">{{ $nbCpn }}</span>
            <span class="hl-kpi-detail">{{ $nbCpn ? 'Date du contact passée, sans consultation' : 'Toutes les CPN sont à jour' }}</span>
        </div>
        <div class="hl-bloc hl-kpi">
            <span class="hl-kpi-libelle">Accouchement dans les 4 semaines</span>
            <span class="hl-kpi-valeur">{{ $nbProches }}</span>
        </div>
        <div class="hl-bloc hl-kpi {{ $nbDepasses ? 'est-danger' : '' }}">
            <span class="hl-kpi-libelle">Terme dépassé</span>
            <span class="hl-kpi-valeur">{{ $nbDepasses }}</span>
            <span class="hl-kpi-detail">{{ $nbDepasses ? 'À revoir en priorité' : 'Aucune patiente' }}</span>
        </div>
    </div>

    <section class="hl-bloc">
        @if($suivis->isEmpty())
            <div class="hl-vide">
                <i class="fas fa-baby" aria-hidden="true"></i>
                Aucune grossesse suivie pour le moment.<br>
                Un suivi s'ouvre depuis le dossier d'une patiente, avec la date de ses dernières règles.
            </div>
        @else
            <div class="gs-outils">
                <div class="hl-puces" role="group" aria-label="Filtrer les suivis">
                    <button type="button" class="hl-puce" data-filtre="tous" aria-pressed="true">Toutes <b>{{ $suivis->count() }}</b></button>
                    <button type="button" class="hl-puce" data-filtre="cpn" aria-pressed="false">CPN à programmer <b>{{ $nbCpn }}</b></button>
                    <button type="button" class="hl-puce" data-filtre="proche" aria-pressed="false">Accouchement proche <b>{{ $nbProches }}</b></button>
                    <button type="button" class="hl-puce" data-filtre="depasse" aria-pressed="false">Terme dépassé <b>{{ $nbDepasses }}</b></button>
                </div>
                <label class="gs-recherche mb-0">
                    <span class="sr-only visually-hidden">Rechercher une patiente</span>
                    <i class="fas fa-search" aria-hidden="true"></i>
                    <input type="search" id="gs-recherche" class="form-control" placeholder="Rechercher une patiente" autocomplete="off">
                </label>
            </div>

            <div class="gs-entetes" aria-hidden="true">
                <span>Patiente</span><span>Terme</span><span>Accouchement prévu</span><span>Prochaine CPN</span><span>Médecin</span><span></span>
            </div>

            <ol class="gs-liste" id="gs-liste">
                @foreach($suivis as $s)
                    @php
                        $g = $s['g'];
                        $noms = preg_split('/\s+/', trim((string) $g->patient->full_name));
                        $initiales = mb_strtoupper(mb_substr($noms[0] ?? '', 0, 1) . mb_substr(end($noms) ?: '', 0, 1));
                        $j = abs($s['jours_avant_dpa']);
                    @endphp
                    <li class="gs-ligne {{ $s['depasse'] ? 'est-depasse' : ($s['proche'] ? 'est-proche' : '') }}"
                        data-nom="{{ mb_strtolower($g->patient->full_name) }}"
                        data-cpn="{{ $s['cpn_a_programmer'] ? 1 : 0 }}"
                        data-proche="{{ $s['proche'] ? 1 : 0 }}"
                        data-depasse="{{ $s['depasse'] ? 1 : 0 }}">

                        <div class="gs-nom">
                            <span class="gs-avatar" aria-hidden="true">{{ $initiales }}</span>
                            <div>
                                <a href="{{ route('parcours.grossesses.show', $g) }}">{{ $g->patient->full_name }}</a>
                                <span class="gs-sous">
                                    {{ $g->patient->age !== null ? $g->patient->age . ' ans' : '' }}@if($g->gestite !== null){{ $g->patient->age !== null ? ' · ' : '' }}G{{ $g->gestite }}P{{ $g->parite ?? 0 }}@endif
                                </span>
                            </div>
                        </div>

                        <div class="gs-terme">
                            <span class="gs-etiquette">Terme</span>
                            <b>{{ $g->termeLisible() }}</b> <span class="gs-sous d-inline">{{ $trimestre($s['sa']) }}</span>
                            <div class="hl-jauge" aria-hidden="true"><span style="width: {{ $s['avancement'] }}%"></span></div>
                        </div>

                        <div>
                            <span class="gs-etiquette">Accouchement prévu</span>
                            <span class="gs-valeur">{{ $g->dpa->format('d/m/Y') }}</span>
                            @if($s['depasse'])
                                <span class="gs-sous gs-depasse">Dépassé de {{ $j }} jour{{ $j > 1 ? 's' : '' }}</span>
                            @else
                                <span class="gs-sous">{{ $j === 0 ? "Aujourd'hui" : 'Dans ' . $j . ' jour' . ($j > 1 ? 's' : '') }}</span>
                            @endif
                        </div>

                        <div>
                            <span class="gs-etiquette">Prochaine CPN</span>
                            @if($s['prochain'])
                                <span class="gs-valeur">{{ $s['prochain']['semaines'] }} SA · {{ $s['prochain']['date_cible']->format('d/m/Y') }}</span>
                                <span class="d-block mt-1">
                                    @if($s['cpn_a_programmer'])
                                        <span class="hl-statut hl-s-alerte">À programmer</span>
                                    @else
                                        <span class="hl-statut hl-s-neutre">À venir</span>
                                    @endif
                                </span>
                            @else
                                <span class="gs-sous">Calendrier terminé</span>
                            @endif
                        </div>

                        <div>
                            <span class="gs-etiquette">Médecin</span>
                            <span class="gs-sous" style="color: var(--hali-texte)">{{ $g->medecin ? 'Dr ' . preg_replace('/^Dr\.?\s+/i', '', $g->medecin->full_name) : '—' }}</span>
                        </div>

                        <span class="gs-chevron" aria-hidden="true"><i class="fas fa-chevron-right"></i></span>
                    </li>
                @endforeach
            </ol>

            <div class="hl-vide" id="gs-aucun" hidden>Aucune patiente ne correspond.</div>
        @endif
    </section>
</div></div>

<script>
(function () {
    var liste = document.getElementById('gs-liste');
    if (!liste) return;
    var lignes = Array.prototype.slice.call(liste.children);
    var puces = document.querySelectorAll('[data-filtre]');
    var recherche = document.getElementById('gs-recherche');
    var aucun = document.getElementById('gs-aucun');
    var filtre = 'tous';

    // Tout est déjà chargé : on filtre sans recharger la page.
    function appliquer() {
        var texte = (recherche.value || '').trim().toLowerCase();
        var visibles = 0;
        lignes.forEach(function (ligne) {
            var okFiltre = filtre === 'tous' || ligne.dataset[filtre] === '1';
            var okTexte = !texte || ligne.dataset.nom.indexOf(texte) !== -1;
            ligne.hidden = !(okFiltre && okTexte);
            if (!ligne.hidden) visibles++;
        });
        aucun.hidden = visibles > 0;
    }

    puces.forEach(function (puce) {
        puce.addEventListener('click', function () {
            filtre = puce.dataset.filtre;
            puces.forEach(function (p) { p.setAttribute('aria-pressed', String(p === puce)); });
            appliquer();
        });
    });
    recherche.addEventListener('input', appliquer);
})();
</script>
@endsection
