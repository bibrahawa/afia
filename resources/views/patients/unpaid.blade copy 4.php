@extends('layouts.backend')

@section('content')

<style>
    .bg-gradient-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .bg-gradient-info {
        background: linear-gradient(135deg, #20bdff 0%, #5433ff 100%);
    }

    .bg-gradient-success {
        background: linear-gradient(135deg, #81c784 0%, #4caf50 100%);
    }

    .btn-gradient-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: white;
        transition: all 0.3s ease;
    }

    .btn-gradient-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        color: white;
    }

    .btn-gradient-success {
        background: linear-gradient(135deg, #81c784 0%, #4caf50 100%);
        border: none;
        color: white;
        transition: all 0.3s ease;
    }

    .btn-gradient-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(76, 175, 80, 0.4);
        color: white;
    }

    .card {
        transition: all 0.3s ease;
    }

    /* .card:hover {
        transform: translateY(-2px);
    } */

    .insurance-card {
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .insurance-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }

    .insurance-card.selected {
        border-color: #4caf50 !important;
        background: linear-gradient(135deg, #e8f5e8 0%, #f1f8e9 100%);
    }

    .form-check-input:checked {
        background-color: #4caf50;
        border-color: #4caf50;
    }

    .table-hover tbody tr:hover {
        background-color: rgba(102, 126, 234, 0.05);
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .fade-in-up {
        animation: fadeInUp 0.5s ease-out;
    }

    /* Badge personnalisé pour les types d'actes */
    .badge-service { background: linear-gradient(135deg, #667eea, #764ba2); }
    .badge-medicament { background: linear-gradient(135deg, #f093fb, #f5576c); }
    .badge-test { background: linear-gradient(135deg, #4facfe, #00f2fe); }
    .badge-consultation { background: linear-gradient(135deg, #43e97b, #38f9d7); }
</style>

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
            <a href="{{ route('patient.index') }}">patiente</a>
          </li>
        </ul>
      </div>

      <div class="row">
        <div class="col-md-12">
          <div class="card">
            <div class="card-header">
              <div class="d-flex align-items-center">
                <h4 class="card-title">Patiente en attente de paiement</h4>
              </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table id="add-row" class="display table table-striped table-hover">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th>ID</th>
					        <th>Name</th>
					        <th>Phone</th>
					        <th>Address</th>
					        <th>Montant Du</th>
					        <th>Assurances</th>
					        <th>Action</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th>ID</th>
					        <th>Name</th>
					        <th>Phone</th>
					        <th>Address</th>
					        <th>Montant Du</th>
					        <th>Assurances</th>
					        <th>Action</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        @foreach($patientsDu as $patient)
                            
                            <tr>
                                <td>{{$patient->id}}</td>
                                <td>{{$patient->first_name." ".$patient->middle_name." ".$patient->last_name}}</td>
                                <td>{{$patient->phone}}</td>
                                <td>{{$patient->district."/".$patient->location}}</td>
                                <td>{{number_format($patient->montant_du)." GNF"}}</td>
                                <td>
                                    @if($patient->patientInsurances && $patient->patientInsurances->count() > 0)
                                        <span class="badge badge-success">
                                            {{ $patient->patientInsurances->count() }} Assurance(s)
                                        </span>
                                    @else
                                        <span class="badge badge-secondary">Aucune</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('patient.show', $patient->id) }}" class="btn btn-sm btn-primary">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                    <button
                                        type="button"
                                        class="btn btn-success btn-sm payer-button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#addNewPaiementModal"
                                        data-patient="{{ json_encode($patient) }}">
                                        <i class="fas fa-money-bill"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    </table>
                </div>

                <!-- Modal Paiement avec Design Amélioré -->
                <div class="modal fade" id="addNewPaiementModal" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-xl" role="document">
                        <div class="modal-content shadow-lg border-0">
                            <!-- Header avec gradient -->
                            <div class="modal-header bg-gradient-primary text-white border-0 rounded-top">
                                <div class="d-flex align-items-center">
                                    <div class="bg-white bg-opacity-20 rounded-circle p-2 me-3">
                                        <i class="fas fa-money-bill-wave fa-lg text-white"></i>
                                    </div>
                                    <div>
                                        <h5 class="modal-title mb-0 fw-bold">Paiement de Consultation</h5>
                                        <small id="patientName" class="text-white-50"></small>
                                    </div>
                                </div>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body p-4">
                                <form id='addNewPaiementForm' action="{{ route("account.payer") }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="patient_id" id="patient_id" value="">
                                    <input type="hidden" name="montant_original" id="montant_original" value="">

                                    <!-- Détail des Actes -->
                                    <div class="row mb-4">
                                        <div class="col-12">
                                            <div class="card border-0 shadow-sm">
                                                <div class="card-header bg-light border-0 py-3">
                                                    <h6 class="text-primary mb-0 fw-bold">
                                                        <i class="fas fa-list-alt me-2"></i>Détail des Actes Médicaux
                                                    </h6>
                                                </div>
                                                <div class="card-body p-0">
                                                    <div class="table-responsive">
                                                        <table class="table table-hover mb-0" id="actesTable">
                                                            <thead class="bg-primary bg-opacity-10">
                                                                <tr>
                                                                    <th class="border-0 fw-semibold">Type</th>
                                                                    <th class="border-0 fw-semibold">Description</th>
                                                                    <th class="border-0 fw-semibold text-end">Prix Unitaire</th>
                                                                    <th class="border-0 fw-semibold text-center">Quantité</th>
                                                                    <th class="border-0 fw-semibold text-end">Sous-total</th>
                                                                    <th class="border-0 fw-semibold text-end">Après Assurance</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody id="actesTableBody">
                                                                <!-- Les actes seront chargés dynamiquement -->
                                                            </tbody>
                                                            <tfoot class="bg-light">
                                                                <tr>
                                                                    <td colspan="4" class="fw-bold text-end border-0">Total:</td>
                                                                    <td class="fw-bold text-end border-0" id="totalOriginal">0 GNF</td>
                                                                    <td class="fw-bold text-end border-0 text-primary" id="totalApresAssurance">0 GNF</td>
                                                                </tr>
                                                            </tfoot>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Assurances existantes -->
                                    <div id="existingInsurancesSection" style="display: none;">
                                        <div class="row mb-4">
                                            <div class="col-12">
                                                <div class="card border-0 shadow-sm border-start border-info border-4">
                                                    <div class="card-body">
                                                        <h6 class="text-info mb-3">
                                                            <i class="fas fa-shield-alt me-2"></i>Assurances Enregistrées
                                                        </h6>
                                                        <div id="existingInsurancesList"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Section Gestion des Assurances -->
                                    <div class="row mb-4">
                                        <div class="col-12">
                                            <div class="card border-0 shadow-sm">
                                                <div class="card-header bg-gradient-info text-white border-0">
                                                    <h6 class="mb-0 fw-bold">
                                                        <i class="fas fa-shield-alt me-2"></i>Gestion des Assurances
                                                    </h6>
                                                </div>
                                                <div class="card-body">
                                                    <div class="form-check form-switch mb-3">
                                                        <input class="form-check-input" type="checkbox" id="useInsurance" name="use_insurance">
                                                        <label class="form-check-label fw-semibold" for="useInsurance">
                                                            Appliquer les assurances pour cette consultation
                                                        </label>
                                                    </div>

                                                    <!-- Section de sélection des assurances -->
                                                    <div id="insuranceSelectionSection" style="display: none;">
                                                        <div class="alert alert-warning border-0 bg-warning bg-opacity-10 border-start border-warning border-4 mb-4">
                                                            <small class="text-warning-emphasis">
                                                                <i class="fas fa-info-circle me-1"></i>
                                                                Sélectionnez les assurances par ordre de priorité. La première sera appliquée en premier.
                                                            </small>
                                                        </div>

                                                        <div class="row" id="insuranceCards">
                                                            <!-- Cartes d'assurance générées dynamiquement -->
                                                        </div>

                                                        <!-- Bouton calcul -->
                                                        <div class="text-center mb-4">
                                                            <button type="button" class="btn btn-gradient-primary btn-lg px-4" id="calculateCoverageBtn">
                                                                <i class="fas fa-calculator me-2"></i>Calculer la Couverture
                                                            </button>
                                                        </div>

                                                        <!-- Résultat du calcul -->
                                                        <div id="coverageResult" style="display: none;">
                                                            <div class="card border-0 shadow-sm border-start border-success border-4">
                                                                <div class="card-body">
                                                                    <h6 class="text-success mb-3">
                                                                        <i class="fas fa-check-circle me-2"></i>Résultat du Calcul
                                                                    </h6>
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <div class="mb-3">
                                                                                <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded">
                                                                                    <span class="fw-semibold">Montant Original:</span>
                                                                                    <span id="montantOriginalDisplay" class="fw-bold text-dark">0 GNF</span>
                                                                                </div>
                                                                            </div>
                                                                            <div class="mb-3">
                                                                                <div class="d-flex justify-content-between align-items-center p-3 bg-success bg-opacity-10 rounded">
                                                                                    <span class="fw-semibold text-success">Couverture Assurance:</span>
                                                                                    <span id="totalCouvertureDisplay" class="fw-bold text-success">0 GNF</span>
                                                                                </div>
                                                                            </div>
                                                                            <div class="mb-0">
                                                                                <div class="d-flex justify-content-between align-items-center p-3 bg-primary bg-opacity-10 rounded">
                                                                                    <span class="fw-semibold text-primary">Reste à Payer:</span>
                                                                                    <span id="resteAPayerDisplay" class="fw-bold text-primary fs-5">0 GNF</span>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="bg-light rounded p-3 h-100">
                                                                                <div id="assurancesUtiliseesDetail"></div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Section Paiement -->
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="card border-0 shadow-sm">
                                                <div class="card-header bg-gradient-success text-white border-0">
                                                    <h6 class="mb-0 fw-bold">
                                                        <i class="fas fa-credit-card me-2"></i>Informations de Paiement
                                                    </h6>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label fw-semibold">Mode de Paiement</label>
                                                            <select name="source" class="form-select form-select-lg" required>
                                                                <option value="">Sélectionner...</option>
                                                                <option value="CASH">💵 Espèces</option>
                                                                <option value="CARD">💳 Carte Bancaire</option>
                                                                <option value="TRANSFER">🏦 Virement</option>
                                                                <option value="MOBILE">📱 Mobile Money</option>
                                                            </select>
                                                        </div>

                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label fw-semibold">Montant à Payer</label>
                                                            <div class="input-group input-group-lg">
                                                                <input type="number" name="montant" id="montantAPayer" 
                                                                    class="form-control form-control-lg text-end fw-bold" 
                                                                    placeholder="0" required min="0" step="0.01">
                                                                <span class="input-group-text bg-primary text-white fw-bold">GNF</span>
                                                            </div>
                                                            <small class="text-muted">
                                                                <i class="fas fa-info-circle me-1"></i>
                                                                Calculé automatiquement avec les assurances
                                                            </small>
                                                        </div>

                                                        <div class="col-12">
                                                            <label class="form-label fw-semibold">Description (Optionnel)</label>
                                                            <textarea name="description" class="form-control" 
                                                                    placeholder="Ajouter une note concernant ce paiement..." 
                                                                    rows="3"></textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Champs cachés -->
                                    <div id="selectedInsurancesInputs"></div>
                                </form>
                            </div>

                            <!-- Footer avec boutons stylisés -->
                            <div class="modal-footer bg-light border-0 rounded-bottom p-4">
                                <button type="button" class="btn btn-outline-secondary btn-lg px-4" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-2"></i>Annuler
                                </button>
                                <button type="submit" class="btn btn-gradient-success btn-lg px-4" id="editRowButton" form="addNewPaiementForm">
                                    <i class="fas fa-money-bill me-2"></i>Effectuer le Paiement
                                    <div class="spinner-border spinner-border-sm ms-2" role="status" id="editLoader" style="display: none;">
                                        <span class="visually-hidden">Chargement...</span>
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
        let currentCalculation = null;
        let patientActes = [];

        // Événement pour ouvrir le modal de paiement
        $(document).on('click', '.payer-button', function() {
            currentPatient = $(this).data('patient');
            
            $('#patient_id').val(currentPatient.id);
            $('#montant_original').val(currentPatient.montant_du);
            $('#patientName').text(' - ' + currentPatient.first_name + ' ' + currentPatient.last_name);
            
            // Réinitialiser le formulaire
            resetPaymentForm();
            
            // Charger les données du patient
            loadPatientData(currentPatient.id);
            
            // Afficher le modal avec animation
            $('#addNewPaiementModal').modal('show');
        });

        // Charger toutes les données du patient (actes et assurances)
        function loadPatientData(patientId) {
            // Charger les actes médicaux
            loadPatientActes(patientId);
            
            // Charger les assurances
            loadPatientInsurances(patientId);
        }

        // Charger les actes médicaux du patient
        function loadPatientActes(patientId) {
            $.ajax({
                url: `/api/patient/${patientId}/actes`,
                method: 'GET',
                success: function(response) {
                    if (response.success && response.actes.length > 0) {
                        patientActes = response.actes;
                        displayActesTable(response.actes);
                    } else {
                        displayEmptyActesTable();
                    }
                },
                error: function() {
                    displayEmptyActesTable();
                }
            });
        }

        // Afficher le tableau des actes
        function displayActesTable(actes) {
            let html = '';
            let totalOriginal = 0;
            
            actes.forEach(function(acte) {
                let badgeClass = getBadgeClassForType(acte.type);
                let sousTotal = acte.prix_unitaire * acte.quantite;
                totalOriginal += sousTotal;
                
                html += `
                    <tr class="fade-in-up">
                        <td>
                            <span class="badge ${badgeClass} px-3 py-2">
                                ${getActeIcon(acte.type)} ${acte.type}
                            </span>
                        </td>
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
                        <td class="text-end fw-bold">${numberFormat(sousTotal)} GNF</td>
                        <td class="text-end fw-bold text-primary" data-original="${sousTotal}">${numberFormat(sousTotal)} GNF</td>
                    </tr>
                `;
            });
            
            $('#actesTableBody').html(html);
            $('#totalOriginal').text(numberFormat(totalOriginal) + ' GNF');
            $('#totalApresAssurance').text(numberFormat(totalOriginal) + ' GNF');
        }

        // Afficher tableau vide
        function displayEmptyActesTable() {
            let html = `
                <tr>
                    <td colspan="6" class="text-center py-4">
                        <div class="text-muted">
                            <i class="fas fa-info-circle fa-2x mb-2"></i>
                            <br>Aucun acte médical enregistré pour cette consultation
                        </div>
                    </td>
                </tr>
            `;
            $('#actesTableBody').html(html);
            $('#totalOriginal').text('0 GNF');
            $('#totalApresAssurance').text('0 GNF');
        }

        // Obtenir la classe CSS pour le badge selon le type d'acte
        function getBadgeClassForType(type) {
            const typeMap = {
                'service': 'badge-service',
                'consultation': 'badge-consultation',
                'medicament': 'badge-medicament',
                'médicament': 'badge-medicament',
                'test': 'badge-test',
                'examen': 'badge-test',
                'analyse': 'badge-test'
            };
            return typeMap[type.toLowerCase()] || 'badge-service';
        }

        // Obtenir l'icône pour le type d'acte
        function getActeIcon(type) {
            const iconMap = {
                'service': '🔧',
                'consultation': '👨‍⚕️',
                'medicament': '💊',
                'médicament': '💊',
                'test': '🔬',
                'examen': '🔍',
                'analyse': '📊'
            };
            return iconMap[type.toLowerCase()] || '📋';
        }

        // Charger les assurances du patient
        function loadPatientInsurances(patientId) {
            $.ajax({
                url: `/api/patient/${patientId}/insurances`,
                method: 'GET',
                success: function(response) {
                    if (response.success && response.insurances.length > 0) {
                        displayExistingInsurances(response.insurances);
                        generateInsuranceCards(response.insurances);
                        $('#existingInsurancesSection').show();
                    } else {
                        $('#existingInsurancesSection').hide();
                        displayNoInsuranceMessage();
                    }
                },
                error: function() {
                    displayNoInsuranceMessage();
                }
            });
        }

        // Afficher les assurances existantes
        function displayExistingInsurances(insurances) {
            let html = '<div class="row">';
            insurances.forEach(function(insurance, index) {
                let status = insurance.status === 'active' ? 'success' : 'warning';
                let remainingLimit = insurance.remaining_limit ? 
                    `Plafond restant: ${numberFormat(insurance.remaining_limit)} GNF` : 
                    'Plafond illimité';
                
                html += `
                    <div class="col-md-6 mb-3">
                        <div class="d-flex align-items-center p-3 bg-light rounded">
                            <div class="me-3">
                                <span class="badge bg-${status} px-3 py-2">${insurance.insurance_company.name}</span>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold">Police: ${insurance.policy_number}</div>
                                <small class="text-muted">${remainingLimit}</small>
                            </div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            $('#existingInsurancesList').html(html);
        }

        // Générer les cartes de sélection d'assurance avec le nouveau design
        function generateInsuranceCards(insurances) {
            let html = '';
            insurances.forEach(function(insurance, index) {
                if (insurance.status === 'active') {
                    html += `
                        <div class="col-md-6 mb-4">
                            <div class="card insurance-card border-2 h-100" data-insurance-id="${insurance.id}">
                                <div class="card-header bg-gradient-info text-white border-0 d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0 fw-bold">${insurance.insurance_company.name}</h6>
                                        <small class="text-white-50">Police: ${insurance.policy_number}</small>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input insurance-checkbox" type="checkbox" 
                                            value="${insurance.id}" id="insurance_${insurance.id}"
                                            data-company-name="${insurance.insurance_company.name}"
                                            data-policy="${insurance.policy_number}"
                                            data-coverage="${insurance.insurance_company.default_coverage_percentage}"
                                            data-remaining-limit="${insurance.remaining_limit || 0}">
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="text-center p-2 bg-light rounded">
                                                <div class="fw-bold text-primary fs-4">${insurance.insurance_company.default_coverage_percentage}%</div>
                                                <small class="text-muted">Couverture</small>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-center p-2 bg-light rounded">
                                                <div class="fw-bold ${insurance.remaining_limit > 0 ? 'text-success' : 'text-warning'} fs-6">
                                                    ${insurance.remaining_limit ? numberFormat(insurance.remaining_limit) : '∞'}
                                                </div>
                                                <small class="text-muted">Plafond (GNF)</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar-alt me-1"></i>
                                            ${formatDate(insurance.start_date)} - ${insurance.end_date ? formatDate(insurance.end_date) : 'Indéterminée'}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }
            });
            $('#insuranceCards').html(html);
        }

        // Gestionnaire pour la sélection de carte d'assurance
        $(document).on('change', '.insurance-checkbox', function() {
            let card = $(this).closest('.insurance-card');
            if ($(this).is(':checked')) {
                card.addClass('selected');
            } else {
                card.removeClass('selected');
            }
        });

        // Afficher message d'absence d'assurance
        function displayNoInsuranceMessage() {
            $('#insuranceCards').html(`
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-shield-alt fa-3x text-muted mb-3"></i>
                            <h6 class="text-muted">Aucune Assurance Active</h6>
                            <p class="text-muted mb-0">Ce patient n'a pas d'assurance active. Le paiement sera intégralement à sa charge.</p>
                        </div>
                    </div>
                </div>
            `);
        }

        // Gestion de la checkbox "Utiliser les assurances"
        $('#useInsurance').change(function() {
            if ($(this).is(':checked')) {
                $('#insuranceSelectionSection').slideDown(300);
            } else {
                $('#insuranceSelectionSection').slideUp(300);
                resetInsuranceCalculation();
                updateActesTableWithoutInsurance();
                $('#montantAPayer').val(currentPatient.montant_du);
            }
        });

        // Calculer la couverture avec mise à jour du tableau des actes
        $('#calculateCoverageBtn').click(function() {
            let selectedInsurances = [];
            $('.insurance-checkbox:checked').each(function() {
                selectedInsurances.push({
                    id: $(this).val(),
                    company_name: $(this).data('company-name'),
                    policy: $(this).data('policy'),
                    coverage: $(this).data('coverage'),
                    remaining_limit: $(this).data('remaining-limit')
                });
            });

            if (selectedInsurances.length === 0) {
                showAlert('Veuillez sélectionner au moins une assurance', 'warning');
                return;
            }

            // Ajouter un loader au bouton
            let btn = $(this);
            let originalText = btn.html();
            btn.html('<i class="fas fa-spinner fa-spin me-2"></i>Calcul en cours...');
            btn.prop('disabled', true);

            // Appel AJAX pour calculer la couverture
            $.ajax({
                url: '{{ route("insurance.calculate") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    patient_id: currentPatient.id,
                    montant_original: currentPatient.montant_du,
                    insurance_ids: selectedInsurances.map(ins => ins.id),
                    actes: patientActes
                },
                success: function(response) {
                    if (response.success) {
                        displayCoverageResult(response.calculation);
                        updateActesTableWithInsurance(response.calculation.actes_detail);
                        updateSelectedInsurancesInputs(selectedInsurances);
                        currentCalculation = response.calculation;
                        showAlert('Couverture calculée avec succès!', 'success');
                    }
                },
                error: function(xhr) {
                    showAlert('Erreur lors du calcul de la couverture', 'error');
                    console.error(xhr.responseText);
                },
                complete: function() {
                    btn.html(originalText);
                    btn.prop('disabled', false);
                }
            });
        });

        // Mettre à jour le tableau des actes avec les montants après assurance
        function updateActesTableWithInsurance(actesDetail) {
            actesDetail.forEach(function(acte) {
                let row = $(`#actesTableBody tr:contains('${acte.nom}')`);
                let apresAssuranceCell = row.find('td:last');
                apresAssuranceCell.html(`${numberFormat(acte.montant_patient)} GNF`);
                
                // Animation de changement
                apresAssuranceCell.addClass('bg-success bg-opacity-10').delay(2000).queue(function() {
                    $(this).removeClass('bg-success bg-opacity-10');
                    $(this).dequeue();
                });
            });
            
            // Mettre à jour le total après assurance
            let totalApresAssurance = actesDetail.reduce((sum, acte) => sum + acte.montant_patient, 0);
            $('#totalApresAssurance').text(numberFormat(totalApresAssurance) + ' GNF');
        }

        // Remettre le tableau des actes sans assurance
        function updateActesTableWithoutInsurance() {
            $('#actesTableBody tr').each(function() {
                let originalAmount = $(this).find('td:last').data('original');
                if (originalAmount) {
                    $(this).find('td:last').html(numberFormat(originalAmount) + ' GNF');
                }
            });
            
            // Remettre le total original
            let totalOriginal = patientActes.reduce((sum, acte) => sum + (acte.prix_unitaire * acte.quantite), 0);
            $('#totalApresAssurance').text(numberFormat(totalOriginal) + ' GNF');
        }

        // Afficher le résultat du calcul avec un design amélioré
        function displayCoverageResult(calculation) {
            $('#montantOriginalDisplay').text(numberFormat(calculation.total_amount) + ' GNF');
            $('#totalCouvertureDisplay').text(numberFormat(calculation.insurance_coverage) + ' GNF');
            $('#resteAPayerDisplay').text(numberFormat(calculation.patient_amount) + ' GNF');
            
            // Mettre à jour le montant à payer avec animation
            let montantInput = $('#montantAPayer');
            montantInput.addClass('bg-warning bg-opacity-20');
            montantInput.val(Math.round(calculation.patient_amount));
            setTimeout(() => {
                montantInput.removeClass('bg-warning bg-opacity-20');
            }, 1500);
            
            // Afficher le détail des assurances utilisées avec un design moderne
            let detailHtml = `
                <h6 class="text-success mb-3">
                    <i class="fas fa-chart-pie me-2"></i>Répartition par Assurance
                </h6>
            `;
            
            calculation.insurances_used.forEach(function(insurance, index) {
                let percentage = ((insurance.total_covered / calculation.total_amount) * 100).toFixed(1);
                detailHtml += `
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-semibold text-primary">${insurance.insurance_company}</span>
                            <span class="badge bg-primary">${percentage}%</span>
                        </div>
                        <div class="progress mb-1" style="height: 8px;">
                            <div class="progress-bar bg-gradient-primary" role="progressbar" 
                                style="width: ${percentage}%" aria-valuenow="${percentage}" 
                                aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <small class="text-muted">Police: ${insurance.policy_number}</small>
                            <small class="fw-bold text-success">${numberFormat(insurance.total_covered)} GNF</small>
                        </div>
                    </div>
                `;
            });
            
            $('#assurancesUtiliseesDetail').html(detailHtml);
            
            // Afficher avec animation
            $('#coverageResult').hide().slideDown(400);
        }

        // Mettre à jour les champs cachés pour les assurances sélectionnées
        function updateSelectedInsurancesInputs(selectedInsurances) {
            let html = '';
            selectedInsurances.forEach(function(insurance, index) {
                html += `
                    <input type="hidden" name="selected_insurances[${index}][id]" value="${insurance.id}">
                    <input type="hidden" name="selected_insurances[${index}][policy]" value="${insurance.policy}">
                `;
            });
            html += `<input type="hidden" name="use_insurance" value="1">`;
            $('#selectedInsurancesInputs').html(html);
        }

        // Fonction d'alerte personnalisée
        function showAlert(message, type = 'info') {
            let alertClass = {
                'success': 'alert-success',
                'warning': 'alert-warning',
                'error': 'alert-danger',
                'info': 'alert-info'
            };
            
            let icon = {
                'success': 'fas fa-check-circle',
                'warning': 'fas fa-exclamation-triangle',
                'error': 'fas fa-times-circle',
                'info': 'fas fa-info-circle'
            };
            
            let alertHtml = `
                <div class="alert ${alertClass[type]} alert-dismissible fade show position-fixed" 
                    style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;" role="alert">
                    <i class="${icon[type]} me-2"></i>${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            $('body').append(alertHtml);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                $('.alert').alert('close');
            }, 5000);
        }

        // Réinitialiser le formulaire
        function resetPaymentForm() {
            $('#useInsurance').prop('checked', false);
            $('#insuranceSelectionSection').hide();
            $('#coverageResult').hide();
            $('.insurance-checkbox').prop('checked', false);
            $('.insurance-card').removeClass('selected');
            $('#montantAPayer').val(currentPatient ? currentPatient.montant_du : '');
            $('#selectedInsurancesInputs').html('');
            $('#actesTableBody').html('');
            currentCalculation = null;
            patientActes = [];
        }

        // Réinitialiser le calcul d'assurance
        function resetInsuranceCalculation() {
            $('#coverageResult').hide();
            $('.insurance-checkbox').prop('checked', false);
            $('.insurance-card').removeClass('selected');
            $('#selectedInsurancesInputs').html('');
            currentCalculation = null;
        }

        // Fonctions utilitaires améliorées
        function numberFormat(number) {
            return new Intl.NumberFormat('fr-FR').format(Math.round(number));
        }

        function formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('fr-FR', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }

        // Validation du formulaire avant soumission avec messages personnalisés
        $('#addNewPaiementForm').on('submit', function(e) {
            let montantAPayer = parseFloat($('#montantAPayer').val());
            let useInsurance = $('#useInsurance').is(':checked');
            let modePayment = $('select[name="source"]').val();
            
            // Validations
            if (!modePayment) {
                e.preventDefault();
                showAlert('Veuillez sélectionner un mode de paiement', 'warning');
                $('select[name="source"]').focus();
                return false;
            }
            
            if (useInsurance && !currentCalculation) {
                e.preventDefault();
                showAlert('Veuillez calculer la couverture d\'assurance avant de procéder au paiement', 'warning');
                $('#calculateCoverageBtn').addClass('btn-warning').removeClass('btn-gradient-primary');
                setTimeout(() => {
                    $('#calculateCoverageBtn').removeClass('btn-warning').addClass('btn-gradient-primary');
                }, 2000);
                return false;
            }
            
            if (montantAPayer < 0) {
                e.preventDefault();
                showAlert('Le montant à payer ne peut pas être négatif', 'error');
                $('#montantAPayer').focus();
                return false;
            }
            
            if (isNaN(montantAPayer) || montantAPayer === '') {
                e.preventDefault();
                showAlert('Veuillez saisir un montant valide', 'error');
                $('#montantAPayer').focus();
                return false;
            }
            
            // Confirmation avant paiement
            let confirmMessage = useInsurance ? 
                `Confirmer le paiement de ${numberFormat(montantAPayer)} GNF avec application des assurances ?` :
                `Confirmer le paiement de ${numberFormat(montantAPayer)} GNF sans assurance ?`;
            
            if (!confirm(confirmMessage)) {
                e.preventDefault();
                return false;
            }
            
            // Afficher le loader avec animation
            let btn = $('#editRowButton');
            btn.prop('disabled', true);
            btn.find('i').removeClass('fa-money-bill').addClass('fa-spinner fa-spin');
            $('#editLoader').show();
            
            showAlert('Traitement du paiement en cours...', 'info');
        });

        // Gestion des événements de hover pour les cartes d'assurance
        $(document).on('mouseenter', '.insurance-card', function() {
            if (!$(this).find('.insurance-checkbox').is(':checked')) {
                $(this).addClass('border-primary');
            }
        });

        $(document).on('mouseleave', '.insurance-card', function() {
            if (!$(this).find('.insurance-checkbox').is(':checked')) {
                $(this).removeClass('border-primary');
            }
        });

        // Animation au clic sur les cartes d'assurance
        $(document).on('click', '.insurance-card', function(e) {
            if (!$(e.target).hasClass('insurance-checkbox')) {
                let checkbox = $(this).find('.insurance-checkbox');
                checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
            }
        });

        // Formatage automatique du montant
        $('#montantAPayer').on('input', function() {
            let value = $(this).val();
            if (value && !isNaN(value)) {
                $(this).removeClass('is-invalid').addClass('is-valid');
            } else {
                $(this).removeClass('is-valid').addClass('is-invalid');
            }
        });

        // Réinitialisation des états après soumission
        $(document).ajaxComplete(function() {
            $('#editRowButton').prop('disabled', false);
            $('#editRowButton').find('i').removeClass('fa-spinner fa-spin').addClass('fa-money-bill');
            $('#editLoader').hide();
        });

        // Animation d'entrée du modal
        $('#addNewPaiementModal').on('shown.bs.modal', function() {
            $(this).find('.modal-content').addClass('fade-in-up');
        });

        // Nettoyage à la fermeture du modal
        $('#addNewPaiementModal').on('hidden.bs.modal', function() {
            resetPaymentForm();
            $(this).find('.modal-content').removeClass('fade-in-up');
        });

        // Événements existants (pour compatibilité)
        $(document).on('click', '.delete-button', function() {
            var patient = $(this).data('patient');
            $('#department_name_to_delete').text("Voulez-vous vraiment supprimer le patient : " + patient.first_name +' '+ patient.last_name + " ?");
            $('#delete_id').val(patient.id);
            $('#deleteDepartmentForm').attr('action', '/patient/' + patient.id);
            $('#deleteRowModal').modal('show');
        });

        // Gestion des loaders pour les autres formulaires
        $('#addDepartmentForm').on('submit', function() {
            $('#addRowButton').prop('disabled', true);
            $('#addLoader').show();
        });

        $('#deleteDepartmentForm').on('submit', function() {
            $('#deleteRowButton').prop('disabled', true);
            $('#deleteLoader').show();
        });

        // Réinitialisation globale des loaders
        $(document).ajaxComplete(function() {
            $('#addRowButton').prop('disabled', false);
            $('#addLoader').hide();
            $('#deleteRowButton').prop('disabled', false);
            $('#deleteLoader').hide();
        });
    </script>
@endsection