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
            <a href="{{ route('doctor.index') }}">doctors</a>
          </li>
        </ul>
      </div>

      <div class="row">
        <div class="col-md-12">
          <div class="card">
            <div class="card-header">
              <div class="d-flex align-items-center">
                <h4 class="card-title">Liste des doctors</h4>
                <button
                  class="btn btn-primary btn-round ms-auto"
                  data-bs-toggle="modal"
                  data-bs-target="#addRowModal"
                >
                  <i class="fa fa-plus"></i> Ajouter un doctor
                </button>
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
                            <th>Department</th>
                            <th>OPD Fee</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Department</th>
                            <th>OPD Fee</th>
                            <th>Action</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        @foreach($doctors as $doctor)
                            <tr>
                                <td>{{ $doctor->id}}</td>
                                <td>{{ $doctor->employee->first_name}} {{$doctor->employee->middle_name}} {{$doctor->employee->last_name}}</td>
                                <td>{{ $doctor->employee->phone}}</td>
                                <td>{{$doctor->employee->department->name}}</td>
                                <td>${{$doctor->fee}}</td>
                                <td>
                                    <div class="form-button-action">
                                        <!-- Modifier : Ajout des data-bs-toggle et data-bs-target -->
                                        <button
                                            type="button"
                                            class="btn btn-warning btn-round btn-sm edit-button"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editRowModal"
                                            data-info="{{$doctor}}"
                                        >
                                            <i class="fa fa-edit"></i>
                                        </button>

                                        <!-- Supprimer : Ajout des data-bs-toggle et data-bs-target -->
                                        <button
                                            type="button"
                                            class="btn btn-danger btn-round btn-sm delete-button"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteRowModal"
                                            data-doctor = "{{$doctor}}"
                                        >
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    </table>
                </div>

                <!-- Modal Add -->
                <div class="modal fade" id="addRowModal" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header border-0">
                                <h5 class="modal-title">
                                    <span class="fw-mediumbold"> Nouveau</span>
                                    <span class="fw-light"> doctor</span>
                                </h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <p class="small">Créez un nouveau doctor en remplissant le formulaire ci-dessous.</p>
                                <form id="addDepartmentForm" action="{{ route('doctor.store') }}" method="POST">
                                    @csrf
                                    <div class="row">

                                        <div class="col-sm-12">
                                            <div class="form-group form-group-default">
                                                <label>Select Doctor:</label>
                                                <select name="employee_id" class="form-control">
                                                    <option disabled selected>Selectionnez un departement</option>
                                                    @foreach ($employees as $employee)
                                                        <option value="{{$employee->id}}">{{$employee->first_name}} {{$employee->middle_name}} {{$employee->last_name}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Doctor Charge:</label>
                                                <div class="input-group">
                                                    <input type="number" name="fee" class="form-control" placeholder="fee">
                                                    <span class="input-group-text">GNF</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>OPD Charge:</label>
                                                <div class="input-group">
                                                    <input type="number" name="opd_charge" class="form-control" placeholder="fee">
                                                    <span class="input-group-text">GNF</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer border-0">
                                <button type="submit" id="addRowButton" class="btn btn-primary" form="addDepartmentForm">
                                    Ajouter
                                    <div class="spinner-border spinner-border-sm text-light" role="status" id="addLoader" style="display: none;">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                </button>

                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">
                                    Fermer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Edit -->
                <div class="modal fade" id="editRowModal" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header border-0">
                                <h5 class="modal-title">
                                    <span class="fw-mediumbold"> Modifier</span>
                                    <span class="fw-light"> doctor</span>
                                </h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form id='editDepartmentForm' action="#" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <input type="hidden" name="edit_id" id="edit_id">
                                            <div class="form-group form-group-default">
                                                <label>Select Doctor:</label>
                                                <select name="employee_id" id="edit_employee_id" class="form-control">
                                                    <option disabled selected>Selectionnez un docteur</option>
                                                    @foreach ($employees as $employee)
                                                        <option value="{{$employee->id}}">{{$employee->first_name}} {{$employee->middle_name}} {{$employee->last_name}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Doctor Charge:</label>
                                                <div class="input-group">
                                                    <input type="number" name="fee" id="edit_fee" class="form-control" placeholder="fee">
                                                    <span class="input-group-text">GNF</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>OPD Charge:</label>
                                                <div class="input-group">
                                                    <input type="number" name="opd_charge" id="edit_opd_charge" class="form-control" placeholder="fee">
                                                    <span class="input-group-text">GNF</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                            </div>
                            <div class="modal-footer border-0">
                                <!-- Bouton pour la modification -->
                                <button type="submit" class="btn btn-success" id="editRowButton" form="editDepartmentForm">
                                    Modifier
                                    <div class="spinner-border spinner-border-sm text-light" role="status" id="editLoader" style="display: none;">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                </button>
                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Fermer</button>
                            </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Modal Delete -->
                <div class="modal fade" id="deleteRowModal" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header border-0">
                                <h5 class="modal-title">Êtes-vous sûr de vouloir supprimer ce doctor ?</h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <!-- Formulaire de suppression -->
                                <form id="deleteDepartmentForm" action="#" method="POST">
                                    @csrf
                                    @method('DELETE') <!-- Utiliser la méthode DELETE -->
                                    <p id="department_name_to_delete"></p>
                                    <input type="hidden" id="delete_id" name="id">
                                </form>
                            </div>
                            <div class="modal-footer border-0">
                                <!-- Bouton pour la suppression -->
                                <button type="submit" class="btn btn-danger" id="deleteRowButton" form="deleteDepartmentForm">
                                    Supprimer
                                    <div class="spinner-border spinner-border-sm text-light" role="status" id="deleteLoader" style="display: none;">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                </button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    Annuler
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
        // Événement pour modifier un doctor
        $(document).on('click', '.edit-button', function() {
            var doctor = $(this).data('info');
            // Mettre à jour le champ du modal
            $('#edit_id').val(doctor.id);
            $('#edit_fee').val(doctor.fee);
            $('#edit_opd_charge').val(doctor.opd_charge);
            $('#edit.doctor_id').val(doctor.employee);

            $('#editDepartmentForm').attr('action', '/doctor/' + doctor.id);

            // Afficher le modal
            $('#editRowModal').modal('show');
        });

        // Événement pour supprimer un doctor
        $(document).on('click', '.delete-button', function() {
            var id = $(this).data('doctor').id;
            var name = $(this).data('doctor').employee.first_name + " " + $(this).data('doctor').employee.last_name;

            // Afficher le nom du doctor à supprimer
            $('#department_name_to_delete').text("Voulez-vous vraiment supprimer le doctor : " + name + " ?");

            // Mettre à jour l'action du formulaire de suppression avec l'ID du doctor
            $('#delete_id').val(id);
            $('#deleteDepartmentForm').attr('action', '/doctor/' + id);

            // Afficher le modal de confirmation
            $('#deleteRowModal').modal('show');
        });

        // Afficher le loader pour l'ajout de doctor
        $('#addDepartmentForm').on('submit', function() {
            $('#addRowButton').prop('disabled', true);  // Désactive le bouton pour éviter plusieurs clics
            $('#addLoader').show();  // Affiche le loader
        });

        // Afficher le loader pour la modification de doctor
        $('#editDepartmentForm').on('submit', function() {
            $('#editRowButton').prop('disabled', true);  // Désactive le bouton pour éviter plusieurs clics
            $('#editLoader').show();  // Affiche le loader
        });

        // Afficher le loader pour la suppression de doctor
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
