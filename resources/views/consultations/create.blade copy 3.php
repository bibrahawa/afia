@extends('layouts.backend')

@section('content')


<div class="container">

    <div class="app-container">
        <div class="page-header animate__animated animate__fadeIn">
            <h4 class="page-title">
                <i class="fas fa-stethoscope me-2"></i>Nouvelle Consultation
            </h4>
            {{-- <p class="text-muted">Département: <span class="badge-department"><i class="fas fa-venus"></i>GYNÉCOLOGIQUE</span></p> --}}
        </div>

        <!-- Mobile Tabs Navigation -->
        <div class="mobile-tabs">
            <div class="mobile-tab active" data-target="patient-info">Patient</div>
            <div class="mobile-tab" data-target="exams-info">Examens</div>
            <div class="mobile-tab" data-target="files-info">Fichiers</div>
            <div class="mobile-tab" data-target="diagnosis-info">Diagnostic</div>
        </div>

        <div class="row">
            <!-- Informations de base -->
            <div class="col-lg-6 mobile-section active" id="patient-info">
                <div class="card animate-card" style="animation-delay: 0.1s">
                    <div class="card-header">
                        <i class="fas fa-user-circle"></i>Informations du patient
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label">Patient</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <select class="form-select selectpicker" name="patient_id" data-live-search="true">
                                    <option selected>HAWAOU Barry</option>
                                </select>
                            </div>
                        </div>

                    </div>
                </div>



                 <!-- Options & Examens -->
                 <div class="mobile-section" id="exams-info">
                    <div class="card animate-card" style="animation-delay: 0.3s">
                        <div class="card-header">
                            <i class="fas fa-vial"></i>Examens & Package
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <div class="exam-line">
                                    <label class="form-label">Examens (facultatif)</label>
                                    <div class="input-group">
                                        <div class="input-group-text">
                                            <input class="form-check-input mt-0" type="checkbox" value="">
                                        </div>
                                        <select class="form-select">
                                            <option selected>Nothing selected</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mb-0">
                                <label class="form-label">Service</label>
                                <div class="select-wrapper">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-hospital"></i></span>
                                        <select class="form-select">
                                            <option selected>Nothing selected</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mb-0">
                                <label class="form-label">Package (facultatif)</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-box"></i></span>
                                    <select class="form-select">
                                        <option selected>Nothing selected</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Fichiers et service -->
                <div class="mobile-section" id="files-info">
                    <div class="card animate-card" style="animation-delay: 0.2s">
                        <div class="card-header">
                            <i class="fas fa-file-medical"></i>Documents & Service
                        </div>
                        <div class="card-body">

                            <div class="form-group">
                                <label class="form-label">Fichiers (résultats labo, radios...)</label>
                                <div class="file-upload">
                                    <div class="upload-icon">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                    </div>
                                    <h6 class="mb-2">Déposez vos fichiers ici</h6>
                                    <p class="text-muted mb-0">ou <span class="text-primary fw-bold">parcourir</span> vos fichiers</p>
                                </div>
                            </div>

                            <div class="form-group mb-0">
                                <label class="form-label">Médecin pour le prochain RDV</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user-md"></i></span>
                                    <select class="form-select">
                                        <option selected>Dr Ibrahim Barry</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group mb-0">
                                <label class="form-label">Prochain rendez-vous</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user-md"></i></span>
                                    <input type="datetime-local" name="prochain_rdv" class="form-control">
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>

            <div class="col-lg-6">
                <!-- Diagnostic info -->
                <div class="mobile-section" id="diagnosis-info">
                    <div class="card animate-card" style="animation-delay: 0.5s">
                        <div class="card-header">
                            <i class="fas fa-clipboard-list"></i>Consultation & Diagnostic
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label class="form-label">Motif de la consultation</label>
                                <textarea class="form-control" placeholder="Saisissez le motif de la consultation"></textarea>
                            </div>

                            <div class="form-group mb-0">
                                <label class="form-label">Diagnostic</label>
                                <textarea class="form-control" placeholder="Saisissez le diagnostic"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="card animate-card" style="animation-delay: 0.6s">
                        <div class="card-header">
                            <i class="fas fa-notes-medical"></i>Signes Cliniques & Observations
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label class="form-label">Signes cliniques (séparés par virgule)</label>
                                <textarea class="form-control" placeholder="Saisissez les signes cliniques"></textarea>
                            </div>

                            <div class="form-group mb-0">
                                <label class="form-label">Observation (facultatif)</label>
                                <textarea class="form-control" placeholder="Saisissez vos observations"></textarea>
                            </div>
                        </div>
                    </div>
                    <!-- Prescription -->
                    <div class="card animate-card" style="animation-delay: 0.4s">
                        <div class="card-header">
                            <i class="fas fa-prescription"></i>Prescription
                        </div>
                        <div class="card-body">
                            <div class="form-group mb-0">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-pills"></i></span>
                                    <select class="form-select">
                                        <option selected>Nothing selected</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>


        </div>

            <div class="row mt-4 animate__animated animate__fadeInUp">
                <div class="col-12 text-end">
                    <button type="button" class="btn btn-secondary me-2">
                        <i class="fas fa-times me-2"></i>Annuler
                    </button>
                    <button type="button" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Enregistrer
                    </button>
                </div>
            </div>

    </div>

</div>


@endsection

@section('style')
    <style>
        :root {
            --primary: #4762F9;
            --primary-light: #EEF1FF;
            --primary-dark: #2F3DB6;
            --success: #2AC769;
            --warning: #FFC555;
            --danger: #FF6B6B;
            --background: #F5F7FF;
            --card-bg: #FFFFFF;
            --text-primary: #333B69;
            --text-secondary: #8A94A6;
            --border-radius: 16px;
            --transition: all 0.3s ease;
        }

        body {
            background-color: var(--background);
            font-family: 'Segoe UI', 'Roboto', sans-serif;
            color: var(--text-primary);
            /* padding: 1rem; */
        }

        .app-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0.5rem;
        }

        .page-header {
            margin-bottom: 2rem;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .page-title {
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
            font-size: 1.5rem;
        }

        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: 0 10px 30px rgba(71, 98, 249, 0.07);
            transition: var(--transition);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(71, 98, 249, 0.1);
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-light) 0%, #FFFFFF 100%);
            padding: 1.25rem 1.5rem;
            font-weight: 600;
            color: var(--primary-dark);
            border-bottom: none;
            display: flex;
            align-items: center;
            border-radius: var(--border-radius) var(--border-radius) 0 0 !important;
        }

        .card-header i {
            margin-right: 10px;
            color: var(--primary);
        }

        .card-body {
            padding: 1.5rem;
            background-color: var(--card-bg);
        }

        .form-control, .form-select, .input-group-text, .selectpicker {
            border-radius: 10px;
            padding: 0rem 1rem;
            border: 1px solid #E2E8F0;
            font-size: 0.95rem;
            transition: var(--transition);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(71, 98, 249, 0.1);
        }

        .input-group-text {
            background-color: var(--primary-light);
            color: var(--primary);
            border: none;
        }

        label.form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            font-size: 0.9rem;
        }

        .file-upload {
            background: linear-gradient(135deg, #FFFFFF 0%, var(--primary-light) 100%);
            border: 2px dashed rgba(71, 98, 249, 0.2);
            border-radius: var(--border-radius);
            padding: 2rem 1rem;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
        }

        .file-upload:hover {
            border-color: var(--primary);
            background: linear-gradient(135deg, var(--primary-light) 0%, #FFFFFF 100%);
        }

        .file-upload .upload-icon {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 1rem;
            transition: var(--transition);
        }

        .file-upload:hover .upload-icon {
            transform: scale(1.1);
        }

        .badge-department {
            background-color: var(--primary-light);
            color: var(--primary);
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            transition: var(--transition);
        }

        .badge-department i {
            margin-right: 5px;
        }

        textarea.form-control {
            min-height: 120px;
            resize: none;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            transition: var(--transition);
        }

        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(71, 98, 249, 0.2);
        }

        .btn-secondary {
            background-color: #EDF2F7;
            border-color: #EDF2F7;
            color: var(--text-secondary);
        }

        .btn-secondary:hover {
            background-color: #E2E8F0;
            border-color: #E2E8F0;
            color: var(--text-primary);
        }

        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        /* Responsive improvements */
        @media (max-width: 767.98px) {
            .d-flex.align-items-center {
                flex-direction: column;
                align-items: flex-start !important;
            }

            .d-flex.align-items-center .input-group {
                width: 100%;
                margin-top: 0.5rem;
            }

            .d-flex.align-items-center label.form-label {
                margin-bottom: 0.5rem;
            }

            .card-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .card-header .badge-department {
                margin-top: 0.5rem;
            }

            .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }
        }

        /* Animations */
        .animate-card {
            animation: fadeInUp 0.5s ease forwards;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Custom styling for inline elements */
        .exam-line {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .exam-line label {
            white-space: nowrap;
            margin-bottom: 0;
        }

        .exam-line .input-group {
            flex: 1;
            min-width: 200px;
        }

        /* Tabs for mobile view */
        .mobile-tabs {
            display: none;
            overflow-x: auto;
            white-space: nowrap;
            margin-bottom: 1rem;
        }

        .mobile-tab {
            display: inline-block;
            padding: 0.5rem 1rem;
            margin-right: 0.5rem;
            background-color: #EDF2F7;
            border-radius: 50px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .mobile-tab.active {
            background-color: var(--primary);
            color: white;
        }

        @media (max-width: 767.98px) {
            .mobile-tabs {
                display: block;
            }

            .mobile-section {
                display: none;
            }

            .mobile-section.active {
                display: block;
                animation: fadeIn 0.3s ease forwards;
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Enhanced select boxes */
        .select-wrapper {
            position: relative;
        }

        .select-wrapper::after {
            content: "\f107";
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            color: var(--primary);
        }
    </style>
@endsection

@section('script')
<script>
    // Animation on load
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.animate-card');
        cards.forEach((card, index) => {
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100 + (index * 100));
        });

        // Mobile tabs functionality
        const tabs = document.querySelectorAll('.mobile-tab');
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                // Remove active class from all tabs and sections
                document.querySelectorAll('.mobile-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.mobile-section').forEach(s => s.classList.remove('active'));

                // Add active class to clicked tab
                tab.classList.add('active');

                // Show corresponding section
                const targetId = tab.getAttribute('data-target');
                document.getElementById(targetId).classList.add('active');
            });
        });

        // Add hover effect to cards
        document.querySelectorAll('.card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
            });

            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    });
</script>

@endsection
