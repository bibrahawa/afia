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
            <a href="{{ route('chambres.index') }}">chambres</a>
          </li>
        </ul>
      </div>

      <div class="row">
        <div class="col-md-12">
          <div class="card">
            <div class="card-header">
              <div class="d-flex align-items-center">
                <h4 class="card-title">Liste des chambres</h4>
                <button
                  class="btn btn-primary btn-round ms-auto"
                  data-bs-toggle="modal"
                  data-bs-target="#addRowModal"
                >
                  <i class="fa fa-plus"></i> Ajouter une chambre
                </button>
              </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table id="add-row" class="display table table-striped table-hover">
                    <thead class="bg-primary text-white"> <!-- Ajout de couleur d'entête -->
                        <tr>
                            <th class="px-4 py-2">Numéro</th>
                            <th class="px-4 py-2">Type</th>
                            <th class="px-4 py-2">Prix / jour</th>
                            <th class="px-4 py-2">Statut</th>
                            <th class="px-4 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th class="px-4 py-2">Numéro</th>
                            <th class="px-4 py-2">Type</th>
                            <th class="px-4 py-2">Prix / jour</th>
                            <th class="px-4 py-2">Statut</th>
                            <th class="px-4 py-2">Actions</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        @foreach($chambres as $chambre)
                            <tr>
                                <td class="px-4 py-2">{{ $chambre->numero }}</td>
                                <td class="px-4 py-2">{{ $chambre->type }}</td>
                                <td class="px-4 py-2">{{ number_format($chambre->prix_par_jour, 2) }} FCFA</td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-1 rounded text-sm
                                        {{ $chambre->statut === 'Libre' ? 'bg-green-200 text-green-800' :
                                        ($chambre->statut === 'Occupée' ? 'bg-red-200 text-red-800' : 'bg-yellow-200 text-yellow-800') }}">
                                        {{ $chambre->statut }}
                                    </span>
                                </td>
                                <td>
                                    <div class="form-button-action">
                                        <!-- Modifier : Ajout des data-bs-toggle et data-bs-target -->
                                        <button
                                            type="button"
                                            class="btn btn-warning btn-round btn-sm edit-button"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editRowModal"
                                            data-info="{{$chambre}}"
                                        >
                                            <i class="fa fa-edit"></i>
                                        </button>

                                        <form action="{{ route('chambres.destroy', $chambre->id) }}" method="POST"
                                            onsubmit="return confirm('Supprimer cette chambre ?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-danger btn-round btn-sm delete-button">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form>

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
                                    <span class="fw-light"> chambre</span>
                                </h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <p class="small">Ajouter une chambre en remplissant le formulaire ci-dessous.</p>
                                <form id="addChambreForm" action="{{ route('chambres.store') }}" method="POST">
                                    @csrf
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <div class="form-group form-group-default">
                                                <label>Numéro :</label>
                                                <input type="text" name="numero" class="form-control" placeholder="Ex: 101, 102 .." required/>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label>Type :</label>
                                                <select name="type" class="form-control" required>
                                                    <option value="Standard">Standard</option>
                                                    <option value="VIP">VIP</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label>Prix par jour :</label>
                                                <div class="input-group">
                                                    <input type="text" name="prix_par_jour" class="form-control" placeholder="prix_par_jour">
                                                    <span class="input-group-text">GNF</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label>Statut :</label>
                                                <select name="statut" class="form-control" required>
                                                    <option value="Libre">Libre</option>
                                                    <option value="Occupée">Occupée</option>
                                                    <option value="En maintenance">En maintenance</option>
                                                </select>
                                            </div>
                                        </div>

                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer border-0">
                                <button type="submit" id="addRowButton" class="btn btn-primary" form="addChambreForm">
                                    Enregistrer
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
                                    <span class="fw-light"> chambre</span>
                                </h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <form id='editChambreForm' action="{{ route('chambre.update') }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="id" id="edit_id">
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <div class="form-group form-group-default">
                                                <label class="form-label required">Numéro :</label>
                                                <input type="text" name="numero" id="numero" class="form-control" placeholder="Ex: 101, 102 .." required/>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label>Type :</label>
                                                <select name="type" id="type" class="form-control" required>
                                                    <option value="Standard">Standard</option>
                                                    <option value="VIP">VIP</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label>Prix par jour :</label>
                                                <div class="input-group">
                                                    <input type="text" name="prix_par_jour" id="prix_par_jour" class="form-control" placeholder="prix_par_jour">
                                                    <span class="input-group-text">GNF</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label>Statut :</label>
                                                <select name="statut" class="form-control" required>
                                                    <option value="Libre">Libre</option>
                                                    <option value="Occupée">Occupée</option>
                                                    <option value="En maintenance">En maintenance</option>
                                                </select>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                                <div class="modal-footer border-0">
                                    <!-- Bouton pour la modification -->
                                    <button type="submit" class="btn btn-success" id="editRowButton" form="editChambreForm">
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
            </div>
        </div>
      </div>
    </div>
</div>

@endsection

@section('script')
    <script type="text/javascript">
        // Événement pour modifier un chambre
        $(document).on('click', '.edit-button', function() {
            var chambre = $(this).data('info');
            // Mettre à jour le champ du modal
            $('#numero').val(chambre.numero);
            $('#type').val(chambre.type);
            $('#status').val(chambre.status);
            $('#prix_par_jour').val(chambre.prix_par_jour);
            $('#edit_id').val(chambre.id);

            // Afficher le modal
            $('#editRowModal').modal('show');
        });

        // Afficher le loader pour l'ajout de chambre
        $('#addChambreForm').on('submit', function() {
            $('#addRowButton').prop('disabled', true);  // Désactive le bouton pour éviter plusieurs clics
            $('#addLoader').show();  // Affiche le loader
        });

        // Afficher le loader pour la modification de chambre
        $('#editChambreForm').on('submit', function() {
            $('#editRowButton').prop('disabled', true);  // Désactive le bouton pour éviter plusieurs clics
            $('#editLoader').show();  // Affiche le loader
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
