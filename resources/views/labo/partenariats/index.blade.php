@extends('layouts.backend')

@section('style') @include('labo.partials.styles') @endsection

@section('content')
<div class="container"><div class="page-inner hl">
    @include('labo.partials.entete', ['titre' => 'Cliniques partenaires', 'fil' => [route('labo.partenariats.index') => 'Partenariats'], 'sousTitre' => 'Une clinique partenaire voit votre catalogue, vous envoie ses demandes et lit les comptes rendus qu\'elle a prescrits. Rien d\'autre.'])

    <section class="hl-bloc">
        <h2 class="hl-bloc-titre">Partenariats <small>{{ count($lignes) }}</small>
            @if($candidats->isNotEmpty())
                <button type="button" class="hl-bouton hl-bouton-plein lb-petit" data-bs-toggle="modal" data-bs-target="#modalPartenariat" style="margin-left:auto"><i class="fa fa-plus" aria-hidden="true"></i> Nouveau partenariat</button>
            @endif
        </h2>
        @if(count($lignes) === 0)
            <div class="hl-vide"><i class="fas fa-handshake" aria-hidden="true"></i>Aucun partenariat. Ouvrez-en un pour recevoir les demandes d'une clinique.</div>
        @else
            <div class="table-responsive">
                <table class="lb-table">
                    <thead><tr><th>Clinique</th><th>Facturation</th><th class="lb-n">Remise</th><th class="lb-n">Demandes</th><th>Dernière</th><th>Statut</th><th></th></tr></thead>
                    <tbody>
                    @foreach($lignes as $ligne)
                        @php $p = $ligne['partenariat']; $a = $ligne['activite']; @endphp
                        <tr style="{{ $p->estActif() ? '' : 'opacity:.65' }}">
                            <td><span class="lb-fort">{{ $p->clinique?->nom }}</span>@if($p->contact_nom)<span class="lb-sous">{{ $p->contact_nom }} {{ $p->contact_telephone }}</span>@endif</td>
                            <td style="font-size:.84rem">{{ $p->mode_facturation_defaut === 'partenaire' ? 'Facturée à la clinique' : 'Encaissée au patient' }}
                                @if($p->delai_paiement_jours)<span class="lb-sous">paiement à {{ $p->delai_paiement_jours }} j</span>@endif</td>
                            <td class="lb-n">{{ rtrim(rtrim(number_format((float) $p->remise_pourcentage, 2, ',', ''), '0'), ',') }} %</td>
                            <td class="lb-n"><span class="lb-fort">{{ $a['total'] }}</span><span class="lb-sous">{{ $a['en_cours'] }} en cours · {{ $a['ce_mois'] }} ce mois</span></td>
                            <td style="font-size:.84rem">{{ $a['derniere'] ? \Carbon\Carbon::parse($a['derniere'])->format('d/m/Y') : '—' }}</td>
                            <td>
                                <span class="hl-statut {{ $p->estActif() ? 'hl-s-succes' : ($p->estPropose() ? 'hl-s-alerte' : 'hl-s-neutre') }}">{{ $p->libelleStatut() }}</span>
                                @if($p->motif_refus)<span class="lb-sous" style="color:var(--hali-danger)">{{ $p->motif_refus }}</span>@endif
                            </td>
                            <td class="lb-actions">
                                <button type="button" class="hl-bouton lb-petit" data-bs-toggle="modal" data-bs-target="#modalModifier{{ $p->id }}">Modifier</button>
                                @unless($p->estPropose())
                                    <form method="POST" action="{{ route('labo.partenariats.basculer', $p) }}"
                                          onsubmit="return confirm('{{ $p->estActif() ? 'Suspendre ce partenariat ? La clinique ne pourra plus envoyer de demande.' : 'Réactiver ce partenariat ?' }}');">@csrf
                                        <button class="hl-bouton lb-petit {{ $p->estActif() ? 'lb-risque' : '' }}">{{ $p->estActif() ? 'Suspendre' : 'Réactiver' }}</button></form>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div></div>

<div class="modal fade" id="modalPartenariat" tabindex="-1"><div class="modal-dialog">
    <form method="POST" action="{{ route('labo.partenariats.store') }}" class="modal-content">@csrf
        <div class="modal-header"><h5 class="modal-title">Nouveau partenariat</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body row g-2">
            <div class="col-12"><label class="form-label">Clinique *</label>
                <select name="clinique_id" class="form-control" required>
                    @foreach($candidats as $candidat)<option value="{{ $candidat->id }}">{{ $candidat->nom }} ({{ $candidat->type }})</option>@endforeach
                </select></div>
            @include('labo.partenariats._champs', ['partenariat' => null])
        </div>
        <div class="modal-footer"><button class="hl-bouton hl-bouton-plein">Ouvrir le partenariat</button></div>
    </form>
</div></div>

@foreach($lignes as $ligne)
    @php $p = $ligne['partenariat']; @endphp
    <div class="modal fade" id="modalModifier{{ $p->id }}" tabindex="-1"><div class="modal-dialog">
        <form method="POST" action="{{ route('labo.partenariats.update', $p) }}" class="modal-content">@csrf @method('PUT')
            <div class="modal-header"><h5 class="modal-title">{{ $p->clinique?->nom }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body row g-2">@include('labo.partenariats._champs', ['partenariat' => $p])</div>
            <div class="modal-footer"><button class="hl-bouton hl-bouton-plein">Enregistrer</button></div>
        </form>
    </div></div>
@endforeach
@endsection
