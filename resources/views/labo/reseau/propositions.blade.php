@extends('layouts.backend')

@section('content')
<div class="container"><div class="page-inner">
    <div class="page-header d-flex align-items-center gap-2">
        <h3 class="fw-bold mb-0">Propositions de laboratoires</h3>
        <a href="{{ route('labo.reseau.index') }}" class="btn btn-sm btn-secondary ms-auto">Analyses envoyées</a>
    </div>

    <p class="text-muted">Un laboratoire vous ouvre son catalogue. Tant que vous n'avez pas accepté, aucune analyse ne peut lui être envoyée.</p>

    <div class="card"><div class="card-body table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Laboratoire</th><th>Conditions</th><th>Proposé le</th><th></th></tr></thead>
            <tbody>
            @forelse($propositions as $p)
                <tr>
                    <td><strong>{{ $p->laboratoire?->nom }}</strong>
                        @if($p->contact_nom)<div class="small text-muted">{{ $p->contact_nom }} {{ $p->contact_telephone }}</div>@endif</td>
                    <td class="small">
                        Remise {{ rtrim(rtrim(number_format((float) $p->remise_pourcentage, 2, ',', ''), '0'), ',') }} %
                        · {{ $p->mode_facturation_defaut === 'partenaire' ? 'le laboratoire vous facture' : 'le laboratoire encaisse le patient' }}
                        @if($p->clinique_facture_patient) · vous facturez votre patient @endif
                        @if($p->delai_paiement_jours) · paiement à {{ $p->delai_paiement_jours }} jours @endif
                        @if($p->notes)<div class="text-muted">{{ $p->notes }}</div>@endif
                    </td>
                    <td class="small">{{ $p->propose_le?->format('d/m/Y') }}</td>
                    <td class="text-end">
                        <form method="POST" action="{{ route('labo.reseau.propositions.accepter', $p->id) }}" class="d-inline">@csrf
                            <button class="btn btn-sm btn-success">Accepter</button></form>
                        <details class="d-inline-block text-start">
                            <summary class="btn btn-sm btn-outline-danger">Refuser</summary>
                            <form method="POST" action="{{ route('labo.reseau.propositions.refuser', $p->id) }}" class="position-absolute bg-white border shadow p-2" style="z-index:10; width:260px">@csrf
                                <input name="motif_refus" class="form-control form-control-sm mb-1" maxlength="255" placeholder="Motif (facultatif)">
                                <button class="btn btn-sm btn-danger w-100">Confirmer le refus</button>
                            </form>
                        </details>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-4">Aucune proposition en attente.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>
</div></div>
@endsection
