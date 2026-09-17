@extends('layouts.backend')
@section('style') @include('labo.partials.styles') @endsection

@section('content')
<div class="container"><div class="page-inner">
    @include('labo.partials.entete', ['titre' => 'Tableau de bord du laboratoire'])

    @if($catalogueVide)
        <div class="alert alert-warning d-flex align-items-center">
            <div>Le catalogue d'examens est vide. Importez le catalogue modèle Aprosafe pour démarrer, puis ajustez prix et normes.</div>
            @can('labo.catalogue.manage')
                <form method="POST" action="{{ route('labo.catalogue.importer') }}" class="ms-auto">@csrf
                    <button class="btn btn-primary btn-sm">Importer le catalogue modèle</button>
                </form>
            @endcan
        </div>
    @endif

    <div class="row">
        @foreach([
            ['demandes_du_jour', 'Demandes du jour', 'labo.demandes.index', 'fa-file-medical'],
            ['a_prelever', 'À prélever', 'labo.prelevements.index', 'fa-syringe'],
            ['en_analyse', 'En analyse', 'labo.paillasse.index', 'fa-vials'],
            ['a_valider', 'À valider (biologiste)', 'labo.validation.index', 'fa-user-md'],
            ['a_publier', 'À publier', 'labo.demandes.index', 'fa-paper-plane'],
            ['urgences', 'Urgences en cours', 'labo.demandes.index', 'fa-bolt'],
        ] as [$cle, $libelle, $route, $icone])
            <div class="col-sm-6 col-md-4 col-xl-2">
                <a href="{{ route($route, $cle === 'urgences' ? ['urgence' => 1] : []) }}" class="text-decoration-none">
                    <div class="card card-stats card-round labo-kpi {{ $cle === 'urgences' && $compteurs[$cle] ? 'labo-urgent' : '' }}">
                        <div class="card-body">
                            <div class="labo-kpi-valeur">{{ $compteurs[$cle] }}</div>
                            <p class="card-category mb-0 mt-2"><i class="fas {{ $icone }} me-1"></i>{{ $libelle }}</p>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    @if($compteurs['mdo_a_declarer'] > 0)
        @can('labo.validation.biologique')
            <div class="alert alert-warning d-flex align-items-center">
                <i class="fas fa-bullhorn me-2"></i>
                <div><strong>{{ $compteurs['mdo_a_declarer'] }}</strong> maladie(s) à déclaration obligatoire en attente de déclaration.</div>
                <a href="{{ route('labo.declarations.index') }}" class="btn btn-sm btn-warning ms-auto">Voir</a>
            </div>
        @endcan
    @endif

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h4 class="card-title text-danger"><i class="fas fa-exclamation-triangle me-1"></i> Valeurs critiques non signalées</h4></div>
                <div class="card-body">
                    @forelse($critiquesNonSignales as $r)
                        <div class="d-flex border-bottom py-2 align-items-center">
                            <div>
                                <strong>{{ $r->demandeExamen->demande->patient->full_name }}</strong>
                                <span class="text-muted small">— {{ $r->demandeExamen->demande->numero }}</span><br>
                                {{ $r->libelle }} : <span class="{{ $r->flag->classeCss() }}">{{ $r->valeurAffichee() }} {{ $r->unite }} {{ $r->flag->symbole() }}</span>
                            </div>
                            <a href="{{ route('labo.paillasse.saisie', $r->demande_examen_id) }}" class="btn btn-sm btn-danger ms-auto">Traiter</a>
                        </div>
                    @empty
                        <p class="text-muted mb-0">Aucune valeur critique en attente d'appel.</p>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header d-flex">
                    <h4 class="card-title">Examens en retard</h4>
                    <span class="ms-auto small text-muted">Délai moyen de rendu (30 j) : <strong>{{ $delaiMoyenHeures !== null ? $delaiMoyenHeures . ' h' : '—' }}</strong></span>
                </div>
                <div class="card-body">
                    @forelse($enRetard as $l)
                        <div class="d-flex border-bottom py-2">
                            <div><strong>{{ $l->examen_nom }}</strong> — {{ $l->demande->patient->full_name }}<br>
                                <span class="small text-danger">Échéance dépassée depuis {{ $l->echeance()->diffForHumans(null, true) }}</span></div>
                            <span class="badge badge-{{ $l->statut->couleur() }} ms-auto align-self-center">{{ $l->statut->libelle() }}</span>
                        </div>
                    @empty
                        <p class="text-muted mb-0">Aucun retard. 👍</p>
                    @endforelse
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h4 class="card-title">Examens les plus demandés (30 j)</h4></div>
                <div class="card-body">
                    @php($max = max(1, $topExamens->max('total') ?? 1))
                    @forelse($topExamens as $t)
                        <div class="mb-2">
                            <div class="d-flex small"><span>{{ $t['nom'] }}</span><strong class="ms-auto">{{ $t['total'] }}</strong></div>
                            <div class="progress" style="height:6px"><div class="progress-bar" style="width:{{ round($t['total'] / $max * 100) }}%;background:var(--aprosafe-primary)"></div></div>
                        </div>
                    @empty
                        <p class="text-muted mb-0">Pas encore de données.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div></div>
@endsection
