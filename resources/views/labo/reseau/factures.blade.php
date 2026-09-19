@extends('layouts.backend')

@php $gnf = fn ($m) => number_format((float) $m, 0, ',', ' '); @endphp

@section('style') @include('labo.partials.styles') @endsection

@section('content')
<div class="container"><div class="page-inner hl">
    @include('labo.partials.entete', ['titre' => 'Factures des laboratoires', 'fil' => [route('labo.reseau.index') => 'Analyses envoyées', 0 => 'Factures'], 'sousTitre' => 'Reste à payer : ' . $gnf($resteDu) . ' GNF'])

    <section class="hl-bloc">
        @forelse($releves as $releve)
            @if($loop->first)
                <div class="table-responsive">
                    <table class="lb-table">
                        <thead><tr><th>Relevé</th><th>Laboratoire</th><th>Période</th><th>Échéance</th><th class="lb-n">Montant</th><th class="lb-n">Reste</th><th></th></tr></thead>
                        <tbody>
            @endif
                            <tr class="{{ $releve->enRetard() ? 'table-warning' : '' }}">
                                <td class="lb-fort">{{ $releve->numero }}</td>
                                <td>{{ $releve->partenariat?->laboratoire?->nom }}</td>
                                <td style="font-size:.84rem">{{ $releve->periode_debut->format('d/m/Y') }} → {{ $releve->periode_fin->format('d/m/Y') }}</td>
                                <td style="font-size:.84rem">{{ $releve->echeance?->format('d/m/Y') ?? '—' }}@if($releve->enRetard())<span class="lb-sous" style="color:var(--hali-danger)">en retard</span>@endif</td>
                                <td class="lb-n">{{ $gnf($releve->montant_total) }}</td>
                                <td class="lb-n lb-fort">{{ $gnf($releve->resteDu()) }}</td>
                                <td class="lb-actions"><a href="{{ route('labo.reseau.facture', $releve->id) }}" class="hl-bouton lb-petit">Détail</a></td>
                            </tr>
            @if($loop->last)
                        </tbody>
                    </table>
                </div>
            @endif
        @empty
            <div class="hl-vide"><i class="fas fa-file-invoice" aria-hidden="true"></i>Aucun relevé reçu.</div>
        @endforelse
        @if($releves->hasPages())
            <div style="padding:14px 18px; border-top:1px solid var(--hali-bordure); display:flex; justify-content:center">{{ $releves->links() }}</div>
        @endif
    </section>
</div></div>
@endsection
