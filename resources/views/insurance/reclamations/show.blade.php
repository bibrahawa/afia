@extends('layouts.backend')

@php
    $gnf = fn ($m) => number_format((float) $m, 0, ',', ' ');
    $c = $reclamation;
    $beneficiaire = $c->patientInsurance?->beneficiaire;
    $modifiable = $c->status !== 'draft' && ! $c->settlementItems->count() && (float) $c->montant_transfere_patient == 0;
@endphp

@section('content')
<div class="container"><div class="page-inner">
    @include('assurance.partials.entete', [
        'titre' => 'Réclamation ' . $c->claim_number,
        'fil' => [route('assurance.creances.index') => 'Créances', route('assurance.creances.show', $c->insuranceCompany) => $c->insuranceCompany->name, 0 => $c->claim_number],
    ])

    <div class="row">
        <div class="col-lg-4">
            <div class="card"><div class="card-body small">
                <p class="mb-1"><strong>Statut :</strong> @include('assurance.partials.statut-reclamation', ['statut' => $c->status])</p>
                <p class="mb-1"><strong>Organisme :</strong> {{ $c->insuranceCompany->name }}</p>
                <p class="mb-1"><strong>Patient :</strong> {{ $c->invoice?->transaction?->patient?->full_name }}
                    @if($beneficiaire && $beneficiaire->lien !== \App\Enums\Assurance\LienBeneficiaire::Adherent)
                        <br><span class="text-muted">{{ $beneficiaire->lien->libelle() }} de {{ $beneficiaire->adhesion->patient->full_name }}</span>
                    @endif</p>
                <p class="mb-1"><strong>Carte :</strong> {{ $c->patientInsurance?->policy_number ?? '—' }}</p>
                <p class="mb-1"><strong>Facture :</strong> {{ $c->invoice?->transaction?->invoice_no }} du {{ $c->invoice?->created_at?->format('d/m/Y') }}</p>
                <p class="mb-1"><strong>Bordereau :</strong> @if($c->bordereau)<a href="{{ route('assurance.bordereaux.show', $c->bordereau) }}">{{ $c->bordereau->numero }}</a>@else — @endif</p>
                <hr>
                <p class="mb-1"><strong>Réclamé :</strong> {{ $gnf($c->claimed_amount) }} GNF</p>
                <p class="mb-1"><strong>Accepté :</strong> {{ $c->approved_amount !== null ? $gnf($c->approved_amount) . ' GNF' : 'en attente de réponse' }}</p>
                @if((float) $c->montant_transfere_patient > 0)<p class="mb-1"><strong>Transféré au patient :</strong> {{ $gnf($c->montant_transfere_patient) }} GNF</p>@endif
                <p class="mb-1"><strong>Réglé :</strong> {{ $gnf($c->montantRegle()) }} GNF</p>
                <p class="mb-0"><strong>Reste dû :</strong> <span class="fw-bold">{{ $gnf($c->resteDu()) }} GNF</span></p>
                @if($c->rejection_reason)<p class="mt-2 text-muted">{{ $c->rejection_reason }}</p>@endif
            </div></div>

            @can('assurance.reclamation.gerer')
                @if($c->ecartEnAttente() > 0 && (float) $c->montant_transfere_patient == 0)
                    <div class="card border-warning"><div class="card-body small">
                        <p><strong>Écart de {{ $gnf($c->ecartEnAttente()) }} GNF</strong> refusé par l'assureur. Deux possibilités :</p>
                        <form method="POST" action="{{ route('assurance.reclamations.transferer', $c) }}" onsubmit="return confirm('Remettre cet écart à la charge du patient ? Sa part sur la facture augmentera d\'autant.');">@csrf
                            <button class="btn btn-sm btn-warning w-100 mb-2">Le mettre à la charge du patient</button>
                        </form>
                        <p class="mb-0 text-muted">ou le passer en perte lors du prochain règlement (imputation manuelle, colonne « Écart »).</p>
                    </div></div>
                @endif
            @endcan
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h4 class="card-title">Détail par acte</h4></div>
                <div class="card-body table-responsive">
                    <form method="POST" action="{{ route('assurance.reclamations.repondre', $c) }}">@csrf
                        <table class="table table-sm align-middle small">
                            <thead><tr><th>Acte</th><th class="text-end">Montant acte</th><th class="text-end">Taux</th><th class="text-end">Réclamé</th><th style="width:140px">Accepté</th><th>Motif du rejet</th></tr></thead>
                            <tbody>
                            @foreach($c->lignes as $l)
                                <tr>
                                    <td>{{ $l->description }}@if($l->quantite > 1) ×{{ $l->quantite }}@endif</td>
                                    <td class="text-end">{{ $gnf($l->montant_acte) }}</td>
                                    <td class="text-end">{{ $l->taux !== null ? rtrim(rtrim(number_format((float) $l->taux, 2, ',', ''), '0'), ',') . ' %' : '—' }}</td>
                                    <td class="text-end">{{ $gnf($l->montant_reclame) }}</td>
                                    @if($modifiable)
                                        @can('assurance.reclamation.gerer')
                                            <td><input type="number" min="0" step="1" max="{{ (float) $l->montant_reclame }}" name="lignes[{{ $l->id }}][montant_accepte]" class="form-control form-control-sm"
                                                       value="{{ $l->montant_accepte !== null ? (float) $l->montant_accepte : (float) $l->montant_reclame }}"></td>
                                            <td><input name="lignes[{{ $l->id }}][motif_rejet]" class="form-control form-control-sm" maxlength="255" value="{{ $l->motif_rejet }}" placeholder="Si montant réduit"></td>
                                        @else
                                            <td class="text-end">{{ $l->montant_accepte !== null ? $gnf($l->montant_accepte) : '—' }}</td><td>{{ $l->motif_rejet }}</td>
                                        @endcan
                                    @else
                                        <td class="text-end">{{ $l->montant_accepte !== null ? $gnf($l->montant_accepte) : '—' }}</td><td>{{ $l->motif_rejet }}</td>
                                    @endif
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        @can('assurance.reclamation.gerer')
                            @if($modifiable)
                                <div class="row g-2">
                                    <div class="col-md-9"><input name="commentaire" class="form-control form-control-sm" placeholder="Commentaire de l'assureur (facultatif)" value="{{ $c->rejection_reason }}"></div>
                                    <div class="col-md-3"><button class="btn btn-sm btn-primary w-100">Enregistrer la réponse</button></div>
                                </div>
                            @elseif($c->status === 'draft')
                                <p class="small text-muted mb-0">La réponse de l'assureur se saisit une fois la réclamation envoyée (bordereau).</p>
                            @endif
                        @endcan
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <h4 class="card-title">Pièces justificatives</h4>
                    @if($c->invoice?->transaction_id)
                        <a href="{{ route('assurance.feuilles-de-soins.show', $c->invoice->transaction_id) }}" target="_blank" class="btn btn-sm btn-outline-primary ms-auto"><i class="fa fa-print"></i> Feuille de soins</a>
                    @endif
                </div>
                <div class="card-body">
                    @if(! $c->pieces->contains('type', 'feuille_soins'))
                        <div class="alert alert-warning small py-2">Feuille de soins signée non jointe : c'est le premier motif de rejet des assureurs.</div>
                    @endif
                    <ul class="list-group list-group-flush small mb-2">
                        @forelse($c->pieces as $piece)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><a href="{{ route('assurance.pieces.telecharger', $piece) }}" target="_blank">{{ $piece->libelleType() }}</a>
                                    <span class="text-muted">— {{ $piece->libelle ?: $piece->nom_original }} · {{ $piece->created_at->format('d/m/Y') }} {{ $piece->auteur ? '· ' . $piece->auteur->name : '' }}</span></span>
                                @can('assurance.reclamation.gerer')
                                    <form method="POST" action="{{ route('assurance.pieces.destroy', $piece) }}" onsubmit="return confirm('Supprimer cette pièce ?');">@csrf @method('DELETE')
                                        <button class="btn btn-sm btn-link text-danger p-0"><i class="fa fa-trash"></i></button></form>
                                @endcan
                            </li>
                        @empty
                            <li class="list-group-item text-muted">Aucune pièce jointe.</li>
                        @endforelse
                    </ul>
                    @can('assurance.reclamation.gerer')
                        <form method="POST" action="{{ route('assurance.pieces.store', $c) }}" enctype="multipart/form-data" class="row g-2">@csrf
                            <div class="col-md-3"><select name="type" class="form-control form-control-sm">
                                @foreach(\App\Models\Assurance\PieceJustificative::TYPES as $valeur => $libelleType)<option value="{{ $valeur }}">{{ $libelleType }}</option>@endforeach
                            </select></div>
                            <div class="col-md-3"><input name="libelle" class="form-control form-control-sm" placeholder="Précision (facultatif)" maxlength="255"></div>
                            <div class="col-md-4"><input type="file" name="fichier" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,.webp" required></div>
                            <div class="col-md-2"><button class="btn btn-sm btn-primary w-100">Joindre</button></div>
                        </form>
                    @endcan
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h4 class="card-title">Règlements</h4></div>
                <ul class="list-group list-group-flush small">
                    @forelse($c->settlementItems as $item)
                        <li class="list-group-item d-flex justify-content-between">
                            <span><a href="{{ route('assurance.reglements.show', $item->settlement) }}">{{ $item->settlement->settlement_no }}</a> du {{ $item->settlement->payment_date?->format('d/m/Y') }}
                                @if($item->paiement?->estAnnule())<span class="badge badge-danger">paiement annulé</span>@endif</span>
                            <span>payé {{ $gnf($item->applied_paid_amount) }} @if((float) $item->applied_discount_amount > 0)· écart {{ $gnf($item->applied_discount_amount) }}@endif</span>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">Aucun règlement.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div></div>
@endsection
