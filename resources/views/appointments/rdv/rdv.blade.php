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
        /* Styles responsive optimisés */
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

        .quick-date-selector {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            margin-bottom: 20px;
        }

        @media (min-width: 768px) {
            .quick-date-selector {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        /* Légende du calendrier */
        .calendar-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
            font-size: 12px;
        }

        @media (min-width: 768px) {
            .calendar-legend {
                font-size: 14px;
            }
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .legend-box {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            flex-shrink: 0;
        }

        .legend-box.selected {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .legend-box.today {
            background: #e3f2fd;
            border: 2px solid #2196F3;
        }

        .legend-box.unavailable {
            background: #f5f5f5;
            position: relative;
            text-decoration: line-through;
            color: #ccc;
        }

        .legend-box.unavailable::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 2px;
            right: 2px;
            height: 1px;
            background-color: #999;
        }

        /* Responsive form inputs */
        input, select, textarea {
            font-size: 16px; /* Évite le zoom sur iOS */
        }

        @media (min-width: 768px) {
            input, select, textarea {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🏥 Prise de Rendez-vous</h1>
        <p>Réservez votre consultation en quelques clics</p>
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

        <div class="loading" id="loading">
            <div class="spinner"></div>
            <p>Traitement en cours...</p>
        </div>

        <!-- Étape 1: Département et Professionnel -->
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
                <div class="professional-grid" id="professionals-grid">
                    <!-- Les professionnels seront chargés via AJAX -->
                </div>
            </div>
        </div>

        <!-- Étape 2: Motif -->
        <div class="step" id="step-2">
            <h2>Motif de la consultation</h2>

            <div class="form-group">
                <label for="reason">Motif principal</label>
                <select id="reason">
                    <option value="">Sélectionnez un motif</option>
                    <option value="consultation_gynecologie">Consultation gynécologie</option>
                    <option value="consultation_desir_maternite">Consultation pour désir de maternité</option>
                    <option value="cpn">Consultation pour suivi de maternité (CPN)</option>
                    <option value="echographie_gynecologique">Echographie gynécologique</option>
                    <option value="echographie_obstetricale">Echographie obstétricale</option>
                    <option value="interpretation_resultats">Interprétation des résultats</option>
                    <option value="monnitoring_ovulation">Monnitoring de l'ovulation</option>
                    <option value="pose_sterilet_gynecologie">Pose de Stérilet gynécologie</option>
                    <option value="pose_implant">Pose implant</option>
                    <option value="autre">Autre</option>
                </select>
            </div>

            <div class="form-group">
                <label for="description">Description (optionnel)</label>
                <textarea id="description" rows="3" placeholder="Décrivez brièvement vos symptômes ou la raison de votre visite..."></textarea>
            </div>
        </div>

        <!-- Étape 3: Date et Heure -->
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
                <div class="available-dates-grid" id="available-dates-grid">
                    <!-- Les dates disponibles seront chargées ici -->
                </div>
            </div>

            <div class="form-group" id="time-slots-container" style="display: none; margin-top: 30px;">
                <label style="font-size: 16px; font-weight: 600; margin-bottom: 15px; display: block; color: #333;">
                    🕐 Créneaux horaires disponibles
                </label>
                <div class="time-slots" id="time-slots">
                    <!-- Les créneaux seront chargés via AJAX -->
                </div>
            </div>
        </div>

        <!-- Étape 4: Finalisation -->
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
                    <input type="tel" id="patient-phone" class="form-control" placeholder="Ex: 620 00 00 00" required>
                    <div class="input-border"></div>
                </div>

                <div class="input-group">
                    <div class="input-label">
                        <span class="label-icon">✅</span>
                        <span>Confirmer le numéro</span>
                    </div>
                    <input type="tel" id="patient-phone-confirm" class="form-control" placeholder="Retapez votre numéro" required>
                    <div class="input-border"></div>
                </div>

                <p style="font-size: 12px; color: #666; margin-top: 8px;">
                    💡 Si c'est votre première visite, un compte sera créé automatiquement
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
    // Configuration Laravel
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Variables globales
    let currentStep = 1;
    let selectedProfessional = null;
    let selectedProfessionalId = null;
    let selectedDate = null;
    let selectedTime = null;
    let departments = [];
    let professionals = [];
    let availableSlots = [];
    let isProcessing = false;

    const months = [
        "Janvier", "Février", "Mars", "Avril", "Mai", "Juin",
        "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre"
    ];

    // Fonction pour afficher les alertes avec animation
    function showAlert(message, type = 'error') {
        const alertContainer = document.getElementById('alert-container');
        const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
        const icon = type === 'success' ? '✅' : '❌';

        alertContainer.innerHTML = `
            <div class="alert ${alertClass}">
                ${icon} ${message}
            </div>
        `;

        alertContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

        setTimeout(() => {
            alertContainer.innerHTML = '';
        }, 5000);
    }

    // Fonction pour afficher/masquer le loader
    function showLoading(show = true) {
        document.getElementById('loading').style.display = show ? 'block' : 'none';
        isProcessing = show;

        document.getElementById('next-btn').disabled = show;
        document.getElementById('prev-btn').disabled = show;
    }

    // Fonction pour faire des requêtes AJAX améliorée
    async function makeRequest(url, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        const finalOptions = { ...defaultOptions, ...options };

        try {
            const response = await fetch(url, finalOptions);
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || data.error || 'Une erreur est survenue');
            }

            return data;
        } catch (error) {
            console.error('Erreur API:', error);
            throw error;
        }
    }

    // Charger et afficher les dates disponibles organisées par mois
    async function loadAndDisplayAvailableDates() {
        if (!selectedProfessionalId) return;

        try {
            showLoading(true);
            
            const startDate = new Date();
            const endDate = new Date();
            endDate.setDate(endDate.getDate() + 60);
            
            const response = await makeRequest(
                '/api/appointments/available-dates?employee_id=' + selectedProfessionalId + 
                '&start_date=' + startDate.toISOString().split('T')[0] + 
                '&end_date=' + endDate.toISOString().split('T')[0]
            );
            
            showLoading(false);
            
            const dates = (response.available_dates || []).map(date => date.split('T')[0]);
            
            const grid = document.getElementById('available-dates-grid');
            const noDateMessage = document.getElementById('no-dates-message');
            
            if (dates.length === 0) {
                grid.style.display = 'none';
                noDateMessage.style.display = 'block';
                return;
            }
            
            noDateMessage.style.display = 'none';
            grid.style.display = 'grid';
            grid.innerHTML = '';

            // Trier les dates par ordre chronologique
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
            showLoading(false);
            showAlert('Erreur lors du chargement des dates disponibles: ' + error.message);
        }
    }

    // Sélectionner une date disponible
    async function selectAvailableDate(date, dateStr, card) {
        document.querySelectorAll('.date-card').forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');
        selectedDate = date;

        try {
            showLoading(true);
            const response = await makeRequest(
                '/api/appointments/slots?employee_id=' + selectedProfessionalId + '&date=' + dateStr
            );
            availableSlots = response;
            showLoading(false);

            const timeSlotsContainer = document.getElementById('time-slots-container');
            timeSlotsContainer.style.display = 'block';
            updateTimeSlots();

            // Scroll automatique vers les créneaux horaires
            setTimeout(() => {
                timeSlotsContainer.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'nearest'
                });
            }, 300);

        } catch (error) {
            showLoading(false);
            showAlert('Erreur lors du chargement des créneaux horaires: ' + error.message);
        }
    }

    // Fonction pour basculer la visibilité du mot de passe
    function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        const toggleBtn = input.parentElement.querySelector('.password-toggle-btn');
        const icon = toggleBtn.querySelector('.toggle-icon');

        if (input.type === 'password') {
            input.type = 'text';
            icon.textContent = '🙈';
            toggleBtn.style.background = 'rgba(79, 172, 254, 0.1)';
        } else {
            input.type = 'password';
            icon.textContent = '👁️';
            toggleBtn.style.background = 'none';
        }
    }

    // Fonction pour valider le format du téléphone
    function validatePhoneNumber(phone) {
        const phoneRegex = /^(\+224|224)?[67]\d{8}$/;
        return phoneRegex.test(phone.replace(/\s/g, ''));
    }

    // Fonction pour ajouter des classes d'erreur aux champs
    function addFieldError(fieldId) {
        const field = document.getElementById(fieldId);
        if (field) {
            field.classList.add('error');
            setTimeout(() => {
                field.classList.remove('error');
            }, 3000);
        }
    }

    // Charger les départements au démarrage
    async function loadDepartments() {
        try {
            showLoading(true);
            const response = await makeRequest('/api/departments');
            showLoading(false);
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
            showLoading(false);
            showAlert('Erreur lors du chargement des départements: ' + error.message);
        }
    }

    // Charger les professionnels selon le département
    async function updateProfessionals() {
        const departmentId = document.getElementById('department').value;
        const grid = document.getElementById('professionals-grid');

        grid.innerHTML = '';
        selectedProfessional = null;
        selectedProfessionalId = null;

        if (!departmentId) return;

        try {
            showLoading(true);
            const response = await makeRequest('/api/professionals/' + departmentId);
            professionals = response;
            showLoading(false);

            if (professionals.length === 0) {
                grid.innerHTML = '<p style="text-align: center; color: #666; padding: 20px;">Aucun professionnel disponible dans ce département</p>';
                return;
            }

            professionals.forEach(prof => {
                const card = document.createElement('div');
                card.className = 'professional-card';
                card.onclick = () => selectProfessional(card, prof);
                const name = 'Dr. ' + prof.first_name + ' ' + prof.last_name;
                const initials = prof.first_name.split(' ').map(n => n[0]).join('');

                card.innerHTML = `
                    <div class="professional-avatar">${initials}</div>
                    <div class="professional-name">${name}</div>
                    <div class="professional-specialty">${prof.speciality || 'Spécialiste'}</div>
                    <div class="professional-schedule">${prof.working_day || 'Disponible'}</div>
                `;
                grid.appendChild(card);
            });
        } catch (error) {
            showLoading(false);
            showAlert('Erreur lors du chargement des professionnels: ' + error.message);
        }
    }

    async function selectProfessional(card, professional) {
        document.querySelectorAll('.professional-card').forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');
        selectedProfessional = professional;
        selectedProfessionalId = professional.id;
    }

    // Mettre à jour les créneaux horaires disponibles
    function updateTimeSlots() {
        const container = document.getElementById('time-slots');
        container.innerHTML = '';

        if (availableSlots.length === 0) {
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

    // Sélectionner un créneau horaire
    function selectTimeSlot(element, time) {
        document.querySelectorAll('.time-slot').forEach(slot => slot.classList.remove('selected'));
        element.classList.add('selected');
        selectedTime = time;
        
        // Scroll vers le bouton "Suivant" après sélection
        setTimeout(() => {
            const nextBtn = document.getElementById('next-btn');
            nextBtn.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'nearest'
            });
            // Petit effet visuel sur le bouton
            nextBtn.style.transform = 'scale(1.05)';
            setTimeout(() => {
                nextBtn.style.transform = '';
            }, 300);
        }, 200);
    }

    // Gestion de l'authentification - fonction simplifiée
    function showAuth(type) {
        // Fonction vide - plus nécessaire avec le nouveau système
    }

    // Validation des données
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
                if (!selectedDate) {
                    showAlert('Veuillez sélectionner une date');
                    return false;
                }
                if (!selectedTime) {
                    showAlert('Veuillez sélectionner une heure');
                    return false;
                }
                break;
            case 4:
                const phone = document.getElementById('patient-phone').value.trim();

                if (!phone) {
                    showAlert('Veuillez saisir votre numéro de téléphone');
                    addFieldError('patient-phone');
                    return false;
                }
                if (!validatePhoneNumber(phone)) {
                    showAlert('Numéro de téléphone invalide (format: 620000000)');
                    addFieldError('patient-phone');
                    return false;
                }
                const phoneConfirm = document.getElementById('patient-phone-confirm').value.trim();

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

                break;
        }
        return true;
    }

    // Navigation entre les étapes
    function nextStep() {
        if (isProcessing) return;

        if (!validateStep(currentStep)) return;

        if (currentStep === 4) {
            finalizeAppointment();
            return;
        }

        if (currentStep < 4) {
            currentStep++;
            updateSteps();

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

    function updateSummary() {
        const department = departments.find(d => d.id === parseInt(document.getElementById('department').value));
        const reason = document.getElementById('reason').value;
        const reasonText = document.getElementById('reason').options[document.getElementById('reason').selectedIndex].text;

        document.getElementById('summary-department').textContent = department ? department.name : '-';
        document.getElementById('summary-professional').textContent = selectedProfessional ?
            `Dr. ${selectedProfessional.first_name} ${selectedProfessional.last_name}` : '-';
        document.getElementById('summary-reason').textContent = reasonText || '-';
        document.getElementById('summary-date').textContent = selectedDate ?
            selectedDate.toLocaleDateString('fr-FR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) : '-';
        document.getElementById('summary-time').textContent = selectedTime || '-';
    }

    // Authentification simplifiée - Vérifier ou créer le patient
    async function authenticatePatient(phone) {
        const url = '/api/patient/find-or-create';
        const data = { phone: phone };

        try {
            const response = await makeRequest(url, {
                method: 'POST',
                body: JSON.stringify(data)
            });

            return response;
        } catch (error) {
            throw error;
        }
    }

    // Enregistrer le rendez-vous
    async function saveAppointment(userId) {
        const appointmentData = {
            employee_id: selectedProfessionalId,
            patient_id: userId,
            appointment_date: selectedDate.toISOString().split('T')[0],
            appointment_time: selectedTime,
            reason: document.getElementById('reason').value,
            description: document.getElementById('description').value || null
        };

        try {
            const response = await makeRequest('/api/appointments', {
                method: 'POST',
                body: JSON.stringify(appointmentData)
            });

            return response;
        } catch (error) {
            throw error;
        }
    }

    // Finaliser le rendez-vous
    async function finalizeAppointment() {
        try {
            showLoading(true);

            const phone = document.getElementById('patient-phone').value.trim();

            const response = await authenticatePatient(phone);

            if (response.error) {
                throw new Error(response.message || 'Erreur d\'authentification');
            }

            const userId = response.patient.id;
            const appointmentResult = await saveAppointment(userId);

            showLoading(false);
            showAlert('🎉 Rendez-vous confirmé avec succès! Vous recevrez un SMS de confirmation.', 'success');

            setTimeout(() => {
                resetForm();
            }, 3000);

        } catch (error) {
            showLoading(false);
            showAlert(error.message);
        }
    }

    // Réinitialiser le formulaire
    function resetForm() {
        currentStep = 1;
        selectedProfessional = null;
        selectedProfessionalId = null;
        selectedDate = null;
        selectedTime = null;

        document.getElementById('department').value = '';
        document.getElementById('reason').value = '';
        document.getElementById('description').value = '';
        document.getElementById('patient-phone').value = '';

        document.querySelectorAll('.professional-card').forEach(card => card.classList.remove('selected'));
        document.querySelectorAll('.date-card').forEach(card => card.classList.remove('selected'));
        document.querySelectorAll('.time-slot').forEach(slot => slot.classList.remove('selected'));

        document.getElementById('professionals-grid').innerHTML = '';
        document.getElementById('available-dates-grid').innerHTML = '';
        document.getElementById('time-slots').innerHTML = '';
        document.getElementById('time-slots-container').style.display = 'none';
        document.getElementById('patient-phone-confirm').value = '';

        updateSteps();
    }

    // Gestion des événements tactiles pour améliorer l'expérience mobile
    function handleTouchFeedback(element) {
        element.style.transform = 'scale(0.95)';
        setTimeout(() => {
            element.style.transform = '';
        }, 150);
    }

    // Initialisation
    document.addEventListener('DOMContentLoaded', () => {
        console.log('DOM chargé - Initialisation...');
        loadDepartments();

        document.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !isProcessing) {
                nextStep();
            }
        });

        document.addEventListener('touchstart', (e) => {
            if (e.target.classList.contains('professional-card') || 
                e.target.classList.contains('date-card') || 
                e.target.classList.contains('time-slot') ||
                e.target.classList.contains('btn')) {
                handleTouchFeedback(e.target);
            }
        });

        const inputs = document.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('touchstart', (e) => {
                e.target.style.fontSize = '16px';
            });
        });
        
        console.log('Initialisation terminée');
    });
</script>