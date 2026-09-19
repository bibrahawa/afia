@extends('layouts.backend')

@php $gnf = fn ($m) => number_format((float) $m, 0, ',', ' '); @endphp

@section('style')
    @include('labo.partials.styles')
    <style>
        .co-lier { display: flex; gap: 6px; margin: 0; }
        .co-lier select { min-width: 200px; }
        .co-perte { color: var(--hali-danger); font-weight: 700; }
    </style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">
    @include('labo.partials.entete', ['titre' => 'Correspondances de catalogue', 'fil' => [route('labo.reseau.index') => 'Analyses envoyées', 0 => 'Correspondances']])

    <p class="lb-alerte lb-alerte-info"><i class="fas fa-info-circle mt-1" aria-hidden="true"></i><span>Chaque examen du laboratoire est facturé à votre patient sur l'acte correspondant de <strong>votre</strong> catalogue : c'est lui que voient vos conventions d'assurance, et c'est son prix que paie le patient. La différence avec le prix négocié est votre marge.</span></p>

    @if($partenaires->count() > 1)
        <form method="GET" style="display:flex; gap:8px; max-width:440px; margin-bottom:16px">
            <select name="partenariat_id" class="form-control" onchange="this.form.submit()" aria-label="Laboratoire">
                @foreach($partenaires as $p)<option value="{{ $p->id }}" @selected($partenariat && $p->id === $partenariat->id)>{{ $p->laboratoire?->nom }}</option>@endforeach
            </select>
            <noscript><button class="hl-bouton">Afficher</button></noscript>
        </form>
    @endif

    @if(! $partenariat)
        <p class="lb-alerte lb-alerte-info"><i class="fas fa-info-circle mt-1" aria-hidden="true"></i><span>Aucun laboratoire partenaire actif.</span></p>
    @else
        <section class="hl-bloc">
            <div class="table-responsive">
                <table class="lb-table">
                    <thead><tr><th>Examen du laboratoire</th><th class="lb-n">Prix négocié</th><th>Acte facturé au patient</th><th class="lb-n">Prix de vente</th><th class="lb-n">Marge</th></tr></thead>
                    <tbody>
                    @forelse($lignes as $ligne)
                        @php
                            $prixVente = (float) ($ligne['acte']?->amount ?? 0);
                            $marge = $prixVente - (float) ($ligne['prix_negocie'] ?? 0);
                        @endphp
                        <tr class="{{ $marge < 0 ? 'table-warning' : '' }}">
                            <td><span class="lb-fort">{{ $ligne['examen']?->nom ?? 'Examen retiré du catalogue' }}</span>
                                <span class="lb-sous">{{ $ligne['examen']?->code }}@if($ligne['lien']->cree_automatiquement) · acte créé automatiquement @endif</span></td>
                            <td class="lb-n">{{ $gnf($ligne['prix_negocie']) }}</td>
                            <td>
                                <form method="POST" action="{{ route('labo.reseau.correspondances.update') }}" class="co-lier">@csrf
                                    <input type="hidden" name="partenariat_id" value="{{ $partenariat->id }}">
                                    <input type="hidden" name="examen_id" value="{{ $ligne['lien']->examen_id }}">
                                    <select name="test_id" class="form-control form-control-sm" aria-label="Acte facturé">
                                        @foreach($actes as $acte)
                                            <option value="{{ $acte->id }}" @selected($ligne['acte'] && $acte->id === $ligne['acte']->id)>{{ $acte->name }}</option>
                                        @endforeach
                                    </select>
                                    <button class="hl-bouton lb-petit">Relier</button>
                                </form>
                                @unless($ligne['acte'])<span class="lb-sous" style="color:var(--hali-alerte)">à relier</span>@endunless
                            </td>
                            <td class="lb-n">{{ $gnf($prixVente) }}</td>
                            <td class="lb-n {{ $marge < 0 ? 'co-perte' : '' }}">{{ $gnf($marge) }}@if($marge < 0)<span class="lb-sous" style="color:var(--hali-danger)">vente à perte</span>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="hl-vide" style="padding:20px">Aucune correspondance : elles se créent au premier envoi d'analyses.</div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <p class="lb-aide" style="padding:12px 18px; margin:0">Le prix de vente se modifie au catalogue des examens de votre établissement.</p>
        </section>
    @endif
</div></div>
@endsection
