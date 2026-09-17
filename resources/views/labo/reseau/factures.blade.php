@extends('layouts.backend')

@php $gnf = fn ($m) => number_format((float) $m, 0, ',', ' '); @endphp

@section('content')
<div class="container"><div class="page-inner">
    <div class="page-header d-flex flex-wrap align-items-center gap-2">
        <h3 class="fw-bold mb-0">Factures des laboratoires</h3>
        <span class="text-muted">reste à payer : <strong>{{ $gnf($resteDu) }} GNF</strong></span>
        <a href="{{ route('labo.reseau.index') }}" class="btn btn-sm btn-secondary ms-auto">Analyses envoyées</a>
    </div>

    <div class="card"><div class="card-body table-responsive">
        <table class="table table-sm align-middle">
            <thead><tr><th>Relevé</th><th>Laboratoire</th><th>Période</th><th>Échéance</th><th class="text-end">Montant</th><th class="text-end">Reste</th><th></th></tr></thead>
            <tbody>
            @forelse($releves as $releve)
                <tr class="{{ $releve->enRetard() ? 'table-warning' : '' }}">
                    <td>{{ $releve->numero }}</td>
                    <td>{{ $releve->partenariat?->laboratoire?->nom }}</td>
                    <td class="small">{{ $releve->periode_debut->format('d/m/Y') }} → {{ $releve->periode_fin->format('d/m/Y') }}</td>
                    <td class="small">{{ $releve->echeance?->format('d/m/Y') ?? '—' }}</td>
                    <td class="text-end">{{ $gnf($releve->montant_total) }}</td>
                    <td class="text-end"><strong>{{ $gnf($releve->resteDu()) }}</strong></td>
                    <td class="text-end"><a href="{{ route('labo.reseau.facture', $releve->id) }}" class="btn btn-sm btn-outline-primary">Détail</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">Aucun relevé reçu.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $releves->links() }}
    </div></div>
</div></div>
@endsection
