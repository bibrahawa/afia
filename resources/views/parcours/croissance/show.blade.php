@extends('layouts.backend')

@php
    $points = $mesures->filter(fn ($m) => $m['mois'] !== null && $m['poids'] !== null)->values();
    $derniere = $mesures->sortByDesc(fn ($m) => $m['date']->timestamp)->first();
    $derniereAvecPoids = $mesures->filter(fn ($m) => $m['poids'] !== null)->sortByDesc(fn ($m) => $m['date']->timestamp)->first();
    $derniereAvecTaille = $mesures->filter(fn ($m) => $m['taille'] !== null)->sortByDesc(fn ($m) => $m['date']->timestamp)->first();

    $kg = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, ',', ''), '0'), ',');
    $cm = fn ($v) => rtrim(rtrim(number_format((float) $v, 1, ',', ''), '0'), ',');
    $z = fn ($v) => ($v > 0 ? '+' : '') . number_format((float) $v, 1, ',', '');

    // Âge lisible : « 1 an 4 mois » plutôt que « 16 mois ».
    $age = function ($mois) {
        if ($mois === null) return '—';
        $mois = (int) $mois;
        if ($mois < 24) return $mois . ' mois';
        $ans = intdiv($mois, 12); $reste = $mois % 12;
        return $ans . ' an' . ($ans > 1 ? 's' : '') . ($reste ? ' ' . $reste . ' mois' : '');
    };
    $ageActuel = $naissance ? (int) $naissance->diffInMonths(today()) : null;

    // Pastille d'après le niveau renvoyé par le service (success, warning, danger…).
    $ton = fn ($niveau) => match ($niveau) {
        'success' => 'hl-s-succes', 'warning' => 'hl-s-alerte', 'danger' => 'hl-s-danger', 'info' => 'hl-s-info', default => 'hl-s-neutre',
    };
    $couleur = fn ($niveau) => match ($niveau) {
        'success' => '#15803d', 'warning' => '#b45309', 'danger' => '#b91c1c', 'info' => '#1d4ed8', default => '#0f766e',
    };

    // Courbe : axes « ronds » pour que la grille tombe sur des valeurs lisibles.
    $courbe = null;
    if ($points->count() >= 1) {
        $moisMax = max(6, (int) ceil(($points->max('mois') + 1) / 6) * 6);
        $pMin = max(0, floor($points->min('poids')) - 1);
        $pMax = ceil($points->max('poids')) + 1;
        $pasPoids = ($pMax - $pMin) > 10 ? 2 : 1;
        [$g, $d, $h, $b] = [44, 620, 16, 216];
        $x = fn ($mois) => round($g + $mois * ($d - $g) / $moisMax, 1);
        $y = fn ($poids) => round($b - ($poids - $pMin) * ($b - $h) / max(1, $pMax - $pMin), 1);
        $courbe = [
            'x' => $x, 'y' => $y, 'moisMax' => $moisMax, 'pMin' => $pMin, 'pMax' => $pMax, 'pas' => $pasPoids,
            'g' => $g, 'd' => $d, 'h' => $h, 'b' => $b,
            'ligne' => $points->map(fn ($p) => $x($p['mois']) . ',' . $y($p['poids']))->join(' '),
        ];
    }
@endphp

@section('style')
<style>
    .cr-grille { display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr); gap: 16px; align-items: start; }
    .cr-kpi-bas { margin-top: 2px; }
    .cr-courbe { padding: 12px 18px 16px; }
    .cr-courbe svg { display: block; width: 100%; height: auto; }
    .cr-courbe text { font-family: inherit; }
    .cr-legende { display: flex; flex-wrap: wrap; gap: 6px 14px; padding: 0 18px 14px; color: var(--hali-discret); font-size: .78rem; }
    .cr-legende i { display: inline-block; width: 9px; height: 9px; margin-right: 5px; border-radius: 50%; vertical-align: -1px; }
    .cr-table { width: 100%; border-collapse: collapse; font-size: .88rem; }
    .cr-table th { padding: 10px 14px; background: #fafbfc; border-bottom: 1px solid var(--hali-bordure); color: var(--hali-discret); font-size: .78rem; font-weight: 600; text-align: left; white-space: nowrap; }
    .cr-table td { padding: 10px 14px; border-top: 1px solid #f3f4f6; font-variant-numeric: tabular-nums; }
    .cr-table tr:first-child td { border-top: 0; }
    .cr-table small { display: block; color: var(--hali-discret); font-size: .75rem; }
    @media (max-width: 1199.98px) { .cr-grille { grid-template-columns: 1fr; } }
</style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">

    <header class="hl-entete">
        <div style="display:flex; align-items:center; gap:14px">
            <span class="hl-avatar" style="width:52px;height:52px;flex-basis:52px;border-radius:14px;font-size:1.05rem" aria-hidden="true">
                @php $n = preg_split('/\s+/', trim((string) $patient->full_name)); @endphp
                {{ mb_strtoupper(mb_substr($n[0] ?? '', 0, 1) . mb_substr(end($n) ?: '', 0, 1)) }}
            </span>
            <div>
                <h1>{{ $patient->full_name }}</h1>
                <p>
                    Croissance ·
                    @if($naissance)
                        né{{ $patient->gender === 'Femme' ? 'e' : '' }} le {{ $naissance->format('d/m/Y') }} · {{ $age($ageActuel) }}
                    @else
                        date de naissance inconnue
                    @endif
                </p>
            </div>
        </div>
        <div class="hl-entete-actions">
            @can('parcours.dossier')
                <a href="{{ route('parcours.dossier.show', $patient->id) }}" class="hl-bouton"><i class="fas fa-folder-open" aria-hidden="true"></i> Dossier</a>
            @endcan
        </div>
    </header>

    @unless($naissance)
        <p class="hl-note hl-note-alerte mb-3">
            <i class="fas fa-exclamation-circle mt-1" aria-hidden="true"></i>
            <span>Sans date de naissance sur la fiche patient, l'âge en mois ne peut pas être calculé : la courbe reste vide. Complétez la fiche patient.</span>
        </p>
    @endunless

    @unless($normesDisponibles)
        <p class="hl-note hl-note-info mb-3">
            <i class="fas fa-info-circle mt-1" aria-hidden="true"></i>
            <span>Les tables de référence de l'OMS ne sont pas encore importées : les mesures s'affichent, mais sans z-score. Import par l'administrateur :
                <code>php artisan aprosafe:importer-normes-oms &lt;fichier&gt; poids_age Homme</code></span>
        </p>
    @endunless

    {{-- ------------------------------------------------ Chiffres clés --}}
    <div class="hl-kpis">
        <div class="hl-bloc hl-kpi">
            <span class="hl-kpi-libelle">Dernier poids</span>
            <span class="hl-kpi-valeur">{{ $derniereAvecPoids ? $kg($derniereAvecPoids['poids']) : '—' }} @if($derniereAvecPoids)<small>kg</small>@endif</span>
            <span class="hl-kpi-detail">{{ $derniereAvecPoids ? 'le ' . $derniereAvecPoids['date']->format('d/m/Y') . ' · ' . $age($derniereAvecPoids['mois']) : 'Aucune pesée enregistrée' }}</span>
        </div>
        <div class="hl-bloc hl-kpi">
            <span class="hl-kpi-libelle">Dernière taille</span>
            <span class="hl-kpi-valeur">{{ $derniereAvecTaille ? $cm($derniereAvecTaille['taille']) : '—' }} @if($derniereAvecTaille)<small>cm</small>@endif</span>
            <span class="hl-kpi-detail">{{ $derniereAvecTaille ? 'le ' . $derniereAvecTaille['date']->format('d/m/Y') : 'Aucune mesure de taille' }}</span>
        </div>
        @php $zDernier = $derniereAvecPoids['z']['poids'] ?? null; $lectureDerniere = $zDernier !== null ? $croissance->interpretation($zDernier) : null; @endphp
        <div class="hl-bloc hl-kpi {{ $lectureDerniere && $lectureDerniere['niveau'] === 'danger' ? 'est-danger' : ($lectureDerniere && $lectureDerniere['niveau'] === 'warning' ? 'est-alerte' : '') }}">
            <span class="hl-kpi-libelle">Poids pour l'âge</span>
            <span class="hl-kpi-valeur">{{ $zDernier !== null ? $z($zDernier) : '—' }} @if($zDernier !== null)<small>z</small>@endif</span>
            <span class="hl-kpi-detail cr-kpi-bas">
                @if($lectureDerniere)
                    <span class="hl-statut {{ $ton($lectureDerniere['niveau']) }}">{{ $lectureDerniere['libelle'] }}</span>
                @else
                    {{ $normesDisponibles ? 'Pas de mesure exploitable' : 'Tables OMS non importées' }}
                @endif
            </span>
        </div>
        <div class="hl-bloc hl-kpi">
            <span class="hl-kpi-libelle">Mesures enregistrées</span>
            <span class="hl-kpi-valeur">{{ $mesures->count() }}</span>
            <span class="hl-kpi-detail">{{ $derniere ? 'dernière le ' . $derniere['date']->format('d/m/Y') : 'reprises des constantes de l\'accueil' }}</span>
        </div>
    </div>

    <div class="cr-grille">
        {{-- ------------------------------------------------ Courbe --}}
        <section class="hl-bloc">
            <h2 class="hl-bloc-titre">Poids selon l'âge <small>kg · mois</small></h2>
            @if($courbe && $points->count() >= 2)
                @php extract($courbe); @endphp
                <div class="cr-courbe">
                    <svg viewBox="0 0 640 244" role="img" aria-label="Courbe du poids selon l'âge">
                        {{-- Grille horizontale (poids) --}}
                        @for($p = $pMin; $p <= $pMax; $p += $pas)
                            <line x1="{{ $g }}" y1="{{ $y($p) }}" x2="{{ $d }}" y2="{{ $y($p) }}" stroke="#eef0f3"/>
                            <text x="{{ $g - 8 }}" y="{{ $y($p) + 4 }}" font-size="11" fill="#9ca3af" text-anchor="end">{{ $p }}</text>
                        @endfor
                        {{-- Grille verticale (âge, tous les 6 mois) --}}
                        @for($m = 0; $m <= $moisMax; $m += 6)
                            <line x1="{{ $x($m) }}" y1="{{ $h }}" x2="{{ $x($m) }}" y2="{{ $b }}" stroke="#f3f4f6"/>
                            <text x="{{ $x($m) }}" y="{{ $b + 18 }}" font-size="11" fill="#9ca3af" text-anchor="middle">{{ $m }}</text>
                        @endfor
                        <line x1="{{ $g }}" y1="{{ $b }}" x2="{{ $d }}" y2="{{ $b }}" stroke="#d1d5db"/>

                        <polyline points="{{ $ligne }}" fill="none" stroke="#0f766e" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"/>
                        @foreach($points as $pt)
                            @php $lecturePoint = $pt['z']['poids'] !== null ? $croissance->interpretation($pt['z']['poids']) : null; @endphp
                            <circle cx="{{ $x($pt['mois']) }}" cy="{{ $y($pt['poids']) }}" r="5" fill="#fff" stroke="{{ $couleur($lecturePoint['niveau'] ?? null) }}" stroke-width="2.5">
                                <title>{{ $pt['date']->format('d/m/Y') }} · {{ $age($pt['mois']) }} · {{ $kg($pt['poids']) }} kg{{ $lecturePoint ? ' · ' . $lecturePoint['libelle'] : '' }}</title>
                            </circle>
                        @endforeach
                        @php $dernierPoint = $points->last(); @endphp
                        <text x="{{ min($d - 4, $x($dernierPoint['mois']) + 10) }}" y="{{ $y($dernierPoint['poids']) - 10 }}" font-size="12" font-weight="700" fill="#111827"
                              text-anchor="{{ $x($dernierPoint['mois']) > $d - 60 ? 'end' : 'start' }}">{{ $kg($dernierPoint['poids']) }} kg</text>
                    </svg>
                </div>
                @if($normesDisponibles)
                    <div class="cr-legende" aria-hidden="true">
                        <span><i style="background:#15803d"></i>Dans la norme</span>
                        <span><i style="background:#b45309"></i>À surveiller</span>
                        <span><i style="background:#b91c1c"></i>Écart important</span>
                    </div>
                @endif
            @else
                <div class="hl-vide">
                    <i class="fas fa-chart-line" aria-hidden="true"></i>
                    Au moins deux pesées avec l'âge de l'enfant sont nécessaires pour tracer la courbe.<br>
                    Le poids se saisit dans les constantes, à l'accueil.
                </div>
            @endif
        </section>

        {{-- ------------------------------------------------ Mesures --}}
        <section class="hl-bloc">
            <h2 class="hl-bloc-titre">Mesures <small>de la plus récente à la plus ancienne</small></h2>
            @if($mesures->isEmpty())
                <div class="hl-vide">Aucune mesure enregistrée.</div>
            @else
                <div class="table-responsive">
                    <table class="cr-table">
                        <thead><tr><th>Date</th><th>Poids</th><th>Taille</th><th>Poids pour l'âge</th></tr></thead>
                        <tbody>
                        @foreach($mesures->sortByDesc(fn ($m) => $m['date']->timestamp) as $m)
                            @php $lecture = $m['z']['poids'] !== null ? $croissance->interpretation($m['z']['poids']) : null; @endphp
                            <tr>
                                <td>{{ $m['date']->format('d/m/Y') }}<small>{{ $age($m['mois']) }}</small></td>
                                <td>{{ $m['poids'] !== null ? $kg($m['poids']) . ' kg' : '—' }}</td>
                                <td>{{ $m['taille'] !== null ? $cm($m['taille']) . ' cm' : '—' }}</td>
                                <td>
                                    @if($lecture)
                                        <span class="hl-statut {{ $ton($lecture['niveau']) }}" title="{{ $lecture['libelle'] }}">{{ $z($m['z']['poids']) }}</span>
                                        <small>{{ $lecture['libelle'] }}</small>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</div></div>
@endsection
