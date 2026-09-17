@extends('layouts.backend')

@section('content')
<div class="container"><div class="page-inner">
    @include('assurance.partials.entete', ['titre' => 'Entreprises', 'fil' => [route('assurance.entreprises.index') => 'Entreprises']])

    <div class="card">
        <div class="card-header d-flex flex-wrap gap-2 align-items-center">
            <form method="GET" class="d-flex gap-2">
                <input name="q" value="{{ $recherche }}" class="form-control form-control-sm" placeholder="Nom ou NIF">
                <button class="btn btn-sm btn-outline-primary">Rechercher</button>
            </form>
            @can('assurance.referentiel.manage')
                <button class="btn btn-primary btn-sm ms-auto" data-bs-toggle="modal" data-bs-target="#modalEntreprise"><i class="fa fa-plus"></i> Nouvelle entreprise</button>
            @endcan
        </div>
        <div class="card-body table-responsive">
            <table class="table table-sm table-hover align-middle">
                <thead><tr><th>Entreprise</th><th>Secteur</th><th>Contact</th><th class="text-center">Employés rattachés</th><th class="text-center">Contrats</th><th></th></tr></thead>
                <tbody>
                @forelse($entreprises as $e)
                    <tr class="{{ $e->actif ? '' : 'text-muted' }}">
                        <td><a href="{{ route('assurance.entreprises.show', $e) }}" class="fw-bold">{{ $e->nom }}</a>@if($e->nif)<div class="small text-muted">NIF {{ $e->nif }}</div>@endif</td>
                        <td>{{ $e->secteur ?: '—' }}</td>
                        <td class="small">{{ $e->contact_nom }}@if($e->telephone)<br>{{ $e->telephone }}@endif</td>
                        <td class="text-center">{{ $e->employes_count }}</td>
                        <td class="text-center">{{ $e->contrats_count }}</td>
                        <td class="text-end"><a href="{{ route('assurance.entreprises.show', $e) }}" class="btn btn-sm btn-outline-primary"><i class="fa fa-eye"></i></a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted text-center py-4">Aucune entreprise. Enregistrez les employeurs de vos patients assurés pour les rattacher à leurs contrats.</td></tr>
                @endforelse
                </tbody>
            </table>
            {{ $entreprises->links() }}
        </div>
    </div>
</div></div>

@can('assurance.referentiel.manage')
<div class="modal fade" id="modalEntreprise" tabindex="-1"><div class="modal-dialog modal-lg">
    <form method="POST" action="{{ route('assurance.entreprises.store') }}" class="modal-content">@csrf
        <div class="modal-header"><h5 class="modal-title">Nouvelle entreprise</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">@include('assurance.entreprises._formulaire', ['entreprise' => null])</div>
        <div class="modal-footer"><button class="btn btn-primary">Enregistrer</button></div>
    </form>
</div></div>
@endcan
@endsection
