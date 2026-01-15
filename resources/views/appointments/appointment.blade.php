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
        
        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
            cursor: pointer;
        }
        
        /* Stats cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.25rem;
            border-radius: 0.75rem;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .stat-card.stat-today {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .stat-card.stat-tomorrow {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }

        .stat-card.stat-week {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }

        .stat-card.stat-month {
            background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);
        }

        .stat-card.stat-total {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            color: #333;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.8rem;
            opacity: 0.95;
            font-weight: 500;
        }
        
        /* Filtres de date avec compteurs */
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
            padding: 0.875rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.75rem;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
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
            font-size: 1.125rem;
            margin-bottom: 0.15rem;
            line-height: 1;
        }

        .date-filter-btn.active .date-filter-icon {
            color: white;
        }

        .date-filter-label {
            font-size: 0.8125rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.15rem;
            line-height: 1.2;
        }

        .date-filter-btn.active .date-filter-label {
            color: white;
        }

        .date-filter-date {
            font-size: 0.7rem;
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
            width: 26px;
            height: 26px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
            border: 2px solid white;
        }

        .date-filter-btn.active .date-filter-count {
            background: white;
            color: #3b82f6;
        }

        .badge-all {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        }

        .date-filter-btn.active.badge-all {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        }

        .filter-section-title {
            font-size: 0.95rem;
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

        /* Style pour l'export */
        .export-btn {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            padding: 0.625rem 1.25rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        }

        .export-btn:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
            color: white;
        }

        .export-btn i {
            margin-right: 0.5rem;
        }

        /* Amélioration du tableau */
        .table {
            border-collapse: separate;
            border-spacing: 0;
        }

        .table thead th {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            padding: 1rem;
            border: none;
        }

        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #e5e7eb;
        }

        .status-badge {
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-confirmed {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .status-completed {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-cancelled {
            background-color: #fee2e2;
            color: #991b1b;
        }

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

        /* Loading overlay pour l'export */
        .export-loading {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .export-loading.active {
            display: flex;
        }

        .export-loading-content {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        }

        /* Avatar amélioré */
        .avatar {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
        }

        .avatar-title {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* Boutons d'action améliorés */
        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(16, 185, 129, 0.3);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            border: none;
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(239, 68, 68, 0.3);
        }

        .btn-info {
            background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
            border: none;
            color: white;
        }

        .btn-info:hover {
            background: linear-gradient(135deg, #0891b2 0%, #0e7490 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(6, 182, 212, 0.3);
            color: white;
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
                        <a href="#">Rendez-vous</a>
                    </li>
                </ul>
            </div>

            {{-- Statistiques Cards --}}
            <div class="stats-grid">
                <div class="stat-card stat-today">
                    <div class="stat-number">{{ $stats['today'] ?? 0 }}</div>
                    <div class="stat-label">☀️ Aujourd'hui</div>
                </div>
                <div class="stat-card stat-tomorrow">
                    <div class="stat-number">{{ $stats['tomorrow'] ?? 0 }}</div>
                    <div class="stat-label">🌤️ Demain</div>
                </div>
                <div class="stat-card stat-week">
                    <div class="stat-number">{{ $stats['this_week'] ?? 0 }}</div>
                    <div class="stat-label">📆 Cette semaine</div>
                </div>
                <div class="stat-card stat-month">
                    <div class="stat-number">{{ $stats['this_month'] ?? 0 }}</div>
                    <div class="stat-label">🗓️ Ce mois</div>
                </div>
                <div class="stat-card stat-total">
                    <div class="stat-number">{{ $stats['total'] ?? 0 }}</div>
                    <div class="stat-label">📋 Total</div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div>
                                    <h4 class="card-title mb-1">📋 Liste des rendez-vous</h4>
                                    <div class="badge bg-primary">
                                        <span id="totalAppointments">{{ $appointments->total() }}</span> rendez-vous
                                    </div>
                                </div>
                                <button type="button" class="btn export-btn" id="exportPdfBtn">
                                    <i class="fas fa-file-pdf"></i>
                                    Exporter en PDF
                                </button>
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
                                            placeholder="Rechercher par nom de patient, téléphone, notes..."
                                            value="{{ request('search') }}">
                                        <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">
                                        🔍 Tapez le nom du patient pour vérifier s'il a un rendez-vous
                                    </small>
                                </div>
                                <div class="col-md-4">
                                    <select id="statusFilter" class="form-select">
                                        <option value="">Tous les statuts</option>
                                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>En attente</option>
                                        <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmé</option>
                                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Terminé</option>
                                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Annulé</option>
                                    </select>
                                </div>
                            </div>

                            {{-- Message si aucun résultat --}}
                            <div id="noResults" class="alert alert-info" style="display: none;">
                                <i class="fas fa-info-circle"></i> Aucun rendez-vous trouvé pour votre recherche.
                            </div>

                            {{-- Tableau des rendez-vous --}}
                            <div id="appointmentsList">
                                @include('appointments.partials.list')
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Loading overlay pour l'export --}}
    <div class="export-loading" id="exportLoading">
        <div class="export-loading-content">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Génération du PDF...</span>
            </div>
            <h5>Génération du PDF en cours...</h5>
            <p class="text-muted mb-0">Veuillez patienter</p>
        </div>
    </div>
@endsection

@section('script')
    <script type="text/javascript">
        $(document).ready(function() {
            let searchTimeout;
            let currentDateFilter = '{{ request("date_filter", "") }}';
            let currentStatusFilter = '{{ request("status", "") }}';
            let currentSearchText = '{{ request("search", "") }}';
            
            // Fonction pour mettre à jour visuellement le filtre actif
            function updateActiveFilter() {
                $('.date-filter-btn').removeClass('active');
                
                if (currentDateFilter === '') {
                    $('.date-filter-btn[data-filter=""]').addClass('active');
                } else {
                    $(`.date-filter-btn[data-filter="${currentDateFilter}"]`).addClass('active');
                }
            }
            
            // Initialiser le filtre actif au chargement
            updateActiveFilter();
            
            // Fonction de recherche AJAX
            function performSearch(page = 1) {
                currentSearchText = $('#searchInput').val();
                currentStatusFilter = $('#statusFilter').val();
                
                // Afficher un loader
                $('#appointmentsList').html(`
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                        <p class="mt-2 text-muted">🔄 Recherche en cours...</p>
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
                        
                        updateActiveFilter();
                        
                        // Compter les lignes du tableau
                        const count = $('#appointmentsList tbody tr').length;
                        $('#totalAppointments').text(count);
                        
                        if (count === 0) {
                            $('#noResults').show();
                        } else {
                            $('#noResults').hide();
                        }
                    },
                    error: function(xhr) {
                        console.error('Erreur lors de la recherche:', xhr);
                        $('#appointmentsList').html(`
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i> 
                                Une erreur est survenue lors de la recherche.
                            </div>
                        `);
                    }
                });
            }
            
            // Filtres de date
            $('.date-filter-btn').on('click', function() {
                currentDateFilter = $(this).data('filter');
                updateActiveFilter();
                performSearch(1);
            });
            
            // Recherche en temps réel avec délai
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
                updateActiveFilter();
                performSearch(1);
            });
            
            // Gérer les clics sur la pagination
            $(document).on('click', '.pagination a', function(e) {
                e.preventDefault();
                
                const url = $(this).attr('href');
                const urlParams = new URLSearchParams(url.split('?')[1]);
                const page = urlParams.get('page') || 1;
                
                $('#appointmentsList').html(`
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                    </div>
                `);
                
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
                        updateActiveFilter();
                        
                        // Scroll vers le haut du tableau
                        $('html, body').animate({
                            scrollTop: $('#appointmentsList').offset().top - 100
                        }, 300);
                    },
                    error: function(xhr) {
                        console.error('Erreur lors du chargement de la page:', xhr);
                        $('#appointmentsList').html(`
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i> 
                                Erreur lors du chargement.
                            </div>
                        `);
                    }
                });
            });
            
            // Export PDF
            $('#exportPdfBtn').on('click', function() {
                $('#exportLoading').addClass('active');
                
                // Construire l'URL avec les paramètres actuels
                const exportUrl = "{{ route('appointments.export-pdf') }}?" + 
                    $.param({
                        search: currentSearchText,
                        status: currentStatusFilter,
                        date_filter: currentDateFilter
                    });
                
                // Créer un lien temporaire pour télécharger le PDF
                window.location.href = exportUrl;
                
                // Masquer le loader après 2 secondes
                setTimeout(function() {
                    $('#exportLoading').removeClass('active');
                }, 2000);
            });
        });
    </script>
@endsection