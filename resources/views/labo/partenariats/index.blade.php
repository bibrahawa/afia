@extends('layouts.backend')

@section('style') @include('labo.partials.styles') @endsection

@section('content')
<div class="container"><div class="page-inner">
    @include('labo.partials.entete', ['titre' => 'Cliniques partenaires', 'fil' => [route('labo.partenariats.index') => 'Partenariats']])

    <p class="text-muted">Une clinique partenaire voit votre catalogue, vous envoie ses demandes et lit les comptes rendus qu'elle a prescrits. Elle ne voit rien d'autre de votre laboratoire.</p>

    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h4 class="card-title">Partenariats</h4>
            @if($candidats->isNotEmpty())
                <button class="btn btn-primary btn-sm ms-auto" data-bs-toggle="modal" data-bs-target="#modalPartenariat"><i class="fa fa-plus"></i> Nouveau partenariat</button>
            @endif
        </div>
        <div class="card-body table-responsive">
            <table class="table table-sm align-middle">
                <thead><tr><th>Clinique</th><th>Facturation</th><th class="text-end">Remise</th><th class="text-center">Demandes</th><th>Dernière</th><th>Statut</th><th></th></tr></thead>
                <tbody>
                @forelse($lignes as $ligne)
                    @php $p = $ligne['partenariat']; $a = $ligne['activite']; @endphp
                    <tr class="{{ $p->estActif() ? '' : 'text-muted' }}">
                        <td><strong>{{ $p->clinique?->nom }}</strong>@if($p->contact_nom)<div class="small text-muted">{{ $p->contact_nom }} {{ $p->contact_telephone }}</div>@endif</td>
                        <td class="small">{{ $p->mode_facturation_defaut === 'partenaire' ? 'Facturée à la clinique' : 'Encaissée au patient' }}
                            @if($p->delai_paiement_jours)<div class="text-muted">paiement à {{ $p->delai_paiement_jours }} j</div>@endif</td>
                        <td class="text-end">{{ rtrim(rtrim(number_format((float) $p->remise_pourcentage, 2, ',', ''), '0'), ',') }} %</td>
                        <td class="text-center">{{ $a['total'] }}<div class="small text-muted">{{ $a['en_cours'] }} en cours · {{ $a['ce_mois'] }} ce mois</div></td>
                        <td class="small">{{ $a['derniere'] ? \Carbon\Carbon::parse($a['derniere'])->format('d/m/Y') : '—' }}</td>
                        <td>
                            <span class="badge badge-{{ $p->estActif() ? 'success' : ($p->estPropose() ? 'warning' : 'secondary') }}">{{ $p->libelleStatut() }}</span>
                            @if($p->motif_refus)<div class="small text-danger">{{ $p->motif_refus }}</div>@endif
                        </td>
                        <td class="text-end text-nowrap">
                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalModifier{{ $p->id }}"><i class="fa fa-edit"></i></button>
                            @unless($p->estPropose())
                                <form method="POST" action="{{ route('labo.partenariats.basculer', $p) }}" class="d-inline"
                                      onsubmit="return confirm('{{ $p->estActif() ? 'Suspendre ce partenariat ? La clinique ne pourra plus envoyer de demande.' : 'Réactiver ce partenariat ?' }}');">@csrf
                                    <button class="btn btn-sm btn-outline-secondary">{{ $p->estActif() ? 'Suspendre' : 'Réactiver' }}</button></form>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Aucun partenariat. Ouvrez-en un pour recevoir les demandes d'une clinique.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
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
        <div class="modal-footer"><button class="btn btn-primary">Ouvrir le partenariat</button></div>
    </form>
</div></div>

@foreach($lignes as $ligne)
    @php $p = $ligne['partenariat']; @endphp
    <div class="modal fade" id="modalModifier{{ $p->id }}" tabindex="-1"><div class="modal-dialog">
        <form method="POST" action="{{ route('labo.partenariats.update', $p) }}" class="modal-content">@csrf @method('PUT')
            <div class="modal-header"><h5 class="modal-title">{{ $p->clinique?->nom }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body row g-2">@include('labo.partenariats._champs', ['partenariat' => $p])</div>
            <div class="modal-footer"><button class="btn btn-primary">Enregistrer</button></div>
        </form>
    </div></div>
@endforeach
@endsection
