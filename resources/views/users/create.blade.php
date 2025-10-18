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
            --danger: #ef4444;
            --border-radius: 0.75rem;
        }

        /* ============================================
        CONTAINER PRINCIPAL
        ============================================ */
        .user-create-container {
            max-width: 100%;
            margin: 0 auto;
        }

        /* ============================================
        HEADER CARD
        ============================================ */
        .card-header-create {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 1.5rem;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            text-align: center;
        }

        .card-header-create h4 {
            margin: 0;
            font-size: 1.75rem;
            font-weight: 600;
        }

        /* ============================================
        CARD BODY
        ============================================ */
        .card-body-create {
            padding: 2rem;
            background: white;
        }

        /* ============================================
        SECTIONS
        ============================================ */
        .form-section {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--primary);
        }

        .section-title {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.1rem;
        }

        .section-title i {
            font-size: 1.25rem;
        }

        /* ============================================
        FORM CONTROLS
        ============================================ */
        .form-label-enhanced {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .required-star {
            color: var(--danger);
            font-weight: 700;
        }

        .form-control-enhanced {
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            transition: all 0.2s ease;
            font-size: 0.95rem;
        }

        .form-control-enhanced:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            outline: none;
        }

        .form-control-enhanced:disabled {
            background: #f3f4f6;
            cursor: not-allowed;
        }

        /* ============================================
        PASSWORD FIELD
        ============================================ */
        .password-wrapper {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6b7280;
            transition: color 0.2s ease;
        }

        .toggle-password:hover {
            color: var(--primary);
        }

        .password-strength {
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            margin-top: 0.5rem;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            transition: all 0.3s ease;
            border-radius: 2px;
        }

        .strength-weak { 
            width: 33%; 
            background: var(--danger); 
        }

        .strength-medium { 
            width: 66%; 
            background: var(--warning); 
        }

        .strength-strong { 
            width: 100%; 
            background: var(--success); 
        }

        .password-hint {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }

        /* ============================================
        ROLE BADGE
        ============================================ */
        .role-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }

        .role-admin {
            background: #fef3c7;
            color: #92400e;
        }

        .role-medecin {
            background: #dbeafe;
            color: #1e40af;
        }

        .role-default {
            background: #e5e7eb;
            color: #374151;
        }

        /* ============================================
        BUTTONS
        ============================================ */
        .action-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px solid #e5e7eb;
        }

        .btn-enhanced {
            padding: 0.75rem 2rem;
            border-radius: 0.5rem;
            font-weight: 600;
            border: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary-enhanced {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
        }

        .btn-primary-enhanced:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
        }

        .btn-secondary-enhanced {
            background: white;
            color: #374151;
            border: 2px solid #e5e7eb;
        }

        .btn-secondary-enhanced:hover {
            background: #f9fafb;
            border-color: var(--primary);
        }

        .btn-loading {
            position: relative;
            pointer-events: none;
            opacity: 0.7;
        }

        .btn-loading::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            top: 50%;
            left: 50%;
            margin-left: -8px;
            margin-top: -8px;
            border: 2px solid #ffffff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spinner 0.6s linear infinite;
        }

        @keyframes spinner {
            to { transform: rotate(360deg); }
        }

        /* ============================================
        VALIDATION
        ============================================ */
        .is-invalid {
            border-color: var(--danger) !important;
        }

        .invalid-feedback {
            display: block;
            color: var(--danger);
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        .is-valid {
            border-color: var(--success) !important;
        }

        .valid-feedback {
            display: block;
            color: var(--success);
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        /* ============================================
        RESPONSIVE
        ============================================ */
        @media (max-width: 768px) {
            .card-body-create {
                padding: 1.5rem 1rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn-enhanced {
                width: 100%;
                justify-content: center;
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
                <li class="nav-home">
                    <a href="{{url('/')}}"><i class="icon-home"></i></a>
                </li>
                <li class="separator"><i class="icon-arrow-right"></i></li>
                <li class="nav-item"><a href="{{ url('/') }}">Admin</a></li>
                <li class="separator"><i class="icon-arrow-right"></i></li>
                <li class="nav-item"><a href="#">Utilisateurs</a></li>
                <li class="separator"><i class="icon-arrow-right"></i></li>
                <li class="nav-item">Créer</li>
            </ul>
        </div>

        <!-- Messages de validation -->
        @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong><i class="fas fa-exclamation-triangle me-2"></i>Erreurs de validation:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <!-- Card Principal -->
        <div class="user-create-container">
            <div class="card shadow-lg">
                <!-- Header -->
                <div class="card-header-create">
                    <h4>
                        <i class="fas fa-user-plus me-2"></i>
                        Créer un Nouvel Utilisateur
                    </h4>
                </div>
                <!-- Body -->
                <div class="card-body-create">
                    <form id="createUserForm" action="{{ route('users.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <!-- Section Informations Personnelles -->
                        <div class="form-section">
                            <div class="section-title">
                                <i class="fas fa-user"></i>
                                Informations Personnelles
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label-enhanced">
                                        Prénom <span class="required-star">*</span>
                                    </label>
                                    <input type="text" 
                                           name="first_name" 
                                           class="form-control form-control-enhanced @error('first_name') is-invalid @enderror" 
                                           placeholder="Ex: Jean"
                                           value="{{ old('first_name') }}"
                                           required>
                                    @error('first_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label-enhanced">
                                        Nom <span class="required-star">*</span>
                                    </label>
                                    <input type="text" 
                                           name="last_name" 
                                           class="form-control form-control-enhanced @error('last_name') is-invalid @enderror" 
                                           placeholder="Ex: Dupont"
                                           value="{{ old('last_name') }}"
                                           required>
                                    @error('last_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label-enhanced">
                                        <i class="fas fa-envelope text-primary me-1"></i>
                                        Email <span class="required-star">*</span>
                                    </label>
                                    <input type="email" 
                                           name="email" 
                                           class="form-control form-control-enhanced @error('email') is-invalid @enderror" 
                                           placeholder="exemple@hopital.com"
                                           value="{{ old('email') }}"
                                           required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label-enhanced">
                                        <i class="fas fa-phone text-success me-1"></i>
                                        Téléphone <span class="required-star">*</span>
                                    </label>
                                    <input type="tel" 
                                           name="phone" 
                                           class="form-control form-control-enhanced @error('phone') is-invalid @enderror" 
                                           placeholder="+224 XXX XX XX XX"
                                           value="{{ old('phone') }}"
                                           required>
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label class="form-label-enhanced">
                                        <i class="fas fa-map-marker-alt text-danger me-1"></i>
                                        Adresse
                                    </label>
                                    <textarea name="address" 
                                              class="form-control form-control-enhanced @error('address') is-invalid @enderror" 
                                              rows="2" 
                                              placeholder="Adresse complète...">{{ old('address') }}</textarea>
                                    @error('address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Section Rôle et Département -->
                        <div class="form-section">
                            <div class="section-title">
                                <i class="fas fa-briefcase"></i>
                                Rôle et Département
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label-enhanced">
                                        <i class="fas fa-user-shield text-warning me-1"></i>
                                        Rôle <span class="required-star">*</span>
                                    </label>
                                    <select name="role_id" 
                                            id="roleSelect"
                                            class="form-control form-control-enhanced @error('role_id') is-invalid @enderror" 
                                            required>
                                        <option value="">-- Sélectionner un rôle --</option>
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->name }}" {{ old('role_id') == $role->name ? 'selected' : '' }}>
                                                {{ ucfirst($role->name) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('role_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label-enhanced">
                                        <i class="fas fa-building text-info me-1"></i>
                                        Département
                                    </label>
                                    <select name="department_id" 
                                            class="form-control form-control-enhanced @error('department_id') is-invalid @enderror">
                                        <option value="">-- Sélectionner un département --</option>
                                        @foreach ($departments as $department)
                                            <option value="{{ $department->id }}" {{ old('department_id') == $department->id ? 'selected' : '' }}>
                                                {{ $department->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('department_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Section Sécurité -->
                        <div class="form-section">
                            <div class="section-title">
                                <i class="fas fa-lock"></i>
                                Sécurité du Compte
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label-enhanced">
                                        <i class="fas fa-key text-danger me-1"></i>
                                        Mot de passe <span class="required-star">*</span>
                                    </label>
                                    <div class="password-wrapper">
                                        <input type="password" 
                                               name="password" 
                                               id="password"
                                               class="form-control form-control-enhanced @error('password') is-invalid @enderror" 
                                               placeholder="••••••••"
                                               required>
                                        <span class="toggle-password" onclick="togglePassword('password')">
                                            <i class="fas fa-eye-slash"></i>
                                        </span>
                                    </div>
                                    <div class="password-strength" id="passwordStrength">
                                        <div class="password-strength-bar" id="passwordStrengthBar"></div>
                                    </div>
                                    <div class="password-hint">
                                        Minimum 8 caractères, incluant majuscules, minuscules et chiffres
                                    </div>
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label-enhanced">
                                        <i class="fas fa-key text-danger me-1"></i>
                                        Confirmer le mot de passe <span class="required-star">*</span>
                                    </label>
                                    <div class="password-wrapper">
                                        <input type="password" 
                                               name="password_confirmation" 
                                               id="passwordConfirm"
                                               class="form-control form-control-enhanced" 
                                               placeholder="••••••••"
                                               required>
                                        <span class="toggle-password" onclick="togglePassword('passwordConfirm')">
                                            <i class="fas fa-eye-slash"></i>
                                        </span>
                                    </div>
                                    <div class="password-hint">
                                        Doit correspondre au mot de passe ci-dessus
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Boutons d'action -->
                        <div class="action-buttons">
                            <a href="{{ url()->previous() }}" class="btn btn-secondary-enhanced">
                                <i class="fas fa-times"></i>
                                Annuler
                            </a>
                            <button type="submit" class="btn btn-primary-enhanced" id="submitBtn">
                                <i class="fas fa-save"></i>
                                Créer l'Utilisateur
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
            // TOGGLE PASSWORD VISIBILITY
            // ============================================
            window.togglePassword = function(fieldId) {
                const field = document.getElementById(fieldId);
                const icon = field.nextElementSibling.querySelector('i');
                
                if (field.type === 'password') {
                    field.type = 'text';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                } else {
                    field.type = 'password';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                }
            };

            // ============================================
            // PASSWORD STRENGTH INDICATOR
            // ============================================
            $('#password').on('input', function() {
                const password = $(this).val();
                const strengthBar = $('#passwordStrengthBar');
                let strength = 0;

                // Critères de force
                if (password.length >= 8) strength++;
                if (password.match(/[a-z]/)) strength++;
                if (password.match(/[A-Z]/)) strength++;
                if (password.match(/[0-9]/)) strength++;
                if (password.match(/[^a-zA-Z0-9]/)) strength++;

                // Mise à jour visuelle
                strengthBar.removeClass('strength-weak strength-medium strength-strong');
                
                if (strength <= 2) {
                    strengthBar.addClass('strength-weak');
                } else if (strength <= 4) {
                    strengthBar.addClass('strength-medium');
                } else {
                    strengthBar.addClass('strength-strong');
                }
            });

            // ============================================
            // PASSWORD MATCH VALIDATION
            // ============================================
            $('#passwordConfirm').on('input', function() {
                const password = $('#password').val();
                const confirmPassword = $(this).val();
                
                if (confirmPassword && password !== confirmPassword) {
                    $(this).addClass('is-invalid').removeClass('is-valid');
                } else if (confirmPassword) {
                    $(this).addClass('is-valid').removeClass('is-invalid');
                }
            });

            // ============================================
            // FORM SUBMISSION
            // ============================================
            $('#createUserForm').on('submit', function() {
                const btn = $('#submitBtn');
                btn.addClass('btn-loading').prop('disabled', true);
            });

            // ============================================
            // AUTO-DISMISS ALERTS
            // ============================================
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);
        });
    </script>
@endsection