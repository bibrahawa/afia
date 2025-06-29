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
                {{-- <button
                  class="btn btn-primary btn-round ms-auto"
                  data-bs-toggle="modal"
                  data-bs-target="#addRowModal">
                  <i class="fa fa-plus"></i> Ajouter un patient
                </button> --}}
              </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table id="add-row" class="display table table-striped table-hover">
                    <thead class="bg-primary text-white"> <!-- Ajout de couleur d'entête -->
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
                                        <!-- Modifier : Ajout des data-bs-toggle et data-bs-target -->
                                        <button
                                            type="button"
                                            class="btn btn-success btn-sm payer-button"
                                            data-bs-toggle="modal"
                                            data-bs-target="#addNewPaiementModal"
                                            data-newpaiement="{{ $patient }}"
                                        >
                                            <i class="fas fa-money-bill"></i>
                                        </button>

                                    </div>

                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    </table>
                </div>

                <!-- Modal Paiement -->
                <div class="modal fade" id="addNewPaiementModal" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header border-0">
                                <h5 class="modal-title">
                                    <span class="fw-mediumbold">Paiement d'une consultation</span>
                                    <span class="fw-light"></span>
                                </h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form id='addNewPaiementForm' action="{{ route("account.payer") }}" method="POST">
                                    @csrf
                                    <div class="row">
                                        <input type="hidden" name="patient_id" id="patient_id" value="">

                                        <div class=" col-sm-12">
                                            <div class="form-group form-group-default">
                                                <label>Mode de paiement:</label>
                                                <div class="input-group">
                                                    <input type="text" name="source" class="form-control" value="CASH" placeholder="montant" readonly required>
                                                </div>
                                            </div>
                                        </div>


                                        <div class="col-sm-12">
                                            <div class="form-group form-group-default">
                                                <label class="form-label">Description:</label>
                                                <textarea name="description" class="form-control" placeholder="Ecrivez une description ici"></textarea>
                                            </div>
                                        </div>

                                        <div class="col-sm-12">
                                            <div class="form-group form-group-default">
                                                <label>Amount</label>
                                                <div class="input-group">
                                                    <input type="text" name="montant" class="form-control" placeholder="montant" required>
                                                    <span class="input-group-text">GNF</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer border-0">
                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Fermer</button>
                                <!-- Bouton pour le paiement -->
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

            $('#patient_id').val(patient.id); // Mettre à jour le champ caché avec l'ID du patient

            // Afficher le modal
            $('#addNewPaiementModal').modal('show');
        });

        // Événement pour supprimer un patient
        $(document).on('click', '.delete-button', function() {
            var patient = $(this).data('patient');

            // Afficher le nom du patient à supprimer
            $('#department_name_to_delete').text("Voulez-vous vraiment supprimer le patient : " + patient.first_name +' '+ patient.last_name + " ?");

            // Mettre à jour l'action du formulaire de suppression avec l'ID du patient
            $('#delete_id').val(patient.id);
            $('#deleteDepartmentForm').attr('action', '/patient/' + patient.id);

            // Afficher le modal de confirmation
            $('#deleteRowModal').modal('show');
        });

        // Afficher le loader pour l'ajout de patient
        $('#addDepartmentForm').on('submit', function() {
            $('#addRowButton').prop('disabled', true);  // Désactive le bouton pour éviter plusieurs clics
            $('#addLoader').show();  // Affiche le loader
        });

        // Afficher le loader pour la modification de patient
        $('#addNewPaiementForm').on('submit', function() {
            $('#editRowButton').prop('disabled', true);  // Désactive le bouton pour éviter plusieurs clics
            $('#editLoader').show();  // Affiche le loader
        });

        // Afficher le loader pour la suppression de patient
        $('#deleteDepartmentForm').on('submit', function() {
            $('#deleteRowButton').prop('disabled', true);  // Désactive le bouton pour éviter plusieurs clics
            $('#deleteLoader').show();  // Affiche le loader
        });

        // Lorsque la requête est terminée (réponse du serveur)
        $(document).ajaxComplete(function() {
            // Masquer les loaders et réactiver les boutons
            $('#addRowButton').prop('disabled', false);  // Réactive le bouton "Ajouter"
            $('#addLoader').hide();  // Masque le loader "Ajouter"

            $('#editRowButton').prop('disabled', false);  // Réactive le bouton "Modifier"
            $('#editLoader').hide();  // Masque le loader "Modifier"

            $('#deleteRowButton').prop('disabled', false);  // Réactive le bouton "Supprimer"
            $('#deleteLoader').hide();  // Masque le loader "Supprimer"
        });

    </script>
@endsection
