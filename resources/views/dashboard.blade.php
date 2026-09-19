@extends('layouts.backend')

@php
    $gnf = fn ($m) => number_format((float) $m, 0, ',', ' ');
    $etablissement = \App\Support\Etablissement\IdentiteDocument::courante();
    $maxSemaine = max(1, $semaine->max('consultations'));
    $totalSemaine = $semaine->sum('consultations');
    $heure = (int) now()->format('G');
    $salut = $heure < 12 ? 'Bonjour' : ($heure < 18 ? 'Bon après-midi' : 'Bonsoir');
    $prenom = trim(preg_replace('/^(dr\.?|docteur)\s+/iu', '', explode(' ', (string) auth()->user()?->name)[0] ?? ''));
    $initiales = fn ($p) => $p ? (mb_strtoupper(mb_substr((string) $p->first_name, 0, 1) . mb_substr((string) $p->last_name, 0, 1)) ?: 'P') : '?';
    $statutsRdv = [
        'completed' => ['Honoré', 'hl-s-succes'], 'no_show' => ['Absent', 'hl-s-danger'],
        'cancelled' => ['Annulé', 'hl-s-neutre'], 'confirmed' => ['Confirmé', 'hl-s-info'], 'pending' => ['À confirmer', 'hl-s-alerte'],
    ];

    // Raccourcis selon les droits : chacun voit ses gestes du quotidien.
    $raccourcis = collect([
        ['parcours.accueil', 'parcours.accueil.index', 'fa-door-open', 'Accueil du jour', 'Arrivées et file d\'attente'],
        ['parcours.file', 'parcours.file.index', 'fa-user-md', 'Ma file d\'attente', 'Appeler le patient suivant'],
        ['appointment.view', 'appointment.index', 'fa-calendar-alt', 'Rendez-vous', 'Agenda de la clinique'],
        ['account.facture', \Illuminate\Support\Facades\Route::has('caisse.index') ? 'caisse.index' : 'account.facture', 'fa-cash-register', 'Caisse', 'Encaisser la part patient'],
        ['patient.view', 'patient.index', 'fa-users', 'Patients', 'Rechercher un dossier'],
        ['rapports.view', 'rapports.index', 'fa-chart-pie', 'Rapports', 'Activité et recettes'],
    ])->filter(fn ($r) => auth()->user()?->can($r[0]) && \Illuminate\Support\Facades\Route::has($r[1]))->take(5);
@endphp

@section('style')
<style>
    .tb-salut { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 12px; margin-bottom: 18px; }
    .tb-salut h1 { margin: 0; color: var(--hali-encre); font-size: 1.6rem; font-weight: 750; letter-spacing: -.02em; }
    .tb-salut p { margin: 4px 0 0; color: var(--hali-discret); font-size: .92rem; }
    .tb-raccourcis { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 10px; margin-bottom: 16px; }
    .tb-raccourci { display: flex; align-items: center; gap: 12px; padding: 13px 14px; border: 1px solid var(--hali-bordure); border-radius: var(--hali-rayon); background: #fff; color: var(--hali-encre); text-decoration: none; transition: border-color .12s ease, box-shadow .12s ease, transform .12s ease; }
    .tb-raccourci:hover { border-color: var(--hali-primaire); box-shadow: 0 6px 16px rgba(15, 118, 110, .10); transform: translateY(-1px); color: var(--hali-encre); text-decoration: none; }
    .tb-raccourci i { display: grid; place-items: center; flex: none; width: 40px; height: 40px; border-radius: 11px; background: var(--hali-primaire-pale); color: var(--hali-primaire); font-size: 1rem; }
    .tb-raccourci strong { display: block; font-size: .9rem; }
    .tb-raccourci span { display: block; color: var(--hali-discret); font-size: .76rem; }

    .tb-grille { display: grid; grid-template-columns: minmax(0, 1.25fr) minmax(0, 1fr) minmax(0, .9fr); gap: 16px; align-items: start; }
    .tb-colonne { display: grid; gap: 16px; min-width: 0; }
    .tb-liste { margin: 0; padding: 0; list-style: none; }
    .tb-liste > li { display: flex; align-items: center; gap: 12px; padding: 11px 18px; border-top: 1px solid #f3f4f6; }
    .tb-liste > li:first-child { border-top: 0; }
    .tb-liste .hl-avatar { width: 34px; height: 34px; flex-basis: 34px; border-radius: 9px; font-size: .75rem; }
    .tb-nom { display: block; color: var(--hali-encre); font-weight: 600; font-size: .9rem; }
    .tb-sous { display: block; color: var(--hali-discret); font-size: .78rem; }
    .tb-droite { margin-left: auto; text-align: right; flex: none; }
    .tb-heure { width: 46px; flex: none; color: var(--hali-encre); font-weight: 700; font-variant-numeric: tabular-nums; }
    .tb-attente-longue { color: var(--hali-danger); font-weight: 700; }
    .tb-bloc-pied { padding: 10px 18px; border-top: 1px solid var(--hali-bordure); color: var(--hali-discret); font-size: .82rem; }

    .tb-barres { display: flex; align-items: flex-end; gap: 8px; height: 150px; padding: 18px 18px 12px; }
    .tb-barre { display: flex; flex: 1; flex-direction: column; align-items: center; justify-content: flex-end; gap: 6px; height: 100%; }
    .tb-barre b { color: var(--hali-encre); font-size: .78rem; font-variant-numeric: tabular-nums; }
    .tb-barre .tige { width: 100%; max-width: 36px; min-height: 4px; border-radius: 7px 7px 3px 3px; background: var(--hali-primaire-clair); }
    .tb-barre.est-aujourdhui .tige { background: var(--hali-primaire); }
    .tb-barre small { color: var(--hali-discret); font-size: .72rem; }

    .tb-veille li { justify-content: space-between; font-size: .88rem; }
    .tb-veille a { display: flex; flex: 1; justify-content: space-between; gap: 10px; color: inherit; text-decoration: none; }
    .tb-veille a:hover { color: var(--hali-primaire-fonce); }
    .tb-veille strong { color: var(--hali-encre); font-variant-numeric: tabular-nums; }
    .tb-veille i { width: 18px; color: #9ca3af; text-align: center; }

    @media (max-width: 1399.98px) { .tb-grille { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); } .tb-grille > :last-child { grid-column: 1 / -1; } }
    @media (max-width: 991.98px) { .tb-grille { grid-template-columns: 1fr; } }
</style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">

    <div class="tb-salut">
        <div>
            <h1>{{ $salut }}{{ $prenom ? ', ' . $prenom : '' }}</h1>
            <p>{{ ucfirst(now()->translatedFormat('l d F Y')) }} · {{ $etablissement->nom }}</p>
        </div>
        <span class="hl-maj" title="Chiffres de la journée en cours">Mis à jour à {{ now()->format('H:i') }}</span>
    </div>

    @if($raccourcis->isNotEmpty())
        <nav class="tb-raccourcis" aria-label="Raccourcis">
            @foreach($raccourcis as [$permission, $route, $icone, $titre, $sous])
                <a href="{{ route($route) }}" class="tb-raccourci">
                    <i class="fas {{ $icone }}" aria-hidden="true"></i>
                    <span><strong>{{ $titre }}</strong><span>{{ $sous }}</span></span>
                </a>
            @endforeach
        </nav>
    @endif

    {{-- ---------------------------------------------------------- La journée --}}
    <div class="hl-kpis">
        <div class="hl-bloc hl-kpi">
            <span class="hl-kpi-libelle">Patients reçus</span>
            <span class="hl-kpi-valeur">{{ $visites->count() }}</span>
            <span class="hl-kpi-detail">{{ $terminees }} vus · {{ $enAttente->count() }} en attente · {{ $enConsultation->count() }} en consultation</span>
        </div>
        <div class="hl-bloc hl-kpi {{ ($attenteMoyenne ?? 0) > 45 ? 'est-alerte' : '' }}">
            <span class="hl-kpi-libelle">Attente moyenne</span>
            <span class="hl-kpi-valeur">{{ $attenteMoyenne !== null ? $attenteMoyenne : '—' }} @if($attenteMoyenne !== null)<small>min</small>@endif</span>
            <span class="hl-kpi-detail">{{ ($attenteMoyenne ?? 0) > 45 ? 'au-delà de 45 min : à surveiller' : 'entre l\'arrivée et l\'appel' }}</span>
        </div>
        <div class="hl-bloc hl-kpi">
            <span class="hl-kpi-libelle">Encaissé aujourd'hui</span>
            <span class="hl-kpi-valeur">{{ $gnf($encaisseCejour) }} <small>GNF</small></span>
        </div>
        <div class="hl-bloc hl-kpi {{ $resteAEncaisser > 0 ? 'est-alerte' : '' }}">
            <span class="hl-kpi-libelle">Reste à encaisser</span>
            <span class="hl-kpi-valeur">{{ $gnf($resteAEncaisser) }} <small>GNF</small></span>
            <span class="hl-kpi-detail">parts patient des factures du jour</span>
        </div>
    </div>

    <div class="tb-grille">
        {{-- ------------------------------------------------ Salle d'attente --}}
        <div class="tb-colonne">
            <section class="hl-bloc">
                <h2 class="hl-bloc-titre">Salle d'attente <small>{{ $enAttente->count() }} patient{{ $enAttente->count() > 1 ? 's' : '' }}</small>
                    @can('parcours.accueil')<a href="{{ route('parcours.accueil.index') }}" style="margin-left:auto; font-size:.82rem; font-weight:600">Ouvrir l'accueil</a>@endcan</h2>
                @if($enAttente->isEmpty())
                    <div class="hl-vide" style="padding:26px"><i class="fas fa-mug-hot" aria-hidden="true" style="color:#a7f3d0"></i>Personne n'attend.</div>
                @else
                    <ol class="tb-liste">
                        @foreach($enAttente->take(7) as $visite)
                            <li>
                                <span class="hl-avatar {{ $visite->urgence ? 'est-urgent' : '' }}" aria-hidden="true">{{ $initiales($visite->patient) }}</span>
                                <div style="min-width:0">
                                    <span class="tb-nom">{{ $visite->patient?->full_name }}</span>
                                    <span class="tb-sous">{{ $visite->motif ?: 'Consultation' }}@if($visite->medecin) · {{ $visite->medecin->nom_affiche }}@endif</span>
                                </div>
                                <div class="tb-droite">
                                    <span class="{{ $visite->minutesAttente() > 45 ? 'tb-attente-longue' : 'tb-nom' }}" style="font-size:.88rem">{{ $visite->minutesAttente() }} min</span>
                                    <span class="tb-sous">arrivé à {{ $visite->arrivee_le->format('H:i') }}</span>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                    @if($enAttente->count() > 7)
                        <div class="tb-bloc-pied">et {{ $enAttente->count() - 7 }} autre{{ $enAttente->count() - 7 > 1 ? 's' : '' }}…</div>
                    @endif
                @endif
            </section>

            <section class="hl-bloc">
                <h2 class="hl-bloc-titre">Consultations des 7 derniers jours <small>{{ $totalSemaine }} au total</small></h2>
                <div class="tb-barres" role="img" aria-label="Consultations par jour sur les 7 derniers jours">
                    @foreach($semaine as $jour)
                        <div class="tb-barre {{ $jour['date']->isToday() ? 'est-aujourdhui' : '' }}">
                            <b>{{ $jour['consultations'] }}</b>
                            <span class="tige" style="height: {{ max(4, (int) round($jour['consultations'] / $maxSemaine * 100)) }}px"></span>
                            <small>{{ $jour['date']->isToday() ? 'Auj.' : ucfirst($jour['date']->translatedFormat('D')) }}</small>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>

        {{-- ------------------------------------------------ Rendez-vous --}}
        <section class="hl-bloc">
            <h2 class="hl-bloc-titre">Rendez-vous du jour <small>{{ $rdvDuJour->count() }}</small>
                @can('appointment.view')<a href="{{ route('appointment.index') }}" style="margin-left:auto; font-size:.82rem; font-weight:600">Agenda</a>@endcan</h2>
            @if($rdvDuJour->isEmpty())
                <div class="hl-vide" style="padding:26px"><i class="fas fa-calendar-check" aria-hidden="true" style="color:#a7f3d0"></i>Aucun rendez-vous aujourd'hui.</div>
            @else
                <ul class="tb-liste">
                    @foreach($rdvDuJour->take(9) as $rdv)
                        @php([$libelle, $ton] = $statutsRdv[$rdv->status] ?? ['À confirmer', 'hl-s-alerte'])
                        <li>
                            {{-- CORRIGÉ : l'heure affichait « 2026- » (début de la date complète). --}}
                            <span class="tb-heure">{{ $rdv->appointment_time?->format('H:i') }}</span>
                            <div style="min-width:0">
                                <span class="tb-nom">{{ $rdv->patient?->full_name }}</span>
                                <span class="tb-sous">{{ $rdv->motifRdv?->nom ?? 'Consultation' }}@if($rdv->employee) · {{ $rdv->employee->nom_affiche }}@endif</span>
                            </div>
                            <span class="tb-droite"><span class="hl-statut {{ $ton }}">{{ $libelle }}</span></span>
                        </li>
                    @endforeach
                </ul>
                @if($rdvHonores || $rdvAbsents || $rdvDuJour->count() > 9)
                    <div class="tb-bloc-pied">
                        {{ $rdvHonores }} honoré{{ $rdvHonores > 1 ? 's' : '' }}@if($rdvAbsents) · <span style="color:var(--hali-danger)">{{ $rdvAbsents }} absent{{ $rdvAbsents > 1 ? 's' : '' }}</span>@endif
                        @if($rdvDuJour->count() > 9) · et {{ $rdvDuJour->count() - 9 }} autres @endif
                    </div>
                @endif
            @endif
        </section>

        {{-- ------------------------------------------------ À surveiller --}}
        <div class="tb-colonne">
            <section class="hl-bloc">
                <h2 class="hl-bloc-titre">À surveiller</h2>
                <ul class="tb-liste tb-veille">
                    @can('assurance.creances.view')
                        <li><a href="{{ route('assurance.creances.index') }}"><span><i class="fas fa-shield-alt" aria-hidden="true"></i> Dû par les assurances</span><strong>{{ $gnf($creancesAssurance) }}</strong></a></li>
                        @if($reclamationsAEnvoyer > 0)
                            <li><a href="{{ route('assurance.creances.index') }}"><span><i class="fas fa-paper-plane" aria-hidden="true"></i> Réclamations à envoyer</span><strong style="color:var(--hali-alerte)">{{ $reclamationsAEnvoyer }}</strong></a></li>
                        @endif
                    @endcan
                    @module('hospitalisation')
                        @can('hospitalisation.view')
                            <li><a href="{{ route('hospitalisations.index') }}"><span><i class="fas fa-procedures" aria-hidden="true"></i> Hospitalisés</span><strong>{{ $hospitalisesActifs }}</strong></a></li>
                            <li><a href="{{ route('hospitalisations.index') }}"><span><i class="fas fa-bed" aria-hidden="true"></i> Chambres libres</span><strong style="{{ $chambresLibres ? '' : 'color:var(--hali-danger)' }}">{{ $chambresLibres }}</strong></a></li>
                        @endcan
                    @endmodule
                    @module('laboratoire')
                        <li><span><span><i class="fas fa-flask" aria-hidden="true"></i> Analyses en cours</span></span><strong>{{ $laboEnCours }}</strong></li>
                    @endmodule
                    <li><span><span><i class="fas fa-user-plus" aria-hidden="true"></i> Nouveaux patients</span></span><strong>{{ $nouveauxPatients }}</strong></li>
                </ul>
            </section>

            <section class="hl-bloc">
                <h2 class="hl-bloc-titre">Derniers patients</h2>
                @if($derniersPatients->isEmpty())
                    <div class="hl-vide" style="padding:22px">Aucun patient enregistré.</div>
                @else
                    <ul class="tb-liste">
                        @foreach($derniersPatients as $patient)
                            <li>
                                <span class="hl-avatar" aria-hidden="true">{{ $initiales($patient) }}</span>
                                <div style="min-width:0">
                                    @can('parcours.dossier')
                                        <a href="{{ route('parcours.dossier.show', $patient->id) }}" class="tb-nom">{{ $patient->full_name }}</a>
                                    @else
                                        <span class="tb-nom">{{ $patient->full_name }}</span>
                                    @endcan
                                    <span class="tb-sous">{{ $patient->gender }}@if($patient->age !== null) · {{ $patient->age }} ans @endif</span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </div>
</div></div>
@endsection
