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
            <a href="{{ route('hospitalisations.index') }}">hospitalisations</a>
          </li>
        </ul>
      </div>

      <div class="row">
        <div class="col-md-12">
          <div class="card">
            <div class="card-header">
              <div class="d-flex align-items-center">
                <h4 class="card-title">Liste des hospitalisations</h4>
                <button
                  class="btn btn-primary btn-round ms-auto"
                  data-bs-toggle="modal"
                  data-bs-target="#addRowModal"
                >
                  <i class="fa fa-plus"></i> Nouvelle hospitalisation
                </button>
              </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table id="add-row" class="display table table-striped table-hover">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th class="p-2">Patiente</th>
                            <th class="p-2">Chambre</th>
                            <th class="p-2">Entrée</th>
                            <th class="p-2">Sortie prévue</th>
                            <th class="p-2">Statut</th>
                            <th class="p-2">Actions</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th class="p-2">Patiente</th>
                            <th class="p-2">Chambre</th>
                            <th class="p-2">Entrée</th>
                            <th class="p-2">Sortie prévue</th>
                            <th class="p-2">Statut</th>
                            <th class="p-2">Actions</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        @foreach($hospitalisations as $hospitalisation)
                            <tr>
                                <td class="p-2">{{ $hospitalisation->patient->first_name." ".$hospitalisation->patient->last_name }}</td>
                                <td class="p-2">{{ $hospitalisation->chambre->numero }}</td>
                                <td class="p-2">{{ $hospitalisation->date_entree }}</td>
                                <td class="p-2">{{ $hospitalisation->date_sortie_prevue }}</td>
                                <td class="p-2">
                                    <span class="text-xs px-2 py-1 rounded
                                        {{ $hospitalisation->statut === 'En cours' ? 'bg-yellow-100 text-yellow-800' :
                                        ($hospitalisation->statut === 'Terminé' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') }}">
                                        {{ $hospitalisation->statut }}
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
                                            data-info="{{$hospitalisation}}"
                                        >
                                            <i class="fa fa-edit"></i>
                                        </button>

                                        &nbsp;&nbsp;
                                        <a href="{{ route('hospitalisations.facture', $hospitalisation->id) }}"
                                            class="btn btn-info btn-round btn-sm" target="_blank">
                                            📄
                                         </a>
                                         &nbsp;&nbsp;
                                        <form action="{{ route('hospitalisations.destroy', $hospitalisation->id) }}" method="POST"
                                            onsubmit="return confirm('Supprimer cette hospitalisation ?')">
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
                                    <span class="fw-light"> hospitalisation</span>
                                </h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <p class="small">Ajouter une hospitalisation en remplissant le formulaire ci-dessous.</p>
                                <form id="addHospitalisationForm" action="{{ route('hospitalisations.store') }}" method="POST">
                                    @csrf
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <div class="form-group form-group-default">
                                                <label class="block font-medium">Patient</label>
                                                <select name="patient_id" class="form-control" required>
                                                    @foreach($patients as $patient)
                                                        <option value="{{ $patient->id }}">{{ $patient->first_name." ".$patient->last_name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label class="block font-medium">Chambre</label>
                                                <select name="chambre_id" class="form-control" required>
                                                    @foreach($chambres as $chambre)
                                                        <option value="{{ $chambre->id }}">{{ $chambre->numero }} - {{ $chambre->type }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label class="block font-medium">Date d’entrée</label>
                                                <input type="date" name="date_entree" class="form-control" required>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label class="block font-medium">Nombre de jours</label>
                                                <input type="number" name="nombre_jours" class="form-control" required>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label class="block font-medium">Observation</label>
                                                <textarea name="observation" class="form-control" ></textarea>
                                            </div>
                                        </div>

                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer border-0">
                                <button type="submit" id="addRowButton" class="btn btn-primary" form="addHospitalisationForm">
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
                                    <span class="fw-light"> hospitalisation</span>
                                </h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <form id='editHospitalisationForm' action="#" method="POST">
                                @csrf
                                @method('POST')

                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <div class="form-group form-group-default">
                                                <label class="block font-medium">Patient</label>
                                                <select name="patient_id" id="patient_id" class="form-control" required>
                                                    @foreach($patients as $patient)
                                                        <option value="{{ $patient->id }}">{{ $patient->first_name." ".$patient->last_name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label class="block font-medium">Chambre</label>
                                                <select name="chambre_id" id="chambre_id" class="form-control" required>
                                                    @foreach($chambres as $chambre)
                                                        <option value="{{ $chambre->id }}">{{ $chambre->numero }} - {{ $chambre->type }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label class="block font-medium">Date d’entrée</label>
                                                <input type="date" name="date_entree" id="date_entree" class="form-control" required>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label class="block font-medium">Nombre de jours</label>
                                                <input type="number" name="nombre_jours" id="nombre_jours" class="form-control" required>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label class="block font-medium">Observation</label>
                                                <textarea name="observation" id="observation" class="form-control" ></textarea>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                                <div class="modal-footer border-0">
                                    <!-- Bouton pour la modification -->
                                    <button type="submit" class="btn btn-success" id="editRowButton" form="editHospitalisationForm">
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
        // Événement pour modifier un hospitalisation
        $(document).on('click', '.edit-button', function() {
            var hospitalisation = $(this).data('info');
            // Mettre à jour le champ du modal
            $('#patient_id').val(hospitalisation.patient.first_name + " " + hospitalisation.patient.last_name);
            $('#chambre_id').val(hospitalisation.chambre.numero);
            $('#date_entree').val(hospitalisation.date_entree);
            $('#nombre_jours').val(hospitalisation.nombre_jours);
            $('#observation').val(hospitalisation.observation);

            // Afficher le modal
            $('#editRowModal').modal('show');
        });

        // Afficher le loader pour l'ajout de hospitalisation
        $('#addHospitalisationForm').on('submit', function() {
            $('#addRowButton').prop('disabled', true);  // Désactive le bouton pour éviter plusieurs clics
            $('#addLoader').show();  // Affiche le loader
        });

        // Afficher le loader pour la modification de hospitalisation
        $('#editHospitalisationForm').on('submit', function() {
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
