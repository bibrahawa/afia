@extends('layouts.backend')
@section('style')
    @include('labo.partials.styles')
    <style>
        .ds-barre { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
        .ds-barre form { margin: 0; }
        .ds-examen-nom { color: var(--hali-encre); font-weight: 600; }
        .ds-marques { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 3px; }
        .ds-cr { display: flex; flex-wrap: wrap; align-items: center; gap: 10px 14px; padding: 12px 18px; border-top: 1px solid #f3f4f6; }
        .ds-cr:first-child { border-top: 0; }
        .ds-cr.est-remplace { opacity: .6; }
        .ds-montants { display: grid; gap: 6px; margin: 0; padding: 12px 14px; border-radius: 10px; background: #f9fafb; font-size: .85rem; }
        .ds-montants div { display: flex; justify-content: space-between; }
        .ds-montants dd { margin: 0; font-weight: 700; color: var(--hali-encre); font-variant-numeric: tabular-nums; }
    </style>
@endsection

@php
    use App\Enums\Labo\ModeFacturation;
    use App\Enums\Labo\StatutExamen;
    use App\Enums\Labo\StatutEchantillon;
    $patient = $demande->patient;
    $aPublier = $demande->examens->where('statut', StatutExamen::VALIDE_BIOLOGIQUE)->count();
    $declarationsMdo = \App\Models\Labo\LaboDeclarationMdo::whereIn('demande_examen_id', $demande->examens->pluck('id'))->get()->keyBy('demande_examen_id');
@endphp

@section('content')
<div class="container"><div class="page-inner hl">
    @include('labo.partials.entete', ['titre' => 'Demande ' . $demande->numero, 'fil' => [route('labo.demandes.index') => 'Demandes', 0 => $demande->numero]])

    @php($initiales = mb_strtoupper(mb_substr((string) $patient->first_name, 0, 1) . mb_substr((string) $patient->last_name, 0, 1)))

    {{-- ------------------------------------------------ Patient et actions --}}
    <section class="hl-bloc {{ $demande->urgence ? 'labo-urgent' : '' }}" style="margin-bottom:16px">
        <div class="lb-patient">
            <span class="hl-avatar {{ $demande->urgence ? 'est-urgent' : '' }}" aria-hidden="true">{{ $initiales }}</span>
            <div>
                <div class="lb-patient-nom">
                    {{ $patient->full_name }}
                    @if($demande->urgence) <span class="labo-pastille-urgent"><i class="fas fa-bolt" aria-hidden="true"></i> Urgent</span>@endif
                </div>
                <div class="lb-patient-meta">
                    <span>{{ $patient->gender }} · {{ \App\Support\Labo\ContexteLabo::ageTexte(\App\Support\Labo\ContexteLabo::ageEnJours($patient, $demande->created_at)) }}</span>
                    @if($demande->grossesse)<span><strong>Enceinte</strong>@if($demande->semaines_amenorrhee) ({{ $demande->semaines_amenorrhee }} SA)@endif</span>@endif
                    <span>{{ $patient->identifiant_national_sante }}</span>
                    <span><i class="fa fa-phone" aria-hidden="true"></i> {{ $patient->telephone ?? '—' }}</span>
                </div>
            </div>
            <div class="lb-droite ds-barre">
                <span class="badge badge-{{ $demande->statut->couleur() }}" style="font-size:.8rem">{{ $demande->statut->libelle() }}</span>
                @can('labo.prelevement')
                    <a href="{{ route('labo.demandes.etiquettes', $demande) }}" target="_blank" class="hl-bouton"><i class="fa fa-barcode" aria-hidden="true"></i> Étiquettes</a>
                @endcan
                @if($aPublier)
                    @can('labo.compte_rendu.publier')
                        <form method="POST" action="{{ route('labo.demandes.publier', $demande) }}">@csrf
                            <button class="hl-bouton hl-bouton-plein"><i class="fa fa-paper-plane" aria-hidden="true"></i> Publier {{ $aPublier }} examen{{ $aPublier > 1 ? 's' : '' }}</button>
                        </form>
                    @endcan
                @endif
                @if(! $demande->estAnnulee())
                    @can('labo.demande.cancel')
                        <button type="button" class="hl-bouton lb-risque" data-bs-toggle="modal" data-bs-target="#modalAnnuler">Annuler la demande</button>
                    @endcan
                @endif
            </div>
        </div>
        @if(\App\Support\Labo\ContexteLabo::ageEnJours($patient) === null)
            <p class="lb-alerte lb-alerte-avert" style="margin:0 18px 14px"><i class="fas fa-exclamation-circle mt-1" aria-hidden="true"></i><span>Âge inconnu : seules les normes sans critère d'âge s'appliqueront. Corrigez la date de naissance du patient.</span></p>
        @endif
        @if($demande->estAnnulee())
            <p class="lb-alerte lb-alerte-erreur" style="margin:0 18px 14px"><i class="fas fa-ban mt-1" aria-hidden="true"></i><span><strong>Demande annulée</strong> : {{ $demande->motif_annulation }}</span></p>
        @endif
    </section>

    <div class="lb-grille">
        <div class="lb-colonne">
            <section class="hl-bloc">
                <h2 class="hl-bloc-titre">Prescription</h2>
                <dl class="lb-infos">
                    <div><dt>Origine</dt><dd>{{ $demande->origine->libelle() }}</dd></div>
                    <div><dt>Prescripteur</dt><dd>{{ $demande->nomPrescripteur() }} {{ $demande->prescripteur_telephone }}</dd></div>
                    @if($demande->renseignements_cliniques)<div><dt>Renseignements</dt><dd style="font-weight:500">{{ $demande->renseignements_cliniques }}</dd></div>@endif
                    @if($demande->consultation && Route::has('consultation.show'))
                        <div><dt>Consultation</dt><dd><a href="{{ route('consultation.show', $demande->consultation) }}"><i class="fas fa-stethoscope" aria-hidden="true"></i> du {{ $demande->consultation->created_at->format('d/m/Y') }}</a></dd></div>
                    @endif
                    <div><dt>Enregistrée</dt><dd style="font-weight:500">le {{ $demande->created_at->format('d/m/Y à H:i') }}<span class="lb-sous">par {{ $demande->enregistrePar?->name }}</span></dd></div>
                </dl>
            </section>

            <section class="hl-bloc">
                <h2 class="hl-bloc-titre">Facturation</h2>
                <div class="lb-form">
                    @if($demande->mode_facturation !== ModeFacturation::LABO)
                        <span class="hl-statut hl-s-neutre" style="justify-self:start">{{ $demande->mode_facturation->libelle() }}</span>
                    @elseif(! $demande->transaction)
                        <div class="ds-montants"><div><dt>Non facturée</dt><dd>{{ number_format($demande->examens->where('statut', '!=', StatutExamen::ANNULE)->sum('prix_applique'), 0, ',', ' ') }} GNF</dd></div></div>
                        @can('labo.facturation')
                            <form method="POST" action="{{ route('labo.demandes.facturer', $demande) }}">@csrf<button class="hl-bouton hl-bouton-plein" style="width:100%">Créer la facture</button></form>
                        @endcan
                    @else
                        @php($inv = $demande->transaction->invoice)
                        <span class="lb-sous">Facture <strong>{{ $demande->transaction->invoice_no }}</strong></span>
                        @if($inv)
                            <dl class="ds-montants">
                                <div><dt>Total</dt><dd>{{ number_format($inv->total_amount, 0, ',', ' ') }} GNF</dd></div>
                                <div><dt>Part assurance</dt><dd>{{ number_format($inv->insurance_amount, 0, ',', ' ') }}</dd></div>
                                <div><dt>Part patient</dt><dd>{{ number_format($inv->patient_amount, 0, ',', ' ') }}</dd></div>
                            </dl>
                        @endif
                        <div class="ds-barre">
                            <span class="hl-statut {{ $demande->partPatientReglee() ? 'hl-s-succes' : 'hl-s-danger' }}">{{ $demande->partPatientReglee() ? 'Part patient réglée' : 'Part patient non réglée' }}</span>
                            @if(! $demande->partPatientReglee() && Route::has('account.facture'))
                                @can('account.facture')<a href="{{ route('account.facture') }}" class="hl-bouton lb-petit"><i class="fa fa-money-bill" aria-hidden="true"></i> Encaisser</a>@endcan
                            @endif
                        </div>
                    @endif
                    @if($demande->resultats_retenus_si_impaye && ! $demande->partPatientReglee())
                        <p class="lb-aide">Résultats retenus pour le patient jusqu'au règlement (le prescripteur interne y a accès).</p>
                    @endif
                </div>
            </section>
        </div>

        <div class="lb-colonne">
            {{-- ------------------------------------------------ Examens --}}
            <section class="hl-bloc">
                <h2 class="hl-bloc-titre">Examens <small>{{ $demande->examens->count() }}</small></h2>
                <div class="table-responsive">
                    <table class="lb-table">
                        <thead><tr><th>Examen</th><th class="lb-n">Prix</th><th>Statut</th><th>Biologiste</th><th></th></tr></thead>
                        <tbody>
                        @foreach($demande->examens as $l)
                            <tr class="{{ $l->statut === StatutExamen::ANNULE ? 'est-barre' : '' }}">
                                <td>
                                    <span class="ds-examen-nom">{{ $l->examen_nom }}</span>
                                    <div class="ds-marques">
                                        @if($l->sous_traite)<span class="hl-statut hl-s-neutre">Sous-traité · {{ $l->laboratoire_sous_traitant }}</span>@endif
                                        @if($l->nombre_rectifications)<span class="hl-statut hl-s-alerte">Rectifié ×{{ $l->nombre_rectifications }}</span>@endif
                                        @if($mdo = $declarationsMdo->get($l->id))
                                            <span class="hl-statut {{ $mdo->statut === 'a_declarer' ? 'hl-s-danger' : 'hl-s-neutre' }}" title="Maladie à déclaration obligatoire">MDO {{ $mdo->maladie }} · {{ \App\Models\Labo\LaboDeclarationMdo::STATUTS[$mdo->statut] }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="lb-n">{{ number_format($l->prix_applique, 0, ',', ' ') }}</td>
                                <td><span class="badge badge-{{ $l->statut->couleur() }}">{{ $l->statut->libelle() }}</span></td>
                                <td style="font-size:.84rem">{{ $l->validateurBiologique?->name }}</td>
                                <td class="lb-actions">
                                    @if($l->statut->permetSaisie())
                                        @can('labo.resultat.saisir')<a href="{{ route('labo.paillasse.saisie', $l) }}" class="hl-bouton lb-petit">{{ $l->statut === StatutExamen::VALIDE_TECHNIQUE ? 'Corriger' : 'Résultats' }}</a>@endcan
                                    @endif
                                    @if($l->statut === StatutExamen::PUBLIE || $l->statut === StatutExamen::VALIDE_BIOLOGIQUE)
                                        @can('labo.validation.biologique')
                                            <button type="button" class="hl-bouton lb-petit btn-rouvrir" data-action="{{ route('labo.validation.rouvrir', $l) }}" data-nom="{{ $l->examen_nom }}">Rectifier</button>
                                        @endcan
                                    @endif
                                    @if(! $l->estVerrouille() && $l->statut !== StatutExamen::ANNULE)
                                        @can('labo.demande.cancel')
                                            <button type="button" class="hl-bouton lb-petit lb-risque btn-annuler-examen" data-action="{{ route('labo.demandes.examens.annuler', $l) }}" data-nom="{{ $l->examen_nom }}" title="Annuler cet examen" aria-label="Annuler {{ $l->examen_nom }}"><i class="fa fa-times"></i></button>
                                        @endcan
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- ------------------------------------------------ Échantillons --}}
            <section class="hl-bloc">
                <h2 class="hl-bloc-titre">Échantillons <small>{{ $demande->echantillons->count() }}</small></h2>
                <div class="table-responsive">
                    <table class="lb-table">
                        <thead><tr><th>Tube</th><th>Examens</th><th>Statut</th><th></th></tr></thead>
                        <tbody>
                        @foreach($demande->echantillons as $e)
                            <tr>
                                <td>@if($e->tube)<span class="labo-tube labo-tube-{{ $e->tube }}"></span>@endif<span class="lb-fort">{{ $e->libelleContenant() }}</span><span class="lb-sous"><code>{{ $e->code_barres }}</code></span></td>
                                <td style="font-size:.84rem">{{ $e->examens->pluck('examen_nom')->implode(', ') }}</td>
                                <td>
                                    <span class="badge badge-{{ $e->statut->couleur() }}">{{ $e->statut->libelle() }}</span>
                                    @if($e->statut === StatutEchantillon::REJETE)<span class="lb-sous" style="color:var(--hali-danger)">{{ StatutEchantillon::motifsRejet()[$e->motif_rejet] ?? $e->motif_rejet }}</span>@endif
                                    @if($e->preleve_le)<span class="lb-sous">Prélevé {{ $e->preleve_le->format('d/m H:i') }} {{ $e->preleveur?->name }}</span>@endif
                                </td>
                                <td class="lb-actions">
                                    @if($e->statut === StatutEchantillon::ATTENDU && ! $demande->estAnnulee())
                                        @can('labo.prelevement')
                                            <form method="POST" action="{{ route('labo.echantillons.preleve', $e) }}">@csrf
                                                <button class="hl-bouton lb-petit">Prélevé</button>
                                                @can('labo.reception')<button name="et_recu" value="1" class="hl-bouton hl-bouton-plein lb-petit">Prélevé et reçu</button>@endcan
                                            </form>
                                        @endcan
                                    @elseif($e->statut === StatutEchantillon::PRELEVE)
                                        @can('labo.reception')
                                            <form method="POST" action="{{ route('labo.echantillons.recu', $e) }}">@csrf<button class="hl-bouton hl-bouton-plein lb-petit">Reçu</button></form>
                                        @endcan
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- ------------------------------------------------ Comptes rendus --}}
            <section class="hl-bloc">
                <h2 class="hl-bloc-titre" style="flex-wrap:wrap">
                    Comptes rendus
                    @if($demande->comptesRendus->isNotEmpty())
                        <span class="ds-barre" style="margin-left:auto">
                            @can('labo.compte_rendu.view')
                                <button type="button" class="hl-bouton hl-bouton-plein lb-petit" data-bs-toggle="modal" data-bs-target="#modalRemise">
                                    <i class="fas fa-hand-holding-medical" aria-hidden="true"></i> Remettre le compte rendu (v{{ $demande->comptesRendus->first()->version }})
                                </button>
                            @endcan
                            @can('labo.compte_rendu.publier')
                                <form method="POST" action="{{ route('labo.demandes.sms', $demande) }}">@csrf
                                    <button class="hl-bouton lb-petit"><i class="fa fa-sms" aria-hidden="true"></i> Renvoyer le SMS</button></form>
                            @endcan
                        </span>
                    @endif
                </h2>
                @forelse($demande->comptesRendus as $cr)
                    <div class="ds-cr {{ $loop->first ? '' : 'est-remplace' }}">
                        <div>
                            <span class="lb-fort">Version {{ $cr->version }}</span>
                            @if($loop->first)<span class="hl-statut hl-s-succes">En vigueur</span>@else<span class="hl-statut hl-s-neutre">Remplacé</span>@endif
                            @if($cr->est_rectificatif)<span class="hl-statut hl-s-alerte">Rectificatif</span>@endif
                            @if($cr->est_partiel)<span class="hl-statut hl-s-info">Partiel</span>@endif
                            <span class="lb-sous">{{ $cr->publie_le->format('d/m/Y à H:i') }} par {{ $cr->publiePar?->name }} · SMS : {{ $cr->sms_envoye_le ? $cr->sms_envoye_le->format('d/m à H:i') : 'non envoyé' }}</span>
                            @if($cr->motif_rectification)<span class="lb-sous">{{ $cr->motif_rectification }}</span>@endif
                        </div>
                        @can('labo.compte_rendu.view')
                            <a href="{{ route('labo.comptes-rendus.pdf', $cr) }}" target="_blank" class="hl-bouton lb-petit" style="margin-left:auto"><i class="fa fa-file-pdf" aria-hidden="true"></i> PDF</a>
                        @endcan
                    </div>
                @empty
                    <div class="hl-vide" style="padding:24px">Aucun compte rendu publié.</div>
                @endforelse

                @if($demande->remises->isNotEmpty())
                    <h3 class="hl-bloc-titre" style="border-top:1px solid var(--hali-bordure); font-size:.88rem">Remises en main propre</h3>
                    <div class="table-responsive">
                        <table class="lb-table">
                            <thead><tr><th>Date</th><th>Version</th><th>Remis à</th><th>Pièce</th><th>Par</th></tr></thead>
                            <tbody>
                            @foreach($demande->remises as $r)
                                <tr class="{{ $r->avant_reglement ? 'table-warning' : '' }}">
                                    <td>{{ $r->remis_le->format('d/m/Y H:i') }}</td>
                                    <td>v{{ $r->version }}</td>
                                    <td>{{ $r->nom_beneficiaire }} <span class="lb-sous" style="display:inline">({{ $r->lien_patient ?? \App\Models\Labo\LaboRemise::BENEFICIAIRES[$r->beneficiaire] ?? $r->beneficiaire }})</span>
                                        @if($r->avant_reglement)<span class="lb-sous" style="color:var(--hali-alerte)"><strong>Avant règlement :</strong> {{ $r->motif_derogation }}</span>@endif</td>
                                    <td>{{ \App\Models\Labo\LaboRemise::PIECES[$r->piece_justificative] ?? '—' }}</td>
                                    <td>{{ $r->remisPar?->name }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>
    </div>
</div></div>

{{-- Modales --}}
<div class="modal fade" id="modalAnnuler" tabindex="-1"><div class="modal-dialog"><form method="POST" action="{{ route('labo.demandes.annuler', $demande) }}" class="modal-content">@csrf
    <div class="modal-header"><h5 class="modal-title">Annuler la demande {{ $demande->numero }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><label class="form-label">Motif (obligatoire, tracé)</label><input name="motif" required maxlength="255" class="form-control"></div>
    <div class="modal-footer"><button class="btn btn-danger">Confirmer l'annulation</button></div>
</form></div></div>

@if($demande->comptesRendus->isNotEmpty())
@php($reglee = $demande->peutEtreRemisAuPatient())
<div class="modal fade" id="modalRemise" tabindex="-1"><div class="modal-dialog"><form method="POST" action="{{ route('labo.demandes.remettre', $demande) }}" class="modal-content">@csrf
    <div class="modal-header"><h5 class="modal-title">Remise du compte rendu v{{ $demande->comptesRendus->first()->version }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        @unless($reglee)
            <div class="alert alert-warning small">Part patient <strong>non réglée</strong>. Remise au patient ou à un représentant possible uniquement avec la permission facturation et un motif. La remise au prescripteur reste libre.</div>
        @endunless
        <label class="form-label">Remis à</label>
        <select name="beneficiaire" id="remiseBeneficiaire" class="form-select mb-2">
            @foreach(\App\Models\Labo\LaboRemise::BENEFICIAIRES as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach
        </select>
        <div data-remise="nom" hidden>
            <label class="form-label">Nom de la personne</label>
            <input name="nom_beneficiaire" class="form-control mb-2" maxlength="150">
        </div>
        <div data-remise="representant" hidden>
            <label class="form-label">Lien avec le patient</label>
            <select name="lien_patient" class="form-select mb-2"><option value=""></option>@foreach(\App\Models\Labo\LaboRemise::LIENS as $l)<option>{{ $l }}</option>@endforeach</select>
        </div>
        <label class="form-label">Pièce présentée</label>
        <select name="piece_justificative" class="form-select mb-2"><option value=""></option>@foreach(\App\Models\Labo\LaboRemise::PIECES as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select>
        <p class="small text-muted">Ne notez jamais le numéro de la pièce, seulement son type.</p>
        @unless($reglee)
            @can('labo.facturation')
                <div data-remise="derogation">
                    <label class="form-label">Motif de remise avant règlement</label>
                    <input name="motif_derogation" class="form-control" maxlength="255" placeholder="Urgence, prise en charge validée…">
                </div>
            @endcan
        @endunless
    </div>
    <div class="modal-footer"><button class="btn btn-success">Enregistrer la remise et imprimer</button></div>
</form></div></div>
@endif

<div class="modal fade" id="modalMotif" tabindex="-1"><div class="modal-dialog"><form method="POST" id="formMotif" class="modal-content">@csrf
    <div class="modal-header"><h5 class="modal-title" id="modalMotifTitre"></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><label class="form-label">Motif (obligatoire, tracé)</label><input name="motif" required minlength="5" maxlength="255" class="form-control"></div>
    <div class="modal-footer"><button class="btn btn-primary">Confirmer</button></div>
</form></div></div>
@endsection

@section('script')
<script>
(function () {
    const choix = document.getElementById('remiseBeneficiaire');
    if (!choix) return;
    const maj = () => {
        document.querySelector('[data-remise="nom"]').hidden = choix.value === 'patient';
        document.querySelector('[data-remise="representant"]').hidden = choix.value !== 'representant';
        const d = document.querySelector('[data-remise="derogation"]');
        if (d) d.hidden = choix.value === 'prescripteur';
    };
    choix.addEventListener('change', maj); maj();
})();
@if(session('labo_imprimer_cr'))
    window.open('{{ route('labo.comptes-rendus.pdf', session('labo_imprimer_cr')) }}', '_blank');
@endif
document.querySelectorAll('.btn-rouvrir, .btn-annuler-examen').forEach(b => b.addEventListener('click', function () {
    document.getElementById('formMotif').action = this.dataset.action;
    document.getElementById('modalMotifTitre').textContent = (this.classList.contains('btn-rouvrir') ? 'Rectifier « ' : 'Annuler « ') + this.dataset.nom + ' »';
    new bootstrap.Modal(document.getElementById('modalMotif')).show();
}));
</script>
@endsection
