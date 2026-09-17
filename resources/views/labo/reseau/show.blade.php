@extends('layouts.backend')

@section('content')
<div class="container"><div class="page-inner">
    <div class="page-header d-flex flex-wrap align-items-center gap-2">
        <h3 class="fw-bold mb-0">Demande {{ $demande->numero }}</h3>
        <span class="badge badge-{{ $demande->statut->value === 'publiee' ? 'success' : 'info' }}">{{ $demande->statut->libelle() }}</span>
        <a href="{{ route('labo.reseau.index') }}" class="btn btn-sm btn-secondary ms-auto">Retour</a>
    </div>

    <div class="row">
        <div class="col-lg-4"><div class="card"><div class="card-body small">
            <p class="mb-1"><strong>Patient :</strong> {{ $demande->patient?->full_name }}</p>
            <p class="mb-1"><strong>Laboratoire :</strong> {{ $demande->etablissement?->nom }}</p>
            <p class="mb-1"><strong>Envoyée le :</strong> {{ $demande->created_at->format('d/m/Y à H:i') }}</p>
            <p class="mb-1"><strong>Facturation :</strong> {{ $demande->mode_facturation->libelle() }}</p>
            <p class="mb-1"><strong>Prescripteur :</strong> {{ $demande->prescripteur_externe ?? '—' }}</p>
            @if($demande->renseignements_cliniques)<p class="mb-2 text-muted">{{ $demande->renseignements_cliniques }}</p>@endif

            <a href="{{ route('labo.reseau.bon', $demande->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary w-100 mb-2">
                <i class="fa fa-print"></i> Bon d'analyses pour le patient
            </a>

            @can('labo.reseau.demander')
                @if($demande->statut->value === 'enregistree')
                    <form method="POST" action="{{ route('labo.reseau.annuler', $demande->id) }}"
                          onsubmit="return confirm('Annuler cette demande auprès du laboratoire ?');">@csrf
                        <input name="motif" class="form-control form-control-sm mb-1" maxlength="255" placeholder="Motif (facultatif)">
                        <button class="btn btn-sm btn-outline-danger w-100">Annuler la demande</button>
                    </form>
                @endif
            @endcan
        </div></div></div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <h4 class="card-title">Examens</h4>
                    @if($compteRendu)
                        <a href="{{ route('labo.reseau.compte-rendu', $demande->id) }}" target="_blank" class="btn btn-sm btn-success ms-auto">
                            <i class="fa fa-file-pdf"></i> Compte rendu (v{{ $compteRendu->version }})
                        </a>
                    @endif
                </div>
                <ul class="list-group list-group-flush small">
                    @foreach($demande->examens as $ligne)
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ $ligne->examen_nom }}</span>
                            <span class="badge badge-light">{{ $ligne->statut->libelle() }}</span>
                        </li>
                    @endforeach
                </ul>
                @unless($compteRendu)
                    <div class="card-footer small text-muted">Le compte rendu apparaîtra ici dès sa publication par le laboratoire.</div>
                @endunless
            </div>
        </div>
    </div>
</div></div>
@endsection
