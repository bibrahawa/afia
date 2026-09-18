@extends('layouts.backend')

@php $gnf = fn ($m) => number_format((float) $m, 0, ',', ' '); @endphp

@section('content')
<div class="container"><div class="page-inner">
    <div class="page-header d-flex flex-wrap align-items-center gap-2">
        <h3 class="fw-bold mb-0">Relevé {{ $releve->numero }}</h3>
        <span class="text-muted">{{ $releve->partenariat?->laboratoire?->nom }}</span>
        <a href="{{ route('labo.reseau.factures') }}" class="btn btn-sm btn-secondary ms-auto">Retour</a>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="small text-muted">Période du {{ $releve->periode_debut->format('d/m/Y') }} au {{ $releve->periode_fin->format('d/m/Y') }}
                @if($releve->echeance) · à régler avant le {{ $releve->echeance->format('d/m/Y') }}@endif</div>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-sm align-middle">
                <thead><tr><th>Demande</th><th>Patient</th><th>Examens</th><th>Date</th><th class="text-end">Montant</th></tr></thead>
                <tbody>
                @foreach($releve->creances as $creance)
                    <tr>
                        <td>{{ $creance->demande?->numero }}</td>
                        <td>{{ $creance->demande?->patient?->full_name }}</td>
                        <td class="small">{{ $creance->demande?->examens->pluck('examen_nom')->join(', ') }}</td>
                        <td class="small">{{ $creance->demande?->created_at->format('d/m/Y') }}</td>
                        <td class="text-end">{{ $gnf($creance->montant) }}</td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot>
                    <tr><th colspan="4" class="text-end">Total</th><th class="text-end">{{ $gnf($releve->montant_total) }} GNF</th></tr>
                    <tr><th colspan="4" class="text-end">Reste à payer</th><th class="text-end">{{ $gnf($releve->resteDu()) }} GNF</th></tr>
                </tfoot>
            </table>
            <div class="row g-2">
                <div class="col-md-4"><div class="border rounded p-2 small">Dû au laboratoire<div class="h5 mb-0">{{ $gnf($rapprochement['du_au_laboratoire']) }} GNF</div></div></div>
                <div class="col-md-4"><div class="border rounded p-2 small">Facturé à vos patients<div class="h5 mb-0">{{ $gnf($rapprochement['facture_au_patient']) }} GNF</div></div></div>
                <div class="col-md-4"><div class="border rounded p-2 small {{ $rapprochement['marge'] < 0 ? 'border-danger text-danger' : '' }}">Marge<div class="h5 mb-0">{{ $gnf($rapprochement['marge']) }} GNF</div></div></div>
            </div>

            @if($rapprochement['facturation_absente'])
                <div class="alert alert-warning small mt-2 mb-0">
                    Aucune de ces analyses n'a été facturée à un patient dans votre établissement.
                    Soit elles sont incluses dans vos consultations, soit personne n'a encaissé : vérifiez avant de régler ce relevé.
                </div>
            @endif

            <p class="small text-muted mb-0 mt-2">Les règlements sont enregistrés par le laboratoire ; ce relevé se met à jour quand il les saisit.</p>
        </div>
    </div>
</div></div>
@endsection
