@extends('layouts.backend')
@section('style')
    @include('labo.partials.styles')
    <style>
        .vl-titre { display: flex; align-items: center; gap: 10px; margin: 0 0 12px; color: var(--hali-encre); font-size: 1.05rem; font-weight: 700; }
        .vl-compte { display: inline-grid; place-items: center; min-width: 24px; height: 24px; padding: 0 7px; border-radius: 999px; background: #f3f4f6; color: var(--hali-texte); font-size: .78rem; font-weight: 700; }
        .vl-demande { margin-bottom: 14px; }
        .vl-examen { padding: 14px 18px; border-top: 1px solid var(--hali-bordure); }
        .vl-examen-haut { display: flex; flex-wrap: wrap; align-items: baseline; gap: 6px 12px; margin-bottom: 8px; }
        .vl-examen-haut strong { color: var(--hali-encre); font-size: .95rem; }
        .vl-resultats { width: 100%; border-collapse: collapse; font-size: .86rem; margin-bottom: 10px; }
        .vl-resultats td { padding: 7px 10px; border-top: 1px solid #f3f4f6; }
        .vl-resultats tr:first-child td { border-top: 0; }
        .vl-resultats td:first-child { width: 36%; color: var(--hali-texte); }
        .vl-valeur { font-weight: 700; color: var(--hali-encre); font-variant-numeric: tabular-nums; }
        .vl-decision { display: flex; flex-wrap: wrap; gap: 8px; }
        .vl-decision input { flex: 1 1 260px; min-height: 38px; }
    </style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">
    @include('labo.partials.entete', ['titre' => 'Validation des résultats', 'fil' => [route('labo.validation.index') => 'Validation'], 'sousTitre' => 'Technique d\'abord, puis biologique : rien ne part au patient sans les deux.'])

    @can('labo.validation.biologique')
        <h2 class="vl-titre">Validation biologique <span class="vl-compte">{{ $aValiderBiologique->flatten()->count() }}</span></h2>
        @forelse($aValiderBiologique as $lignes)
            @php
                $demande = $lignes->first()->demande;
                $initiales = mb_strtoupper(mb_substr((string) $demande->patient->first_name, 0, 1) . mb_substr((string) $demande->patient->last_name, 0, 1));
            @endphp
            <section class="hl-bloc vl-demande {{ $demande->urgence ? 'labo-urgent' : '' }}">
                <div class="lb-patient">
                    <span class="hl-avatar {{ $demande->urgence ? 'est-urgent' : '' }}" aria-hidden="true">{{ $initiales }}</span>
                    <div>
                        <div class="lb-patient-nom">{{ $demande->patient->full_name }}
                            @if($demande->urgence) <span class="labo-pastille-urgent"><i class="fas fa-bolt" aria-hidden="true"></i> Urgent</span>@endif</div>
                        <div class="lb-patient-meta">
                            <span>{{ $demande->numero }}</span>
                            <span>{{ $demande->patient->gender }} · {{ \App\Support\Labo\ContexteLabo::ageTexte(\App\Support\Labo\ContexteLabo::ageEnJours($demande->patient, $demande->created_at)) }}</span>
                            @if($demande->grossesse)<span><strong>Enceinte</strong></span>@endif
                        </div>
                        @if($demande->renseignements_cliniques)<span class="lb-sous" style="margin-top:4px">{{ $demande->renseignements_cliniques }}</span>@endif
                    </div>
                    <div class="lb-droite"><a href="{{ route('labo.demandes.show', $demande) }}" class="hl-bouton lb-petit">Dossier complet</a></div>
                </div>

                @foreach($lignes as $l)
                    <div class="vl-examen">
                        <div class="vl-examen-haut">
                            <strong>{{ $l->examen_nom }}</strong>
                            <span class="lb-sous" style="display:inline">validé techniquement {{ $l->valide_technique_le?->diffForHumans() }}</span>
                            <a href="{{ route('labo.paillasse.saisie', $l) }}" style="margin-left:auto; font-size:.82rem; font-weight:600">Voir la saisie</a>
                        </div>
                        <table class="vl-resultats">
                            @foreach($l->resultats->filter->aUneValeur() as $r)
                                <tr class="{{ $r->flag?->estCritique() ? 'labo-ligne-critique' : '' }}">
                                    <td>{{ $r->libelle }}</td>
                                    <td><span class="vl-valeur {{ $r->flag?->classeCss() }}">{{ $r->valeurAffichee() }} {{ $r->flag?->symbole() }}</span> <span class="lb-sous" style="display:inline">{{ $r->unite }}</span></td>
                                    <td class="lb-sous" style="display:table-cell">{{ $r->normeAffichee() }}</td>
                                    <td style="font-size:.8rem">
                                        @if($r->flag?->estCritique())
                                            @if($r->alertes->where('signale_le', '>=', $r->saisi_le)->isNotEmpty())<span class="hl-statut hl-s-succes">Appel tracé</span>
                                            @else<span class="hl-statut hl-s-danger">Appel NON tracé</span>@endif
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            @foreach($l->germesIsoles as $g)
                                <tr><td>Germe</td><td colspan="3"><strong>{{ $g->germe_nom }}</strong> {{ $g->numeration }}
                                    <span class="lb-sous" style="display:inline">— résistant : {{ $g->antibiogramme->where('interpretation', 'R')->pluck('antibiotique_nom')->implode(', ') ?: 'aucun' }}</span></td></tr>
                            @endforeach
                        </table>
                        <form method="POST" action="{{ route('labo.validation.biologique', $l) }}" class="vl-decision">@csrf
                            <input name="commentaire" class="form-control form-control-sm" placeholder="Commentaire du biologiste (imprimé sur le compte rendu)">
                            <button class="hl-bouton hl-bouton-plein"><i class="fas fa-check" aria-hidden="true"></i> Valider</button>
                            <button type="button" class="hl-bouton btn-renvoyer" data-action="{{ route('labo.validation.renvoyer', $l) }}" data-nom="{{ $l->examen_nom }}">Renvoyer à la paillasse</button>
                        </form>
                    </div>
                @endforeach
                @can('labo.compte_rendu.publier')
                    <p class="lb-aide" style="padding:0 18px 14px">Après validation, publiez depuis la <a href="{{ route('labo.demandes.show', $demande) }}">fiche de la demande</a>.</p>
                @endcan
            </section>
        @empty
            <section class="hl-bloc" style="margin-bottom:16px"><div class="hl-vide" style="padding:24px"><i class="fas fa-check-circle" aria-hidden="true" style="color:#a7f3d0"></i>Aucun examen en attente de validation biologique.</div></section>
        @endforelse
    @endcan

    <h2 class="vl-titre" style="margin-top:24px">Validation technique <span class="vl-compte">{{ $aValiderTechnique->count() }}</span></h2>
    <section class="hl-bloc">
        @if($aValiderTechnique->isEmpty())
            <div class="hl-vide" style="padding:24px"><i class="fas fa-check-circle" aria-hidden="true" style="color:#a7f3d0"></i>Rien à valider techniquement.</div>
        @else
            <div class="table-responsive">
                <table class="lb-table">
                    <thead><tr><th>Examen</th><th>Patient</th><th>Saisi</th><th></th></tr></thead>
                    <tbody>
                    @foreach($aValiderTechnique as $l)
                        <tr>
                            <td><span class="lb-fort">{{ $l->examen_nom }}</span> @if($l->resultats->contains(fn ($r) => $r->flag?->estCritique()))<span class="labo-flag-critique">critique</span>@endif</td>
                            <td>{{ $l->demande->patient->full_name }}<span class="lb-sous">{{ $l->demande->numero }}</span></td>
                            <td style="font-size:.84rem">{{ $l->updated_at->diffForHumans() }}</td>
                            <td class="lb-actions">
                                <a href="{{ route('labo.paillasse.saisie', $l) }}" class="hl-bouton lb-petit">Vérifier</a>
                                <form method="POST" action="{{ route('labo.validation.technique', $l) }}">@csrf<button class="hl-bouton hl-bouton-plein lb-petit">Valider</button></form>
                                @can('labo.validation.biologique')
                                    <form method="POST" action="{{ route('labo.validation.complete', $l) }}">@csrf<button class="hl-bouton lb-petit" title="Petit laboratoire : validations technique et biologique en une fois">Valider tout</button></form>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div></div>

<div class="modal fade" id="modalRenvoyer" tabindex="-1"><div class="modal-dialog"><form method="POST" id="formRenvoyer" class="modal-content">@csrf
    <div class="modal-header"><h5 class="modal-title" id="renvoyerTitre"></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><label class="form-label">Motif (repasser, contrôler, diluer…)</label><input name="motif" required maxlength="255" class="form-control"></div>
    <div class="modal-footer"><button class="hl-bouton hl-bouton-plein">Renvoyer à la paillasse</button></div>
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
