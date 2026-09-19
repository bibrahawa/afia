@extends('layouts.backend')

@section('style') @include('labo.partials.styles') @endsection

@section('content')
<div class="container"><div class="page-inner hl">
    @include('labo.partials.entete', ['titre' => 'Propositions de laboratoires', 'fil' => [route('labo.reseau.index') => 'Analyses envoyées', 0 => 'Propositions'], 'sousTitre' => 'Un laboratoire vous ouvre son catalogue. Tant que vous n\'avez pas accepté, aucune analyse ne peut lui être envoyée.'])

    <section class="hl-bloc">
        @forelse($propositions as $p)
            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:10px 16px; padding:16px 18px; {{ $loop->first ? '' : 'border-top:1px solid var(--hali-bordure)' }}">
                <div style="flex:1 1 320px">
                    <span class="lb-fort" style="font-size:1rem">{{ $p->laboratoire?->nom }}</span>
                    @if($p->contact_nom)<span class="lb-sous">{{ $p->contact_nom }} {{ $p->contact_telephone }}</span>@endif
                    <div style="display:flex; flex-wrap:wrap; gap:6px; margin-top:8px">
                        <span class="hl-statut hl-s-info">Remise {{ rtrim(rtrim(number_format((float) $p->remise_pourcentage, 2, ',', ''), '0'), ',') }} %</span>
                        <span class="hl-statut hl-s-neutre">{{ $p->mode_facturation_defaut === 'partenaire' ? 'Le laboratoire vous facture' : 'Le laboratoire encaisse le patient' }}</span>
                        @if($p->clinique_facture_patient)<span class="hl-statut hl-s-neutre">Vous facturez votre patient</span>@endif
                        @if($p->delai_paiement_jours)<span class="hl-statut hl-s-neutre">Paiement à {{ $p->delai_paiement_jours }} jours</span>@endif
                    </div>
                    @if($p->notes)<span class="lb-sous" style="margin-top:6px">{{ $p->notes }}</span>@endif
                    <span class="lb-sous" style="margin-top:4px">Proposé le {{ $p->propose_le?->format('d/m/Y') }}</span>
                </div>
                <div style="display:flex; flex-wrap:wrap; gap:8px; align-items:flex-start">
                    <form method="POST" action="{{ route('labo.reseau.propositions.accepter', $p->id) }}" style="margin:0">@csrf
                        <button class="hl-bouton hl-bouton-plein">Accepter</button></form>
                    <details style="position:relative">
                        <summary class="hl-bouton lb-risque" style="list-style:none">Refuser</summary>
                        <form method="POST" action="{{ route('labo.reseau.propositions.refuser', $p->id) }}" class="hl-bloc" style="position:absolute; right:0; z-index:10; width:280px; display:grid; gap:8px; padding:12px; margin-top:6px; box-shadow:var(--hali-ombre-forte)">@csrf
                            <input name="motif_refus" class="form-control form-control-sm" maxlength="255" placeholder="Motif (facultatif)">
                            <button class="hl-bouton lb-plein-risque">Confirmer le refus</button>
                        </form>
                    </details>
                </div>
            </div>
        @empty
            <div class="hl-vide"><i class="fas fa-handshake" aria-hidden="true"></i>Aucune proposition en attente.</div>
        @endforelse
    </section>
</div></div>
@endsection
