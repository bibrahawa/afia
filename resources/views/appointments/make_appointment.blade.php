<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prise de Rendez-vous - Clinique</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('front/css/rdv.css') }}">

    <style>
        .professional-schedule {
            font-size: 12px;
            color: #4facfe;
            font-weight: 600;
            margin-top: 8px;
            padding: 4px 8px;
            background: rgba(79, 172, 254, 0.1);
            border-radius: 4px;
            display: inline-block;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 10px;
        }

        @media (min-width: 768px) {
            .container {
                padding: 20px;
            }
        }

        .professional-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
        }

        @media (min-width: 640px) {
            .professional-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (min-width: 1024px) {
            .professional-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        .available-dates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 12px;
            max-height: 450px;
            overflow-y: auto;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 12px;
        }

        @media (min-width: 640px) {
            .available-dates-grid {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
                gap: 15px;
            }
        }

        .date-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            min-height: 100px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .date-card:hover {
            border-color: #4facfe;
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(79, 172, 254, 0.25);
        }

        .date-card.selected {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            border-color: #4facfe;
            color: white;
            transform: scale(1.05);
            box-shadow: 0 8px 20px rgba(79, 172, 254, 0.4);
        }

        .date-card-day {
            font-size: 32px;
            font-weight: bold;
            line-height: 1;
            margin-bottom: 8px;
        }

        .date-card-month {
            font-size: 13px;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
            opacity: 0.9;
            margin-bottom: 4px;
        }

        .date-card-weekday {
            font-size: 11px;
            text-transform: capitalize;
            opacity: 0.75;
            font-weight: 500;
        }

        .date-card.selected .date-card-day,
        .date-card.selected .date-card-month,
        .date-card.selected .date-card-weekday {
            color: white;
        }

        #no-dates-message {
            background: white;
            border: 2px dashed #ddd;
            border-radius: 12px;
            padding: 40px 20px;
            text-align: center;
        }

        #no-dates-message p:first-child {
            font-size: 48px;
            margin-bottom: 15px;
        }

        #no-dates-message p:last-child {
            color: #666;
            font-size: 16px;
            margin: 0;
        }

        .time-slots {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        @media (min-width: 640px) {
            .time-slots {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (min-width: 1024px) {
            .time-slots {
                grid-template-columns: repeat(5, 1fr);
            }
        }

        .auth-form {
            padding: 15px;
        }

        @media (min-width: 768px) {
            .auth-form {
                padding: 25px;
            }
        }

        .buttons {
            display: flex;
            gap: 10px;
            flex-direction: column;
        }

        @media (min-width: 640px) {
            .buttons {
                flex-direction: row;
                justify-content: space-between;
            }
        }

        .btn {
            width: 100%;
            padding: 12px 20px;
            font-size: 14px;
        }

        @media (min-width: 640px) {
            .btn {
                width: auto;
                min-width: 150px;
                font-size: 16px;
            }
        }

        input, select, textarea {
            font-size: 16px;
        }

        @media (min-width: 768px) {
            input, select, textarea {
                font-size: 14px;
            }
        }

        .emergency-banner {
            background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 12px;
            margin-top: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(255, 107, 107, 0.3);
            display: flex;
            align-items: center;
            gap: 15px;
            animation: pulse 2s ease-in-out infinite;
        }

        .emergency-banner-icon {
            font-size: 32px;
            flex-shrink: 0;
        }

        .emergency-banner-content {
            flex: 1;
        }

        .emergency-banner-title {
            font-size: 16px;
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }

        .emergency-banner-text {
            margin: 0;
            font-size: 14px;
            line-height: 1.4;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }

        @media (max-width: 640px) {
            .emergency-banner {
                padding: 12px 15px;
            }

            .emergency-banner-icon {
                font-size: 24px;
            }

            .emergency-banner-title {
                font-size: 14px;
            }

            .emergency-banner-text {
                font-size: 12px;
            }
        }

        @keyframes slideInDown {
            from {
                transform: translateY(-30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes slideOutUp {
            from {
                transform: translateY(0);
                opacity: 1;
            }
            to {
                transform: translateY(-30px);
                opacity: 0;
            }
        }

        .alert-success {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%) !important;
        }

        .alert-error {
            background: linear-gradient(135deg, #f44336 0%, #da190b 100%) !important;
        }

        .alert-info {
            background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%) !important;
        }

        .alert-warning {
            background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%) !important;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🏥 Prise de Rendez-vous</h1>
        <p>Réservez votre consultation en quelques clics</p>
    </div>

    <div class="emergency-banner">
        <span class="emergency-banner-icon">🚨</span>
        <div class="emergency-banner-content">
            <strong class="emergency-banner-title">URGENCES MÉDICALES</strong>
            <p class="emergency-banner-text">
                En cas d'urgence, présentez-vous directement à la clinique sans rendez-vous.
            </p>
        </div>
    </div>

    <div class="progress-bar">
        <div class="progress-step active" id="progress-1">1</div>
        <div class="progress-line" id="line-1"></div>
        <div class="progress-step" id="progress-2">2</div>
        <div class="progress-line" id="line-2"></div>
        <div class="progress-step" id="progress-3">3</div>
        <div class="progress-line" id="line-3"></div>
        <div class="progress-step" id="progress-4">4</div>
    </div>

    <div class="form-content">
        <div id="alert-container"></div>

        <div class="loading" id="loading" style="display:none;">
            <div class="spinner"></div>
            <p>Traitement en cours...</p>
        </div>

        <div class="step active" id="step-1">
            <h2>Choisissez le département et le professionnel</h2>

            <div class="form-group">
                <label for="department">Département</label>
                <select id="department" onchange="updateProfessionals()">
                    <option value="">Sélectionnez un département</option>
                </select>
            </div>

            <div class="form-group">
                <label>Professionnel</label>
                <div class="professional-grid" id="professionals-grid"></div>
            </div>
        </div>

        <div class="step" id="step-2">
            <h2>Motif de la consultation</h2>

            <div class="form-group">
                <select id="reason">
                    <option value="">Sélectionnez un motif</option>
                    <option value="consultation_gynecologie">Consultation gynécologie</option>
                    <option value="consultation_desir_maternite">Consultation pour désir de maternité</option>
                    <option value="cpn">Consultation pour suivi de maternité (CPN)</option>
                    <option value="echographie_gynecologique">Echographie gynécologique</option>
                    <option value="echographie_obstetricale">Echographie obstétricale</option>
                    <option value="interpretation_resultats">Interprétation des résultats</option>
                    <option value="monnitoring_ovulation">Monitoring de l'ovulation</option>
                    <option value="pose_sterilet_gynecologie">Pose de stérilet gynécologie</option>
                    <option value="pose_implant">Pose implant</option>
                    <option value="autre">Autre</option>
                </select>
            </div>

            <div class="form-group">
                <label for="description">Description (optionnel)</label>
                <textarea id="description" rows="3" placeholder="Décrivez brièvement vos symptômes ou la raison de votre visite..."></textarea>
            </div>
        </div>

        <div class="step" id="step-3">
            <h2>Choisissez la date et l'heure</h2>

            <div class="form-group">
                <label style="font-size: 16px; font-weight: 600; margin-bottom: 15px; display: block; color: #333;">
                    📅 Dates disponibles
                </label>

                <div id="no-dates-message" style="display: none;">
                    <p>📅</p>
                    <p>Aucune date disponible pour ce professionnel</p>
                </div>

                <div class="available-dates-grid" id="available-dates-grid"></div>
            </div>

            <div class="form-group" id="time-slots-container" style="display: none; margin-top: 30px;">
                <label style="font-size: 16px; font-weight: 600; margin-bottom: 15px; display: block; color: #333;">
                    🕐 Créneaux horaires disponibles
                </label>
                <div class="time-slots" id="time-slots"></div>
            </div>
        </div>

        <div class="step" id="step-4">
            <h2>Finaliser votre rendez-vous</h2>

            <div class="appointment-summary">
                <h3>📋 Résumé de votre rendez-vous</h3>

                <div class="summary-item">
                    <span class="summary-label">Département:</span>
                    <span class="summary-value" id="summary-department">-</span>
                </div>

                <div class="summary-item">
                    <span class="summary-label">Professionnel:</span>
                    <span class="summary-value" id="summary-professional">-</span>
                </div>

                <div class="summary-item">
                    <span class="summary-label">Motif:</span>
                    <span class="summary-value" id="summary-reason">-</span>
                </div>

                <div class="summary-item">
                    <span class="summary-label">Date:</span>
                    <span class="summary-value" id="summary-date">-</span>
                </div>

                <div class="summary-item">
                    <span class="summary-label">Heure:</span>
                    <span class="summary-value" id="summary-time">-</span>
                </div>
            </div>

            <div class="auth-container">
                <div class="auth-header" style="text-align: center; margin-bottom: 30px;">
                    <div class="auth-icon" style="font-size: 48px; margin-bottom: 15px;">📱</div>
                    <h3 style="font-size: 24px; margin-bottom: 10px;">Confirmation</h3>
                    <p style="color: #666;">Entrez votre numéro de téléphone pour finaliser</p>
                </div>

                <div class="input-group">
                    <div class="input-label">
                        <span class="label-icon">📱</span>
                        <span>Numéro de téléphone</span>
                    </div>
                    <input type="tel" id="patient-phone" class="form-control" placeholder="Ex: 620 00 00 00">
                    <div class="input-border"></div>
                </div>

                <div class="input-group" id="phone-confirm-group" style="display:none;">
                    <div class="input-label">
                        <span class="label-icon">✅</span>
                        <span>Confirmer le numéro</span>
                    </div>
                    <input type="tel" id="patient-phone-confirm" class="form-control" placeholder="Retapez votre numéro">
                    <div class="input-border"></div>
                </div>

                <div class="input-group" id="patient-name-group" style="display:none;">
                    <div class="input-label">
                        <span class="label-icon">👤</span>
                        <span>Votre nom</span>
                    </div>
                    <input type="text" id="patient-name" class="form-control" placeholder="Ex: Hawaou Barry">
                    <div class="input-border"></div>
                </div>

                <p style="font-size: 12px; color: #666; margin-top: 8px;">
                    💡 Si c'est votre première visite, votre dossier patient sera créé automatiquement.
                </p>
            </div>
        </div>
    </div>

    <div class="buttons">
        <button class="btn btn-secondary" id="prev-btn" onclick="previousStep()" style="display: none;">
            ← Précédent
        </button>
        <button class="btn btn-primary" id="next-btn" onclick="nextStep()">
            Suivant →
        </button>
    </div>
</div>

<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    const API_ROUTES = {
        checkPatient: "{{ url('/api/check-patient') }}",
        departments: "{{ url('/api/departments') }}",
        professionals: "{{ url('/api/professionals') }}",
        availableDates: "{{ url('/api/appointments/available-dates') }}",
        slots: "{{ url('/api/appointments/slots') }}",
        appointments: "{{ url('/api/appointments') }}",
        findOrCreatePatient: "{{ url('/api/patient/find-or-create') }}"
    };

    let currentStep = 1;
    let selectedProfessional = null;
    let selectedProfessionalId = null;
    let selectedDate = null;
    let selectedDateValue = null;
    let selectedTime = null;
    let departments = [];
    let professionals = [];
    let availableSlots = [];
    let isProcessing = false;

    window.phoneConflict = false;
    window.existingPatient = null;

    function showAlert(message, type = 'error') {
        const alertContainer = document.getElementById('alert-container');

        const alertConfig = {
            success: {
                class: 'alert-success',
                icon: '✅',
                gradient: 'linear-gradient(135deg, #4CAF50 0%, #45a049 100%)'
            },
            error: {
                class: 'alert-error',
                icon: '❌',
                gradient: 'linear-gradient(135deg, #f44336 0%, #da190b 100%)'
            },
            info: {
                class: 'alert-info',
                icon: 'ℹ️',
                gradient: 'linear-gradient(135deg, #2196F3 0%, #1976D2 100%)'
            },
            warning: {
                class: 'alert-warning',
                icon: '⚠️',
                gradient: 'linear-gradient(135deg, #ff9800 0%, #f57c00 100%)'
            }
        };

        const config = alertConfig[type] || alertConfig.error;

        alertContainer.innerHTML = `
            <div class="alert ${config.class}" style="
                background: ${config.gradient};
                color: white;
                padding: 15px 20px;
                border-radius: 8px;
                margin-bottom: 15px;
                display: flex;
                align-items: center;
                gap: 12px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                animation: slideInDown 0.4s ease-out;
                font-size: 15px;
                font-weight: 500;
                white-space: pre-line;
            ">
                <span style="font-size: 22px; flex-shrink: 0;">${config.icon}</span>
                <span style="flex: 1;">${message}</span>
                <button onclick="this.parentElement.remove()" style="
                    background: rgba(255,255,255,0.2);
                    border: none;
                    color: white;
                    width: 28px;
                    height: 28px;
                    border-radius: 50%;
                    cursor: pointer;
                    font-size: 18px;
                    line-height: 1;
                    flex-shrink: 0;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                ">×</button>
            </div>
        `;

        alertContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

        setTimeout(() => {
            const alert = alertContainer.querySelector('.alert');
            if (alert) {
                alert.style.animation = 'slideOutUp 0.4s ease-in';
                setTimeout(() => {
                    alertContainer.innerHTML = '';
                }, 400);
            }
        }, 5000);
    }

    function showLoading(show = true) {
        document.getElementById('loading').style.display = show ? 'block' : 'none';
        isProcessing = show;
        document.getElementById('next-btn').disabled = show;
        document.getElementById('prev-btn').disabled = show;
    }

    function formatPhoneNumber(phone) {
        return (phone || '').replace(/\D/g, '');
    }

    function validatePhoneNumber(phone) {
        const cleanPhone = formatPhoneNumber(phone);
        return /^(224)?[67]\d{8}$/.test(cleanPhone);
    }

    function addFieldError(fieldId) {
        const field = document.getElementById(fieldId);
        if (field) {
            field.classList.add('error');
            setTimeout(() => field.classList.remove('error'), 3000);
        }
    }

    async function makeRequest(url, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        const finalOptions = { ...defaultOptions, ...options };
        const response = await fetch(url, finalOptions);

        let data = {};
        try {
            data = await response.json();
        } catch (e) {
            data = {};
        }

        if (!response.ok) {
            throw new Error(data.message || data.error || 'Une erreur est survenue');
        }

        return data;
    }

    async function checkPatientExists(phone) {
        const response = await fetch(API_ROUTES.checkPatient, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ phone })
        });

        let data = {};
        try {
            data = await response.json();
        } catch (e) {
            data = {};
        }

        return {
            ok: response.ok,
            status: response.status,
            data
        };
    }

    function formatWorkingDays(workingDay) {
        if (!workingDay) return 'Disponibilité à confirmer';

        const daysMap = {
            'monday': 'Lun',
            'tuesday': 'Mar',
            'wednesday': 'Mer',
            'thursday': 'Jeu',
            'friday': 'Ven',
            'saturday': 'Sam',
            'sunday': 'Dim',
            'lundi': 'Lun',
            'mardi': 'Mar',
            'mercredi': 'Mer',
            'jeudi': 'Jeu',
            'vendredi': 'Ven',
            'samedi': 'Sam',
            'dimanche': 'Dim'
        };

        if (workingDay.length < 15 && (workingDay.includes('-') || workingDay.includes(','))) {
            return workingDay;
        }

        const lowerWorkingDay = workingDay.toLowerCase();

        if (lowerWorkingDay.includes('tous les jours') || lowerWorkingDay.includes('every day')) {
            return 'Lun-Dim';
        }

        if (lowerWorkingDay.includes('semaine') ||
            (lowerWorkingDay.includes('lundi') && lowerWorkingDay.includes('vendredi'))) {
            return 'Lun-Ven';
        }

        const foundDays = [];
        for (const [key, value] of Object.entries(daysMap)) {
            if (lowerWorkingDay.includes(key) && !foundDays.includes(value)) {
                foundDays.push(value);
            }
        }

        if (foundDays.length > 0) {
            const allDays = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
            const indices = foundDays.map(day => allDays.indexOf(day)).sort((a, b) => a - b);

            if (indices.length > 2) {
                let isContinuous = true;
                for (let i = 1; i < indices.length; i++) {
                    if (indices[i] !== indices[i - 1] + 1) {
                        isContinuous = false;
                        break;
                    }
                }

                if (isContinuous) {
                    return `${allDays[indices[0]]}-${allDays[indices[indices.length - 1]]}`;
                }
            }

            return foundDays.join(', ');
        }

        return workingDay.length > 25 ? workingDay.substring(0, 22) + '...' : workingDay;
    }

    function updateReasonOptions() {
        const reasonSelect = document.getElementById('reason');
        if (!selectedProfessional) return;

        const speciality = (selectedProfessional.speciality || '').toLowerCase();
        const isPediatre = speciality.includes('pediatrie') || speciality.includes('pédiatrie');

        reasonSelect.innerHTML = '<option value="">Sélectionnez un motif</option>';

        if (isPediatre) {
            reasonSelect.innerHTML += `
                <option value="consultation_immunologie">Immunologie</option>
                <option value="consultation_nutrition_obesite">Nutrition & obésité</option>
                <option value="consultation_hematologie">Hématologie pédiatrique</option>
                <option value="consultation_drepanocytose">Prise en charge de la drépanocytose</option>
                <option value="autre_pediatre">Autre</option>
            `;
        } else {
            reasonSelect.innerHTML += `
                <option value="consultation_gynecologie">Consultation gynécologie</option>
                <option value="consultation_desir_maternite">Consultation pour désir de maternité</option>
                <option value="cpn">Consultation pour suivi de maternité (CPN)</option>
                <option value="echographie_gynecologique">Echographie gynécologique</option>
                <option value="echographie_obstetricale">Echographie obstétricale</option>
                <option value="interpretation_resultats">Interprétation des résultats</option>
                <option value="monnitoring_ovulation">Monitoring de l'ovulation</option>
                <option value="pose_sterilet_gynecologie">Pose de stérilet gynécologie</option>
                <option value="pose_implant">Pose implant</option>
                <option value="autre">Autre</option>
            `;
        }

        reasonSelect.value = '';
    }

    async function loadDepartments() {
        try {
            showLoading(true);
            const response = await makeRequest(API_ROUTES.departments);
            departments = response;

            const select = document.getElementById('department');
            select.innerHTML = '<option value="">Sélectionnez un département</option>';

            departments.forEach(dept => {
                const option = document.createElement('option');
                option.value = dept.id;
                option.textContent = dept.name;
                select.appendChild(option);
            });
        } catch (error) {
            showAlert('Erreur lors du chargement des départements: ' + error.message);
        } finally {
            showLoading(false);
        }
    }

    async function updateProfessionals() {
        const departmentId = document.getElementById('department').value;
        const grid = document.getElementById('professionals-grid');

        grid.innerHTML = '';
        selectedProfessional = null;
        selectedProfessionalId = null;

        if (!departmentId) return;

        try {
            showLoading(true);
            const response = await makeRequest(`${API_ROUTES.professionals}/${departmentId}`);
            professionals = response;

            if (professionals.length === 0) {
                grid.innerHTML = '<p style="text-align: center; color: #666; padding: 20px;">Aucun professionnel disponible dans ce département</p>';
                return;
            }

            professionals.forEach(prof => {
                const card = document.createElement('div');
                card.className = 'professional-card';
                card.onclick = () => selectProfessional(card, prof);

                const name = `Dr. ${prof.first_name} ${prof.last_name}`;
                const initials = `${(prof.first_name || '').charAt(0)}${(prof.last_name || '').charAt(0)}`;
                const workingDays = formatWorkingDays(prof.working_day);

                card.innerHTML = `
                    <div class="professional-avatar">${initials}</div>
                    <div class="professional-name">${name}</div>
                    <div class="professional-specialty">${prof.speciality || 'Spécialiste'}</div>
                    <div class="professional-schedule">${workingDays}</div>
                `;

                grid.appendChild(card);
            });
        } catch (error) {
            showAlert('Erreur lors du chargement des professionnels: ' + error.message);
        } finally {
            showLoading(false);
        }
    }

    function selectProfessional(card, professional) {
        document.querySelectorAll('.professional-card').forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');
        selectedProfessional = professional;
        selectedProfessionalId = professional.id;
        updateReasonOptions();
    }

    async function loadAndDisplayAvailableDates() {
        if (!selectedProfessionalId) return;

        try {
            showLoading(true);

            const startDate = new Date();
            const endDate = new Date();
            endDate.setDate(endDate.getDate() + 60);

            const response = await makeRequest(
                `${API_ROUTES.availableDates}?employee_id=${selectedProfessionalId}&start_date=${startDate.toISOString().split('T')[0]}&end_date=${endDate.toISOString().split('T')[0]}`
            );

            const dates = (response.available_dates || []).map(date => String(date).split('T')[0]);

            const grid = document.getElementById('available-dates-grid');
            const noDateMessage = document.getElementById('no-dates-message');

            if (dates.length === 0) {
                grid.style.display = 'none';
                noDateMessage.style.display = 'block';
                noDateMessage.innerHTML = `
                    <p style="font-size: 48px; margin-bottom: 15px;">📅</p>
                    <p style="color: #666; font-size: 16px; margin: 0 0 20px 0;">
                        Aucune date disponible pour ce professionnel
                    </p>
                    <div style="
                        background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
                        color: white;
                        padding: 20px;
                        border-radius: 12px;
                        margin-top: 20px;
                        box-shadow: 0 4px 12px rgba(255, 152, 0, 0.3);
                    ">
                        <p style="font-size: 20px; margin: 0 0 10px 0;">🚨 EN CAS D'URGENCE</p>
                        <p style="font-size: 14px; margin: 0; line-height: 1.6;">
                            Vous pouvez vous présenter <strong>directement à la clinique</strong> sans rendez-vous.<br>
                            Nos équipes vous prendront en charge dans les plus brefs délais.
                        </p>
                    </div>
                `;
                return;
            }

            noDateMessage.style.display = 'none';
            grid.style.display = 'grid';
            grid.innerHTML = '';

            dates.sort((a, b) => new Date(a) - new Date(b));

            const dayNames = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
            const monthNames = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];

            dates.forEach(dateStr => {
                const date = new Date(dateStr + 'T12:00:00');
                const card = document.createElement('div');
                card.className = 'date-card';
                card.onclick = () => selectAvailableDate(date, dateStr, card);

                card.innerHTML = `
                    <div class="date-card-day">${date.getDate()}</div>
                    <div class="date-card-month">${monthNames[date.getMonth()]}</div>
                    <div class="date-card-weekday">${dayNames[date.getDay()]}</div>
                `;

                grid.appendChild(card);
            });
        } catch (error) {
            showAlert('Erreur lors du chargement des dates disponibles: ' + error.message);
        } finally {
            showLoading(false);
        }
    }

    async function selectAvailableDate(date, dateStr, card) {
        document.querySelectorAll('.date-card').forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');

        selectedDate = date;
        selectedDateValue = dateStr;
        selectedTime = null;

        try {
            showLoading(true);
            const response = await makeRequest(`${API_ROUTES.slots}?employee_id=${selectedProfessionalId}&date=${dateStr}`);
            availableSlots = response;

            const timeSlotsContainer = document.getElementById('time-slots-container');
            timeSlotsContainer.style.display = 'block';
            updateTimeSlots();

            setTimeout(() => {
                timeSlotsContainer.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest'
                });
            }, 300);
        } catch (error) {
            showAlert('Erreur lors du chargement des créneaux horaires: ' + error.message);
        } finally {
            showLoading(false);
        }
    }

    function updateTimeSlots() {
        const container = document.getElementById('time-slots');
        container.innerHTML = '';

        if (!availableSlots.length) {
            container.innerHTML = '<p style="text-align: center; color: #666; padding: 20px; grid-column: 1/-1;">❌ Aucun créneau disponible pour cette date</p>';
            return;
        }

        availableSlots.forEach(slot => {
            const timeDiv = document.createElement('div');
            timeDiv.className = 'time-slot';
            timeDiv.textContent = slot.time;
            timeDiv.onclick = () => selectTimeSlot(timeDiv, slot.time);
            container.appendChild(timeDiv);
        });
    }

    function selectTimeSlot(element, time) {
        document.querySelectorAll('.time-slot').forEach(slot => slot.classList.remove('selected'));
        element.classList.add('selected');
        selectedTime = time;

        setTimeout(() => {
            const nextBtn = document.getElementById('next-btn');
            nextBtn.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest'
            });
            nextBtn.style.transform = 'scale(1.05)';
            setTimeout(() => nextBtn.style.transform = '', 300);
        }, 200);
    }

    async function authenticatePatient(phone) {
        const name = document.getElementById('patient-name').value.trim() || null;

        return await makeRequest(API_ROUTES.findOrCreatePatient, {
            method: 'POST',
            body: JSON.stringify({
                phone,
                name
            })
        });
    }

    async function saveAppointment(patientId) {
        return await makeRequest(API_ROUTES.appointments, {
            method: 'POST',
            body: JSON.stringify({
                employee_id: selectedProfessionalId,
                patient_id: patientId,
                appointment_date: selectedDateValue,
                appointment_time: selectedTime,
                reason: document.getElementById('reason').value,
                description: document.getElementById('description').value || null
            })
        });
    }

    function updateSummary() {
        const department = departments.find(d => d.id === parseInt(document.getElementById('department').value, 10));
        const reasonSelect = document.getElementById('reason');
        const reasonText = reasonSelect.options[reasonSelect.selectedIndex]?.text || '-';

        document.getElementById('summary-department').textContent = department ? department.name : '-';
        document.getElementById('summary-professional').textContent = selectedProfessional
            ? `Dr. ${selectedProfessional.first_name} ${selectedProfessional.last_name}`
            : '-';
        document.getElementById('summary-reason').textContent = reasonText;
        document.getElementById('summary-date').textContent = selectedDate
            ? selectedDate.toLocaleDateString('fr-FR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
            : '-';
        document.getElementById('summary-time').textContent = selectedTime || '-';

        document.getElementById('phone-confirm-group').style.display = 'none';
        document.getElementById('patient-name-group').style.display = 'none';
        document.getElementById('patient-phone-confirm').removeAttribute('required');
        document.getElementById('patient-name').removeAttribute('required');
    }

    function validateStep(step) {
        switch (step) {
            case 1:
                if (!document.getElementById('department').value) {
                    showAlert('Veuillez sélectionner un département');
                    return false;
                }
                if (!selectedProfessional) {
                    showAlert('Veuillez sélectionner un professionnel');
                    return false;
                }
                break;

            case 2:
                if (!document.getElementById('reason').value) {
                    showAlert('Veuillez sélectionner un motif de consultation');
                    return false;
                }
                break;

            case 3:
                if (!selectedDateValue) {
                    showAlert('Veuillez sélectionner une date');
                    return false;
                }
                if (!selectedTime) {
                    showAlert('Veuillez sélectionner une heure');
                    return false;
                }
                break;

            case 4:
                if (window.phoneConflict === true) {
                    showAlert('Ce numéro est déjà lié à un compte non patient. Veuillez utiliser un autre numéro ou contacter la clinique.', 'warning');
                    addFieldError('patient-phone');
                    return false;
                }

                const phone = formatPhoneNumber(document.getElementById('patient-phone').value.trim());
                const phoneConfirm = formatPhoneNumber(document.getElementById('patient-phone-confirm').value.trim());
                const patientName = document.getElementById('patient-name').value.trim();
                const confirmGroupVisible = document.getElementById('phone-confirm-group').style.display !== 'none';
                const nameGroupVisible = document.getElementById('patient-name-group').style.display !== 'none';

                if (!phone) {
                    showAlert('Veuillez saisir votre numéro de téléphone');
                    addFieldError('patient-phone');
                    return false;
                }

                if (!validatePhoneNumber(phone)) {
                    showAlert('Numéro de téléphone invalide');
                    addFieldError('patient-phone');
                    return false;
                }

                if (confirmGroupVisible) {
                    if (!phoneConfirm) {
                        showAlert('Veuillez confirmer votre numéro de téléphone');
                        addFieldError('patient-phone-confirm');
                        return false;
                    }

                    if (phone !== phoneConfirm) {
                        showAlert('Les numéros de téléphone ne correspondent pas');
                        addFieldError('patient-phone-confirm');
                        return false;
                    }
                }

                if (nameGroupVisible && !patientName) {
                    showAlert('Veuillez saisir votre nom');
                    addFieldError('patient-name');
                    return false;
                }

                break;
        }

        return true;
    }

    async function nextStep() {
        if (isProcessing) return;
        if (!validateStep(currentStep)) return;

        if (currentStep === 4) {
            await finalizeAppointment();
            return;
        }

        if (currentStep < 4) {
            currentStep++;
            await updateSteps();

            if (currentStep === 4) {
                updateSummary();
            }
        }
    }

    function previousStep() {
        if (isProcessing) return;

        if (currentStep > 1) {
            currentStep--;
            updateSteps();
        }
    }

    async function updateSteps() {
        document.querySelectorAll('.step').forEach(step => step.classList.remove('active'));
        document.getElementById(`step-${currentStep}`).classList.add('active');

        for (let i = 1; i <= 4; i++) {
            const progressStep = document.getElementById(`progress-${i}`);
            const progressLine = document.getElementById(`line-${i}`);

            if (i < currentStep) {
                progressStep.className = 'progress-step completed';
                if (progressLine) progressLine.classList.add('active');
            } else if (i === currentStep) {
                progressStep.className = 'progress-step active';
            } else {
                progressStep.className = 'progress-step';
                if (progressLine) progressLine.classList.remove('active');
            }
        }

        document.getElementById('prev-btn').style.display = currentStep === 1 ? 'none' : 'block';

        const nextBtn = document.getElementById('next-btn');
        if (currentStep === 4) {
            nextBtn.innerHTML = '🎯 Confirmer';
            nextBtn.style.background = 'linear-gradient(135deg, #4CAF50 0%, #45a049 100%)';
        } else {
            nextBtn.innerHTML = 'Suivant →';
            nextBtn.style.background = 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)';
        }

        if (currentStep === 3 && document.getElementById('available-dates-grid').children.length === 0) {
            await loadAndDisplayAvailableDates();
        }

        document.querySelector('.form-content').scrollTop = 0;
    }

    async function finalizeAppointment() {
        try {
            showLoading(true);

            if (window.phoneConflict === true) {
                throw new Error('Ce numéro est déjà lié à un compte non patient. Veuillez utiliser un autre numéro ou contacter la clinique.');
            }

            const phone = formatPhoneNumber(document.getElementById('patient-phone').value.trim());
            const response = await authenticatePatient(phone);

            if (!response.patient || !response.patient.id) {
                throw new Error('Impossible d’identifier le patient');
            }

            const patientId = response.patient.id;
            const appointmentResult = await saveAppointment(patientId);
            const appointment = appointmentResult.appointment;

            showAlert(
                `🎉 Rendez-vous confirmé !\n📅 ${appointment.date} à ${appointment.time}\n👨‍⚕️ ${appointment.doctor}\nVous recevrez un SMS de confirmation.`,
                'success'
            );

            setTimeout(() => {
                resetForm();
            }, 4000);
        } catch (error) {
            showAlert(error.message || 'Une erreur est survenue', 'error');
        } finally {
            showLoading(false);
        }
    }

    function resetForm() {
        currentStep = 1;
        selectedProfessional = null;
        selectedProfessionalId = null;
        selectedDate = null;
        selectedDateValue = null;
        selectedTime = null;
        availableSlots = [];
        professionals = [];
        window.phoneConflict = false;
        window.existingPatient = null;

        document.getElementById('department').value = '';
        document.getElementById('reason').value = '';
        document.getElementById('description').value = '';
        document.getElementById('patient-phone').value = '';
        document.getElementById('patient-phone-confirm').value = '';
        document.getElementById('patient-name').value = '';

        document.getElementById('phone-confirm-group').style.display = 'none';
        document.getElementById('patient-name-group').style.display = 'none';
        document.getElementById('patient-phone-confirm').removeAttribute('required');
        document.getElementById('patient-name').removeAttribute('required');

        document.querySelectorAll('.professional-card').forEach(card => card.classList.remove('selected'));
        document.querySelectorAll('.date-card').forEach(card => card.classList.remove('selected'));
        document.querySelectorAll('.time-slot').forEach(slot => slot.classList.remove('selected'));

        document.getElementById('professionals-grid').innerHTML = '';
        document.getElementById('available-dates-grid').innerHTML = '';
        document.getElementById('time-slots').innerHTML = '';
        document.getElementById('time-slots-container').style.display = 'none';

        updateSteps();
    }

    document.getElementById('patient-phone').addEventListener('blur', async function () {
        const phoneRaw = this.value.trim();
        const phone = formatPhoneNumber(phoneRaw);

        const nameField = document.getElementById('patient-name');
        const confirmField = document.getElementById('patient-phone-confirm');
        const nameGroup = document.getElementById('patient-name-group');
        const confirmGroup = document.getElementById('phone-confirm-group');

        this.classList.remove('error');

        if (!phone) return;

        if (!validatePhoneNumber(phone)) {
            showAlert('Veuillez entrer un numéro de téléphone valide', 'error');
            this.classList.add('error');
            return;
        }

        try {
            showLoading(true);

            const result = await checkPatientExists(phone);

            confirmGroup.style.display = 'none';
            confirmField.removeAttribute('required');
            confirmField.value = '';

            nameGroup.style.display = 'none';
            nameField.removeAttribute('required');
            nameField.value = '';

            window.existingPatient = null;
            window.phoneConflict = false;

            if (!result.ok && result.status === 422 && result.data.exists === true && result.data.is_patient === false) {
                window.phoneConflict = true;
                this.classList.add('error');

                showAlert(
                    result.data.message + ' Veuillez utiliser un autre numéro ou contacter la clinique.',
                    'warning'
                );
                return;
            }

            if (!result.ok) {
                showAlert(result.data.message || 'Erreur lors de la vérification. Veuillez réessayer.', 'error');
                return;
            }

            confirmGroup.style.display = 'block';
            confirmField.setAttribute('required', 'required');

            if (result.data.exists === true && result.data.is_patient === true) {
                nameGroup.style.display = 'none';
                nameField.removeAttribute('required');
                nameField.value = '';

                window.existingPatient = result.data.patient;
                showAlert(`Bienvenue ${result.data.patient.name || 'de retour'} ! 👋`, 'success');
                return;
            }

            nameGroup.style.display = 'block';
            nameField.setAttribute('required', 'required');

            showAlert('Premier rendez-vous ? Renseignez votre nom ci-dessous. 📝', 'info');

        } catch (error) {
            showAlert('Erreur lors de la vérification. Veuillez réessayer.', 'error');
        } finally {
            showLoading(false);
        }
    });

    document.getElementById('patient-phone-confirm').addEventListener('input', function () {
        const phone = formatPhoneNumber(document.getElementById('patient-phone').value);
        const confirmPhone = formatPhoneNumber(this.value);

        if (confirmPhone && phone !== confirmPhone) {
            this.classList.add('error');
            this.setCustomValidity('Les numéros ne correspondent pas');
        } else {
            this.classList.remove('error');
            this.setCustomValidity('');
        }
    });

    function handleTouchFeedback(element) {
        element.style.transform = 'scale(0.95)';
        setTimeout(() => {
            element.style.transform = '';
        }, 150);
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadDepartments();

        document.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !isProcessing) {
                nextStep();
            }
        });

        document.addEventListener('touchstart', (e) => {
            const clickable = e.target.closest('.professional-card, .date-card, .time-slot, .btn');
            if (clickable) {
                handleTouchFeedback(clickable);
            }
        });

        const inputs = document.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('touchstart', (e) => {
                e.target.style.fontSize = '16px';
            });
        });
    });
</script>

</body>
</html>