@extends('layouts.backend')

@php $gnf = fn ($m) => number_format((float) $m, 0, ',', ' '); @endphp

@section('content')
<div class="container"><div class="page-inner">
    <div class="page-header d-flex flex-wrap align-items-center gap-2">
        <h3 class="fw-bold mb-0">{{ $partenariat->clinique?->nom }}</h3>
        <span class="text-muted">reste dû : <strong>{{ $gnf($resume['reste_du']) }} GNF</strong></span>
        <a href="{{ route('labo.creances.index') }}" class="btn btn-sm btn-secondary ms-auto">Retour</a>
    </div>

    <div class="row">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <h4 class="card-title">Analyses à facturer ({{ $aFacturer->count() }})</h4>
                    <span class="ms-auto">{{ $gnf($aFacturer->sum('montant')) }} GNF</span>
                </div>
                <div class="card-body table-responsive" style="max-height:320px">
                    <table class="table table-sm align-middle small">
                        <thead><tr><th>Demande</th><th>Patient</th><th>Date</th><th class="text-end">Montant</th></tr></thead>
                        <tbody>
                        @forelse($aFacturer as $creance)
                            <tr>
                                <td>{{ $creance->demande?->numero }}</td>
                                <td>{{ $creance->demande?->patient?->full_name }}</td>
                                <td>{{ $creance->demande?->created_at->format('d/m/Y') }}</td>
                                <td class="text-end">{{ $gnf($creance->montant) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-muted">Rien à facturer pour l'instant.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @if($aFacturer->isNotEmpty())
                    <div class="card-footer">
                        <form method="POST" action="{{ route('labo.creances.releves.store', $partenariat) }}" class="row g-2 align-items-end">@csrf
                            <div class="col-md-4"><label class="form-label small">Du</label>
                                <input type="date" name="periode_debut" class="form-control form-control-sm" value="{{ today()->subMonth()->startOfMonth()->toDateString() }}" required></div>
                            <div class="col-md-4"><label class="form-label small">Au</label>
                                <input type="date" name="periode_fin" class="form-control form-control-sm" value="{{ today()->subMonth()->endOfMonth()->toDateString() }}" required></div>
                            <div class="col-md-4"><button class="btn btn-sm btn-primary w-100">Préparer un relevé</button></div>
                        </form>
                    </div>
                @endif
            </div>

            <div class="card">
                <div class="card-header"><h4 class="card-title">Relevés</h4></div>
                <div class="card-body table-responsive">
                    <table class="table table-sm align-middle small">
                        <thead><tr><th>N°</th><th>Période</th><th class="text-end">Montant</th><th class="text-end">Reste</th><th>Statut</th><th></th></tr></thead>
                        <tbody>
                        @forelse($releves as $releve)
                            <tr class="{{ $releve->enRetard() ? 'table-warning' : '' }}">
                                <td>{{ $releve->numero }}</td>
                                <td>{{ $releve->periode_debut->format('d/m/Y') }} → {{ $releve->periode_fin->format('d/m/Y') }}</td>
                                <td class="text-end">{{ $gnf($releve->montant_total) }}</td>
                                <td class="text-end">{{ $gnf($releve->resteDu()) }}</td>
                                <td>
                                    <span class="badge badge-{{ $releve->statut === 'solde' ? 'success' : ($releve->statut === 'envoye' ? 'info' : 'secondary') }}">{{ ucfirst($releve->statut) }}</span>
                                    @if($releve->enRetard())<div class="small text-danger">échu le {{ $releve->echeance->format('d/m/Y') }}</div>@endif
                                </td>
                                <td class="text-end"><a href="{{ route('labo.releves.show', $releve) }}" class="btn btn-sm btn-outline-primary">Ouvrir</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted">Aucun relevé.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><h4 class="card-title">Enregistrer un règlement</h4></div>
                <form method="POST" action="{{ route('labo.creances.reglements.store', $partenariat) }}">@csrf
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-6"><label class="form-label small">Montant reçu *</label>
                                <input type="number" step="1" min="1" name="montant" class="form-control form-control-sm" required></div>
                            <div class="col-6"><label class="form-label small">Mode</label>
                                <select name="mode" class="form-control form-control-sm">
                                    @foreach($modes as $valeur => $libelle)<option value="{{ $valeur }}">{{ $libelle }}</option>@endforeach
                                </select></div>
                            <div class="col-6"><label class="form-label small">Référence</label>
                                <input name="reference" class="form-control form-control-sm" maxlength="255"></div>
                            <div class="col-6"><label class="form-label small">Reçu le *</label>
                                <input type="date" name="recu_le" class="form-control form-control-sm" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" required></div>
                        </div>

                        <details class="mt-2">
                            <summary class="small text-primary" style="cursor:pointer">Imputer à la main (sinon : des plus anciennes aux plus récentes)</summary>
                            <table class="table table-sm small mt-2">
                                <thead><tr><th>Demande</th><th>Relevé</th><th class="text-end">Reste dû</th><th style="width:110px">Imputé</th></tr></thead>
                                <tbody>
                                @foreach($creancesOuvertes as $creance)
                                    <tr>
                                        <td>{{ $creance->demande?->numero }}</td>
                                        <td>{{ $creance->releve?->numero ?? '—' }}</td>
                                        <td class="text-end">{{ $gnf($creance->resteDu()) }}</td>
                                        <td><input type="number" step="1" min="0" max="{{ $creance->resteDu() }}" name="imputations[{{ $creance->id }}]" class="form-control form-control-sm"></td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </details>

                        <textarea name="notes" class="form-control form-control-sm mt-2" rows="2" placeholder="Note (facultatif)"></textarea>
                    </div>
                    <div class="card-footer"><button class="btn btn-primary w-100">Enregistrer le règlement</button></div>
                </form>
            </div>

            <div class="card">
                <div class="card-header"><h4 class="card-title">Ancienneté du reste dû</h4></div>
                <ul class="list-group list-group-flush small">
                    @foreach($resume['anciennete'] as $tranche => $montant)
                        <li class="list-group-item d-flex justify-content-between {{ $tranche === '90+' && $montant > 0 ? 'text-danger' : '' }}">
                            <span>{{ $tranche === '90+' ? 'Plus de 90 jours' : $tranche . ' jours' }}</span>
                            <strong>{{ $gnf($montant) }} GNF</strong>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="card">
                <div class="card-header"><h4 class="card-title">Derniers règlements</h4></div>
                <ul class="list-group list-group-flush small">
                    @forelse($reglements as $reglement)
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <span class="{{ $reglement->estAnnule() ? 'text-muted text-decoration-line-through' : '' }}">
                                    {{ $reglement->recu_le->format('d/m/Y') }} — {{ $modes[$reglement->mode] ?? $reglement->mode }}
                                    @if($reglement->reference)<span class="text-muted">{{ $reglement->reference }}</span>@endif
                                </span>
                                <strong>{{ $gnf($reglement->montant) }} GNF</strong>
                            </div>
                            @if($reglement->estAnnule())
                                <div class="small text-danger">Annulé — {{ $reglement->motif_annulation }}</div>
                            @else
                                <details>
                                    <summary class="small text-danger" style="cursor:pointer">Annuler ce règlement</summary>
                                    <form method="POST" action="{{ route('labo.creances.reglements.annuler', $reglement) }}" class="d-flex gap-1 mt-1">@csrf
                                        <input name="motif_annulation" class="form-control form-control-sm" maxlength="255" placeholder="Motif (virement rejeté…)" required>
                                        <button class="btn btn-sm btn-outline-danger">Annuler</button>
                                    </form>
                                </details>
                            @endif
                        </li>
                    @empty
                        <li class="list-group-item text-muted">Aucun règlement enregistré.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div></div>
@endsection
