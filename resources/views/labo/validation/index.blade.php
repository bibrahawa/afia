@extends('layouts.backend')
@section('style') @include('labo.partials.styles') @endsection

@section('content')
<div class="container"><div class="page-inner">
    @include('labo.partials.entete', ['titre' => 'Validation des résultats', 'fil' => [route('labo.validation.index') => 'Validation']])

    @can('labo.validation.biologique')
        <h4 class="mb-3">Validation biologique <span class="badge badge-primary">{{ $aValiderBiologique->flatten()->count() }}</span></h4>
        @forelse($aValiderBiologique as $lignes)
            @php($demande = $lignes->first()->demande)
            <div class="card {{ $demande->urgence ? 'labo-urgent' : '' }}">
                <div class="card-header d-flex align-items-center">
                    <div><h4 class="card-title mb-0">{{ $demande->patient->full_name }}</h4>
                        <span class="small text-muted">{{ $demande->numero }} · {{ $demande->patient->gender }} · {{ \App\Support\Labo\ContexteLabo::ageTexte(\App\Support\Labo\ContexteLabo::ageEnJours($demande->patient, $demande->created_at)) }}
                            @if($demande->grossesse)· enceinte @endif</span>
                        @if($demande->renseignements_cliniques)<br><span class="small">{{ $demande->renseignements_cliniques }}</span>@endif
                    </div>
                    <a href="{{ route('labo.demandes.show', $demande) }}" class="btn btn-link btn-sm ms-auto">Dossier complet</a>
                </div>
                <div class="card-body">
                    @foreach($lignes as $l)
                        <div class="border rounded p-2 mb-3">
                            <div class="d-flex align-items-center mb-1">
                                <strong>{{ $l->examen_nom }}</strong>
                                <span class="small text-muted ms-2">validé techniquement {{ $l->valide_technique_le?->diffForHumans() }}</span>
                                <a href="{{ route('labo.paillasse.saisie', $l) }}" class="small ms-auto">Voir la saisie</a>
                            </div>
                            <table class="table table-sm mb-2">
                                @foreach($l->resultats->filter->aUneValeur() as $r)
                                    <tr class="{{ $r->flag?->estCritique() ? 'labo-ligne-critique' : '' }}">
                                        <td style="width:40%">{{ $r->libelle }}</td>
                                        <td><span class="{{ $r->flag?->classeCss() }}">{{ $r->valeurAffichee() }} {{ $r->flag?->symbole() }}</span></td>
                                        <td class="small">{{ $r->unite }}</td>
                                        <td class="small text-muted">{{ $r->normeAffichee() }}</td>
                                        <td class="small">
                                            @if($r->flag?->estCritique())
                                                @if($r->alertes->where('signale_le', '>=', $r->saisi_le)->isNotEmpty())<span class="text-success">appel tracé</span>
                                                @else<span class="text-danger fw-bold">appel NON tracé</span>@endif
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                @foreach($l->germesIsoles as $g)
                                    <tr><td>Germe</td><td colspan="4"><strong>{{ $g->germe_nom }}</strong> {{ $g->numeration }}
                                        <span class="small">— R : {{ $g->antibiogramme->where('interpretation', 'R')->pluck('antibiotique_nom')->implode(', ') ?: 'aucun' }}</span></td></tr>
                                @endforeach
                            </table>
                            <form method="POST" action="{{ route('labo.validation.biologique', $l) }}" class="d-flex gap-2">@csrf
                                <input name="commentaire" class="form-control form-control-sm" placeholder="Commentaire du biologiste (imprimé sur le compte rendu)">
                                <button class="btn btn-success btn-sm text-nowrap">Valider</button>
                                <button type="button" class="btn btn-outline-warning btn-sm text-nowrap btn-renvoyer" data-action="{{ route('labo.validation.renvoyer', $l) }}" data-nom="{{ $l->examen_nom }}">Renvoyer</button>
                            </form>
                        </div>
                    @endforeach
                    @can('labo.compte_rendu.publier')
                        <p class="small text-muted mb-0">Après validation, publiez depuis la <a href="{{ route('labo.demandes.show', $demande) }}">fiche de la demande</a>.</p>
                    @endcan
                </div>
            </div>
        @empty
            <div class="card"><div class="card-body text-muted">Aucun examen en attente de validation biologique.</div></div>
        @endforelse
    @endcan

    <h4 class="mb-3 mt-4">Validation technique <span class="badge badge-secondary">{{ $aValiderTechnique->count() }}</span></h4>
    <div class="card"><div class="card-body table-responsive">
        <table class="table table-sm align-middle">
            <thead><tr><th>Examen</th><th>Patient</th><th>Demande</th><th>Saisi</th><th></th></tr></thead>
            <tbody>
            @forelse($aValiderTechnique as $l)
                <tr>
                    <td>{{ $l->examen_nom }} @if($l->resultats->contains(fn ($r) => $r->flag?->estCritique()))<span class="labo-flag-critique">critique</span>@endif</td>
                    <td>{{ $l->demande->patient->full_name }}</td>
                    <td>{{ $l->demande->numero }}</td>
                    <td class="small">{{ $l->updated_at->diffForHumans() }}</td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('labo.paillasse.saisie', $l) }}" class="btn btn-sm btn-outline-primary">Vérifier</a>
                        <form method="POST" action="{{ route('labo.validation.technique', $l) }}" class="d-inline">@csrf<button class="btn btn-sm btn-primary">Valider</button></form>
                        @can('labo.validation.biologique')
                            <form method="POST" action="{{ route('labo.validation.complete', $l) }}" class="d-inline">@csrf<button class="btn btn-sm btn-success" title="Petit labo : technique + biologique">Valider tout</button></form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-muted text-center">Rien à valider techniquement.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>
</div></div>

<div class="modal fade" id="modalRenvoyer" tabindex="-1"><div class="modal-dialog"><form method="POST" id="formRenvoyer" class="modal-content">@csrf
    <div class="modal-header"><h5 class="modal-title" id="renvoyerTitre"></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><label class="form-label">Motif (repasser, contrôler, diluer…)</label><input name="motif" required maxlength="255" class="form-control"></div>
    <div class="modal-footer"><button class="btn btn-warning">Renvoyer à la paillasse</button></div>
</form></div></div>
@endsection

@section('script')
<script>
document.querySelectorAll('.btn-renvoyer').forEach(b => b.addEventListener('click', function () {
    document.getElementById('formRenvoyer').action = this.dataset.action;
    document.getElementById('renvoyerTitre').textContent = 'Renvoyer « ' + this.dataset.nom + ' »';
    new bootstrap.Modal(document.getElementById('modalRenvoyer')).show();
}));
</script>
@endsection
