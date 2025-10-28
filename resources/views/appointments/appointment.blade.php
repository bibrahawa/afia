@extends('layouts.backend')

@section('style')
    <style>
        .spinner-border {
            width: 3rem;
            height: 3rem;
            border-width: 0.3em;
        }
        
        #appointmentsList {
            min-height: 200px;
            transition: opacity 0.3s ease;
        }
        
        .appointment-card {
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        /* ============================================
   FILTRES DE DATE AVEC COMPTEURS - ICÔNES RÉDUITES
   ============================================ */
.date-filters-container {
    background: white;
    padding: 1.5rem;
    border-radius: 0.75rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.date-filters {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
}

@media (max-width: 768px) {
    .date-filters {
        grid-template-columns: repeat(2, 1fr);
    }
}

.date-filter-btn {
    position: relative;
    padding: 0.875rem; /* Réduit de 1rem à 0.875rem */
    border: 2px solid #e5e7eb;
    border-radius: 0.75rem;
    background: white;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
    display: flex;
    flex-direction: column;
    gap: 0.35rem; /* Réduit de 0.5rem à 0.35rem */
    align-items: center;
}

.date-filter-btn:hover {
    border-color: #3b82f6;
    background: #eff6ff;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
}

.date-filter-btn.active {
    border-color: #3b82f6;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.date-filter-icon {
    font-size: 1.125rem; /* Réduit de 1.5rem à 1.125rem */
    margin-bottom: 0.15rem; /* Réduit de 0.25rem à 0.15rem */
    line-height: 1;
}

.date-filter-btn.active .date-filter-icon {
    color: white;
}

.date-filter-label {
    font-size: 0.8125rem; /* Réduit de 0.875rem à 0.8125rem */
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.15rem; /* Réduit de 0.25rem à 0.15rem */
    line-height: 1.2;
}

.date-filter-btn.active .date-filter-label {
    color: white;
}

.date-filter-date {
    font-size: 0.7rem; /* Réduit de 0.75rem à 0.7rem */
    color: #6b7280;
    font-weight: 500;
    line-height: 1.2;
}

.date-filter-btn.active .date-filter-date {
    color: rgba(255, 255, 255, 0.9);
}

.date-filter-count {
    position: absolute;
    top: -8px;
    right: -8px;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    width: 26px; /* Réduit de 28px à 26px */
    height: 26px; /* Réduit de 28px à 26px */
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem; /* Réduit de 0.75rem à 0.7rem */
    font-weight: 700;
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
    border: 2px solid white;
}

.date-filter-btn.active .date-filter-count {
    background: white;
    color: #3b82f6;
}

/* Badge "Tous" */
.badge-all {
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
}

.date-filter-btn.active.badge-all {
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
}

/* Section title */
.filter-section-title {
    font-size: 0.95rem; /* Réduit de 1rem à 0.95rem */
    font-weight: 600;
    color: #374151;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.filter-section-title i {
    color: #3b82f6;
    font-size: 0.9rem;
}

/* Version encore plus compacte pour mobile */
@media (max-width: 576px) {
    .date-filter-btn {
        padding: 0.75rem;
        gap: 0.25rem;
    }
    
    .date-filter-icon {
        font-size: 1rem;
    }
    
    .date-filter-label {
        font-size: 0.75rem;
    }
    
    .date-filter-date {
        font-size: 0.65rem;
    }
    
    .date-filter-count {
        width: 24px;
        height: 24px;
        font-size: 0.65rem;
        top: -6px;
        right: -6px;
    }
}
    </style>
@endsection

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
                    <a href="#">Appointments</a>
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
                    {{-- Filtres de date --}}
                    <div class="date-filters-container">
                        <h5 class="filter-section-title">
                            <i class="fas fa-calendar-alt"></i>
                            Filtrer par date
                        </h5>
                        <div class="date-filters">
                            {{-- Tous --}}
                            <button class="date-filter-btn active badge-all" data-filter="">
                                <div class="date-filter-icon">📅</div>
                                <div class="date-filter-label">Tous</div>
                                <div class="date-filter-date">Tous les rendez-vous</div>
                                @if(isset($stats['total']) && $stats['total'] > 0)
                                    <span class="date-filter-count">{{ $stats['total'] }}</span>
                                @endif
                            </button>

                            {{-- Aujourd'hui --}}
                            <button class="date-filter-btn" data-filter="today">
                                <div class="date-filter-icon">☀️</div>
                                <div class="date-filter-label">Aujourd'hui</div>
                                <div class="date-filter-date">{{ \Carbon\Carbon::today()->locale('fr')->isoFormat('DD MMM') }}</div>
                                @if(isset($stats['today']) && $stats['today'] > 0)
                                    <span class="date-filter-count">{{ $stats['today'] }}</span>
                                @endif
                            </button>

                            {{-- Demain --}}
                            <button class="date-filter-btn" data-filter="tomorrow">
                                <div class="date-filter-icon">🌤️</div>
                                <div class="date-filter-label">Demain</div>
                                <div class="date-filter-date">{{ \Carbon\Carbon::tomorrow()->locale('fr')->isoFormat('DD MMM') }}</div>
                                @if(isset($stats['tomorrow']) && $stats['tomorrow'] > 0)
                                    <span class="date-filter-count">{{ $stats['tomorrow'] }}</span>
                                @endif
                            </button>

                            {{-- Après-demain --}}
                            <button class="date-filter-btn" data-filter="day_after_tomorrow">
                                <div class="date-filter-icon">⛅</div>
                                <div class="date-filter-label">Après-demain</div>
                                <div class="date-filter-date">{{ \Carbon\Carbon::today()->addDays(2)->locale('fr')->isoFormat('DD MMM') }}</div>
                                @if(isset($stats['day_after_tomorrow']) && $stats['day_after_tomorrow'] > 0)
                                    <span class="date-filter-count">{{ $stats['day_after_tomorrow'] }}</span>
                                @endif
                            </button>

                            {{-- Cette semaine --}}
                            <button class="date-filter-btn" data-filter="this_week">
                                <div class="date-filter-icon">📆</div>
                                <div class="date-filter-label">Cette semaine</div>
                                <div class="date-filter-date">
                                    {{ \Carbon\Carbon::now()->startOfWeek()->locale('fr')->isoFormat('DD') }} - 
                                    {{ \Carbon\Carbon::now()->endOfWeek()->locale('fr')->isoFormat('DD MMM') }}
                                </div>
                                @if(isset($stats['this_week']) && $stats['this_week'] > 0)
                                    <span class="date-filter-count">{{ $stats['this_week'] }}</span>
                                @endif
                            </button>

                            {{-- Semaine prochaine --}}
                            <button class="date-filter-btn" data-filter="next_week">
                                <div class="date-filter-icon">📅</div>
                                <div class="date-filter-label">Semaine prochaine</div>
                                <div class="date-filter-date">
                                    {{ \Carbon\Carbon::now()->addWeek()->startOfWeek()->locale('fr')->isoFormat('DD') }} - 
                                    {{ \Carbon\Carbon::now()->addWeek()->endOfWeek()->locale('fr')->isoFormat('DD MMM') }}
                                </div>
                                @if(isset($stats['next_week']) && $stats['next_week'] > 0)
                                    <span class="date-filter-count">{{ $stats['next_week'] }}</span>
                                @endif
                            </button>

                            {{-- Ce mois --}}
                            <button class="date-filter-btn" data-filter="this_month">
                                <div class="date-filter-icon">🗓️</div>
                                <div class="date-filter-label">Ce mois</div>
                                <div class="date-filter-date">{{ \Carbon\Carbon::now()->locale('fr')->isoFormat('MMMM YYYY') }}</div>
                                @if(isset($stats['this_month']) && $stats['this_month'] > 0)
                                    <span class="date-filter-count">{{ $stats['this_month'] }}</span>
                                @endif
                            </button>
                        </div>
                    </div>
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
                                            @can('medecin.confirm_appointment')
                                                <form method="POST" action="{{ route('medecin.appointments.confirm', $item['id']) }}">
                                                    @csrf
                                                    <button type="submit" class="px-3 py-1 bg-green-600 text-white rounded-md text-sm hover:bg-green-700">
                                                        <i class="fas fa-check me-1"></i> Confirmer
                                                    </button>
                                                </form>
                                            @endcan
                                        @elseif ($item['status'] === 'confirmed')
                                            @can('medecin.complete_appointment')
                                                <form method="POST" action="{{ route('medecin.appointments.complete', $item['id']) }}">
                                                    @csrf
                                                    <button type="submit" class="px-3 py-1 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700">
                                                        <i class="fas fa-check-double me-1"></i> Terminer
                                                    </button>
                                                </form>
                                            @endcan
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

    <script type="text/javascript">
        $(document).ready(function() {
            let searchTimeout;
            let currentDateFilter = '';
            let currentStatusFilter = '';
            let currentSearchText = '';
            
            // Fonction pour mettre à jour visuellement le filtre actif
            function updateActiveFilter() {
                // Retirer la classe active de tous les boutons
                $('.date-filter-btn').removeClass('active');
                
                // Ajouter la classe active au bon bouton selon currentDateFilter
                if (currentDateFilter === '') {
                    $('.date-filter-btn[data-filter=""]').addClass('active');
                } else {
                    $(`.date-filter-btn[data-filter="${currentDateFilter}"]`).addClass('active');
                }
            }
            
            // Fonction de recherche AJAX
            function performSearch(page = 1) {
                currentSearchText = $('#searchInput').val();
                currentStatusFilter = $('#statusFilter').val();
                
                // Afficher un loader
                $('#appointmentsList').html(`
                    <div class="col-span-full text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                        <p class="mt-2 text-muted">Recherche en cours...</p>
                    </div>
                `);
                
                // Requête AJAX
                $.ajax({
                    url: "{{ route('medecin.appointments') }}",
                    method: 'GET',
                    data: {
                        search: currentSearchText,
                        status: currentStatusFilter,
                        date_filter: currentDateFilter,
                        page: page
                    },
                    success: function(response) {
                        $('#appointmentsList').html(response);
                        
                        // IMPORTANT: Restaurer l'état visuel du filtre actif
                        updateActiveFilter();
                        
                        // Mettre à jour le compteur
                        const count = $('#appointmentsList .appointment-card').length;
                        $('#totalAppointments').text(count);
                        
                        // Afficher le message si aucun résultat
                        if (count === 0) {
                            $('#noResults').show();
                        } else {
                            $('#noResults').hide();
                        }
                    },
                    error: function(xhr) {
                        console.error('Erreur lors de la recherche:', xhr);
                        $('#appointmentsList').html(`
                            <div class="col-span-full">
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle"></i> 
                                    Une erreur est survenue lors de la recherche.
                                </div>
                            </div>
                        `);
                    }
                });
            }
            
            // Filtres de date
            $('.date-filter-btn').on('click', function() {
                // Récupérer le filtre
                currentDateFilter = $(this).data('filter');
                
                // Mettre à jour visuellement
                updateActiveFilter();
                
                // Effectuer la recherche
                performSearch(1);
            });
            
            // Recherche en temps réel avec délai (debounce)
            $('#searchInput').on('keyup', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    performSearch(1);
                }, 500);
            });
            
            // Filtrage par statut
            $('#statusFilter').on('change', function() {
                performSearch(1);
            });
            
            // Bouton pour effacer la recherche
            $('#clearSearch').on('click', function() {
                $('#searchInput').val('');
                $('#statusFilter').val('');
                currentDateFilter = '';
                currentStatusFilter = '';
                currentSearchText = '';
                
                // Remettre le filtre "Tous" comme actif
                updateActiveFilter();
                
                performSearch(1);
            });
            
            // Gérer les clics sur la pagination
            $(document).on('click', '.pagination a', function(e) {
                e.preventDefault();
                
                const url = $(this).attr('href');
                const urlParams = new URLSearchParams(url.split('?')[1]);
                const page = urlParams.get('page') || 1;
                
                // Afficher un loader
                $('#appointmentsList').html(`
                    <div class="col-span-full text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                    </div>
                `);
                
                // Effectuer la recherche avec tous les paramètres
                $.ajax({
                    url: "{{ route('medecin.appointments') }}",
                    method: 'GET',
                    data: {
                        search: currentSearchText,
                        status: currentStatusFilter,
                        date_filter: currentDateFilter,
                        page: page
                    },
                    success: function(response) {
                        $('#appointmentsList').html(response);
                        
                        // IMPORTANT: Restaurer l'état visuel du filtre actif
                        updateActiveFilter();
                        
                        // Faire défiler vers le haut
                        $('html, body').animate({
                            scrollTop: $('#appointmentsList').offset().top - 100
                        }, 300);
                    },
                    error: function(xhr) {
                        console.error('Erreur lors du chargement de la page:', xhr);
                        $('#appointmentsList').html(`
                            <div class="col-span-full">
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle"></i> 
                                    Erreur lors du chargement.
                                </div>
                            </div>
                        `);
                    }
                });
            });
            
            // Au chargement de la page, vérifier s'il y a un filtre dans l'URL
            const urlParams = new URLSearchParams(window.location.search);
            const initialDateFilter = urlParams.get('date_filter');
            const initialStatus = urlParams.get('status');
            const initialSearch = urlParams.get('search');
            
            if (initialDateFilter) {
                currentDateFilter = initialDateFilter;
                updateActiveFilter();
            }
            
            if (initialStatus) {
                currentStatusFilter = initialStatus;
                $('#statusFilter').val(initialStatus);
            }
            
            if (initialSearch) {
                currentSearchText = initialSearch;
                $('#searchInput').val(initialSearch);
            }
        });
    </script>

@endsection