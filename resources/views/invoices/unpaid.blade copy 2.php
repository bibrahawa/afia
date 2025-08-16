@extends('layouts.backend')

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
            <a href="{{ route('patient.index') }}">Facture</a>
          </li>
        </ul>
      </div>

      <div class="row">
        <div class="col-md-12">
          <div class="card">
            <div class="card-header">
              <div class="d-flex align-items-center">
                <h4 class="card-title">Facture en attente de paiement</h4>
              </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table id="add-row" class="display table table-striped table-hover">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th>ID</th>
					        <th>Nom du patient</th>
					        <th>Assurances</th>
					        <th>Total</th>
					        <th>Part Patient</th>
					        <th>Montant Du</th>
					        <th>Action</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th>ID</th>
					        <th>Nom du patient</th>
					        <th>Assurances</th>
					        <th>Total</th>
					        <th>Part Patient</th>
					        <th>Montant Du</th>
					        <th>Action</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        @foreach($transactionsDu as $index => $transaction)
                            
                            <tr>
                                <td>{{++$index}}</td>
                                <td>{{$transaction->patient->getFullNameAttribute()."-".$transaction->patient->phone}}</td>
                                <td>
                                    @if($transaction->patient->patientInsurances && $transaction->patient->patientInsurances->count() > 0)
                                        <span class="badge badge-success">
                                            {{ $transaction->patient->patientInsurances->count() }} Assurance(s)
                                        </span>
                                    @else
                                        <span class="badge badge-secondary">Aucune</span>
                                    @endif
                                </td>
                                <td>{{number_format($transaction->invoice->total_amount)." GNF"}}</td>
                                <td>{{number_format($transaction->invoice->patient_amount)." GNF" ?? 0}}</td>
                                <td>
                                    @if(($transaction->invoice->patient_amount - $transaction->montant_payer) > 0)
                                        <span class="badge badge-danger">
                                            {{number_format($transaction->invoice->patient_amount - $transaction->montant_payer)." GNF" ?? 0}}
                                        </span>
                                    @else
                                        <span class="badge badge-secondary">0</span>
                                    @endif
                                <td>
                                    <a href="{{ route('patient.show', $transaction->patient->id) }}" class="btn btn-sm btn-primary">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                    <button
                                        type="button"
                                        class="btn btn-success btn-sm payer-button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#addNewPaiementModal"
                                        data-patient="{{ json_encode($transaction->patient) }}"
                                        data-invoice="{{ json_encode($transaction->invoice) }}"
                                        data-transaction="{{ json_encode($transaction) }}">
                                        <i class="fas fa-money-bill"></i>
                                    </button>

                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    </table>
                </div>

                <!-- Modal Paiement Optimisé -->
                <div class="modal fade" id="addNewPaiementModal" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-xl" role="document">
                        <div class="modal-content">
                            <div class="modal-header border-0">
                                <h5 class="modal-title">
                                    <span class="fw-mediumbold">Paiement d'une consultation</span>
                                    <small id="patientName" class="text-muted"></small>
                                </h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form id='addNewPaiementForm' action="{{ route("account.payer") }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="patient_id" id="patient_id" value="">
                                    <input type="hidden" name="montant_original" id="montant_original" value="">
                                    <input type="hidden" name="part_insurance" id="part_insurance" value="">
                                    <input type="hidden" name="part_patient" id="part_patient" value="">
                                    <input type="hidden" name="transaction_id" id="transaction_id" value="">

                                    <!-- Section Assurance (Simplifiée) -->
                                    <div class="row mb-3" id="insuranceQuickSection" style="display: none;">
                                        <div class="col-12">
                                            <div class="alert alert-info d-flex align-items-center justify-content-between">
                                                <div>
                                                    <h6 class="mb-1"><i class="fas fa-shield-alt"></i> Assurances disponibles</h6>
                                                    <div id="quickInsurancesList"></div>
                                                </div>
                                                <div>
                                                    <button type="button" class="btn btn-outline-primary btn-sm" id="toggleInsuranceBtn">
                                                        <i class="fas fa-toggle-off"></i> Activer
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Détail des Actes avec Remises -->
                                    <div class="row mb-4">
                                        <div class="col-12">
                                            <div class="card border-0 shadow-sm">
                                                <div class="card-header bg-light border-0 py-3 d-flex justify-content-between align-items-center">
                                                    <h6 class="text-primary mb-0 fw-bold">
                                                        <i class="fas fa-list-alt me-2"></i>Détail des Actes Médicaux
                                                    </h6>
                                                    <div class="d-flex gap-2">
                                                        <button type="button" class="btn btn-outline-success btn-sm" id="applyGlobalDiscountBtn">
                                                            <i class="fas fa-percent"></i> Remise globale
                                                        </button>
                                                        <button type="button" class="btn btn-outline-info btn-sm" id="recalculateBtn">
                                                            <i class="fas fa-sync"></i> Recalculer
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="card-body p-0">
                                                    <!-- Remise globale (masquée par défaut) -->
                                                    <div id="globalDiscountSection" class="p-3 bg-light border-bottom" style="display: none;">
                                                        <div class="row align-items-center">
                                                            <div class="col-md-6">
                                                                <label class="form-label">Type de remise globale:</label>
                                                                <select id="globalDiscountType" class="form-select form-select-sm">
                                                                    <option value="percentage">Pourcentage (%)</option>
                                                                    <option value="fixed">Montant fixe (GNF)</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label class="form-label">Valeur:</label>
                                                                <input type="number" id="globalDiscountValue" class="form-control form-control-sm" min="0" step="0.01">
                                                            </div>
                                                            <div class="col-md-2">
                                                                <button type="button" class="btn btn-success btn-sm w-100 mt-4" id="applyGlobalBtn">
                                                                    Appliquer
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="table-responsive">
                                                        <table class="table table-hover mb-0" id="actesTable">
                                                            <thead class="bg-primary bg-opacity-10">
                                                                <tr>
                                                                    <th class="border-0 fw-semibold">Description</th>
                                                                    <th class="border-0 fw-semibold text-end">Prix Unit.</th>
                                                                    <th class="border-0 fw-semibold text-center">Qté</th>
                                                                    <th class="border-0 fw-semibold text-end">Sous-total</th>
                                                                    <th class="border-0 fw-semibold text-end">Remise</th>
                                                                    <th class="border-0 fw-semibold text-end">Net</th>
                                                                    <th class="border-0 fw-semibold text-end">Après Assurance</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody id="actesTableBody">
                                                                <!-- Les actes seront chargés dynamiquement -->
                                                            </tbody>
                                                            <tfoot class="bg-light">
                                                                <tr>
                                                                    <td colspan="3" class="fw-bold text-end border-0">Total:</td>
                                                                    <td class="fw-bold text-end border-0" id="totalOriginal">0 GNF</td>
                                                                    <td class="fw-bold text-end border-0 text-success" id="totalRemise">0 GNF</td>
                                                                    <td class="fw-bold text-end border-0 text-info" id="totalNet">0 GNF</td>
                                                                    <td class="fw-bold text-end border-0 text-primary" id="totalApresAssurance">0 GNF</td>
                                                                </tr>
                                                            </tfoot>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Section de paiement simplifiée -->
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="card border-primary">
                                                <div class="card-header bg-primary text-white">
                                                    <h6 class="mb-0"><i class="fas fa-credit-card"></i> Mode de paiement</h6>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-12 mb-3">
                                                            <div class="btn-group w-100" role="group">
                                                                <input type="radio" class="btn-check" name="source" value="CASH" id="cash" checked>
                                                                <label class="btn btn-outline-success" for="cash">
                                                                    <i class="fas fa-money-bill-wave"></i> Espèces
                                                                </label>
                                                                
                                                                <input type="radio" class="btn-check" name="source" value="CARD" id="card">
                                                                <label class="btn btn-outline-primary" for="card">
                                                                    <i class="fas fa-credit-card"></i> Carte
                                                                </label>
                                                                
                                                                <input type="radio" class="btn-check" name="source" value="MOBILE" id="mobile">
                                                                <label class="btn btn-outline-warning" for="mobile">
                                                                    <i class="fas fa-mobile-alt"></i> Mobile
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="card border-success">
                                                <div class="card-header bg-success text-white">
                                                    <h6 class="mb-0"><i class="fas fa-calculator"></i> Montant à payer</h6>
                                                </div>
                                                <div class="card-body">
                                                    <div class="text-center mb-3">
                                                        <h3 class="text-success mb-0" id="montantAPayerDisplay">0 GNF</h3>
                                                        <small class="text-muted">Montant final à payer</small>
                                                    </div>
                                                    <input type="hidden" name="montant" id="montantAPayer" value="">
                                                    
                                                    <!-- Bouton de paiement rapide -->
                                                    <div class="d-grid gap-2">
                                                        <button type="button" class="btn btn-outline-info btn-sm" id="customAmountBtn">
                                                            <i class="fas fa-edit"></i> Montant personnalisé
                                                        </button>
                                                        <div id="customAmountInput" style="display: none;">
                                                            <input type="number" class="form-control form-control-sm" id="customAmount" min="0" step="0.01">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12 mt-3">
                                            <div class="form-group">
                                                <label class="form-label">Description (optionnelle):</label>
                                                <textarea name="description" class="form-control" placeholder="Commentaire sur le paiement..." rows="2"></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Résumé du calcul -->
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <div class="alert alert-light">
                                                <div class="row text-center">
                                                    <div class="col-md-3">
                                                        <h6 class="mb-1">Total original</h6>
                                                        <span class="h5 text-muted" id="resumeOriginal">0 GNF</span>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <h6 class="mb-1">Remises</h6>
                                                        <span class="h5 text-success" id="resumeRemises">0 GNF</span>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <h6 class="mb-1">Couverture assurance</h6>
                                                        <span class="h5 text-info" id="resumeAssurance">0 GNF</span>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <h6 class="mb-1">À payer</h6>
                                                        <span class="h5 text-primary fw-bold" id="resumeAPayer">0 GNF</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Champs cachés pour les remises et assurances -->
                                    <div id="actesDiscountsInputs"></div>
                                    <div id="selectedInsurancesInputs"></div>
                                </form>
                            </div>
                            <div class="modal-footer border-0">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="fas fa-times"></i> Annuler
                                </button>
                                <button type="submit" class="btn btn-success btn-lg" id="editRowButton" form="addNewPaiementForm">
                                    <i class="fas fa-money-bill"></i> Effectuer le paiement
                                    <div class="spinner-border spinner-border-sm text-light ms-2" role="status" id="editLoader" style="display: none;">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
      </div>
    </div>
</div>

@endsection

@section('script')
    <script type="text/javascript">
        let currentPatient = null;
        let invoice = null;
        let transaction = null;
        let currentCalculation = null;
        let patientActes = [];
        let patientInsurances = [];

        // Événement pour ouvrir le modal de paiement
        $(document).on('click', '.payer-button', function() {
            currentPatient = $(this).data('patient');
            invoice = $(this).data('invoice');
            transaction = $(this).data('transaction');
            
            $('#patient_id').val(currentPatient.id);
            $('#montant_original').val(invoice.total_amount);
            $('#transaction_id').val(invoice.transaction_id);
            $('#patientName').text(' - ' + currentPatient.first_name + ' ' + currentPatient.last_name);
            
            // Réinitialiser et charger les données
            resetPaymentForm();
            loadAllData();
            
            // Afficher le modal
            $('#addNewPaiementModal').modal('show');
        });

        // Charger toutes les données nécessaires
        function loadAllData() {
            Promise.all([
                loadPatientActes(invoice.transaction_id),
                loadPatientInsurances(currentPatient.id)
            ]).then(() => {
                calculateInitialAmounts();
            });
        }

        // Charger les assurances du patient (retourne une Promise)
        function loadPatientInsurances(patientId) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: `/api/patient/${patientId}/insurances`,
                    method: 'GET',
                    success: function(response) {
                        if (response.success && response.insurances.length > 0) {
                            patientInsurances = response.insurances.filter(ins => ins.status === 'active');
                            displayQuickInsurances(patientInsurances);
                            $('#insuranceQuickSection').show();
                        } else {
                            patientInsurances = [];
                            $('#insuranceQuickSection').hide();
                        }
                        resolve();
                    },
                    error: function() {
                        patientInsurances = [];
                        $('#insuranceQuickSection').hide();
                        resolve();
                    }
                });
            });
        }

        // Charger les actes médicaux du patient (retourne une Promise)
        function loadPatientActes(transactionId) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: `/api/patient/${transactionId}/actes`,
                    method: 'GET',
                    success: function(response) {
                        if (response.success && response.actes.length > 0) {
                            patientActes = response.actes.map(acte => ({
                                ...acte,
                                remise: 0,
                                montant_net: acte.prix_unitaire * acte.quantite,
                                montant_apres_assurance: acte.prix_unitaire * acte.quantite
                            }));
                            displayOptimizedActesTable(patientActes);
                        } else {
                            patientActes = [];
                            displayEmptyActesTable();
                        }
                        resolve();
                    },
                    error: function() {
                        patientActes = [];
                        displayEmptyActesTable();
                        resolve();
                    }
                });
            });
        }

        // Afficher les assurances de manière simplifiée
        function displayQuickInsurances(insurances) {
            let html = '';
            insurances.forEach(function(insurance, index) {
                html += `
                    <span class="badge bg-info me-2 mb-1">
                        ${insurance.insurance_company.name} (${insurance.insurance_company.default_coverage_percentage}%)
                    </span>
                `;
            });
            $('#quickInsurancesList').html(html);
        }

        // Afficher le tableau des actes optimisé
        function displayOptimizedActesTable(actes) {
            let html = '';
            
            actes.forEach(function(acte, index) {
                let sousTotal = acte.prix_unitaire * acte.quantite;
                
                html += `
                    <tr data-acte-index="${index}">
                        <td>
                            <div>
                                <strong>${acte.nom}</strong>
                                ${acte.description ? `<br><small class="text-muted">${acte.description}</small>` : ''}
                            </div>
                        </td>
                        <td class="text-end fw-semibold">${numberFormat(acte.prix_unitaire)} GNF</td>
                        <td class="text-center">
                            <span class="badge bg-secondary">${acte.quantite}</span>
                        </td>
                        <td class="text-end fw-bold sous-total">${numberFormat(sousTotal)} GNF</td>
                        <td class="text-end">
                            <div class="input-group input-group-sm">
                                <input type="number" 
                                       class="form-control form-control-sm text-end remise-input" 
                                       min="0" 
                                       max="${sousTotal}" 
                                       step="0.01"
                                       value="0"
                                       data-acte-index="${index}"
                                       placeholder="0">
                                <span class="input-group-text">GNF</span>
                            </div>
                        </td>
                        <td class="text-end fw-bold text-info montant-net">${numberFormat(sousTotal)} GNF</td>
                        <td class="text-end fw-bold text-primary montant-assurance">${numberFormat(sousTotal)} GNF</td>
                    </tr>
                `;
            });
            
            $('#actesTableBody').html(html);
            updateTotals();
        }

        // Calculer les montants initiaux
        function calculateInitialAmounts() {
            let total = patientActes.reduce((sum, acte) => sum + (acte.prix_unitaire * acte.quantite), 0);
            let montantDu = invoice.patient_amount - transaction.montant_payer;
            
            $('#montantAPayer').val(montantDu);
            $('#montantAPayerDisplay').text(numberFormat(montantDu) + ' GNF');
            
            updateResumeSection();
        }

        // Gestion des remises en temps réel
        $(document).on('input', '.remise-input', function() {
            let index = $(this).data('acte-index');
            let remise = parseFloat($(this).val()) || 0;
            let acte = patientActes[index];
            let sousTotal = acte.prix_unitaire * acte.quantite;
            
            // Limiter la remise au sous-total
            if (remise > sousTotal) {
                remise = sousTotal;
                $(this).val(remise);
            }
            
            // Mettre à jour l'acte
            acte.remise = remise;
            acte.montant_net = sousTotal - remise;
            acte.montant_apres_assurance = acte.montant_net; // Sera recalculé si assurance
            
            // Mettre à jour l'affichage de cette ligne
            let row = $(this).closest('tr');
            row.find('.montant-net').text(numberFormat(acte.montant_net) + ' GNF');
            row.find('.montant-assurance').text(numberFormat(acte.montant_apres_assurance) + ' GNF');
            
            // Recalculer les totaux
            updateTotals();
            
            // Si l'assurance est activée, recalculer
            if ($('#toggleInsuranceBtn').hasClass('btn-success')) {
                applyInsuranceCalculation();
            }
        });

        // Remise globale
        $('#applyGlobalDiscountBtn').click(function() {
            $('#globalDiscountSection').slideToggle();
        });

        $('#applyGlobalBtn').click(function() {
            let type = $('#globalDiscountType').val();
            let value = parseFloat($('#globalDiscountValue').val()) || 0;
            
            if (value <= 0) {
                alert('Veuillez saisir une valeur positive');
                return;
            }
            
            patientActes.forEach(function(acte, index) {
                let sousTotal = acte.prix_unitaire * acte.quantite;
                let remise = 0;
                
                if (type === 'percentage') {
                    remise = (sousTotal * value) / 100;
                } else {
                    // Répartir la remise fixe proportionnellement
                    let totalOriginal = patientActes.reduce((sum, a) => sum + (a.prix_unitaire * a.quantite), 0);
                    let proportion = sousTotal / totalOriginal;
                    remise = value * proportion;
                }
                
                // Limiter la remise au sous-total
                remise = Math.min(remise, sousTotal);
                
                // Mettre à jour l'input et l'acte
                $(`.remise-input[data-acte-index="${index}"]`).val(remise.toFixed(2));
                acte.remise = remise;
                acte.montant_net = sousTotal - remise;
                acte.montant_apres_assurance = acte.montant_net;
                
                // Mettre à jour l'affichage
                let row = $(`tr[data-acte-index="${index}"]`);
                row.find('.montant-net').text(numberFormat(acte.montant_net) + ' GNF');
                row.find('.montant-assurance').text(numberFormat(acte.montant_apres_assurance) + ' GNF');
            });
            
            updateTotals();
            $('#globalDiscountSection').slideUp();
            
            // Recalculer l'assurance si activée
            if ($('#toggleInsuranceBtn').hasClass('btn-success')) {
                applyInsuranceCalculation();
            }
        });

        // Toggle assurance
        $('#toggleInsuranceBtn').click(function() {
            if ($(this).hasClass('btn-outline-primary')) {
                // Activer l'assurance
                $(this).removeClass('btn-outline-primary').addClass('btn-success');
                $(this).html('<i class="fas fa-toggle-on"></i> Activé');
                applyInsuranceCalculation();
            } else {
                // Désactiver l'assurance
                $(this).removeClass('btn-success').addClass('btn-outline-primary');
                $(this).html('<i class="fas fa-toggle-off"></i> Activer');
                resetInsuranceCalculation();
            }
        });

        // Appliquer le calcul d'assurance
        function applyInsuranceCalculation() {
            if (patientInsurances.length === 0) return;
            
            // Calculer avec toutes les assurances actives
            $.ajax({
                url: '{{ route("insurance.calculate") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    patient_id: currentPatient.id,
                    transaction_id: invoice.transaction_id,
                    actes: patientActes,
                    insurance_ids: patientInsurances.map(ins => ins.insurance_company.id)
                },
                success: function(response) {
                    if (response.success) {
                        currentCalculation = response.calculation;
                        
                        // Mettre à jour les montants après assurance
                        response.calculation.details.forEach(function(detail, index) {
                            patientActes[index].montant_apres_assurance = detail.patient_amount;
                            let row = $(`tr[data-acte-index="${index}"]`);
                            row.find('.montant-assurance').text(numberFormat(detail.patient_amount) + ' GNF');
                        });
                        
                        updateTotals();
                        updateSelectedInsurancesInputs();
                    }
                },
                error: function() {
                    alert('Erreur lors du calcul de la couverture');
                }
            });
        }

        // Réinitialiser le calcul d'assurance
        function resetInsuranceCalculation() {
            patientActes.forEach(function(acte, index) {
                acte.montant_apres_assurance = acte.montant_net;
                let row = $(`tr[data-acte-index="${index}"]`);
                row.find('.montant-assurance').text(numberFormat(acte.montant_net) + ' GNF');
            });
            
            currentCalculation = null;
            updateTotals();
            $('#selectedInsurancesInputs').html('');
        }

        // Mettre à jour tous les totaux
        function updateTotals() {
            let totalOriginal = 0;
            let totalRemise = 0;
            let totalNet = 0;
            let totalApresAssurance = 0;
            
            patientActes.forEach(function(acte) {
                let sousTotal = acte.prix_unitaire * acte.quantite;
                totalOriginal += sousTotal;
                totalRemise += acte.remise || 0;
                totalNet += acte.montant_net || sousTotal;
                totalApresAssurance += acte.montant_apres_assurance || acte.montant_net || sousTotal;
            });
            
            // Mettre à jour le tableau
            $('#totalOriginal').text(numberFormat(totalOriginal) + ' GNF');
            $('#totalRemise').text(numberFormat(totalRemise) + ' GNF');
            $('#totalNet').text(numberFormat(totalNet) + ' GNF');
            $('#totalApresAssurance').text(numberFormat(totalApresAssurance) + ' GNF');
            
            // Calculer le montant final à payer
            let montantDeja = transaction.montant_payer || 0;
            let montantAPayer = Math.max(0, totalApresAssurance - montantDeja);
            
            $('#montantAPayer').val(montantAPayer);
            $('#montantAPayerDisplay').text(numberFormat(montantAPayer) + ' GNF');
            
            updateResumeSection();
            updateActesDiscountsInputs();
        }

        // Mettre à jour la section résumé
        function updateResumeSection() {
            let totalOriginal = patientActes.reduce((sum, acte) => sum + (acte.prix_unitaire * acte.quantite), 0);
            let totalRemises = patientActes.reduce((sum, acte) => sum + (acte.remise || 0), 0);
            let totalNet = patientActes.reduce((sum, acte) => sum + (acte.montant_net || (acte.prix_unitaire * acte.quantite)), 0);
            let totalApresAssurance = patientActes.reduce((sum, acte) => sum + (acte.montant_apres_assurance || acte.montant_net || (acte.prix_unitaire * acte.quantite)), 0);
            let couvertureAssurance = totalNet - totalApresAssurance;
            let montantAPayer = Math.max(0, totalApresAssurance - (transaction.montant_payer || 0));
            
            $('#resumeOriginal').text(numberFormat(totalOriginal) + ' GNF');
            $('#resumeRemises').text(numberFormat(totalRemises) + ' GNF');
            $('#resumeAssurance').text(numberFormat(couvertureAssurance) + ' GNF');
            $('#resumeAPayer').text(numberFormat(montantAPayer) + ' GNF');
        }

        // Montant personnalisé
        $('#customAmountBtn').click(function() {
            $('#customAmountInput').slideToggle();
        });

        $(document).on('input', '#customAmount', function() {
            let customAmount = parseFloat($(this).val()) || 0;
            $('#montantAPayer').val(customAmount);
            $('#montantAPayerDisplay').text(numberFormat(customAmount) + ' GNF');
            $('#resumeAPayer').text(numberFormat(customAmount) + ' GNF');
        });

        // Recalcul manuel
        $('#recalculateBtn').click(function() {
            updateTotals();
            if ($('#toggleInsuranceBtn').hasClass('btn-success')) {
                applyInsuranceCalculation();
            }
        });

        // Mettre à jour les inputs cachés pour les remises
        function updateActesDiscountsInputs() {
            let html = '';
            patientActes.forEach(function(acte, index) {
                if (acte.remise && acte.remise > 0) {
                    html += `
                        <input type="hidden" name="actes_remises[${acte.id}][remise]" value="${acte.remise}">
                        <input type="hidden" name="actes_remises[${acte.id}][montant_net]" value="${acte.montant_net}">
                    `;
                }
            });
            $('#actesDiscountsInputs').html(html);
        }

        // Mettre à jour les inputs cachés pour les assurances
        function updateSelectedInsurancesInputs() {
            if (!currentCalculation) {
                $('#selectedInsurancesInputs').html('');
                return;
            }
            
            let html = '<input type="hidden" name="use_insurance" value="1">';
            html += `<input type="hidden" name="part_insurance" value="${currentCalculation.insurance_coverage}">`;
            html += `<input type="hidden" name="part_patient" value="${currentCalculation.patient_amount}">`;
            
            if (currentCalculation.insurances_used) {
                currentCalculation.insurances_used.forEach(function(insurance, index) {
                    html += `
                        <input type="hidden" name="selected_insurances[${index}][id]" value="${insurance.insurance_id}">
                        <input type="hidden" name="selected_insurances[${index}][coverage]" value="${insurance.total_covered}">
                        <input type="hidden" name="selected_insurances[${index}][policy]" value="${insurance.policy_number}">
                    `;
                });
            }
            
            $('#selectedInsurancesInputs').html(html);
        }

        // Afficher tableau vide
        function displayEmptyActesTable() {
            let html = `
                <tr>
                    <td colspan="7" class="text-center py-4">
                        <div class="text-muted">
                            <i class="fas fa-info-circle fa-2x mb-2"></i>
                            <br>Aucun acte médical enregistré pour cette consultation
                        </div>
                    </td>
                </tr>
            `;
            $('#actesTableBody').html(html);
            $('#totalOriginal').text('0 GNF');
            $('#totalRemise').text('0 GNF');
            $('#totalNet').text('0 GNF');
            $('#totalApresAssurance').text('0 GNF');
        }

        // Réinitialiser le formulaire
        function resetPaymentForm() {
            // Réinitialiser l'état de l'assurance
            $('#toggleInsuranceBtn').removeClass('btn-success').addClass('btn-outline-primary');
            $('#toggleInsuranceBtn').html('<i class="fas fa-toggle-off"></i> Activer');
            
            // Cacher les sections
            $('#globalDiscountSection').hide();
            $('#customAmountInput').hide();
            $('#insuranceQuickSection').hide();
            
            // Réinitialiser les valeurs
            $('#globalDiscountValue').val('');
            $('#customAmount').val('');
            
            // Sélectionner le paiement en espèces par défaut
            $('#cash').prop('checked', true);
            
            // Vider les containers
            $('#selectedInsurancesInputs').html('');
            $('#actesDiscountsInputs').html('');
            
            // Réinitialiser les variables
            currentCalculation = null;
            patientActes = [];
            patientInsurances = [];
        }

        // Validation du formulaire avant soumission
        $('#addNewPaiementForm').on('submit', function(e) {
            let montantAPayer = parseFloat($('#montantAPayer').val()) || 0;
            
            if (montantAPayer < 0) {
                e.preventDefault();
                alert('Le montant à payer ne peut pas être négatif');
                return false;
            }
            
            // Vérifier si des remises ont été appliquées
            let totalRemises = patientActes.reduce((sum, acte) => sum + (acte.remise || 0), 0);
            if (totalRemises > 0) {
                updateActesDiscountsInputs();
            }
            
            // Vérifier si l'assurance est utilisée
            if ($('#toggleInsuranceBtn').hasClass('btn-success')) {
                updateSelectedInsurancesInputs();
            }
            
            // Afficher le loader
            $('#editRowButton').prop('disabled', true);
            $('#editLoader').show();
        });

        // Fonctions utilitaires
        function numberFormat(number) {
            return new Intl.NumberFormat('fr-FR').format(Math.round(number));
        }

        function formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('fr-FR');
        }

        // Événements existants (conservés)
        $(document).on('click', '.delete-button', function() {
            var patient = $(this).data('patient');
            $('#department_name_to_delete').text("Voulez-vous vraiment supprimer le patient : " + patient.first_name +' '+ patient.last_name + " ?");
            $('#delete_id').val(patient.id);
            $('#deleteDepartmentForm').attr('action', '/patient/' + patient.id);
            $('#deleteRowModal').modal('show');
        });

        // Gestion des loaders
        $('#addDepartmentForm').on('submit', function() {
            $('#addRowButton').prop('disabled', true);
            $('#addLoader').show();
        });

        $('#deleteDepartmentForm').on('submit', function() {
            $('#deleteRowButton').prop('disabled', true);
            $('#deleteLoader').show();
        });

        // Réactiver les boutons après les requêtes AJAX
        $(document).ajaxComplete(function() {
            $('#addRowButton').prop('disabled', false);
            $('#addLoader').hide();
            $('#editRowButton').prop('disabled', false);
            $('#editLoader').hide();
            $('#deleteRowButton').prop('disabled', false);
            $('#deleteLoader').hide();
        });

        // Animation d'apparition des éléments
        $('.fade-in-up').each(function(index) {
            $(this).delay(100 * index).animate({
                opacity: 1,
                transform: 'translateY(0)'
            }, 300);
        });

    </script>

    <style>
        .fade-in-up {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s ease;
        }
        
        .btn-check:checked + .btn-outline-success {
            background-color: #28a745;
            border-color: #28a745;
            color: white;
        }
        
        .btn-check:checked + .btn-outline-primary {
            background-color: #007bff;
            border-color: #007bff;
            color: white;
        }
        
        .btn-check:checked + .btn-outline-warning {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #212529;
        }
        
        .input-group-sm .form-control {
            font-size: 0.875rem;
        }
        
        .badge {
            font-size: 0.75em;
        }
        
        .table th {
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .table td {
            font-size: 0.875rem;
            vertical-align: middle;
        }
        
        .card-header h6 {
            font-weight: 600;
        }
        
        .alert {
            border: none;
            border-radius: 0.375rem;
        }
        
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }
        
        .modal-xl {
            max-width: 1200px;
        }
        
        @media (max-width: 768px) {
            .modal-xl {
                max-width: 95%;
                margin: 1rem auto;
            }
            
            .table-responsive {
                font-size: 0.8rem;
            }
            
            .btn-group .btn {
                font-size: 0.75rem;
                padding: 0.25rem 0.5rem;
            }
        }
    </style>
@endsection