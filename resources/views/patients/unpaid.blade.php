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
                                <td>{{$patient->getFullNameAttribute()}}</td>
                                <td>{{$patient->phone}}</td>
                                <td>{{$patient->getFullAddressAttribute()}}</td>
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

                <!-- Modal Paiement avec Assurance -->
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

                                    <!-- Assurances existantes du patient -->
                                    <div id="existingInsurancesSection" style="display: none;">
                                        <div class="row mb-3">
                                            <div class="col-12">
                                                <div class="alert alert-info">
                                                    <h6><i class="fas fa-info-circle"></i> Assurances enregistrées pour ce patient:</h6>
                                                    <div id="existingInsurancesList"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

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
                                                                    {{-- <th class="border-0 fw-semibold">Type</th> --}}
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
                                                                    <td colspan="3" class="fw-bold text-end border-0">Total:</td>
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

                                    <!-- Section Assurance -->
                                    <div class="row mb-4">
                                        <div class="col-12">
                                            <h6 class="text-primary mb-3">
                                                <i class="fas fa-shield-alt"></i> Gestion des assurances
                                            </h6>
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="checkbox" id="useInsurance" name="use_insurance">
                                                <label class="form-check-label" for="useInsurance">
                                                    Utiliser les assurances pour cette consultation
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Section de sélection des assurances -->
                                    <div id="insuranceSelectionSection" style="display: none;">
                                        <div class="row">
                                            <div class="col-12 mb-3">
                                                <div class="alert alert-warning">
                                                    <small><i class="fas fa-exclamation-triangle"></i> 
                                                    Sélectionnez les assurances à utiliser dans l'ordre de priorité. 
                                                    La première assurance sera appliquée en premier, puis la seconde sur le reste.</small>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row" id="insuranceCards">
                                            <!-- Les cartes d'assurance seront générées dynamiquement ici -->
                                        </div>

                                        <!-- Bouton pour calculer la couverture -->
                                        <div class="row mb-3">
                                            <div class="col-12 text-center">
                                                <button type="button" class="btn btn-info" id="calculateCoverageBtn">
                                                    <i class="fas fa-calculator"></i> Calculer la couverture
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Résultat du calcul -->
                                        <div id="coverageResult" style="display: none;">
                                            <div class="alert alert-success">
                                                <h6><i class="fas fa-check-circle"></i> Calcul de la couverture:</h6>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <p class="mb-1">Montant original: <span id="montantOriginalDisplay" class="fw-bold">0 GNF</span></p>
                                                        <p class="mb-1">Total couverture assurance: <span id="totalCouvertureDisplay" class="fw-bold text-success">0 GNF</span></p>
                                                        <p class="mb-0">Reste à payer: <span id="resteAPayerDisplay" class="fw-bold text-primary">0 GNF</span></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div id="assurancesUtiliseesDetail"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Section de paiement -->
                                    <div class="row mt-4">
                                        <div class="col-12">
                                            <h6 class="text-primary mb-3">
                                                <i class="fas fa-credit-card"></i> Informations de paiement
                                            </h6>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group form-group-default">
                                                <label>Mode de paiement:</label>
                                                <select name="source" class="form-control" required>
                                                    <option value="CASH">Espèces</option>
                                                    <option value="CARD">Carte bancaire</option>
                                                    <option value="TRANSFER">Virement</option>
                                                    <option value="MOBILE">Mobile Money</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group form-group-default">
                                                <label>Montant à payer par le patient</label>
                                                <div class="input-group">
                                                    <input type="number" name="montant" id="montantAPayer" class="form-control" placeholder="montant" required min="0" step="0.01">
                                                    <span class="input-group-text">GNF</span>
                                                </div>
                                                <small class="text-muted">Ce montant sera calculé automatiquement si des assurances sont utilisées</small>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="form-group form-group-default">
                                                <label class="form-label">Description:</label>
                                                <textarea name="description" class="form-control" placeholder="Ecrivez une description ici" rows="3"></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Champs cachés pour les assurances sélectionnées -->
                                    <div id="selectedInsurancesInputs"></div>
                                </form>
                            </div>
                            <div class="modal-footer border-0">
                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">
                                    <i class="fas fa-times"></i> Fermer
                                </button>
                                <button type="submit" class="btn btn-success" id="editRowButton" form="addNewPaiementForm">
                                    <i class="fas fa-money-bill"></i> Effectuer le paiement
                                    <div class="spinner-border spinner-border-sm text-light" role="status" id="editLoader" style="display: none;">
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
            
            // Charger les assurances du patient
            loadPatientInsurances(currentPatient.id);
            
            // Afficher le modal
            $('#addNewPaiementModal').modal('show');
        });

        // Charger les assurances du patient
        function loadPatientInsurances(patientId) {
            // Charger les actes des patients
            loadPatientActes(patientId);

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
                    <hr>
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



        // Afficher les assurances existantes
        function displayExistingInsurances(insurances) {
            let html = '<div class="row">';
            insurances.forEach(function(insurance, index) {
                let status = insurance.status === 'active' ? 'success' : 'warning';
                let remainingLimit = insurance.remaining_limit ? 
                    `Plafond restant: ${numberFormat(insurance.remaining_limit)} GNF` : 
                    'Plafond illimité';
                
                html += `
                    <div class="col-md-6 mb-2">
                        <div class="d-flex align-items-center">
                            <span class="badge badge-${status} me-2">${insurance.insurance_company.name}</span>
                            <small class="text-muted">
                                Police: ${insurance.policy_number} | ${remainingLimit}
                            </small>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            $('#existingInsurancesList').html(html);
        }

        // Générer les cartes de sélection d'assurance
        function generateInsuranceCards(insurances) {
            let html = '';
            insurances.forEach(function(insurance, index) {
                if (insurance.status === 'active') {
                    html += `
                        <div class="col-md-6 mb-3">
                            <div class="card border-info">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">${insurance.insurance_company.name}</h6>
                                    <div class="form-check">
                                        <input class="form-check-input insurance-checkbox" type="checkbox" 
                                               value="${insurance.id}" id="insurance_${insurance.id}"
                                               data-company-name="${insurance.insurance_company.name}"
                                               data-policy="${insurance.policy_number}"
                                               data-coverage="${insurance.insurance_company.default_coverage_percentage}"
                                               data-remaining-limit="${insurance.remaining_limit || 0}">
                                        <label class="form-check-label" for="insurance_${insurance.id}">
                                            Utiliser
                                        </label>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <p class="mb-1"><strong>Police:</strong> ${insurance.policy_number}</p>
                                    <p class="mb-1"><strong>Couverture par défaut:</strong> ${insurance.insurance_company.default_coverage_percentage}%</p>
                                    <p class="mb-1"><strong>Période:</strong> ${formatDate(insurance.start_date)} - ${insurance.end_date ? formatDate(insurance.end_date) : 'Indéterminée'}</p>
                                    <p class="mb-0">
                                        <strong>Plafond restant:</strong> 
                                        <span class="text-${insurance.remaining_limit > 0 ? 'success' : 'danger'}">
                                            ${insurance.remaining_limit ? numberFormat(insurance.remaining_limit) + ' GNF' : 'Illimité'}
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    `;
                }
            });
            $('#insuranceCards').html(html);
        }

        // Afficher message d'absence d'assurance
        function displayNoInsuranceMessage() {
            $('#insuranceCards').html(`
                <div class="col-12">
                    <div class="alert alert-warning text-center">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                        <h6>Aucune assurance active trouvée</h6>
                        <p class="mb-0">Ce patient n'a pas d'assurance active enregistrée. Le paiement sera intégralement à sa charge.</p>
                    </div>
                </div>
            `);
        }

        // Gestion de la checkbox "Utiliser les assurances"
        $('#useInsurance').change(function() {
            if ($(this).is(':checked')) {
                $('#insuranceSelectionSection').slideDown();
            } else {
                $('#insuranceSelectionSection').slideUp();
                resetInsuranceCalculation();
                // Montant à payer = montant original
                $('#montantAPayer').val(currentPatient.montant_du);
            }
        });

        // Calculer la couverture
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
                alert('Veuillez sélectionner au moins une assurance');
                return;
            }

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
                        updateSelectedInsurancesInputs(selectedInsurances);
                        updateActesTableWithInsurance(response.calculation.details, response.calculation.patient_amount);
                        currentCalculation = response.calculation;
                    }
                },
                error: function(xhr) {
                    alert('Erreur lors du calcul de la couverture');
                    console.error(xhr.responseText);
                }
            });
        });

        // Mettre à jour le tableau des actes avec les montants après assurance
        function updateActesTableWithInsurance(actesDetail, patientTotal) {
            actesDetail.forEach(function(acte) {
                let row = $(`#actesTableBody tr:contains('${acte.item_description}')`);
                let apresAssuranceCell = row.find('td:last');
                apresAssuranceCell.html(`${numberFormat(acte.patient_amount)} GNF`);
                
                // Animation de changement
                apresAssuranceCell.addClass('bg-success bg-opacity-10').delay(2000).queue(function() {
                    $(this).removeClass('bg-success bg-opacity-10');
                    $(this).dequeue();
                });

            });
            
            // Mettre à jour le total après assurance
            // let totalApresAssurance = actesDetail.reduce((sum, acte) => sum + acte.montant_patient, 0);
            $('#totalApresAssurance').text(numberFormat(patientTotal) + ' GNF');
        }

        // Afficher le résultat du calcul
        function displayCoverageResult(calculation) {
            $('#montantOriginalDisplay').text(numberFormat(calculation.total_amount) + ' GNF');
            $('#totalCouvertureDisplay').text(numberFormat(calculation.insurance_coverage) + ' GNF');
            $('#resteAPayerDisplay').text(numberFormat(calculation.patient_amount) + ' GNF');
            
            $('#part_insurance').val(calculation.insurance_coverage);
            $('#part_patient').val(calculation.patient_amount);
            
            // Mettre à jour le montant à payer
            $('#montantAPayer').val(Math.round(calculation.patient_amount));
            
            // Afficher le détail des assurances utilisées
            let detailHtml = '<h6>Détail par assurance:</h6>';
            calculation.insurances_used.forEach(function(insurance) {
                detailHtml += `
                    <p class="mb-1">
                        <strong>${insurance.insurance_company}:</strong> 
                        ${numberFormat(insurance.total_covered)} GNF
                        <small class="text-muted">(Police: ${insurance.policy_number})</small>
                    </p>
                `;
            });
            $('#assurancesUtiliseesDetail').html(detailHtml);
            
            $('#coverageResult').slideDown();
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

        // Réinitialiser le formulaire
        function resetPaymentForm() {
            $('#useInsurance').prop('checked', false);
            $('#insuranceSelectionSection').hide();
            $('#coverageResult').hide();
            $('.insurance-checkbox').prop('checked', false);
            $('#montantAPayer').val(currentPatient ? currentPatient.montant_du : '');
            $('#selectedInsurancesInputs').html('');
            currentCalculation = null;
        }

        // Réinitialiser le calcul d'assurance
        function resetInsuranceCalculation() {
            $('#coverageResult').hide();
            $('.insurance-checkbox').prop('checked', false);
            $('#selectedInsurancesInputs').html('');
            currentCalculation = null;
        }

        // Fonctions utilitaires
        function numberFormat(number) {
            return new Intl.NumberFormat('fr-FR').format(number);
        }

        function formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('fr-FR');
        }

        // Validation du formulaire avant soumission
        $('#addNewPaiementForm').on('submit', function(e) {
            let montantAPayer = parseFloat($('#montantAPayer').val());
            let useInsurance = $('#useInsurance').is(':checked');
            
            if (useInsurance && !currentCalculation) {
                e.preventDefault();
                alert('Veuillez calculer la couverture d\'assurance avant de procéder au paiement');
                return false;
            }
            
            if (montantAPayer < 0) {
                e.preventDefault();
                alert('Le montant à payer ne peut pas être négatif');
                return false;
            }
            
            // Afficher le loader
            $('#editRowButton').prop('disabled', true);
            $('#editLoader').show();
        });

        // Événements existants
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

        $(document).ajaxComplete(function() {
            $('#addRowButton').prop('disabled', false);
            $('#addLoader').hide();
            $('#editRowButton').prop('disabled', false);
            $('#editLoader').hide();
            $('#deleteRowButton').prop('disabled', false);
            $('#deleteLoader').hide();
        });

    </script>
@endsection