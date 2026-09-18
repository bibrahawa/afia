@extends('layouts.backend')

@section('content')
<div class="container"><div class="page-inner">
    <div class="page-header d-flex flex-wrap align-items-center gap-2">
        <h3 class="fw-bold mb-0">Analyses envoyées</h3>
        @if($nonVus > 0)<span class="badge badge-success">{{ $nonVus }} résultat(s) non consulté(s)</span>@endif
        @can('labo.reseau.demander')
            @if($partenaires->isNotEmpty())
                <a href="{{ route('labo.reseau.create') }}" class="btn btn-primary btn-sm ms-auto"><i class="fa fa-paper-plane"></i> Envoyer une demande</a>
            @endif
        @endcan
    </div>

    @forelse($propositions as $proposition)
        <div class="alert alert-info d-flex flex-wrap align-items-center gap-2">
            <span><strong>{{ $proposition->laboratoire?->nom }}</strong> vous propose un partenariat
                @if($proposition->remise_pourcentage > 0)(remise de {{ rtrim(rtrim(number_format((float) $proposition->remise_pourcentage, 2, ',', ''), '0'), ',') }} %)@endif.</span>
            <span class="ms-auto d-flex gap-2">
                <form method="POST" action="{{ route('labo.reseau.propositions.accepter', $proposition->id) }}">@csrf
                    <button class="btn btn-sm btn-success">Accepter</button></form>
                <form method="POST" action="{{ route('labo.reseau.propositions.refuser', $proposition->id) }}" class="d-flex gap-1">@csrf
                    <input name="motif_refus" class="form-control form-control-sm" maxlength="255" placeholder="Motif (facultatif)">
                    <button class="btn btn-sm btn-outline-danger">Refuser</button></form>
            </span>
        </div>
    @empty
    @endforelse

    @if($partenaires->isEmpty())
        <div class="alert alert-info">Aucun laboratoire partenaire pour l'instant. C'est le laboratoire qui ouvre le partenariat depuis son écran « Cliniques partenaires ».</div>
    @endif

    <div class="card">
        <div class="card-header">
            <form method="GET" class="row g-2">
                <div class="col-md-4"><select name="partenariat_id" class="form-control form-control-sm">
                    <option value="">Tous les laboratoires</option>
                    @foreach($partenaires as $p)<option value="{{ $p->id }}" @selected(($filtres['partenariat_id'] ?? null) == $p->id)>{{ $p->laboratoire?->nom }}</option>@endforeach
                </select></div>
                <div class="col-md-3"><select name="statut" class="form-control form-control-sm">
                    <option value="">Tous les statuts</option>
                    @foreach($statuts as $statut)<option value="{{ $statut->value }}" @selected(($filtres['statut'] ?? null) === $statut->value)>{{ $statut->libelle() }}</option>@endforeach
                </select></div>
                <div class="col-md-3"><input name="patient" value="{{ $filtres['patient'] ?? '' }}" class="form-control form-control-sm" placeholder="Patient"></div>
                <div class="col-md-2"><button class="btn btn-sm btn-outline-primary w-100">Filtrer</button></div>
            </form>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-sm table-hover align-middle">
                <thead><tr><th>Demande</th><th>Patient</th><th>Laboratoire</th><th>Examens</th><th>Envoyée</th><th>Statut</th><th></th></tr></thead>
                <tbody>
                @forelse($demandes as $d)
                    <tr>
                        <td><a href="{{ route('labo.reseau.show', $d->id) }}">{{ $d->numero }}</a>@if($d->urgence)<span class="badge badge-danger ms-1">urgent</span>@endif</td>
                        <td>{{ $d->patient?->full_name }}</td>
                        <td class="small">{{ $d->etablissement?->nom }}</td>
                        <td class="small">{{ $d->examens->pluck('examen_nom')->take(3)->join(', ') }}@if($d->examens->count() > 3) +{{ $d->examens->count() - 3 }}@endif</td>
                        <td class="small">{{ $d->created_at->format('d/m/Y H:i') }}</td>
                        <td><span class="badge badge-{{ $d->statut->value === 'publiee' ? 'success' : ($d->statut->value === 'annulee' ? 'secondary' : 'info') }}">{{ $d->statut->libelle() }}</span>
                            @if($d->premiere_publication_le && ! $d->resultat_vu_le)<div class="small text-success">nouveau résultat</div>@endif</td>
                        <td class="text-end"><a href="{{ route('labo.reseau.show', $d->id) }}" class="btn btn-sm btn-outline-primary">Ouvrir</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Aucune demande envoyée.</td></tr>
                @endforelse
                </tbody>
            </table>
            {{ $demandes->links() }}
        </div>
    </div>
</div></div>
@endsection
