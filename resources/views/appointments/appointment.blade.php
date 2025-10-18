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
              <div class="d-flex align-items-center justify-content-between">
                <h4 class="card-title">Liste des rendez-vous</h4>
                <div class="badge bg-primary">
                  <span id="totalAppointments">{{ count($appointments) }}</span> rendez-vous
                </div>
              </div>
            </div>

            <div class="card-body">
                {{-- Barre de recherche --}}
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="input-group">
                            <span class="input-group-text bg-light">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" 
                                   id="searchInput" 
                                   class="form-control" 
                                   placeholder="Rechercher par nom de patient, date, statut...">
                            <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <small class="text-muted">
                            Tapez le nom du patient pour vérifier s'il a un rendez-vous
                        </small>
                    </div>
                    <div class="col-md-4">
                        <select id="statusFilter" class="form-select">
                            <option value="">Tous les statuts</option>
                            <option value="pending">En attente</option>
                            <option value="confirmed">Confirmé</option>
                            <option value="completed">Terminé</option>
                        </select>
                    </div>
                </div>

                {{-- Message si aucun résultat --}}
                <div id="noResults" class="alert alert-info" style="display: none;">
                    <i class="fas fa-info-circle"></i> Aucun rendez-vous trouvé pour votre recherche.
                </div>

                {{-- Liste des rendez-vous --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-4" id="appointmentsList">
                    @foreach ($appointments as $item)
                        <div class="appointment-card bg-white rounded-lg shadow-md p-6" 
                             data-patient="{{ strtolower($item['patient']['first_name'] ?? '') }} {{ strtolower($item['patient']['last_name'] ?? '') }}"
                             data-date="{{ \Carbon\Carbon::parse($item['appointment_date'])->format('Y-m-d') }}"
                             data-status="{{ $item['status'] }}"
                             data-notes="{{ strtolower($item['notes'] ?? '') }}">
                            <div class="flex justify-between items-start">
                                <div class="flex items-center space-x-4">
                                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-user text-blue-600"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-medium text-gray-900">
                                            {{ $item['patient']['first_name'] ?? 'Nom' }} {{ $item['patient']['last_name'] ?? 'inconnu' }}
                                        </h4>
                                        <p class="text-sm text-gray-600">
                                            <i class="far fa-calendar me-1"></i>
                                            {{ \Carbon\Carbon::parse($item['appointment_date'])->locale('fr')->isoFormat('dddd D MMM YYYY') }} 
                                            à {{ $item['appointment_time']->format('H:i') }}
                                        </p>
                                        @if ($item['notes'])
                                            <p class="text-sm text-gray-600 mt-1">
                                                <i class="far fa-comment-dots me-1"></i>
                                                {{ $item['notes'] }}
                                            </p>
                                        @endif
                                        @if(isset($item['patient']['phone']))
                                            <p class="text-sm text-gray-500 mt-1">
                                                <i class="fas fa-phone me-1"></i>
                                                {{ $item['patient']['phone'] }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="px-3 py-1 rounded-full text-xs font-medium
                                        {{ $item['status'] === 'pending' ? 'bg-yellow-200 text-yellow-800' :
                                        ($item['status'] === 'confirmed' ? 'bg-blue-200 text-blue-800' :
                                        ($item['status'] === 'completed' ? 'bg-green-200 text-green-800' : '')) }}">
                                        @if($item['status'] === 'pending')
                                            En attente
                                        @elseif($item['status'] === 'confirmed')
                                            Confirmé
                                        @elseif($item['status'] === 'completed')
                                            Terminé
                                        @endif
                                    </span>

                                    @if ($item['status'] === 'pending')
                                        <form method="POST" action="{{ route('medecin.appointments.confirm', $item['id']) }}">
                                            @csrf
                                            <button type="submit" class="px-3 py-1 bg-green-600 text-white rounded-md text-sm hover:bg-green-700">
                                                <i class="fas fa-check me-1"></i> Confirmer
                                            </button>
                                        </form>
                                    @elseif ($item['status'] === 'confirmed')
                                        <form method="POST" action="{{ route('medecin.appointments.complete', $item['id']) }}">
                                            @csrf
                                            <button type="submit" class="px-3 py-1 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700">
                                                <i class="fas fa-check-double me-1"></i> Terminer
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
        $(document).ready(function() {
            // Fonction de recherche et filtrage
            function filterAppointments() {
                const searchText = $('#searchInput').val().toLowerCase();
                const statusFilter = $('#statusFilter').val();
                let visibleCount = 0;

                $('.appointment-card').each(function() {
                    const card = $(this);
                    const patientName = card.data('patient');
                    const appointmentDate = card.data('date');
                    const status = card.data('status');
                    const notes = card.data('notes');

                    // Vérifier si le texte de recherche correspond
                    const matchesSearch = searchText === '' || 
                                        patientName.includes(searchText) ||
                                        appointmentDate.includes(searchText) ||
                                        notes.includes(searchText);

                    // Vérifier si le statut correspond
                    const matchesStatus = statusFilter === '' || status === statusFilter;

                    // Afficher ou masquer la carte
                    if (matchesSearch && matchesStatus) {
                        card.show();
                        visibleCount++;
                    } else {
                        card.hide();
                    }
                });

                // Afficher le message si aucun résultat
                if (visibleCount === 0) {
                    $('#noResults').show();
                } else {
                    $('#noResults').hide();
                }

                // Mettre à jour le compteur
                $('#totalAppointments').text(visibleCount);
            }

            // Événement de recherche en temps réel
            $('#searchInput').on('keyup', function() {
                filterAppointments();
            });

            // Événement de filtrage par statut
            $('#statusFilter').on('change', function() {
                filterAppointments();
            });

            // Bouton pour effacer la recherche
            $('#clearSearch').on('click', function() {
                $('#searchInput').val('');
                $('#statusFilter').val('');
                filterAppointments();
            });

            // Highlight du texte recherché (optionnel)
            $('#searchInput').on('keyup', function() {
                const searchText = $(this).val();
                if (searchText.length > 2) {
                    $('.appointment-card:visible h4').each(function() {
                        const text = $(this).text();
                        const regex = new RegExp(`(${searchText})`, 'gi');
                        const highlightedText = text.replace(regex, '<mark>$1</mark>');
                        $(this).html(highlightedText);
                    });
                } else {
                    $('.appointment-card h4').each(function() {
                        $(this).text($(this).text());
                    });
                }
            });
        });

        // Événement pour modifier un département
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

            $('#editAvailabilityLoader').addClass('d-none');
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

        // Afficher le loader pour l'ajout
        $('#addDepartmentForm').on('submit', function() {
            $('#addRowButton').prop('disabled', true);
            $('#addLoader').show();
        });

        // Afficher le loader pour la modification
        $('#editDepartmentForm').on('submit', function() {
            $('#editRowButton').prop('disabled', true);
            $('#editLoader').show();
        });

        // Afficher le loader pour la suppression
        $('#deleteDepartmentForm').on('submit', function() {
            $('#deleteRowButton').prop('disabled', true);
            $('#deleteLoader').show();
        });

        // Lorsque la requête est terminée
        $(document).ajaxComplete(function() {
            $('#addRowButton').prop('disabled', false);
            $('#addLoader').hide();

            $('#editRowButton').prop('disabled', false);
            $('#editLoader').hide();

            $('#deleteRowButton').prop('disabled', false);
            $('#deleteLoader').hide();
        });
    </script>
@endsection