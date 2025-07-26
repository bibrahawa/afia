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
            <a href="{{ route('employee.index') }}">employees</a>
          </li>
        </ul>
      </div>

      <div class="row">
        <div class="col-md-12">
          <div class="card">
            <div class="card-header">
              <div class="d-flex align-items-center">
                <h4 class="card-title">Liste des employees</h4>
                {{-- <button
                  class="btn btn-primary btn-round ms-auto"
                  data-bs-toggle="modal"
                  data-bs-target="#addRowModal"
                >
                  <i class="fa fa-plus"></i> Ajouter un employee
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
					        <th>Contact</th>
					        {{-- <th>Working Days</th>
					        <th>In-time</th>
					        <th>Out-time</th> --}}
					        <th>Type</th>
                            <th style="width: 10%">Actions</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th>ID</th>
					        <th>Name</th>
					        <th>Contact</th>
					        {{-- <th>Working Days</th>
					        <th>In-time</th>
					        <th>Out-time</th> --}}
					        <th>Type</th>
                            <th>Actions</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        @foreach($employees as $employee)
                            <tr>
                                <td>{{ $employee->id}}</td>
                                <td>{{$employee->first_name}} {{$employee->middle_name}} {{$employee->last_name}}</td>
                                <td>{{$employee->phone}}</td>
                                {{-- <td>{{$employee->working_day}}</td>
                                <td>{{$employee->in_time}}</td>
                                <td>{{$employee->out_time}}</td> --}}
                                <td>{{$employee->type}}</td>
                                <td>
                                    <div class="form-button-action">
                                        <!-- Modifier : Ajout des data-bs-toggle et data-bs-target -->
                                        <button
                                            type="button"
                                            class="btn btn-warning btn-round btn-sm edit-button"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editRowModal"
                                            data-info="{{ $employee }}"
                                        >
                                            <i class="fa fa-edit"></i>
                                        </button>

                                        <!-- Supprimer : Ajout des data-bs-toggle et data-bs-target -->
                                        <button
                                            type="button"
                                            class="btn btn-danger btn-round btn-sm delete-button"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteRowModal"
                                            data-employee="{{$employee}}"
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
                                    <span class="fw-light"> employee</span>
                                </h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <p class="small">Créez un nouveau employee en remplissant le formulaire ci-dessous.</p>
                                <form id="addEmployeForm" action="{{ route('employee.store') }}" method="POST">
                                    @csrf
                                    <div class="row">

                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>First Name:</label>
                                                <input id="first_name" name="first_name" type="text" class="form-control" placeholder="Entrez votre prenom" required/>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Last Name:</label>
                                                <input id="last_name" name="last_name" type="text" class="form-control" placeholder="Entrez votre nom" required/>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Type:</label>
                                                <select class="form-control" name="type" required>
                                                    <option value="Docteur">Docteur</option>
                                                    <option value="Laboratoire">Laboratoire</option>
                                                    <option value="Secretaire">Secretaire</option>
                                                    <option value="Comptable">Comptable</option>
                                                    <option value="Infirmière">Infirmière</option>
                                                    <option value="Autre">Autre</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Departement</label>
                                                <select name="department_id" class="form-control">
                                                    <option disabled selected>Selectionnez un departement</option>
                                                    @foreach ($departments as $department)
                                                        <option value="{{$department->id}}">{{ $department->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Address:</label>
                                                <textarea id="address" name="address" class="form-control" placeholder="Description"></textarea>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Education:</label>
                                                <textarea id="education" name="education" class="form-control" placeholder="Description"></textarea>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Descrption:</label>
                                                <textarea id="description" name="description" class="form-control" placeholder="Description"></textarea>
                                            </div>
                                        </div>


                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Certificate:</label>
                                                <textarea id="certificate" name="certificate" class="form-control" placeholder="Description"></textarea>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Speciality:</label>
                                                <textarea id="speciality" name="speciality" class="form-control" placeholder="Description"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer border-0">
                                <button type="submit" id="addRowButton" class="btn btn-primary" form="addEmployeForm">
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
                                    <span class="fw-light"> employee</span>
                                </h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form id='editEmployeForm' action="" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="row">
                                        <input type="hidden" name="id" id="edit_id">

                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>First Name:</label>
                                                <input id="edit_first_name" name="first_name" type="text" class="form-control" placeholder="Entrez l'email" required/>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Last Name:</label>
                                                <input id="edit_last_name" name="last_name" type="text" class="form-control" placeholder="Entrez l'email" required/>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Telephone</label>
                                                <input id="edit_phone" name="phone" type="phone" class="form-control" placeholder="Entrez votre numero" required/>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label>Type:</label>
                                                <select class="form-control" name="type" id="edit_type" required>
                                                    <option value="Docteur">Docteur</option>
                                                    <option value="Laboratoire">Laboratoire</option>
                                                    <option value="Secretaire">Secretaire</option>
                                                    <option value="Comptable">Comptable</option>
                                                    <option value="Infirmière">Infirmière</option>
                                                    <option value="Autre">Autre</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Departement</label>
                                                <select name="department_id" id="edit_department_id" class="form-control">
                                                    <option disabled selected>Selectionnez un departement</option>
                                                    @foreach ($departments as $department)
                                                        <option value="{{$department->id}}">{{ $department->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Address:</label>
                                                <textarea id="edit_address" name="address" class="form-control" placeholder="Description"></textarea>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Education:</label>
                                                <textarea id="edit_education" name="education" class="form-control" placeholder="Description"></textarea>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Descrption:</label>
                                                <textarea id="edit_description" name="description" class="form-control" placeholder="Description"></textarea>
                                            </div>
                                        </div>


                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Certificate:</label>
                                                <textarea id="edit_certificate" name="certificate" class="form-control" placeholder="Description"></textarea>
                                            </div>
                                        </div>

                                        <div class="col-sm-12">
                                            <div class="form-group form-group-default">
                                                <label>Speciality:</label>
                                                <textarea id="edit_speciality" name="speciality" class="form-control" placeholder="Description"></textarea>
                                            </div>
                                        </div>

                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer border-0">
                                <!-- Bouton pour la modification -->
                                <button type="submit" class="btn btn-success" id="editRowButton" form="editEmployeForm">
                                    Modifier
                                    <div class="spinner-border spinner-border-sm text-light" role="status" id="editLoader" style="display: none;">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                </button>

                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Fermer</button>

                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Delete -->
                <div class="modal fade" id="deleteRowModal" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header border-0">
                                <h5 class="modal-title">Êtes-vous sûr de vouloir supprimer ce employee ?</h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <!-- Formulaire de suppression -->
                                <form id="deleteEmployeForm" action="#" method="POST">
                                    @csrf
                                    @method('DELETE') <!-- Utiliser la méthode DELETE -->
                                    <p id="employes_name_to_delete"></p>
                                    <input type="hidden" id="delete_id" name="id">
                                </form>
                            </div>
                            <div class="modal-footer border-0">
                                <!-- Bouton pour la suppression -->
                                <button type="submit" class="btn btn-danger" id="deleteRowButton" form="deleteEmployeForm">
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

        // Événement pour modifier un employee
        $(document).on('click', '.edit-button', function() {
            var employee = $(this).data('info');

            // Mettre à jour le champ du modal
            $('#edit_id').val(employee.id);
            $('#edit_first_name').val(employee.first_name);
            $('#edit_last_name').val(employee.last_name);=
            $('#edit_phone').val(employee.phone);
            $('#edit_address').val(employee.address);
            $('#edit_education').val(employee.education);
            $('#edit_description').val(employee.description);
            $('#edit_certificate').val(employee.certificate);
            $('#edit_speciality').val(employee.speciality);
            $('#edit_type').val(employee.type);
            $('#edit_department_id').val(employee.department_id);

            $('#editEmployeForm').attr('action', '/employee/' + employee.id);

            // Afficher le modal
            $('#editRowModal').modal('show');
        });

        // Événement pour supprimer un employee
        $(document).on('click', '.delete-button', function() {
            var employee = $(this).data('employee');

            // Afficher le nom du employee à supprimer
            $('#employes_name_to_delete').text("Voulez-vous vraiment supprimer l'employee : " + employee.first_name +' '+ employee.last_name + " ?");

            // Mettre à jour l'action du formulaire de suppression avec l'ID du employee
            $('#delete_id').val(employee.id);
            $('#deleteEmployeForm').attr('action', '/employee/' + employee.id);

            // Afficher le modal de confirmation
            $('#deleteRowModal').modal('show');
        });

        // Afficher le loader pour l'ajout de employee
        $('#addEmployeForm').on('submit', function() {
            $('#addRowButton').prop('disabled', true);  // Désactive le bouton pour éviter plusieurs clics
            $('#addLoader').show();  // Affiche le loader
        });

        // Afficher le loader pour la modification de employee
        $('#editEmployeForm').on('submit', function() {
            $('#editRowButton').prop('disabled', true);  // Désactive le bouton pour éviter plusieurs clics
            $('#editLoader').show();  // Affiche le loader
        });

        // Afficher le loader pour la suppression de employee
        $('#deleteEmployeForm').on('submit', function() {
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
