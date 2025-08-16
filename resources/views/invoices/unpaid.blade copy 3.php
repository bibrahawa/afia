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

                <!-- Modal Paiement Simple -->
                <div class="modal fade" id="addNewPaiementModal" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    Paiement - <span id="patientName"></span>
                                </h5>
                                <button type="button" class="close" data-bs-dismiss="modal">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form id='addNewPaiementForm' action="{{ route("account.payer") }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="patient_id" id="patient_id">
                                    <input type="hidden" name="montant_original" id="montant_original">
                                    <input type="hidden" name="transaction_id" id="transaction_id">

                                    <!-- Liste des actes -->
                                    <div class="mb-4">
                                        <h6>Actes médicaux :</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Acte</th>
                                                        <th>Prix</th>
                                                        <th>Qté</th>
                                                        <th>Total</th>
                                                        <th>Remise</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="actesTableBody">
                                                    <!-- Actes chargés dynamiquement -->
                                                </tbody>
                                                <tfoot>
                                                    <tr class="table-active">
                                                        <th colspan="3">Total</th>
                                                        <th id="totalAmount">0 GNF</th>
                                                        <th id="totalDiscount">0 GNF</th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- Assurance simple -->
                                    <div class="mb-3" id="insuranceSection" style="display:none;">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="useInsurance">
                                            <label class="form-check-label" for="useInsurance">
                                                Utiliser l'assurance (<span id="insuranceInfo"></span>)
                                            </label>
                                        </div>
                                        <small class="text-muted">Économie estimée: <span id="insuranceSavings">0 GNF</span></small>
                                    </div>

                                    <!-- Paiement -->
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label>Mode de paiement</label>
                                            <select name="source" class="form-control" required>
                                                <option value="CASH">Espèces</option>
                                                <option value="CARD">Carte</option>
                                                <option value="MOBILE">Mobile Money</option>
                                                <option value="TRANSFER">Virement</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Montant à payer</label>
                                            <div class="input-group">
                                                <input type="number" name="montant" id="montantAPayer" class="form-control" required>
                                                <span class="input-group-text">GNF</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-3">
                                        <label>Description</label>
                                        <textarea name="description" class="form-control" rows="2" placeholder="Commentaire..."></textarea>
                                    </div>

                                    <!-- Champs cachés -->
                                    <div id="hiddenInputs"></div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                <button type="submit" class="btn btn-success" form="addNewPaiementForm">
                                    Effectuer le paiement
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
    <script>
        let currentData = {};
        let actes = [];
        let insurances = [];

        // Ouvrir modal
        $('.payer-button').click(function() {
            currentData = {
                patient: $(this).data('patient'),
                invoice: $(this).data('invoice'),
                transaction: $(this).data('transaction')
            };
            
            initModal();
            loadData();
        });

        function initModal() {
            let patient = currentData.patient;
            let invoice = currentData.invoice;
            let transaction = currentData.transaction;
            
            $('#patientName').text(patient.first_name + ' ' + patient.last_name);
            $('#patient_id').val(patient.id);
            $('#montant_original').val(invoice.total_amount);
            $('#transaction_id').val(invoice.transaction_id);
            $('#montantAPayer').val(invoice.patient_amount - transaction.montant_payer);
        }

        function loadData() {
            // Charger actes
            $.get(`/api/patient/${currentData.invoice.transaction_id}/actes`)
                .done(function(response) {
                    if (response.success) {
                        actes = response.actes;
                        displayActes();
                    }
                });

            // Charger assurances
            $.get(`/api/patient/${currentData.patient.id}/insurances`)
                .done(function(response) {
                    if (response.success && response.insurances.length > 0) {
                        insurances = response.insurances.filter(i => i.status === 'active');
                        displayInsurances();
                    }
                });
        }

        function displayActes() {
            let html = '';
            let total = 0;
            
            actes.forEach((acte, index) => {
                let sousTotal = acte.prix_unitaire * acte.quantite;
                total += sousTotal;
                
                html += `
                    <tr>
                        <td>${acte.nom}</td>
                        <td>${formatNumber(acte.prix_unitaire)} GNF</td>
                        <td>${acte.quantite}</td>
                        <td>${formatNumber(sousTotal)} GNF</td>
                        <td>
                            <input type="number" class="form-control form-control-sm discount-input" 
                                   data-index="${index}" max="${sousTotal}" min="0" value="0">
                        </td>
                    </tr>
                `;
            });
            
            $('#actesTableBody').html(html);
            $('#totalAmount').text(formatNumber(total) + ' GNF');
            
            // Event pour les remises
            $('.discount-input').on('input', calculateTotals);
        }

        function displayInsurances() {
            if (insurances.length === 0) return;
            
            let info = insurances.map(i => i.insurance_company.name + ' (' + i.insurance_company.default_coverage_percentage + '%)').join(', ');
            $('#insuranceInfo').text(info);
            $('#insuranceSection').show();
            
            $('#useInsurance').change(function() {
                if ($(this).is(':checked')) {
                    calculateInsurance();
                } else {
                    resetInsurance();
                }
            });
        }

        function calculateTotals() {
            let totalOriginal = 0;
            let totalDiscount = 0;
            let totalNet = 0;
            
            actes.forEach((acte, index) => {
                let sousTotal = acte.prix_unitaire * acte.quantite;
                let discount = parseFloat($(`.discount-input[data-index="${index}"]`).val()) || 0;
                
                totalOriginal += sousTotal;
                totalDiscount += discount;
                totalNet += (sousTotal - discount);
            });
            
            $('#totalAmount').text(formatNumber(totalOriginal) + ' GNF');
            $('#totalDiscount').text(formatNumber(totalDiscount) + ' GNF');
            
            // Recalculer le montant à payer
            let montantDeja = currentData.transaction.montant_payer || 0;
            let montantAPayer = Math.max(0, totalNet - montantDeja);
            
            if ($('#useInsurance').is(':checked')) {
                calculateInsurance();
            } else {
                $('#montantAPayer').val(montantAPayer);
            }
            
            updateHiddenInputs();
        }

        function calculateInsurance() {
            let totalNet = getTotalNet();
            
            // Calcul simple avec le premier taux d'assurance
            if (insurances.length > 0) {
                let coverage = insurances[0].insurance_company.default_coverage_percentage / 100;
                let insurancePart = totalNet * coverage;
                let patientPart = totalNet - insurancePart;
                
                $('#insuranceSavings').text(formatNumber(insurancePart) + ' GNF');
                
                let montantDeja = currentData.transaction.montant_payer || 0;
                let montantAPayer = Math.max(0, patientPart - montantDeja);
                $('#montantAPayer').val(montantAPayer);
            }
        }

        function resetInsurance() {
            $('#insuranceSavings').text('0 GNF');
            calculateTotals();
        }

        function getTotalNet() {
            let total = 0;
            actes.forEach((acte, index) => {
                let sousTotal = acte.prix_unitaire * acte.quantite;
                let discount = parseFloat($(`.discount-input[data-index="${index}"]`).val()) || 0;
                total += (sousTotal - discount);
            });
            return total;
        }

        function updateHiddenInputs() {
            let html = '';
            
            // Remises
            actes.forEach((acte, index) => {
                let discount = parseFloat($(`.discount-input[data-index="${index}"]`).val()) || 0;
                if (discount > 0) {
                    html += `<input type="hidden" name="actes_remises[${acte.id}][remise]" value="${discount}">`;
                }
            });
            
            // Assurance
            if ($('#useInsurance').is(':checked') && insurances.length > 0) {
                html += '<input type="hidden" name="use_insurance" value="1">';
                html += `<input type="hidden" name="insurance_id" value="${insurances[0].insurance_company.id}">`;
            }
            
            $('#hiddenInputs').html(html);
        }

        function formatNumber(num) {
            return new Intl.NumberFormat('fr-FR').format(Math.round(num));
        }

        // Reset au fermeture
        $('#addNewPaiementModal').on('hidden.bs.modal', function() {
            actes = [];
            insurances = [];
            $('#insuranceSection').hide();
            $('#useInsurance').prop('checked', false);
        });

    </script>
@endsection