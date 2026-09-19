@extends('layouts.backend')

@php
    $libellesTypes = \App\Services\Parcours\DossierPatientService::TYPES;

    // Un type = une couleur et une icône, reprises partout sur la page.
    $types = [
        'consultation'    => ['icone' => 'fa-stethoscope',   'classe' => 'dz-t-consultation'],
        'hospitalisation' => ['icone' => 'fa-procedures',    'classe' => 'dz-t-hospitalisation'],
        'laboratoire'     => ['icone' => 'fa-vials',         'classe' => 'dz-t-laboratoire'],
        'rendez_vous'     => ['icone' => 'fa-calendar-check','classe' => 'dz-t-rdv'],
        'grossesse'       => ['icone' => 'fa-baby',          'classe' => 'dz-t-grossesse'],
    ];

    // Couleur d'un statut d'après son libellé : le médecin lit l'état d'un coup d'œil.
    $tonStatut = function (?string $statut): string {
        $s = mb_strtolower((string) $statut);
        return match (true) {
            str_contains($s, 'à valider'), str_contains($s, 'à confirmer'), str_contains($s, 'à programmer') => 'dz-s-alerte',
            str_contains($s, 'annul'), str_contains($s, 'absent'), str_contains($s, 'manqu'), str_contains($s, 'rejet') => 'dz-s-danger',
            str_contains($s, 'en cours'), str_contains($s, 'en analyse'), str_contains($s, 'prélev') => 'dz-s-info',
            str_contains($s, 'publi'), str_contains($s, 'honor'), str_contains($s, 'confirm'), str_contains($s, 'termin'), str_contains($s, 'valid') => 'dz-s-succes',
            default => 'dz-s-neutre',
        };
    };

    // Dates en français sans dépendre de la locale configurée sur le serveur.
    $jours = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
    $mois = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    $libelleJour = function (\Carbon\Carbon $d) use ($jours, $mois): string {
        if ($d->isToday()) return "Aujourd'hui";
        if ($d->isYesterday()) return 'Hier';
        $texte = $jours[$d->dayOfWeek] . ' ' . $d->day . ' ' . $mois[$d->month];
        return ucfirst($d->year === today()->year ? $texte : $texte . ' ' . $d->year);
    };

    $parJour = $evenements->groupBy(fn ($e) => $e['date']->toDateString());
    $totalEvenements = $total ?? $evenements->count();
    $filtreActif = count($typesChoisis) < count($libellesTypes) || ! empty($filtres['depuis']) || ! empty($filtres['jusqu_a']);

    $noms = preg_split('/\s+/', trim((string) $patient->full_name));
    $initiales = mb_strtoupper(mb_substr($noms[0] ?? '', 0, 1) . mb_substr(end($noms) ?: '', 0, 1));

    // Raccourcis de période : un clic, pas de calendrier à manipuler.
    $raccourcis = [
        '30 jours' => today()->subDays(30)->toDateString(),
        '6 mois'   => today()->subMonths(6)->toDateString(),
        '1 an'     => today()->subYear()->toDateString(),
    ];
@endphp

@section('content')
<style>
    /* ------------------------------------------------ Dossier patient (dz-) */
    .dz { --dz-ligne: #e5e7eb; }
    .dz *, .dz *::before, .dz *::after { box-sizing: border-box; }

    /* En-tête patient */
    .dz-entete { display: flex; flex-wrap: wrap; align-items: center; gap: 16px; margin-bottom: 20px; }
    .dz-avatar {
        width: 52px; height: 52px; flex: 0 0 52px; display: grid; place-items: center;
        border-radius: 14px; background: var(--hali-primaire-clair, #ccfbf1); color: var(--hali-primaire-fonce, #115e59);
        font-size: 1.1rem; font-weight: 700; letter-spacing: .02em;
    }
    .dz-identite h1 { margin: 0; font-size: 1.45rem; font-weight: 700; color: var(--hali-encre, #111827); letter-spacing: -.015em; line-height: 1.25; }
    .dz-identite p { margin: 2px 0 0; color: var(--hali-discret, #6b7280); font-size: .9rem; }
    .dz-actions { margin-left: auto; display: flex; flex-wrap: wrap; gap: 8px; }
    .dz-bouton {
        display: inline-flex; align-items: center; gap: 7px; min-height: 36px; padding: 0 14px;
        border: 1px solid var(--dz-ligne); border-radius: 8px; background: #fff;
        color: var(--hali-texte, #374151); font-size: .85rem; font-weight: 600; text-decoration: none;
        transition: border-color .12s ease, color .12s ease, background-color .12s ease;
    }
    .dz-bouton:hover { border-color: var(--hali-primaire, #0f766e); color: var(--hali-primaire-fonce, #115e59); text-decoration: none; }
    .dz-bouton i { font-size: .82rem; opacity: .8; }
    .dz-bouton-plein { background: var(--hali-primaire, #0f766e); border-color: var(--hali-primaire, #0f766e); color: #fff; }
    .dz-bouton-plein:hover { background: var(--hali-primaire-fonce, #115e59); color: #fff; }

    /* Allergies : l'information qui ne doit jamais être ratée */
    .dz-allergies {
        display: flex; gap: 12px; align-items: flex-start; margin-bottom: 20px; padding: 14px 16px;
        border: 1px solid #fecaca; border-left: 4px solid var(--hali-danger, #b91c1c); border-radius: 10px;
        background: var(--hali-danger-pale, #fef2f2); color: #7f1d1d;
    }
    .dz-allergies i { margin-top: 3px; color: var(--hali-danger, #b91c1c); }
    .dz-allergies strong { display: block; font-size: .8rem; letter-spacing: .02em; }

    /* Grille */
    .dz-grille { display: grid; grid-template-columns: minmax(0, 320px) minmax(0, 1fr); gap: 20px; align-items: start; }
    .dz-cote { position: sticky; top: 88px; display: grid; gap: 16px; }
    .dz-bloc { background: #fff; border: 1px solid var(--dz-ligne); border-radius: 12px; box-shadow: 0 1px 2px rgba(17,24,39,.04); }
    .dz-bloc-titre { display: flex; align-items: center; gap: 8px; margin: 0; padding: 14px 18px; border-bottom: 1px solid var(--dz-ligne); font-size: .95rem; font-weight: 650; color: var(--hali-encre, #111827); }
    .dz-bloc-corps { padding: 16px 18px; }

    /* Grossesse */
    .dz-terme { display: flex; align-items: baseline; gap: 8px; margin-bottom: 2px; }
    .dz-terme-valeur { font-size: 1.6rem; font-weight: 700; color: var(--hali-encre, #111827); letter-spacing: -.02em; font-variant-numeric: tabular-nums; }
    .dz-terme-trimestre { color: var(--hali-discret, #6b7280); font-size: .85rem; }
    .dz-jauge { position: relative; height: 8px; margin: 12px 0 6px; border-radius: 999px; background: #fce7f3; overflow: hidden; }
    .dz-jauge span { position: absolute; inset: 0 auto 0 0; border-radius: 999px; background: #be185d; }
    .dz-jauge-reperes { display: flex; justify-content: space-between; color: #9ca3af; font-size: .72rem; font-variant-numeric: tabular-nums; }
    .dz-lignes { margin: 14px 0 16px; display: grid; gap: 8px; font-size: .88rem; }
    .dz-ligne { display: flex; justify-content: space-between; gap: 12px; }
    .dz-ligne dt { color: var(--hali-discret, #6b7280); font-weight: 500; }
    .dz-ligne dd { margin: 0; color: var(--hali-encre, #111827); font-weight: 600; text-align: right; font-variant-numeric: tabular-nums; }
    .dz-rappel { display: flex; gap: 8px; align-items: center; margin-bottom: 14px; padding: 8px 10px; border-radius: 8px; background: var(--hali-alerte-pale, #fffbeb); color: #78350f; font-size: .82rem; }
    .dz-lien-plein { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; }

    .dz-form { display: grid; gap: 10px; }
    .dz-form .dz-deux { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .dz-form label { font-size: .8rem; margin-bottom: 4px; }
    .dz-vide { color: var(--hali-discret, #6b7280); font-size: .88rem; margin: 0; }

    /* Barre de filtres */
    .dz-barre { padding: 14px 18px; border-bottom: 1px solid var(--dz-ligne); display: grid; gap: 12px; }
    .dz-barre-haut { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
    .dz-compte { margin: 0; font-size: 1rem; font-weight: 650; color: var(--hali-encre, #111827); }
    .dz-compte small { font-weight: 500; color: var(--hali-discret, #6b7280); font-size: .85rem; }
    .dz-reinit { margin-left: auto; font-size: .82rem; font-weight: 600; }

    .dz-puces { display: flex; flex-wrap: wrap; gap: 8px; }
    .dz-puce { position: relative; margin: 0; }
    .dz-puce input { position: absolute; opacity: 0; pointer-events: none; }
    .dz-puce span {
        display: inline-flex; align-items: center; gap: 7px; min-height: 34px; padding: 0 12px;
        border: 1px solid var(--dz-ligne); border-radius: 999px; background: #fff;
        color: var(--hali-discret, #6b7280); font-size: .83rem; font-weight: 600; cursor: pointer; user-select: none;
        transition: background-color .12s ease, border-color .12s ease, color .12s ease;
    }
    .dz-puce span i { font-size: .78rem; }
    .dz-puce input:checked + span { background: var(--dz-pale); border-color: var(--dz-teinte); color: var(--dz-fonce); }
    .dz-puce input:focus-visible + span { outline: 2px solid var(--hali-primaire, #0f766e); outline-offset: 2px; }
    .dz-puce span:hover { border-color: var(--dz-teinte); }

    .dz-periode { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; font-size: .83rem; }
    .dz-periode-lien {
        display: inline-flex; align-items: center; min-height: 30px; padding: 0 10px; border-radius: 6px;
        color: var(--hali-texte, #374151); font-weight: 600; text-decoration: none; background: #f3f4f6;
    }
    .dz-periode-lien:hover { background: var(--hali-primaire-clair, #ccfbf1); color: var(--hali-primaire-fonce, #115e59); text-decoration: none; }
    .dz-periode-lien.est-actif { background: var(--hali-primaire, #0f766e); color: #fff; }
    .dz-periode details { margin-left: auto; }
    .dz-periode summary { list-style: none; color: var(--hali-primaire, #0f766e); font-weight: 600; cursor: pointer; }
    .dz-periode summary::-webkit-details-marker { display: none; }
    .dz-dates { display: flex; flex-wrap: wrap; align-items: end; gap: 8px; margin-top: 10px; }
    .dz-dates label { display: grid; gap: 4px; font-size: .78rem; margin: 0; }
    .dz-dates input { min-height: 34px; padding: 0 8px; font-size: .85rem; }

    /* Types : teinte, pâle, foncé */
    .dz-t-consultation    { --dz-teinte: #0f766e; --dz-pale: #f0fdfa; --dz-fonce: #115e59; }
    .dz-t-hospitalisation { --dz-teinte: #b91c1c; --dz-pale: #fef2f2; --dz-fonce: #991b1b; }
    .dz-t-laboratoire     { --dz-teinte: #1d4ed8; --dz-pale: #eff6ff; --dz-fonce: #1e40af; }
    .dz-t-rdv             { --dz-teinte: #b45309; --dz-pale: #fffbeb; --dz-fonce: #92400e; }
    .dz-t-grossesse       { --dz-teinte: #be185d; --dz-pale: #fdf2f8; --dz-fonce: #9d174d; }

    /* Frise */
    .dz-frise { padding: 6px 18px 18px; }
    .dz-jour { margin: 0; padding: 14px 0 8px; background: #fff; color: var(--hali-encre, #111827); font-size: .85rem; font-weight: 700; }
    .dz-jour small { margin-left: 6px; color: #9ca3af; font-weight: 500; }
    .dz-liste { position: relative; margin: 0; padding: 0; list-style: none; }
    .dz-liste::before { content: ""; position: absolute; left: 17px; top: 8px; bottom: 8px; width: 2px; background: #f1f2f4; }

    .dz-evt { position: relative; display: grid; grid-template-columns: 36px minmax(0, 1fr); gap: 14px; padding: 6px 0; }
    .dz-noeud {
        position: relative; z-index: 1; width: 36px; height: 36px; display: grid; place-items: center;
        border-radius: 10px; background: var(--dz-pale); color: var(--dz-teinte); border: 1px solid #fff; box-shadow: 0 0 0 3px #fff;
    }
    .dz-noeud i { font-size: .85rem; }
    .dz-carte {
        position: relative; padding: 10px 14px; border: 1px solid transparent; border-radius: 10px;
        transition: background-color .12s ease, border-color .12s ease;
    }
    .dz-evt.a-lien .dz-carte:hover { background: var(--dz-pale); border-color: color-mix(in srgb, var(--dz-teinte) 25%, transparent); }
    .dz-carte-haut { display: flex; align-items: baseline; gap: 10px; }
    .dz-titre { margin: 0; font-size: .95rem; font-weight: 650; color: var(--hali-encre, #111827); line-height: 1.4; overflow-wrap: anywhere; }
    .dz-titre a { color: inherit; text-decoration: none; }
    .dz-titre a::after { content: ""; position: absolute; inset: 0; border-radius: 10px; }
    .dz-titre a:focus-visible { outline: none; }
    .dz-titre a:focus-visible::after { outline: 2px solid var(--hali-primaire, #0f766e); outline-offset: 1px; }
    .dz-heure { margin-left: auto; color: #9ca3af; font-size: .8rem; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .dz-type { color: var(--dz-teinte); font-size: .78rem; font-weight: 600; }
    .dz-details { display: flex; flex-wrap: wrap; align-items: center; gap: 4px 14px; margin-top: 4px; font-size: .85rem; color: var(--hali-texte, #374151); }
    .dz-details span b { color: var(--hali-discret, #6b7280); font-weight: 500; }
    .dz-ouvrir { color: var(--dz-teinte); font-size: .8rem; font-weight: 600; opacity: 0; transition: opacity .12s ease; }
    .dz-evt.a-lien .dz-carte:hover .dz-ouvrir, .dz-titre a:focus-visible ~ .dz-ouvrir { opacity: 1; }

    .dz-statut { display: inline-flex; align-items: center; gap: 6px; padding: 2px 9px; border-radius: 999px; font-size: .76rem; font-weight: 600; }
    .dz-statut::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
    .dz-s-succes { background: var(--hali-succes-pale, #f0fdf4); color: var(--hali-succes, #15803d); }
    .dz-s-alerte { background: var(--hali-alerte-pale, #fffbeb); color: var(--hali-alerte, #b45309); }
    .dz-s-danger { background: var(--hali-danger-pale, #fef2f2); color: var(--hali-danger, #b91c1c); }
    .dz-s-info   { background: var(--hali-info-pale, #eff6ff); color: var(--hali-info, #1d4ed8); }
    .dz-s-neutre { background: #f3f4f6; color: var(--hali-texte, #374151); }

    .dz-plus { padding: 14px 18px; border-top: 1px solid var(--dz-ligne); text-align: center; }

    .dz-aucun { padding: 48px 24px; text-align: center; }
    .dz-aucun i { font-size: 1.6rem; color: #d1d5db; }
    .dz-aucun p { margin: 10px 0 14px; color: var(--hali-discret, #6b7280); }

    @media (max-width: 991.98px) {
        .dz-grille { grid-template-columns: 1fr; }
        .dz-cote { position: static; }
        .dz-actions { margin-left: 0; width: 100%; }
    }
    @media (max-width: 575.98px) {
        .dz-barre, .dz-frise { padding-left: 12px; padding-right: 12px; }
        .dz-evt { gap: 10px; }
        .dz-carte { padding: 8px 10px; }
    }
    @media (hover: none) { .dz-ouvrir { opacity: 1; } }
    @media (prefers-reduced-motion: reduce) { .dz * { transition: none !important; } }
    @media print {
        .dz-actions, .dz-barre, .dz-plus, .dz-ouvrir { display: none !important; }
        .dz-grille { grid-template-columns: 1fr; }
        .dz-cote { position: static; }
    }
</style>

<div class="container"><div class="page-inner dz">

    {{-- ============================================ Patient --}}
    <header class="dz-entete">
        <div class="dz-avatar" aria-hidden="true">{{ $initiales }}</div>
        <div class="dz-identite">
            <h1>{{ $patient->full_name }}</h1>
            <p>{{ $patient->gender }}{{ $patient->age !== null ? ' · ' . $patient->age . ' ans' : '' }} · Dossier médical</p>
        </div>
        <nav class="dz-actions" aria-label="Actions du dossier">
            <a href="{{ route('parcours.documents.index', $patient->id) }}" class="dz-bouton"><i class="fas fa-file-medical"></i> Documents</a>
            @if($patient->dateNaissance() && $patient->dateNaissance()->diffInMonths(today()) <= \App\Services\Parcours\CroissanceService::AGE_MAX_MOIS)
                <a href="{{ route('parcours.croissance.show', $patient->id) }}" class="dz-bouton"><i class="fas fa-chart-line"></i> Croissance</a>
            @endif
            <a href="{{ route('patient.show', $patient->id) }}" class="dz-bouton dz-bouton-plein"><i class="fas fa-id-card"></i> Fiche patient</a>
        </nav>
    </header>

    @if($patient->antecedant?->allergies)
        <div class="dz-allergies" role="alert">
            <i class="fas fa-exclamation-triangle"></i>
            <div><strong>Allergies</strong>{{ $patient->antecedant->allergies }}</div>
        </div>
    @endif

    <div class="dz-grille">

        {{-- ============================================ Colonne de côté --}}
        <aside class="dz-cote">
            @if($grossesse)
                @php
                    [$sa, $j] = $grossesse->terme() ?? [0, 0];
                    $avancement = min(100, round(($sa * 7 + $j) * 100 / 280));
                    $trimestre = $sa < 14 ? '1er trimestre' : ($sa < 28 ? '2e trimestre' : '3e trimestre');
                    $prochain = $grossesse->prochainContact();
                    $joursAvantDpa = (int) today()->diffInDays($grossesse->dpa, false);
                @endphp
                <section class="dz-bloc">
                    <h2 class="dz-bloc-titre"><i class="fas fa-baby" style="color:#be185d"></i> Grossesse en cours</h2>
                    <div class="dz-bloc-corps">
                        <div class="dz-terme">
                            <span class="dz-terme-valeur">{{ $grossesse->termeLisible() }}</span>
                            <span class="dz-terme-trimestre">{{ $trimestre }}</span>
                        </div>
                        <div class="dz-jauge" role="progressbar" aria-label="Avancement de la grossesse" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $avancement }}">
                            <span style="width: {{ $avancement }}%"></span>
                        </div>
                        <div class="dz-jauge-reperes" aria-hidden="true"><span>0</span><span>14 SA</span><span>28 SA</span><span>40 SA</span></div>

                        <dl class="dz-lignes">
                            <div class="dz-ligne">
                                <dt>Accouchement prévu</dt>
                                <dd>{{ $grossesse->dpa->format('d/m/Y') }}</dd>
                            </div>
                            <div class="dz-ligne">
                                <dt>{{ $joursAvantDpa >= 0 ? 'Dans' : 'Terme dépassé de' }}</dt>
                                <dd>{{ abs($joursAvantDpa) }} jour{{ abs($joursAvantDpa) > 1 ? 's' : '' }}</dd>
                            </div>
                            @if($prochain)
                                <div class="dz-ligne">
                                    <dt>Prochaine CPN</dt>
                                    <dd>{{ $prochain['semaines'] }} SA · {{ $prochain['date_cible']->format('d/m/Y') }}</dd>
                                </div>
                            @endif
                        </dl>

                        @if($prochain && $prochain['statut'] === 'a_programmer')
                            <p class="dz-rappel"><i class="fas fa-bell"></i> Cette CPN n'est pas encore programmée.</p>
                        @endif

                        <a href="{{ route('parcours.grossesses.show', $grossesse) }}" class="dz-bouton dz-lien-plein">Ouvrir le suivi de grossesse</a>
                    </div>
                </section>
            @elseif($patient->gender === 'Femme')
                @can('parcours.grossesse')
                    <section class="dz-bloc">
                        <h2 class="dz-bloc-titre"><i class="fas fa-baby" style="color:#be185d"></i> Suivi de grossesse</h2>
                        <div class="dz-bloc-corps">
                            <form method="POST" action="{{ route('parcours.grossesses.store', $patient->id) }}" class="dz-form">
                                @csrf
                                <div>
                                    <label class="form-label" for="dz-ddr">Date des dernières règles</label>
                                    <input type="date" id="dz-ddr" name="ddr" class="form-control form-control-sm" max="{{ today()->toDateString() }}" required>
                                    <p class="form-text mb-0">Le terme et la date d'accouchement se calculent seuls.</p>
                                </div>
                                <div class="dz-deux">
                                    <div><label class="form-label" for="dz-gestite">Gestité</label><input type="number" id="dz-gestite" min="1" max="20" name="gestite" class="form-control form-control-sm" inputmode="numeric"></div>
                                    <div><label class="form-label" for="dz-parite">Parité</label><input type="number" id="dz-parite" min="0" max="20" name="parite" class="form-control form-control-sm" inputmode="numeric"></div>
                                </div>
                                <button class="dz-bouton dz-bouton-plein dz-lien-plein" type="submit">Ouvrir un suivi de grossesse</button>
                            </form>
                        </div>
                    </section>
                @endcan
            @endif
        </aside>

        {{-- ============================================ Historique --}}
        <section class="dz-bloc" aria-labelledby="dz-historique">
            <form method="GET" class="dz-barre" id="dz-filtres">
                <div class="dz-barre-haut">
                    <h2 class="dz-compte" id="dz-historique">
                        Historique
                        <small>
                            {{ $evenements->count() }} événement{{ $evenements->count() > 1 ? 's' : '' }}@if($totalEvenements > $evenements->count()) sur {{ $totalEvenements }}@endif
                        </small>
                    </h2>
                    @if($filtreActif)
                        <a href="{{ url()->current() }}" class="dz-reinit">Tout afficher</a>
                    @endif
                </div>

                <div class="dz-puces" role="group" aria-label="Types d'événements affichés">
                    @foreach($libellesTypes as $valeur => $libelle)
                        <label class="dz-puce {{ $types[$valeur]['classe'] ?? '' }}">
                            <input type="checkbox" name="types[]" value="{{ $valeur }}" @checked(in_array($valeur, $typesChoisis, true))>
                            <span><i class="fas {{ $types[$valeur]['icone'] ?? 'fa-circle' }}" aria-hidden="true"></i>{{ $libelle }}</span>
                        </label>
                    @endforeach
                </div>

                <div class="dz-periode">
                    @foreach($raccourcis as $libelle => $depuis)
                        <a class="dz-periode-lien {{ ($filtres['depuis'] ?? null) === $depuis && empty($filtres['jusqu_a']) ? 'est-actif' : '' }}"
                           href="{{ request()->fullUrlWithQuery(['depuis' => $depuis, 'jusqu_a' => null, 'limite' => null]) }}">{{ $libelle }}</a>
                    @endforeach
                    <a class="dz-periode-lien {{ empty($filtres['depuis']) && empty($filtres['jusqu_a']) ? 'est-actif' : '' }}"
                       href="{{ request()->fullUrlWithQuery(['depuis' => null, 'jusqu_a' => null, 'limite' => null]) }}">Tout</a>

                    <details @if(! empty($filtres['depuis']) && ! in_array($filtres['depuis'], $raccourcis, true) || ! empty($filtres['jusqu_a'])) open @endif>
                        <summary>Dates précises</summary>
                        <div class="dz-dates">
                            <label>Du<input type="date" name="depuis" class="form-control" value="{{ $filtres['depuis'] ?? '' }}"></label>
                            <label>Au<input type="date" name="jusqu_a" class="form-control" value="{{ $filtres['jusqu_a'] ?? '' }}"></label>
                            <button type="submit" class="dz-bouton dz-bouton-plein">Afficher</button>
                        </div>
                    </details>
                </div>
            </form>

            @if($evenements->isEmpty())
                <div class="dz-aucun">
                    <i class="fas fa-folder-open" aria-hidden="true"></i>
                    @if($filtreActif)
                        <p>Aucun événement ne correspond à ces filtres.</p>
                        <a href="{{ url()->current() }}" class="dz-bouton">Afficher tout l'historique</a>
                    @else
                        <p>Aucun événement enregistré pour ce patient dans la clinique.</p>
                    @endif
                </div>
            @else
                <div class="dz-frise">
                    @foreach($parJour as $jour => $evenementsDuJour)
                        <h3 class="dz-jour">
                            {{ $libelleJour(\Carbon\Carbon::parse($jour)) }}
                            <small>{{ $evenementsDuJour->count() }}</small>
                        </h3>
                        <ol class="dz-liste">
                            @foreach($evenementsDuJour as $e)
                                @php
                                    $t = $types[$e['type']] ?? ['icone' => 'fa-circle', 'classe' => ''];
                                    $details = $e['details'];
                                    $statut = $details['Statut'] ?? null;
                                    unset($details['Statut']);
                                    $heure = $e['date']->format('H:i');
                                @endphp
                                <li class="dz-evt {{ $t['classe'] }} {{ $e['lien'] ? 'a-lien' : '' }}">
                                    <span class="dz-noeud" aria-hidden="true"><i class="fas {{ $t['icone'] }}"></i></span>
                                    <div class="dz-carte">
                                        <div class="dz-carte-haut">
                                            <h4 class="dz-titre">
                                                @if($e['lien'])
                                                    <a href="{{ $e['lien'] }}">{{ $e['titre'] }}</a>
                                                @else
                                                    {{ $e['titre'] }}
                                                @endif
                                            </h4>
                                            @if($e['lien'])<span class="dz-ouvrir" aria-hidden="true">Ouvrir</span>@endif
                                            @if($heure !== '00:00')<time class="dz-heure" datetime="{{ $e['date']->toIso8601String() }}">{{ $heure }}</time>@endif
                                        </div>
                                        <div class="dz-details">
                                            <span class="dz-type">{{ $libellesTypes[$e['type']] ?? $e['type'] }}</span>
                                            @if($statut)<span class="dz-statut {{ $tonStatut($statut) }}">{{ $statut }}</span>@endif
                                            @foreach($details as $cle => $valeur)
                                                <span><b>{{ $cle }}</b> {{ $valeur }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    @endforeach
                </div>

                @if(isset($limite) && $totalEvenements > $evenements->count())
                    <div class="dz-plus">
                        <a href="{{ request()->fullUrlWithQuery(['limite' => $limite + 50]) }}" class="dz-bouton">
                            Afficher 50 événements plus anciens
                        </a>
                    </div>
                @endif
            @endif
        </section>
    </div>
</div></div>

<script>
(function () {
    var formulaire = document.getElementById('dz-filtres');
    if (!formulaire) return;
    var puces = formulaire.querySelectorAll('.dz-puce input');

    // Une puce cliquée = le résultat tout de suite, sans bouton « Appliquer ».
    // On garde au moins un type coché : zéro type reviendrait à tout afficher.
    puces.forEach(function (puce) {
        puce.addEventListener('change', function () {
            var cochees = formulaire.querySelectorAll('.dz-puce input:checked').length;
            if (cochees === 0) { puce.checked = true; return; }
            formulaire.submit();
        });
    });
})();
</script>
@endsection
