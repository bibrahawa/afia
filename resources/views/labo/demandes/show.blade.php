@extends('layouts.backend')
@section('style') @include('labo.partials.styles') @endsection

@php
    use App\Enums\Labo\ModeFacturation;
    use App\Enums\Labo\StatutExamen;
    use App\Enums\Labo\StatutEchantillon;
    $patient = $demande->patient;
    $aPublier = $demande->examens->where('statut', StatutExamen::VALIDE_BIOLOGIQUE)->count();
@endphp

@section('content')
<div class="container"><div class="page-inner">
    @include('labo.partials.entete', ['titre' => 'Demande ' . $demande->numero, 'fil' => [route('labo.demandes.index') => 'Demandes', 0 => $demande->numero]])

    <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
        <span class="badge badge-{{ $demande->statut->couleur() }} fs-6">{{ $demande->statut->libelle() }}</span>
        @if($demande->urgence)<span class="badge badge-danger fs-6">URGENT</span>@endif
        <div class="ms-auto d-flex flex-wrap gap-2">
            @can('labo.prelevement')
                <a href="{{ route('labo.demandes.etiquettes', $demande) }}" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="fa fa-barcode"></i> Étiquettes</a>
            @endcan
            @if($aPublier)
                @can('labo.compte_rendu.publier')
                    <form method="POST" action="{{ route('labo.demandes.publier', $demande) }}">@csrf
                        <button class="btn btn-success btn-sm"><i class="fa fa-paper-plane"></i> Publier {{ $aPublier }} examen(s)</button>
                    </form>
                @endcan
            @endif
            @if(! $demande->estAnnulee())
                @can('labo.demande.cancel')
                    <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalAnnuler">Annuler la demande</button>
                @endcan
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h4 class="card-title">Patient</h4></div>
                <div class="card-body">
                    <h5 class="mb-1">{{ $patient->full_name }}</h5>
                    <p class="small text-muted mb-2">{{ $patient->identifiant_national_sante }}</p>
                    <p class="mb-1">{{ $patient->gender }} · {{ \App\Support\Labo\ContexteLabo::ageTexte(\App\Support\Labo\ContexteLabo::ageEnJours($patient, $demande->created_at)) }}
                        @if($demande->grossesse) · <strong>Enceinte</strong>@if($demande->semaines_amenorrhee) ({{ $demande->semaines_amenorrhee }} SA)@endif @endif</p>
                    <p class="mb-0"><i class="fa fa-phone"></i> {{ $patient->telephone ?? '—' }}</p>
                    @if(\App\Support\Labo\ContexteLabo::ageEnJours($patient) === null)
                        <div class="alert alert-warning py-1 px-2 small mt-2 mb-0">Âge inconnu : seules les normes sans critère d'âge s'appliqueront.</div>
                    @endif
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h4 class="card-title">Prescription</h4></div>
                <div class="card-body small">
                    <p class="mb-1"><strong>{{ $demande->origine->libelle() }}</strong></p>
                    <p class="mb-1">{{ $demande->nomPrescripteur() }} {{ $demande->prescripteur_telephone }}</p>
                    @if($demande->renseignements_cliniques)<p class="mb-1">{{ $demande->renseignements_cliniques }}</p>@endif
                    <p class="mb-0 text-muted">Enregistrée le {{ $demande->created_at->format('d/m/Y H:i') }} par {{ $demande->enregistrePar?->name }}</p>
                    @if($demande->estAnnulee())<div class="alert alert-dark mt-2 mb-0">Annulée : {{ $demande->motif_annulation }}</div>@endif
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h4 class="card-title">Facturation</h4></div>
                <div class="card-body">
                    @if($demande->mode_facturation !== ModeFacturation::LABO)
                        <p class="mb-0">{{ $demande->mode_facturation->libelle() }}</p>
                    @elseif(! $demande->transaction)
                        <p>Non facturée — {{ number_format($demande->examens->where('statut', '!=', StatutExamen::ANNULE)->sum('prix_applique'), 0, ',', ' ') }} GNF</p>
                        @can('labo.facturation')
                            <form method="POST" action="{{ route('labo.demandes.facturer', $demande) }}">@csrf<button class="btn btn-primary btn-sm w-100">Créer la facture</button></form>
                        @endcan
                    @else
                        @php($inv = $demande->transaction->invoice)
                        <p class="mb-1">Facture <strong>{{ $demande->transaction->invoice_no }}</strong></p>
                        @if($inv)
                            <p class="mb-1 small">Total {{ number_format($inv->total_amount, 0, ',', ' ') }} GNF · part patient {{ number_format($inv->patient_amount, 0, ',', ' ') }} · assurance {{ number_format($inv->insurance_amount, 0, ',', ' ') }}</p>
                        @endif
                        <span class="badge badge-{{ $demande->partPatientReglee() ? 'success' : 'danger' }}">{{ $demande->partPatientReglee() ? 'Part patient réglée' : 'Part patient non réglée' }}</span>
                        @if(Route::has('invoice.index'))<a href="{{ route('invoice.index') }}" class="small ms-2">Encaisser</a>@endif
                    @endif
                    @if($demande->resultats_retenus_si_impaye && ! $demande->partPatientReglee())
                        <p class="small text-muted mt-2 mb-0">Résultats retenus pour le patient jusqu'au règlement (le prescripteur interne y a accès).</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h4 class="card-title">Examens</h4></div>
                <div class="card-body table-responsive">
                    <table class="table table-sm align-middle">
                        <thead><tr><th>Examen</th><th>Prix</th><th>Statut</th><th>Biologiste</th><th></th></tr></thead>
                        <tbody>
                        @foreach($demande->examens as $l)
                            <tr class="{{ $l->statut === StatutExamen::ANNULE ? 'text-muted text-decoration-line-through' : '' }}">
                                <td>{{ $l->examen_nom }} @if($l->sous_traite)<span class="badge badge-light">Sous-traité · {{ $l->laboratoire_sous_traitant }}</span>@endif
                                    @if($l->nombre_rectifications)<span class="badge badge-warning">Rectifié ×{{ $l->nombre_rectifications }}</span>@endif</td>
                                <td>{{ number_format($l->prix_applique, 0, ',', ' ') }}</td>
                                <td><span class="badge badge-{{ $l->statut->couleur() }}">{{ $l->statut->libelle() }}</span></td>
                                <td class="small">{{ $l->validateurBiologique?->name }}</td>
                                <td class="text-end text-nowrap">
                                    @if($l->statut->permetSaisie())
                                        @can('labo.resultat.saisir')<a href="{{ route('labo.paillasse.saisie', $l) }}" class="btn btn-sm btn-outline-primary">Résultats</a>@endcan
                                    @endif
                                    @if($l->statut === StatutExamen::PUBLIE || $l->statut === StatutExamen::VALIDE_BIOLOGIQUE)
                                        @can('labo.validation.biologique')
                                            <button class="btn btn-sm btn-outline-warning btn-rouvrir" data-action="{{ route('labo.validation.rouvrir', $l) }}" data-nom="{{ $l->examen_nom }}">Rectifier</button>
                                        @endcan
                                    @endif
                                    @if(! $l->estVerrouille() && $l->statut !== StatutExamen::ANNULE)
                                        @can('labo.demande.cancel')
                                            <button class="btn btn-sm btn-link text-danger btn-annuler-examen" data-action="{{ route('labo.demandes.examens.annuler', $l) }}" data-nom="{{ $l->examen_nom }}"><i class="fa fa-times"></i></button>
                                        @endcan
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h4 class="card-title">Échantillons</h4></div>
                <div class="card-body table-responsive">
                    <table class="table table-sm align-middle">
                        <thead><tr><th>Code</th><th>Contenant</th><th>Examens</th><th>Statut</th><th></th></tr></thead>
                        <tbody>
                        @foreach($demande->echantillons as $e)
                            <tr>
                                <td><code>{{ $e->code_barres }}</code></td>
                                <td>@if($e->tube)<span class="labo-tube labo-tube-{{ $e->tube }}"></span>@endif{{ $e->libelleContenant() }}</td>
                                <td class="small">{{ $e->examens->pluck('examen_nom')->implode(', ') }}</td>
                                <td><span class="badge badge-{{ $e->statut->couleur() }}">{{ $e->statut->libelle() }}</span>
                                    @if($e->statut === StatutEchantillon::REJETE)<br><span class="small text-danger">{{ StatutEchantillon::motifsRejet()[$e->motif_rejet] ?? $e->motif_rejet }}</span>@endif
                                    @if($e->preleve_le)<br><span class="small text-muted">Prélevé {{ $e->preleve_le->format('d/m H:i') }} {{ $e->preleveur?->name }}</span>@endif</td>
                                <td class="text-end text-nowrap">
                                    @if($e->statut === StatutEchantillon::ATTENDU && ! $demande->estAnnulee())
                                        @can('labo.prelevement')
                                            <form method="POST" action="{{ route('labo.echantillons.preleve', $e) }}" class="d-inline">@csrf
                                                <button class="btn btn-sm btn-outline-primary">Prélevé</button>
                                                @can('labo.reception')<button name="et_recu" value="1" class="btn btn-sm btn-primary">Prélevé + reçu</button>@endcan
                                            </form>
                                        @endcan
                                    @elseif($e->statut === StatutEchantillon::PRELEVE)
                                        @can('labo.reception')
                                            <form method="POST" action="{{ route('labo.echantillons.recu', $e) }}" class="d-inline">@csrf<button class="btn btn-sm btn-primary">Reçu</button></form>
                                        @endcan
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <h4 class="card-title">Comptes rendus</h4>
                    @if($demande->comptesRendus->isNotEmpty())
                        @can('labo.compte_rendu.publier')
                            <form method="POST" action="{{ route('labo.demandes.sms', $demande) }}" class="ms-auto">@csrf
                                <button class="btn btn-sm btn-outline-secondary"><i class="fa fa-sms"></i> Renvoyer le SMS au patient</button></form>
                        @endcan
                    @endif
                </div>
                <div class="card-body">
                    @forelse($demande->comptesRendus as $cr)
                        <div class="d-flex border-bottom py-2 align-items-center">
                            <div>
                                <strong>Version {{ $cr->version }}</strong>
                                @if($cr->est_rectificatif)<span class="badge badge-warning">Rectificatif</span>@endif
                                @if($cr->est_partiel)<span class="badge badge-info">Partiel</span>@endif
                                @if($loop->first)<span class="badge badge-success">En vigueur</span>@else<span class="badge badge-light">Remplacé</span>@endif
                                <br><span class="small text-muted">{{ $cr->publie_le->format('d/m/Y H:i') }} par {{ $cr->publiePar?->name }}
                                    · SMS : {{ $cr->sms_envoye_le ? $cr->sms_envoye_le->format('d/m H:i') : 'non envoyé' }}</span>
                                @if($cr->motif_rectification)<br><span class="small">{{ $cr->motif_rectification }}</span>@endif
                            </div>
                            @can('labo.compte_rendu.view')
                                <a href="{{ route('labo.comptes-rendus.pdf', $cr) }}" target="_blank" class="btn btn-sm btn-outline-primary ms-auto"><i class="fa fa-file-pdf"></i> PDF</a>
                            @endcan
                        </div>
                    @empty
                        <p class="text-muted mb-0">Aucun compte rendu publié.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div></div>

{{-- Modales --}}
<div class="modal fade" id="modalAnnuler" tabindex="-1"><div class="modal-dialog"><form method="POST" action="{{ route('labo.demandes.annuler', $demande) }}" class="modal-content">@csrf
    <div class="modal-header"><h5 class="modal-title">Annuler la demande {{ $demande->numero }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><label class="form-label">Motif (obligatoire, tracé)</label><input name="motif" required maxlength="255" class="form-control"></div>
    <div class="modal-footer"><button class="btn btn-danger">Confirmer l'annulation</button></div>
</form></div></div>

<div class="modal fade" id="modalMotif" tabindex="-1"><div class="modal-dialog"><form method="POST" id="formMotif" class="modal-content">@csrf
    <div class="modal-header"><h5 class="modal-title" id="modalMotifTitre"></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><label class="form-label">Motif (obligatoire, tracé)</label><input name="motif" required minlength="5" maxlength="255" class="form-control"></div>
    <div class="modal-footer"><button class="btn btn-primary">Confirmer</button></div>
</form></div></div>
@endsection

@section('script')
<script>
document.querySelectorAll('.btn-rouvrir, .btn-annuler-examen').forEach(b => b.addEventListener('click', function () {
    document.getElementById('formMotif').action = this.dataset.action;
    document.getElementById('modalMotifTitre').textContent = (this.classList.contains('btn-rouvrir') ? 'Rectifier « ' : 'Annuler « ') + this.dataset.nom + ' »';
    new bootstrap.Modal(document.getElementById('modalMotif')).show();
}));
</script>
@endsection
