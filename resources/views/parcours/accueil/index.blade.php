@extends('layouts.backend')

@php $gnf = fn ($m) => number_format((float) $m, 0, ',', ' '); @endphp

@section('style')
<style>
    /* ------------------------------------------------ Accueil du jour (ac-) */
    .ac-bloc { margin-bottom: 16px; }
    .ac-titre { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 12px; margin: 0; padding: 14px 18px; border-bottom: 1px solid var(--hali-bordure); color: var(--hali-encre); font-size: .95rem; font-weight: 650; }
    .ac-compte { display: inline-grid; place-items: center; min-width: 22px; height: 22px; padding: 0 6px; border-radius: 999px; background: #f3f4f6; color: var(--hali-texte); font-size: .75rem; font-weight: 700; }

    /* Rendez-vous à accueillir */
    .ac-rdv { display: grid; grid-template-columns: 56px minmax(0, 1fr) auto; align-items: center; gap: 12px; padding: 11px 18px; border-top: 1px solid #f3f4f6; }
    .ac-rdv:first-of-type { border-top: 0; }
    .ac-rdv-heure { color: var(--hali-encre); font-size: 1rem; font-weight: 700; font-variant-numeric: tabular-nums; }
    .ac-rdv-heure.est-retard { color: var(--hali-alerte); }
    .ac-rdv-nom { color: var(--hali-encre); font-weight: 600; }
    .ac-rdv-meta { color: var(--hali-discret); font-size: .84rem; }
    .ac-rdv-actions { display: flex; gap: 8px; }
    .ac-rdv-actions form { margin: 0; }

    /* Réglage de l'ordre de passage */
    .ac-ordre { display: flex; align-items: center; gap: 8px; margin: 0 0 0 auto; font-size: .83rem; font-weight: 500; }
    .ac-ordre span { color: var(--hali-discret); }
    .ac-ordre select { width: auto; min-height: 34px; padding-top: 0; padding-bottom: 0; font-size: .83rem; }

    /* Tableau de la file */
    .ac-zone { overflow-x: auto; }
    @media (min-width: 1200px) { .ac-zone { overflow: visible; } }
    .ac-table { width: 100%; border-collapse: collapse; font-size: .88rem; }
    .ac-table th { padding: 10px 14px; background: #fafbfc; border-bottom: 1px solid var(--hali-bordure); color: var(--hali-discret); font-size: .78rem; font-weight: 600; text-align: left; white-space: nowrap; }
    .ac-table td { padding: 11px 14px; border-top: 1px solid #f3f4f6; vertical-align: middle; }
    .ac-table tbody tr:hover > td { background: var(--hali-primaire-pale); }
    .ac-table tr.table-danger > td { background: var(--hali-danger-pale); }
    .ac-table tr.table-danger > td:first-child { box-shadow: inset 3px 0 0 var(--hali-danger); }

    .ac-groupe th { padding: 14px 14px 8px; background: #fff; border-bottom: 0; color: var(--hali-encre); font-size: .85rem; font-weight: 700; }
    .ac-groupe:not(:first-child) th { border-top: 1px solid var(--hali-bordure); }
    .ac-groupe button { margin-left: 8px; padding: 0; border: 0; background: none; color: var(--hali-primaire); font-size: .8rem; font-weight: 600; cursor: pointer; }

    .ac-heure { color: var(--hali-encre); font-weight: 600; font-variant-numeric: tabular-nums; }
    .ac-attente { display: block; color: var(--hali-discret); font-size: .78rem; }
    .ac-attente.est-longue { color: var(--hali-alerte); font-weight: 650; }
    .ac-patient { display: flex; align-items: center; gap: 10px; min-width: 190px; }
    .ac-patient .hl-avatar { width: 34px; height: 34px; flex-basis: 34px; font-size: .75rem; border-radius: 9px; }
    .ac-patient strong { display: block; color: var(--hali-encre); font-weight: 600; white-space: nowrap; }
    .ac-patient .ac-motif { display: block; max-width: 220px; overflow: hidden; color: var(--hali-discret); font-size: .8rem; text-overflow: ellipsis; white-space: nowrap; }
    .ac-medecin { white-space: nowrap; color: var(--hali-texte); }
    .ac-constantes { min-width: 180px; max-width: 260px; }
    .ac-saisir { padding: 0; border: 0; background: none; color: var(--hali-primaire); font-size: .8rem; font-weight: 600; cursor: pointer; }
    .ac-saisir:hover { text-decoration: underline; }

    .ac-actions { white-space: nowrap; text-align: right; }
    .ac-actions form { display: inline; margin: 0; }
    .ac-icone {
        display: inline-grid; place-items: center; width: 32px; height: 32px; margin-left: 2px;
        border: 1px solid var(--hali-bordure); border-radius: 8px; background: #fff; color: var(--hali-texte); cursor: pointer;
    }
    .ac-icone:hover { border-color: var(--hali-primaire); color: var(--hali-primaire-fonce); background: var(--hali-primaire-pale); }
    .ac-icone.est-fort { border-color: var(--hali-primaire); color: var(--hali-primaire); }
    .ac-plus { position: relative; display: inline-block; }
    .ac-plus > summary { list-style: none; }
    .ac-plus > summary::-webkit-details-marker { display: none; }
    .ac-plus-menu {
        position: absolute; right: 0; top: calc(100% + 6px); z-index: 30; width: 290px; padding: 12px;
        border: 1px solid var(--hali-bordure); border-radius: 10px; background: #fff; box-shadow: var(--hali-ombre-forte);
        text-align: left; white-space: normal;
    }
    .ac-plus-menu form { display: block !important; }
    .ac-plus-menu + .ac-plus-menu { margin-top: 10px; }
    .ac-plus-titre { margin: 0 0 6px; color: var(--hali-discret); font-size: .76rem; font-weight: 650; }
    .ac-plus-menu hr { margin: 10px 0; }

    .ac-vide { padding: 40px 20px; color: var(--hali-discret); text-align: center; }
    .ac-vide i { display: block; margin-bottom: 8px; font-size: 1.6rem; color: #d1d5db; }

    @media (max-width: 767.98px) {
        .ac-rdv { grid-template-columns: 48px minmax(0, 1fr); }
        .ac-rdv-actions { grid-column: 1 / -1; }
        .ac-ordre { margin-left: 0; width: 100%; flex-wrap: wrap; }
    }
</style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">
    @php
        $estEnAttente = fn ($v) => $v->statut->value === 'en_attente';
        $estEnConsultation = fn ($v) => $v->statut->value === 'en_consultation';
        $enAttente = $visites->filter($estEnAttente);
        $enConsultation = $visites->filter($estEnConsultation);
        $terminees = $visites->filter(fn ($v) => $v->statut->value === 'terminee');
        $fermees = $visites->reject(fn ($v) => $estEnAttente($v) || $estEnConsultation($v));
        $plusLongueAttente = $enAttente->max(fn ($v) => $v->minutesAttente());

        $initiales = function ($nom) {
            $noms = preg_split('/\s+/', trim((string) $nom));
            return mb_strtoupper(mb_substr($noms[0] ?? '', 0, 1) . mb_substr(end($noms) ?: '', 0, 1));
        };

        // La file se lit par groupes : qui attend, qui est avec un médecin, qui est parti.
        $groupes = [
            ['cle' => 'attente', 'titre' => 'En attente', 'visites' => $enAttente, 'replie' => false],
            ['cle' => 'consultation', 'titre' => 'En consultation', 'visites' => $enConsultation, 'replie' => false],
            ['cle' => 'fermees', 'titre' => 'Terminées ou reparties', 'visites' => $fermees, 'replie' => true],
        ];
    @endphp

    <header class="hl-entete">
        <div>
            <h1>Accueil du jour</h1>
            <p>{{ ucfirst(today()->translatedFormat('l d F Y')) }} · <span class="hl-maj" title="La page se met à jour toute seule chaque minute">actualisé à {{ now()->format('H:i') }}</span></p>
        </div>
        <div class="hl-entete-actions">
            <button type="button" class="hl-bouton hl-bouton-plein" data-bs-toggle="modal" data-bs-target="#modalArrivee">
                <i class="fa fa-user-plus" aria-hidden="true"></i> Patient sans rendez-vous
            </button>
            @can('parcours.accueil')
                <a href="{{ route('parcours.salle-attente.index') }}" target="_blank" class="hl-bouton">
                    <i class="fa fa-tv" aria-hidden="true"></i> Écran salle d'attente
                </a>
            @endcan
        </div>
    </header>

    {{-- La journée en un regard : ce que l'accueil doit savoir sans lire le tableau. --}}
    <div class="hl-kpis">
        <div class="hl-bloc hl-kpi">
            <span class="hl-kpi-libelle">Rendez-vous à accueillir</span>
            <span class="hl-kpi-valeur">{{ $rendezVous->count() }}</span>
            <span class="hl-kpi-detail">{{ $rendezVous->isEmpty() ? 'Tous les rendez-vous sont arrivés' : 'encore attendus aujourd\'hui' }}</span>
        </div>
        <div class="hl-bloc hl-kpi {{ ($plusLongueAttente ?? 0) > 45 ? 'est-alerte' : '' }}">
            <span class="hl-kpi-libelle">En attente</span>
            <span class="hl-kpi-valeur">{{ $enAttente->count() }}</span>
            <span class="hl-kpi-detail">{{ $plusLongueAttente ? 'Plus longue attente : ' . $plusLongueAttente . ' min' : 'Personne n\'attend' }}</span>
        </div>
        <div class="hl-bloc hl-kpi">
            <span class="hl-kpi-libelle">En consultation</span>
            <span class="hl-kpi-valeur">{{ $enConsultation->count() }}</span>
            <span class="hl-kpi-detail">avec un médecin</span>
        </div>
        <div class="hl-bloc hl-kpi">
            <span class="hl-kpi-libelle">Terminées</span>
            <span class="hl-kpi-valeur">{{ $terminees->count() }}</span>
            <span class="hl-kpi-detail">patients vus aujourd'hui</span>
        </div>
    </div>

    {{-- ------------------------------------------------ Rendez-vous à accueillir --}}
    @if($rendezVous->isNotEmpty())
        <section class="hl-bloc ac-bloc">
            <h2 class="ac-titre">Rendez-vous à accueillir <span class="ac-compte">{{ $rendezVous->count() }}</span></h2>
            @foreach($rendezVous as $rdv)
                @php $enRetard = $rdv->appointment_time && now()->gt(today()->setTimeFrom($rdv->appointment_time)->addHour()); @endphp
                <div class="ac-rdv">
                    <span class="ac-rdv-heure {{ $enRetard ? 'est-retard' : '' }}">{{ $rdv->appointment_time?->format('H:i') }}</span>
                    <div>
                        <div class="ac-rdv-nom">{{ $rdv->patient?->full_name }}</div>
                        <div class="ac-rdv-meta">
                            {{ $rdv->employee?->nom_affiche }} · {{ $rdv->motifRdv?->nom ?? 'Consultation' }}
                            @if($enRetard) · <span style="color: var(--hali-alerte)">plus d'une heure de retard</span>@endif
                        </div>
                    </div>
                    <div class="ac-rdv-actions">
                        <form method="POST" action="{{ route('parcours.accueil.arrivee-rdv', $rdv) }}">@csrf
                            <button class="hl-bouton hl-bouton-plein" title="Patient arrivé"><i class="fa fa-check" aria-hidden="true"></i> Arrivé</button></form>
                        @if($enRetard)
                            <form method="POST" action="{{ route('parcours.accueil.absent', $rdv) }}" onsubmit="return confirm('Marquer ce rendez-vous absent ?');">@csrf
                                <button class="hl-bouton" title="Absent">Absent</button></form>
                        @endif
                    </div>
                </div>
            @endforeach
        </section>
    @endif

    {{-- ------------------------------------------------ File du jour --}}
    <section class="hl-bloc ac-bloc">
        <div class="ac-titre">
            <h2 style="margin:0; font-size:inherit; font-weight:inherit; color:inherit">File d'attente du jour</h2>
            <span class="ac-compte">{{ $visites->count() }}</span>
            @php $ordreActuel = \App\Support\EtablissementContext::current()?->ordre_file ?? 'arrivee'; @endphp
            <form method="POST" action="{{ route('parcours.accueil.reglage-ordre') }}" class="ac-ordre" id="acOrdre">@csrf
                <span>Les patients passent</span>
                <select name="ordre_file" class="form-control" aria-label="Ordre de passage">
                    <option value="arrivee" @selected($ordreActuel === 'arrivee')>dans l'ordre d'arrivée</option>
                    <option value="rendez_vous" @selected($ordreActuel === 'rendez_vous')>rendez-vous d'abord</option>
                </select>
                <noscript><button class="hl-bouton">Appliquer</button></noscript>
            </form>
        </div>

        @if($visites->isEmpty())
            <div class="ac-vide">
                <i class="fas fa-door-open" aria-hidden="true"></i>
                Aucun patient arrivé aujourd'hui.<br>
                Cliquez « Arrivé » sur un rendez-vous, ou « Patient sans rendez-vous ».
            </div>
        @else
            <div class="ac-zone">
                <table class="ac-table">
                    <thead><tr><th>Arrivée</th><th>Patient</th><th>Médecin</th><th>Constantes</th><th>Caisse</th><th>Statut</th><th><span class="sr-only visually-hidden">Actions</span></th></tr></thead>
                    @foreach($groupes as $groupe)
                        @continue($groupe['visites']->isEmpty())
                        <tbody>
                            <tr class="ac-groupe">
                                <th colspan="7">
                                    {{ $groupe['titre'] }} <span class="ac-compte">{{ $groupe['visites']->count() }}</span>
                                    @if($groupe['replie'])
                                        <button type="button" class="js-deplier" data-cible="groupe-{{ $groupe['cle'] }}" aria-expanded="false">Afficher</button>
                                    @endif
                                </th>
                            </tr>
                        </tbody>
                        <tbody id="groupe-{{ $groupe['cle'] }}" @if($groupe['replie']) hidden @endif>
                        @foreach($groupe['visites'] as $v)
                            @php $transaction = $v->consultation?->transaction; @endphp
                            <tr class="{{ $v->urgence && $v->statut->estActive() ? 'table-danger' : '' }}">
                                <td>
                                    <span class="ac-heure">{{ $v->arrivee_le->format('H:i') }}</span>
                                    @if($v->statut === \App\Enums\Parcours\StatutVisite::EnAttente)
                                        <span class="ac-attente {{ $v->minutesAttente() > 45 ? 'est-longue' : '' }}">{{ $v->minutesAttente() }} min</span>
                                    @endif
                                    @if($v->rang !== null)<span class="hl-statut hl-s-neutre" title="Ordre imposé par l'accueil">ordre manuel</span>@endif
                                </td>
                                <td>
                                    <div class="ac-patient">
                                        <span class="hl-avatar {{ $v->urgence ? 'est-urgent' : '' }}" aria-hidden="true">{{ $initiales($v->patient->full_name) }}</span>
                                        <div style="min-width:0">
                                            <strong>{{ $v->patient->full_name }}</strong>
                                            <span class="ac-motif" title="{{ $v->motif }}">{{ $v->urgence ? 'Urgence · ' : '' }}{{ $v->motif }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="ac-medecin">{{ $v->medecin->nom_affiche }}</td>
                                <td class="ac-constantes">
                                    @include('parcours.partials.constantes', ['c' => $v->derniereConstante])
                                    @if($v->statut->estActive())
                                        @can('parcours.constantes')
                                            <div><button type="button" class="ac-saisir" data-bs-toggle="modal" data-bs-target="#modalConstantes{{ $v->id }}">{{ $v->derniereConstante ? 'Reprendre' : 'Saisir' }}</button></div>
                                        @endcan
                                    @endif
                                </td>
                                <td style="white-space:nowrap">
                                    @if($transaction)
                                        @if($transaction->status === 'paid' || $transaction->status === 'approved')
                                            <span class="hl-statut hl-s-succes">Réglé</span>
                                        @else
                                            <a href="{{ route('consultation.show', $v->consultation) }}" class="hl-statut hl-s-alerte" title="Ouvrir pour encaisser">À encaisser {{ $gnf($transaction->total) }}</a>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td><span class="badge badge-{{ $v->statut->badge() }}">{{ $v->statut->libelle() }}</span></td>
                                <td class="ac-actions">
                                    @if($v->statut === \App\Enums\Parcours\StatutVisite::EnAttente)
                                        <form method="POST" action="{{ route('parcours.accueil.prioriser', $v) }}">@csrf
                                            <button class="ac-icone est-fort" title="Faire passer maintenant" aria-label="Faire passer maintenant"><i class="fa fa-angle-double-up"></i></button></form>
                                        <form method="POST" action="{{ route('parcours.accueil.deplacer', $v) }}">@csrf
                                            <input type="hidden" name="direction" value="haut">
                                            <button class="ac-icone" title="Monter d'une place" aria-label="Monter d'une place"><i class="fa fa-arrow-up"></i></button></form>
                                        <form method="POST" action="{{ route('parcours.accueil.deplacer', $v) }}">@csrf
                                            <input type="hidden" name="direction" value="bas">
                                            <button class="ac-icone" title="Descendre d'une place" aria-label="Descendre d'une place"><i class="fa fa-arrow-down"></i></button></form>
                                        <details class="ac-plus">
                                            <summary class="ac-icone" title="Autres actions" aria-label="Autres actions"><i class="fa fa-ellipsis-h"></i></summary>
                                            <div class="ac-plus-menu">
                                                <p class="ac-plus-titre">Changer de médecin</p>
                                                <form method="POST" action="{{ route('parcours.accueil.transferer', $v) }}" class="d-flex gap-1">@csrf
                                                    <select name="medecin_id" class="form-control form-control-sm">
                                                        @foreach($medecins as $m)<option value="{{ $m->id }}" @selected($m->id === $v->medecin_id)>{{ $m->nom_affiche }}</option>@endforeach
                                                    </select>
                                                    <button class="hl-bouton">Transférer</button>
                                                </form>
                                                <form method="POST" action="{{ route('parcours.accueil.ordre-defaut', $v) }}" class="mt-2">@csrf
                                                    <button class="hl-bouton w-100">Revenir à l'ordre de la clinique</button></form>
                                                <hr>
                                                <p class="ac-plus-titre">Le patient est reparti</p>
                                                <form method="POST" action="{{ route('parcours.accueil.partie', $v) }}" onsubmit="return confirm('Le patient est reparti sans consulter ?');">@csrf
                                                    <input name="motif" class="form-control form-control-sm mb-1" maxlength="255" placeholder="Motif (facultatif)">
                                                    <label class="small d-block mb-2" style="font-weight:500"><input type="hidden" name="annuler_facture" value="0"><input type="checkbox" name="annuler_facture" value="1" checked> Annuler la facture de l'acte (si rien n'est encaissé)</label>
                                                    <button class="hl-bouton w-100" style="color: var(--hali-danger); border-color:#fecaca">Reparti sans consulter</button></form>
                                            </div>
                                        </details>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    @endforeach
                </table>
            </div>
        @endif
    </section>
</div></div>

@can('parcours.constantes')
    @foreach($visites->filter(fn ($v) => $v->statut->estActive()) as $v)
        <div class="modal fade" id="modalConstantes{{ $v->id }}" tabindex="-1"><div class="modal-dialog">
            <form method="POST" action="{{ route('parcours.accueil.constantes', $v) }}" class="modal-content">@csrf
                <div class="modal-header"><h5 class="modal-title">Constantes — {{ $v->patient->full_name }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <p class="small text-muted">Remplir seulement ce qui a été mesuré. La taille d'un adulte est reprise de la dernière mesure.</p>
                    <div class="row g-2">
                        <div class="col-4"><label class="form-label small">Température (°C)</label><input type="number" step="0.1" name="temperature" class="form-control" inputmode="decimal"></div>
                        <div class="col-4"><label class="form-label small">TA systolique</label><input type="number" name="tension_systolique" class="form-control" inputmode="numeric" placeholder="120"></div>
                        <div class="col-4"><label class="form-label small">TA diastolique</label><input type="number" name="tension_diastolique" class="form-control" inputmode="numeric" placeholder="80"></div>
                        <div class="col-4"><label class="form-label small">Pouls (bpm)</label><input type="number" name="pouls" class="form-control" inputmode="numeric"></div>
                        <div class="col-4"><label class="form-label small">SpO₂ (%)</label><input type="number" name="saturation_o2" class="form-control" inputmode="numeric"></div>
                        <div class="col-4"><label class="form-label small">Fréq. resp.</label><input type="number" name="frequence_respiratoire" class="form-control" inputmode="numeric"></div>
                        <div class="col-4"><label class="form-label small">Poids (kg)</label><input type="number" step="0.01" name="poids_kg" class="form-control" inputmode="decimal"></div>
                        <div class="col-4"><label class="form-label small">Taille (cm)</label><input type="number" step="0.1" name="taille_cm" class="form-control" inputmode="decimal"></div>
                        <div class="col-4"><label class="form-label small">Glycémie (g/L)</label><input type="number" step="0.01" name="glycemie" class="form-control" inputmode="decimal"></div>
                        @if($v->patient->gender === 'Femme')
                            <div class="col-6"><label class="form-label small">Date des dernières règles</label><input type="date" name="ddr" class="form-control" max="{{ today()->toDateString() }}"></div>
                        @endif
                        <div class="col-12"><input name="notes" class="form-control form-control-sm" maxlength="255" placeholder="Remarque (facultatif)"></div>
                    </div>
                </div>
                <div class="modal-footer"><button class="btn btn-primary">Enregistrer</button></div>
            </form>
        </div></div>
    @endforeach
@endcan

{{-- Le formulaire d'arrivée ne sert que par intermittence : il ouvre une
     fenêtre, et la file d'attente garde toute la largeur de l'écran. --}}
<div class="modal fade" id="modalArrivee" tabindex="-1"><div class="modal-dialog modal-lg">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Patient sans rendez-vous</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <form method="POST" action="{{ route('parcours.accueil.arrivee') }}" class="row g-2">@csrf
                        <div class="col-12">@include('assurance.partials.choix-patient', ['id' => 'visite', 'libelle' => 'Patient', 'url' => route('parcours.patients.recherche')])
                            <a href="{{ route('patient.index') }}" class="small">Nouveau patient ?</a></div>
                        <div class="col-md-6"><label class="form-label small">Médecin *</label>
                            <select name="medecin_id" class="form-control form-control-sm" required>
                                @foreach($medecins as $m)<option value="{{ $m->id }}">{{ $m->nom_affiche }}</option>@endforeach
                            </select></div>
                        <div class="col-md-6"><label class="form-label small">Motif</label>
                            <select name="motif_rdv_id" class="form-control form-control-sm js-motif">
                                <option value="">Consultation</option>
                                @foreach($motifs as $motif)<option value="{{ $motif->id }}" data-service="{{ $motif->service_id }}">{{ $motif->nom }}</option>@endforeach
                            </select></div>
                        <div class="col-md-8"><label class="form-label small">Acte à facturer</label>
                            <select name="service_id" class="form-control form-control-sm js-service">
                                <option value="">— Aucun pour l'instant —</option>
                                @foreach($services as $s)<option value="{{ $s->id }}">{{ $s->name }} — {{ $gnf($s->amount) }} GNF</option>@endforeach
                            </select></div>
                        <div class="col-md-4 pt-4"><input type="hidden" name="urgence" value="0">
                            <label class="text-danger small"><input type="checkbox" name="urgence" value="1"> Urgence</label></div>
                        <div class="col-12"><input name="notes_accueil" class="form-control form-control-sm" maxlength="1000" placeholder="Précision (facultatif) : fièvre depuis 3 jours…"></div>
                        <div class="col-12"><button class="btn btn-primary btn-sm w-100">Ajouter à la file d'attente</button></div>
                    </form>
        </div>
    </div>
</div></div>

@endsection

@section('script')
    @include('assurance.partials.choix-patient-script')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Ordre de passage : appliqué dès qu'on le change.
        var ordre = document.getElementById('acOrdre');
        if (ordre) ordre.querySelector('select').addEventListener('change', function () { ordre.submit(); });

        // Groupe replié (terminées ou reparties).
        document.querySelectorAll('.js-deplier').forEach(function (bouton) {
            bouton.addEventListener('click', function () {
                var cible = document.getElementById(bouton.dataset.cible);
                cible.hidden = !cible.hidden;
                bouton.setAttribute('aria-expanded', String(!cible.hidden));
                bouton.textContent = cible.hidden ? 'Afficher' : 'Masquer';
            });
        });

        // Un seul menu « … » ouvert à la fois, fermé par un clic ailleurs.
        document.addEventListener('click', function (e) {
            document.querySelectorAll('details.ac-plus[open]').forEach(function (menu) {
                if (!menu.contains(e.target)) menu.removeAttribute('open');
            });
        });

        // Actualisation toutes les minutes, sauf pendant une saisie.
        setInterval(function () {
            if (document.hidden) return;
            if (document.querySelector('details[open], .modal.show')) return;
            var actif = document.activeElement;
            if (actif && /INPUT|TEXTAREA|SELECT/.test(actif.tagName)) return;
            window.location.reload();
        }, 60000);

        var motif = document.querySelector('.js-motif'), service = document.querySelector('.js-service');
        if (!motif || !service) return;
        motif.addEventListener('change', function () {
            var s = motif.options[motif.selectedIndex].dataset.service;
            if (s) service.value = s;
        });
    });
    </script>
@endsection
