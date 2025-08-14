@extends('layouts.backend')

@section('title', 'Soldes des Assurances')

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
                    <a href="#">Soldes des assurances</a>
                </li>
            </ul>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-chart-line mr-2"></i>
                            Soldes des Assurances
                        </h3>
                        <div>
                            <a href="{{ route('insurance.balances.export') }}" class="btn btn-success">
                                <i class="fas fa-download mr-1"></i>
                                Exporter CSV
                            </a>
                            <button type="button" class="btn btn-info" onclick="location.reload()">
                                <i class="fas fa-sync mr-1"></i>
                                Actualiser
                            </button>
                        </div>
                    </div>

                    <div class="card-body">
                        <!-- Statistiques générales -->
                        <div class="row mb-4">
                            <div class="col-lg-4 col-md-6">
                                <div class="card bg-danger text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h6 class="card-title">Total Dû</h6>
                                                <h4>{{ number_format($insuranceBalances->sum('montant_du') , 0, ',', ' ') }} GNF</h4>
                                            </div>
                                            <i class="fas fa-exclamation-circle fa-2x opacity-50"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-lg-4 col-md-6">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h6 class="card-title">Total Payé</h6>
                                                <h4>{{ number_format($insuranceBalances->sum('montant_paye') , 0, ',', ' ') }} GNF</h4>
                                            </div>
                                            <i class="fas fa-check-circle fa-2x opacity-50"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- <div class="col-lg-3 col-md-6">
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h6 class="card-title">Assurances Actives</h6>
                                                <h4>{{ $insuranceBalances->count() }}</h4>
                                            </div>
                                            <i class="fas fa-building fa-2x opacity-50"></i>
                                        </div>
                                    </div>
                                </div>
                            </div> --}}

                            <div class="col-lg-4 col-md-6">
                                <div class="card bg-warning text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h6 class="card-title">Factures En Attente</h6>
                                                <h4>{{ $insuranceBalances->sum('factures_impayees') }}</h4>
                                            </div>
                                            <i class="fas fa-clock fa-2x opacity-50"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tableau des soldes -->
                        <div class="table-responsive">
                            <table  id="add-row" class="table table-striped table-bordered">
                                <thead class="bg-primary text-red">
                                    <tr>
                                        <th>Code</th>
                                        <th>Nom</th>
                                        <th class="text-right">Dû</th>
                                        <th class="text-right">Payé</th>
                                        <th class="text-right">Total</th>
                                        <th class="text-center">Fac Impayées</th>
                                        {{-- <th class="text-center">Statut</th> --}}
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($insuranceBalances as $balance)
                                        <tr class="{{ $balance['montant_du'] > 0 ? 'table-warning' : '' }}">
                                            <td>
                                                <strong>{{ $balance['code'] }}</strong>
                                            </td>
                                            <td>
                                                <strong>{{ $balance['name'] }}</strong>
                                            </td>
                                            <td class="text-right">
                                                @if($balance['montant_du'] > 0)
                                                    <span class="text-danger font-weight-bold">
                                                        {{ number_format($balance['montant_du'], 0, ',', ' ') }} GNF
                                                    </span>
                                                @else
                                                    <span class="text-muted">0 GNF</span>
                                                @endif
                                            </td>
                                            <td class="text-right">
                                                <span class="text-success">
                                                    {{ number_format($balance['montant_paye'], 0, ',', ' ') }} GNF
                                                </span>
                                            </td>
                                            <td class="text-right">
                                                <strong>{{ number_format($balance['montant_total'], 0, ',', ' ') }} GNF</strong>
                                            </td>
                                            <td class="text-center">
                                                @if($balance['factures_impayees'] > 0)
                                                    <span class="badge badge-warning">
                                                        {{ $balance['factures_impayees'] }}
                                                    </span>
                                                @else
                                                    <span class="badge badge-success">0</span>
                                                @endif
                                            </td>
                                            {{-- <td class="text-center">
                                                <span class="badge badge-{{ $balance['status'] === 'active' ? 'success' : 'secondary' }}">
                                                    {{ ucfirst($balance['status']) }}
                                                </span>
                                            </td> --}}
                                            
                                            <td class="text-center">
                                                <a href="{{ route('insurance.balances.show', $balance['id']) }}" class="btn btn-sm btn-primary" title="Voir détails">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                {{-- @if($balance['montant_du'] > 0)
                                                    <button
                                                        type="button"
                                                        class="btn btn-success btn-sm payer-button"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#addNewPaiementModal"
                                                        data-insurance-companies="{{ json_encode($balance) }}"
                                                        data-montant_du="{{ json_encode($balance['montant_du']) }}"
                                                        >
                                                        <i class="fas fa-money-bill-wave"></i>
                                                    </button>
                                                @endif --}}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-4">
                                                <i class="fas fa-info-circle text-muted mr-2"></i>
                                                Aucune assurance trouvée.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Paiement avec Assurance -->
        <div class="modal fade" id="addNewPaiementModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <h5 class="modal-title">
                            <i class="fas fa-money-bill-wave mr-2"></i>
                            Paiement Groupé - 
                            {{-- {{ $insurance->name }} --}}
                        </h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id='addNewPaiementForm' action="{{ route("account.payer") }}" method="POST">
                            @csrf
                            <!-- Détail des Actes -->
                            
                            <div class="alert alert-info">
                                <div id="group-summary">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="card bg-danger text-white">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between">
                                                        <div>
                                                            <h6 class="card-title">Total Dû</h6>
                                                            <h4 id="total_du"> 0 GNF</h4>
                                                        </div>
                                                        <i class="fas fa-exclamation-circle fa-2x opacity-50"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    
                                        <div class="col-6">
                                            <div class="card bg-success text-white">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between">
                                                        <div>
                                                            <h6 class="card-title">Total Payé</h6>
                                                            <h4 id="total_paye"> 0 GNF</h4>
                                                        </div>
                                                        <i class="fas fa-check-circle fa-2x opacity-50"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            
                            
                            <div class="form-group">
                                <label for="payment_date_group">Date de paiement *</label>
                                <input type="date" name="payment_date" id="payment_date_group" 
                                    class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="claim_number_group">Numéro de réclamation</label>
                                <input type="text" name="claim_number" id="claim_number_group" 
                                    class="form-control" placeholder="Ex: CLM-2025-001">
                            </div>
                            
                            <div class="form-group">
                                <label for="notes_group">Notes</label>
                                <textarea name="notes" id="notes_group" class="form-control" rows="3" 
                                        placeholder="Notes additionnelles..."></textarea>
                            </div>

                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">
                                <i class="fas fa-times"></i> Fermer
                            </button>
                            <button type="submit" class="btn btn-success pull-right" id="editRowButton" form="addNewPaiementForm">
                                <i class="fas fa-money-bill"></i> Effectuer le paiement
                                <div class="spinner-border spinner-border-sm text-light" role="status" id="editLoader" style="display: none;">
                                    <span class="sr-only">Loading...</span>
                                </div>
                            </button>

                            <br>
                            <br>

                            <div class="row">
                                <div class="col-12">
                                    <div class="card border-0 shadow-sm">
                                        <div class="card-header bg-light border-0 py-3">
                                            <h6 class="text-primary mb-0 fw-bold">
                                                <i class="fas fa-list-alt me-2"></i>Détails des factures impayées
                                            </h6>
                                        </div>
                                        <div class="card-body p-0">
                                            <div class="table-responsive">
                                                <table class="table table-hover mb-0" id="actesTable">
                                                    <thead class="bg-primary bg-opacity-10">
                                                        <tr>
                                                            <th class="border-0 fw-semibold">N° Tx</th>
                                                            <th class="border-0 fw-semibold text-center">Patient</th>
                                                            <th class="border-0 fw-semibold text-center">Description</th>
                                                            <th class="border-0 fw-semibold text-center">Date</th>
                                                            <th class="border-0 fw-semibold text-center">Part Assurance</th>
                                                            <th class="border-0 fw-semibold text-center">Part Patient</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="actesTableBody">
                                                        <!-- Les actes seront chargés dynamiquement -->
                                                    </tbody>
                                                    <tfoot class="bg-light">
                                                        <tr>
                                                            <td colspan="4" class="fw-bold text-end border-0">Total:</td>
                                                            <td class="fw-bold text-end border-0" id="montant_due">0 GNF</td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de paiement groupé -->
        <div class="modal fade" id="groupPaymentModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="#">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-money-bill-wave mr-2"></i>
                                Paiement Groupé
                            </h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Assurance</label>
                                <input type="text" id="insurance-name" class="form-control" readonly>
                            </div>
                            
                            <div class="form-group">
                                <label for="payment_date">Date de paiement *</label>
                                <input type="date" name="payment_date" id="payment_date" 
                                    class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="claim_number">Numéro de réclamation</label>
                                <input type="text" name="claim_number" id="claim_number" 
                                    class="form-control" placeholder="Ex: CLM-2025-001">
                            </div>
                            
                            <div class="form-group">
                                <label for="notes">Notes</label>
                                <textarea name="notes" id="notes" class="form-control" rows="3" 
                                        placeholder="Notes additionnelles..."></textarea>
                            </div>
                        </div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check mr-1"></i>
                                Confirmer le paiement
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
    <script type="text/javascript">
        let currentPatient = null;
        let insurance = null;
        let montant_due = 0;
        let invoices = null;
        let transaction = null;
        let currentCalculation = null;
        let patientActes = [];

        // Événement pour ouvrir le modal de paiement
        $(document).on('click', '.payer-button', function() {
            insurance = $(this).data('insurance-companies');
            montant_due = $(this).data('montant_due');

            $('#insurance_compagnies_id').val(insurance['id']);
            $('#montant_due').val(montant_due);

            // Réinitialiser le formulaire
            resetPaymentForm();
            
            // Charger les assurances du patient
            loadInsuranceInvoices(insurance['id']);
            
            // Afficher le modal
            $('#addNewPaiementModal').modal('show');
        });

        // Charger les assurances du patient
        function loadInsuranceInvoices(insuranceId) {
            // Charger les actes des patients

            $.ajax({
                url: `/api/insurance/pending-invoices/${insuranceId}`,
                method: 'GET',
                success: function(response) {
                    if (response.success && response.invoices.length > 0) {
                        invoices = response;
                        $('#total_du').text(numberFormat(invoices.stats.montant_du) + ' GNF');
                        $('#total_paye').text(numberFormat(invoices.stats.montant_paye) + ' GNF');
                       
                        displayActesTable(response.invoices);
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
        function displayActesTable(insuranceInvoices) {
            let html = '';
            let totalOriginal = 0;
            
            insuranceInvoices.forEach(function(invoice) {
                let sousTotal = invoice.insurance_amount;
                totalOriginal += sousTotal;

                 const date = new Date(invoice.created_at);
                const formattedDate = date.toLocaleString('fr-FR', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                html += `
                    <tr>
                        <td>
                            <strong>${ invoice.transaction.invoice_no }</strong>
                        </td>
                        <td>
                            ${ invoice.transaction.patient.first_name+' '+invoice.transaction.patient.last_name}
                        </td>
                        <td>${ invoice.transaction.description }</td>
                        <td>${ formattedDate }</td>
                        <td class="text-right">
                            <strong class="text-danger">
                                ${ numberFormat(invoice.insurance_amount) } GNF
                            </strong>
                        </td>
                        <td class="text-center">
                            <span class="badge badge-${ invoice.patient_amount_status === 'paid' ? 'success' : 'warning' }">
                                ${ invoice.patient_amount_status === 'paid' ? 'Payé' : 'En attente' }
                            </span>
                        </td>
                    </tr>
                    <hr>
                `;
            });

            $('#actesTableBody').html(html);
            $('#montant_due').text(numberFormat(invoices.stats.montant_du) + ' GNF');
        }

        // Afficher tableau vide
        function displayEmptyActesTable() {
            let html = `
                <tr>
                    <td colspan="6" class="text-center py-4">
                        <div class="text-muted">
                            <i class="fas fa-info-circle fa-2x mb-2"></i>
                            <h4>Aucune facture impayée</h4>
                            <p class="text-muted">Cette assurance n'a pas de factures en attente de paiement.</p>
                        </div>
                    </td>
                </tr>
            `;
            $('#actesTableBody').html(html);
            $('#montant_due').text('0 GNF');
        }

        // Calculer la couverture
        $('#calculateCoverageBtn').click(function() {
            let selectedInsurances = [];
            $('.insurance-checkbox:checked').each(function() {
                selectedInsurances.push({
                    id: $(this).val(),
                    company_name: $(this).data('company-name'),
                    patient_insurance: $(this).data('patient-insurance'),
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
                    transaction_id: invoice.transaction_id,
                    montant_original: invoice.total_amount,
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
            $('#montantAPayer').val(Math.round(calculation.patient_amount - transaction.montant_payer));
            
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
            $('#montant_due').val(montant_due ? montant_due : '');
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

        setInterval(function() {
            location.reload();
        }, 300000);
        
    </script>
@endsection