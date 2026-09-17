@extends('layouts.backend')

@php $gnf = fn ($m) => number_format((float) $m, 0, ',', ' '); @endphp

@section('content')
<div class="container"><div class="page-inner">
    @include('assurance.partials.entete', ['titre' => 'Créances — ' . $organisme->name, 'fil' => [route('assurance.creances.index') => 'Créances', 0 => $organisme->name]])

    <div class="row">
        <div class="col-md-4"><div class="card card-stats card-round"><div class="card-body">
            <p class="card-category mb-1">Reste dû</p><h4 class="card-title">{{ $gnf($ouvertes->sum(fn ($c) => $c->resteDu())) }} GNF</h4>
            <p class="small text-muted mb-0">{{ $ouvertes->count() }} réclamation(s) ouverte(s)</p>
        </div></div></div>
        <div class="col-md-4"><div class="card card-stats card-round"><div class="card-body">
            <p class="card-category mb-1">Écarts en attente</p><h4 class="card-title text-danger">{{ $gnf($ouvertes->sum(fn ($c) => $c->ecartEnAttente())) }} GNF</h4>
            <p class="small text-muted mb-0">à transférer au patient ou à passer en perte au règlement</p>
        </div></div></div>
        <div class="col-md-4"><div class="card card-stats card-round"><div class="card-body">
            <p class="card-category mb-1">À envoyer</p><h4 class="card-title">{{ $aEnvoyer }} réclamation(s)</h4>
            @can('assurance.reclamation.gerer')
                @if($aEnvoyer)<button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalBordereau">Préparer un bordereau</button>@endif
            @endcan
        </div></div></div>
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h4 class="card-title">Réclamations ouvertes</h4>
            @can('assurance.reglement.enregistrer')
                @if($ouvertes->isNotEmpty())
                    <button class="btn btn-sm btn-success ms-auto" data-bs-toggle="modal" data-bs-target="#modalReglement"><i class="fa fa-money-bill"></i> Enregistrer un règlement</button>
                @endif
            @endcan
        </div>
        <div class="card-body table-responsive">
            <table class="table table-sm table-hover align-middle">
                <thead><tr><th>Réclamation</th><th>Patient</th><th>Facture</th><th class="text-end">Réclamé</th><th class="text-end">Accepté</th><th class="text-end">Réglé</th><th class="text-end">Reste dû</th><th>Statut</th><th>Bordereau</th></tr></thead>
                <tbody>
                @forelse($ouvertes as $c)
                    <tr>
                        <td><a href="{{ route('assurance.reclamations.show', $c) }}">{{ $c->claim_number }}</a></td>
                        <td>{{ $c->invoice?->transaction?->patient?->full_name }}</td>
                        <td class="small">{{ $c->invoice?->transaction?->invoice_no }} · {{ $c->invoice?->created_at?->format('d/m/Y') }}</td>
                        <td class="text-end">{{ $gnf($c->claimed_amount) }}</td>
                        <td class="text-end">{{ $c->approved_amount !== null ? $gnf($c->approved_amount) : '—' }}</td>
                        <td class="text-end">{{ $gnf($c->montantRegle()) }}</td>
                        <td class="text-end fw-bold">{{ $gnf($c->resteDu()) }}</td>
                        <td>@include('assurance.partials.statut-reclamation', ['statut' => $c->status])</td>
                        <td class="small">@if($c->bordereau)<a href="{{ route('assurance.bordereaux.show', $c->bordereau) }}">{{ $c->bordereau->numero }}</a>@else — @endif</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-muted text-center">Aucune réclamation ouverte.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6"><div class="card">
            <div class="card-header"><h4 class="card-title">Bordereaux</h4></div>
            <ul class="list-group list-group-flush small">
                @forelse($bordereaux as $b)
                    <li class="list-group-item d-flex justify-content-between">
                        <span><a href="{{ route('assurance.bordereaux.show', $b) }}">{{ $b->numero }}</a> — {{ $b->reclamations_count }} réclamation(s)
                            @if($b->periode_debut || $b->periode_fin)<span class="text-muted">· {{ $b->periode_debut?->format('d/m/Y') ?? '…' }} → {{ $b->periode_fin?->format('d/m/Y') ?? '…' }}</span>@endif</span>
                        <span class="badge badge-{{ $b->estBrouillon() ? 'secondary' : 'info' }}">{{ $b->estBrouillon() ? 'Brouillon' : 'Envoyé le ' . $b->date_envoi?->format('d/m/Y') }}</span>
                    </li>
                @empty
                    <li class="list-group-item text-muted">Aucun bordereau.</li>
                @endforelse
            </ul>
        </div></div>
        <div class="col-lg-6"><div class="card">
            <div class="card-header"><h4 class="card-title">Règlements reçus</h4></div>
            <ul class="list-group list-group-flush small">
                @forelse($reglements as $r)
                    <li class="list-group-item d-flex justify-content-between">
                        <span><a href="{{ route('assurance.reglements.show', $r) }}">{{ $r->settlement_no }}</a> — {{ $r->payment_date?->format('d/m/Y') }} {{ $r->payment_reference ? '· réf. ' . $r->payment_reference : '' }}</span>
                        <span>{{ $gnf($r->paid_amount) }} GNF @if((float) $r->discount_amount > 0)<span class="text-danger">(+ {{ $gnf($r->discount_amount) }} écart)</span>@endif</span>
                    </li>
                @empty
                    <li class="list-group-item text-muted">Aucun règlement.</li>
                @endforelse
            </ul>
        </div></div>
    </div>
</div></div>

@can('assurance.reclamation.gerer')
<div class="modal fade" id="modalBordereau" tabindex="-1"><div class="modal-dialog">
    <form method="POST" action="{{ route('assurance.bordereaux.store', $organisme) }}" class="modal-content">@csrf
        <div class="modal-header"><h5 class="modal-title">Préparer un bordereau</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <p class="small text-muted">Regroupe les réclamations non envoyées dont la facture est dans la période (toutes si vide).</p>
            <div class="row g-2">
                <div class="col-6"><label class="form-label">Du</label><input type="date" name="periode_debut" class="form-control" value="{{ today()->subMonthNoOverflow()->startOfMonth()->toDateString() }}"></div>
                <div class="col-6"><label class="form-label">Au</label><input type="date" name="periode_fin" class="form-control" value="{{ today()->subMonthNoOverflow()->endOfMonth()->toDateString() }}"></div>
                <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
            </div>
        </div>
        <div class="modal-footer"><button class="btn btn-primary">Préparer</button></div>
    </form>
</div></div>
@endcan

@can('assurance.reglement.enregistrer')
<div class="modal fade" id="modalReglement" tabindex="-1"><div class="modal-dialog modal-xl">
    <form method="POST" action="{{ route('assurance.reglements.store', $organisme) }}" class="modal-content">@csrf
        <div class="modal-header"><h5 class="modal-title">Règlement reçu de {{ $organisme->name }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="row g-2 mb-3">
                <div class="col-md-3"><label class="form-label">Montant reçu (GNF) *</label><input type="number" min="0" step="1" name="montant_recu" class="form-control" required></div>
                <div class="col-md-2"><label class="form-label">Mode</label>
                    <select name="payment_method" class="form-control"><option value="VIREMENT">Virement</option><option value="CHEQUE">Chèque</option><option value="CASH">Espèces</option><option value="MOBILE">Mobile money</option></select></div>
                <div class="col-md-3"><label class="form-label">Référence</label><input name="payment_reference" class="form-control" maxlength="255" placeholder="N° virement / chèque"></div>
                <div class="col-md-2"><label class="form-label">Date *</label><input type="date" name="payment_date" class="form-control" required value="{{ today()->toDateString() }}"></div>
                <div class="col-md-2"><label class="form-label">Imputation</label>
                    <select name="mode" class="form-control js-mode-reglement">
                        <option value="automatique">Automatique</option>
                        <option value="manuel">Manuelle</option>
                    </select></div>
                <div class="col-12"><input name="notes" class="form-control" placeholder="Notes (relevé de l'assureur, période…)"></div>
            </div>

            <p class="small text-muted js-aide-auto">Automatique : le montant est imputé aux réclamations les plus anciennes (envoyées d'abord), à hauteur de l'accepté quand l'assureur a répondu.</p>

            <div class="table-responsive js-imputations d-none">
                <p class="small text-muted">Manuelle : le total « payé » doit égaler le montant reçu. « Écart » = part refusée ou remise négociée, passée en perte (le patient ne la doit pas).</p>
                <table class="table table-sm align-middle small">
                    <thead><tr><th>Réclamation</th><th>Patient</th><th class="text-end">Accepté</th><th class="text-end">Reste dû</th><th style="width:140px">Payé</th><th style="width:140px">Écart</th></tr></thead>
                    <tbody>
                    @foreach($ouvertes as $c)
                        <tr>
                            <td>{{ $c->claim_number }}</td>
                            <td>{{ $c->invoice?->transaction?->patient?->full_name }}</td>
                            <td class="text-end">{{ $c->approved_amount !== null ? $gnf($c->approved_amount) : '—' }}</td>
                            <td class="text-end">{{ $gnf($c->resteDu()) }}</td>
                            <td><input type="number" min="0" step="1" name="imputations[{{ $c->id }}][paye]" class="form-control form-control-sm"></td>
                            <td><input type="number" min="0" step="1" name="imputations[{{ $c->id }}][ecart]" class="form-control form-control-sm"></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="modal-footer"><button class="btn btn-success">Enregistrer le règlement</button></div>
    </form>
</div></div>
@endcan
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var mode = document.querySelector('.js-mode-reglement');
    if (!mode) return;
    mode.addEventListener('change', function () {
        var manuel = mode.value === 'manuel';
        document.querySelector('.js-imputations').classList.toggle('d-none', !manuel);
        document.querySelector('.js-aide-auto').classList.toggle('d-none', manuel);
    });
});
</script>
@endsection
