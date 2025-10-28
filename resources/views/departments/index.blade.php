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
            <a href="{{ route('department.index') }}">Department</a>
          </li>
        </ul>
      </div>

      <div class="row">
        <div class="col-md-12">
          <div class="card">
            <div class="card-header">
              <div class="d-flex align-items-center">
                <h4 class="card-title">Liste des departements</h4>
                @can('department.create')
                    <button
                    class="btn btn-primary btn-round ms-auto"
                    data-bs-toggle="modal"
                    data-bs-target="#addRowModal"
                    >
                    <i class="fa fa-plus"></i> Ajouter un département
                    </button>
                @endcan
              </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table id="add-row" class="display table table-striped table-hover">
                    <thead class="bg-primary text-white"> <!-- Ajout de couleur d'entête -->
                        <tr>
                        <th style="width: 10%">ID</th>
                        <th>Nom</th>
                        <th style="width: 10%">Action</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Action</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        @foreach($departments as $department)
                            <tr>
                                <td>{{ $department->id}}</td>
                                <td>{{ $department->name}}</td>
                                <td>
                                    <div class="form-button-action">
                                        @can('department.edit')
                                            <button
                                                type="button"
                                                class="btn btn-warning btn-round btn-sm edit-button"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editRowModal"
                                                data-info="{{$department->id}},{{$department->name}}"
                                            >
                                                <i class="fa fa-edit"></i>
                                            </button>
                                        @endcan
                                        @can('department.delete')
                                            <button
                                                type="button"
                                                class="btn btn-danger btn-round btn-sm delete-button"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteRowModal"
                                                data-id="{{$department->id}}"
                                                data-name="{{$department->name}}"
                                            >
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        @endcan
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
                            <span class="fw-light"> Département</span>
                        </h5>
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        </div>
                        <div class="modal-body">
                        <p class="small">Créez un nouveau département en remplissant le formulaire ci-dessous.</p>
                        <form id="addDepartmentForm" action="{{ route('department.add') }}" method="POST">
                            @csrf
                            <div class="row">
                            <div class="col-sm-12">
                                <div class="form-group form-group-default">
                                <label>Nom du département</label>
                                <input
                                    id="name"
                                    name="name"
                                    type="text"
                                    class="form-control"
                                    placeholder="Entrez le nom"
                                    required
                                />
                                </div>
                            </div>
                            </div>
                        </form>
                        </div>
                        <div class="modal-footer border-0">
                        <!-- Exemple de bouton avec un spinner -->
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
                                    <span class="fw-light"> Département</span>
                                </h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form id='editDepartmentForm' action="{{ route('department.update') }}" method="POST">
                                    @csrf
                                    @method('POST')
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <div class="form-group form-group-default">
                                                <input type="hidden" id="edit_id" name="id" />
                                                <label>Nom du département</label>
                                                <input
                                                    id="edit_name"
                                                    name="name"
                                                    type="text"
                                                    class="form-control"
                                                    placeholder="Entrez le nom"
                                                    required
                                                />
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
                                <h5 class="modal-title">Êtes-vous sûr de vouloir supprimer ce département ?</h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <!-- Formulaire de suppression -->
                                <form id="deleteDepartmentForm" action="{{ route('department.delete', ['id' => '']) }}" method="POST">
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
        // Événement pour modifier un département
        $(document).on('click', '.edit-button', function() {
            var details = $(this).data('info').split(',');
            var departmentId = details[0];  // Récupérer l'ID
            var departmentName = details[1];  // Récupérer le nom

            // Mettre à jour le champ du modal
            $('#edit_id').val(departmentId);
            $('#edit_name').val(departmentName);

            // Afficher le modal
            $('#editRowModal').modal('show');
        });

        // Événement pour supprimer un département
        $(document).on('click', '.delete-button', function() {
            var id = $(this).data('id');
            var name = $(this).data('name');

            // Afficher le nom du département à supprimer
            $('#department_name_to_delete').text("Voulez-vous vraiment supprimer le département : " + name + " ?");

            // Mettre à jour l'action du formulaire de suppression avec l'ID du département
            $('#delete_id').val(id);
            $('#deleteDepartmentForm').attr('action', '/department/delete/' + id);

            // Afficher le modal de confirmation
            $('#deleteRowModal').modal('show');
        });

        // Afficher le loader pour l'ajout de département
        $('#addDepartmentForm').on('submit', function() {
            $('#addRowButton').prop('disabled', true);  // Désactive le bouton pour éviter plusieurs clics
            $('#addLoader').show();  // Affiche le loader
        });

        // Afficher le loader pour la modification de département
        $('#editDepartmentForm').on('submit', function() {
            $('#editRowButton').prop('disabled', true);  // Désactive le bouton pour éviter plusieurs clics
            $('#editLoader').show();  // Affiche le loader
        });

        // Afficher le loader pour la suppression de département
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
