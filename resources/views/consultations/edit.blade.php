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

        .slot-button {
            padding: 0.25rem 0.75rem;
            border: 1px solid var(--primary-color);
            background: white;
            color: var(--primary-color);
            border-radius: 1rem;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .slot-button:hover {
            background: var(--primary-color);
            color: white;
        }

        .slot-button.selected {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        body {
            background-color: #f5f6fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-container {
            /* max-width: 1200px; */
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

        /* Styles pour les médicaments avec quantité */
        .medicament-item {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .medicament-info {
            flex: 1;
        }

        .medicament-name {
            font-weight: 600;
            color: var(--primary-color);
        }

        .medicament-price {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .quantity-input {
            width: 80px;
            text-align: center;
        }

        .btn-quantity {
            width: 30px;
            height: 30px;
            border: 1px solid var(--border-color);
            background: white;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--primary-color);
            transition: all 0.3s ease;
        }

        .btn-quantity:hover {
            background: var(--primary-color);
            color: white;
        }

        .btn-remove-medicament {
            color: #dc3545;
            border: 1px solid #dc3545;
            background: white;
            width: 30px;
            height: 30px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-remove-medicament:hover {
            background: #dc3545;
            color: white;
        }

        .add-medicament-btn {
            background: var(--success-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            margin-top: 10px;
        }

        .add-medicament-btn:hover {
            background: #218838;
        }

        .available-slots {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        /* Styles pour les créneaux horaires */
        .time-slots {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }

        .time-slot {
            padding: 10px 15px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }

        .time-slot:hover {
            border-color: var(--primary-color);
            background: rgba(0, 102, 204, 0.1);
        }

        .time-slot.selected {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .time-slot.unavailable {
            background: #e9ecef;
            color: #6c757d;
            cursor: not-allowed;
            border-color: #dee2e6;
        }

        .time-slot.unavailable:hover {
            background: #e9ecef;
            border-color: #dee2e6;
        }

        .loading-slots {
            text-align: center;
            padding: 20px;
            color: var(--text-muted);
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
    <div class="container">
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

                            <!-- Médicaments avec quantité -->
                            <div class="service-item">
                                <div class="service-header">
                                    <h6 class="service-title">
                                        <i class="fas fa-pills me-2"></i>
                                        Médicaments
                                    </h6>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Ajouter un médicament</label>
                                    <select class="form-select select2" id="medicament-selector">
                                        <option value="">Sélectionner un médicament...</option>
                                        @foreach($medicaments as $medicament)
                                            <option value="{{ $medicament->id }}" 
                                                    data-name="{{ $medicament->nom }}" 
                                                    data-price="{{ $medicament->amount }}">
                                                {{ $medicament->nom }} - {{ number_format($medicament->amount, 0, ',', ' ') }} FCFA
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div id="selected-medicaments">
                                    @if($consultation->medicaments->count() > 0)
                                        @foreach($consultation->medicaments as $medicament)
                                            <div class="medicament-item" data-id="{{ $medicament->id }}">
                                                <div class="medicament-info">
                                                    <div class="medicament-name">{{ $medicament->nom }}</div>
                                                    <div class="medicament-price">{{ number_format($medicament->amount, 0, ',', ' ') }} FCFA</div>
                                                </div>
                                                <div class="quantity-control">
                                                    <button type="button" class="btn-quantity" onclick="decreaseQuantity(this)">
                                                        <i class="fas fa-minus"></i>
                                                    </button>
                                                    <input type="number" class="form-control quantity-input" 
                                                        name="medicament_quantities[{{ $medicament->id }}]" 
                                                        value="{{ $medicament->pivot->quantity ?? 1 }}" 
                                                        min="1" max="999">
                                                    <button type="button" class="btn-quantity" onclick="increaseQuantity(this)">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </div>
                                                <button type="button" class="btn-remove-medicament" onclick="removeMedicament(this)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <input type="hidden" name="medicaments[]" value="{{ $medicament->id }}">
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>

                            <!-- Section Rendez-vous améliorée -->
                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Date du prochain rendez-vous</label>
                                        <input type="date" class="form-control" id="rdv-date" name="rdv_date" 
                                            value="{{ \Carbon\Carbon::parse($consultation->prochain_rdv)->format('Y-m-d') }}"
                                            min="{{ date('Y-m-d') }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Heure du rendez-vous</label>
                                        <div id="time-slots-container">
                                            <div class="loading-slots">
                                                <i class="fas fa-clock me-2"></i>
                                                Sélectionnez une date pour voir les créneaux disponibles
                                            </div>
                                        </div>
                                        <input type="hidden" name="rdv_time" id="selected-time"
                                            value="{{ \Carbon\Carbon::parse($consultation->prochain_rdv)->format('H:i:s') }}"
                                        >
                                        <input type="hidden" name="prochain_rdv" id="full-datetime"
                                            value="{{ \Carbon\Carbon::parse($consultation->prochain_rdv)->format('Y-m-d') }}"
                                        >
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
    </div>
@endsection

@section('script')

<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        // Initialiser Select2 pour tous les selects
        $('.select2').select2({
            theme: 'bootstrap-5',
            // width: '100%',
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

        // Gestion de l'ajout de médicaments
        $('#medicament-selector').on('select2:select', function(e) {
            const data = e.params.data;
            const medicamentId = data.id;
            const medicamentName = $(this).find('option:selected').data('name');
            const medicamentPrice = $(this).find('option:selected').data('price');

            // Vérifier si le médicament n'est pas déjà ajouté
            if ($(`#selected-medicaments .medicament-item[data-id="${medicamentId}"]`).length === 0) {
                addMedicamentToList(medicamentId, medicamentName, medicamentPrice);
            }

            // Réinitialiser le sélecteur
            $(this).val(null).trigger('change');
        });

        // Gestion de la date de rendez-vous
        $('#rdv-date').on('change', function() {
            const selectedDate = $(this).val();
            if (selectedDate) {
                loadAvailableTimeSlots(selectedDate);
            }
        });

        // Charger les créneaux si une date est déjà sélectionnée
        const initialDate = $('#rdv-date').val();
        if (initialDate) {
            loadAvailableTimeSlots(initialDate);
        }

        // Gestionnaire de soumission du formulaire
        $('#updateConsultationForm').on('submit', function(e) {
            const saveButton = $('#saveRowButton');
            const saveLoader = $('#saveLoader');

            // Vérifier qu'un créneau horaire est sélectionné si une date est choisie
            const selectedDate = $('#rdv-date').val();
            const selectedTime = $('#selected-time').val();
            
            if (selectedDate && !selectedTime) {
                e.preventDefault();
                alert('Veuillez sélectionner un créneau horaire pour le rendez-vous.');
                return false;
            }

            // Construire la date-heure complète
            if (selectedDate && selectedTime) {
                $('#full-datetime').val(selectedDate + 'T' + selectedTime);
            }

            // Désactiver le bouton et afficher le loader
            saveButton.prop('disabled', true);
            saveLoader.show();
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
            // width: '100%',
            placeholder: 'Rechercher des services...',
            allowClear: true
        });

        $('#tests-select').select2({
            theme: 'bootstrap-5',
            // width: '100%',
            placeholder: 'Rechercher des examens...',
            allowClear: true
        });

        $('#packages-select').select2({
            theme: 'bootstrap-5',
            // width: '100%',
            placeholder: 'Rechercher des packages...',
            allowClear: true
        });

        $('#medicament-selector').select2({
            theme: 'bootstrap-5',
            // width: '100%',
            placeholder: 'Rechercher des médicaments...',
            allowClear: true
        });
    });

    // Fonction pour ajouter un médicament à la liste
    function addMedicamentToList(id, name, price) {
        const medicamentHtml = `
            <div class="medicament-item" data-id="${id}">
                <div class="medicament-info">
                    <div class="medicament-name">${name}</div>
                    <div class="medicament-price">${parseInt(price).toLocaleString('fr-FR')} FCFA</div>
                </div>
                <div class="quantity-control">
                    <button type="button" class="btn-quantity" onclick="decreaseQuantity(this)">
                        <i class="fas fa-minus"></i>
                    </button>
                    <input type="number" class="form-control quantity-input" 
                           name="medicament_quantities[${id}]" 
                           value="1" min="1" max="999">
                    <button type="button" class="btn-quantity" onclick="increaseQuantity(this)">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                <button type="button" class="btn-remove-medicament" onclick="removeMedicament(this)">
                    <i class="fas fa-trash"></i>
                </button>
                <input type="hidden" name="medicaments[]" value="${id}">
            </div>
        `;

        $('#selected-medicaments').append(medicamentHtml);
    }

    // Fonction pour augmenter la quantité
    function increaseQuantity(button) {
        const input = $(button).siblings('.quantity-input');
        let currentValue = parseInt(input.val()) || 1;
        const maxValue = parseInt(input.attr('max')) || 999;
        
        if (currentValue < maxValue) {
            input.val(currentValue + 1);
        }
    }

    // Fonction pour diminuer la quantité
    function decreaseQuantity(button) {
        const input = $(button).siblings('.quantity-input');
        let currentValue = parseInt(input.val()) || 1;
        const minValue = parseInt(input.attr('min')) || 1;
        
        if (currentValue > minValue) {
            input.val(currentValue - 1);
        }
    }

    // Fonction pour supprimer un médicament
    function removeMedicament(button) {
        if (confirm('Êtes-vous sûr de vouloir supprimer ce médicament ?')) {
            $(button).closest('.medicament-item').fadeOut(300, function() {
                $(this).remove();
            });
        }
    }

    // Fonction pour charger les créneaux horaires disponibles
    function loadAvailableTimeSlots(date) {
        const container = $('#time-slots-container');
        
        // Afficher le loading
        container.html(`
            <div class="loading-slots">
                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                Chargement des créneaux disponibles...
            </div>
        `);

        // Appel API pour récupérer les créneaux
        $.ajax({
            url: '/api/appointments/slots', // Route à créer
            method: 'GET',
            data: {
                date: date,
                employee_id: {{ auth()->id() }} // ID du médecin connecté
            },
            success: function(response) {
                displayTimeSlots(response, date);
            },
            error: function(xhr, status, error) {
                console.error('Erreur lors du chargement des créneaux:', error);
                container.html(`
                    <div class="text-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Erreur lors du chargement des créneaux. Veuillez réessayer.
                    </div>
                `);
            }
        });
    }

    // Fonction pour afficher les créneaux horaires
    function displayTimeSlots(availableSlotsFromApi, date) {
        const container = $('#time-slots-container');
        
        if (availableSlotsFromApi.length <= 0) {
            container.html(`
                <div class="no-items">
                    <i class="fas fa-calendar-times me-2"></i>
                    Aucun créneau disponible pour cette date
                </div>
            `);
            return;
        }

        // $slots = generateAvailableSlots(availableSlotsFromApi, date)

        let slotsHtml = '<div class="available-slots">';
        
        availableSlotsFromApi.forEach(slot => {
            // const isSelected = slot.time === currentTime;
            // const statusClass = slot.is_available ? (isSelected ? 'selected' : '') : 'unavailable';
            slotsHtml += `
                <div class="slot-button" 
                     data-time="${slot.time}" 
                     ${slot.is_available ? 'onclick="selectTimeSlot(this)"' : ''}>
                    <div>${slot.time}</div>
                </div>
            `;
        });
        
        slotsHtml += '</div>';
        container.html(slotsHtml);

        // Pré-sélectionner l'heure actuelle si elle existe
        if (false) {
            $('#selected-time').val(currentTime);
        }
    }


    function generateAvailableSlots(availableSlotsFromApi,requestedDate) {
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

    // Fonction pour sélectionner un créneau horaire
    function selectTimeSlot(element) {

        const $element = $(element);
        
        // Désélectionner tous les autres créneaux
        $('.slot-button').removeClass('selected');
        
        // Sélectionner le créneau cliqué
        $element.addClass('selected');
        
        // Mettre à jour l'input hidden
        const selectedTime = $element.data('time');
        $('#selected-time').val(selectedTime);
        
        console.log('Créneau sélectionné:', selectedTime);
    }

    // Validation des inputs de quantité
    $(document).on('input', '.quantity-input', function() {
        const value = parseInt($(this).val());
        const min = parseInt($(this).attr('min')) || 1;
        const max = parseInt($(this).attr('max')) || 999;
        
        if (value < min) {
            $(this).val(min);
        } else if (value > max) {
            $(this).val(max);
        }
    });

</script>
@endsection