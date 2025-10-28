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
            <a href="#">disponibilités</a>
          </li>
        </ul>
      </div>

      @if($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
      @endif

      @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
      @endif

      <div class="row">
        <div class="col-md-12">
          <div class="card">
            <div class="card-header">
              <div class="d-flex align-items-center">
                <h4 class="card-title">Liste des disponibilités</h4>
                @can('medecin.availabilities')
                    <button
                    class="btn btn-primary btn-round ms-auto"
                    data-bs-toggle="modal"
                    data-bs-target="#addRowModal"
                    >
                    <i class="fa fa-plus"></i> Ajouter une disponibilité
                    </button>
                @endcan
              </div>
            </div>

            <div class="card-body">
                <div class="bg-white rounded-lg shadow-md">
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach ($availabilities as $item)
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex justify-between items-start mb-2">
                                        <h4 class="font-medium text-gray-900">{{ ucfirst($item['day_of_week']) }}</h4>
                                        <div class="flex space-x-2">
                                            @can('medecin.availabilities')
                                                <button class="text-blue-600 hover:text-blue-800 edit-button"
                                                        data-id="{{ $item['id'] }}"
                                                        data-day="{{ $item['day_of_week'] }}"
                                                        data-start="{{ $item['start_time'] }}"
                                                        data-end="{{ $item['end_time'] }}"
                                                        data-duration="{{ $item['slot_duration'] }}"
                                                        data-active="{{ $item['is_active'] }}">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="text-red-600 hover:text-red-800 delete-button"
                                                        data-id="{{ $item['id'] }}"
                                                        data-name="{{ ucfirst($item['day_of_week']) }}">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            @endcan
                                        </div>
                                    </div>
                                    <p class="text-sm text-gray-600 mb-2">
                                        <span>{{ $item['start_time']->format('H:i') }}</span> - <span>{{ $item['end_time']->format('H:i') }}</span>
                                    </p>
                                    <p class="text-sm text-gray-600 mb-2">
                                        Durée : <span>{{ $item['slot_duration'] }}</span> min
                                    </p>
                                    <span class="px-2 py-1 rounded-full text-xs {{ $item['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $item['is_active'] ? 'Actif' : 'Inactif' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <!-- Modal Add -->
                <div class="modal fade" id="addRowModal" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header border-0">
                                <h5 class="modal-title">
                                    <span class="fw-mediumbold"> Nouvelle</span>
                                    <span class="fw-light"> Disponibilité</span>
                                </h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <form action="{{ route('medecin.availabilities.store') }}" method="POST" id="addAvailabilityForm">
                                @csrf
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label>Jour de la semaine</label>
                                        <select name="day_of_week" class="form-control" required>
                                            <option value="">Sélectionner un jour</option>
                                            <option value="Lundi">Lundi</option>
                                            <option value="Mardi">Mardi</option>
                                            <option value="Mercredi">Mercredi</option>
                                            <option value="Jeudi">Jeudi</option>
                                            <option value="Vendredi">Vendredi</option>
                                            <option value="Samedi">Samedi</option>
                                            <option value="Dimanche">Dimanche</option>
                                        </select>
                                    </div>

                                    <div class="row">
                                        <div class="form-group col-md-6">
                                            <label>Heure de début</label>
                                            <input type="time" name="start_time" class="form-control" required>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Heure de fin</label>
                                            <input type="time" name="end_time" class="form-control" required>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label>Durée des créneaux (minutes)</label>
                                        <select name="slot_duration" class="form-control">
                                            <option value="10">10 minutes</option>
                                            <option value="15">15 minutes</option>
                                            <option value="20">20 minutes</option>
                                            <option value="30">30 minutes</option>
                                            <option value="45">45 minutes</option>
                                            <option value="60">1 heure</option>
                                            <option value="90">1h30 minutes</option>
                                            <option value="120">2 heures</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="modal-footer border-0">
                                    <button type="submit" id="addRowButton" class="btn btn-primary">
                                        Ajouter
                                        <div class="spinner-border spinner-border-sm text-light" role="status" id="addLoader" style="display: none;">
                                            <span class="sr-only">Loading...</span>
                                        </div>
                                    </button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Modal Edit Availability -->
                <div class="modal fade" id="editAvailabilityModal" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <form method="POST" action="{{ route('medecin.availabilities.update') }}" id="editAvailabilityForm">
                        @csrf
                        @method('PUT')
                        <div class="modal-content">
                            <div class="modal-header border-0">
                            <h5 class="modal-title">Modifier la disponibilité</h5>
                            <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            </div>

                            <div class="modal-body">
                                <input type="hidden" name="id" id="edit_availability_id">

                                <div class="form-group">
                                    <label>Jour de la semaine</label>
                                    <select name="day_of_week" id="edit_day_of_week" class="form-control" required>
                                    <option value="">Sélectionner un jour</option>
                                    <option value="Lundi">Lundi</option>
                                    <option value="Mardi">Mardi</option>
                                    <option value="Mercredi">Mercredi</option>
                                    <option value="Jeudi">Jeudi</option>
                                    <option value="Vendredi">Vendredi</option>
                                    <option value="Samedi">Samedi</option>
                                    <option value="Dimanche">Dimanche</option>
                                    </select>
                                </div>

                                <div class="row">
                                    <div class="form-group col-md-6">
                                    <label>Heure de début</label>
                                    <input type="time" name="start_time" id="edit_start_time" class="form-control" required>
                                    </div>
                                    <div class="form-group col-md-6">
                                    <label>Heure de fin</label>
                                    <input type="time" name="end_time" id="edit_end_time" class="form-control" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Durée des créneaux (minutes)</label>
                                    <select name="slot_duration" id="edit_slot_duration" class="form-control">
                                    <option value="10">10 minutes</option>
                                    <option value="15">15 minutes</option>
                                    <option value="20">20 minutes</option>
                                    <option value="30">30 minutes</option>
                                    <option value="45">45 minutes</option>
                                    <option value="60">1 heure</option>
                                    <option value="90">1h30 minutes</option>
                                    <option value="120">2 heures</option>
                                    </select>
                                </div>

                                <div class="form-check mt-3">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active" value="1">
                                    <label class="form-check-label" for="edit_is_active">
                                    Actif
                                    </label>
                                </div>
                            </div>

                            <div class="modal-footer border-0">
                            <button type="submit" class="btn btn-success">
                                Modifier
                                <div class="spinner-border spinner-border-sm text-light d-none" role="status" id="editAvailabilityLoader">
                                <span class="sr-only">Loading...</span>
                                </div>
                            </button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            </div>
                        </div>
                        </form>
                    </div>
                </div>

                <!-- Modal Delete Availability -->
                <div class="modal fade" id="deleteAvailabilityModal" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <form method="POST" action="" id="deleteAvailabilityForm">
                        @csrf
                        @method('DELETE')
                        <div class="modal-content">
                            <div class="modal-header border-0">
                            <h5 class="modal-title">Supprimer la disponibilité</h5>
                            <button type="button" class="close" data-bs-dismiss="modal" aria-label="Fermer">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            </div>

                            <div class="modal-body">
                            <input type="hidden" name="id" id="delete_availability_id">
                            <p id="availability_to_delete_text" class="text-danger font-weight-bold"></p>
                            </div>

                            <div class="modal-footer border-0">
                            <button type="submit" class="btn btn-danger">
                                Supprimer
                                <div class="spinner-border spinner-border-sm text-light d-none" role="status" id="deleteAvailabilityLoader">
                                <span class="sr-only">Loading...</span>
                                </div>
                            </button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            </div>
                        </div>
                        </form>
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
        // Remplir et afficher le modal de modification
        $(document).on('click', '.edit-button', function () {
            const id = $(this).data('id');
            const day = $(this).data('day');
            const start = $(this).data('start');
            const end = $(this).data('end');
            const duration = $(this).data('duration');
            const active = $(this).data('active');

            $('#edit_availability_id').val(id);
            $('#edit_day_of_week').val(day);
            $('#edit_start_time').val(start);
            $('#edit_end_time').val(end);
            $('#edit_slot_duration').val(duration);
            $('#edit_is_active').prop('checked', active == 1);

            $('#editAvailabilityLoader').addClass('d-none'); // Réinitialiser le loader
            $('#editAvailabilityModal').modal('show');
        });

        // Loader lors de la soumission
        $('#editAvailabilityForm').on('submit', function () {
            $('#editAvailabilityLoader').removeClass('d-none');
        });


        // Préparer la suppression
        $(document).on('click', '.delete-button', function () {
            const id = $(this).data('id');
            const day = $(this).data('name');

            $('#delete_availability_id').val(id);
            $('#availability_to_delete_text').text(`Voulez-vous vraiment supprimer la disponibilité du ${day} ?`);
            $('#deleteAvailabilityForm').attr('action', `/medecin/availabilities/${id}`);
            $('#deleteAvailabilityModal').modal('show');
        });

        // Loader lors de la soumission
        $('#deleteAvailabilityForm').on('submit', function () {
            $('#deleteAvailabilityLoader').removeClass('d-none');
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
