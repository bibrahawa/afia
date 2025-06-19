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
            <a href="{{ route('service.index') }}">Services</a>
          </li>
        </ul>
      </div>

      <div class="row">
        <div class="col-md-12">
          <div class="card">
            <div class="card-header">
              <div class="d-flex align-items-center">
                <h4 class="card-title">Liste des services</h4>
                <button
                  class="btn btn-primary btn-round ms-auto"
                  data-bs-toggle="modal"
                  data-bs-target="#addRowModal"
                >
                  <i class="fa fa-plus"></i> Ajouter un service
                </button>
              </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table id="add-row" class="display table table-striped table-hover">
                    <thead class="bg-primary text-white"> <!-- Ajout de couleur d'entête -->
                        <tr>
                            <th style="width: 10%">ID</th>
                            <th>Service</th>
                            <th>Department</th>
                            <th>Amount</th>
                            <th style="width: 10%">Actions</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th>ID</th>
				            <th>Service</th>
				            <th>Department</th>
				            <th>Amount</th>
				            <th>Actions</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        @foreach($services as $service)
                            <tr>
                                <td>{{ $service->id}}</td>
                                <td>{{ $service->name}}</td>
                                <td>{{ $service->department->name}}</td>
                                <td>{{ number_format($service->amount, 2)}}</td>
                                <td>
                                    <div class="form-button-action">
                                        <!-- Modifier : Ajout des data-bs-toggle et data-bs-target -->
                                        <button
                                            type="button"
                                            class="btn btn-warning btn-round btn-sm edit-button"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editRowModal"
                                            data-info="{{$service->id}},{{$service->name}},{{$service->department_id}},{{$service->amount}}"
                                        >
                                            <i class="fa fa-edit"></i>
                                        </button>

                                        <!-- Supprimer : Ajout des data-bs-toggle et data-bs-target -->
                                        <button
                                            type="button"
                                            class="btn btn-danger btn-round btn-sm delete-button"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteRowModal"
                                            data-id="{{$service->id}}"
                                            data-name="{{$service->name}}"
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
                                    <span class="fw-light"> service</span>
                                </h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <p class="small">Créez un nouveau service en remplissant le formulaire ci-dessous.</p>
                                <form id="addDepartmentForm" action="{{ route('service.add') }}" method="POST">
                                    @csrf
                                    <div class="row">

                                        <div class="col-sm-12">
                                            <div class="form-group form-group-default">
                                                <label>Nom du service</label>
                                                <input id="name" name="name" type="text" class="form-control" placeholder="Entrez le nom" required/>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label>Departement</label>
                                                <select class="selectpicker" name="department_id" data-live-search="true">
                                                    @foreach ($departments as $department)
                                                        <option value="{{$department->id}}">{{ $department->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label>Amount</label>
                                                <div class="input-group">
                                                    <input type="text" name="amount" class="form-control" placeholder="Amount">
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
                                    <span class="fw-light"> service</span>
                                </h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form id='editDepartmentForm' action="{{ route('service.update') }}" method="POST">
                                    @csrf
                                    @method('POST')
                                    <div class="row">
                                        <input type="hidden" name="id" id="edit_id" value="">
                                        <div class="col-sm-12">
                                            <div class="form-group form-group-default">
                                                <label>Nom du service</label>
                                                <input id="edit_name" name="name" type="text" class="form-control" placeholder="Entrez le nom" required/>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <div class="form-group">
                                                    <label>Departement</label>
                                                    <select class="selectpicker" name="department_id" id="edit_department_id" data-live-search="true">
                                                    @foreach ($departments as $department)
                                                        <option value="{{$department->id}}">{{ $department->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label>Amount</label>
                                                <div class="input-group">
                                                    <input type="text" id="edit_amount" name="amount" class="form-control" aria-label="Amount (to the nearest dollar)">
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
                                <h5 class="modal-title">Êtes-vous sûr de vouloir supprimer ce service ?</h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <!-- Formulaire de suppression -->
                                <form id="deleteDepartmentForm" action="{{ route('service.delete', ['id' => '']) }}" method="POST">
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
        // Événement pour modifier un service
        $(document).on('click', '.edit-button', function() {
            var services = $(this).data('info').split(',');
            var serviceId = services[0];  // Récupérer l'ID
            var serviceName = services[1];  // Récupérer le name
            var serviceDepartmentId = services[2];  // Récupérer le departement name
            var serviceAmount = services[3];  // Récupérer le service amount
            // Mettre à jour le champ du modal
            $('#edit_id').val(serviceId);
            $('#edit_name').val(serviceName);
            $('#edit_department_id').val(serviceDepartmentId);
            $('#edit_amount').val(serviceAmount);

            // Afficher le modal
            $('#editRowModal').modal('show');
        });

        // Événement pour supprimer un service
        $(document).on('click', '.delete-button', function() {
            var id = $(this).data('id');
            var name = $(this).data('name');

            // Afficher le nom du service à supprimer
            $('#department_name_to_delete').text("Voulez-vous vraiment supprimer le service : " + name + " ?");

            // Mettre à jour l'action du formulaire de suppression avec l'ID du service
            $('#delete_id').val(id);
            $('#deleteDepartmentForm').attr('action', '/service/delete/' + id);

            // Afficher le modal de confirmation
            $('#deleteRowModal').modal('show');
        });

        // Afficher le loader pour l'ajout de service
        $('#addDepartmentForm').on('submit', function() {
            $('#addRowButton').prop('disabled', true);  // Désactive le bouton pour éviter plusieurs clics
            $('#addLoader').show();  // Affiche le loader
        });

        // Afficher le loader pour la modification de service
        $('#editDepartmentForm').on('submit', function() {
            $('#editRowButton').prop('disabled', true);  // Désactive le bouton pour éviter plusieurs clics
            $('#editLoader').show();  // Affiche le loader
        });

        // Afficher le loader pour la suppression de service
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
