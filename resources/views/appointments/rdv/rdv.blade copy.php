<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prise de Rendez-vous - Clinique</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            max-width: 700px;
            width: 100%;
            overflow: hidden;
            position: relative;
        }

        .header {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="white" opacity="0.1"/><circle cx="80" cy="30" r="1" fill="white" opacity="0.1"/><circle cx="40" cy="70" r="1.5" fill="white" opacity="0.1"/></svg>');
        }

        .header h1 {
            font-size: 2em;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }

        .header p {
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .progress-bar {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            background: rgba(255, 255, 255, 0.5);
            gap: 15px;
        }

        .progress-step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e0e0e0;
            color: #999;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            transition: all 0.3s ease;
            position: relative;
        }

        .progress-step.active {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            transform: scale(1.1);
        }

        .progress-step.completed {
            background: #4CAF50;
            color: white;
        }

        .progress-line {
            height: 2px;
            width: 50px;
            background: #e0e0e0;
            transition: all 0.3s ease;
        }

        .progress-line.active {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .form-content {
            padding: 40px;
        }

        .step {
            display: none;
            animation: fadeIn 0.5s ease-in-out;
        }

        .step.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-group select,
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: white;
        }

        .form-group select:focus,
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #4facfe;
            box-shadow: 0 0 0 3px rgba(79, 172, 254, 0.1);
        }

        .professional-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .professional-card {
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            text-align: center;
        }

        .professional-card:hover {
            border-color: #4facfe;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .professional-card.selected {
            border-color: #4facfe;
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }

        .professional-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0 auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }

        .calendar-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            background: rgba(79, 172, 254, 0.1);
            padding: 15px;
            border-radius: 10px;
        }

        .calendar-nav {
            background: #4facfe;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 18px;
            transition: all 0.3s ease;
        }

        .calendar-nav:hover {
            background: #357abd;
            transform: scale(1.05);
        }

        .calendar-nav:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .month-year {
            font-size: 18px;
            font-weight: bold;
            color: #4facfe;
            text-align: center;
            flex: 1;
            margin: 0 20px;
        }

        .calendar-header {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px;
            margin-bottom: 10px;
        }

        .calendar-header-day {
            padding: 10px;
            text-align: center;
            font-weight: bold;
            color: #4facfe;
            background: rgba(79, 172, 254, 0.1);
            border-radius: 8px;
            font-size: 14px;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px;
            margin-bottom: 20px;
        }

        .calendar-day {
            padding: 12px;
            text-align: center;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            min-height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
            position: relative;
        }

        .calendar-day:hover:not(.disabled):not(.other-month) {
            background: #f0f8ff;
            border-color: #4facfe;
            transform: scale(1.05);
        }

        .calendar-day.selected {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            border-color: #4facfe;
        }

        .calendar-day.disabled {
            background: #f5f5f5;
            color: #ccc;
            cursor: not-allowed;
        }

        .calendar-day.other-month {
            background: #fafafa;
            color: #bbb;
            cursor: default;
        }

        .calendar-day.today {
            border: 2px solid #ff6b6b;
            font-weight: bold;
        }

        .calendar-day.today::after {
            content: '';
            position: absolute;
            bottom: 2px;
            left: 50%;
            transform: translateX(-50%);
            width: 6px;
            height: 6px;
            background: #ff6b6b;
            border-radius: 50%;
        }

        .time-slots {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }

        .time-slot {
            padding: 12px;
            text-align: center;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            font-weight: 500;
        }

        .time-slot:hover {
            border-color: #4facfe;
            background: #f0f8ff;
            transform: translateY(-2px);
        }

        .time-slot.selected {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            border-color: #4facfe;
        }

        .time-slot.unavailable {
            background: #ffebee;
            color: #f44336;
            cursor: not-allowed;
            border-color: #ffcdd2;
        }

        .time-slot.unavailable:hover {
            background: #ffebee;
            transform: none;
        }

        .buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            gap: 15px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            flex: 1;
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(79, 172, 254, 0.3);
        }

        .btn-secondary {
            background: #f5f5f5;
            color: #666;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
        }

        .auth-tabs {
            display: flex;
            margin-bottom: 20px;
            border-radius: 10px;
            overflow: hidden;
            background: #f5f5f5;
        }

        .auth-tab {
            flex: 1;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .auth-tab.active {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }

        .appointment-summary {
            background: linear-gradient(135deg, #f8f9ff 0%, #e8f2ff 100%);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(79, 172, 254, 0.1);
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid rgba(79, 172, 254, 0.1);
        }

        .summary-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .summary-label {
            font-weight: 600;
            color: #4facfe;
        }

        .quick-date-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .quick-date-btn {
            padding: 8px 16px;
            background: rgba(79, 172, 254, 0.1);
            border: 2px solid rgba(79, 172, 254, 0.3);
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            color: #4facfe;
            font-weight: 500;
        }

        .quick-date-btn:hover {
            background: rgba(79, 172, 254, 0.2);
            transform: translateY(-2px);
        }

        .quick-date-btn.selected {
            background: #4facfe;
            color: white;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 10px;
            font-weight: 500;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #4facfe;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .container {
                margin: 10px;
            }

            .form-content {
                padding: 20px;
            }

            .professional-grid {
                grid-template-columns: 1fr;
            }

            .calendar-controls {
                flex-direction: column;
                gap: 10px;
            }

            .month-year {
                margin: 0;
            }

            .calendar-grid {
                grid-template-columns: repeat(7, 1fr);
                gap: 3px;
            }

            .calendar-day {
                padding: 8px 4px;
                font-size: 12px;
                min-height: 40px;
            }

            .time-slots {
                grid-template-columns: repeat(2, 1fr);
            }

            .buttons {
                flex-direction: column;
            }

            .quick-date-selector {
                justify-content: center;
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
                <p>Chargement...</p>
            </div>

            <!-- Étape 1: Département et Professionnel -->
            <div class="step active" id="step-1">
                <h2>Choisissez le département et le professionnel</h2>

                <div class="form-group">
                    <label for="department">Département</label>
                    <select id="department" onchange="updateProfessionals()">
                        <option value="">Sélectionnez un département</option>
                        <!-- Options seront chargées via AJAX -->
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
                        <option value="consultation">Consultation générale</option>
                        <option value="controle">Contrôle de routine</option>
                        <option value="urgence">Urgence</option>
                        <option value="suivi">Suivi médical</option>
                        <option value="prevention">Prévention</option>
                        <option value="bilan">Bilan de santé</option>
                        <option value="vaccination">Vaccination</option>
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

                <div class="quick-date-selector">
                    <div class="quick-date-btn" onclick="selectQuickDate('today')">Aujourd'hui</div>
                    <div class="quick-date-btn" onclick="selectQuickDate('tomorrow')">Demain</div>
                    <div class="quick-date-btn" onclick="selectQuickDate('thisWeek')">Cette semaine</div>
                    <div class="quick-date-btn" onclick="selectQuickDate('nextWeek')">Semaine prochaine</div>
                </div>

                <div class="form-group">
                    <label>Date</label>
                    <div class="calendar-controls">
                        <button class="calendar-nav" onclick="changeMonth(-1)">‹</button>
                        <div class="month-year" id="month-year"></div>
                        <button class="calendar-nav" onclick="changeMonth(1)">›</button>
                    </div>

                    <div class="calendar-header">
                        <div class="calendar-header-day">Dim</div>
                        <div class="calendar-header-day">Lun</div>
                        <div class="calendar-header-day">Mar</div>
                        <div class="calendar-header-day">Mer</div>
                        <div class="calendar-header-day">Jeu</div>
                        <div class="calendar-header-day">Ven</div>
                        <div class="calendar-header-day">Sam</div>
                    </div>

                    <div class="calendar-grid" id="calendar-grid">
                        <!-- Le calendrier sera généré dynamiquement -->
                    </div>
                </div>

                <div class="form-group">
                    <label>Heure disponible</label>
                    <div class="time-slots" id="time-slots">
                        <!-- Les créneaux seront chargés via AJAX -->
                    </div>
                </div>
            </div>

            <!-- Étape 4: Finalisation -->
            <div class="step" id="step-4">
                <h2>Finaliser votre rendez-vous</h2>

                <div class="appointment-summary">
                    <h3>Résumé de votre rendez-vous</h3>
                    <div class="summary-item">
                        <span class="summary-label">Département:</span>
                        <span id="summary-department">-</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Professionnel:</span>
                        <span id="summary-professional">-</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Motif:</span>
                        <span id="summary-reason">-</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Date:</span>
                        <span id="summary-date">-</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Heure:</span>
                        <span id="summary-time">-</span>
                    </div>
                </div>

                @guest
                <div class="auth-tabs">
                    <div class="auth-tab active" onclick="showAuth('login')">Se connecter</div>
                    <div class="auth-tab" onclick="showAuth('register')">Créer un compte</div>
                </div>

                <div id="login-form">
                    <div class="form-group">
                        <label for="login-email">Email</label>
                        <input type="email" id="login-email" placeholder="votre.email@exemple.com">
                    </div>
                    <div class="form-group">
                        <label for="login-password">Mot de passe</label>
                        <input type="password" id="login-password" placeholder="••••••••">
                    </div>
                </div>

                <div id="register-form" style="display: none;">
                    <div class="form-group">
                        <label for="register-name">Nom complet</label>
                        <input type="text" id="register-name" placeholder="Nom Prénom">
                    </div>
                    <div class="form-group">
                        <label for="register-email">Email</label>
                        <input type="email" id="register-email" placeholder="votre.email@exemple.com">
                    </div>
                    <div class="form-group">
                        <label for="register-phone">Téléphone</label>
                        <input type="tel" id="register-phone" placeholder="06 12 34 56 78">
                    </div>
                    <div class="form-group">
                        <label for="register-password">Mot de passe</label>
                        <input type="password" id="register-password" placeholder="••••••••">
                    </div>
                </div>
                @endguest

                @auth
                    <div class="appointment-summary">
                        <h3>Utilisateur connecté</h3>
                        <div class="summary-item">
                            <span class="summary-label">Nom:</span>
                            <span>{{ Auth::user()->name }}</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Email:</span>
                            <span>{{ Auth::user()->email }}</span>
                        </div>
                    </div>
                @endauth
            </div>

            <div class="buttons">
                <button class="btn btn-secondary" id="prev-btn" onclick="previousStep()" style="display: none;">Précédent</button>
                <button class="btn btn-primary" id="next-btn" onclick="nextStep()">Suivant</button>
            </div>
        </div>
    </div>


<script>

    // Configuration Laravel
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const baseUrl = '{{ url("/") }}';

    // Variables globales
    let currentStep = 1;
    let selectedProfessional = null;
    let selectedProfessionalId = null;
    let selectedDate = null;
    let selectedTime = null;
    let currentMonth = new Date().getMonth();
    let currentYear = new Date().getFullYear();
    let departments = [];
    let professionals = [];
    let availableSlots = [];

    const months = [
        "Janvier", "Février", "Mars", "Avril", "Mai", "Juin",
        "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre"
    ];

    // Fonction pour afficher les alertes
    function showAlert(message, type = 'error') {
        const alertContainer = document.getElementById('alert-container');
        const alertClass = type === 'success' ? 'alert-success' : 'alert-error';

        alertContainer.innerHTML = `
            <div class="alert ${alertClass}">
                ${message}
            </div>
        `;

        setTimeout(() => {
            alertContainer.innerHTML = '';
        }, 5000);
    }

    // Fonction pour afficher/masquer le loader
    function showLoading(show = true) {
        document.getElementById('loading').style.display = show ? 'block' : 'none';
    }

    // Fonction pour faire des requêtes AJAX
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
                throw new Error(data.error || 'Une erreur est survenue');
            }

            return data;
        } catch (error) {
            throw error;
        }
    }

    // Charger les départements au démarrage
    async function loadDepartments() {
        try {
            showLoading(true);
            const response = await makeRequest(`${baseUrl}/api/departments`);
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
            const response = await makeRequest(`${baseUrl}/api/professionals/${departmentId}`);
            professionals = response;
            showLoading(false);

            professionals.forEach(prof => {
                const card = document.createElement('div');
                card.className = 'professional-card';
                card.onclick = () => selectProfessional(card, prof);
                const name = 'Dr. ' + prof.first_name + ' ' + prof.last_name;
                const initials = prof.first_name.split(' ').map(n => n[0]).join('');

                card.innerHTML = `
                    <div class="professional-avatar">${initials}</div>
                    <div style="font-weight: 600; margin-bottom: 5px;">${name}</div>
                    <div style="color: #666; font-size: 14px; margin-bottom: 5px;">${prof.speciality || 'Spécialiste'}</div>
                    <div style="color: #999; font-size: 12px;">${prof.working_day}</div>
                `;
                grid.appendChild(card);
            });
        } catch (error) {
            showLoading(false);
            showAlert('Erreur lors du chargement des professionnels: ' + error.message);
        }
    }

    function selectProfessional(card, professional) {
        document.querySelectorAll('.professional-card').forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');
        selectedProfessional = professional;
        selectedProfessionalId = professional.id;
    }

    // Générer le calendrier
    function generateCalendar() {
        const firstDay = new Date(currentYear, currentMonth, 1);
        const lastDay = new Date(currentYear, currentMonth + 1, 0);
        const grid = document.getElementById('calendar-grid');
        const monthYearLabel = document.getElementById('month-year');

        monthYearLabel.textContent = `${months[currentMonth]} ${currentYear}`;
        grid.innerHTML = '';

        // Ajouter les jours du mois précédent
        const firstDayWeek = firstDay.getDay();
        const prevMonthLastDay = new Date(currentYear, currentMonth, 0).getDate();

        for (let i = firstDayWeek - 1; i >= 0; i--) {
        const dayDiv = document.createElement('div');
        dayDiv.className = 'calendar-day other-month';
        dayDiv.textContent = prevMonthLastDay - i;
        grid.appendChild(dayDiv);
        }

        // Ajouter les jours du mois en cours
        const today = new Date();
        for (let day = 1; day <= lastDay.getDate(); day++) {
        const date = new Date(currentYear, currentMonth, day);
        const dayDiv = document.createElement('div');
        dayDiv.className = 'calendar-day';
        dayDiv.textContent = day;

        if (date < today) {
            dayDiv.classList.add('disabled');
        } else {
            dayDiv.onclick = () => selectDate(date, dayDiv);
        }

        if (date.toDateString() === today.toDateString()) {
            dayDiv.classList.add('today');
        }

        grid.appendChild(dayDiv);
        }

        // Compléter avec les jours du mois suivant
        const remainingDays = 42 - (firstDayWeek + lastDay.getDate());
        for (let i = 1; i <= remainingDays; i++) {
        const dayDiv = document.createElement('div');
        dayDiv.className = 'calendar-day other-month';
        dayDiv.textContent = i;
        grid.appendChild(dayDiv);
        }
    }

    // Sélectionner une date
    async function selectDate(date, element) {
        if (!selectedProfessionalId) {
        showAlert('Veuillez d\'abord sélectionner un professionnel');
        return;
        }

        document.querySelectorAll('.calendar-day').forEach(day => day.classList.remove('selected'));
        element.classList.add('selected');
        selectedDate = date;

        try {
            showLoading(true);
            const formattedDate = date.toISOString().split('T')[0];
            const response = await makeRequest(
                `${baseUrl}/api/appointments/slots?employee_id=${selectedProfessionalId}&date=${formattedDate}`
            );
            availableSlots = response;
            showLoading(false);

            updateTimeSlots();

        } catch (error) {
            showLoading(false);
            showAlert('Erreur lors du chargement des créneaux horaires: ' + error.message);
        }
    }

    // Mettre à jour les créneaux horaires disponibles
    function updateTimeSlots() {
        const container = document.getElementById('time-slots');
        container.innerHTML = '';

        if (availableSlots.length === 0) {
            container.innerHTML = '<p>Aucun créneau disponible pour cette date</p>';
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
    }

    // Navigation entre les mois
    function changeMonth(delta) {
        currentMonth += delta;
        if (currentMonth > 11) {
        currentMonth = 0;
        currentYear++;
        } else if (currentMonth < 0) {
        currentMonth = 11;
        currentYear--;
        }
        generateCalendar();
    }

    // Sélection rapide de dates
    function selectQuickDate(type) {
        const today = new Date();
        let targetDate;

        switch (type) {
        case 'today':
            targetDate = today;
            break;
        case 'tomorrow':
            targetDate = new Date(today);
            targetDate.setDate(today.getDate() + 1);
            break;
        case 'thisWeek':
            targetDate = new Date(today);
            targetDate.setDate(today.getDate() + (6 - today.getDay()));
            break;
        case 'nextWeek':
            targetDate = new Date(today);
            targetDate.setDate(today.getDate() + 7);
            break;
        }

        currentMonth = targetDate.getMonth();
        currentYear = targetDate.getFullYear();
        generateCalendar();

        // Trouver et sélectionner la date dans le calendrier
        setTimeout(() => {
        const days = document.querySelectorAll('.calendar-day:not(.other-month)');
        const day = Array.from(days).find(d => parseInt(d.textContent) === targetDate.getDate());
        if (day) {
            selectDate(targetDate, day);
        }
        }, 0);
    }


    function nextStep() {
        if (currentStep === 1) {
            if (!document.getElementById('department').value || !selectedProfessional) {
                alert('Veuillez sélectionner un département et un professionnel.');
                return;
            }
        } else if (currentStep === 2) {
            if (!document.getElementById('reason').value) {
                alert('Veuillez sélectionner un motif de consultation.');
                return;
            }
        } else if (currentStep === 3) {
            if (!selectedDate || !selectedTime) {
                alert('Veuillez sélectionner une date et une heure.');
                return;
            }
            updateSummary();
        } else if (currentStep === 4) {

            if (!selectedProfessionalId || !selectedDate || !selectedTime) {
                alert('Veuillez sélectionner un professionnel, une date et une heure.');
                return;
            }

            const email = document.getElementById('login-email')?.value || document.getElementById('register-email')?.value;
            const password = document.getElementById('login-password')?.value || document.getElementById('register-password')?.value;
            if (!email || !password) {
                alert('Veuillez entrer votre email et mot de passe.');
                return;
            }
            // Authentification de l'utilisateur
            authenticateUser(email, password)
                .then(() => {
                    // Enregistrer le rendez-vous après authentification réussie
                    return saveAppointment();
                })
                .catch(error => {
                    showAlert('Erreur: ' + error.message);
                });
            // Afficher un message de succès
            updateSummary();
            showAlert('Rendez-vous enregistré avec succès!', 'success');
            // Réinitialiser les sélections
            selectedProfessional = null;
            selectedProfessionalId = null;
            selectedDate = null;
            selectedTime = null;
            document.getElementById('department').value = '';
            document.getElementById('reason').value = '';
            document.querySelectorAll('.professional-card').forEach(card => card.classList.remove('selected'));
            document.querySelectorAll('.calendar-day').forEach(day => day.classList.remove('selected'));
            document.querySelectorAll('.time-slot').forEach(slot => slot.classList.remove('selected'));
            // Réinitialiser les étapes
            currentStep = 1;
            updateSteps();
            // Afficher un message de confirmation
            document.getElementById('alert-container').innerHTML = `
            <div class="alert alert-success">
                Rendez-vous confirmé avec succès! Vous recevrez un email de confirmation.
            </div>
            `;
            setTimeout(() => {
                document.getElementById('alert-container').innerHTML = '';
            }, 5000);
            alert('Rendez-vous confirmé avec succès! Vous recevrez un email de confirmation.');
            return;
        }

        if (currentStep < 4) {
            currentStep++;
            updateSteps();
        }
    }

    function previousStep() {
        if (currentStep > 1) {
            currentStep--;
            updateSteps();
        }
    }

    function updateSteps() {
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
        document.getElementById('next-btn').textContent = currentStep === 4 ? 'Confirmer' : 'Suivant';

        if (currentStep === 3 && document.getElementById('calendar-grid').children.length === 0) {
            generateCalendar();
        }
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

    // Authentification
    function showAuth(type) {
        const loginForm = document.getElementById('login-form');
        const registerForm = document.getElementById('register-form');

        if (type === 'login') {
            loginForm.style.display = 'block';
            registerForm.style.display = 'none';
        } else {
            loginForm.style.display = 'none';
            registerForm.style.display = 'block';
        }
    }
    // Gestion des événements pour les onglets d'authentification
    document.querySelectorAll('.auth-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            const type = tab.textContent.trim().toLowerCase();
            showAuth(type);
        });
    });

    // Verification de l'authentification
    async function authenticateUser(email, password, isLogin = true) {
        const url = isLogin ? `${baseUrl}/api/login` : `${baseUrl}/api/register`;
        const data = {
            email: email,
            password: password,
            name: isLogin ? undefined : document.getElementById('register-name').value,
            phone: isLogin ? undefined : document.getElementById('register-phone').value
        };
        try {
            showLoading(true);
            const response = await makeRequest(url, {
                method: 'POST',
                body: JSON.stringify(data)
            });
            showLoading(false);

            if (isLogin) {
                // Rediriger ou mettre à jour l'interface après la connexion
                window.location.reload();
            } else {
                // Rediriger ou mettre à jour l'interface après l'inscription
                showAlert('Inscription réussie ! Vous pouvez maintenant vous connecter.', 'success');
                showAuth('login');
            }
        } catch (error) {
            showLoading(false);
            showAlert(error.message);
        }
    }

    // enregistrer les informations du rendez-vous
    async function saveAppointment() {
        if (!selectedProfessionalId || !selectedDate || !selectedTime) {
            showAlert('Veuillez sélectionner un professionnel, une date et une heure.');
            return;
        }

        const appointmentData = {
            professional_id: selectedProfessionalId,
            date: selectedDate,
            time: selectedTime
        };

        try {

            const response = await fetch('/api/appointments', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(appointmentData)
            });

            if (!response.ok) {
                throw new Error('Erreur lors de la création du rendez-vous.');
            }

            const result = await response.json();
            showAlert('Rendez-vous créé avec succès !');
            console.log(result);
        } catch (error) {
            showAlert(error.message);
        }
    }

    // Initialisation
    document.addEventListener('DOMContentLoaded', () => {
        loadDepartments();
        generateCalendar();
    });


    </script>
