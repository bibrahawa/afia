@extends('layouts.backend')

@section('content')
<div class="container"><div class="page-inner">
    @include('assurance.partials.entete', ['titre' => 'Contrats d\'assurance', 'fil' => [route('assurance.contrats.index') => 'Contrats']])

    <div class="card">
        <div class="card-header d-flex flex-wrap gap-2 align-items-center">
            <form method="GET" class="d-flex flex-wrap gap-2">
                <input name="q" value="{{ $recherche }}" class="form-control form-control-sm" placeholder="Police, libellé, entreprise…">
                <select name="organisme" class="form-control form-control-sm">
                    <option value="">Tous les organismes</option>
                    @foreach($organismes as $o)<option value="{{ $o->id }}" @selected(request('organisme') == $o->id)>{{ $o->name }}</option>@endforeach
                </select>
                <button class="btn btn-sm btn-outline-primary">Filtrer</button>
            </form>
            @can('assurance.referentiel.manage')
                <button class="btn btn-primary btn-sm ms-auto" data-bs-toggle="modal" data-bs-target="#modalContrat"><i class="fa fa-plus"></i> Nouveau contrat</button>
            @endcan
        </div>
        <div class="card-body table-responsive">
            <table class="table table-sm table-hover align-middle">
                <thead><tr><th>Contrat</th><th>Organisme payeur</th><th>Souscripteur</th><th>Période</th><th class="text-center">Formules</th><th>Statut</th><th></th></tr></thead>
                <tbody>
                @forelse($contrats as $c)
                    <tr>
                        <td><a href="{{ route('assurance.contrats.show', $c) }}" class="fw-bold">{{ $c->libelle ?: 'Police ' . $c->numero_police }}</a><div class="small text-muted">N° {{ $c->numero_police }}</div></td>
                        <td>{{ $c->organismePayeur?->name }}</td>
                        <td>@if($c->entreprise)<a href="{{ route('assurance.entreprises.show', $c->entreprise) }}">{{ $c->entreprise->nom }}</a>@else<span class="text-muted">Individuel</span>@endif</td>
                        <td class="small">{{ $c->date_debut->format('d/m/Y') }} → {{ $c->date_fin?->format('d/m/Y') ?? '…' }}</td>
                        <td class="text-center">{{ $c->formules_count }}</td>
                        <td>@include('assurance.partials.statut', ['statut' => $c->statut, 'fin' => $c->date_fin])</td>
                        <td class="text-end"><a href="{{ route('assurance.contrats.show', $c) }}" class="btn btn-sm btn-outline-primary"><i class="fa fa-eye"></i></a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-muted text-center py-4">Aucun contrat.</td></tr>
                @endforelse
                </tbody>
            </table>
            {{ $contrats->links() }}
        </div>
    </div>
</div></div>

@can('assurance.referentiel.manage')
<div class="modal fade" id="modalContrat" tabindex="-1"><div class="modal-dialog modal-lg">
    <form method="POST" action="{{ route('assurance.contrats.store') }}" class="modal-content">@csrf
        <div class="modal-header"><h5 class="modal-title">Nouveau contrat</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">@include('assurance.contrats._formulaire', ['contrat' => null])</div>
        <div class="modal-footer"><button class="btn btn-primary">Enregistrer</button></div>
    </form>
</div></div>
@endcan
@endsection
