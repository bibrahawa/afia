@extends('layouts.backend')

@section('title', 'Détails - ' . $insurance->name)

@section('content')
<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <ul class="breadcrumbs">
                <li class="nav-home">
                    <a href="{{ url('/') }}">
                        <i class="icon-home"></i>
                    </a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="{{ url('/') }}">Admin</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="{{ route('insurance.balances.index') }}">Soldes des assurances</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">{{ $insurance->name }}</a>
                </li>
            </ul>
        </div>

        {{-- En-tête --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <h3 class="card-title mb-0">
                                    <i class="fas fa-building mr-2"></i>
                                    {{ $insurance->name }} ({{ $insurance->code }})
                                </h3>
                                <small class="text-muted">Suivi des factures et règlements assurance</small>
                            </div>
                            <div class="d-flex gap-2">
                                @can('insurance_balance.view')
                                    <a href="{{ route('insurance.balances.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left mr-1"></i>
                                        Retour
                                    </a>
                                @endcan

                                @can('insurance_balance.payment')
                                    @if(($stats['montant_du'] ?? 0) > 0)
                                        <button type="button" class="btn btn-success"
                                                data-bs-toggle="modal"
                                                data-bs-target="#settlementModal">
                                            <i class="fas fa-money-bill-wave mr-1"></i>
                                            Nouveau règlement
                                        </button>
                                    @endif
                                @endcan
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-3 col-md-6 mb-3">
                                <div class="card bg-danger text-white mb-0">
                                    <div class="card-body text-center">
                                        <h4>{{ number_format($stats['montant_du'] ?? 0, 0, ',', ' ') }} GNF</h4>
                                        <p class="mb-0">Reste à payer</p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6 mb-3">
                                <div class="card bg-success text-white mb-0">
                                    <div class="card-body text-center">
                                        <h4>{{ number_format($stats['montant_paye'] ?? 0, 0, ',', ' ') }} GNF</h4>
                                        <p class="mb-0">Montant payé</p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6 mb-3">
                                <div class="card bg-info text-white mb-0">
                                    <div class="card-body text-center">
                                        <h4>{{ number_format($stats['montant_remise'] ?? 0, 0, ',', ' ') }} GNF</h4>
                                        <p class="mb-0">Remises accordées</p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6 mb-3">
                                <div class="card bg-warning text-white mb-0">
                                    <div class="card-body text-center">
                                        <h4>{{ $stats['factures_impayees'] ?? 0 }}</h4>
                                        <p class="mb-0">Factures non soldées</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-light border mt-3 mb-0">
                            <div class="row">
                                <div class="col-md-4">
                                    <strong>Montant total assurance :</strong>
                                    {{ number_format($stats['montant_total'] ?? 0, 0, ',', ' ') }} GNF
                                </div>
                                <div class="col-md-4">
                                    <strong>Total soldé :</strong>
                                    {{ number_format(($stats['montant_paye'] ?? 0) + ($stats['montant_remise'] ?? 0), 0, ',', ' ') }} GNF
                                </div>
                                <div class="col-md-4">
                                    <strong>Total factures :</strong>
                                    {{ $stats['total_factures'] ?? 0 }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Factures --}}
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">
                            <i class="fas fa-file-invoice mr-2"></i>
                            Factures assurance
                        </h4>
                    </div>

                    <div class="card-body">
                        @if($invoices->count())
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>N° Tx</th>
                                            <th>Patient</th>
                                            <th>Description</th>
                                            <th>Date</th>
                                            <th class="text-end">Montant assurance</th>
                                            <th class="text-end">Déjà payé</th>
                                            <th class="text-end">Remise</th>
                                            <th class="text-end">Reste</th>
                                            <th class="text-center">Statut patient</th>
                                            <th class="text-center">Statut assurance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($invoices as $invoice)
                                            @php
                                                $paidAmount = (float) $invoice->settlementItems->sum('applied_paid_amount');
                                                $discountAmount = (float) $invoice->settlementItems->sum('applied_discount_amount');
                                                $settledAmount = $paidAmount + $discountAmount;
                                                $remainingAmount = max(0, (float) $invoice->insurance_amount - $settledAmount);
                                            @endphp
                                            <tr>
                                                <td><strong>{{ $invoice->transaction->invoice_no ?? '-' }}</strong></td>
                                                <td>{{ $invoice->transaction->patient?->getFullNameAttribute() ?? 'N/A' }}</td>
                                                <td>{{ \Illuminate\Support\Str::limit($invoice->transaction->description ?? '-', 50) }}</td>
                                                <td>{{ $invoice->created_at?->format('d/m/Y') }}</td>
                                                <td class="text-end">
                                                    {{ number_format((float) $invoice->insurance_amount, 0, ',', ' ') }} GNF
                                                </td>
                                                <td class="text-end text-success">
                                                    {{ number_format($paidAmount, 0, ',', ' ') }} GNF
                                                </td>
                                                <td class="text-end text-info">
                                                    {{ number_format($discountAmount, 0, ',', ' ') }} GNF
                                                </td>
                                                <td class="text-end {{ $remainingAmount > 0 ? 'text-danger' : 'text-success' }}">
                                                    <strong>{{ number_format($remainingAmount, 0, ',', ' ') }} GNF</strong>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge badge-{{ $invoice->patient_amount_status === 'paid' ? 'success' : 'warning' }}">
                                                        {{ $invoice->patient_amount_status === 'paid' ? 'Payé' : 'En attente' }}
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    @php
                                                        $statusClass = match($invoice->insurance_status) {
                                                            'paid' => 'success',
                                                            'approved' => 'info',
                                                            'pending' => 'warning',
                                                            default => 'secondary',
                                                        };
                                                    @endphp
                                                    <span class="badge badge-{{ $statusClass }}">
                                                        {{ $invoice->insurance_status ?? 'N/A' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-3">
                                {{ $invoices->links() }}
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                <h4>Aucune facture trouvée</h4>
                                <p class="text-muted mb-0">Aucune facture liée à cette assurance.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Historique règlements --}}
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">
                            <i class="fas fa-history mr-2"></i>
                            Historique des règlements
                        </h4>
                    </div>

                    <div class="card-body">
                        @if(isset($settlements) && $settlements->count())
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>N° règlement</th>
                                            <th>Date</th>
                                            <th>Mode</th>
                                            <th>Référence</th>
                                            <th class="text-end">Montant payé</th>
                                            <th class="text-end">Remise</th>
                                            <th class="text-end">Nb factures</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($settlements as $settlement)
                                            <tr>
                                                <td><strong>{{ $settlement->settlement_no }}</strong></td>
                                                <td>{{ optional($settlement->payment_date)->format('d/m/Y') ?? optional($settlement->created_at)->format('d/m/Y') }}</td>
                                                <td>{{ $settlement->payment_method ?? '-' }}</td>
                                                <td>{{ $settlement->payment_reference ?? '-' }}</td>
                                                <td class="text-end text-success">
                                                    {{ number_format((float) $settlement->paid_amount, 0, ',', ' ') }} GNF
                                                </td>
                                                <td class="text-end text-info">
                                                    {{ number_format((float) $settlement->discount_amount, 0, ',', ' ') }} GNF
                                                </td>
                                                <td class="text-end">
                                                    {{ $settlement->items->count() }}
                                                </td>
                                                <td>{{ $settlement->notes ?? '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-3">
                                {{ $settlements->links() }}
                            </div>
                        @else
                            <div class="text-center py-4 text-muted">
                                Aucun règlement enregistré pour cette assurance.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal règlement assurance --}}
<div class="modal fade" id="settlementModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('insurance.balances.payment') }}" method="POST">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-money-bill-wave mr-2"></i>
                        Nouveau règlement - {{ $insurance->name }}
                    </h5>
                    <button type="button" class="close" data-bs-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>

                <input type="hidden" name="insurance_companies_id" value="{{ $insurance->id }}">

                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>Montant restant à solder :</strong>
                        {{ number_format($stats['montant_du'] ?? 0, 0, ',', ' ') }} GNF
                    </div>

                    <div class="form-group">
                        <label for="payment_date">Date de règlement</label>
                        <input type="date" name="payment_date" id="payment_date" class="form-control"
                               value="{{ date('Y-m-d') }}">
                    </div>

                    <div class="form-group">
                        <label for="payment_method">Mode de paiement</label>
                        <select name="payment_method" id="payment_method" class="form-control">
                            <option value="">-- Sélectionner --</option>
                            <option value="CASH">Espèces</option>
                            <option value="CARD">Carte</option>
                            <option value="MOBILE">Mobile Money</option>
                            <option value="TRANSFER">Virement</option>
                            <option value="CHEQUE">Chèque</option>
                            <option value="OTHER">Autre</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="payment_reference">Référence paiement</label>
                        <input type="text" name="payment_reference" id="payment_reference" class="form-control"
                               placeholder="N° virement, chèque, référence mobile money...">
                    </div>

                    <div class="form-group">
                        <label for="montant">Montant payé</label>
                        <input type="number" name="montant" id="montant" class="form-control"
                               min="0" step="0.01" value="{{ $stats['montant_du'] ?? 0 }}" required>
                    </div>

                    <div class="form-group">
                        <label for="discount_amount">Remise globale assurance</label>
                        <input type="number" name="discount_amount" id="discount_amount" class="form-control"
                               min="0" step="0.01" value="0">
                        <small class="text-muted">
                            Remise appliquée sur le total assurance à solder, pas sur chaque item.
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="period_start">Période de début</label>
                        <input type="date" name="period_start" id="period_start" class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="period_end">Période de fin</label>
                        <input type="date" name="period_end" id="period_end" class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea name="notes" id="notes" rows="3" class="form-control"
                                  placeholder="Commentaire sur ce règlement assurance..."></textarea>
                    </div>

                    <div class="alert alert-light border mb-0">
                        <div class="d-flex justify-content-between">
                            <span>Montant payé :</span>
                            <strong id="previewPaid">0 GNF</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Remise :</span>
                            <strong id="previewDiscount">0 GNF</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Total soldé :</span>
                            <strong id="previewSettled">0 GNF</strong>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check mr-1"></i>
                        Enregistrer le règlement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const montantInput = document.getElementById('montant');
    const discountInput = document.getElementById('discount_amount');

    const previewPaid = document.getElementById('previewPaid');
    const previewDiscount = document.getElementById('previewDiscount');
    const previewSettled = document.getElementById('previewSettled');

    function formatGNF(value) {
        return new Intl.NumberFormat('fr-FR').format(Math.round(value || 0)) + ' GNF';
    }

    function updatePreview() {
        const montant = parseFloat(montantInput?.value || 0);
        const discount = parseFloat(discountInput?.value || 0);
        const settled = montant + discount;

        previewPaid.textContent = formatGNF(montant);
        previewDiscount.textContent = formatGNF(discount);
        previewSettled.textContent = formatGNF(settled);
    }

    if (montantInput) {
        montantInput.addEventListener('input', updatePreview);
    }

    if (discountInput) {
        discountInput.addEventListener('input', updatePreview);
    }

    updatePreview();
});
</script>
@endsection