@extends('layouts.backend')

@section('style')
    <style>
        /* ============================================
        VARIABLES CSS - Thème global
        ============================================ */
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --light-bg: #f8fafc;
            --border-radius: 0.75rem;
            --transition: all 0.2s ease;
        }

        /* ============================================
        BASE - Styles de base
        ============================================ */
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        .main-container {
            max-width: 1400px;
            margin: 50px auto 0;
            padding: 2rem 1rem;
        }

        /* ============================================
        HEADER - En-tête de page
        ============================================ */
        .page-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .page-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        /* ============================================
        CARDS - Cartes de consultation
        ============================================ */
        .consultation-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        .card-header-custom {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            position: relative;
        }

        .card-header-custom::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--success));
        }

        .card-header-custom h5 {
            font-weight: 600;
            color: #1e293b;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.1rem;
        }

        .card-body-custom {
            padding: 1.5rem;
        }

        /* ============================================
        FORMS - Formulaires
        ============================================ */
        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .required-mark {
            color: var(--danger);
            font-weight: 700;
        }

        .form-control {
            border: 2px solid #e5e7eb;
            border-radius: var(--border-radius);
            padding: 0.625rem 1rem;
            font-size: 0.95rem;
            transition: var(--transition);
            background: #fafafa;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            background: white;
            outline: none;
        }

        /* ============================================
        BADGES - Statuts
        ============================================ */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .status-required { 
            background: #fef2f2; 
            color: #dc2626; 
        }

        .status-optional { 
            background: #f0fdf4; 
            color: #16a34a; 
        }

        /* ============================================
        BUTTONS - Boutons
        ============================================ */
        .btn-enhanced {
            padding: 0.625rem 1.25rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            font-size: 0.9rem;
            border: none;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }

        .btn-primary-enhanced {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
        }

        .btn-primary-enhanced:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
        }

        .btn-outline-enhanced {
            background: white;
            border: 2px solid #e5e7eb;
            color: #374151;
        }

        .btn-outline-enhanced:hover {
            background: #f9fafb;
            border-color: var(--primary);
            color: var(--primary);
        }

        .action-buttons {
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
            position: sticky;
            bottom: 20px;
            z-index: 50;
        }

        /* ============================================
        SECTIONS SPÉCIALES
        ============================================ */
        .antecedents-section {
            background: linear-gradient(135deg, #fef3c7 0%, #fbbf24 5%, #fef3c7 100%);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-top: 1rem;
            border: 1px solid #f59e0b;
        }

        .section-notice {
            background: #eff6ff;
            border: 1px solid #dbeafe;
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-bottom: 1.5rem;
            color: #1e40af;
            font-size: 0.9rem;
        }

        .alert-appointment {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-bottom: 1rem;
            color: #92400e;
        }

        /* ============================================
        SERVICE SELECTION - Recherche et sélection
        ============================================ */
        .service-search-container {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
        }

        .search-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: var(--border-radius);
            font-size: 16px;
            outline: none;
            transition: var(--transition);
        }

        .search-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .selected-items {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
            min-height: 50px;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: var(--border-radius);
            background: white;
        }

        .selected-items:empty::before {
            content: "Aucun élément sélectionné";
            color: #6c757d;
            font-style: italic;
            display: block;
            width: 100%;
            text-align: center;
        }

        .selected-item {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
            animation: slideIn 0.2s ease-out;
        }

        .quantity-input {
            width: 45px;
            padding: 2px 4px;
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 4px;
            background: rgba(255,255,255,0.2);
            color: white;
            text-align: center;
            font-size: 12px;
        }

        .remove-item {
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .remove-item:hover {
            background: rgba(255, 255, 255, 0.4);
        }

        /* ============================================
        DROPDOWN - Liste déroulante
        ============================================ */
        .position-relative {
            position: relative;
        }

        .dropdown-list {
            /* position: flex !important; */
            top: auto !important;
            bottom: auto !important;
            left: 0;
            right: 0;
            margin-top: 0;
            background: white;
            border: 2px solid #e9ecef;
            border-top: none;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
            max-height: 300px;
            overflow-y: auto;
            z-index: 9999 !important;
            display: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            opacity: 0;
            transform: translateY(-10px);
            transition: var(--transition);
        }

        .dropdown-list.show {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        .dropdown-item {
            padding: 10px 16px;
            cursor: pointer;
            border-bottom: 1px solid #f1f3f4;
            transition: var(--transition);
            font-size: 0.9rem;
        }

        .dropdown-item:hover {
            background: #f8f9fa;
        }

        .dropdown-item.selected {
            background: #e7f3ff;
            color: var(--primary);
            font-weight: 500;
        }

        .category {
            padding: 6px 16px;
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e9ecef;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        /* ============================================
        MODAL - Fenêtre de résumé
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
        }

        .summary-section {
            background: #f8fafc;
            border-radius: var(--border-radius);
            padding: 1.25rem;
            margin-bottom: 1rem;
        }

        .summary-section h6 {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1rem;
        }

        .summary-item {
            display: grid;
            grid-template-columns: 140px 1fr;
            gap: 1rem;
            margin-bottom: 0.75rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .summary-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .summary-label {
            font-weight: 600;
            color: #374151;
            font-size: 0.9rem;
        }

        .summary-value {
            color: #6b7280;
            font-size: 0.9rem;
        }

        /* ============================================
        SLOTS - Créneaux disponibles
        ============================================ */
        .available-slots {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .slot-button {
            padding: 0.375rem 0.75rem;
            border: 1px solid var(--primary);
            background: white;
            color: var(--primary);
            border-radius: 1rem;
            font-size: 0.8rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .slot-button:hover {
            background: var(--primary);
            color: white;
        }

        /* ============================================
        ANIMATIONS
        ============================================ */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* ============================================
        RESPONSIVE
        ============================================ */
        @media (max-width: 768px) {
            .main-container {
                padding: 1rem 0.5rem;
                margin-top: 20px;
            }
            
            .page-header h1 {
                font-size: 1.25rem;
            }
            
            .action-buttons {
                flex-direction: column;
                position: relative;
                bottom: 0;
            }
            
            .summary-item {
                grid-template-columns: 1fr;
                gap: 0.25rem;
            }
            
            .card-body-custom {
                padding: 1rem;
            }
        }

        /* ============================================
        OPTIMISATIONS PERFORMANCE
        ============================================ */
        * {
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .dropdown-list {
            will-change: transform, opacity;
        }

        .selected-item {
            will-change: transform;
        }

        /* ---------------- Hali : alignement sur la charte (ajouté, rien retiré) */
        :root {
            --primary: var(--hali-primaire, #0f766e);
            --primary-dark: var(--hali-primaire-fonce, #115e59);
            --success: var(--hali-succes, #15803d);
            --danger: var(--hali-danger, #b91c1c);
            --warning: var(--hali-alerte, #b45309);
        }
        /* En-tête de page : un titre, pas une bannière */
        .page-header { background: none !important; color: var(--hali-encre, #111827) !important; box-shadow: none !important; padding: 0 0 12px !important; border-bottom: 1px solid var(--hali-bordure, #e5e7eb); }
        .page-header h1, .page-header h1 i { color: var(--hali-encre, #111827) !important; font-size: 1.45rem !important; }
        .page-header h1 i { color: var(--hali-primaire, #0f766e) !important; }
        .card-header-custom { background: #fafbfc !important; }
        .card-header-custom::before { background: var(--hali-primaire, #0f766e) !important; height: 3px; }
        .selected-item { background: var(--hali-primaire-pale, #f0fdfa) !important; color: var(--hali-primaire-fonce, #115e59) !important; border: 1px solid var(--hali-primaire-clair, #ccfbf1); box-shadow: none !important; }
        .modal-header-enhanced { background: var(--hali-primaire, #0f766e) !important; }
    </style>
@endsection

@section('content')
    <div class="main-container">
        <div class="page-header">
            <h1>
                <i class="fas fa-user-md"></i>
                Nouvelle Consultation Médicale 
                @if (auth()->user()->hasRole('medecin'))
                    {{ auth()->user()->employee->department->name }}
                @endif
            </h1>
        </div>

        <form id="consultationForm" action="{{ route('consultation.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method("POST")
            <!-- Section Patient -->
            <div class="consultation-card">
                <div class="card-header-custom">
                    <h5>
                        <i class="fas fa-user text-primary"></i>
                        Informations du Patient
                    </h5>
                </div>
                <div class="card-body-custom">
                    <div class="row">
                        <div class="col-lg-6 col-md-12">
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-user-circle text-primary"></i>
                                    Sélectionner un Patient
                                    <span class="required-mark">*</span>
                                    <span class="status-badge status-required ms-2">Obligatoire</span>
                                </label>
                                <select id="patient_id" class="form-control selectpicker" data-live-search="true" name="patient_id" data-container="body" title="Choisir un patient...">
                                    @foreach ($patients as $patient)
                                        <option value="{{ $patient->id }}"> {{ $patient->getFullNameAttribute()." - ".($patient->telephone ?: 'N/A') }} </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-12">
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-file-medical text-info"></i>
                                    Documents Associés
                                    <span class="status-badge status-optional ms-2">Optionnel</span>
                                </label>
                                <select class="form-control" name="fichiers_enregistres[]">
                                    <option value="">Choisir un fichier...</option>
                                    @foreach ($fichiersPatients as $file)
                                        <option value="{{ $file->id }}">{{ $file->nom_fichier }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Section Antécédents -->
                    @if (auth()->user()->hasRole('medecin'))
                        <div id="antecedents-section" class="antecedents-section" style="display: none;">
                            <div class="section-notice">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Première consultation :</strong> Veuillez remplir les antécédents médicaux du patient.
                            </div>
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-history text-warning"></i>
                                            Antécédents Médicaux
                                        </label>
                                        <textarea name="antecedents_medicaux" class="form-control" rows="3" placeholder="Diabète, hypertension, chirurgies antérieures..."></textarea>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-notes-medical text-warning"></i>
                                            Antécédents Chirurgicaux
                                        </label>
                                        <textarea name="antecedents_chirurgicaux" class="form-control" rows="3" placeholder="Interventions chirurgicales antérieures..."></textarea>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-venus-mars text-warning"></i>
                                            Antécédents Gynéco-Obstétricaux
                                        </label>
                                        <textarea name="antecedents_gyneco_obstetricaux" class="form-control" rows="3" placeholder="Grossesses, accouchements..."></textarea>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-users text-warning"></i>
                                            Antécédents Familiaux
                                        </label>
                                        <textarea name="antecedents_familiaux" class="form-control" rows="3" placeholder="Maladies héréditaires..."></textarea>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-exclamation-triangle text-danger"></i>
                                            Allergies
                                        </label>
                                        <textarea name="allergies" class="form-control" rows="3" placeholder="Allergies médicamenteuses, alimentaires..."></textarea>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-pills text-info"></i>
                                            Traitements en Cours
                                        </label>
                                        <textarea name="traitements_cours" class="form-control" rows="3" placeholder="Médicaments actuels, posologie..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Section Consultation -->
            @if (auth()->user()->hasRole('medecin'))
                <div class="consultation-card">
                    <div class="card-header-custom">
                        <h5>
                            <i class="fas fa-stethoscope text-primary"></i>
                            Détails de la Consultation
                        </h5>
                    </div>
                    <div class="card-body-custom">
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-question-circle text-primary"></i>
                                        Motif de Consultation
                                        <span class="required-mark">*</span>
                                        <span class="status-badge status-required ms-2">Obligatoire</span>
                                    </label>
                                    <textarea name="motif" class="form-control" rows="3" placeholder="Douleur thoracique, fièvre, contrôle de routine..."></textarea>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-thermometer-half text-primary"></i>
                                        Examen Physique
                                        <span class="required-mark">*</span>
                                        <span class="status-badge status-required ms-2">Obligatoire</span>
                                    </label>
                                    <textarea name="signes_cliniques" class="form-control" rows="3" placeholder="Température: 38°C, Tension: 140/90, Pouls: 85 bpm..."></textarea>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-diagnoses text-success"></i>
                                        Diagnostic
                                        <span class="required-mark">*</span>
                                        <span class="status-badge status-required ms-2">Obligatoire</span>
                                    </label>
                                    <textarea name="diagnostic" class="form-control" rows="3" placeholder="Diagnostic principal et différentiel..."></textarea>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-clipboard-check text-secondary"></i>
                                        Conduite Tenue
                                        <span class="status-badge status-optional ms-2">Optionnel</span>
                                    </label>
                                    <textarea name="observation" class="form-control" rows="3" placeholder="Décisions prises, traitements administrés..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Section Services et Prescriptions -->
            <div class="consultation-card">
                <div class="card-header-custom">
                    <h5>
                        <i class="fas fa-prescription-bottle-alt text-primary"></i>
                        Services, Examens et Prescriptions
                    </h5>
                </div>
                <div class="service-search-container">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-search text-info"></i>
                            Rechercher Services, Examens et Médicaments
                            <span class="status-badge status-optional ms-2">Optionnel</span>
                        </label>
                        <div class="position-relative">
                            <input type="hidden" name="selected_items" id="selected-items-input">
                            <input type="text" class="search-input" placeholder="Rechercher services, examens, médicaments..." id="search-input">
                            <div class="dropdown-list" id="dropdown-list">
                                <div class="category">Services</div>
                                @foreach ($services as $service)
                                    <div class="dropdown-item" data-value="service-{{$service->id}}" data-price="{{$service->amount}}" data-name="{{ $service->name }}">
                                        {{ $service->name }} - {{ number_format($service->amount) }} GNF
                                    </div>
                                @endforeach
                                
                                <div class="category">Examens Complémentaires</div>
                                @foreach ($tests as $test)
                                    <div class="dropdown-item" data-value="examen-{{$test->id}}" data-price="{{$test->amount}}" data-name="{{$test->name}}">
                                        {{ $test->name }} - {{ number_format($test->amount) }} GNF
                                    </div>
                                @endforeach
                                
                                <div class="category">Packages</div>
                                @foreach ($packages as $package)
                                    <div class="dropdown-item" data-value="package-{{$package->id}}" data-price="{{$package->price}}" data-name="{{$package->name}}">
                                        {{ $package->name }} - {{ number_format($package->price) }} GNF
                                    </div>
                                @endforeach
                                
                                <div class="category">Médicaments</div>
                                @foreach ($medicaments as $medicament)
                                    <div class="dropdown-item" data-value="medicament-{{$medicament->id}}" data-price="{{$medicament->amount}}" data-name="{{$medicament->nom}}" data-type="medicament">
                                        {{ $medicament->nom }} - {{ number_format($medicament->amount) }} GNF
                                    </div>
                                @endforeach
                            </div>
                            <div class="selected-items" id="selected-items"></div>  <!-- ✅ APRÈS le dropdown -->
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Section Rendez-vous -->
            @if (auth()->user()->hasRole('medecin'))
                <div class="consultation-card">
                    <div class="card-header-custom">
                        <h5>
                            <i class="fas fa-calendar-alt text-primary"></i>
                            Prochain Rendez-vous
                        </h5>
                    </div>
                    <div class="card-body-custom">
                        <div id="appointment-alert" class="alert-appointment" style="display: none;">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <span id="appointment-message"></span>
                            <div class="available-slots" id="available-slots"></div>
                        </div>
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-calendar-alt text-warning"></i>
                                        Date et Heure du Rendez-vous
                                        <span class="status-badge status-optional ms-2">Optionnel</span>
                                    </label>
                                    <input type="datetime-local" name="prochain_rdv" id="prochain_rdv" class="form-control">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-user-md text-warning"></i>
                                        Médecin de Suivi
                                        <span class="status-badge status-optional ms-2">Optionnel</span>
                                    </label>
                                    <select class="form-control" name="medecin_suivi" disabled>
                                        <option value="{{auth()->user()->employee->id}}" selected>{{auth()->user()->employee->getFullNameAttribute()}}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Boutons d'action -->
            <div class="action-buttons">
                <button type="button" class="btn btn-outline-enhanced">
                    <i class="fas fa-times"></i>
                    Annuler
                </button>
                <button type="button" class="btn btn-primary-enhanced" data-bs-toggle="modal" data-bs-target="#consultationSummaryModal">
                    <i class="fas fa-eye"></i>
                    Prévisualiser
                </button>
                <button type="submit" class="btn btn-primary-enhanced">
                    <i class="fas fa-check-circle"></i>
                    Confirmer et Enregistrer
                </button>

            </div>
        </form>
    </div>

    <!-- Modal Résumé -->
    <div class="modal fade" id="consultationSummaryModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content modal-content-enhanced">
                <div class="modal-header modal-header-enhanced">
                    <h5 class="modal-title">
                        <i class="fas fa-clipboard-check me-2"></i>
                        Résumé de la Consultation
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="section-notice mb-4">
                        <i class="fas fa-info-circle me-2"></i>
                        Veuillez vérifier attentivement toutes les informations avant de confirmer l'enregistrement.
                    </div>

                    <div class="summary-section">
                        <h6><i class="fas fa-user"></i> Informations Patient</h6>
                        <div class="summary-item">
                            <span class="summary-label">Patient :</span>
                            <span class="summary-value" id="summary-patient">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Documents :</span>
                            <span class="summary-value" id="summary-documents">-</span>
                        </div>
                    </div>

                    <div class="summary-section" id="summary-antecedents" style="display: none;">
                        <h6><i class="fas fa-history"></i> Antécédents Médicaux</h6>
                        <div class="summary-item">
                            <span class="summary-label">Médicaux :</span>
                            <span class="summary-value" id="summary-antecedents-medicaux">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Chirurgicaux :</span>
                            <span class="summary-value" id="summary-antecedents-chirurgicaux">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Gynéco-Obstétricaux :</span>
                            <span class="summary-value" id="summary-antecedents-gyneco">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Familiaux :</span>
                            <span class="summary-value" id="summary-antecedents-familiaux">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Allergies :</span>
                            <span class="summary-value" id="summary-allergies">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Traitements :</span>
                            <span class="summary-value" id="summary-traitements">-</span>
                        </div>
                    </div>

                    <div class="summary-section">
                        <h6><i class="fas fa-stethoscope"></i> Consultation</h6>
                        <div class="summary-item">
                            <span class="summary-label">Motif :</span>
                            <span class="summary-value" id="summary-motif">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Examen Physique :</span>
                            <span class="summary-value" id="summary-signes">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Diagnostic :</span>
                            <span class="summary-value" id="summary-diagnostic">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Conduite :</span>
                            <span class="summary-value" id="summary-observation">-</span>
                        </div>
                    </div>

                    <div class="summary-section">
                        <h6><i class="fas fa-prescription-bottle-alt"></i> Services et Prescriptions</h6>
                        <div class="summary-item">
                            <span class="summary-label">Services :</span>
                            <span class="summary-value" id="summary-services">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Examens :</span>
                            <span class="summary-value" id="summary-examens">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Packages :</span>
                            <span class="summary-value" id="summary-packages">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Médicaments :</span>
                            <span class="summary-value" id="summary-medicaments">-</span>
                        </div>
                    </div>

                    <div class="summary-section">
                        <h6><i class="fas fa-calendar-alt"></i> Suivi</h6>
                        <div class="summary-item">
                            <span class="summary-label">Prochain RDV :</span>
                            <span class="summary-value" id="summary-rdv">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Médecin Suivi :</span>
                            <span class="summary-value" id="summary-medecin">-</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-enhanced" data-bs-dismiss="modal">
                        <i class="fas fa-edit"></i>
                        Modifier
                    </button>
                    <button type="button" class="btn btn-primary-enhanced" id="confirmConsultation">
                        <i class="fas fa-check-circle"></i>
                        Confirmer
                    </button>
                </div>
            </div>
        </div>
    </div>
    
@endsection

@section('script')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        'use strict';

        const isMedecin = {{ auth()->user()->hasRole('medecin') ? 'true' : 'false' }};

        const DOM = {
            patientSelect: $('#patient_id'),
            antecedentsSection: document.getElementById('antecedents-section'),
            fileSelect: document.querySelector('select[name="fichiers_enregistres[]"]'),

            searchInput: document.getElementById('search-input'),
            dropdownList: document.getElementById('dropdown-list'),
            selectedItemsContainer: document.getElementById('selected-items'),
            selectedItemsInput: document.getElementById('selected-items-input'),

            appointmentInput: document.getElementById('prochain_rdv'),
            appointmentAlert: document.getElementById('appointment-alert'),
            appointmentMessage: document.getElementById('appointment-message'),
            availableSlots: document.getElementById('available-slots'),

            form: document.getElementById('consultationForm'),
            summaryModal: $('#consultationSummaryModal'),
            confirmButton: document.getElementById('confirmConsultation')
        };

        const patients = @json($patients);
        const fichierPatients = @json($fichiersPatients);
        const employeeId = @json(auth()->user()->id);

        const state = {
            selectedItems: [],
            medicamentQuantities: {},
            availableSlotsData: [],
            searchTimeout: null
        };

        function init() {
            DOM.patientSelect.selectpicker();
            attachEventListeners();
        }

        function attachEventListeners() {
            DOM.patientSelect.on('change', handlePatientChange);

            if (DOM.searchInput) {
                DOM.searchInput.addEventListener('focus', () => showDropdown(true));
                DOM.searchInput.addEventListener('input', debounce(handleSearchInput, 150));
            }

            if (DOM.dropdownList)
                DOM.dropdownList.addEventListener('click', handleDropdownClick);

            if (DOM.selectedItemsContainer) {
                DOM.selectedItemsContainer.addEventListener('click', handleSelectedItemsClick);
                DOM.selectedItemsContainer.addEventListener('input', handleQuantityChange);
            }

            if (DOM.appointmentInput)
                DOM.appointmentInput.addEventListener('change', handleAppointmentChange);

            DOM.summaryModal.on('show.bs.modal', updateSummary);

            if (DOM.confirmButton)
                DOM.confirmButton.addEventListener('click', handleConfirmConsultation);

            document.addEventListener('click', handleOutsideClick);
        }

        // ============================================
        // GESTION DU PATIENT
        // ============================================
        function handlePatientChange() {
            const patientId = parseInt(this.value);
            const patient = patients.find(p => p.id === patientId);

            if (isMedecin && DOM.antecedentsSection) {
                if (patient && patient.first_visit) {
                    DOM.antecedentsSection.style.display = 'block';
                    DOM.antecedentsSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    DOM.antecedentsSection.style.display = 'none';
                }
            }

            updatePatientFiles(patientId);
        }

        function updatePatientFiles(patientId) {
            if (!DOM.fileSelect) return;

            while (DOM.fileSelect.options.length > 1) {
                DOM.fileSelect.remove(1);
            }

            const patientFiles = fichierPatients.filter(file => file.patient_id == patientId);
            const fragment = document.createDocumentFragment();

            patientFiles.forEach(file => {
                const option = document.createElement('option');
                option.value = file.id;
                option.textContent = file.nom_fichier;
                fragment.appendChild(option);
            });

            DOM.fileSelect.appendChild(fragment);
        }

        // ============================================
        // RECHERCHE ET SÉLECTION D'ITEMS
        // ============================================
        function handleSearchInput(e) {
            const filter = e.target.value.toLowerCase();
            const items = DOM.dropdownList.querySelectorAll('.dropdown-item');

            requestAnimationFrame(() => {
                items.forEach(item => {
                    item.style.display = item.textContent.toLowerCase().includes(filter) ? 'block' : 'none';
                });
            });
        }

        function handleDropdownClick(e) {
            const item = e.target.closest('.dropdown-item');
            if (!item) return;

            const value = item.dataset.value;
            const name = item.dataset.name;
            const type = item.dataset.type;

            if (state.selectedItems.some(s => s.value === value)) return;

            state.selectedItems.push({ value, name, type });

            if (type === 'medicament') {
                state.medicamentQuantities[value] = 1;
            }

            item.classList.add('selected');
            updateSelectedItemsDisplay();
            DOM.searchInput.value = '';
            showDropdown(false);
        }

        function handleSelectedItemsClick(e) {
            if (!e.target.classList.contains('remove-item')) return;

            const value = e.target.dataset.value;
            state.selectedItems = state.selectedItems.filter(item => item.value !== value);
            delete state.medicamentQuantities[value];

            const dropdownItem = DOM.dropdownList.querySelector(`[data-value="${value}"]`);
            if (dropdownItem) dropdownItem.classList.remove('selected');

            updateSelectedItemsDisplay();
        }

        function handleQuantityChange(e) {
            if (!e.target.classList.contains('quantity-input')) return;

            state.medicamentQuantities[e.target.dataset.value] = parseInt(e.target.value) || 1;
            updateHiddenInput();
        }

        function updateSelectedItemsDisplay() {
            const fragment = document.createDocumentFragment();

            state.selectedItems.forEach(item => {
                const div = document.createElement('div');
                div.className = 'selected-item';

                let html = `<span>${item.name}</span>`;

                if (item.type === 'medicament') {
                    const qty = state.medicamentQuantities[item.value] || 1;
                    html += `<input type="number" class="quantity-input" value="${qty}" min="1" data-value="${item.value}">`;
                }

                html += `<span class="remove-item" data-value="${item.value}">&times;</span>`;
                div.innerHTML = html;
                fragment.appendChild(div);
            });

            DOM.selectedItemsContainer.innerHTML = '';
            DOM.selectedItemsContainer.appendChild(fragment);
            updateHiddenInput();
        }

        function updateHiddenInput() {
            const categorized = state.selectedItems.reduce((acc, item) => {
                const [category, cleanValue] = getCategoryAndId(item.value);
                if (!acc[category]) acc[category] = [];

                const itemData = { id: cleanValue, name: item.name };
                if (item.type === 'medicament') {
                    itemData.quantity = state.medicamentQuantities[item.value] || 1;
                }

                acc[category].push(itemData);
                return acc;
            }, {});

            DOM.selectedItemsInput.value = JSON.stringify(categorized);
        }

        function getCategoryAndId(value) {
            const prefixes = {
                'service-': 'services',
                'examen-': 'examens',
                'package-': 'packages',
                'medicament-': 'medicaments'
            };

            for (const [prefix, category] of Object.entries(prefixes)) {
                if (value.startsWith(prefix)) return [category, value.replace(prefix, '')];
            }

            return ['autres', value];
        }

        function showDropdown(show) {
            requestAnimationFrame(() => {
                DOM.dropdownList.classList.toggle('show', show);
            });
        }

        function handleOutsideClick(e) {
            if (!e.target.closest('.position-relative')) showDropdown(false);
        }

        // ============================================
        // RENDEZ-VOUS (médecin uniquement)
        // ============================================
        function handleAppointmentChange() {
            if (this.value) {
                checkAppointmentAvailability(new Date(this.value));
            } else {
                hideAppointmentAlert();
            }
        }

        function checkAppointmentAvailability(requestedDate) {
            $.ajax({
                url: '/api/appointments/slots',
                method: 'GET',
                data: { date: requestedDate.toISOString().split('T')[0], employee_id: employeeId },
                success: function(data) {
                    handleAvailabilityResponse(data, requestedDate);
                },
                error: function() {
                    hideAppointmentAlert();
                }
            });
        }

        function handleAvailabilityResponse(data, requestedDate) {
            state.availableSlotsData = data;
            DOM.appointmentMessage.textContent = data.length > 0
                ? "Voici les créneaux disponibles :"
                : "Aucun créneau disponible pour cette date.";

            if (data.length > 0) displayAvailableSlots(requestedDate);
            else DOM.availableSlots.innerHTML = '';

            showAppointmentAlert();
        }

        function displayAvailableSlots(requestedDate) {
            const fragment = document.createDocumentFragment();

            state.availableSlotsData.forEach(item => {
                const [hours, minutes] = item.time.split(':').map(Number);
                const slotDate = new Date(requestedDate);
                slotDate.setHours(hours, minutes, 0, 0);

                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'slot-button';
                button.textContent = slotDate.toLocaleString('fr-FR', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' });
                button.onclick = () => {
                    DOM.appointmentInput.value = slotDate.toISOString().slice(0, 16);
                    hideAppointmentAlert();
                };

                fragment.appendChild(button);
            });

            DOM.availableSlots.innerHTML = '';
            DOM.availableSlots.appendChild(fragment);
        }

        function showAppointmentAlert() { DOM.appointmentAlert.style.display = 'block'; }
        function hideAppointmentAlert() { DOM.appointmentAlert.style.display = 'none'; }

        // ============================================
        // MODAL RÉSUMÉ
        // ============================================
        function updateSummary() {
            updatePatientSummary();
            updateAntecedentsSummary();
            updateConsultationSummary();
            updateServicesSummary();
            updateFollowUpSummary();
        }

        function updatePatientSummary() {
            const patientSelect = DOM.patientSelect[0];
            const selectedPatient = patientSelect.options[patientSelect.selectedIndex];
            setSummaryValue('summary-patient', selectedPatient ? selectedPatient.text : 'Non sélectionné');

            const fileSelect = DOM.fileSelect;
            const selectedFile = fileSelect ? fileSelect.options[fileSelect.selectedIndex] : null;
            setSummaryValue('summary-documents', selectedFile && selectedFile.value ? selectedFile.text : 'Aucun');
        }

        function updateAntecedentsSummary() {
            const antecedentsSection = document.getElementById('summary-antecedents');
            if (!isMedecin || !DOM.antecedentsSection || DOM.antecedentsSection.style.display === 'none') {
                if (antecedentsSection) antecedentsSection.style.display = 'none';
                return;
            }

            antecedentsSection.style.display = 'block';
            ['antecedents_medicaux', 'antecedents_chirurgicaux', 'antecedents_gyneco_obstetricaux',
             'antecedents_familiaux', 'allergies', 'traitements_cours'].forEach(field => {
                setSummaryValue('summary-' + field.replace(/_/g, '-'), getTextareaValue(field));
            });
        }

        function updateConsultationSummary() {
            // Si pas médecin, les champs n'existent pas dans le DOM → N/A
            setSummaryValue('summary-motif',       isMedecin ? getTextareaValue('motif')           : 'N/A');
            setSummaryValue('summary-signes',      isMedecin ? getTextareaValue('signes_cliniques') : 'N/A');
            setSummaryValue('summary-diagnostic',  isMedecin ? getTextareaValue('diagnostic')       : 'N/A');
            setSummaryValue('summary-observation', isMedecin ? getTextareaValue('observation')      : 'N/A');
        }

        function updateServicesSummary() {
            const categories = {
                services:    state.selectedItems.filter(i => i.value.startsWith('service-')),
                examens:     state.selectedItems.filter(i => i.value.startsWith('examen-')),
                packages:    state.selectedItems.filter(i => i.value.startsWith('package-')),
                medicaments: state.selectedItems.filter(i => i.value.startsWith('medicament-'))
            };

            setSummaryValue('summary-services',    formatItemsList(categories.services));
            setSummaryValue('summary-examens',     formatItemsList(categories.examens));
            setSummaryValue('summary-packages',    formatItemsList(categories.packages));
            setSummaryValue('summary-medicaments', formatMedicamentsList(categories.medicaments), true);
        }

        function updateFollowUpSummary() {
            if (!isMedecin) {
                setSummaryValue('summary-rdv',     'N/A');
                setSummaryValue('summary-medecin', 'N/A');
                return;
            }

            if (DOM.appointmentInput && DOM.appointmentInput.value) {
                setSummaryValue('summary-rdv', new Date(DOM.appointmentInput.value).toLocaleString('fr-FR'));
            } else {
                setSummaryValue('summary-rdv', 'Non planifié');
            }

            const medecinSelect = document.querySelector('select[name="medecin_suivi"]');
            if (medecinSelect) {
                setSummaryValue('summary-medecin', medecinSelect.options[medecinSelect.selectedIndex]?.text || 'Non assigné');
            }
        }

        // ============================================
        // UTILITAIRES
        // ============================================
        function setSummaryValue(id, value, isHtml = false) {
            const el = document.getElementById(id);
            if (!el) return;
            isHtml ? el.innerHTML = value : el.textContent = value;
        }

        function getTextareaValue(name) {
            const textarea = document.querySelector(`textarea[name="${name}"]`);
            return textarea ? (textarea.value.trim() || 'Non renseigné') : 'Non renseigné';
        }

        function formatItemsList(items) {
            return items.length > 0 ? items.map(i => i.name).join(', ') : 'Aucun';
        }

        function formatMedicamentsList(medicaments) {
            if (!medicaments.length) return 'Aucun';
            return medicaments.map(med => `${med.name} (x${state.medicamentQuantities[med.value] || 1})`).join('<br>');
        }

        function debounce(func, wait) {
            return function(...args) {
                clearTimeout(state.searchTimeout);
                state.searchTimeout = setTimeout(() => func.apply(this, args), wait);
            };
        }

        function handleConfirmConsultation() {
            DOM.summaryModal.modal('hide');
            DOM.form.submit();
        }

        // Lancement
        init();
    });
</script>
@endsection