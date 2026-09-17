@extends('layouts.backend')

@php $gnf = fn ($m) => number_format((float) $m, 0, ',', ' '); @endphp

@section('content')
<div class="container"><div class="page-inner">
    <div class="page-header d-flex flex-wrap align-items-center gap-2">
        <h3 class="fw-bold mb-0">Relevé {{ $releve->numero }}</h3>
        <span class="badge badge-{{ $releve->statut === 'solde' ? 'success' : ($releve->statut === 'envoye' ? 'info' : 'secondary') }}">{{ ucfirst($releve->statut) }}</span>
        <a href="{{ route('labo.creances.show', $releve->partenariat) }}" class="btn btn-sm btn-secondary ms-auto">Retour</a>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center gap-2">
            <div>
                <strong>{{ $releve->partenariat->clinique?->nom }}</strong>
                <div class="small text-muted">Période du {{ $releve->periode_debut->format('d/m/Y') }} au {{ $releve->periode_fin->format('d/m/Y') }}
                    @if($releve->echeance) · échéance {{ $releve->echeance->format('d/m/Y') }}@endif</div>
            </div>
            <div class="ms-auto d-flex gap-2">
                <a href="{{ route('labo.releves.imprimer', $releve) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="fa fa-print"></i> Imprimer</a>
                @if(! $releve->estEnvoye())
                    <form method="POST" action="{{ route('labo.releves.envoyer', $releve) }}" onsubmit="return confirm('Marquer ce relevé envoyé ? Les montants seront figés.');">@csrf
                        <button class="btn btn-sm btn-primary">Marquer envoyé</button></form>
                @elseif($releve->montantRegle() < 1)
                    <form method="POST" action="{{ route('labo.releves.rouvrir', $releve) }}" onsubmit="return confirm('Rouvrir ce relevé ?');">@csrf
                        <button class="btn btn-sm btn-outline-warning">Rouvrir</button></form>
                @endif
            </div>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-sm align-middle">
                <thead><tr><th>Demande</th><th>Patient</th><th>Examens</th><th>Date</th><th class="text-end">Montant</th><th class="text-end">Réglé</th><th></th></tr></thead>
                <tbody>
                @foreach($releve->creances as $creance)
                    <tr>
                        <td>{{ $creance->demande?->numero }}</td>
                        <td>{{ $creance->demande?->patient?->full_name }}</td>
                        <td class="small">{{ $creance->demande?->examens->pluck('examen_nom')->join(', ') }}</td>
                        <td class="small">{{ $creance->demande?->created_at->format('d/m/Y') }}</td>
                        <td class="text-end">{{ $gnf($creance->montant) }}</td>
                        <td class="text-end">{{ $gnf($creance->montant_regle) }}</td>
                        <td class="text-end">
                            @unless($releve->estEnvoye())
                                <form method="POST" action="{{ route('labo.creances.retirer', $creance) }}">@csrf
                                    <button class="btn btn-sm btn-link text-danger">Retirer</button></form>
                            @endunless
                        </td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot><tr>
                    <th colspan="4" class="text-end">Total</th>
                    <th class="text-end">{{ $gnf($releve->montant_total) }}</th>
                    <th class="text-end">{{ $gnf($releve->montantRegle()) }}</th>
                    <th></th>
                </tr>
                <tr><th colspan="4" class="text-end">Reste dû</th><th colspan="2" class="text-end">{{ $gnf($releve->resteDu()) }} GNF</th><th></th></tr></tfoot>
            </table>
        </div>
    </div>
</div></div>
@endsection
