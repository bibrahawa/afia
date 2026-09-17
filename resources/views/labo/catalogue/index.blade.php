@extends('layouts.backend')
@section('style') @include('labo.partials.styles') @endsection

@section('content')
<div class="container"><div class="page-inner">
    @include('labo.partials.entete', ['titre' => 'Catalogue des examens', 'fil' => [route('labo.catalogue.index') => 'Catalogue']])

    @can('labo.catalogue.manage')
        <div class="d-flex flex-wrap gap-2 mb-3">
            <a href="{{ route('labo.catalogue.examens.create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Nouvel examen</a>
            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalSection"><i class="fa fa-plus"></i> Section</button>
            <form method="POST" action="{{ route('labo.catalogue.importer') }}" class="ms-auto"
                  onsubmit="return confirm('Importer le catalogue modèle ? Les examens déjà présents ne sont jamais écrasés.')">@csrf
                <button class="btn btn-outline-secondary btn-sm">{{ $catalogueVide ? 'Importer le catalogue modèle' : 'Compléter depuis le catalogue modèle' }}</button>
            </form>
        </div>
    @endcan

    @if($catalogueVide)
        <div class="alert alert-info">Aucun examen. Le catalogue modèle contient ~30 examens courants (hématologie, biochimie, sérologie, parasitologie, bactériologie). Après import : <strong>renseignez les prix</strong> (tous à 0) et <strong>faites valider les normes</strong> par votre biologiste.</div>
    @endif

    @foreach($sections as $section)
        <div class="card">
            <div class="card-header"><h4 class="card-title">{{ $section->nom }} <span class="small text-muted">({{ $section->examens->count() }})</span></h4></div>
            <div class="card-body table-responsive">
                <table class="table table-sm table-hover align-middle">
                    <thead><tr><th>Code</th><th>Examen</th><th>Échantillon</th><th>Paramètres</th><th>Délai</th><th class="text-end">Prix (GNF)</th><th>Statut</th><th></th></tr></thead>
                    <tbody>
                    @foreach($section->examens as $ex)
                        <tr class="{{ $ex->actif ? '' : 'text-muted' }}">
                            <td><code>{{ $ex->code }}</code></td>
                            <td>{{ $ex->nom }} @if($ex->sous_traite)<span class="badge badge-light">sous-traité</span>@endif @if($ex->a_jeun)<span class="badge badge-light">à jeun</span>@endif</td>
                            <td class="small">@if($ex->tube)<span class="labo-tube labo-tube-{{ $ex->tube }}"></span>@endif{{ $ex->libelleContenant() }}</td>
                            <td>{{ $ex->parametres_count }}</td>
                            <td>{{ $ex->delai_rendu_heures }} h</td>
                            <td class="text-end {{ (float) $ex->prix === 0.0 ? 'text-danger fw-bold' : '' }}">{{ number_format($ex->prix, 0, ',', ' ') }}</td>
                            <td><span class="badge badge-{{ $ex->actif ? 'success' : 'secondary' }}">{{ $ex->actif ? 'Actif' : 'Désactivé' }}</span></td>
                            <td class="text-end text-nowrap">
                                @can('labo.catalogue.manage')
                                    <a href="{{ route('labo.catalogue.examens.edit', $ex) }}" class="btn btn-sm btn-warning"><i class="fa fa-edit"></i></a>
                                    <form method="POST" action="{{ route('labo.catalogue.examens.basculer', $ex) }}" class="d-inline">@csrf
                                        <button class="btn btn-sm btn-outline-secondary" title="{{ $ex->actif ? 'Désactiver' : 'Réactiver' }}"><i class="fa fa-power-off"></i></button></form>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
</div></div>

@can('labo.catalogue.manage')
<div class="modal fade" id="modalSection" tabindex="-1"><div class="modal-dialog"><form method="POST" action="{{ route('labo.catalogue.sections.store') }}" class="modal-content">@csrf
    <div class="modal-header"><h5 class="modal-title">Nouvelle section</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <input name="nom" class="form-control mb-2" placeholder="Nom (ex. Immunologie)" required>
        <input name="code" class="form-control mb-2" placeholder="Code (ex. IMMUNO)" required>
        <input name="ordre" type="number" class="form-control" placeholder="Ordre d'affichage">
    </div>
    <div class="modal-footer"><button class="btn btn-primary">Ajouter</button></div>
</form></div></div>
@endcan
@endsection
