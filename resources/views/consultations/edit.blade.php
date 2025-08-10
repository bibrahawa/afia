@extends('layouts.backend')
@section('style')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2-bootstrap-5-theme/1.3.0/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0066cc;
            --secondary-color: #f8f9fa;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --border-color: #e9ecef;
            --text-muted: #6c757d;
        }

        body {
            background-color: #f5f6fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .breadcrumb-container {
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        .breadcrumb {
            margin: 0;
            background: none;
            padding: 0;
        }

        .breadcrumb-item {
            color: var(--text-muted);
        }

        .breadcrumb-item.active {
            color: var(--primary-color);
            font-weight: 500;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color), #0052a3);
            color: white;
            padding: 20px;
            border: none;
        }

        .card-title {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .card-body {
            padding: 30px;
        }

        .section-title {
            color: var(--primary-color);
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--primary-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            border: 1px solid var(--border-color);
        }

        .form-label {
            font-weight: 500;
            color: #333;
            margin-bottom: 8px;
        }

        .form-control, .form-select {
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(0, 102, 204, 0.25);
        }

        /* Select2 styling */
        .select2-container--bootstrap-5 .select2-selection {
            border: 2px solid var(--border-color) !important;
            border-radius: 8px !important;
            padding: 8px 15px !important;
            min-height: 48px !important;
            font-size: 14px !important;
        }

        .select2-container--bootstrap-5.select2-container--focus .select2-selection {
            border-color: var(--primary-color) !important;
            box-shadow: 0 0 0 0.2rem rgba(0, 102, 204, 0.25) !important;
        }

        .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice {
            background-color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
            color: white !important;
            border-radius: 6px !important;
            padding: 2px 8px !important;
            margin: 2px !important;
        }

        .select2-dropdown {
            border: 1px solid var(--border-color) !important;
            border-radius: 8px !important;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1) !important;
        }

        .select2-results__option--highlighted {
            background-color: var(--primary-color) !important;
        }

        .select2-search__field {
            border: 1px solid var(--border-color) !important;
            border-radius: 6px !important;
            padding: 8px 12px !important;
        }

        .select-multiple {
            min-height: 120px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), #0052a3);
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 102, 204, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 500;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
            margin-top: 30px;
        }

        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
        }

        .required::after {
            content: " *";
            color: #dc3545;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .icon {
            color: var(--primary-color);
        }

        .select2-container {
            width: 100% !important;
        }

        .no-items {
            text-align: center;
            color: var(--text-muted);
            font-style: italic;
            padding: 20px;
        }

        /* Styles pour les antécédents */
        .antecedent-group {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background: #fafafa;
        }

        .antecedent-title {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .badge {
            font-size: 0.8rem;
            padding: 4px 8px;
        }

        .badge-info {
            background-color: #17a2b8;
            color: white;
        }

        .service-item {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .service-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .service-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .service-title {
            font-weight: 600;
            color: var(--primary-color);
            margin: 0;
        }

        /* Animation pour les transitions */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-5px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .selection-summary {
            animation: fadeIn 0.3s ease-out;
        }

    </style>

@endsection

@section('content')
    <div class="main-container">
        <!-- Breadcrumb -->
        <div class="breadcrumb-container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="#" class="text-decoration-none">
                            <i class="fas fa-home"></i> Accueil
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="#" class="text-decoration-none">Admin</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="#" class="text-decoration-none">Consultations</a>
                    </li>
                    <li class="breadcrumb-item active">Modifier</li>
                </ol>
            </nav>
        </div>

        <!-- Main Card -->
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">
                    <i class="fas fa-edit me-2"></i>
                    Modifier la consultation
                </h4>
            </div>

            <div class="card-body">
                <form id="updateConsultationForm" action="{{ route('consultation.update', $consultation) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <!-- Section Patient -->
                    <div class="form-section">
                        <h5 class="section-title">
                            <i class="fas fa-user-circle icon"></i>
                            Informations Patiente
                        </h5>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label required">Patiente</label>
                                    <select class="form-select select2" name="patient_id" required>
                                        @foreach($patients as $patient)
                                            <option value="{{ $patient->id }}" {{ $consultation->patient_id == $patient->id ? 'selected' : '' }}>
                                                {{ $patient->first_name." ".$patient->last_name }} - {{ $patient->phone }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section Antécédents (cachée par défaut) -->
                    <div class="form-section">
                            <h5 class="section-title">
                                <i class="section-notice"></i>
                                Veuillez modifier les antécédents médicaux de la patiente.
                            </h5>

                        <div class="row">
                            <div class="col-lg-6 col-md-12">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-history text-warning"></i>
                                        Antécédents Medicaux
                                    </label>
                                    <textarea name="antecedents_medicaux" class="form-control" rows="3" placeholder="Diabète, hypertension, chirurgies antérieures...">{{ old('antecedents_medicaux', $consultation->patient->antecedant->antecedents_medicaux) }}</textarea>
                                </div>
                            </div>

                            <div class="col-lg-6 col-md-12">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-notes-medical text-warning"></i>
                                        Antécédents Chirurgicaux
                                    </label>
                                    <textarea name="antecedents_chirurgicaux" class="form-control" rows="3" placeholder="Interventions chirurgicales antérieures, maladies chroniques (Diabète, hypertension)...">{{ old('antecedents_chirurgicaux', $consultation->patient->antecedant->antecedents_chirurgicaux) }}</textarea>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-12">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-venus-mars text-warning"></i>
                                        Antécédents Gyneco-Obstetricaux
                                    </label>
                                    <textarea name="antecedents_gyneco_obstetricaux" class="form-control" rows="3" placeholder="Grossesses, accouchements, cycles menstruels, maladies héréditaires familiales...">{{ old('antecedents_gyneco_obstetricaux', $consultation->patient->antecedant->antecedents_gyneco_obstetricaux) }}</textarea>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-12">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-users text-warning"></i>
                                        Antécédents Familiaux
                                    </label>
                                    <textarea name="antecedents_familiaux" class="form-control" rows="3" placeholder="Maladies héréditaires, antécédents familiaux...">{{ old('antecedents_familiaux', $consultation->patient->antecedant->antecedents_familiaux) }}</textarea>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-12">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-exclamation-triangle text-danger"></i>
                                        Allergies
                                    </label>
                                    <textarea name="allergies" class="form-control" rows="3" placeholder="Allergies médicamenteuses, alimentaires..."> {{ old('allergies', $consultation->patient->antecedant->allergies) }} </textarea>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-12">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-pills text-info"></i>
                                        Traitements en Cours
                                    </label>
                                    <textarea name="traitements_cours" class="form-control" rows="3" placeholder="Médicaments actuels, posologie...">{{ old('traitements_cours', $consultation->patient->antecedant->traitements_cours) }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section Services & Examens -->
                    <div class="form-section">
                        <h5 class="section-title">
                            <i class="fas fa-stethoscope icon"></i>
                            Services & Examens
                        </h5>

                        <!-- Services -->
                        <div class="service-item">
                            <div class="service-header">
                                <h6 class="service-title">
                                    <i class="fas fa-cog me-2"></i>
                                    Services
                                </h6>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="form-label">Sélectionner les services</label>
                                        <select class="form-select select2" name="services[]" multiple id="services-select">
                                            @foreach($services as $service)
                                                <option value="{{ $service->id }}" {{ in_array($service->id, $consultation->services->pluck('id')->toArray()) ? 'selected' : '' }}>
                                                    {{ $service->name }} - {{ number_format($service->amount, 0, ',', ' ') }} FCFA
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Examens -->
                        <div class="service-item">
                            <div class="service-header">
                                <h6 class="service-title">
                                    <i class="fas fa-microscope me-2"></i>
                                    Examens
                                </h6>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="form-label">Sélectionner les examens</label>
                                        <select class="form-select select2" name="tests[]" multiple id="tests-select">
                                            @foreach($tests as $test)
                                                <option value="{{ $test->id }}" {{ in_array($test->id, $consultation->tests->pluck('id')->toArray()) ? 'selected' : '' }}>
                                                    {{ $test->name }} - {{ number_format($test->amount, 0, ',', ' ') }} FCFA
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Packages -->
                        <div class="service-item">
                            <div class="service-header">
                                <h6 class="service-title">
                                    <i class="fas fa-box me-2"></i>
                                    Packages
                                </h6>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="form-label">Sélectionner les packages</label>
                                        <select class="form-select select2" name="packages[]" multiple id="packages-select">
                                            @foreach($packages as $package)
                                                <option value="{{ $package->id }}" {{ in_array($package->id, $consultation->packages->pluck('id')->toArray()) ? 'selected' : '' }}>
                                                    {{ $package->name }} - {{ number_format($package->price, 0, ',', ' ') }} FCFA
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section Consultation -->
                    <div class="form-section">
                        <h5 class="section-title">
                            <i class="fas fa-notes-medical icon"></i>
                            Détails de la consultation
                        </h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label required">Motif de la consultation</label>
                                    <textarea class="form-control" name="motif" rows="3" required>{{ old('motif', $consultation->motif) }}</textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label required">Signes cliniques</label>
                                    <textarea class="form-control" name="signes_cliniques" rows="3" required>{{ old('signes_cliniques', implode(', ', $consultation->signes_cliniques)) }}</textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label required">Diagnostic</label>
                                    <textarea class="form-control" name="diagnostic" rows="3" required>{{ old('diagnostic', $consultation->diagnostic) }}</textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Observation</label>
                                    <textarea class="form-control" name="observation" rows="3">{{ old('observation', $consultation->observation) }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section Prescription -->
                    <div class="form-section">
                        <h5 class="section-title">
                            <i class="fas fa-prescription-bottle-alt icon"></i>
                            Prescription & Suivi
                        </h5>

                        <!-- Médicaments -->
                        <div class="service-item">
                            <div class="service-header">
                                <h6 class="service-title">
                                    <i class="fas fa-pills me-2"></i>
                                    Médicaments
                                </h6>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="form-label">Prescription</label>
                                        <select class="form-select select2" name="medicaments[]" multiple id="medicaments-select">
                                            @foreach($medicaments as $medicament)
                                                <option value="{{ $medicament->id }}" {{ in_array($medicament->id, $consultation->medicaments->pluck('id')->toArray()) ? 'selected' : '' }}>
                                                    {{ $medicament->nom }} - {{ number_format($medicament->amount, 0, ',', ' ') }} FCFA
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Prochain rendez-vous</label>
                                    <input type="datetime-local" class="form-control" name="prochain_rdv" value="{{ \Carbon\Carbon::parse($consultation->prochain_rdv)->format('Y-m-d\TH:i') }}">
                                </div>
                            </div>
                        </div>
                    </div>  

                    <input type="hidden" name="invoiceId" value="{{ $consultation?->transaction?->invoice?->id }}">

                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <button type="button" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>
                            Annuler
                        </button>
                        <button type="submit" class="btn btn-primary" id="saveRowButton">
                            <i class="fas fa-save me-2"></i>
                            Mettre à jour
                            <div class="spinner-border spinner-border-sm text-light ms-2" role="status" id="saveLoader" style="display: none;">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')

<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        // Initialiser Select2 pour tous les selects
        $('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Rechercher...',
            allowClear: true,
            language: {
                noResults: () => "Aucun résultat trouvé",
                searching: () => "Recherche en cours...",
                removeAllItems: () => "Supprimer tous les éléments",
                removeItem: () => "Supprimer l'élément",
                search: () => "Rechercher"
            }
        });

        // Gestionnaire de soumission du formulaire
        $('#updateConsultationForm').on('submit', function(e) {
            const saveButton = $('#saveRowButton');
            const saveLoader = $('#saveLoader');

            // Désactiver le bouton et afficher le loader
            saveButton.prop('disabled', true);
            saveLoader.show();

            // Le formulaire sera soumis normalement
        });

        // Bouton Annuler
        $('.btn-secondary').on('click', function() {
            if (confirm('Êtes-vous sûr de vouloir annuler les modifications ?')) {
                window.history.back();
            }
        });

        // Personnaliser les placeholders pour chaque select
        $('#services-select').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Rechercher des services...',
            allowClear: true
        });

        $('#tests-select').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Rechercher des examens...',
            allowClear: true
        });

        $('#packages-select').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Rechercher des packages...',
            allowClear: true
        });

        $('#medicaments-select').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Rechercher des médicaments...',
            allowClear: true
        });
    });
</script>

@endsection
