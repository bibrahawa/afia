@extends('layouts.backend')

@php $gnf = fn ($m) => number_format((float) $m, 0, ',', ' '); @endphp

@section('content')
<div class="container"><div class="page-inner">
    <div class="page-header d-flex flex-wrap align-items-center gap-2">
        <h3 class="fw-bold mb-0">Correspondances de catalogue</h3>
        <a href="{{ route('labo.reseau.index') }}" class="btn btn-sm btn-secondary ms-auto">Analyses envoyées</a>
    </div>

    <p class="text-muted">Chaque examen du laboratoire est facturé à votre patient sur l'acte correspondant de <strong>votre</strong> catalogue :
        c'est lui que voient vos conventions d'assurance, et c'est son prix que paie le patient. La différence avec le prix négocié est votre marge.</p>

    @if($partenaires->count() > 1)
        <form method="GET" class="mb-3 d-flex gap-2" style="max-width:420px">
            <select name="partenariat_id" class="form-control form-control-sm">
                @foreach($partenaires as $p)<option value="{{ $p->id }}" @selected($partenariat && $p->id === $partenariat->id)>{{ $p->laboratoire?->nom }}</option>@endforeach
            </select>
            <button class="btn btn-sm btn-outline-primary">Afficher</button>
        </form>
    @endif

    @if(! $partenariat)
        <div class="alert alert-info">Aucun laboratoire partenaire actif.</div>
    @else
        <div class="card"><div class="card-body table-responsive">
            <table class="table table-sm align-middle">
                <thead><tr><th>Examen du laboratoire</th><th class="text-end">Prix négocié</th><th>Acte facturé au patient</th><th class="text-end">Prix de vente</th><th class="text-end">Marge</th><th></th></tr></thead>
                <tbody>
                @forelse($lignes as $ligne)
                    @php
                        $prixVente = (float) ($ligne['acte']?->amount ?? 0);
                        $marge = $prixVente - (float) ($ligne['prix_negocie'] ?? 0);
                    @endphp
                    <tr class="{{ $marge < 0 ? 'table-warning' : '' }}">
                        <td>{{ $ligne['examen']?->nom ?? 'Examen retiré du catalogue' }}
                            <span class="small text-muted">{{ $ligne['examen']?->code }}</span>
                            @if($ligne['lien']->cree_automatiquement)<span class="badge badge-light">acte créé automatiquement</span>@endif</td>
                        <td class="text-end">{{ $gnf($ligne['prix_negocie']) }}</td>
                        <td>
                            <form method="POST" action="{{ route('labo.reseau.correspondances.update') }}" class="d-flex gap-1">@csrf
                                <input type="hidden" name="partenariat_id" value="{{ $partenariat->id }}">
                                <input type="hidden" name="examen_id" value="{{ $ligne['lien']->examen_id }}">
                                <select name="test_id" class="form-control form-control-sm">
                                    @foreach($actes as $acte)
                                        <option value="{{ $acte->id }}" @selected($ligne['acte'] && $acte->id === $ligne['acte']->id)>{{ $acte->name }}</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-sm btn-outline-primary">OK</button>
                            </form>
                        </td>
                        <td class="text-end">{{ $gnf($prixVente) }}</td>
                        <td class="text-end">{{ $gnf($marge) }}@if($marge < 0)<div class="small text-danger">vente à perte</div>@endif</td>
                        <td class="text-end small text-muted">{{ $ligne['acte'] ? 'lié' : 'à relier' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Aucune correspondance : elles se créent au premier envoi d'analyses.</td></tr>
                @endforelse
                </tbody>
            </table>
            <p class="small text-muted mb-0">Le prix de vente se modifie au catalogue des examens de votre établissement.</p>
        </div></div>
    @endif
</div></div>
@endsection
