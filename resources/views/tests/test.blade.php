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
            <a href="{{ route('test.index') }}">Examens</a>
          </li>
        </ul>
      </div>

      <div class="row">
        <div class="col-md-12">
          <div class="card">
            <div class="card-header">
              <div class="d-flex align-items-center">
                <h4 class="card-title">Liste des examens</h4>
                @can('test.create')
                    <button
                    class="btn btn-primary btn-round ms-auto"
                    data-bs-toggle="modal"
                    data-bs-target="#addRowModal"
                    >
                    <i class="fa fa-plus"></i> Ajouter un examen
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
					        <th>Type</th>
					        <td>Montant</td>
                            <th style="width: 10%">Actions</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th>ID</th>
				            <th>Nom</th>
					        <th>Type</th>
					        <td>Montant</td>
				            <th>Actions</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        @foreach($tests as $test)
                            <tr>
                                <td>{{ $test->id}}</td>
                                <td>{{ $test->name}}</td>
                                <td>{{ ucfirst($test->report_type) }}</td>
                                <td>{{ number_format($test->amount)}}</td>
                                <td>
                                    <div class="form-button-action">
                                        @can('test.edit')
                                            <button
                                                type="button"
                                                class="btn btn-warning btn-round btn-sm edit-button"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editRowModal"
                                                data-info="{{$test}}"
                                            >
                                                <i class="fa fa-edit"></i>
                                            </button>
                                        @endcan

                                        @can('test.delete')
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
                                    <span class="fw-light"> Examen</span>
                                </h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <p class="small">Créez un examen en remplissant le formulaire ci-dessous.</p>
                                <form id="addTestForm" action="{{ route('test.store') }}" method="POST">
                                    @csrf
                                    <div class="row">

                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label>Nom de l'examen</label>
                                                <input name="name" type="text" class="form-control" placeholder="Entrez le nom" required />
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label>Montant</label>
                                                <div class="input-group">
                                                    <input type="number" name="amount" class="form-control" placeholder="Montant" required>
                                                    <span class="input-group-text">GNF</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label>Type d'examen</label>
                                                <select class="form-select" name="report_type">
                                                    <option value="BIOCHIMIE">BIOCHIMIE</option>
                                                    <option value="IMMUNOLOGIE">IMMUNOLOGIE</option>
                                                    <option value="INFECTOLOGIE">INFECTOLOGIE</option>
                                                    <option value="HEMATOLOGIE">HEMATOLOGIE</option>
                                                    <option value="HEMOSTASE">HEMOSTASE</option>
                                                    <option value="BACTERIOLOGIE">BACTERIOLOGIE</option>
                                                    <option value="PARASITOLOGIE">PARASITOLOGIE</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label>Description</label>
                                                <textarea name="description" class="form-control" placeholder="Entrez une description"></textarea>
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
                                <h5 class="modal-title">Modifier l'examen</h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Fermer">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form id='editTestForm' action="{{ route('test.edit') }}" method="POST">
                                    @csrf
                                    @method('POST')
                                    <div class="row">
                                        <input type="hidden" id="edit_id" name="edit_id">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label>Nom de l'examen</label>
                                                <input id="edit_name" name="name" type="text" class="form-control" placeholder="Entrez le nom" required />
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label>Montant</label>
                                                <div class="input-group">
                                                    <input type="text" id="edit_amount" name="amount" class="form-control" placeholder="Montant">
                                                    <span class="input-group-text">GNF</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label>Type d'examen</label>
                                                <select class="form-control" name="report_type" id="edit_report_type">
                                                    <option value="BIOCHIMIE">BIOCHIMIE</option>
                                                    <option value="IMMUNOLOGIE">IMMUNOLOGIE</option>
                                                    <option value="INFECTOLOGIE">INFECTOLOGIE</option>
                                                    <option value="HEMATOLOGIE">HEMATOLOGIE</option>
                                                    <option value="HEMOSTASE">HEMOSTASE</option>
                                                    <option value="BACTERIOLOGIE">BACTERIOLOGIE</option>
                                                    <option value="PARASITOLOGIE">PARASITOLOGIE</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label>Description</label>
                                                <textarea name="description" id="edit_description" class="form-control" placeholder="Entrez une description"></textarea>
                                            </div>
                                        </div>
                                    </div>
                            </div>
                            <div class="modal-footer border-0">
                                <!-- Bouton pour la modification -->
                                <button type="submit" class="btn btn-success" id="editRowButton" form="editTestForm">
                                    Enregistrer les modifications
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
                                <form id="deleteTestForm" action="{{ route('test.delete') }}" method="POST">
                                    @csrf
                                    @method('DELETE')
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
