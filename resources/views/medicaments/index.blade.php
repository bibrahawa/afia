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
            <a href="{{ route('medicaments.index') }}">medicaments</a>
          </li>
        </ul>
      </div>

      <div class="row">
        <div class="col-md-12">
          <div class="card">
            <div class="card-header">
              <div class="d-flex align-items-center">
                <h4 class="card-title">Liste des medicaments</h4>
                <button
                  class="btn btn-primary btn-round ms-auto"
                  data-bs-toggle="modal"
                  data-bs-target="#addRowModal"
                >
                  <i class="fa fa-plus"></i> Ajouter un medicament
                </button>
              </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table id="add-row" class="display table table-striped table-hover">
                    <thead class="bg-primary text-white"> <!-- Ajout de couleur d'entête -->
                        <tr>
                            <th>Nom</th>
                            <th>Forme</th>
                            <th>Dosage</th>
                            <th>Frequence</th>
                            <th>instructions</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th>Nom</th>
                            <th>Forme</th>
                            <th>Dosage</th>
                            <th>Frequence</th>
                            <th>instructions</th>
                            <th>Actions</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        @forelse ($medicaments as $medicament)
                        <tr>
                            <td>{{ $medicament->nom }}</td>
                            <td>{{ $medicament->forme }}</td>
                            <td>{{ $medicament->dosage }}</td>
                            <td>{{ $medicament->frequence }}</td>
                            {{-- <td>{{ $medicament->duree }}</td> --}}
                            <td>{{ $medicament->instructions }}</td>
                            <td>
                                <div class="form-button-action">
                                    <!-- Modifier : Ajout des data-bs-toggle et data-bs-target -->
                                    <button
                                        type="button"
                                        class="btn btn-warning btn-round btn-sm edit-button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editRowModal"
                                        data-info="{{$medicament}}"
                                    >
                                        <i class="fa fa-edit"></i>
                                    </button>

                                    <!-- Supprimer : Ajout des data-bs-toggle et data-bs-target -->
                                    <button
                                        type="button"
                                        class="btn btn-danger btn-round btn-sm delete-button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteRowModal"
                                        data-medicament = "{{$medicament}}"
                                    >
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">Aucune medicament enregistrée.</td>
                            </tr>
                        @endforelse
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
                                    <span class="fw-light"> medicament</span>
                                </h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <p class="small">Créez un nouveau medicament en remplissant le formulaire ci-dessous.</p>
                                <form id="addDepartmentForm" action="{{ route('medicaments.store') }}" method="POST">
                                    @csrf
                                    <div class="row">

                                        <div class="col-sm-12">
                                            <div class="form-group form-group-default">
                                                <label>Selectionnez une forme:</label>
                                                <select name="forme" class="form-control">
                                                    <option value="Comprime">Comprime</option>
                                                    <option value="Sirop">Sirop</option>
                                                    <option value="Injection">Injection</option>
                                                    <option value="Perfusion">Perfusion</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Nom:</label>
                                                <input type="text" name="nom" class="form-control" required>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Dosage:</label>
                                                <input type="text" name="dosage" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Frequence:</label>
                                                <input type="text" name="frequence" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Duree:</label>
                                                <input type="text" name="duree" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-sm-12">
                                            <div class="form-group form-group-default">
                                                <label>Instruction:</label>
                                                <textarea name="instructions" class="form-control"></textarea>
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
                                    <span class="fw-light"> medicament</span>
                                </h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form id='editMedicamentForm' action="" method="POST">
                                    @csrf
                                    @method('PUT')
                                    @csrf
                                    <div class="row">
                                        <input type="hidden" name="id" id="edit_id">
                                        <div class="col-sm-12">
                                            <div class="form-group form-group-default">
                                                <label>Selectionnez une forme:</label>
                                                <select name="forme" id="edit_forme" class="form-control">
                                                    <option value="Comprime">Comprime</option>
                                                    <option value="Sirop">Sirop</option>
                                                    <option value="Injection">Injection</option>
                                                    <option value="Perfusion">Perfusion</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Nom:</label>
                                                <input type="text" name="nom" id="edit_nom" class="form-control" required>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Dosage:</label>
                                                <input type="text" name="dosage" id="edit_dosage" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Frequence:</label>
                                                <input type="text" name="frequence" id="edit_frequence" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Duree:</label>
                                                <input type="text" name="duree" id="edit_duree" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-sm-12">
                                            <div class="form-group form-group-default">
                                                <label>Instruction:</label>
                                                <textarea name="instructions" id="edit_instructions" class="form-control"></textarea>
                                            </div>
                                        </div>

                                    </div>
                            </div>
                            <div class="modal-footer border-0">
                                <!-- Bouton pour la modification -->
                                <button type="submit" class="btn btn-success" id="editRowButton" form="editMedicamentForm">
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
                                <h5 class="modal-title">Êtes-vous sûr de vouloir supprimer ce medicament ?</h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <!-- Formulaire de suppression -->
                                <form id="deleteMedicamentForm" action="#" method="POST">
                                    @csrf
                                    @method('DELETE') <!-- Utiliser la méthode DELETE -->
                                    <p id="medicament_name_to_delete"></p>
                                    <input type="hidden" id="delete_id" name="id">
                                </form>
                            </div>
                            <div class="modal-footer border-0">
                                <!-- Bouton pour la suppression -->
                                <button type="submit" class="btn btn-danger" id="deleteRowButton" form="deleteMedicamentForm">
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
        // Événement pour modifier un medicament
        $(document).on('click', '.edit-button', function() {
            var medicament = $(this).data('info');
            // Mettre à jour le champ du modal
            $('#edit_id').val(medicament.id);
            $('#edit_forme').val(medicament.forme);
            $('#edit_nom').val(medicament.nom);
            $('#edit_frequence').val(medicament.frequence);
            $('#edit_instructions').val(medicament.instructions);
            $('#edit_duree').val(medicament.duree);
            $('#edit_dosage').val(medicament.dosage);

            $('#editMedicamentForm').attr('action', '/medicaments/' + medicament.id);

            // Afficher le modal
            $('#editRowModal').modal('show');
        });

        // Événement pour supprimer un medicament
        $(document).on('click', '.delete-button', function() {
            var id = $(this).data('medicament').id;
            var name = $(this).data('medicament').nom;

            // Afficher le nom du medicament à supprimer
            $('#medicament_name_to_delete').text("Voulez-vous vraiment supprimer le medicament : " + name + " ?");

            // Mettre à jour l'action du formulaire de suppression avec l'ID du medicament
            $('#delete_id').val(id);
            $('#deleteMedicamentForm').attr('action', '/medicaments/' + id);

            // Afficher le modal de confirmation
            $('#deleteRowModal').modal('show');
        });

        // Afficher le loader pour l'ajout de medicament
        $('#addDepartmentForm').on('submit', function() {
            $('#addRowButton').prop('disabled', true);  // Désactive le bouton pour éviter plusieurs clics
            $('#addLoader').show();  // Affiche le loader
        });

        // Afficher le loader pour la modification de medicament
        $('#editMedicamentForm').on('submit', function() {
            $('#editRowButton').prop('disabled', true);  // Désactive le bouton pour éviter plusieurs clics
            $('#editLoader').show();  // Affiche le loader
        });

        // Afficher le loader pour la suppression de medicament
        $('#deleteMedicamentForm').on('submit', function() {
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
