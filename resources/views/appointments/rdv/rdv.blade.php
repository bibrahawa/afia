<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prise de Rendez-vous - Clinique</title>
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

        .btn-primary {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }

        .btn-primary:hover {
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
            <!-- Étape 1: Département et Professionnel -->
            <div class="step active" id="step-1">
                <h2>Choisissez le département et le professionnel</h2>

                <div class="form-group">
                    <label for="department">Département</label>
                    <select id="department" onchange="updateProfessionals()">
                        <option value="">Sélectionnez un département</option>
                        <option value="medecine">Médecine Générale</option>
                        <option value="cardiologie">Cardiologie</option>
                        <option value="dermatologie">Dermatologie</option>
                        <option value="pediatrie">Pédiatrie</option>
                        <option value="orthopédie">Orthopédie</option>
                        <option value="gynécologie">Gynécologie</option>
                        <option value="neurologie">Neurologie</option>
                        <option value="psychiatrie">Psychiatrie</option>
                        <option value="ophtalmologie">Ophtalmologie</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Professionnel</label>
                    <div class="professional-grid" id="professionals-grid">
                        <!-- Les professionnels seront générés dynamiquement -->
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
                        <!-- Les créneaux seront générés dynamiquement -->
                    </div>
                </div>
            </div>

            <!-- Étape 4: Authentification -->
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
            </div>

            <div class="buttons">
                <button class="btn btn-secondary" id="prev-btn" onclick="previousStep()" style="display: none;">Précédent</button>
                <button class="btn btn-primary" id="next-btn" onclick="nextStep()">Suivant</button>
            </div>
        </div>
    </div>

    <script>
        let currentStep = 1;
        let selectedProfessional = null;
        let selectedDate = null;
        let selectedTime = null;
        let currentMonth = new Date().getMonth();
        let currentYear = new Date().getFullYear();

        const professionals = {
            medecine: [
                { name: "Dr. Martin Dubois", speciality: "Médecin généraliste", availability: "Tous les jours sauf dimanche" },
                { name: "Dr. Sophie Laurent", speciality: "Médecin généraliste", availability: "Lundi, mercredi, vendredi" }
            ],
            cardiologie: [
                { name: "Dr. Pierre Moreau", speciality: "Cardiologue", availability: "Mardi, jeudi, vendredi" },
                { name: "Dr. Marie Petit", speciality: "Cardiologue", availability: "Lundi, mercredi, samedi" }
            ],
            dermatologie: [
                { name: "Dr. Jean Dupont", speciality: "Dermatologue", availability: "Lundi au vendredi" },
                { name: "Dr. Claire Simon", speciality: "Dermatologue", availability: "Mardi, jeudi, samedi" }
            ],
            pediatrie: [
                { name: "Dr. Anne Lefèvre", speciality: "Pédiatre", availability: "Tous les jours" },
                { name: "Dr. Paul Roux", speciality: "Pédiatre", availability: "Lundi, mercredi, vendredi" }
            ],
            orthopédie: [
                { name: "Dr. Michel Bernard", speciality: "Orthopédiste", availability: "Lundi, mercredi, vendredi" },
                { name: "Dr. Nathalie Garnier", speciality: "Orthopédiste", availability: "Mardi, jeudi, samedi" }
            ],
            gynécologie: [
                { name: "Dr. Isabelle Morel", speciality: "Gynécologue", availability: "Lundi au vendredi" },
                { name: "Dr. Catherine Rousseau", speciality: "Gynécologue", availability: "Mardi, jeudi, samedi" }
            ],
            neurologie: [
                { name: "Dr. Philippe Blanc", speciality: "Neurologue", availability: "Lundi, mercredi, vendredi" },
                { name: "Dr. Françoise Noir", speciality: "Neurologue", availability: "Mardi, jeudi" }
            ],
            psychiatrie: [
                { name: "Dr. Antoine Vert", speciality: "Psychiatre", availability: "Lundi au vendredi" },
                { name: "Dr. Sylvie Rouge", speciality: "Psychiatre", availability: "Mardi, jeudi, samedi" }
            ],
            ophtalmologie: [
                { name: "Dr. Lucas Bleu", speciality: "Ophtalmologue", availability: "Lundi, mercredi, vendredi" },
                { name: "Dr. Emma Jaune", speciality: "Ophtalmologue", availability: "Mardi, jeudi, samedi" }
            ]
        };

        const timeSlots = ["08:00", "08:30", "09:00", "09:30", "10:00", "10:30", "11:00", "11:30", "14:00", "14:30", "15:00", "15:30", "16:00", "16:30", "17:00", "17:30"];

        const months = [
            "Janvier", "Février", "Mars", "Avril", "Mai", "Juin",
            "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre"
        ];

        function updateProfessionals() {
            const department = document.getElementById('department').value;
            const grid = document.getElementById('professionals-grid');

            grid.innerHTML = '';

            if (department && professionals[department]) {
                professionals[department].forEach((prof, index) => {
                    const card = document.createElement('div');
                    card.className = 'professional-card';
                    card.onclick = () => selectProfessional(card, prof);
                    card.innerHTML = `
                        <div class="professional-avatar">${prof.name.split(' ')[1][0]}</div>
                        <div style="font-weight: 600; margin-bottom: 5px;">${prof.name}</div>
                        <div style="color: #666; font-size: 14px; margin-bottom: 5px;">${prof.speciality}</div>
                        <div style="color: #999; font-size: 12px;">${prof.availability}</div>
                    `;
                    grid.appendChild(card);
                });
            }
        }

        function selectProfessional(card, professional) {
            document.querySelectorAll('.professional-card').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            selectedProfessional = professional;
        }

        function generateCalendar() {
            const grid = document.getElementById('calendar-grid');
            const monthYear = document.getElementById('month-year');

            grid.innerHTML = '';
            monthYear.textContent = `${months[currentMonth]} ${currentYear}`;

            const firstDay = new Date(currentYear, currentMonth, 1);
            const lastDay = new Date(currentYear, currentMonth + 1, 0);
            const startDate = new Date(firstDay);
            startDate.setDate(startDate.getDate() - firstDay.getDay());

            const today = new Date();
            today.setHours(0, 0, 0, 0);

            for (let i = 0; i < 42; i++) {
                const date = new Date(startDate);
                date.setDate(startDate.getDate() + i);

                const dayDiv = document.createElement('div');
                dayDiv.className = 'calendar-day';
                dayDiv.textContent = date.getDate();

                const isCurrentMonth = date.getMonth() === currentMonth;
                const isToday = date.getTime() === today.getTime();
                const isPast = date < today;
                const isWeekend = date.getDay() === 0 || date.getDay() === 6;

                if (!isCurrentMonth) {
                    dayDiv.classList.add('other-month');
                } else if (isPast) {
                    dayDiv.classList.add('disabled');
                    dayDiv.onclick = null;
                } else if (isWeekend) {
                    dayDiv.classList.add('disabled');
                    dayDiv.onclick = null;
                } else {
                    dayDiv.onclick = () => selectDate(dayDiv, date);
                }

                if (isToday) {
                    dayDiv.classList.add('today');
                }

                grid.appendChild(dayDiv);
            }
        }

        function changeMonth(direction) {
            const today = new Date();
            const maxMonth = today.getMonth() + 6; // 6 mois à l'avance
            const maxYear = today.getFullYear() + (maxMonth >= 12 ? 1 : 0);
            const adjustedMaxMonth = maxMonth % 12;

            currentMonth += direction;

            if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            } else if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            }

            // Empêcher de naviguer trop loin dans le passé ou le futur
            if (currentYear < today.getFullYear() ||
                (currentYear === today.getFullYear() && currentMonth < today.getMonth()) ||
                (currentYear > maxYear) ||
                (currentYear === maxYear && currentMonth > adjustedMaxMonth)) {

                currentMonth -= direction;
                if (currentMonth < 0) {
                    currentMonth = 11;
                    currentYear--;
                } else if (currentMonth > 11) {
                    currentMonth = 0;
                    currentYear++;
                }
                return;
            }

            generateCalendar();
        }

        function selectQuickDate(type) {
            const today = new Date();
            let targetDate;

            document.querySelectorAll('.quick-date-btn').forEach(btn => btn.classList.remove('selected'));
            event.target.classList.add('selected');

            switch (type) {
                case 'today':
                    targetDate = new Date(today);
                    break;
                case 'tomorrow':
                    targetDate = new Date(today);
                    targetDate.setDate(today.getDate() + 1);
                    break;
                case 'thisWeek':
                    targetDate = new Date(today);
                    // Aller au prochain jour ouvrable de cette semaine
                    const daysUntilFriday = 5 - today.getDay();
                    if (daysUntilFriday > 0) {
                        targetDate.setDate(today.getDate() + Math.min(daysUntilFriday, 1));
                    } else {
                        targetDate.setDate(today.getDate() + 1);
                    }
                    break;
                case 'nextWeek':
                    targetDate = new Date(today);
                    const daysUntilMonday = 8 - today.getDay();
                    targetDate.setDate(today.getDate() + daysUntilMonday);
                    break;
            }

            // Éviter les weekends
            while (targetDate.getDay() === 0 || targetDate.getDay() === 6) {
                targetDate.setDate(targetDate.getDate() + 1);
            }

            currentMonth = targetDate.getMonth();
            currentYear = targetDate.getFullYear();
            generateCalendar();

            // Sélectionner automatiquement la date
            setTimeout(() => {
                const dayElements = document.querySelectorAll('.calendar-day');
                dayElements.forEach(day => {
                    if (parseInt(day.textContent) === targetDate.getDate() &&
                        !day.classList.contains('other-month') &&
                        !day.classList.contains('disabled')) {
                        selectDate(day, targetDate);
                    }
                });
            }, 100);
        }

        function selectDate(dayDiv, date) {
            document.querySelectorAll('.calendar-day').forEach(d => d.classList.remove('selected'));
            dayDiv.classList.add('selected');
            selectedDate = date;
            generateTimeSlots();
        }

        function generateTimeSlots() {
            const slotsContainer = document.getElementById('time-slots');
            slotsContainer.innerHTML = '';

            // Simuler des créneaux indisponibles de manière aléatoire
            const unavailableSlots = [];
            const randomUnavailable = Math.floor(Math.random() * 4);
            for (let i = 0; i < randomUnavailable; i++) {
                const randomIndex = Math.floor(Math.random() * timeSlots.length);
                if (!unavailableSlots.includes(timeSlots[randomIndex])) {
                    unavailableSlots.push(timeSlots[randomIndex]);
                }
            }

            timeSlots.forEach(time => {
                const slot = document.createElement('div');
                slot.className = 'time-slot';
                slot.textContent = time;

                if (unavailableSlots.includes(time)) {
                    slot.classList.add('unavailable');
                    slot.textContent += ' - Indisponible';
                } else {
                    slot.onclick = () => selectTime(slot, time);
                }

                slotsContainer.appendChild(slot);
            });
        }

        function selectTime(slot, time) {
            document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected'));
            slot.classList.add('selected');
            selectedTime = time;
        }

        function showAuth(type) {
            document.querySelectorAll('.auth-tab').forEach(tab => tab.classList.remove('active'));
            event.target.classList.add('active');

            if (type === 'login') {
                document.getElementById('login-form').style.display = 'block';
                document.getElementById('register-form').style.display = 'none';
            } else {
                document.getElementById('login-form').style.display = 'none';
                document.getElementById('register-form').style.display = 'block';
            }
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
                const isLogin = document.getElementById('login-form').style.display !== 'none';
                const email = isLogin ? document.getElementById('login-email').value : document.getElementById('register-email').value;
                const password = isLogin ? document.getElementById('login-password').value : document.getElementById('register-password').value;

                if (!email || !password) {
                    alert('Veuillez remplir tous les champs obligatoires.');
                    return;
                }

                // Simuler une confirmation de rendez-vous
                setTimeout(() => {
                    alert('✅ Rendez-vous confirmé avec succès!\n\nVous recevrez un email de confirmation dans quelques instants.\n\nN\'oubliez pas d\'apporter votre carte vitale et une pièce d\'identité.');
                }, 500);
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
            document.getElementById('next-btn').textContent = currentStep === 4 ? 'Confirmer le rendez-vous' : 'Suivant';

            if (currentStep === 3 && document.getElementById('calendar-grid').children.length === 0) {
                generateCalendar();
            }
        }

        function updateSummary() {
            const department = document.getElementById('department').selectedOptions[0].textContent;
            const reason = document.getElementById('reason').selectedOptions[0].textContent;
            const dateStr = selectedDate.toLocaleDateString('fr-FR', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            document.getElementById('summary-department').textContent = department;
            document.getElementById('summary-professional').textContent = selectedProfessional.name;
            document.getElementById('summary-reason').textContent = reason;
            document.getElementById('summary-date').textContent = dateStr;
            document.getElementById('summary-time').textContent = selectedTime;
        }

        // Initialisation
        updateSteps();
    </script>
</body>
</html>
