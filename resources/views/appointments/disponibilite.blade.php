@extends('layouts.backend')

@section('content')
<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <ul class="breadcrumbs">
                <li class="nav-home">
                    <a href="{{ url('/') }}">
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
                    <a href="#">Disponibilités</a>
                </li>
            </ul>
        </div>

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
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

        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <h4 class="card-title mb-0">Liste des disponibilités</h4>

                            @can('medecin.availabilities')
                                <button
                                    type="button"
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
                            <div class="p-3 p-md-4">
                                <div class="row g-3">
                                    @forelse ($availabilities as $item)
                                        <div class="col-md-6 col-lg-4">
                                            <div class="border border-gray-200 rounded-lg p-4 h-100">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h4 class="font-medium text-gray-900 mb-0">
                                                        {{ ucfirst($item->day_of_week) }}
                                                    </h4>

                                                    @can('medecin.availabilities')
                                                        <div class="d-flex gap-2">
                                                            <button
                                                                type="button"
                                                                class="btn btn-link p-0 text-primary edit-button"
                                                                data-id="{{ $item->id }}"
                                                                data-day="{{ $item->day_of_week }}"
                                                                data-start="{{ $item->start_time->format('H:i') }}"
                                                                data-end="{{ $item->end_time->format('H:i') }}"
                                                                data-duration="{{ $item->slot_duration }}"
                                                                data-active="{{ $item->is_active ? 1 : 0 }}"
                                                                title="Modifier"
                                                            >
                                                                <i class="fas fa-edit"></i>
                                                            </button>

                                                            <button
                                                                type="button"
                                                                class="btn btn-link p-0 text-danger delete-button"
                                                                data-id="{{ $item->id }}"
                                                                data-name="{{ ucfirst($item->day_of_week) }}"
                                                                title="Supprimer"
                                                            >
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    @endcan
                                                </div>

                                                <p class="text-sm text-gray-600 mb-2">
                                                    <span>{{ $item->start_time->format('H:i') }}</span>
                                                    -
                                                    <span>{{ $item->end_time->format('H:i') }}</span>
                                                </p>

                                                <p class="text-sm text-gray-600 mb-2">
                                                    Durée : <span>{{ $item->slot_duration }}</span> min
                                                </p>

                                                <span class="px-2 py-1 rounded-full text-xs {{ $item->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                    {{ $item->is_active ? 'Actif' : 'Inactif' }}
                                                </span>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="col-12">
                                            <div class="alert alert-info mb-0">
                                                Aucune disponibilité enregistrée.
                                            </div>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <!-- Modal Add -->
                        <div class="modal fade" id="addRowModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header border-0">
                                        <h5 class="modal-title">
                                            <span class="fw-mediumbold">Nouvelle</span>
                                            <span class="fw-light"> disponibilité</span>
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
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
                                                <select name="slot_duration" class="form-control" required>
                                                    <option value="10">10 minutes</option>
                                                    <option value="15">15 minutes</option>
                                                    <option value="20">20 minutes</option>
                                                    <option value="30">30 minutes</option>
                                                    <option value="45">45 minutes</option>
                                                    <option value="60">1 heure</option>
                                                    <option value="90">1h30</option>
                                                    <option value="120">2 heures</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="modal-footer border-0">
                                            <button type="submit" id="addRowButton" class="btn btn-primary">
                                                Ajouter
                                                <span class="spinner-border spinner-border-sm text-light ms-1 d-none" role="status" id="addLoader">
                                                    <span class="visually-hidden">Loading...</span>
                                                </span>
                                            </button>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Edit -->
                        <div class="modal fade" id="editAvailabilityModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <form method="POST" action="" id="editAvailabilityForm">
                                    @csrf
                                    @method('PUT')

                                    <div class="modal-content">
                                        <div class="modal-header border-0">
                                            <h5 class="modal-title">Modifier la disponibilité</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
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
                                                <select name="slot_duration" id="edit_slot_duration" class="form-control" required>
                                                    <option value="10">10 minutes</option>
                                                    <option value="15">15 minutes</option>
                                                    <option value="20">20 minutes</option>
                                                    <option value="30">30 minutes</option>
                                                    <option value="45">45 minutes</option>
                                                    <option value="60">1 heure</option>
                                                    <option value="90">1h30</option>
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
                                            <button type="submit" id="editAvailabilityButton" class="btn btn-success">
                                                Modifier
                                                <span class="spinner-border spinner-border-sm text-light ms-1 d-none" role="status" id="editAvailabilityLoader">
                                                    <span class="visually-hidden">Loading...</span>
                                                </span>
                                            </button>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Modal Delete -->
                        <div class="modal fade" id="deleteAvailabilityModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <form method="POST" action="" id="deleteAvailabilityForm">
                                    @csrf
                                    @method('DELETE')

                                    <div class="modal-content">
                                        <div class="modal-header border-0">
                                            <h5 class="modal-title">Supprimer la disponibilité</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                        </div>

                                        <div class="modal-body">
                                            <input type="hidden" name="id" id="delete_availability_id">
                                            <p id="availability_to_delete_text" class="text-danger fw-bold mb-0"></p>
                                        </div>

                                        <div class="modal-footer border-0">
                                            <button type="submit" id="deleteAvailabilityButton" class="btn btn-danger">
                                                Supprimer
                                                <span class="spinner-border spinner-border-sm text-light ms-1 d-none" role="status" id="deleteAvailabilityLoader">
                                                    <span class="visually-hidden">Loading...</span>
                                                </span>
                                            </button>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <!-- End Modals -->

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
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
        $('#edit_is_active').prop('checked', Number(active) === 1);

        $('#editAvailabilityForm').attr('action', `/medecin/availabilities/${id}`);
        $('#editAvailabilityLoader').addClass('d-none');
        $('#editAvailabilityButton').prop('disabled', false);

        $('#editAvailabilityModal').modal('show');
    });

    $('#editAvailabilityForm').on('submit', function () {
        $('#editAvailabilityButton').prop('disabled', true);
        $('#editAvailabilityLoader').removeClass('d-none');
    });

    $(document).on('click', '.delete-button', function () {
        const id = $(this).data('id');
        const day = $(this).data('name');

        $('#delete_availability_id').val(id);
        $('#availability_to_delete_text').text(`Voulez-vous vraiment supprimer la disponibilité du ${day} ?`);
        $('#deleteAvailabilityForm').attr('action', `/medecin/availabilities/${id}`);
        $('#deleteAvailabilityLoader').addClass('d-none');
        $('#deleteAvailabilityButton').prop('disabled', false);

        $('#deleteAvailabilityModal').modal('show');
    });

    $('#deleteAvailabilityForm').on('submit', function () {
        $('#deleteAvailabilityButton').prop('disabled', true);
        $('#deleteAvailabilityLoader').removeClass('d-none');
    });

    $('#addAvailabilityForm').on('submit', function () {
        $('#addRowButton').prop('disabled', true);
        $('#addLoader').removeClass('d-none');
    });
</script>
@endsection