@extends('layouts.backend')

@section('style')
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary-color: #64748b;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --light-bg: #f8fafc;
            --card-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --card-shadow-hover: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --border-radius: 0.75rem;
            --border-radius-lg: 1rem;
        }

        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
        }

        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 1rem;
            margin-top:50px;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            border-radius: var(--border-radius-lg);
            padding: 1rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
        }

        .page-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .page-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .consultation-card {
            background: white;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--card-shadow);
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid rgba(226, 232, 240, 0.8);
        }

        .consultation-card:hover {
            box-shadow: var(--card-shadow-hover);
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
            background: linear-gradient(90deg, var(--primary-color), var(--success-color));
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

        .card-body-service-seach {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 1.5em;
            /* La card va s'adapter automatiquement */
            min-height: 1000px;
            height: 500px;
            transition: all 0.3s ease;
        }

        /* .form-group-enhanced {
            margin-bottom: 1.5rem;
            position: relative;
        } */

        .form-label-enhanced {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .required-mark {
            color: var(--danger-color);
            font-weight: 700;
        }

        .form-control-enhanced {
            border: 2px solid #e5e7eb;
            border-radius: var(--border-radius);
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: #fafafa;
        }

        .form-control-enhanced:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            background: white;
            outline: none;
        }

        .form-control-enhanced.readonly {
            background: #f8fafc;
            color: var(--secondary-color);
            cursor: not-allowed;
        }

        .select-wrapper {
            position: relative;
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
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
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
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .btn-success-enhanced {
            background: linear-gradient(135deg, var(--success-color) 0%, #059669 100%);
            color: white;
        }

        .btn-success-enhanced:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }

        .btn-sm-enhanced {
            padding: 0.375rem 0.75rem;
            font-size: 0.8rem;
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
            border-radius: var(--border-radius-lg);
            box-shadow: var(--card-shadow);
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
        }

        .modal-content-enhanced {
            border-radius: var(--border-radius-lg);
            border: none;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .modal-header-enhanced {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            border-radius: var(--border-radius-lg) var(--border-radius-lg) 0 0;
            padding: 1.5rem;
        }

        .modal-header-enhanced h5 {
            font-weight: 600;
            margin: 0;
        }

        .summary-section {
            background: #f8fafc;
            border-radius: var(--border-radius);
            padding: 1.25rem;
            margin-bottom: 1rem;
        }

        .summary-section h6 {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .summary-item {
            display: flex;
            margin-bottom: 0.5rem;
        }

        .summary-label {
            font-weight: 500;
            color: #374151;
            min-width: 120px;
        }

        .summary-value {
            color: #6b7280;
            flex: 1;
        }

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
            border-color: var(--primary-color);
            background: #f0f8ff;
        }

        .icon-wrapper {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.5rem;
            height: 2.5rem;
            background: rgba(37, 99, 235, 0.1);
            border-radius: 50%;
            margin-right: 0.75rem;
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

        .status-required { background: #fef2f2; color: #dc2626; }
        .status-optional { background: #f0fdf4; color: #16a34a; }

        @media (max-width: 768px) {
            .main-container {
                padding: 1rem 0.5rem;
            }

            .page-header {
                padding: 1.5rem;
            }

            .card-body-custom {
                padding: 1rem;
            }

            .action-buttons {
                flex-direction: column;
            }
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>

<style>
    .card-body-service-seach {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        padding: 20px;
        /* La card va s'adapter automatiquement */
        min-height: auto;
        height: auto;
        transition: all 0.3s ease;
    }

    .status-badge {
        font-size: 11px;
        padding: 2px 8px;
        border-radius: 12px;
        font-weight: 500;
    }

    .status-optional {
        background: #e3f2fd;
        color: #1976d2;
    }

    .search-select {
        position: relative;
        width: 100%;
    }

    .search-input {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        font-size: 16px;
        outline: none;
        transition: border-color 0.2s;
        box-sizing: border-box;
    }

    .search-input:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
    }

    .selected-items {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 12px;
        min-height: auto;
        padding: 12px;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        background: white;
        align-items: flex-start;
        align-content: flex-start;
        /* Animation pour le redimensionnement */
        transition: all 0.3s ease;
        box-sizing: border-box;
    }

    .selected-items:empty::before {
        content: "Aucun élément sélectionné";
        color: #6c757d;
        font-style: italic;
        display: block;
        width: 100%;
        text-align: center;
        line-height: 16px;
    }

    .selected-item {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: black;
        padding: 8px 12px;
        border-radius: 20px;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
        animation: slideIn 0.3s ease-out;
        transition: all 0.2s ease;
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
        border-radius: 0 0 8px 8px;
        max-height: 300px;
        overflow-y: auto;
        z-index: 1000;
        display: none;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        /* Animation d'ouverture */
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
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .dropdown-item:last-child {
        border-bottom: none;
    }

    .dropdown-item:hover {
        background: #f8f9fa;
    }

    .dropdown-item.selected {
        background: #e7f3ff;
        color: #007bff;
        font-weight: 500;
    }

    .dropdown-item.selected::before {
        content: "✓";
        color: #007bff;
        font-weight: bold;
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
        z-index: 10;
    }

    .item-count {
        background: #6c757d;
        color: white;
        font-size: 12px;
        padding: 4px 10px;
        border-radius: 12px;
        margin-left: auto;
        flex-shrink: 0;
    }

    .no-results {
        padding: 20px;
        text-align: center;
        color: #6c757d;
        font-style: italic;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .card-body-service-seach {
            padding: 15px;
        }

        .selected-items {
            padding: 10px;
        }
    }

    /* Animation de hauteur pour la card */
    .card-expanding {
        overflow: hidden;
    }
</style>

<style>
    .dropdown-item {
        padding: 8px 12px;
        cursor: pointer;
        border-bottom: 1px solid #f0f0f0;
    }

    .dropdown-item:hover {
        background-color: #f8f9fa;
    }

    .item-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
    }

    .item-name {
        flex: 1;
        font-weight: 500;
    }

    .billing-options {
        margin-left: 15px;
    }

    .billing-checkbox {
        display: flex;
        align-items: center;
        cursor: pointer;
        font-size: 12px;
        color: #666;
    }

    .billing-checkbox input[type="checkbox"] {
        margin-right: 5px;
        transform: scale(0.9);
    }

    .billing-label {
        font-size: 11px;
        white-space: nowrap;
    }

    .checkmark {
        margin-left: 3px;
    }

    /* Style pour les éléments sélectionnés */
    .selected-item {
        display: inline-flex;
        align-items: center;
        background: #e3f2fd;
        border: 1px solid #2196f3;
        border-radius: 15px;
        padding: 5px 10px;
        margin: 2px;
        font-size: 12px;
    }

    .selected-item.not-billed {
        background: #fff3e0;
        border-color: #ff9800;
    }

    .selected-item .billing-status {
        font-size: 10px;
        margin-left: 5px;
        padding: 2px 6px;
        border-radius: 8px;
        background: rgba(255,255,255,0.7);
    }

    .selected-item.not-billed .billing-status {
        color: #e65100;
    }

    .selected-item .remove-item {
        margin-left: 8px;
        cursor: pointer;
        color: #666;
        font-weight: bold;
    }
</style>

@endsection

@section('content')

    <div class="main-container">
        <!-- En-tête de page -->
        <div class="page-header fade-in">
            <h1>
                <i class="fas fa-user-md"></i>
                Nouvelle Consultation Médicale <i>{{ auth()->user()->employee->department->name }}</i>
            </h1>
        </div>

        <form id="consultationForm" action="{{ route('consultation.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method("POST")
            <!-- Section Patient -->
            <div class="consultation-card fade-in">
                <div class="card-header-custom">
                    <h5>
                        <i class="fas fa-user text-primary"></i>
                        Informations du Patient
                    </h5>
                </div>
                <div class="card-body-custom">
                    <div class="row">
                        <div class="col-lg-6 col-md-12">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-user-circle text-primary"></i>
                                    Sélectionner un Patient
                                    <span class="required-mark">*</span>
                                    <span class="status-badge status-required ms-2">Obligatoire</span>
                                </label>
                                <div class="select-wrapper">
                                    <select class="form-control" id="patient_id" name="patient_id" required>
                                        <option value="">Choisir un patient...</option>
                                        @foreach ($patients as $patient)
                                            <option value="{{ $patient->id }}"> {{ $patient->first_name." ".$patient->middle_name." ".$patient->last_name." ".$patient->phone }} </option>
                                        @endforeach
                                    </select>
                                </div>
                                {{-- <button type="button" class="btn btn-outline-enhanced btn-sm-enhanced mt-2">
                                    <i class="fas fa-plus"></i>
                                    Nouveau Patient
                                </button> --}}
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-12">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-file-medical text-info"></i>
                                    Documents Associés
                                    <span class="status-badge status-optional ms-2">Optionnel</span>
                                </label>

                                <div class="select-wrapper">
                                    <select class="form-control" name="fichiers_enregistres[]">
                                        <option value="">Choisir un fichier...</option>
                                        @foreach ($fichiersPatients as $file)
                                            <option value="{{ $file->id }}">{{ $file->nom_fichier }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                {{-- <button type="button" class="btn btn-outline-enhanced btn-sm-enhanced mt-2 add-file-button" data-bs-toggle="modal" data-bs-target="#uploadFileModal">
                                    <i class="fas fa-upload"></i>
                                    Ajouter un Document
                                </button> --}}
                            </div>
                        </div>
                    </div>

                    <!-- Section Antécédents (cachée par défaut) -->
                    <div id="antecedents-section" class="antecedents-section" style="display: none;">
                        <div class="section-notice">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Première consultation :</strong> Veuillez remplir les antécédents médicaux de la patiente.
                        </div>

                        <div class="row">
                            <div class="col-lg-6 col-md-12">
                                <div class="form-group-enhanced">
                                    <label class="form-label-enhanced">
                                        <i class="fas fa-history text-warning"></i>
                                        Antécédents Medicaux
                                    </label>
                                    <textarea name="antecedents_medicaux" class="form-control" rows="3" placeholder="Diabète, hypertension, chirurgies antérieures..."></textarea>
                                </div>
                            </div>

                            <div class="col-lg-6 col-md-12">
                                <div class="form-group-enhanced">
                                    <label class="form-label-enhanced">
                                        <i class="fas fa-notes-medical text-warning"></i>
                                        Antécédents Chirurgicaux
                                    </label>
                                    <textarea name="antecedents_chirurgicaux" class="form-control" rows="3" placeholder="Interventions chirurgicales antérieures, maladies chroniques (Diabète, hypertension)..."></textarea>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-12">
                                <div class="form-group-enhanced">
                                    <label class="form-label-enhanced">
                                        <i class="fas fa-venus-mars text-warning"></i>
                                        Antécédents Gyneco-Obstetricaux
                                    </label>
                                    <textarea name="antecedents_gyneco_obstetricaux" class="form-control" rows="3" placeholder="Grossesses, accouchements, cycles menstruels, maladies héréditaires familiales..."></textarea>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-12">
                                <div class="form-group-enhanced">
                                    <label class="form-label-enhanced">
                                        <i class="fas fa-users text-warning"></i>
                                        Antécédents Familiaux
                                    </label>
                                    <textarea name="antecedents_familiaux" class="form-control" rows="3" placeholder="Maladies héréditaires, antécédents familiaux..."></textarea>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-12">
                                <div class="form-group-enhanced">
                                    <label class="form-label-enhanced">
                                        <i class="fas fa-exclamation-triangle text-danger"></i>
                                        Allergies
                                    </label>
                                    <textarea name="allergies" class="form-control" rows="3" placeholder="Allergies médicamenteuses, alimentaires..."></textarea>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-12">
                                <div class="form-group-enhanced">
                                    <label class="form-label-enhanced">
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

            <!-- Section Consultation Actuelle -->
            <div class="consultation-card fade-in">
                <div class="card-header-custom">
                    <h5>
                        <i class="fas fa-stethoscope text-primary"></i>
                        Détails de la Consultation
                    </h5>
                </div>
                <div class="card-body-custom">
                    <div class="row">
                        <div class="col-lg-6 col-md-12">
                            <div class="form-group-enhanced">
                                <label class="form-label-enhanced">
                                    <i class="fas fa-question-circle text-primary"></i>
                                    Motif de Consultation
                                    <span class="required-mark">*</span>
                                    <span class="status-badge status-required ms-2">Obligatoire</span>
                                </label>
                                <textarea name="motif" class="form-control" rows="3" required placeholder="Douleur thoracique, fièvre, contrôle de routine..."></textarea>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-12">
                            <div class="form-group-enhanced">
                                <label class="form-label-enhanced">
                                    <i class="fas fa-thermometer-half text-primary"></i>
                                    Examen physique
                                    <span class="required-mark">*</span>
                                    <span class="status-badge status-required ms-2">Obligatoire</span>
                                </label>
                                <textarea name="signes_cliniques" class="form-control" rows="3" required placeholder="Température: 38°C, Tension: 140/90, Pouls: 85 bpm..."></textarea>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-12">
                            <div class="form-group-enhanced">
                                <label class="form-label-enhanced">
                                    <i class="fas fa-diagnoses text-success"></i>
                                    Diagnostic
                                    <span class="required-mark">*</span>
                                    <span class="status-badge status-required ms-2">Obligatoire</span>
                                </label>
                                <textarea name="diagnostic" class="form-control" rows="3" required placeholder="Diagnostic principal et différentiel..."></textarea>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-12">
                            <div class="form-group-enhanced">
                                <label class="form-label-enhanced">
                                    <i class="fas fa-clipboard-check text-secondary"></i> Conduite tenue
                                    <span class="status-badge status-optional ms-2">Optionnel</span>
                                </label>
                                <textarea name="observation" class="form-control" rows="3" placeholder="Décisions prises, traitements administrés, suivis recommandés..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section Actes et Prescriptions -->
            <div class="consultation-card fade-in">
                <div class="card-header-custom">
                    <h5>
                        <i class="fas fa-prescription-bottle-alt text-primary"></i>
                        Actes, Examens, package & Prescriptions
                    </h5>
                </div>
                <div class="card-body-service-seach">
                    <div class="row">
                        <div class="col-lg-12 col-md-12">
                            <div class="form-group-enhanced">
                                <label class="form-label-enhanced">
                                    <i class="fas fa-vial text-info" style="margin-right: 8px; color: #17a2b8 !important;"></i>
                                    Actes, Examens, packages & Prescriptions
                                    <span class="status-badge status-optional ms-2">Optionnel</span>
                                </label>
                                <div class="search-select">
                                    <input type="hidden" name="selected_items" id="selected-items-input">
                                    <input type="hidden" name="billing_status" id="billing-status-input">
                                    <input type="text" class="search-input" placeholder="Rechercher services, examens, packages, médicaments..." id="search-input">
                                    <div class="selected-items" id="selected-items"></div>
                                    <div class="dropdown-list" id="dropdown-list">
                                        <div class="category">Services</div>
                                        @foreach ($services as $service)
                                            <div class="dropdown-item" data-value="service-{{$service->id}}" data-category="services" data-name="{{ $service->name }}">
                                                <div class="item-content">
                                                    <span class="item-name">{{ $service->name }}</span>
                                                    <div class="billing-options">
                                                        <label class="billing-checkbox">
                                                            <input type="checkbox" class="billing-check" data-item="service-{{$service->id}}" checked>
                                                            <span class="checkmark"></span>
                                                            <span class="billing-label"></span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach

                                        <div class="category">Examens Complémentaires</div>
                                        @foreach ($tests as $examen)
                                            <div class="dropdown-item" data-value="examen-{{$examen->id}}" data-category="examens" data-name="{{ $examen->name }}">
                                                <div class="item-content">
                                                    <span class="item-name">{{ $examen->name }}</span>
                                                    <div class="billing-options">
                                                        <label class="billing-checkbox">
                                                            <input type="checkbox" class="billing-check" data-item="examen-{{$examen->id}}" checked>
                                                            <span class="checkmark"></span>
                                                            <span class="billing-label"></span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach

                                        <div class="category">Packages</div>
                                        @foreach ($packages as $package)
                                            <div class="dropdown-item" data-value="package-{{$package->id}}" data-category="packages" data-name="{{ $package->name }}">
                                                <div class="item-content">
                                                    <span class="item-name">{{ $package->name }}</span>
                                                    <div class="billing-options">
                                                        <label class="billing-checkbox">
                                                            <input type="checkbox" class="billing-check" data-item="package-{{$package->id}}" checked>
                                                            <span class="checkmark"></span>
                                                            <span class="billing-label"></span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach

                                        <div class="category">Prescription Médicamenteuse</div>
                                        @foreach ($medicaments as $medicament)
                                            <div class="dropdown-item" data-value="medicament-{{$medicament->id}}" data-category="medicaments" data-name="{{ $medicament->nom }}">
                                                <div class="item-content">
                                                    <span class="item-name">{{ $medicament->nom }}</span>
                                                    <div class="billing-options">
                                                        <label class="billing-checkbox">
                                                            <input type="checkbox" class="billing-check" data-item="medicament-{{$medicament->id}}" checked>
                                                            <span class="checkmark"></span>
                                                            <span class="billing-label"></span>
                                                        </label>
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
            </div>

            <!-- Section Fichiers et Suivi -->
            <div class="consultation-card fade-in">
                <div class="card-header-custom">
                    <h5>
                        <i class="fas fa-folder-open text-primary"></i>
                        Documents & Suivi
                    </h5>
                </div>
                <div class="card-body-custom">
                    <div class="row">
                        <div class="col-lg-12 col-md-12">
                            <div class="form-group-enhanced">
                                <label class="form-label-enhanced">
                                    <i class="fas fa-calendar-alt text-warning"></i>
                                    Prochain Rendez-vous
                                    <span class="status-badge status-optional ms-2">Optionnel</span>
                                </label>
                                <input type="datetime-local" name="prochain_rdv" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Boutons d'action -->
            <div class="action-buttons fade-in">
                <button type="button" class="btn btn-outline-enhanced">
                    <i class="fas fa-times"></i>
                    Annuler
                </button>
                <button type="submit" class="btn btn-primary-enhanced">
                    <i class="fas fa-save"></i>
                    Enregistrer la Consultation
                </button>
            </div>
        </form>
    </div>

    <!-- Modals -->
    <!-- Modal Résumé de Consultation -->
    <div class="modal fade" id="consultationSummaryModal" tabindex="-1" aria-hidden="true">
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
                            <span class="summary-value" id="summary-patient-name">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Département :</span>
                            <span class="summary-value" id="summary-department-name">-</span>
                        </div>
                    </div>

                    <div class="summary-section" id="summary-antecedents-block" style="display: none;">
                        <h6><i class="fas fa-history"></i> Antécédents Médicaux</h6>
                        <div class="summary-item">
                            <span class="summary-label">Personnels :</span>
                            <span class="summary-value" id="summary-antecedents-personnels">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Allergies :</span>
                            <span class="summary-value" id="summary-allergies">-</span>
                        </div>
                    </div>

                    <div class="summary-section">
                        <h6><i class="fas fa-stethoscope"></i> Consultation</h6>
                        <div class="summary-item">
                            <span class="summary-label">Motif :</span>
                            <span class="summary-value" id="summary-motif">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Signes cliniques :</span>
                            <span class="summary-value" id="summary-signes-cliniques">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Diagnostic :</span>
                            <span class="summary-value" id="summary-diagnostic">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Observations :</span>
                            <span class="summary-value" id="summary-observation">-</span>
                        </div>
                    </div>

                    <div class="summary-section">
                        <h6><i class="fas fa-prescription-bottle-alt"></i> Prescriptions & Examens</h6>
                        <div class="summary-item">
                            <span class="summary-label">Services :</span>
                            <span class="summary-value" id="summary-services">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Examens :</span>
                            <span class="summary-value" id="summary-tests">-</span>
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
                        <h6><i class="fas fa-calendar-alt"></i> Suivi & Documents</h6>
                        <div class="summary-item">
                            <span class="summary-label">Documents :</span>
                            <span class="summary-value" id="summary-fichiers">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Prochain RDV :</span>
                            <span class="summary-value" id="summary-prochain-rdv">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Médecin suivi :</span>
                            <span class="summary-value" id="summary-prochain-medecin">-</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-enhanced" data-bs-dismiss="modal">
                        <i class="fas fa-edit"></i>
                        Modifier
                    </button>
                    <button type="button" class="btn btn-success-enhanced" id="confirmConsultation">
                        <i class="fas fa-check-circle"></i>
                        Confirmer et Enregistrer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Créer Package -->
    <div class="modal fade" id="createPackageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content modal-content-enhanced">
                <div class="modal-header modal-header-enhanced">
                    <h5 class="modal-title">
                        <i class="fas fa-box-open me-2"></i>
                        Créer un Nouveau Package
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="form-group-enhanced">
                        <label class="form-label-enhanced">
                            <i class="fas fa-tag text-primary"></i>
                            Nom du Package
                            <span class="required-mark">*</span>
                        </label>
                        <input type="text" id="packageName" class="form-control" required placeholder="Ex: Package Cardiologie Complet">
                    </div>
                    <div class="form-group-enhanced">
                        <label class="form-label-enhanced">
                            <i class="fas fa-concierge-bell text-info"></i>
                            Services Inclus
                        </label>
                        <select class="form-control" id="packageServices" multiple>
                            <option value="1">Consultation Cardiologue</option>
                            <option value="2">ECG</option>
                            <option value="3">Échographie</option>
                        </select>
                    </div>
                    <div class="form-group-enhanced">
                        <label class="form-label-enhanced">
                            <i class="fas fa-vial text-info"></i>
                            Examens Inclus
                        </label>
                        <select class="form-control" id="packageTests" multiple>
                            <option value="1">Bilan Sanguin Complet</option>
                            <option value="2">Radiographie Thoracique</option>
                            <option value="3">IRM Cardiaque</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-enhanced" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i>
                        Annuler
                    </button>
                    <button type="button" class="btn btn-primary-enhanced">
                        <i class="fas fa-plus"></i>
                        Créer le Package
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Upload Fichier -->
    <div class="modal fade" id="uploadFileModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content modal-content-enhanced">
                <div class="modal-header modal-header-enhanced">
                    <h5 class="modal-title">
                        <i class="fas fa-cloud-upload-alt me-2"></i>
                        Ajouter un Document
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="addFileForm" action="" method="post" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="patient_id" id="patient_file_id">
                    <div class="modal-body p-4">
                        <div class="file-upload-zone" onclick="document.getElementById('fileInput').click()">
                            <i class="fas fa-cloud-upload-alt text-primary fa-3x mb-3"></i>
                            <h6>Glissez votre fichier ici ou cliquez pour parcourir</h6>
                            <p class="text-muted mb-0">PDF, JPG, PNG, DOCX - Max 10MB</p>
                            <input type="file" id="fileInput" name="file" class="d-none" accept=".pdf,.jpg,.jpeg,.png,.docx">
                        </div>
                        <div class="form-group-enhanced mt-3">
                            <label class="form-label-enhanced">
                                <i class="fas fa-comment text-secondary"></i>
                                Description du Document
                            </label>
                            <input type="text" name="name" class="form-control" placeholder="Ex: Radiographie thoracique du 02/06/2025">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-enhanced" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i>
                            Annuler
                        </button>

                        <button type="submit" class="btn btn-primary-enhanced">
                            <i class="fas fa-upload"></i>
                            Uploader
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@section('script')

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Événement pour modifier un patient
        $(document).on('click', '.edit-button', function() {
            var patient = $(this).data('patient_update');

            // Mettre à jour le champ du modal
            $('#edit_id').val(patient.id);
            $('#edit_first_name').val(patient.first_name);
            $('#edit_last_name').val(patient.last_name);
            $('#edit_email').val(patient.email);
            $('#edit_phone').val(patient.phone);
            $('#edit_gender').val(patient.gender);
            $('#edit_marital_status').val(patient.marital_status);
            $('#edit_blood_group').val(patient.blood_group);
            $('#edit_birth_date').val(patient.birth_date);
            $('#edit_relative_name').val(patient.relative_name);
            $('#edit_relative_phone').val(patient.relative_phone);
            $('#edit_district').val(patient.district);
            $('#edit_location').val(patient.location);
            $('#edit_occupation').val(patient.occupation);
            $('#edit_department').val(patient.description);

            $('#editDepartmentForm').attr('action', '/patient/' + patient.id);

            // Afficher le modal
            $('#editRowModal').modal('show');
        });

        $(document).on('click', '.add-file-button', function() {
            var patient = $(this).data('patient');
            $('#patient_file_id').val(patient.id);

            // Mettre à jour l'action du formulaire de suppression avec l'ID du patient
            $('#addFileForm').attr('action', '/consultation/' + patient.id + '/add-file');

            // Afficher le modal de confirmation
            $('#uploadFileModal').modal('show');
        });

        // No $('.selectpicker').selectpicker(); needed here
        const patients = @json($patients);
        const fichierPatients = @json($fichiersPatients);

        // --- Animation d'entrée pour les cartes ---
        const cards = document.querySelectorAll('.consultation-card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = 'all 0.6s ease';

            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 150);
        });

        // --- Gestion de la sélection de patient et affichage des antécédents ---
        const patientSelect = document.getElementById('patient_id');
        const antecedentsSection = document.getElementById('antecedents-section');

        patientSelect.addEventListener('change', function() {
            const patientId = this.value;

            if (patientId) {
                // Trouver le patient sélectionné directement
                const selectedPatient = patients.find(patient => patient.id == patientId);

                if (selectedPatient && selectedPatient.first_visit == true) {
                    antecedentsSection.style.display = 'block';
                    antecedentsSection.scrollIntoView({ behavior: 'smooth', block: 'center' });

                    // Optionnel : rendre les textareas requis
                    // const textareas = antecedentsSection.querySelectorAll('textarea');
                    // textareas.forEach(textarea => {
                    //     textarea.setAttribute('required', 'required');
                    // });
                } else {
                    antecedentsSection.style.display = 'none';
                    const textareas = antecedentsSection.querySelectorAll('textarea');
                    textareas.forEach(textarea => {
                        textarea.removeAttribute('required');
                    });
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

            } else {
                // Aucun patient sélectionné
                antecedentsSection.style.display = 'none';
                antecedentsSection.querySelectorAll('textarea').forEach(textarea => {
                    textarea.removeAttribute('required');
                });

                // Réinitialiser le select des fichiers
                const fileSelect = document.querySelector('select[name="fichiers_enregistres[]"]');
                if (fileSelect) {
                    while (fileSelect.options.length > 1) {
                        fileSelect.remove(1);
                    }
                }
            }
        });

        // --- Gestion du modal de résumé (using jQuery for Bootstrap modal events) ---
        // Access Bootstrap modal events using jQuery on the modal element itself
        $('#consultationSummaryModal').on('show.bs.modal', function() { // Using jQuery for Bootstrap event
            const patientName = document.querySelector('#patient_id option:checked')?.textContent || 'Non sélectionné';

            document.getElementById('summary-patient-name').textContent = patientName;

            const antecedentsVisible = antecedentsSection.style.display !== 'none';
            const antecedentsBlock = document.getElementById('summary-antecedents-block');

            if (antecedentsVisible) {
                antecedentsBlock.style.display = 'block';
                document.getElementById('summary-antecedents-personnels').textContent =
                    document.querySelector('textarea[name="antecedents_personnels"]').value || 'Non renseigné';
                document.getElementById('summary-allergies').textContent =
                    document.querySelector('textarea[name="allergies"]').value || 'Non renseigné';
            } else {
                antecedentsBlock.style.display = 'none';
            }

            document.getElementById('summary-motif').textContent =
                document.querySelector('textarea[name="motif"]').value || 'Non renseigné';
            document.getElementById('summary-signes-cliniques').textContent =
                document.querySelector('textarea[name="signes_cliniques"]').value || 'Non renseigné';
            document.getElementById('summary-diagnostic').textContent =
                document.querySelector('textarea[name="diagnostic"]').value || 'Non renseigné';
            document.getElementById('summary-observation').textContent =
                document.querySelector('textarea[name="observation"]').value || 'Aucune';

            const getSelectedOptions = (selector) => {
                const select = document.querySelector(selector);
                if (!select) return 'Aucun';
                const selectedOptions = Array.from(select.selectedOptions);
                return selectedOptions.length > 0
                    ? selectedOptions.map(option => option.textContent).join(', ')
                    : 'Aucun';
            };

            document.getElementById('summary-services').textContent = getSelectedOptions('select[name="services[]"]');
            document.getElementById('summary-tests').textContent = getSelectedOptions('select[name="tests[]"]');
            document.getElementById('summary-packages').textContent = getSelectedOptions('select[name="packages[]"]');
            document.getElementById('summary-medicaments').textContent = getSelectedOptions('select[name="medicaments[]"]');
            document.getElementById('summary-fichiers').textContent = getSelectedOptions('select[name="fichiers_enregistres[]"]');

            const prochainRdvInput = document.querySelector('input[name="prochain_rdv"]');
            const prochainRdv = prochainRdvInput ? prochainRdvInput.value : '';
            document.getElementById('summary-prochain-rdv').textContent =
                prochainRdv ? new Date(prochainRdv).toLocaleString('fr-FR', {
                    year: 'numeric', month: 'numeric', day: 'numeric',
                    hour: 'numeric', minute: 'numeric'
                }) : 'Non planifié';

        });

        // --- Confirmation d'enregistrement ---
        document.getElementById('confirmConsultation').addEventListener('click', function() {
            const button = this;
            const originalText = button.innerHTML;

            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement...';
            button.disabled = true;

            setTimeout(() => {
                button.innerHTML = '<i class="fas fa-check"></i> Enregistré !';
                button.classList.remove('btn-success-enhanced');
                button.classList.add('btn-success');

                // Simuler l'enregistrement de la consultation

                setTimeout(() => {
                    const modalInstance = bootstrap.Modal.getInstance(document.getElementById('consultationSummaryModal'));
                    if (modalInstance) {
                        modalInstance.hide();
                    } else {
                        $('#consultationSummaryModal').modal('hide');
                    }

                    showNotification('Consultation enregistrée avec succès !', 'success');

                    button.innerHTML = originalText;
                    button.disabled = false;
                    button.classList.remove('btn-success');
                }, 1500);
            }, 2000);
        });

        // --- Gestion de l'upload de fichier ---
        const fileInput = document.getElementById('fileInput');
        const uploadZone = document.querySelector('.file-upload-zone');

        if (fileInput && uploadZone) {
            fileInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    uploadZone.innerHTML = `
                        <i class="fas fa-file-alt text-success fa-2x mb-2"></i>
                        <h6 class="text-success">${file.name}</h6>
                        <p class="text-muted mb-0">Fichier sélectionné - ${(file.size / 1024 / 1024).toFixed(2)} MB</p>
                    `;
                    uploadZone.style.borderColor = 'var(--success-color, green)';
                    uploadZone.style.backgroundColor = '#f0fdf4';
                }
            });
        }

        // --- Fonction pour afficher des notifications ---
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} position-fixed`;
            notification.style.cssText = `
                top: 20px;
                right: 20px;
                z-index: 9999;
                min-width: 300px;
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
                border: none;
                animation: slideInRight 0.5s ease forwards;
                display: flex;
                align-items: center;
                gap: 8px;
            `;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i>
                ${message}
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            `;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.remove();
            }, 5000);
        }

    });

    // --- Animation CSS pour les notifications ---
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        .btn-close {
            box-sizing: content-box;
            width: 1em;
            height: 1em;
            padding: .25em .25em;
            color: #000;
            background: transparent url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23000'%3e%3cpath d='M.293.293a1 1 0 0 1 1.414 0L8 6.586 14.293.293a1 1 1 1 1 1.414 1.414L9.414 8l6.293 6.293a1 1 0 0 1-1.414 1.414L8 9.414l-6.293 6.293a1 1 0 0 1-1.414-1.414L6.586 8 .293 1.707a1 1 0 0 1 0-1.414z'/%3e%3c/svg%3e") center/1em auto no-repeat;
            border: 0;
            border-radius: .25rem;
            opacity: .5;
        }
        .btn-close:hover {
            color: #000;
            text-decoration: none;
            opacity: .75;
        }
    `;
    document.head.appendChild(style);

</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('search-input');
        const dropdownList = document.getElementById('dropdown-list');
        const selectedItemsContainer = document.getElementById('selected-items');
        const selectedItemsInput = document.getElementById('selected-items-input');
        const cardBody = document.querySelector('.card-body-service-seach');

        let selected = []; // Stores {value, text} for display
        let billingStatus = {}; // Stores {value: boolean} for billing status

        // Cache all dropdown items for efficient filtering
        const allDropdownItems = Array.from(dropdownList.querySelectorAll('.dropdown-item'));

        // --- Utility Functions ---

        // Animates the card's resize for a smoother user experience
        function animateCardResize() {
            cardBody.style.transition = 'all 0.3s ease';
            // The card will naturally adjust its height due to `height: auto`
            setTimeout(() => {
                cardBody.style.transition = '';
            }, 300);
        }

        // Updates the displayed selected items and the hidden input fields
        function updateSelectedItemsDisplay() {
            selectedItemsContainer.innerHTML = ''; // Clear current display

            selected.forEach(item => {
                const isBilled = billingStatus[item.value];
                const selectedItemDiv = document.createElement('div');
                selectedItemDiv.className = `selected-item ${!isBilled ? 'not-billed' : ''}`;
                selectedItemDiv.innerHTML = `
                    <span>${item.text}</span>
                    <span class="billing-status">${isBilled ? 'Facturé' : 'Non facturé'}</span>
                    <span class="remove-item" data-value="${item.value}">&times;</span>
                `;
                selectedItemsContainer.appendChild(selectedItemDiv);
            });

            updateItemCount(); // Update the count of selected items
            updateHiddenInputs(); // Update the hidden input fields for form submission
        }

        // Updates the count of selected items displayed
        function updateItemCount() {
            const existingCount = document.querySelector('.item-count');
            if (existingCount) {
                existingCount.remove();
            }

            if (selected.length > 0) {
                const countElement = document.createElement('div');
                countElement.className = 'item-count';
                countElement.textContent = `${selected.length} sélectionné${selected.length > 1 ? 's' : ''}`;
                selectedItemsContainer.appendChild(countElement);
            }
        }

        // Updates the hidden input fields (`selected-items-input` and `billing-status-input`)
        function updateHiddenInputs() {
            // Transform selected items into the desired categorized format
            const categorizedSelected = selected.reduce((acc, item) => {
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
                acc[category].push({
                    value: cleanValue,
                    text: item.text
                });

                return acc;
            }, {});

            selectedItemsInput.value = JSON.stringify(categorizedSelected);
            // Store billing status for all selected items
            document.getElementById('billing-status-input').value = JSON.stringify(billingStatus);
        }

        // Filters dropdown items based on the search input
        function filterDropdownItems(filter) {
            let hasVisibleItems = false;
            let currentCategory = null;

            // Hide all categories first
            dropdownList.querySelectorAll('.category').forEach(cat => {
                cat.style.display = 'none';
            });

            allDropdownItems.forEach(item => {
                const text = item.textContent.toLowerCase();
                const category = item.dataset.category;
                const isVisible = text.includes(filter);

                item.style.display = isVisible ? 'flex' : 'none';

                if (isVisible) {
                    hasVisibleItems = true;

                    // Show the category if needed
                    if (category !== currentCategory) {
                        const categoryElement = item.previousElementSibling;
                        if (categoryElement && categoryElement.classList.contains('category')) {
                            categoryElement.style.display = 'block';
                        }
                        currentCategory = category;
                    }
                }
            });

            // Display "No results" message if no items are visible and there's a filter
            let noResults = dropdownList.querySelector('.no-results');
            if (!hasVisibleItems && filter) {
                if (!noResults) {
                    noResults = document.createElement('div');
                    noResults.className = 'no-results';
                    noResults.textContent = 'Aucun résultat trouvé';
                    dropdownList.appendChild(noResults);
                }
                noResults.style.display = 'block';
            } else if (noResults) {
                noResults.style.display = 'none';
            }
        }

        // --- Event Listeners ---

        // Show dropdown and filter on search input focus
        searchInput.addEventListener('focus', () => {
            dropdownList.classList.add('show');
            filterDropdownItems(searchInput.value.toLowerCase());
        });

        // Filter dropdown items as the user types
        searchInput.addEventListener('input', (e) => {
            filterDropdownItems(e.target.value.toLowerCase());
        });

        // Handle item selection and billing checkbox changes in the dropdown
        dropdownList.addEventListener('click', (e) => {
            // Handle billing checkbox change
            if (e.target.classList.contains('billing-check')) {
                const itemValue = e.target.dataset.item;
                billingStatus[itemValue] = e.target.checked;
                // If the item is already selected, update its display
                if (selected.some(item => item.value === itemValue)) {
                    updateSelectedItemsDisplay();
                    animateCardResize();
                }
                return; // Prevent item selection logic from running
            }

            // Handle item selection
            const itemElement = e.target.closest('.dropdown-item');
            if (itemElement) {
                const value = itemElement.dataset.value;
                const text = itemElement.textContent.replace(/(Facturé|Non facturé)/, '').trim(); // Remove billing status from text
                const billingCheck = itemElement.querySelector('.billing-check');

                if (!selected.some(item => item.value === value)) {
                    selected.push({
                        value,
                        text
                    });
                    // Initialize billing status if not already set (e.g., from pre-selected items)
                    if (billingCheck) {
                        billingStatus[value] = billingCheck.checked;
                    } else {
                        billingStatus[value] = true; // Default to billed if no checkbox
                    }

                    itemElement.classList.add('selected'); // Mark as selected in dropdown
                    updateSelectedItemsDisplay();
                    animateCardResize();
                }

                searchInput.value = '';
                filterDropdownItems(''); // Clear filter and show all items
                // Keep focus on search input if needed, or close dropdown
                // searchInput.focus();
                dropdownList.classList.remove('show');
            }
        });

        // Remove selected item when its 'x' button is clicked
        selectedItemsContainer.addEventListener('click', (e) => {
            if (e.target.classList.contains('remove-item')) {
                const valueToRemove = e.target.dataset.value;
                selected = selected.filter(item => item.value !== valueToRemove);
                delete billingStatus[valueToRemove]; // Also remove its billing status

                const dropdownItem = dropdownList.querySelector(`[data-value="${valueToRemove}"]`);
                if (dropdownItem) {
                    dropdownItem.classList.remove('selected');
                }

                updateSelectedItemsDisplay();
                animateCardResize();
            }
        });

        // Close dropdown when clicking outside the search-select area
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.search-select')) {
                dropdownList.classList.remove('show');
            }
        });

        // Keyboard navigation (Escape to close dropdown)
        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                dropdownList.classList.remove('show');
                searchInput.blur();
            }
        });

        // --- Global Functions (for external interaction if needed) ---

        window.getSelectedValues = function() {
            return selected.map(item => item.value);
        };

        window.getSelectedItems = function() {
            return selected.map(item => ({
                value: item.value,
                text: item.text
            }));
        };

        window.setSelectedValues = function(values) {
            selected = [];
            billingStatus = {}; // Clear existing billing status

            allDropdownItems.forEach(item => {
                item.classList.remove('selected');
                if (values.includes(item.dataset.value)) {
                    const value = item.dataset.value;
                    const text = item.textContent.replace(/(Facturé|Non facturé)/, '').trim();
                    selected.push({
                        value,
                        text
                    });

                    const billingCheck = item.querySelector('.billing-check');
                    if (billingCheck) {
                        billingStatus[value] = billingCheck.checked;
                    } else {
                        billingStatus[value] = true; // Default to billed
                    }
                    item.classList.add('selected');
                }
            });

            updateSelectedItemsDisplay();
            animateCardResize();
        };

        // --- Initialization ---

        // Observe selectedItemsContainer for resize to trigger card animation if needed
        if (window.ResizeObserver) {
            const resizeObserver = new ResizeObserver(() => {
                // No direct action needed here as animateCardResize is called on changes
            });
            resizeObserver.observe(selectedItemsContainer);
        }
    });
</script>

@endsection
