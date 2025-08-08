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
                                    <a href="{{ route('patient.show', $patient->id) }}" class="btn btn-sm btn-primary"><i class="fa fa-eye"></i></a>
                                    <div class="form-button-action">
                                        <button
                                            type="button"
                                            class="btn btn-success btn-sm payer-button"
                                            data-bs-toggle="modal"
                                            data-bs-target="#addNewPaiementModal"
                                            data-newpaiement="{{ $patient }}">
                                            <i class="fas fa-money-bill"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    </table>
                </div>

                <!-- Modal Paiement avec Assurance -->
                <div class="modal fade" id="addNewPaiementModal" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header border-0">
                                <h5 class="modal-title">
                                    <span class="fw-mediumbold">Paiement d'une consultation</span>
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

                                    <!-- Section Assurance -->
                                    <div class="row mb-4">
                                        <div class="col-12">
                                            <h6 class="text-primary mb-3">Informations d'assurance</h6>
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="checkbox" id="hasInsurance" name="has_insurance">
                                                <label class="form-check-label" for="hasInsurance">
                                                    La patiente a-t-elle une assurance ?
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Section des assurances (masquée par défaut) -->
                                    <div id="insuranceSection" style="display: none;">
                                        <div class="row">
                                            <!-- Première assurance -->
                                            <div class="col-md-6">
                                                <div class="card border-primary mb-3">
                                                    <div class="card-header bg-light">
                                                        <h6 class="mb-0">Assurance principale</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="form-group mb-3">
                                                            <label>Nom de l'assurance:</label>
                                                            <select name="assurance_1" id="assurance_1" class="form-control">
                                                                <option value="">Sélectionner une assurance</option>
                                                                @if(isset($assurances))
                                                                    @foreach($assurances as $assurance)
                                                                        <option value="{{ $assurance->id }}" data-coverage="{{ $assurance->taux_couverture }}">
                                                                            {{ $assurance->nom }} ({{ $assurance->taux_couverture }}%)
                                                                        </option>
                                                                    @endforeach
                                                                @endif
                                                            </select>
                                                        </div>
                                                        <div class="form-group">
                                                            <label>Numéro de police:</label>
                                                            <input type="text" name="police_1" class="form-control" placeholder="Numéro de police">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Deuxième assurance (optionnelle) -->
                                            <div class="col-md-6">
                                                <div class="card border-secondary mb-3">
                                                    <div class="card-header bg-light">
                                                        <h6 class="mb-0">Assurance secondaire (optionnelle)</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="form-group mb-3">
                                                            <label>Nom de l'assurance:</label>
                                                            <select name="assurance_2" id="assurance_2" class="form-control">
                                                                <option value="">Sélectionner une assurance</option>
                                                                @if(isset($assurances))
                                                                    @foreach($assurances as $assurance)
                                                                        <option value="{{ $assurance->id }}" data-coverage="{{ $assurance->taux_couverture }}">
                                                                            {{ $assurance->nom }} ({{ $assurance->taux_couverture }}%)
                                                                        </option>
                                                                    @endforeach
                                                                @endif
                                                            </select>
                                                        </div>
                                                        <div class="form-group">
                                                            <label>Numéro de police:</label>
                                                            <input type="text" name="police_2" class="form-control" placeholder="Numéro de police">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Calcul automatique -->
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="alert alert-info">
                                                    <h6>Calcul de la couverture:</h6>
                                                    <div id="calculCouverture">
                                                        <p class="mb-1">Montant original: <span id="montantOriginalDisplay">0 GNF</span></p>
                                                        <p class="mb-1">Couverture assurance 1: <span id="couverture1">0 GNF</span></p>
                                                        <p class="mb-1">Couverture assurance 2: <span id="couverture2">0 GNF</span></p>
                                                        <hr>
                                                        <p class="mb-0 fw-bold text-primary">Reste à payer: <span id="resteAPayer">0 GNF</span></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Section de paiement -->
                                    <div class="row mt-4">
                                        <div class="col-12">
                                            <h6 class="text-primary mb-3">Informations de paiement</h6>
                                        </div>

                                        {{-- <div class="col-sm-12">
                                            <div class="form-group form-group-default">
                                                <label>Mode de paiement:</label>
                                                <div class="input-group">
                                                    <input type="text" name="source" class="form-control" value="CASH" placeholder="montant" readonly required>
                                                </div>
                                            </div>
                                        </div> --}}

                                        <div class="col-sm-12">
                                            <div class="form-group form-group-default">
                                                <label class="form-label">Description:</label>
                                                <textarea name="description" class="form-control" placeholder="Ecrivez une description ici"></textarea>
                                            </div>
                                        </div>

                                        <div class="col-sm-12">
                                            <div class="form-group form-group-default">
                                                <label>Montant à payer</label>
                                                <div class="input-group">
                                                    <input type="text" name="montant" id="montantAPayer" class="form-control" placeholder="montant" required>
                                                    <span class="input-group-text">GNF</span>
                                                </div>
                                                <small class="text-muted">Le montant sera calculé automatiquement si des assurances sont sélectionnées</small>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer border-0">
                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Fermer</button>
                                <button type="submit" class="btn btn-success" id="editRowButton" form="addNewPaiementForm">
                                    Payer
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

        // Événement pour modifier un patient
        $(document).on('click', '.payer-button', function() {
            var patient = $(this).data('newpaiement');

            $('#patient_id').val(patient.id);
            $('#montant_original').val(patient.montant_du);
            $('#montantOriginalDisplay').text(numberFormat(patient.montant_du) + ' GNF');
            
            // Réinitialiser le formulaire
            resetInsuranceForm();
            
            // Afficher le modal
            $('#addNewPaiementModal').modal('show');
        });

        // Gestion de la checkbox d'assurance
        $('#hasInsurance').change(function() {
            if ($(this).is(':checked')) {
                $('#insuranceSection').slideDown();
            } else {
                $('#insuranceSection').slideUp();
                resetInsuranceForm();
                // Montant à payer = montant original
                var montantOriginal = $('#montant_original').val();
                $('#montantAPayer').val(montantOriginal);
            }
        });

        // Calcul automatique lors du changement d'assurance
        $('#assurance_1, #assurance_2').change(function() {
            calculateCoverage();
        });

        function calculateCoverage() {
            var montantOriginal = parseFloat($('#montant_original').val()) || 0;
            var couverture1 = 0;
            var couverture2 = 0;

            // Calcul couverture assurance 1
            var assurance1 = $('#assurance_1 option:selected');
            if (assurance1.val()) {
                var taux1 = parseFloat(assurance1.data('coverage')) || 0;
                couverture1 = (montantOriginal * taux1) / 100;
            }

            // Calcul couverture assurance 2 sur le reste
            var assurance2 = $('#assurance_2 option:selected');
            if (assurance2.val()) {
                var reste = montantOriginal - couverture1;
                var taux2 = parseFloat(assurance2.data('coverage')) || 0;
                couverture2 = (reste * taux2) / 100;
            }

            // Calcul du reste à payer
            var resteAPayer = montantOriginal - couverture1 - couverture2;
            
            // Mise à jour de l'affichage
            $('#couverture1').text(numberFormat(couverture1) + ' GNF');
            $('#couverture2').text(numberFormat(couverture2) + ' GNF');
            $('#resteAPayer').text(numberFormat(resteAPayer) + ' GNF');
            
            // Mise à jour du champ montant à payer
            $('#montantAPayer').val(Math.round(resteAPayer));
        }

        function resetInsuranceForm() {
            $('#assurance_1').val('');
            $('#assurance_2').val('');
            $('input[name="police_1"]').val('');
            $('input[name="police_2"]').val('');
            $('#hasInsurance').prop('checked', false);
            $('#couverture1').text('0 GNF');
            $('#couverture2').text('0 GNF');
            $('#resteAPayer').text('0 GNF');
        }

        function numberFormat(number) {
            return new Intl.NumberFormat('fr-FR').format(number);
        }

        // Événement pour supprimer un patient
        $(document).on('click', '.delete-button', function() {
            var patient = $(this).data('patient');

            $('#department_name_to_delete').text("Voulez-vous vraiment supprimer le patient : " + patient.first_name +' '+ patient.last_name + " ?");
            $('#delete_id').val(patient.id);
            $('#deleteDepartmentForm').attr('action', '/patient/' + patient.id);
            $('#deleteRowModal').modal('show');
        });

        // Afficher le loader pour l'ajout de patient
        $('#addDepartmentForm').on('submit', function() {
            $('#addRowButton').prop('disabled', true);
            $('#addLoader').show();
        });

        // Afficher le loader pour la modification de patient
        $('#addNewPaiementForm').on('submit', function() {
            $('#editRowButton').prop('disabled', true);
            $('#editLoader').show();
        });

        // Afficher le loader pour la suppression de patient
        $('#deleteDepartmentForm').on('submit', function() {
            $('#deleteRowButton').prop('disabled', true);
            $('#deleteLoader').show();
        });

        // Lorsque la requête est terminée
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