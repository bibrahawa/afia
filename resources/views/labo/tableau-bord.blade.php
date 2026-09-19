@extends('layouts.backend')
@section('style')
    @include('labo.partials.styles')
    <style>
        .tb-grille { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 16px; align-items: start; }
        .tb-colonne { display: grid; gap: 16px; }
        .tb-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin-bottom: 16px; }
        .tb-kpi { display: grid; gap: 6px; padding: 16px 18px; text-decoration: none; transition: border-color .12s ease, box-shadow .12s ease; }
        .tb-kpi:hover { border-color: var(--hali-primaire); box-shadow: 0 4px 14px rgba(15, 118, 110, .10); text-decoration: none; }
        .tb-kpi-haut { display: flex; align-items: center; justify-content: space-between; color: var(--hali-discret); font-size: .83rem; font-weight: 600; }
        .tb-kpi-haut i { color: #9ca3af; }
        .tb-kpi b { color: var(--hali-encre); font-size: 1.9rem; font-weight: 700; line-height: 1.1; font-variant-numeric: tabular-nums; }
        .tb-kpi small { color: var(--hali-primaire); font-size: .78rem; font-weight: 600; }
        .tb-kpi.est-alerte b { color: var(--hali-alerte); }
        .tb-kpi.est-danger { border-color: #fecaca; background: #fffafa; }
        .tb-kpi.est-danger b, .tb-kpi.est-danger .tb-kpi-haut i { color: var(--hali-danger); }

        .tb-liste { margin: 0; padding: 0; list-style: none; }
        .tb-liste li { display: flex; align-items: center; gap: 12px; padding: 12px 18px; border-top: 1px solid #f3f4f6; }
        .tb-liste li:first-child { border-top: 0; }
        .tb-liste strong { color: var(--hali-encre); }
        .tb-sous { display: block; color: var(--hali-discret); font-size: .8rem; }
        .tb-liste .hl-bouton { margin-left: auto; min-height: 32px; }
        .tb-critique .hl-bloc-titre { color: var(--hali-danger); }
        .tb-critique { border-color: #fecaca; }
        @media (max-width: 1199.98px) { .tb-grille { grid-template-columns: 1fr; } }
    </style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">
    @include('labo.partials.entete', ['titre' => 'Tableau de bord', 'sousTitre' => 'Ce qui attend le laboratoire, étape par étape.'])

    @if($catalogueVide)
        <div class="hl-note hl-note-alerte mb-3" style="align-items:center">
            <i class="fas fa-book-medical" aria-hidden="true"></i>
            <span>Le catalogue d'examens est vide. Importez le catalogue modèle pour démarrer, puis ajustez prix et normes.</span>
            @can('labo.catalogue.manage')
                <form method="POST" action="{{ route('labo.catalogue.importer') }}" style="margin-left:auto">@csrf
                    <button class="hl-bouton hl-bouton-plein">Importer le catalogue modèle</button>
                </form>
            @endcan
        </div>
    @endif

    @if($compteurs['mdo_a_declarer'] > 0)
        @can('labo.validation.biologique')
            <div class="hl-note hl-note-alerte mb-3" style="align-items:center">
                <i class="fas fa-bullhorn" aria-hidden="true"></i>
                <span><strong>{{ $compteurs['mdo_a_declarer'] }}</strong> maladie{{ $compteurs['mdo_a_declarer'] > 1 ? 's' : '' }} à déclaration obligatoire en attente de déclaration.</span>
                <a href="{{ route('labo.declarations.index') }}" class="hl-bouton" style="margin-left:auto">Déclarer</a>
            </div>
        @endcan
    @endif

    {{-- Les étapes dans l'ordre du circuit : on voit où ça bloque. --}}
    <div class="tb-kpis">
        @foreach([
            ['urgences', 'Urgences en cours', 'labo.demandes.index', 'fa-bolt', 'Voir les urgences'],
            ['demandes_du_jour', 'Demandes du jour', 'labo.demandes.index', 'fa-file-medical', 'Voir les demandes'],
            ['a_prelever', 'À prélever', 'labo.prelevements.index', 'fa-syringe', 'Prélever'],
            ['en_analyse', 'En analyse', 'labo.paillasse.index', 'fa-vials', 'Aller à la paillasse'],
            ['a_valider', 'À valider', 'labo.validation.index', 'fa-user-md', 'Valider'],
            ['a_publier', 'À publier', 'labo.demandes.index', 'fa-paper-plane', 'Publier'],
        ] as [$cle, $libelle, $route, $icone, $action])
            @php
                $valeur = $compteurs[$cle] ?? 0;
                $ton = $cle === 'urgences' && $valeur ? 'est-danger' : (in_array($cle, ['a_valider', 'a_publier'], true) && $valeur ? 'est-alerte' : '');
            @endphp
            <a href="{{ route($route, $cle === 'urgences' ? ['urgence' => 1] : []) }}" class="hl-bloc tb-kpi {{ $ton }}">
                <span class="tb-kpi-haut">{{ $libelle }} <i class="fas {{ $icone }}" aria-hidden="true"></i></span>
                <b>{{ $valeur }}</b>
                @if($valeur)<small>{{ $action }} ›</small>@endif
            </a>
        @endforeach
    </div>

    <div class="tb-grille">
        <div class="tb-colonne">
            <section class="hl-bloc {{ $critiquesNonSignales->isNotEmpty() ? 'tb-critique' : '' }}">
                <h2 class="hl-bloc-titre"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i> Valeurs critiques à signaler</h2>
                @if($critiquesNonSignales->isEmpty())
                    <div class="hl-vide" style="padding:24px"><i class="fas fa-check-circle" aria-hidden="true" style="color:#a7f3d0"></i>Aucune valeur critique en attente d'appel.</div>
                @else
                    <ul class="tb-liste">
                        @foreach($critiquesNonSignales as $r)
                            <li>
                                <div>
                                    <strong>{{ $r->demandeExamen->demande->patient->full_name }}</strong>
                                    <span class="tb-sous">{{ $r->demandeExamen->demande->numero }} · {{ $r->libelle }} :
                                        <span class="{{ $r->flag->classeCss() }}">{{ $r->valeurAffichee() }} {{ $r->unite }} {{ $r->flag->symbole() }}</span></span>
                                </div>
                                <a href="{{ route('labo.paillasse.saisie', $r->demande_examen_id) }}" class="hl-bouton" style="color:var(--hali-danger); border-color:#fecaca">Appeler et tracer</a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="hl-bloc">
                <h2 class="hl-bloc-titre">Examens en retard <small>délai moyen de rendu sur 30 j : <strong>{{ $delaiMoyenHeures !== null ? $delaiMoyenHeures . ' h' : '—' }}</strong></small></h2>
                @if($enRetard->isEmpty())
                    <div class="hl-vide" style="padding:24px"><i class="fas fa-check-circle" aria-hidden="true" style="color:#a7f3d0"></i>Aucun examen en retard.</div>
                @else
                    <ul class="tb-liste">
                        @foreach($enRetard as $l)
                            <li>
                                <div>
                                    <strong>{{ $l->examen_nom }}</strong> · {{ $l->demande->patient->full_name }}
                                    <span class="tb-sous" style="color:var(--hali-danger)">Échéance dépassée depuis {{ $l->echeance()->diffForHumans(null, true) }}</span>
                                </div>
                                <span class="badge badge-{{ $l->statut->couleur() }}" style="margin-left:auto">{{ $l->statut->libelle() }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        <section class="hl-bloc">
            <h2 class="hl-bloc-titre">Examens les plus demandés <small>30 derniers jours</small></h2>
            @php($max = max(1, $topExamens->max('total') ?? 1))
            @if($topExamens->isEmpty())
                <div class="hl-vide" style="padding:24px">Pas encore de données.</div>
            @else
                <ul class="hl-classement">
                    @foreach($topExamens as $t)
                        <li>
                            <div class="hl-classement-haut"><span>{{ $t['nom'] }}</span><b>{{ $t['total'] }}</b></div>
                            <div class="hl-jauge" aria-hidden="true"><span style="width: {{ round($t['total'] / $max * 100) }}%"></span></div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</div></div>
@endsection
