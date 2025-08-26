@extends('layouts.backend')

@section('style')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --light-bg: #f8fafc;
            --shadow: 0 1px 3px rgba(0,0,0,0.1);
            --shadow-hover: 0 4px 6px rgba(0,0,0,0.1);
            --border-radius: 0.75rem;
        }

        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
        }

        .main-container {
            max-width: 1400px;
            margin: 50px auto 0;
            padding: 2rem 1rem;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
        }

        .page-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .consultation-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid rgba(226, 232, 240, 0.8);
        }

        .consultation-card:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-2px);
        }

        .card-header-custom {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            padding: 1.25rem 1.5rem;
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
        }

        .card-body-custom {
            padding: 1.5rem;
        }

        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .required-mark {
            color: var(--danger);
            font-weight: 700;
        }

        .form-control {
            border: 2px solid #e5e7eb;
            border-radius: var(--border-radius);
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: #fafafa;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            background: white;
            outline: none;
        }

        .btn-enhanced {
            padding: 0.625rem 1.25rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            font-size: 0.9rem;
            border: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
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
        }

        .action-buttons {
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
        }

        /* Service Selection Styles */
        .service-search-container {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            min-height: auto;
        }

        .search-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: var(--border-radius);
            font-size: 16px;
            outline: none;
            transition: border-color 0.2s;
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
            transition: all 0.3s ease;
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
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            animation: slideIn 0.3s ease-out;
        }

        .quantity-input {
            width: 50px;
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
            font-size: 16px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }

        .remove-item:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .dropdown-list {
            position: flex;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 2px solid #e9ecef;
            border-top: none;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            box-shadow: var(--shadow-hover);
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.2s ease;
        }

        .dropdown-list.show {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        .dropdown-item {
            padding: 12px 16px;
            cursor: pointer;
            border-bottom: 1px solid #f1f3f4;
            transition: background 0.2s;
        }

        .dropdown-item:hover {
            background: #f8f9fa;
        }

        .dropdown-item.selected {
            background: #e7f3ff;
            color: var(--primary);
        }

        .category {
            padding: 8px 16px;
            background: #f8f9fa;
            font-weight: bold;
            color: #495057;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e9ecef;
            position: sticky;
            top: 0;
        }

        /* Modal Styles */
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
        }

        .summary-item {
            display: grid;
            grid-template-columns: 150px 1fr;
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
        }

        .summary-value {
            color: #6b7280;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-required { 
            background: #fef2f2; 
            color: #dc2626; 
        }

        .status-optional { 
            background: #f0fdf4; 
            color: #16a34a; 
        }

        /* Alert styles */
        .alert-appointment {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-bottom: 1rem;
            color: #92400e;
        }

        .available-slots {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .slot-button {
            padding: 0.25rem 0.75rem;
            border: 1px solid var(--primary);
            background: white;
            color: var(--primary);
            border-radius: 1rem;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .slot-button:hover {
            background: var(--primary);
            color: white;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 1rem 0.5rem;
            }
            .action-buttons {
                flex-direction: column;
            }
            .summary-item {
                grid-template-columns: 1fr;
                gap: 0.25rem;
            }
        }
    </style>
@endsection

@section('content')
    <div class="main-container">
        <div class="page-header">
            <h1>
                <i class="fas fa-user-md"></i>
                Nouvelle Consultation Médicale {{ auth()->user()->employee->department->name }}
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
                                        <option value="{{ $patient->id }}"> {{ $patient->getFullNameAttribute()." ".$patient->phone }} </option>
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
                </div>
            </div>

            <!-- Section Consultation -->
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
                                <textarea name="motif" class="form-control" rows="3" required placeholder="Douleur thoracique, fièvre, contrôle de routine..."></textarea>
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
                                <textarea name="signes_cliniques" class="form-control" rows="3" required placeholder="Température: 38°C, Tension: 140/90, Pouls: 85 bpm..."></textarea>
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
                                <textarea name="diagnostic" class="form-control" rows="3" required placeholder="Diagnostic principal et différentiel..."></textarea>
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
                            <div class="selected-items" id="selected-items"></div>
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
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section Rendez-vous -->
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

    {{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script> --}}
    
    <script>
        
        document.addEventListener('DOMContentLoaded', function() {

            // Initialize selectpicker
            $('.selectpicker').selectpicker();

            // Mock data for demonstration
            const patients = @json($patients);
            const fichierPatients = @json($fichiersPatients);

            // Service selection management
            const searchInput = document.getElementById('search-input');
            const dropdownList = document.getElementById('dropdown-list');
            const selectedItemsContainer = document.getElementById('selected-items');
            const selectedItemsInput = document.getElementById('selected-items-input');

            let selectedItems = [];
            let medicamentQuantities = {};
            let availableSlotsFromApi = {};

            // Patient selection handler
            $('#patient_id').on('change', function() {
                const patientId = parseInt(this.value);
                const patient = patients.find(p => p.id === patientId);
                const antecedentsSection = document.getElementById('antecedents-section');

                if (patient && patient.first_visit) {
                    antecedentsSection.style.display = 'block';
                    antecedentsSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    antecedentsSection.style.display = 'none';
                }

                // Gestion des fichiers - nettoyer et repeupler les options
                const fileSelect = document.querySelector('select[name="fichiers_enregistres[]"]');

                if (fileSelect) {
                    // Garder seulement la première option par défaut
                    while (fileSelect.options.length > 1) {
                        fileSelect.remove(1);
                    }

                    // Ajouter les nouvelles options (filtrer par patient si nécessaire)
                    const patientFiles = fichierPatients.filter(file => file.patient_id == patientId);
                    patientFiles.forEach(file => {
                        const option = document.createElement('option');
                        option.value = file.id;
                        option.textContent = file.nom_fichier;
                        fileSelect.appendChild(option);
                    });
                }

            });

            // Search functionality
            searchInput.addEventListener('focus', () => {
                dropdownList.classList.add('show');
            });

            searchInput.addEventListener('input', (e) => {
                const filter = e.target.value.toLowerCase();
                const items = dropdownList.querySelectorAll('.dropdown-item');
                
                items.forEach(item => {
                    const text = item.textContent.toLowerCase();
                    item.style.display = text.includes(filter) ? 'block' : 'none';
                });
            });

            // Item selection
            dropdownList.addEventListener('click', (e) => {
                const item = e.target.closest('.dropdown-item');
                if (!item) return;

                const value = item.dataset.value;
                const name = item.dataset.name;
                const type = item.dataset.type;

                if (!selectedItems.find(s => s.value === value)) {
                    const selectedItem = { value, name, type };
                    selectedItems.push(selectedItem);

                    if (type === 'medicament') {
                        medicamentQuantities[value] = 1;
                    }

                    updateSelectedItemsDisplay();
                    item.classList.add('selected');
                }

                searchInput.value = '';
                dropdownList.classList.remove('show');
            });
            

            function updateSelectedItemsDisplay() {
                selectedItemsContainer.innerHTML = '';

                selectedItems.forEach(item => {
                    const div = document.createElement('div');
                    div.className = 'selected-item';
                    
                    let content = `<span>${item.name}</span>`;
                    
                    if (item.type === 'medicament') {
                        content += `<input type="number" class="quantity-input" value="${medicamentQuantities[item.value] || 1}" min="1" data-value="${item.value}">`;
                    }
                    
                    content += `<span class="remove-item" data-value="${item.value}">&times;</span>`;
                    
                    div.innerHTML = content;
                    selectedItemsContainer.appendChild(div);
                });

                updateHiddenInput();
            }

            function updateHiddenInput() {
                const categorized = selectedItems.reduce((acc, item) => {
                    let category = 'autres';
                    let cleanValue = item.value;

                    if (item.value.startsWith('service-')) {
                        category = 'services';
                        cleanValue = item.value.replace('service-', '');
                    } else if (item.value.startsWith('examen-')) {
                        category = 'examens';
                        cleanValue = item.value.replace('examen-', '');
                    } else if (item.value.startsWith('package-')) {
                        category = 'packages';
                        cleanValue = item.value.replace('package-', '');
                    } else if (item.value.startsWith('medicament-')) {
                        category = 'medicaments';
                        cleanValue = item.value.replace('medicament-', '');
                    }

                    if (!acc[category]) acc[category] = [];
                    
                    const itemData = {
                        id: cleanValue,
                        name: item.name
                    };

                    if (item.type === 'medicament') {
                        itemData.quantity = medicamentQuantities[item.value] || 1;
                    }

                    acc[category].push(itemData);
                    return acc;
                }, {});

                selectedItemsInput.value = JSON.stringify(categorized);
            }

            // Remove item handler
            selectedItemsContainer.addEventListener('click', (e) => {
                if (e.target.classList.contains('remove-item')) {
                    const value = e.target.dataset.value;
                    selectedItems = selectedItems.filter(item => item.value !== value);
                    delete medicamentQuantities[value];

                    const dropdownItem = dropdownList.querySelector(`[data-value="${value}"]`);
                    if (dropdownItem) {
                        dropdownItem.classList.remove('selected');
                    }

                    updateSelectedItemsDisplay();
                }
            });

            // Quantity change handler
            selectedItemsContainer.addEventListener('input', (e) => {
                if (e.target.classList.contains('quantity-input')) {
                    const value = e.target.dataset.value;
                    medicamentQuantities[value] = parseInt(e.target.value) || 1;
                    updateHiddenInput();
                }
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.position-relative')) {
                    dropdownList.classList.remove('show');
                }
            });

            // Appointment availability checker
            const appointmentInput = document.getElementById('prochain_rdv');
            const appointmentAlert = document.getElementById('appointment-alert');
            const appointmentMessage = document.getElementById('appointment-message');
            const availableSlots = document.getElementById('available-slots');

            appointmentInput.addEventListener('change', function () {
                if (this.value) {
                    checkAppointmentAvailability(new Date(this.value));
                } else {
                    appointmentAlert.style.display = 'none';
                }
            });

            function checkAppointmentAvailability(requestedDate) {
                $.ajax({
                    url: '/api/appointments/slots',
                    method: 'GET',
                    data: {
                        date: requestedDate.toISOString().split('T')[0],
                        employee_id: @json(auth()->user()->id)
                    },
                    success: function (data) {
                        availableSlotsFromApi = data;

                        if (availableSlotsFromApi.length > 0) {
                            appointmentMessage.textContent =
                                "Voici les créneaux disponibles :";

                            const slots = generateAvailableSlots(requestedDate);

                            availableSlots.innerHTML = '';

                            slots.forEach(slot => {
                                const button = document.createElement('button');
                                button.type = 'button';
                                button.className = 'slot-button';
                                button.textContent = slot.display;
                                button.onclick = () => selectTimeSlot(slot.value);
                                availableSlots.appendChild(button);
                            });


                            appointmentAlert.style.display = 'block';
                        } else {
                            appointmentMessage.textContent =
                                "Aucun créneau disponible pour cette date.";
                            appointmentAlert.style.display = 'block';
                            availableSlots.innerHTML = '';
                        }
                    },
                    error: function (err) {
                        console.error("Error:", err);
                        appointmentAlert.style.display = 'none';
                    }
                });
            }

            function generateAvailableSlots(requestedDate) {
                const slots = [];

                availableSlotsFromApi.forEach(item => {
                    // item.time est une string "HH:MM"
                    const [hours, minutes] = item.time.split(':').map(Number);

                    // clone la date demandée
                    const slotDate = new Date(requestedDate);
                    slotDate.setHours(hours, minutes, 0, 0);

                    slots.push({
                        value: slotDate.toISOString().slice(0, 16), // pour input datetime-local
                        display: slotDate.toLocaleString('fr-FR', {
                            day: '2-digit',
                            month: '2-digit',
                            hour: '2-digit',
                            minute: '2-digit'
                        })
                    });
                });

                return slots;
            }

            function selectTimeSlot(value) {
                appointmentInput.value = value;
                appointmentAlert.style.display = 'none';
            }

            // Summary modal handler
            $('#consultationSummaryModal').on('show.bs.modal', function() {
                updateSummary();
            });

            function updateSummary() {
                // Patient info
                const patientSelect = document.getElementById('patient_id');
                document.getElementById('summary-patient').textContent = 
                    patientSelect.options[patientSelect.selectedIndex]?.text || 'Non sélectionné';

                // Documents
                const documentsSelect = document.querySelector('select[name="fichiers_enregistres[]"]');
                document.getElementById('summary-documents').textContent = 
                    documentsSelect.options[documentsSelect.selectedIndex]?.text || 'Aucun';

                // Antecedents
                const antecedentsVisible = document.getElementById('antecedents-section').style.display !== 'none';
                const antecedentsSection = document.getElementById('summary-antecedents');
                
                if (antecedentsVisible) {
                    antecedentsSection.style.display = 'block';
                    document.getElementById('summary-antecedents-medicaux').textContent = 
                        document.querySelector('textarea[name="antecedents_medicaux"]').value || 'Non renseigné';
                    document.getElementById('summary-antecedents-chirurgicaux').textContent = 
                        document.querySelector('textarea[name="antecedents_chirurgicaux"]').value || 'Non renseigné';
                    document.getElementById('summary-antecedents-gyneco').textContent = 
                        document.querySelector('textarea[name="antecedents_gyneco_obstetricaux"]').value || 'Non renseigné';
                    document.getElementById('summary-antecedents-familiaux').textContent = 
                        document.querySelector('textarea[name="antecedents_familiaux"]').value || 'Non renseigné';
                    document.getElementById('summary-allergies').textContent = 
                        document.querySelector('textarea[name="allergies"]').value || 'Non renseigné';
                    document.getElementById('summary-traitements').textContent = 
                        document.querySelector('textarea[name="traitements_cours"]').value || 'Non renseigné';
                } else {
                    antecedentsSection.style.display = 'none';
                }

                // Consultation details
                document.getElementById('summary-motif').textContent = 
                    document.querySelector('textarea[name="motif"]').value || 'Non renseigné';
                document.getElementById('summary-signes').textContent = 
                    document.querySelector('textarea[name="signes_cliniques"]').value || 'Non renseigné';
                document.getElementById('summary-diagnostic').textContent = 
                    document.querySelector('textarea[name="diagnostic"]').value || 'Non renseigné';
                document.getElementById('summary-observation').textContent = 
                    document.querySelector('textarea[name="observation"]').value || 'Aucune';

                // Services and prescriptions
                updateServicesSummary();

                // Follow-up
                const rdvInput = document.querySelector('input[name="prochain_rdv"]');
                if (rdvInput && rdvInput.value) {
                    const rdvDate = new Date(rdvInput.value);
                    document.getElementById('summary-rdv').textContent = rdvDate.toLocaleString('fr-FR');
                } else {
                    document.getElementById('summary-rdv').textContent = 'Non planifié';
                }

                const medecinSelect = document.querySelector('select[name="medecin_suivi"]');
                document.getElementById('summary-medecin').textContent = 
                    medecinSelect.options[medecinSelect.selectedIndex]?.text || 'Non assigné';
            }

            function updateServicesSummary() {
                const services = selectedItems.filter(item => item.value.startsWith('service-'));
                const examens = selectedItems.filter(item => item.value.startsWith('examen-'));
                const packages = selectedItems.filter(item => item.value.startsWith('package-'));
                const medicaments = selectedItems.filter(item => item.value.startsWith('medicament-'));

                document.getElementById('summary-services').textContent = 
                    services.length > 0 ? services.map(s => s.name).join(', ') : 'Aucun';
                
                document.getElementById('summary-examens').textContent = 
                    examens.length > 0 ? examens.map(e => e.name).join(', ') : 'Aucun';
                
                document.getElementById('summary-packages').textContent = 
                    packages.length > 0 ? packages.map(p => p.name).join(', ') : 'Aucun';
                
                document.getElementById('summary-medicaments').innerHTML = 
                    medicaments.length > 0 ? medicaments.map(m => {
                        const quantity = medicamentQuantities[m.value] || 1;
                        return `${m.name} (x${quantity})`;
                    }).join('<br>') : 'Aucun';
            }

            // Confirm consultation
            document.getElementById('confirmConsultation').addEventListener('click', function() {
                $('#consultationSummaryModal').modal('hide');
                // const button = this;
                // const originalText = button.innerHTML;

                // button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement...';
                // button.disabled = true;

                // // Simulate API call
                // setTimeout(() => {
                //     button.innerHTML = '<i class="fas fa-check"></i> Enregistré !';
                    
                //     setTimeout(() => {
                //         $('#consultationSummaryModal').modal('hide');
                //         showNotification('Consultation enregistrée avec succès !', 'success');
                        
                //         button.innerHTML = originalText;
                //         button.disabled = false;
                //     }, 1500);
                // }, 2000);
            });

            function showNotification(message, type = 'info') {
                const notification = document.createElement('div');
                notification.className = `alert alert-${type} position-fixed`;
                notification.style.cssText = `
                    top: 20px; right: 20px; z-index: 9999;
                    min-width: 300px; box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
                    animation: slideInRight 0.5s ease forwards;
                `;
                notification.innerHTML = `
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i>
                    ${message}
                    <button type="button" class="btn-close ms-auto" onclick="this.parentElement.remove()"></button>
                `;

                document.body.appendChild(notification);
                setTimeout(() => notification.remove(), 5000);
            }

            // Add animation keyframes
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideInRight {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
            `;
            document.head.appendChild(style);
        });
    </script>

@endsection