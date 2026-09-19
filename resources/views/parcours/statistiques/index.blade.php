@extends('layouts.backend')

@php
    $nb = fn ($v) => $v === null ? '—' : number_format((float) $v, 0, ',', ' ');
    $pluriel = fn (int $n, string $mot, ?string $motPluriel = null) => $n . ' ' . ($n > 1 ? ($motPluriel ?? $mot . 's') : $mot);

    // 356 min ne se lit pas : 5 h 56 si.
    $duree = function ($minutes) {
        if ($minutes === null) return '—';
        $minutes = (int) $minutes;
        return $minutes < 60 ? $minutes . ' min' : intdiv($minutes, 60) . ' h ' . str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT);
    };

    $mois = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    $dateLongue = fn (\Carbon\Carbon $d) => ($d->day === 1 ? '1er' : $d->day) . ' ' . $mois[$d->month] . ' ' . $d->year;
    $debut = $stats['periode']['debut'];
    $fin = $stats['periode']['fin'];
    $libellePeriode = $debut->isSameDay($fin) ? 'Le ' . $dateLongue($debut) : 'Du ' . $dateLongue($debut) . ' au ' . $dateLongue($fin);

    $medecinChoisi = $filtres['medecin_id'] ? $medecins->firstWhere('id', (int) $filtres['medecin_id']) : null;
    $sansDr = fn ($nom) => preg_replace('/^Dr\.?\s+/i', '', (string) $nom);

    // Raccourcis de période : le cas courant en un clic.
    $raccourcis = [
        "Aujourd'hui"   => [today(), today()],
        '7 derniers jours' => [today()->subDays(6), today()],
        'Ce mois'       => [today()->startOfMonth(), today()],
        'Mois dernier'  => [today()->subMonthNoOverflow()->startOfMonth(), today()->subMonthNoOverflow()->endOfMonth()],
        '3 derniers mois' => [today()->subMonthsNoOverflow(3)->addDay(), today()],
    ];

    $visites = max(1, (int) $stats['visites']);
    $terminees = (int) $stats['terminees'];
    $enCours = (int) $stats['en_cours'];
    $parties = (int) $stats['parties'];

    $recette = (float) $stats['recette_actes'];
    $partPatient = (float) ($stats['recette_parts']['patient'] ?? 0);
    $partAssurance = (float) ($stats['recette_parts']['assurance'] ?? 0);
    $totalParts = max(1, $partPatient + $partAssurance);

    $attente = $stats['attente_moyenne'];
    $tonAttente = $attente === null ? '' : ($attente > 120 ? 'est-danger' : ($attente > 45 ? 'est-alerte' : ''));

    $maxMedecin = max(1, (int) collect($stats['par_medecin'])->max('visites'));
    $maxMotif = max(1, (int) collect($stats['motifs'])->max());
    $maxDiagnostic = max(1, (int) collect($stats['diagnostics'])->max());
@endphp

@section('content')
<style>
    .st-filtres { display: grid; gap: 12px; padding: 16px 18px; margin-bottom: 16px; }
    .st-ligne { display: flex; flex-wrap: wrap; align-items: center; gap: 10px 16px; }
    .st-medecin { display: flex; align-items: center; gap: 8px; margin-left: auto; }
    .st-medecin label { margin: 0; color: var(--hali-discret); font-weight: 600; font-size: .83rem; white-space: nowrap; }
    .st-medecin select { min-width: 200px; min-height: 36px; }
    .st-dates summary { color: var(--hali-primaire); font-weight: 600; font-size: .85rem; list-style: none; }
    .st-dates summary::-webkit-details-marker { display: none; }
    .st-dates-champs { display: flex; flex-wrap: wrap; align-items: end; gap: 8px; margin-top: 10px; }
    .st-dates-champs label { display: grid; gap: 4px; margin: 0; font-size: .78rem; }
    .st-dates-champs input { min-height: 36px; }

    .st-grille { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; align-items: start; }
    .st-kpi-bas { display: grid; gap: 8px; margin-top: 6px; }

    @media (max-width: 1199.98px) { .st-grille { grid-template-columns: 1fr 1fr; } }
    @media (max-width: 767.98px) {
        .st-grille { grid-template-columns: 1fr; }
        .st-medecin { margin-left: 0; width: 100%; }
        .st-medecin select { flex: 1; min-width: 0; }
    }
</style>

<div class="container"><div class="page-inner hl">

    <header class="hl-entete">
        <div>
            <h1>Statistiques de consultation</h1>
            <p>{{ $libellePeriode }} · {{ $medecinChoisi ? 'Dr ' . $sansDr($medecinChoisi->full_name) : 'tous les médecins' }}</p>
        </div>
    </header>

    {{-- ============================================ Filtres --}}
    <form method="GET" class="hl-bloc st-filtres" id="st-filtres">
        <div class="st-ligne">
            <div class="hl-puces" role="group" aria-label="Période">
                @foreach($raccourcis as $libelle => $periode)
                    @php
                        [$du, $au] = $periode;
                        $actif = $filtres['depuis'] === $du->toDateString() && $filtres['jusqu_a'] === $au->toDateString();
                    @endphp
                    <a class="hl-puce {{ $actif ? 'est-actif' : '' }}"
                       href="{{ request()->fullUrlWithQuery(['depuis' => $du->toDateString(), 'jusqu_a' => $au->toDateString()]) }}">{{ $libelle }}</a>
                @endforeach
            </div>

            <div class="st-medecin">
                <label for="st-medecin">Médecin</label>
                <select name="medecin_id" id="st-medecin" class="form-control">
                    <option value="">Tous les médecins</option>
                    @foreach($medecins as $m)
                        <option value="{{ $m->id }}" @selected((int) $filtres['medecin_id'] === $m->id)>Dr {{ $sansDr($m->full_name) }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <details class="st-dates">
            <summary>Choisir des dates précises</summary>
            <div class="st-dates-champs">
                <label>Du<input type="date" name="depuis" class="form-control" value="{{ $filtres['depuis'] }}"></label>
                <label>Au<input type="date" name="jusqu_a" class="form-control" value="{{ $filtres['jusqu_a'] }}"></label>
                <button type="submit" class="hl-bouton hl-bouton-plein">Afficher</button>
            </div>
        </details>
    </form>

    {{-- ============================================ Chiffres clés --}}
    <div class="hl-kpis">
        <div class="hl-bloc hl-kpi">
            <span class="hl-kpi-libelle">Patients reçus</span>
            <span class="hl-kpi-valeur">{{ $stats['visites'] }}</span>
            <div class="st-kpi-bas">
                <div class="hl-jauge-empilee" aria-hidden="true">
                    @if($terminees)<span style="width: {{ $terminees * 100 / $visites }}%; background: var(--hali-primaire)"></span>@endif
                    @if($enCours)<span style="width: {{ $enCours * 100 / $visites }}%; background: #5eead4"></span>@endif
                    @if($parties)<span style="width: {{ $parties * 100 / $visites }}%; background: #fca5a5"></span>@endif
                </div>
                <div class="hl-legende">
                    <span><i style="background: var(--hali-primaire)"></i>{{ $terminees }} vu{{ $terminees > 1 ? 's' : '' }}</span>
                    <span><i style="background: #5eead4"></i>{{ $enCours }} en cours</span>
                    <span><i style="background: #fca5a5"></i>{{ $parties }} parti{{ $parties > 1 ? 's' : '' }} sans consulter</span>
                </div>
            </div>
        </div>

        <div class="hl-bloc hl-kpi {{ $tonAttente }}">
            <span class="hl-kpi-libelle">Attente moyenne</span>
            <span class="hl-kpi-valeur">{{ $duree($attente) }}</span>
            <span class="hl-kpi-detail">
                Médiane {{ $duree($stats['attente_mediane']) }} · la plus longue {{ $duree($stats['attente_max']) }}<br>
                De l'arrivée à l'appel par le médecin.
            </span>
        </div>

        <div class="hl-bloc hl-kpi {{ $stats['rdv_absents'] > 0 ? 'est-alerte' : '' }}">
            <span class="hl-kpi-libelle">Rendez-vous non honorés</span>
            <span class="hl-kpi-valeur">
                {{ $stats['rdv_absents'] }}
                @if($stats['taux_absence'] !== null)<small>{{ str_replace('.', ',', (string) $stats['taux_absence']) }} %</small>@endif
            </span>
            <span class="hl-kpi-detail">{{ $stats['taux_absence'] !== null ? 'des rendez-vous passés sur la période' : 'Aucun rendez-vous passé sur la période' }}</span>
        </div>

        <div class="hl-bloc hl-kpi">
            <span class="hl-kpi-libelle">Actes de consultation facturés</span>
            <span class="hl-kpi-valeur">{{ $nb($recette) }} <small>GNF</small></span>
            @if($partPatient + $partAssurance > 0)
                <div class="st-kpi-bas">
                    <div class="hl-jauge-empilee" aria-hidden="true">
                        @if($partPatient)<span style="width: {{ $partPatient * 100 / $totalParts }}%; background: var(--hali-primaire)"></span>@endif
                        @if($partAssurance)<span style="width: {{ $partAssurance * 100 / $totalParts }}%; background: #93c5fd"></span>@endif
                    </div>
                    <div class="hl-legende">
                        <span><i style="background: var(--hali-primaire)"></i>Patients {{ $nb($partPatient) }}</span>
                        <span><i style="background: #93c5fd"></i>Assurances {{ $nb($partAssurance) }}</span>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @if($enCours > 0 && $fin->lt(today()))
        <p class="hl-note hl-note-alerte mb-3">
            <i class="fas fa-exclamation-circle mt-1" aria-hidden="true"></i>
            <span>{{ $pluriel($enCours, 'visite est restée ouverte', 'visites sont restées ouvertes') }} sur cette période. Pensez à les clôturer depuis l'accueil : elles faussent le temps d'attente et le nombre de patients vus.</span>
        </p>
    @endif

    {{-- ============================================ Classements --}}
    <div class="st-grille">
        <section class="hl-bloc">
            <h2 class="hl-bloc-titre">Activité par médecin</h2>
            @if(collect($stats['par_medecin'])->isEmpty())
                <div class="hl-vide">Aucune visite sur la période.</div>
            @else
                <ul class="hl-classement">
                    @foreach($stats['par_medecin'] as $ligne)
                        <li>
                            <div class="hl-classement-haut">
                                <span>Dr {{ $sansDr($ligne['medecin']?->full_name ?? '—') }}</span>
                                <b>{{ $ligne['terminees'] }} <small>vus sur {{ $ligne['visites'] }}</small></b>
                            </div>
                            <div class="hl-jauge" style="--hl-jauge-fond: #e6f4f1" aria-hidden="true">
                                <span style="width: {{ $ligne['visites'] * 100 / $maxMedecin }}%; background: #99f6e4"></span>
                                <span style="width: {{ $ligne['terminees'] * 100 / $maxMedecin }}%"></span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="hl-bloc">
            <h2 class="hl-bloc-titre">Motifs les plus fréquents</h2>
            @if(collect($stats['motifs'])->isEmpty())
                <div class="hl-vide">Aucun motif sur la période.</div>
            @else
                <ul class="hl-classement">
                    @foreach($stats['motifs'] as $motif => $total)
                        <li>
                            <div class="hl-classement-haut"><span>{{ $motif !== '' ? $motif : 'Sans motif' }}</span><b>{{ $total }}</b></div>
                            <div class="hl-jauge" aria-hidden="true"><span style="width: {{ $total * 100 / $maxMotif }}%"></span></div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="hl-bloc">
            <h2 class="hl-bloc-titre">Diagnostics les plus posés</h2>
            @if(collect($stats['diagnostics'])->isEmpty())
                <div class="hl-vide">Aucun diagnostic sur la période.</div>
            @else
                <ul class="hl-classement">
                    @foreach($stats['diagnostics'] as $diagnostic => $total)
                        <li>
                            <div class="hl-classement-haut"><span>{{ $diagnostic }}</span><b>{{ $total }}</b></div>
                            <div class="hl-jauge" style="--hl-jauge: #1d4ed8; --hl-jauge-fond: #eff6ff" aria-hidden="true"><span style="width: {{ $total * 100 / $maxDiagnostic }}%"></span></div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    <p class="hl-note hl-note-info mt-3">
        <i class="fas fa-info-circle mt-1" aria-hidden="true"></i>
        <span>
            {{ $pluriel((int) $stats['sans_rendez_vous'], 'venue sans rendez-vous', 'venues sans rendez-vous') }},
            {{ $pluriel((int) $stats['urgences'], 'urgence') }}.
            Le laboratoire et l'hospitalisation ont leurs propres chiffres dans leurs écrans.
        </span>
    </p>
</div></div>

<script>
(function () {
    // Changer de médecin affiche le résultat directement.
    var choix = document.getElementById('st-medecin');
    if (choix) choix.addEventListener('change', function () { document.getElementById('st-filtres').submit(); });
})();
</script>
@endsection
