<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Professionnel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.13.3/cdn.min.js" defer></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        [x-cloak] { display: none !important; }
        .fade-in { animation: fadeIn 0.3s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="bg-gray-50 min-h-screen" x-data="professionalDashboard()">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <i class="fas fa-user-md text-2xl text-blue-600"></i>
                    <h1 class="text-xl font-bold text-gray-900">Dashboard Professionnel</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-600">Dr. <span x-text="professional.name"></span></span>
                    <button class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-sign-out-alt"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="flex">
        <div class="w-64 bg-white shadow-lg min-h-screen">
            <div class="p-4">
                <ul class="space-y-2">
                    <li>
                        <button @click="currentView = 'dashboard'"
                                :class="currentView === 'dashboard' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100'"
                                class="w-full flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors">
                            <i class="fas fa-tachometer-alt mr-3"></i>
                            Tableau de bord
                        </button>
                    </li>
                    <li>
                        <button @click="currentView = 'availability'"
                                :class="currentView === 'availability' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100'"
                                class="w-full flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors">
                            <i class="fas fa-calendar-check mr-3"></i>
                            Disponibilités
                        </button>
                    </li>
                    <li>
                        <button @click="currentView = 'leaves'"
                                :class="currentView === 'leaves' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100'"
                                class="w-full flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors">
                            <i class="fas fa-plane mr-3"></i>
                            Congés
                        </button>
                    </li>
                    <li>
                        <button @click="currentView = 'appointments'"
                                :class="currentView === 'appointments' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100'"
                                class="w-full flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors">
                            <i class="fas fa-calendar-alt mr-3"></i>
                            Rendez-vous
                        </button>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-6">
            <!-- Dashboard View -->
            <div x-show="currentView === 'dashboard'" x-cloak class="fade-in">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Tableau de bord</h2>
                    <p class="text-gray-600">Bienvenue, Dr. <span x-text="professional.name"></span></p>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <div class="flex items-center">
                            <div class="p-3 bg-blue-100 rounded-lg">
                                <i class="fas fa-calendar-day text-blue-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-2xl font-bold text-gray-900" x-text="todayAppointments.length"></p>
                                <p class="text-gray-600">Rendez-vous aujourd'hui</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <div class="flex items-center">
                            <div class="p-3 bg-green-100 rounded-lg">
                                <i class="fas fa-calendar-week text-green-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-2xl font-bold text-gray-900" x-text="upcomingAppointments.length"></p>
                                <p class="text-gray-600">Prochains rendez-vous</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <div class="flex items-center">
                            <div class="p-3 bg-yellow-100 rounded-lg">
                                <i class="fas fa-clock text-yellow-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-2xl font-bold text-gray-900" x-text="pendingAppointments"></p>
                                <p class="text-gray-600">En attente</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Today's Appointments -->
                <div class="bg-white rounded-lg shadow-md mb-6">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Rendez-vous d'aujourd'hui</h3>
                    </div>
                    <div class="p-6">
                        <div x-show="todayAppointments.length === 0" class="text-center py-8 text-gray-500">
                            <i class="fas fa-calendar-check text-4xl mb-4"></i>
                            <p>Aucun rendez-vous aujourd'hui</p>
                        </div>
                        <div class="space-y-4">
                            <template x-for="appointment in todayAppointments" :key="appointment.id">
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <div class="flex items-center space-x-4">
                                        <div class="flex-shrink-0">
                                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                                <i class="fas fa-user text-blue-600"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900" x-text="appointment.patient.name"></p>
                                            <p class="text-sm text-gray-500" x-text="appointment.time"></p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <span :class="getStatusColor(appointment.status)"
                                              class="px-3 py-1 rounded-full text-xs font-medium"
                                              x-text="getStatusText(appointment.status)"></span>
                                        <button @click="confirmAppointment(appointment.id)"
                                                x-show="appointment.status === 'pending'"
                                                class="px-3 py-1 bg-green-600 text-white rounded-md text-sm hover:bg-green-700">
                                            Confirmer
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Availability View -->
            <div x-show="currentView === 'availability'" x-cloak class="fade-in">
                <div class="mb-6">
                    <div class="flex justify-between items-center">
                        <h2 class="text-2xl font-bold text-gray-900">Gestion des disponibilités</h2>
                        <button @click="showAvailabilityModal = true"
                                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-plus mr-2"></i>Ajouter une disponibilité
                        </button>
                    </div>
                </div>

                <!-- Availability List -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <template x-for="availability in availabilities" :key="availability.id">
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex justify-between items-start mb-2">
                                        <h4 class="font-medium text-gray-900" x-text="getDayName(availability.day_of_week)"></h4>
                                        <div class="flex space-x-2">
                                            <button @click="editAvailability(availability)"
                                                    class="text-blue-600 hover:text-blue-800">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button @click="deleteAvailability(availability.id)"
                                                    class="text-red-600 hover:text-red-800">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <p class="text-sm text-gray-600 mb-2">
                                        <span x-text="availability.start_time"></span> - <span x-text="availability.end_time"></span>
                                    </p>
                                    <p class="text-sm text-gray-600 mb-2">
                                        Durée: <span x-text="availability.slot_duration"></span> min
                                    </p>
                                    <span :class="availability.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                          class="px-2 py-1 rounded-full text-xs">
                                        <span x-text="availability.is_active ? 'Actif' : 'Inactif'"></span>
                                    </span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Leaves View -->
            <div x-show="currentView === 'leaves'" x-cloak class="fade-in">
                <div class="mb-6">
                    <div class="flex justify-between items-center">
                        <h2 class="text-2xl font-bold text-gray-900">Gestion des congés</h2>
                        <button @click="showLeaveModal = true"
                                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-plus mr-2"></i>Demander un congé
                        </button>
                    </div>
                </div>

                <!-- Leaves List -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="p-6">
                        <div class="space-y-4">
                            <template x-for="leave in leaves" :key="leave.id">
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h4 class="font-medium text-gray-900" x-text="getLeaveTypeText(leave.type)"></h4>
                                            <p class="text-sm text-gray-600 mt-1">
                                                Du <span x-text="formatDate(leave.start_date)"></span> au <span x-text="formatDate(leave.end_date)"></span>
                                            </p>
                                            <p class="text-sm text-gray-600 mt-1" x-show="leave.reason" x-text="leave.reason"></p>
                                        </div>
                                        <span :class="getLeaveStatusColor(leave.status)"
                                              class="px-3 py-1 rounded-full text-xs font-medium"
                                              x-text="leave.status || 'En attente'"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Appointments View -->
            <div x-show="currentView === 'appointments'" x-cloak class="fade-in">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">Gestion des rendez-vous</h2>
                </div>

                <!-- Appointments List -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="p-6">
                        <div class="space-y-4">
                            <template x-for="appointment in appointments" :key="appointment.id">
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex justify-between items-start">
                                        <div class="flex items-center space-x-4">
                                            <div class="flex-shrink-0">
                                                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                                    <i class="fas fa-user text-blue-600"></i>
                                                </div>
                                            </div>
                                            <div>
                                                <h4 class="font-medium text-gray-900" x-text="appointment.patient.name"></h4>
                                                <p class="text-sm text-gray-600">
                                                    <span x-text="formatDate(appointment.appointment_date)"></span> à <span x-text="appointment.appointment_time"></span>
                                                </p>
                                                <p class="text-sm text-gray-600 mt-1" x-show="appointment.notes" x-text="appointment.notes"></p>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <span :class="getStatusColor(appointment.status)"
                                                  class="px-3 py-1 rounded-full text-xs font-medium"
                                                  x-text="getStatusText(appointment.status)"></span>
                                            <button @click="confirmAppointment(appointment.id)"
                                                    x-show="appointment.status === 'pending'"
                                                    class="px-3 py-1 bg-green-600 text-white rounded-md text-sm hover:bg-green-700">
                                                Confirmer
                                            </button>
                                            <button @click="completeAppointment(appointment.id)"
                                                    x-show="appointment.status === 'confirmed'"
                                                    class="px-3 py-1 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700">
                                                Terminer
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Availability Modal -->
    <div x-show="showAvailabilityModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         @click.self="showAvailabilityModal = false">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form @submit.prevent="saveAvailability">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Ajouter une disponibilité</h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Jour de la semaine</label>
                                <select x-model="availabilityForm.day_of_week"
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Sélectionner un jour</option>
                                    <option value="monday">Lundi</option>
                                    <option value="tuesday">Mardi</option>
                                    <option value="wednesday">Mercredi</option>
                                    <option value="thursday">Jeudi</option>
                                    <option value="friday">Vendredi</option>
                                    <option value="saturday">Samedi</option>
                                    <option value="sunday">Dimanche</option>
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Heure de début</label>
                                    <input type="time"
                                           x-model="availabilityForm.start_time"
                                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Heure de fin</label>
                                    <input type="time"
                                           x-model="availabilityForm.end_time"
                                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Durée des créneaux (minutes)</label>
                                <select x-model="availabilityForm.slot_duration"
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="15">15 minutes</option>
                                    <option value="30">30 minutes</option>
                                    <option value="45">45 minutes</option>
                                    <option value="60">1 heure</option>
                                    <option value="90">1h30</option>
                                    <option value="120">2 heures</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Sauvegarder
                        </button>
                        <button type="button"
                                @click="showAvailabilityModal = false"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Annuler
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Leave Modal -->
    <div x-show="showLeaveModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         @click.self="showLeaveModal = false">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form @submit.prevent="saveLeave">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Demander un congé</h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Type de congé</label>
                                <select x-model="leaveForm.type"
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Sélectionner un type</option>
                                    <option value="vacation">Vacances</option>
                                    <option value="sick">Maladie</option>
                                    <option value="conference">Conférence</option>
                                    <option value="other">Autre</option>
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Date de début</label>
                                    <input type="date"
                                           x-model="leaveForm.start_date"
                                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Date de fin</label>
                                    <input type="date"
                                           x-model="leaveForm.end_date"
                                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Raison (optionnel)</label>
                                <textarea x-model="leaveForm.reason"
                                          rows="3"
                                          class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Demander
                        </button>
                        <button type="button"
                                @click="showLeaveModal = false"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Annuler
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function professionalDashboard() {
            return {
                currentView: 'dashboard',
                showAvailabilityModal: false,
                showLeaveModal: false,

                professional: {
                    name: 'Marie Dubois',
                    email: 'marie.dubois@example.com',
                    speciality: 'Cardiologie'
                },

                todayAppointments: [
                    {
                        id: 1,
                        patient: { name: 'Jean Dupont' },
                        time: '09:00',
                        status: 'confirmed'
                    },
                    {
                        id: 2,
                        patient: { name: 'Marie Martin' },
                        time: '10:30',
                        status: 'pending'
                    },
                    {
                        id: 3,
                        patient: { name: 'Pierre Durand' },
                        time: '14:00',
                        status: 'confirmed'
                    }
                ],

                upcomingAppointments: [
                    {
                        id: 4,
                        patient: { name: 'Sophie Moreau' },
                        appointment_date: '2024-07-17',
                        appointment_time: '09:30',
                        status: 'confirmed'
                    },
                    {
                        id: 5,
                        patient: { name: 'Lucas Bernard' },
                        appointment_date: '2024-07-17',
                        appointment_time: '11:00',
                        status: 'pending'
                    }
                ],

                appointments: [
                    {
                        id: 1,
                        patient: { name: 'Jean Dupont' },
                        appointment_date: '2024-07-16',
                        appointment_time: '09:00',
                        status: 'confirmed',
                        notes: 'Consultation de routine'
                    },
                    {
                        id: 2,
                        patient: { name: 'Marie Martin' },
                        appointment_date: '2024-07-16',
                        appointment_time: '10:30',
                        status: 'pending',
                        notes: ''
                    }
                ],

                availabilities: [
                    {
                        id: 1,
                        day_of_week: 'monday',
                        start_time: '09:00',
                        end_time: '17:00',
                        slot_duration: 30,
                        is_active: true
                    },
                    {
                        id: 2,
                        day_of_week: 'tuesday',
                        start_time: '09:00',
                        end_time: '17:00',
                        slot_duration: 30,
                        is_active: true
                    },
                    {
                        id: 3,
                        day_of_week: 'wednesday',
                        start_time: '09:00',
                        end_time: '12:00',
                        slot_duration: 45,
                        is_active: true
                    }
                ],

                leaves: [
                    {
                        id: 1,
                        type: 'vacation',
                        start_date: '2024-08-15',
                        end_date: '2024-08-25',
                        reason: 'Vacances d\'été',
                        status: 'approved'
                    },
                    {
                        id: 2,
                        type: 'conference',
                        start_date: '2024-09-10',
                        end_date: '2024-09-12',
                        reason: 'Congrès de cardiologie',
                        status: 'pending'
                    }
                ],

                availabilityForm: {
                    day_of_week: '',
                    start_time: '',
                    end_time: '',
                    slot_duration: '30'
                },

                leaveForm: {
                    type: '',
                    start_date: '',
                    end_date: '',
                    reason: ''
                },

                get pendingAppointments() {
                    return this.appointments.filter(app => app.status === 'pending').length;
                },

                getDayName(day) {
                    const days = {
                        'monday': 'Lundi',
                        'tuesday': 'Mardi',
                        'wednesday': 'Mercredi',
                        'thursday': 'Jeudi',
                        'friday': 'Vendredi',
                        'saturday': 'Samedi',
                        'sunday': 'Dimanche'
                    };
                    return days[day] || day;
                },

                getStatusColor(status) {
                    const colors = {
                        'pending': 'bg-yellow-100 text-yellow-800',
                        'confirmed': 'bg-green-100 text-green-800',
                        'completed': 'bg-blue-100 text-blue-800',
                        'cancelled': 'bg-red-100 text-red-800'
                    };
                    return colors[status] || 'bg-gray-100 text-gray-800';
                },

                getStatusText(status) {
                    const texts = {
                        'pending': 'En attente',
                        'confirmed': 'Confirmé',
                        'completed': 'Terminé',
                        'cancelled': 'Annulé'
                    };
                    return texts[status] || status;
                },

                getLeaveTypeText(type) {
                    const types = {
                        'vacation': 'Vacances',
                        'sick': 'Congé maladie',
                        'conference': 'Conférence',
                        'other': 'Autre'
                    };
                    return types[type] || type;
                },

                getLeaveStatusColor(status) {
                    const colors = {
                        'pending': 'bg-yellow-100 text-yellow-800',
                        'approved': 'bg-green-100 text-green-800',
                        'rejected': 'bg-red-100 text-red-800'
                    };
                    return colors[status] || 'bg-yellow-100 text-yellow-800';
                },

                formatDate(date) {
                    return new Date(date).toLocaleDateString('fr-FR');
                },

                async confirmAppointment(appointmentId) {
                    try {
                        // Simuler l'appel API
                        await new Promise(resolve => setTimeout(resolve, 500));

                        // Mettre à jour le statut dans todayAppointments
                        const todayIndex = this.todayAppointments.findIndex(app => app.id === appointmentId);
                        if (todayIndex !== -1) {
                            this.todayAppointments[todayIndex].status = 'confirmed';
                        }

                        // Mettre à jour le statut dans appointments
                        const appointmentIndex = this.appointments.findIndex(app => app.id === appointmentId);
                        if (appointmentIndex !== -1) {
                            this.appointments[appointmentIndex].status = 'confirmed';
                        }

                        // Mettre à jour le statut dans upcomingAppointments
                        const upcomingIndex = this.upcomingAppointments.findIndex(app => app.id === appointmentId);
                        if (upcomingIndex !== -1) {
                            this.upcomingAppointments[upcomingIndex].status = 'confirmed';
                        }

                        this.showNotification('Rendez-vous confirmé avec succès', 'success');
                    } catch (error) {
                        this.showNotification('Erreur lors de la confirmation', 'error');
                    }
                },

                async completeAppointment(appointmentId) {
                    try {
                        // Simuler l'appel API
                        await new Promise(resolve => setTimeout(resolve, 500));

                        // Mettre à jour le statut
                        const appointmentIndex = this.appointments.findIndex(app => app.id === appointmentId);
                        if (appointmentIndex !== -1) {
                            this.appointments[appointmentIndex].status = 'completed';
                        }

                        const todayIndex = this.todayAppointments.findIndex(app => app.id === appointmentId);
                        if (todayIndex !== -1) {
                            this.todayAppointments[todayIndex].status = 'completed';
                        }

                        this.showNotification('Rendez-vous marqué comme terminé', 'success');
                    } catch (error) {
                        this.showNotification('Erreur lors de la mise à jour', 'error');
                    }
                },

                async saveAvailability() {
                    try {
                        // Validation
                        if (!this.availabilityForm.day_of_week || !this.availabilityForm.start_time || !this.availabilityForm.end_time) {
                            this.showNotification('Veuillez remplir tous les champs obligatoires', 'error');
                            return;
                        }

                        // Simuler l'appel API
                        await new Promise(resolve => setTimeout(resolve, 500));

                        // Ajouter la nouvelle disponibilité
                        const newAvailability = {
                            id: this.availabilities.length + 1,
                            ...this.availabilityForm,
                            is_active: true
                        };

                        this.availabilities.push(newAvailability);

                        // Réinitialiser le formulaire
                        this.availabilityForm = {
                            day_of_week: '',
                            start_time: '',
                            end_time: '',
                            slot_duration: '30'
                        };

                        this.showAvailabilityModal = false;
                        this.showNotification('Disponibilité ajoutée avec succès', 'success');
                    } catch (error) {
                        this.showNotification('Erreur lors de l\'ajout de la disponibilité', 'error');
                    }
                },

                async saveLeave() {
                    try {
                        // Validation
                        if (!this.leaveForm.type || !this.leaveForm.start_date || !this.leaveForm.end_date) {
                            this.showNotification('Veuillez remplir tous les champs obligatoires', 'error');
                            return;
                        }

                        // Simuler l'appel API
                        await new Promise(resolve => setTimeout(resolve, 500));

                        // Ajouter le nouveau congé
                        const newLeave = {
                            id: this.leaves.length + 1,
                            ...this.leaveForm,
                            status: 'pending'
                        };

                        this.leaves.unshift(newLeave);

                        // Réinitialiser le formulaire
                        this.leaveForm = {
                            type: '',
                            start_date: '',
                            end_date: '',
                            reason: ''
                        };

                        this.showLeaveModal = false;
                        this.showNotification('Demande de congé soumise avec succès', 'success');
                    } catch (error) {
                        this.showNotification('Erreur lors de la soumission de la demande', 'error');
                    }
                },

                editAvailability(availability) {
                    this.availabilityForm = { ...availability };
                    this.showAvailabilityModal = true;
                },

                async deleteAvailability(availabilityId) {
                    if (confirm('Êtes-vous sûr de vouloir supprimer cette disponibilité ?')) {
                        try {
                            // Simuler l'appel API
                            await new Promise(resolve => setTimeout(resolve, 500));

                            // Supprimer la disponibilité
                            this.availabilities = this.availabilities.filter(av => av.id !== availabilityId);

                            this.showNotification('Disponibilité supprimée avec succès', 'success');
                        } catch (error) {
                            this.showNotification('Erreur lors de la suppression', 'error');
                        }
                    }
                },

                showNotification(message, type = 'info') {
                    // Créer une notification temporaire
                    const notification = document.createElement('div');
                    notification.className = `fixed top-4 right-4 z-50 px-4 py-2 rounded-lg shadow-lg transition-all duration-300 ${
                        type === 'success' ? 'bg-green-500 text-white' :
                        type === 'error' ? 'bg-red-500 text-white' :
                        'bg-blue-500 text-white'
                    }`;
                    notification.textContent = message;

                    document.body.appendChild(notification);

                    // Supprimer après 3 secondes
                    setTimeout(() => {
                        notification.remove();
                    }, 3000);
                }
            };
        }
    </script>
</body>
</html>
