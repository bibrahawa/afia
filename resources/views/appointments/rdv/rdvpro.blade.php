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
            max-width: 600px;
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

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px;
            margin-top: 15px;
        }

        .calendar-day {
            padding: 10px;
            text-align: center;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }

        .calendar-day:hover {
            background: #f0f8ff;
            border-color: #4facfe;
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

        .time-slots {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }

        .time-slot {
            padding: 10px;
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
        }

        .time-slot.selected {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            border-color: #4facfe;
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

            .calendar-grid {
                grid-template-columns: repeat(7, 1fr);
                gap: 3px;
            }

            .calendar-day {
                padding: 8px 4px;
                font-size: 12px;
            }

            .time-slots {
                grid-template-columns: repeat(3, 1fr);
            }

            .buttons {
                flex-direction: column;
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
                    <label>Date</label>
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

        const professionals = {
            medecine: [
                { name: "Dr. Martin Dubois", speciality: "Médecin généraliste" },
                { name: "Dr. Sophie Laurent", speciality: "Médecin généraliste" }
            ],
            cardiologie: [
                { name: "Dr. Pierre Moreau", speciality: "Cardiologue" },
                { name: "Dr. Marie Petit", speciality: "Cardiologue" }
            ],
            dermatologie: [
                { name: "Dr. Jean Dupont", speciality: "Dermatologue" },
                { name: "Dr. Claire Simon", speciality: "Dermatologue" }
            ],
            pediatrie: [
                { name: "Dr. Anne Lefèvre", speciality: "Pédiatre" },
                { name: "Dr. Paul Roux", speciality: "Pédiatre" }
            ],
            orthopédie: [
                { name: "Dr. Michel Bernard", speciality: "Orthopédiste" },
                { name: "Dr. Nathalie Garnier", speciality: "Orthopédiste" }
            ],
            gynécologie: [
                { name: "Dr. Isabelle Morel", speciality: "Gynécologue" },
                { name: "Dr. Catherine Rousseau", speciality: "Gynécologue" }
            ]
        };

        const timeSlots = ["09:00", "09:30", "10:00", "10:30", "11:00", "11:30", "14:00", "14:30", "15:00", "15:30", "16:00", "16:30"];

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
                        <div style="color: #666; font-size: 14px;">${prof.speciality}</div>
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
            grid.innerHTML = '';

            const today = new Date();
            const currentMonth = today.getMonth();
            const currentYear = today.getFullYear();

            for (let i = 0; i < 30; i++) {
                const date = new Date(today);
                date.setDate(today.getDate() + i);

                const dayDiv = document.createElement('div');
                dayDiv.className = 'calendar-day';
                dayDiv.textContent = date.getDate();
                dayDiv.onclick = () => selectDate(dayDiv, date);

                if (date.getDay() === 0 || date.getDay() === 6) {
                    dayDiv.classList.add('disabled');
                    dayDiv.onclick = null;
                }

                grid.appendChild(dayDiv);
            }
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

            timeSlots.forEach(time => {
                const slot = document.createElement('div');
                slot.className = 'time-slot';
                slot.textContent = time;
                slot.onclick = () => selectTime(slot, time);
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
