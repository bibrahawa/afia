@extends('layouts.backend')

@section('title', 'Détails - ' . $insurance->name)

@section('content')
<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <ul class="breadcrumbs">
                <li class="nav-home">
                    <a href="{{url('/')}}">
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
                    <a href="#">Détails des factures impayées</a>
                </li>
            </ul>
        </div>
        <!-- En-tête avec statistiques -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="card-title mb-0">
                                    <i class="fas fa-building mr-2"></i>
                                    {{ $insurance->name }} ({{ $insurance->code }})
                                </h3>
                                <small class="text-muted">Détails des factures impayées</small>
                            </div>
                            <div>
                                <a href="{{ route('insurance.balances.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left mr-1"></i>
                                    Retour
                                </a>
                                @if($stats['factures_impayees'] > 0)
                                    <button type="button" class="btn btn-success"
                                        data-bs-toggle="modal"
                                        data-bs-target="#addNewPaiementModal">
                                        <i class="fas fa-money-bill-wave mr-1"></i>
                                        Paiement Groupé
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-4">
                                <div class="card bg-danger text-white">
                                    <div class="card-body text-center">
                                        <h4>{{ number_format($stats['montant_du'], 0, ',', ' ') }} GNF</h4>
                                        <p class="mb-0">Montant Dû</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="card bg-success text-white">
                                    <div class="card-body text-center">
                                        <h4>{{ number_format($stats['montant_paye'], 0, ',', ' ') }} GNF</h4>
                                        <p class="mb-0">Montant Payé</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="card bg-warning text-white">
                                    <div class="card-body text-center">
                                        <h4>{{ $stats['factures_impayees'] }}</h4>
                                        <p class="mb-0">Factures Impayées</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liste des factures impayées -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">
                            <i class="fas fa-file-invoice mr-2"></i>
                            Factures Impayées
                        </h4>
                    </div>

                    <div class="card-body">
                        @if($invoices->isNotEmpty())
                            <div class="table-responsive">
                                <table id="add-row" class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>N° Tx</th>
                                            <th>Patient</th>
                                            <th>Description</th>
                                            <th>Date</th>
                                            <th class="text-right">Part Assurance</th>
                                            <th class="text-center">Part Patient</th>
                                            {{-- <th class="text-center">Actions</th> --}}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($invoices as $invoice)
                                            <tr>
                                                <td>
                                                    <strong>{{ $invoice->transaction->invoice_no }}</strong>
                                                </td>
                                                <td>
                                                    @if($invoice->transaction->patient)
                                                        {{ $invoice->transaction->patient->getFullNameAttribute() ?? 'N/A' }}
                                                    @else
                                                        <span class="text-muted">Patient introuvable</span>
                                                    @endif
                                                </td>
                                                <td>{{ Str::limit($invoice->transaction->description, 50) }}</td>
                                                <td>{{ $invoice->created_at->format('d/m/Y') }}</td>
                                                <td class="text-right">
                                                    <strong class="text-danger">
                                                        {{ number_format($invoice->insurance_amount, 0, ',', ' ') }} GNF
                                                    </strong>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge badge-{{ $invoice->patient_amount_status === 'paid' ? 'success' : 'warning' }}">
                                                        {{ $invoice->patient_amount_status === 'paid' ? 'Payé' : 'En attente' }}
                                                    </span>
                                                </td>
                                                {{-- <td class="text-center">
                                                    <button type="button" class="btn btn-sm btn-success" 
                                                            onclick="openPaymentModal({{ $invoice->id }}, {{ $invoice->insurance_amount }})">
                                                        <i class="fas fa-money-bill"></i>
                                                    </button>
                                                </td> --}}
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <!-- Boutons de paiement groupé -->
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="selected-summary d-none">
                                        <div class="alert alert-info">
                                            <strong>
                                                <span id="selected-count">0</span> facture(s) sélectionnée(s)
                                            </strong>
                                            - Montant total: <strong id="selected-total">0 GNF</strong>
                                        </div>
                                        <button type="button" class="btn btn-success" onclick="openGroupPaymentModal()">
                                            <i class="fas fa-money-bill-wave mr-1"></i>
                                            Effectuer le paiement groupé
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                <h4>Aucune facture impayée</h4>
                                <p class="text-muted">Cette assurance n'a pas de factures en attente de paiement.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de paiement groupé -->
<div class="modal fade" id="addNewPaiementModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="#" method="post">
                @csrf
                @method('POST')
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-money-bill-wave mr-2"></i>
                        Paiement Groupé - {{ $insurance->name }}
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                
                <div class="modal-body">
                    <div class="alert alert-info">
                        <div id="group-summary">
                            <strong>{{ $stats['total_factures'] }} facture(s)</strong><br>
                            <strong>Montant total a payer: {{ number_format($stats['montant_du'], 0, ',', ' ') }} GNF</strong>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_date_group">Date de paiement *</label>
                        <input type="date" name="payment_date" id="payment_date_group" 
                            class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    
                    {{-- <div class="form-group">
                        <label for="claim_number_group">Numéro de réclamation</label>
                        <input type="text" name="claim_number" id="claim_number_group" 
                            class="form-control" placeholder="Ex: CLM-2025-001">
                    </div> --}}

                    <div class="form-group form-group-default">
                        <label>Montant à payer par {{ $insurance->name }}</label>
                        <div class="input-group">
                            <input type="number" name="montant" id="montantAPayer" class="form-control" placeholder="montant" required min="0" step="0.01" value="{{$stats['montant_du']}}">
                            <span class="input-group-text">GNF</span>
                        </div>
                        <small class="text-muted">Ce montant est calculé automatiquement</small>
                    </div>

                    <div class="form-group">
                        <label for="notes_group">Notes</label>
                        <textarea name="notes" id="notes_group" class="form-control" rows="3" 
                                placeholder="Notes additionnelles..."></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check mr-1"></i>
                        <div class="spinner-border spinner-border-sm text-light" role="status" id="editLoader" style="display: none;">
                            <span class="sr-only">Loading...</span>
                        </div>
                        Confirmer le paiement groupé
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
let selectedInvoices = [];

function openPaymentModal(invoiceId, amount) {
    document.getElementById('payment_amount').value = amount;
    document.getElementById('paymentForm').action = `/insurance/balances/payment/${invoiceId}`;
    $('#paymentModal').modal('show');
}

function toggleAllCheckboxes() {
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.invoice-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
    
    updateSelectedSummary();
}

function updateSelectedSummary() {
    const checkboxes = document.querySelectorAll('.invoice-checkbox:checked');
    const selectedSummary = document.querySelector('.selected-summary');
    
    if (checkboxes.length > 0) {
        selectedSummary.classList.remove('d-none');
        
        let total = 0;
        const invoiceIds = [];
        
        checkboxes.forEach(checkbox => {
            const row = checkbox.closest('tr');
            const amountText = row.querySelector('td:nth-child(6)').textContent.trim();
            const amount = parseInt(amountText.replace(/[^\d]/g, ''));
            total += amount;
            invoiceIds.push(checkbox.value);
        });
        
        document.getElementById('selected-count').textContent = checkboxes.length;
        document.getElementById('selected-total').textContent = new Intl.NumberFormat('fr-FR').format(total) + ' GNF';
        
        selectedInvoices = invoiceIds;
    } else {
        selectedSummary.classList.add('d-none');
        selectedInvoices = [];
    }
}

function openGroupPaymentModal() {
    
    if (selectedInvoices.length === 0) {
        alert('Veuillez sélectionner au moins une facture.');
        return;
    }
    
    const checkboxes = document.querySelectorAll('.invoice-checkbox:checked');
    let total = 0;
    
    checkboxes.forEach(checkbox => {
        const row = checkbox.closest('tr');
        const amountText = row.querySelector('td:nth-child(6)').textContent.trim();
        const amount = parseInt(amountText.replace(/[^\d]/g, ''));
        total += amount;
    });
    
    document.getElementById('group-summary').innerHTML = `
        <strong>${selectedInvoices.length} facture(s) sélectionnée(s)</strong><br>
        <strong>Montant total: ${new Intl.NumberFormat('fr-FR').format(total)} GNF</strong>
    `;
    
    $('#groupPaymentModal').modal('show');
}

function submitGroupPayment() {
    const form = document.getElementById('groupPaymentForm');
    
    // Ajouter les champs du modal au formulaire
    const paymentDate = document.getElementById('payment_date_group').value;
    const claimNumber = document.getElementById('claim_number_group').value;
    const notes = document.getElementById('notes_group').value;
    
    // Créer les champs cachés
    const paymentDateInput = document.createElement('input');
    paymentDateInput.type = 'hidden';
    paymentDateInput.name = 'payment_date';
    paymentDateInput.value = paymentDate;
    form.appendChild(paymentDateInput);
    
    if (claimNumber) {
        const claimNumberInput = document.createElement('input');
        claimNumberInput.type = 'hidden';
        claimNumberInput.name = 'claim_number';
        claimNumberInput.value = claimNumber;
        form.appendChild(claimNumberInput);
    }
    
    if (notes) {
        const notesInput = document.createElement('input');
        notesInput.type = 'hidden';
        notesInput.name = 'notes';
        notesInput.value = notes;
        form.appendChild(notesInput);
    }
    
    form.submit();
}

function selectAllInvoices() {
    document.getElementById('select-all').checked = true;
    toggleAllCheckboxes();
}

// Écouteur d'événements pour les checkboxes individuelles
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.invoice-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedSummary);
    });
});
</script>
@endsection