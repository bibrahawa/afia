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
                <h4 class="card-title">Liste des rendez-vous</h4>
              </div>
            </div>

            <div class="card-body">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-4">
                    @foreach ($appointments as $item)
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <div class="flex justify-between items-start">
                                <div class="flex items-center space-x-4">
                                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-user text-blue-600"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-medium text-gray-900">{{ $item['patient']['first_name'] ?? 'Nom inconnu' }}</h4>
                                        <p class="text-sm text-gray-600">
                                            {{ \Carbon\Carbon::parse($item['appointment_date'])->locale('fr')->isoFormat('dddd D MMM') }} à {{ $item['appointment_time']->format('H:i') }}
                                        </p>
                                        @if ($item['notes'])
                                            <p class="text-sm text-gray-600 mt-1">{{ $item['notes'] }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="px-3 py-1 rounded-full text-xs font-medium
                                        {{ $item['status'] === 'pending' ? 'bg-yellow-200 text-yellow-800' :
                                        ($item['status'] === 'confirmed' ? 'bg-blue-200 text-blue-800' :
                                        ($item['status'] === 'completed' ? 'bg-green-200 text-green-800' : '')) }}">
                                        {{ ucfirst($item['status']) }}
                                    </span>

                                    @if ($item['status'] === 'pending')
                                        <form method="POST" action="{{ route('medecin.appointments.confirm', $item['id']) }}">
                                            @csrf
                                            <button type="submit" class="px-3 py-1 bg-green-600 text-white rounded-md text-sm hover:bg-green-700">
                                                Confirmer
                                            </button>
                                        </form>
                                    @elseif ($item['status'] === 'confirmed')
                                        <form method="POST" action="{{ route('medecin.appointments.complete', $item['id']) }}">
                                            @csrf
                                            <button type="submit" class="px-3 py-1 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700">
                                                Terminer
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
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
