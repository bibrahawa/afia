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
            <a href="{{ route('test.index') }}">Tests</a>
          </li>
        </ul>
      </div>

      <div class="row">
        <div class="col-md-12">
          <div class="card">
            <div class="card-header">
              <div class="d-flex align-items-center">
                <h4 class="card-title">Liste des tests</h4>
                <button
                  class="btn btn-primary btn-round ms-auto"
                  data-bs-toggle="modal"
                  data-bs-target="#addRowModal"
                >
                  <i class="fa fa-plus"></i> Ajouter un test
                </button>
              </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table id="add-row" class="display table table-striped table-hover">
                    <thead class="bg-primary text-white"> <!-- Ajout de couleur d'entête -->
                        <tr>
                            <th style="width: 10%">ID</th>
                            <th>Name</th>
					        <th>Type</th>
					        <td>Description</td>
                            <th style="width: 10%">Actions</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th>ID</th>
				            <th>Name</th>
					        <th>Type</th>
					        <td>Description</td>
				            <th>Actions</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        @foreach($tests as $test)
                            <tr>
                                <td>{{ $test->id}}</td>
                                <td>{{ $test->name}}</td>
                                <td>{{ ucfirst($test->report_type) }}</td>
                                <td>{{ $test->description}}</td>
                                <td>
                                    <div class="form-button-action">
                                        <!-- Modifier : Ajout des data-bs-toggle et data-bs-target -->
                                        <button
                                            type="button"
                                            class="btn btn-warning btn-round btn-sm edit-button"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editRowModal"
                                            data-info="{{$test}}">

                                            <i class="fa fa-edit"></i>
                                        </button>

                                        <!-- Supprimer : Ajout des data-bs-toggle et data-bs-target -->
                                        <button
                                            type="button"
                                            class="btn btn-danger btn-round btn-sm delete-button"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteRowModal"
                                            data-id="{{$test->id}}"
                                            data-name="{{$test->name}}"
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
                                    <span class="fw-light"> test</span>
                                </h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <p class="small">Créez un nouveau test en remplissant le formulaire ci-dessous.</p>
                                <form id="addTestForm" action="{{ route('test.store') }}" method="POST">
                                    @csrf
                                    <div class="row">

                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Nom du test</label>
                                                <input id="name" name="name" type="text" class="form-control" placeholder="Entrez le nom" required/>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Amount</label>
                                                <div class="input-group">
                                                    <input type="text" name="amount" class="form-control" placeholder="Amount">
                                                    <span class="input-group-text">GNF</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label class="form-label">Type :</label>
                                                <select class="selectpicker" name="report_type" data-live-search="true">
                                                    <option value="haematology">HÉMATOLOGIE</option>
                                                    <option value="biochemistry">BIOCHIMIE</option>
                                                    <option value="immunology">IMMUNOLOGIE</option>
                                                    <option value="examination">EXAMEN</option>
                                                    <option value="microbiology">MICROBIOLOGIE</option>
                                                    <option value="stain">COLORATION</option>
                                                    <option value="widal">TEST DE WIDAL</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-sm-12">
                                            <div class="form-group form-group-default">
                                                <label>Description</label>
                                                <textarea name='description' class="form-control" placeholder="Description"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer border-0">
                                <button type="submit" id="addRowButton" class="btn btn-primary" form="addTestForm">
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
                                    <span class="fw-light"> test</span>
                                </h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form id='editTestForm' action="#" method="POST">
                                    @csrf
                                    @method('POST')
                                    <div class="row">
                                        <input type="hidden" name="edit_id" name="id">
                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Nom du test</label>
                                                <input id="edit_name" name="name" type="text" class="form-control" placeholder="Entrez le nom" required/>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Amount</label>
                                                <div class="input-group">
                                                    <input type="text" id="edit_amount" name="amount" class="form-control" placeholder="Amount">
                                                    <span class="input-group-text">GNF</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label>Type :</label>
                                                <select class="selectpicker" name="report_type" id="edit_report_type" data-live-search="true">
                                                    <option value="haematology">HÉMATOLOGIE</option>
                                                    <option value="biochemistry">BIOCHIMIE</option>
                                                    <option value="immunology">IMMUNOLOGIE</option>
                                                    <option value="examination">EXAMEN</option>
                                                    <option value="microbiology">MICROBIOLOGIE</option>
                                                    <option value="stain">COLORATION</option>
                                                    <option value="widal">TEST DE WIDAL</option>
                                                </select>
                                            </div>
                                        </div>

                                        {{-- <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Service</label>
                                                <select name="service_id" id="edit_service_id" class="form-control">
                                                    <option disabled selected>Selectionnez un service</option>
                                                    @foreach ($services as $service)
                                                        <option value="{{$service->id}}">{{ $service->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div> --}}

                                        <div class="col-sm-12">
                                            <div class="form-group form-group-default">
                                                <label>Description</label>
                                                <textarea name='description' id="edit_description" class="form-control" placeholder="Description"></textarea>
                                            </div>
                                        </div>
                                    </div>
                            </div>
                            <div class="modal-footer border-0">
                                <!-- Bouton pour la modification -->
                                <button type="submit" class="btn btn-success" id="editRowButton" form="editTestForm">
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
                                <h5 class="modal-title">Êtes-vous sûr de vouloir supprimer ce test ?</h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <!-- Formulaire de suppression -->
                                <form id="deleteTestForm" action="{{ route('test.delete', ['id' => '']) }}" method="POST">
                                    @csrf
                                    @method('DELETE') <!-- Utiliser la méthode DELETE -->
                                    <p id="test_name_to_delete"></p>
                                    <input type="hidden" id="delete_id" name="id">
                                </form>
                            </div>
                            <div class="modal-footer border-0">
                                <!-- Bouton pour la suppression -->
                                <button type="submit" class="btn btn-danger" id="deleteRowButton" form="deleteTestForm">
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
        // Événement pour modifier un test
        $(document).on('click', '.edit-button', function() {
            var test = $(this).data('info')
            // Mettre à jour le champ du modal
            $('#edit_id').val(test.id);
            $('#edit_name').val(test.name);
            $('#edit_amount').val(test.amount);
            $('#edit_description').val(test.description);
            $('#edit_service_id').val(test.service_id);
            $('#edit_report_type').val(test.report_type);

            // Afficher le modal
            $('#editRowModal').modal('show');
        });

        // Événement pour supprimer un test
        $(document).on('click', '.delete-button', function() {
            var id = $(this).data('id');
            var name = $(this).data('name');

            // Afficher le nom du test à supprimer
            $('#test_name_to_delete').text("Voulez-vous vraiment supprimer l'examen : " + name + " ?");

            // Mettre à jour l'action du formulaire de suppression avec l'ID du test
            $('#delete_id').val(id);
            $('#deleteTestForm').attr('action', '/test/delete/' + id);

            // Afficher le modal de confirmation
            $('#deleteRowModal').modal('show');
        });

        // Afficher le loader pour l'ajout de test
        $('#addTestForm').on('submit', function() {
            $('#addRowButton').prop('disabled', true);  // Désactive le bouton pour éviter plusieurs clics
            $('#addLoader').show();  // Affiche le loader
        });

        // Afficher le loader pour la modification de test
        $('#editTestForm').on('submit', function() {
            $('#editRowButton').prop('disabled', true);  // Désactive le bouton pour éviter plusieurs clics
            $('#editLoader').show();  // Affiche le loader
        });

        // Afficher le loader pour la suppression de test
        $('#deleteTestForm').on('submit', function() {
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
