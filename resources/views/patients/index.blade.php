@extends('layouts.backend')

@section('style')
    <style>
        /* ============================================
        VARIABLES CSS
        ============================================ */
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --border-radius: 0.75rem;
        }

        /* ============================================
        HEADER
        ============================================ */
        .page-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .page-header h1 {
            margin: 0;
            font-size: 1.75rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .btn-add-patient {
            background: white;
            color: var(--primary);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-add-patient:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 255, 255, 0.3);
            color: var(--primary);
        }

        /* ============================================
        BARRE DE RECHERCHE ET FILTRES
        ============================================ */
        .search-filter-bar {
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 3rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
        }

        /* ============================================
        GRID DE CARDS
        ============================================ */
        .patients-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        @media (max-width: 768px) {
            .patients-grid {
                grid-template-columns: 1fr;
            }
        }

        /* ============================================
        PATIENT CARD
        ============================================ */
        .patient-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .patient-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        }

        .patient-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            border-color: var(--primary);
        }

        /* Header de la card */
        .patient-card-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.25rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #f3f4f6;
        }

        .patient-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.5rem;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .patient-info {
            flex: 1;
            min-width: 0;
        }

        .patient-name {
            font-size: 1.125rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .patient-id {
            font-size: 0.875rem;
            color: #6b7280;
            font-weight: 500;
        }

        /* Corps de la card */
        .patient-card-body {
            margin-bottom: 1rem;
        }

        .patient-detail {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 0;
            font-size: 0.9rem;
        }

        .patient-detail i {
            width: 20px;
            color: var(--primary);
            font-size: 1rem;
        }

        .patient-detail-label {
            color: #6b7280;
            font-weight: 500;
            min-width: 80px;
        }

        .patient-detail-value {
            color: #1f2937;
            font-weight: 600;
            flex: 1;
            word-break: break-word;
        }

        /* Solde badge */
        .balance-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 1.5rem;
            font-weight: 700;
            font-size: 0.875rem;
        }

        /* Actions */
        .patient-card-footer {
            display: flex;
            gap: 0.5rem;
            padding-top: 1rem;
            border-top: 1px solid #f3f4f6;
        }

        .card-action-btn {
            flex: 1;
            padding: 0.625rem;
            border-radius: 0.5rem;
            border: none;
            font-size: 0.875rem;
            font-weight: 600;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .card-action-btn:hover {
            transform: translateY(-2px);
        }

        .btn-view { 
            background: #e0f2fe; 
            color: #0369a1; 
        }
        .btn-view:hover { 
            background: #0369a1; 
            color: white; 
        }

        .btn-edit { 
            background: #fef3c7; 
            color: #92400e; 
        }
        .btn-edit:hover { 
            background: #f59e0b; 
            color: white; 
        }

        .btn-delete { 
            background: #fee2e2; 
            color: #991b1b; 
        }
        .btn-delete:hover { 
            background: #ef4444; 
            color: white; 
        }

        .btn-upload { 
            background: #e0e7ff; 
            color: #4338ca; 
        }
        .btn-upload:hover { 
            background: #6366f1; 
            color: white; 
        }

        /* ============================================
        EMPTY STATE
        ============================================ */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .empty-state i {
            font-size: 4rem;
            color: #d1d5db;
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            color: #6b7280;
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: #9ca3af;
        }
    </style>
@endsection

@section('content')
    <div class="container">
        <div class="page-inner">

            <!-- Header -->
            <div class="page-header">
                <h1>
                    <i class="fas fa-users"></i>
                    Liste des Patientes
                </h1>
                <button class="btn-add-patient" data-bs-toggle="modal" data-bs-target="#addRowModal">
                    <i class="fas fa-plus"></i>
                    Nouvelle Patiente
                </button>
            </div>

            <!-- Barre de recherche -->
            {{-- <div class="search-filter-bar">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Rechercher une patiente par nom, téléphone ou ID...">
                </div>
            </div> --}}

            <div class="search-filter-bar">
                <form method="GET" action="{{ route('patient.index') }}" id="searchForm">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input 
                            type="text" 
                            name="search"
                            id="searchInput" 
                            placeholder="Rechercher une patiente par nom, téléphone ou ID..."
                            value="{{ $search ?? '' }}"
                            autocomplete="off"
                        >
                        @if($search)
                            <a href="{{ route('patient.index') }}" style="position:absolute; right:1rem; top:50%; transform:translateY(-50%); color:#6b7280;">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Grid de cards -->
            <div class="patients-grid" id="patientsGrid">
                @forelse($patients as $patient)
                    <div class="patient-card" data-patient-name="{{ strtolower($patient->first_name . ' ' . $patient->last_name) }}" 
                        data-patient-phone="{{ $patient->user->phone ?? '' }}" 
                        data-patient-id="{{ $patient->id }}">
                        
                        <!-- Header -->
                        <div class="patient-card-header">
                            <div class="patient-avatar">
                                {{ strtoupper(substr($patient->first_name ?? 'P', 0, 1)) }}
                            </div>
                            <div class="patient-info">
                                <div class="patient-name">
                                    {{ $patient->first_name }} {{ $patient->last_name }}
                                </div>
                                <div class="patient-id">
                                    #{{ str_pad($patient->id, 4, '0', STR_PAD_LEFT) }}
                                </div>
                            </div>
                        </div>

                        <!-- Body -->
                        <div class="patient-card-body">
                            <div class="patient-detail">
                                <i class="fas fa-phone"></i>
                                <span class="patient-detail-label">Téléphone:</span>
                                <span class="patient-detail-value">{{ $patient->user->phone ?? 'N/A' }}</span>
                            </div>

                            <div class="patient-detail">
                                <i class="fas fa-map-marker-alt"></i>
                                <span class="patient-detail-label">Adresse:</span>
                                <span class="patient-detail-value">{{ $patient->location ?? 'Non renseignée' }}</span>
                            </div>

                            <div class="patient-detail">
                                <i class="fas fa-calendar"></i>
                                <span class="patient-detail-label">Âge:</span>
                                <span class="patient-detail-value">{{ $patient->age ?? '-' }} ans</span>
                            </div>

                            <div class="patient-detail">
                                <i class="fas fa-wallet"></i>
                                <span class="patient-detail-label">Solde:</span>
                                <span class="balance-badge">
                                    {{ number_format($patient->solde ?? 0, 0, ',', ' ') }} GNF
                                </span>
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="patient-card-footer">
                            @can('patient.view')
                                <a href="{{ route('patient.show', $patient->id) }}" class="card-action-btn btn-view">
                                    <i class="fas fa-eye"></i>
                                    
                                </a>
                            @endcan

                            @can('patient.edit')
                                <button class="card-action-btn btn-edit edit-button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editRowModal"
                                        data-patient='@json($patient)'
                                        data-phone="{{ $patient->user->phone ?? '' }}">
                                    <i class="fas fa-edit"></i>
                                    
                                </button>
                            @endcan
                            
                            @can('patient.delete')
                                <button class="card-action-btn btn-delete delete-button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteRowModal"
                                        data-patient='@json($patient)'>
                                    <i class="fas fa-trash"></i>
                                    
                                </button>
                            @endcan
                            
                            @can('patient.add_file')
                                <button class="card-action-btn btn-upload add-file-button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#uploadFileModal"
                                        data-patient='@json($patient)'>
                                    <i class="fas fa-upload"></i>
                                    Fichier
                                </button>
                            @endcan
                        </div>
                    </div>
                @empty
                    <div class="empty-state" style="grid-column: 1 / -1;">
                        <i class="fas fa-users-slash"></i>
                        <h3>Aucune patiente trouvée</h3>
                        <p>Ajoutez votre première patiente en cliquant sur le bouton ci-dessus</p>
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center">
                {{ $patients->links() }}
            </div>

            <!-- MODAL AJOUT -->
                <div class="modal fade" id="addRowModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content modal-content-enhanced">
                            <div class="modal-header modal-header-enhanced">
                                <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Nouvelle Patiente</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form id="addPatientForm" action="{{ route('patient.store') }}" method="POST">
                                @csrf
                                <div class="modal-body p-4">
                                    <!-- Informations Personnelles -->
                                    <div class="form-section">
                                        <div class="form-section-title">
                                            <i class="fas fa-user"></i>Informations Personnelles
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label-enhanced">Prénom <span class="text-danger">*</span></label>
                                                <input type="text" name="first_name" class="form-control form-control-enhanced" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label-enhanced">Nom <span class="text-danger">*</span></label>
                                                <input type="text" name="last_name" class="form-control form-control-enhanced" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label-enhanced">Téléphone <span class="text-danger">*</span></label>
                                                <input type="tel" name="phone" class="form-control form-control-enhanced" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label-enhanced">Âge <span class="text-danger">*</span></label>
                                                <input type="number" name="age" class="form-control form-control-enhanced" required>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Informations Médicales -->
                                    <div class="form-section">
                                        <div class="form-section-title">
                                            <i class="fas fa-heartbeat"></i>Informations Médicales
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label-enhanced">Situation Matrimoniale</label>
                                                <select name="marital_status" class="form-control form-control-enhanced">
                                                    <option value="">-- Sélectionner --</option>
                                                    <option value="Marie">Mariée</option>
                                                    <option value="Celibataire">Célibataire</option>
                                                    <option value="Autre">Autre</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label-enhanced">Groupe Sanguin</label>
                                                <select name="blood_group" class="form-control form-control-enhanced">
                                                    <option value="">-- Sélectionner --</option>
                                                    @foreach(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $group)
                                                        <option value="{{ $group }}">{{ $group }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Contact et Localisation -->
                                    <div class="form-section">
                                        <div class="form-section-title">
                                            <i class="fas fa-map-marker-alt"></i>Contact et Localisation
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label-enhanced">Nom du Proche</label>
                                                <input type="text" name="relative_name" class="form-control form-control-enhanced">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label-enhanced">Téléphone du Proche</label>
                                                <input type="tel" name="relative_phone" class="form-control form-control-enhanced">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label-enhanced">Profession</label>
                                                <input type="text" name="occupation" class="form-control form-control-enhanced">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label-enhanced">Localisation</label>
                                                <input type="text" name="location" class="form-control form-control-enhanced">
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="gender" value="Femme">
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                    <button type="submit" class="btn btn-primary" id="btnAddPatient">
                                        <i class="fas fa-save me-2"></i>Enregistrer
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- MODAL MODIFICATION -->
                <div class="modal fade" id="editRowModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content modal-content-enhanced">
                            <div class="modal-header modal-header-enhanced">
                                <h5 class="modal-title"><i class="fas fa-user-edit me-2"></i>Modifier Patiente</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form id="editPatientForm" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="modal-body p-4">
                                    <div class="form-section">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label-enhanced">Prénom</label>
                                                <input type="text" name="first_name" id="edit_first_name" class="form-control form-control-enhanced">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label-enhanced">Nom</label>
                                                <input type="text" name="last_name" id="edit_last_name" class="form-control form-control-enhanced">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label-enhanced">Téléphone</label>
                                                <input type="tel" name="phone" id="edit_phone" class="form-control form-control-enhanced">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label-enhanced">Âge</label>
                                                <input type="number" name="age" id="edit_age" class="form-control form-control-enhanced">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label-enhanced">Situation Matrimoniale</label>
                                                <select name="marital_status" id="edit_marital_status" class="form-control form-control-enhanced">
                                                    <option value="">-- Sélectionner --</option>
                                                    <option value="Marie">Mariée</option>
                                                    <option value="Celibataire">Célibataire</option>
                                                    <option value="Autre">Autre</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label-enhanced">Groupe Sanguin</label>
                                                <select name="blood_group" id="edit_blood_group" class="form-control form-control-enhanced">
                                                    @foreach(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $group)
                                                        <option value="{{ $group }}">{{ $group }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label-enhanced">Localisation</label>
                                                <input type="text" name="location" id="edit_location" class="form-control form-control-enhanced">
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="gender" value="Femme">
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                    <button type="submit" class="btn btn-warning" id="btnEditPatient">
                                        <i class="fas fa-save me-2"></i>Modifier
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- MODAL SUPPRESSION -->
                <div class="modal fade" id="deleteRowModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content modal-content-enhanced">
                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Confirmation</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <form id="deletePatientForm" method="POST">
                                @csrf
                                @method('DELETE')
                                <div class="modal-body text-center p-4">
                                    <i class="fas fa-user-times fa-3x text-danger mb-3"></i>
                                    <p class="h5 mb-3" id="delete_patient_name"></p>
                                    <p class="text-muted">Cette action est irréversible</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                    <button type="submit" class="btn btn-danger" id="btnDeletePatient">
                                        <i class="fas fa-trash me-2"></i>Supprimer
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- MODAL UPLOAD -->
                <div class="modal fade" id="uploadFileModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content modal-content-enhanced">
                            <div class="modal-header modal-header-enhanced">
                                <h5 class="modal-title"><i class="fas fa-cloud-upload-alt me-2"></i>Ajouter un Document</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form id="uploadFileForm" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="modal-body p-4">
                                    <div class="file-upload-zone" onclick="document.getElementById('fileInput').click()">
                                        <i class="fas fa-cloud-upload-alt text-primary fa-3x mb-3"></i>
                                        <h6>Glissez ou cliquez pour parcourir</h6>
                                        <p class="text-muted mb-0">PDF, JPG, PNG - Max 10MB</p>
                                        <input type="file" id="fileInput" name="file" class="d-none" accept=".pdf,.jpg,.jpeg,.png">
                                    </div>
                                    <div class="file-name-display" id="fileNameDisplay"></div>
                                    <div class="mt-3">
                                        <label class="form-label-enhanced">Description</label>
                                        <input type="text" name="name" class="form-control form-control-enhanced" placeholder="Ex: Radiographie du 02/06/2025">
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                    <button type="submit" class="btn btn-primary" id="btnUploadFile">
                                        <i class="fas fa-upload me-2"></i>Uploader
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>


        </div>
    </div>
@endsection

@section('script')
    <script>
        $(document).ready(function() {
            
        // RECHERCHE EN TEMPS RÉEL
        // ============================================
        $('#searchInput').on('keyup', function() {
            const searchTerm = $(this).val().toLowerCase().trim();
            
            if (searchTerm === '') {
                $('.patient-card').show();
                return;
            }
            
            $('.patient-card').each(function() {
                const name = ($(this).data('patient-name') || '').toString().toLowerCase();
                const phone = ($(this).data('patient-phone') || '').toString().toLowerCase();
                const id = ($(this).data('patient-id') || '').toString().toLowerCase();
                
                const searchableText = `${name} ${phone} ${id}`;
                const matches = searchableText.includes(searchTerm);
                
                $(this).toggle(matches);
            });
        });

            // ============================================
            // MODIFIER PATIENTE
            // ============================================
            $('.edit-button').on('click', function() {
                const patient = $(this).data('patient');
                const phone = $(this).data('phone');
                
                $('#edit_first_name').val(patient.first_name);
                $('#edit_last_name').val(patient.last_name);
                $('#edit_phone').val(phone);
                $('#edit_age').val(patient.age);
                $('#edit_marital_status').val(patient.marital_status);
                $('#edit_blood_group').val(patient.blood_group);
                $('#edit_location').val(patient.location);
                
                $('#editPatientForm').attr('action', '/patient/' + patient.id);
            });

            // ============================================
            // SUPPRIMER PATIENTE
            // ============================================
            $('.delete-button').on('click', function() {
                const patient = $(this).data('patient');
                
                $('#delete_patient_name').text(`Voulez-vous vraiment supprimer ${patient.first_name} ${patient.last_name} ?`);
                $('#deletePatientForm').attr('action', '/patient/' + patient.id);
            });

            // ============================================
            // UPLOAD FICHIER
            // ============================================
            $('.add-file-button').on('click', function() {
                const patient = $(this).data('patient');
                $('#uploadFileForm').attr('action', '/patient/file/' + patient.id);
            });

            // Afficher le nom du fichier sélectionné
            $('#fileInput').on('change', function() {
                const fileName = this.files[0]?.name;
                if (fileName) {
                    $('#fileNameDisplay').text('📄 ' + fileName).show();
                } else {
                    $('#fileNameDisplay').hide();
                }
            });

            // ============================================
            // LOADING STATES
            // ============================================
            function setLoading(btn, isLoading) {
                if (isLoading) {
                    $(btn).addClass('btn-loading').prop('disabled', true);
                } else {
                    $(btn).removeClass('btn-loading').prop('disabled', false);
                }
            }

            $('#addPatientForm').on('submit', function() {
                setLoading('#btnAddPatient', true);
            });

            $('#editPatientForm').on('submit', function() {
                setLoading('#btnEditPatient', true);
            });

            $('#deletePatientForm').on('submit', function() {
                setLoading('#btnDeletePatient', true);
            });

            $('#uploadFileForm').on('submit', function() {
                setLoading('#btnUploadFile', true);
            });
        });
    </script>
@endsection