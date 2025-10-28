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
        HEADER OPTIMISÉ
        ============================================ */
        .card-header-enhanced {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 1.25rem 1.5rem;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
        }

        .card-header-enhanced h4 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .btn-add-patient {
            background: white;
            color: var(--primary);
            border: none;
            padding: 0.625rem 1.5rem;
            border-radius: 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-add-patient:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 255, 255, 0.3);
        }

        /* ============================================
        TABLE OPTIMISÉE
        ============================================ */
        .table-container {
            background: white;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
            overflow: hidden;
        }

        .table thead th {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            padding: 1rem 0.75rem;
            border: none;
        }

        .table tbody tr {
            transition: all 0.2s ease;
        }

        .table tbody tr:hover {
            background: #f8fafc;
            transform: translateX(2px);
        }

        .table tbody td {
            padding: 1rem 0.75rem;
            vertical-align: middle;
        }

        /* ============================================
        ACTIONS RAPIDES
        ============================================ */

        .btn-action {
            padding: 0.375rem 0.75rem;
            border-radius: 0.5rem;
            border: none;
            transition: all 0.2s ease;
            font-size: 0.875rem;
        }

        .btn-action:hover {
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
        BADGE SOLDE
        ============================================ */
        .balance-badge {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 0.375rem 0.875rem;
            border-radius: 1.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            display: inline-block;
        }

        /* ============================================
        MODALS OPTIMISÉS
        ============================================ */
        .modal-content-enhanced {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .modal-header-enhanced {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            padding: 1.5rem;
            border: none;
        }

        .modal-header-enhanced .btn-close {
            filter: brightness(0) invert(1);
        }

        .form-section {
            background: #f8fafc;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }

        .form-section-title {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-label-enhanced {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .form-control-enhanced {
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 0.625rem 0.875rem;
            transition: all 0.2s ease;
        }

        .form-control-enhanced:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            outline: none;
        }

        /* ============================================
        UPLOAD ZONE
        ============================================ */
        .file-upload-zone {
            border: 2px dashed #d1d5db;
            border-radius: var(--border-radius);
            padding: 2rem;
            text-align: center;
            background: #fafafa;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .file-upload-zone:hover {
            border-color: var(--primary);
            background: #eff6ff;
        }

        .file-name-display {
            margin-top: 1rem;
            padding: 0.75rem;
            background: #e0f2fe;
            border-radius: 0.5rem;
            color: #0369a1;
            font-weight: 500;
            display: none;
        }

        /* ============================================
        LOADING STATES
        ============================================ */
        .btn-loading {
            position: relative;
            pointer-events: none;
            opacity: 0.7;
        }

        .btn-loading::after {
            content: '';
            position: absolute;
            width: 14px;
            height: 14px;
            top: 50%;
            left: 50%;
            margin-left: -7px;
            margin-top: -7px;
            border: 2px solid #ffffff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spinner 0.6s linear infinite;
        }

        @keyframes spinner {
            to { transform: rotate(360deg); }
        }

        /* ============================================
        RESPONSIVE
        ============================================ */
        @media (max-width: 768px) {
            
            .card-header-enhanced {
                flex-direction: column;
                gap: 1rem;
            }
            
            .table {
                font-size: 0.875rem;
            }
        }
    </style>
@endsection

@section('content')
    <div class="container">
        <div class="page-inner">
            <!-- Breadcrumbs -->
            <div class="page-header mb-4">
                <ul class="breadcrumbs">
                    <li class="nav-home"><a href="{{url('/')}}"><i class="icon-home"></i></a></li>
                    <li class="separator"><i class="icon-arrow-right"></i></li>
                    <li class="nav-item"><a href="{{ url('/') }}">Admin</a></li>
                    <li class="separator"><i class="icon-arrow-right"></i></li>
                    <li class="nav-item"><a href="{{ route('patient.index') }}">Patientes</a></li>
                </ul>
            </div>

            <!-- Card Principal -->
            <div class="card shadow-sm">
                <!-- Header -->
                <div class="card-header-enhanced d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-users me-2"></i>Liste des Patientes</h4>
                    @can('patient.create')
                        <button class="btn btn-add-patient" data-bs-toggle="modal" data-bs-target="#addRowModal">
                            <i class="fa fa-plus me-2"></i>Nouvelle Patiente
                        </button>
                    @endcan
                </div>
                </div>

                <!-- Table -->
                <div class="table-container">
                    <div class="table-responsive">
                        <table id="add-row" class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nom Complet</th>
                                    <th>Téléphone</th>
                                    <th>Adresse</th>
                                    <th>Solde</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($patients as $patient)
                                <tr>
                                    <td><strong>#{{ str_pad($patient->id, 4, '0', STR_PAD_LEFT) }}</strong></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                                {{ strtoupper(substr($patient->first_name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <strong>{{ $patient->first_name }} {{ $patient->last_name }}</strong>
                                                @if($patient->middle_name)
                                                    <br><small class="text-muted">{{ $patient->middle_name }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <i class="fas fa-phone-alt text-primary me-1"></i>
                                        {{ $patient->user->phone }}
                                    </td>
                                    <td>
                                        <i class="fas fa-map-marker-alt text-danger me-1"></i>
                                        {{ $patient->district }}, {{ $patient->location }}
                                    </td>
                                    <td>
                                        <span class="balance-badge">
                                            {{ number_format($patient->account?->balance ?? 0) }} GNF
                                        </span>
                                    </td>
                                    <td>
                                        <div class="quick-actions">
                                            <a href="{{ route('patient.show', $patient->id) }}" 
                                            class="btn btn-action btn-view" 
                                            title="Voir">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            @can('patient.edit')
                                            <button class="btn btn-action btn-edit edit-button" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editRowModal"
                                                    data-patient='@json($patient)'
                                                    data-phone="{{ $patient->user->phone }}"
                                                    title="Modifier">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            @endcan
                                            @can('patient.delete')
                                            <button class="btn btn-action btn-delete delete-button" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deleteRowModal"
                                                    data-patient='@json($patient)'
                                                    title="Supprimer">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                            @endcan
                                            @can('patient.add_file')
                                            <button class="btn btn-action btn-upload add-file-button" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#uploadFileModal"
                                                    data-patient='@json($patient)'
                                                    title="Ajouter un fichier">
                                                <i class="fas fa-upload"></i>
                                            </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
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