<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prise de Rendez-vous - Clinique</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('front/css/rdv.css') }}">
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
                <div class="calendar-header-day">D</div>
                <div class="calendar-header-day">L</div>
                <div class="calendar-header-day">M</div>
                <div class="calendar-header-day">M</div>
                <div class="calendar-header-day">J</div>
                <div class="calendar-header-day">V</div>
                <div class="calendar-header-day">S</div>
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
            <div class="auth-tabs">
                <div class="auth-tab active" onclick="showAuth('login')">
                    🔑 Se connecter
                </div>
                <div class="auth-tab" onclick="showAuth('register')">
                    ✨ Créer un compte
                </div>
            </div>

            <div class="auth-form" id="login-form">
                <div class="auth-header">
                    <div class="auth-icon">🔐</div>
                    <h3>Connexion</h3>
                    <p>Connectez-vous à votre compte patient</p>
                </div>

                <div class="input-group">
                    <div class="input-label">
                        <span class="label-icon">📱</span>
                        <span>Numéro de téléphone</span>
                    </div>
                    <input type="tel" id="login-phone" class="form-control" placeholder="Ex: 600 00 00 00" required>
                    <div class="input-border"></div>
                </div>

                <div class="input-group">
                    <div class="input-label">
                        <span class="label-icon">🔒</span>
                        <span>Mot de passe</span>
                    </div>
                    <div class="password-input-wrapper">
                        <input type="password" id="login-password" class="form-control" placeholder="Saisissez votre mot de passe" required>
                        <button type="button" class="password-toggle-btn" onclick="togglePassword('login-password')">
                            <span class="toggle-icon">👁️</span>
                        </button>
                    </div>
                    <div class="input-border"></div>
                </div>

                <div class="auth-options">
                    <label class="remember-me">
                        <input type="checkbox" id="remember-login">
                        <span class="checkmark"></span>
                        <span class="remember-text">Se souvenir de moi</span>
                    </label>
                    <a href="#" class="forgot-password">Mot de passe oublié ?</a>
                </div>
            </div>

            <div class="auth-form" id="register-form" style="display: none;">
                <div class="auth-header">
                    <div class="auth-icon">✨</div>
                    <h3>Créer un compte</h3>
                    <p>Rejoignez-nous pour gérer vos rendez-vous</p>
                </div>

                <div class="input-group">
                    <div class="input-label">
                        <span class="label-icon">👤</span>
                        <span>Nom complet</span>
                    </div>
                    <input type="text" id="register-fullname" class="form-control" placeholder="Ex: Hawaou Barry" required>
                    <div class="input-border"></div>
                </div>

                <div class="input-group">
                    <div class="input-label">
                        <span class="label-icon">📱</span>
                        <span>Numéro de téléphone</span>
                    </div>
                    <input type="tel" id="register-phone" class="form-control" placeholder="Ex: +224 600 00 00 00" required>
                    <div class="input-border"></div>
                </div>

                <div class="input-group">
                    <div class="input-label">
                        <span class="label-icon">🔒</span>
                        <span>Mot de passe</span>
                    </div>
                    <div class="password-input-wrapper">
                        <input type="password" id="register-password" class="form-control" placeholder="Minimum 6 caractères" required>
                        <button type="button" class="password-toggle-btn" onclick="togglePassword('register-password')">
                            <span class="toggle-icon">👁️</span>
                        </button>
                    </div>
                    <div class="input-border"></div>
                    <div class="password-strength" id="password-strength">
                        <div class="strength-bar">
                            <div class="strength-fill"></div>
                        </div>
                        <span class="strength-text">Mot de passe requis</span>
                    </div>
                </div>

                <div class="input-group">
                    <div class="input-label">
                        <span class="label-icon">🔐</span>
                        <span>Confirmer le mot de passe</span>
                    </div>
                    <div class="password-input-wrapper">
                        <input type="password" id="register-password-confirm" class="form-control" placeholder="Retapez votre mot de passe" required>
                        <button type="button" class="password-toggle-btn" onclick="togglePassword('register-password-confirm')">
                            <span class="toggle-icon">👁️</span>
                        </button>
                    </div>
                    <div class="input-border"></div>
                </div>

                <div class="auth-options">
                    <label class="terms-agreement">
                        <input type="checkbox" id="agree-terms" required>
                        <span class="checkmark"></span>
                        <span class="terms-text">J'accepte les <a href="#">conditions d'utilisation</a> et la <a href="#">politique de confidentialité</a></span>
                    </label>
                </div>
            </div>
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

    // Scroll vers le haut pour voir l'alerte sur mobile
    alertContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

    setTimeout(() => {
        alertContainer.innerHTML = '';
    }, 5000);
}

// Fonction pour afficher/masquer le loader
function showLoading(show = true) {
    document.getElementById('loading').style.display = show ? 'block' : 'none';
    isProcessing = show;

    // Désactiver les boutons pendant le traitement
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

// Fonction pour vérifier la force du mot de passe
function checkPasswordStrength(password) {
    let strength = 0;
    let feedback = '';

    if (password.length >= 6) strength += 25;
    if (password.match(/[a-z]/)) strength += 25;
    if (password.match(/[A-Z]/)) strength += 25;
    if (password.match(/[0-9]/) || password.match(/[^a-zA-Z0-9]/)) strength += 25;

    if (strength === 0) {
        feedback = 'Mot de passe requis';
    } else if (strength <= 25) {
        feedback = 'Mot de passe faible';
    } else if (strength <= 50) {
        feedback = 'Mot de passe moyen';
    } else if (strength <= 75) {
        feedback = 'Mot de passe fort';
    } else {
        feedback = 'Mot de passe très fort';
    }

    return { strength, feedback };
}

// Fonction pour mettre à jour l'indicateur de force du mot de passe
function updatePasswordStrength() {
    const passwordInput = document.getElementById('register-password');
    const strengthFill = document.querySelector('.strength-fill');
    const strengthText = document.querySelector('.strength-text');

    if (!passwordInput || !strengthFill || !strengthText) return;

    const password = passwordInput.value;
    const { strength, feedback } = checkPasswordStrength(password);

    strengthFill.style.width = strength + '%';
    strengthText.textContent = feedback;

    // Couleurs selon la force
    if (strength <= 25) {
        strengthFill.style.background = '#ff4444';
    } else if (strength <= 50) {
        strengthFill.style.background = '#ffaa00';
    } else if (strength <= 75) {
        strengthFill.style.background = '#4CAF50';
    } else {
        strengthFill.style.background = 'linear-gradient(90deg, #4CAF50, #45a049)';
    }
}

// Fonction pour valider le format du téléphone
function validatePhoneNumber(phone) {
    // Regex simple pour numéros guinéens (+224 ou 224 ou commençant par 6/7)
    const phoneRegex = /^(\+224|224)?[67]\d{8}$/;
    return phoneRegex.test(phone.replace(/\s/g, ''));
}

// Fonction pour formater le numéro de téléphone en temps réel
function formatPhoneNumber(input) {
    let value = input.value.replace(/\D/g, '');
    
    if (value.startsWith('224')) {
        value = '+' + value;
    } else if (!value.startsWith('+224') && value.length > 0) {
        if (value.startsWith('6') || value.startsWith('7')) {
            value = '+224 ' + value;
        }
    }

    // Formatage avec espaces
    if (value.startsWith('+224')) {
        value = value.replace(/(\+224)(\d{3})(\d{2})(\d{2})(\d{2})/, '$1 $2 $3 $4 $5');
    }

    input.value = value;
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
        const response = await makeRequest(`${baseUrl}/api/professionals/${departmentId}`);
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

    // Réinitialiser les boutons
    document.querySelectorAll('.quick-date-btn').forEach(btn => btn.classList.remove('selected'));
    event.target.classList.add('selected');

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
        if (day && !day.classList.contains('disabled')) {
            selectDate(targetDate, day);
        }
    }, 100);
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
            const isLogin = document.getElementById('login-form').style.display !== 'none';
            if (isLogin) {
                const phone = document.getElementById('login-phone').value.trim();
                const password = document.getElementById('login-password').value;

                if (!phone) {
                    showAlert('Veuillez saisir votre numéro de téléphone');
                    addFieldError('login-phone');
                    return false;
                }
                if (!validatePhoneNumber(phone)) {
                    showAlert('Numéro de téléphone invalide (format: 600000000)');
                    addFieldError('login-phone');
                    return false;
                }
                if (!password) {
                    showAlert('Veuillez saisir votre mot de passe');
                    addFieldError('login-password');
                    return false;
                }
                
            } else {

                const fullname = document.getElementById('register-fullname').value.trim();
                const phone = document.getElementById('register-phone').value.trim();
                const password = document.getElementById('register-password').value;
                const confirmPassword = document.getElementById('register-password-confirm').value;
                const agreeTerms = document.getElementById('agree-terms').checked;

                if (!fullname) {
                    showAlert('Veuillez saisir votre nom complet');
                    addFieldError('register-fullname');
                    return false;
                }
                if (fullname.length < 2) {
                    showAlert('Le nom doit contenir au moins 2 caractères');
                    addFieldError('register-fullname');
                    return false;
                }
                if (!phone) {
                    showAlert('Veuillez saisir votre numéro de téléphone');
                    addFieldError('register-phone');
                    return false;
                }
                if (!validatePhoneNumber(phone)) {
                    showAlert('Numéro de téléphone invalide (format: +224 600 00 00 00)');
                    addFieldError('register-phone');
                    return false;
                }
                if (!password) {
                    showAlert('Veuillez saisir un mot de passe');
                    addFieldError('register-password');
                    return false;
                }
                if (password.length < 6) {
                    showAlert('Le mot de passe doit contenir au moins 6 caractères');
                    addFieldError('register-password');
                    return false;
                }
                if (password !== confirmPassword) {
                    showAlert('Les mots de passe ne correspondent pas');
                    addFieldError('register-password-confirm');
                    return false;
                }
                if (!agreeTerms) {
                    showAlert('Veuillez accepter les conditions d\'utilisation');
                    return false;
                }
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
        // Finaliser le rendez-vous
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
    const nextBtn = document.getElementById('next-btn');

    if (currentStep === 4) {
        nextBtn.innerHTML = '🎯 Confirmer';
        nextBtn.style.background = 'linear-gradient(135deg, #4CAF50 0%, #45a049 100%)';
    } else {
        nextBtn.innerHTML = 'Suivant →';
        nextBtn.style.background = 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)';
    }

    if (currentStep === 3 && document.getElementById('calendar-grid').children.length === 0) {
        generateCalendar();
    }

    // Scroll vers le haut de l'étape sur mobile
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

// Gestion de l'authentification
function showAuth(type) {
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    const tabs = document.querySelectorAll('.auth-tab');

    tabs.forEach(tab => tab.classList.remove('active'));

    if (type === 'login') {
        loginForm.style.display = 'block';
        registerForm.style.display = 'none';
        tabs[0].classList.add('active');
    } else {
        loginForm.style.display = 'none';
        registerForm.style.display = 'block';
        tabs[1].classList.add('active');
    }
}

async function authenticateUser(name, phone, password, isLogin = true) {
    const url = isLogin ? `${baseUrl}/api/login` : `${baseUrl}/api/register`;
    const data = {
        phone: phone,
        password: password
    };

    if (!isLogin) {
        data.name = name;
    }

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
        const response = await makeRequest(`${baseUrl}/api/appointments`, {
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

        const isLogin = document.getElementById('login-form').style.display !== 'none';
        const phone = isLogin ?
            document.getElementById('login-phone').value.trim() :
            document.getElementById('register-phone').value.trim();
        const password = isLogin ?
            document.getElementById('login-password').value :
            document.getElementById('register-password').value;

        const name = isLogin ?
            null :
            document.getElementById('register-fullname').value.trim();

        const response = await authenticateUser(name, phone, password, isLogin);

        if (response.error) {
            throw new Error(response.message || 'Erreur d\'authentification');
        }

        const userId = response.patient.id;

        // Enregistrement du rendez-vous
        const appointmentResult = await saveAppointment(userId);

        showLoading(false);

        // Afficher le succès
        showAlert('🎉 Rendez-vous confirmé avec succès! Vous recevrez un sms de confirmation.', 'success');

        // Réinitialiser le formulaire après un délai
        setTimeout(() => {
            resetForm();
        }, 10000);

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
    document.getElementById('login-phone').value = '';
    document.getElementById('login-password').value = '';
    document.getElementById('register-fullname').value = '';
    document.getElementById('register-phone').value = '';
    document.getElementById('register-password').value = '';
    document.getElementById('register-password-confirm').value = '';

    document.querySelectorAll('.professional-card').forEach(card => card.classList.remove('selected'));
    document.querySelectorAll('.calendar-day').forEach(day => day.classList.remove('selected'));
    document.querySelectorAll('.time-slot').forEach(slot => slot.classList.remove('selected'));
    document.querySelectorAll('.quick-date-btn').forEach(btn => btn.classList.remove('selected'));

    document.getElementById('professionals-grid').innerHTML = '';
    document.getElementById('time-slots').innerHTML = '';

    updateSteps();
    showAuth('login');
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
    loadDepartments();
    generateCalendar();

    // Gestion de la touche Entrée
    document.addEventListener('keypress', (e) => {
        if (e.key === 'Enter' && !isProcessing) {
            nextStep();
        }
    });

    // Amélioration tactile pour mobile
    document.addEventListener('touchstart', (e) => {
        if (e.target.classList.contains('professional-card') || 
            e.target.classList.contains('calendar-day') || 
            e.target.classList.contains('time-slot') ||
            e.target.classList.contains('btn')) {
            handleTouchFeedback(e.target);
        }
    });

    // Empêcher le zoom sur les inputs sur iOS
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