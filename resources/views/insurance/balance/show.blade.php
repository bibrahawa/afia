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
                                @if($invoices->isNotEmpty())
                                    <button type="button" class="btn btn-success" onclick="exportToExcel()">
                                        <i class="fas fa-file-excel mr-1"></i>
                                        Exporter Excel
                                    </button>
                                @endif
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
            <form action="{{ route('insurance.balances.payment')}}" method="post">
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

                <input type="hidden" name="insurance_companies_id" value="{{ $insurance->id }}">
                
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
                    
                    <div class="form-group">
                        <label for="globalDiscount">Remise globale (%)</label>
                        <input type="number" name="globalDiscount" id="globalDiscount" class="form-control" 
                            min="0" step="0.01" max="100" placeholder="Ex: 2">
                        <small class="text-muted">Saisissez le pourcentage de remise (0-100%)</small>
                    </div>

                    <input type="hidden" name="totalRemise" id="totalRemise" value="0">

                    <div class="form-group form-group-default">
                        <label>Montant à payer par {{ $insurance->name }}</label>
                        <div class="input-group">
                            <input type="number" name="montant" id="montantAPayer" class="form-control" 
                                placeholder="montant" required min="0" step="0.01" value="{{$stats['montant_du']}}">
                            <span class="input-group-text">GNF</span>
                        </div>
                        <small class="text-muted">Ce montant est calculé automatiquement selon la remise appliquée</small>
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

@section('script')
<!-- Inclure SheetJS pour l'export Excel -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>


// Fonction d'export Excel
function exportToExcel() {
    // Données de base
    const companyName = "{{ $insurance->name }}";
    const today = new Date().toLocaleDateString('fr-FR');
    
    // Créer les données pour l'export
    const exportData = [];
    
    // En-tête principal
    exportData.push(['SOCIETE: ' + companyName.toUpperCase()]);
    exportData.push([]);
    
    // En-têtes du tableau
    exportData.push([
        'DATE',
        'NOMS ASSURES PRINCIPAUX', 
        'BENEFICIAIRE',
        'N° CARTE',
        'NATURE PRESTATION',
        'MONTANT PRESTATION',
        'REPARTITION',
        '',
        ''
    ]);
    
    // Sous-en-têtes pour la répartition
    exportData.push([
        '', '', '', '', '', '', 'Part assures', 'Part assureurs'
    ]);
    
    // Données des factures
    @foreach($invoices as $invoice)
    exportData.push([
        '{{ $invoice->created_at->format("d/m/Y") }}',
        '{{ $invoice->transaction->patient ? $invoice->transaction->patient->getFullNameAttribute() : "N/A" }}',
        '{{ $invoice->transaction->patient ? $invoice->transaction->patient->getFullNameAttribute() : "N/A" }}',
        'N/A', // N° carte - à adapter selon vos données
        '{{ str_replace("'", "\'", $invoice->transaction->description) }}',
        '{{ number_format($invoice->insurance_amount + ($invoice->patient_amount ?? 0), 0, ",", " ") }} GNF',
        '0 GNF', // Part assurés
        '{{ number_format($invoice->insurance_amount, 0, ",", " ") }} GNF'
    ]);
    @endforeach
    
    // Ligne vide
    exportData.push([]);
    
    // Total global
    exportData.push([
        '', '', '', '', 'TOTAL GLOBAL', 
        '{{ number_format($stats["montant_du"], 0, ",", " ") }} GNF',
        '0 GNF',
        '{{ number_format($stats["montant_du"], 0, ",", " ") }} GNF'
    ]);
    
    // Ligne vide
    exportData.push([]);
    
    // Net à payer
    exportData.push([
        '', '', '', '', 'NET A PAYER', '', '', '{{ number_format($stats["montant_du"], 0, ",", " ") }} GNF'
    ]);
    
    // Créer le workbook
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.aoa_to_sheet(exportData);
    
    // Style pour l'en-tête principal
    ws['A1'] = { 
        v: 'SOCIETE: ' + companyName.toUpperCase(), 
        t: 's', 
        s: { 
            font: { bold: true, sz: 14 },
            alignment: { horizontal: 'center' },
            fill: { fgColor: { rgb: 'CCCCCC' } }
        }
    };
    
    // Fusionner les cellules de l'en-tête
    ws['!merges'] = [
        { s: { r: 0, c: 0 }, e: { r: 0, c: 8 } }, // Ligne société
        { s: { r: 2, c: 6 }, e: { r: 2, c: 8 } }   // Répartition
    ];
    
    // Définir la largeur des colonnes
    ws['!cols'] = [
        { wch: 12 }, // DATE
        { wch: 25 }, // NOMS ASSURES
        { wch: 25 }, // BENEFICIAIRE
        { wch: 12 }, // N° CARTE
        { wch: 20 }, // NATURE
        { wch: 15 }, // MONTANT
        { wch: 12 }, // Part assurés
        { wch: 15 }  // Part assureurs
    ];
    
    // Ajouter la feuille au workbook
    XLSX.utils.book_append_sheet(wb, ws, 'Factures Impayées');
    
    // Générer le nom du fichier
    const filename = `Factures_Impayees_${companyName.replace(/[^a-zA-Z0-9]/g, '_')}_${today.replace(/\//g, '-')}.xlsx`;
    
    // Télécharger le fichier
    XLSX.writeFile(wb, filename);
}

// Calcul automatique de la remise - VERSION CORRIGÉE
document.addEventListener("DOMContentLoaded", function() {
    const discountInput = document.getElementById("globalDiscount");
    const totalAfterDiscountInput = document.getElementById("montantAPayer");

    // Montant de base récupéré depuis PHP
    const baseAmount = parseFloat("{{ $stats['montant_du'] }}") || 0;

    function updatePaymentAmount() {
        let discountPercent = parseFloat(discountInput.value) || 0;

        // Validation des limites
        if (discountPercent < 0) {
            discountPercent = 0;
            discountInput.value = 0;
        }
        if (discountPercent > 100) {
            discountPercent = 100;
            discountInput.value = 100;
        }

        // Calcul du montant après remise
        const discountAmount = (baseAmount * discountPercent) / 100;
        const finalAmount = baseAmount - discountAmount;

        // Mise à jour du champ total remise
        $('#totalRemise').val(Math.round(discountAmount));

        // Mise à jour du champ montant à payer
        totalAfterDiscountInput.value = Math.round(finalAmount);
        
        // Mise à jour du résumé en temps réel
        const summaryElement = document.getElementById('group-summary');
        if (summaryElement) {
            const discountText = discountPercent > 0 ? 
                `<br><span class="text-success">Remise appliquée: ${discountPercent}% (-${new Intl.NumberFormat('fr-FR').format(Math.round(discountAmount))} GNF)</span>` : '';
            
            summaryElement.innerHTML = `
                <strong>{{ $stats['total_factures'] ?? 0 }} facture(s)</strong><br>
                <strong>Montant original: ${new Intl.NumberFormat('fr-FR').format(baseAmount)} GNF</strong>
                ${discountText}
                <br><strong class="text-primary">Montant à payer: ${new Intl.NumberFormat('fr-FR').format(Math.round(finalAmount))} GNF</strong>
            `;
        }
    }

    // Écouter les changements sur le champ remise
    discountInput.addEventListener("input", updatePaymentAmount);
    discountInput.addEventListener("change", updatePaymentAmount);

    // Calcul initial au chargement
    updatePaymentAmount();
});
</script>
@endsection