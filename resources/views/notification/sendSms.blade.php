@extends('layouts.backend')

@section('title', 'Envoyer SMS')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- En-tête -->
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-t-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold">Envoyer SMS</h1>
                        <p class="text-indigo-100 mt-2">Envoyez des messages SMS à vos clients</p>
                    </div>
                    <div class="text-right">
                        <div class="bg-indigo-500 px-3 py-1 rounded-full text-sm">
                            <span id="credit-balance">Crédit: 1,250 SMS</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-lg rounded-b-lg">
                <!-- Onglets -->
                <div class="border-b border-gray-200">
                    <nav class="flex space-x-8 px-6" aria-label="Tabs">
                        <button class="tab-btn active py-4 px-1 border-b-2 border-indigo-500 font-medium text-sm text-indigo-600" data-tab="single">
                            SMS Individuel
                        </button>
                        <button class="tab-btn py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300" data-tab="bulk">
                            SMS Groupé
                        </button>
                        <button class="tab-btn py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300" data-tab="template">
                            Modèles
                        </button>
                    </nav>
                </div>

                <!-- Contenu Onglet SMS Individuel -->
                <div id="tab-single" class="tab-content p-6">
                    <form action="{{ route('sms.send') }}" method="POST" id="single-sms-form">
                        @csrf
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Formulaire principal -->
                            <div class="space-y-6">
                                <!-- Destinataire -->
                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                        Numéro de téléphone *
                                    </label>
                                    <div class="relative">
                                        <select class="absolute left-3 top-3 border-none bg-transparent text-sm text-gray-600" name="country_code">
                                            <option value="+224">🇬🇳 +224</option>
                                            <option value="+33">🇫🇷 +33</option>
                                            <option value="+1">🇺🇸 +1</option>
                                        </select>
                                        <input type="tel" id="phone" name="phone" required
                                            class="block w-full pl-20 pr-10 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                            placeholder="Entrez le numéro">
                                        <button type="button" class="absolute right-3 top-3 text-gray-400 hover:text-indigo-600" title="Carnet d'adresses">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">Format: +224 XXX XXX XXX</p>
                                </div>

                                <!-- Nom de l'expéditeur -->
                                <div>
                                    <label for="sender_name" class="block text-sm font-medium text-gray-700 mb-2">
                                        Nom de l'expéditeur *
                                    </label>
                                    <select id="sender_name" name="sender_name" required
                                            class="block w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                        <option value="CauriWallet">CauriWallet</option>
                                        <option value="MyCauri">MyCauri</option>
                                        <option value="Support">Support</option>
                                        <option value="Promo">Promo</option>
                                    </select>
                                </div>

                                <!-- Type de message -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Type de message</label>
                                    <div class="grid grid-cols-2 gap-3">
                                        <label class="relative flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                                            <input type="radio" name="message_type" value="notification" class="sr-only" checked>
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0">
                                                    <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M10 2L3 7v11c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V7l-7-5z"/>
                                                    </svg>
                                                </div>
                                                <span class="ml-3 text-sm font-medium text-gray-900">Notification</span>
                                            </div>
                                        </label>
                                        <label class="relative flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                                            <input type="radio" name="message_type" value="marketing" class="sr-only">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0">
                                                    <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
                                                    </svg>
                                                </div>
                                                <span class="ml-3 text-sm font-medium text-gray-900">Marketing</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Programmation -->
                                <div>
                                    <label class="flex items-center">
                                        <input type="checkbox" id="schedule_message" name="schedule_message" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        <span class="ml-2 text-sm font-medium text-gray-700">Programmer l'envoi</span>
                                    </label>
                                    <div id="schedule_fields" class="mt-3 grid grid-cols-2 gap-3 hidden">
                                        <input type="date" name="schedule_date" class="border border-gray-300 rounded-lg px-3 py-2">
                                        <input type="time" name="schedule_time" class="border border-gray-300 rounded-lg px-3 py-2">
                                    </div>
                                </div>
                            </div>

                            <!-- Aperçu en temps réel -->
                            <div class="lg:sticky lg:top-6">
                                <div class="bg-gray-50 rounded-lg p-4 border">
                                    <h3 class="text-sm font-medium text-gray-900 mb-3">Aperçu du SMS</h3>
                                    <div class="bg-white rounded-lg shadow-sm border p-4 mb-4">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-xs font-medium text-gray-500" id="preview-sender">CauriWallet</span>
                                            <span class="text-xs text-gray-400">Maintenant</span>
                                        </div>
                                        <div class="bg-blue-500 text-white rounded-lg p-3 max-w-xs">
                                            <p class="text-sm" id="preview-message">Votre message apparaîtra ici...</p>
                                        </div>
                                    </div>
                                    <div class="flex justify-between text-xs text-gray-500">
                                        <span>Caractères: <span id="char-count">0</span>/160</span>
                                        <span>SMS: <span id="sms-count">1</span></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Message -->
                        <div class="mt-6">
                            <label for="message" class="block text-sm font-medium text-gray-700 mb-2">
                                Message *
                            </label>
                            <div class="relative">
                                <textarea id="message" name="message" rows="4" required
                                        class="block w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none"
                                        placeholder="Tapez votre message ici..."></textarea>
                                <div class="absolute bottom-3 right-3 flex space-x-2">
                                    <button type="button" class="text-gray-400 hover:text-indigo-600" title="Insérer emoji">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </button>
                                    <button type="button" class="text-gray-400 hover:text-indigo-600" title="Variables">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="mt-2 flex justify-between">
                                <div class="flex space-x-4 text-xs">
                                    <button type="button" class="text-indigo-600 hover:text-indigo-500 font-medium" onclick="insertVariable('{nom}')">
                                        + {nom}
                                    </button>
                                    <button type="button" class="text-indigo-600 hover:text-indigo-500 font-medium" onclick="insertVariable('{solde}')">
                                        + {solde}
                                    </button>
                                    <button type="button" class="text-indigo-600 hover:text-indigo-500 font-medium" onclick="insertVariable('{date}')">
                                        + {date}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-between mt-8 pt-6 border-t border-gray-200">
                            <div class="flex items-center space-x-4">
                                <button type="button" class="text-sm text-gray-600 hover:text-gray-800 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a4 4 0 118 0v4"></path>
                                        <path d="M6 21V9a2 2 0 012-2h8a2 2 0 012 2v12l-6-2-6 2z"></path>
                                    </svg>
                                    Enregistrer comme modèle
                                </button>
                            </div>
                            <div class="flex space-x-3">
                                <button type="button" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    Aperçu
                                </button>
                                <button type="submit" class="px-6 py-2 bg-indigo-600 border border-transparent rounded-lg text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                        </svg>
                                        Envoyer SMS
                                    </span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Contenu Onglet SMS Groupé -->
                <div id="tab-bulk" class="tab-content p-6 hidden">
                    <form action="{{ route('sms.send-bulk') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="space-y-6">
                            <!-- Sélection des destinataires -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-4">Sélectionner les destinataires</label>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <!-- Import fichier -->
                                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-indigo-400 cursor-pointer" onclick="document.getElementById('file-upload').click()">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <p class="mt-2 text-sm text-gray-600">Import fichier CSV/Excel</p>
                                        <input id="file-upload" type="file" name="contacts_file" accept=".csv,.xlsx" class="hidden">
                                    </div>

                                    <!-- Groupes de contacts -->
                                    <div class="border border-gray-300 rounded-lg p-4">
                                        <h4 class="font-medium text-gray-900 mb-3">Groupes de contacts</h4>
                                        <div class="space-y-2">
                                            <label class="flex items-center">
                                                <input type="checkbox" name="groups[]" value="clients" class="rounded border-gray-300 text-indigo-600">
                                                <span class="ml-2 text-sm">Clients (1,234)</span>
                                            </label>
                                            <label class="flex items-center">
                                                <input type="checkbox" name="groups[]" value="prospects" class="rounded border-gray-300 text-indigo-600">
                                                <span class="ml-2 text-sm">Prospects (567)</span>
                                            </label>
                                            <label class="flex items-center">
                                                <input type="checkbox" name="groups[]" value="vip" class="rounded border-gray-300 text-indigo-600">
                                                <span class="ml-2 text-sm">VIP (89)</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Saisie manuelle -->
                                    <div class="border border-gray-300 rounded-lg p-4">
                                        <h4 class="font-medium text-gray-900 mb-3">Saisie manuelle</h4>
                                        <textarea name="manual_numbers" rows="4" 
                                                class="w-full px-3 py-2 border border-gray-300 rounded text-sm"
                                                placeholder="Un numéro par ligne&#10;+224123456789&#10;+224987654321"></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Configuration -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="bulk_sender" class="block text-sm font-medium text-gray-700 mb-2">Expéditeur</label>
                                    <select id="bulk_sender" name="sender_name" class="block w-full border border-gray-300 rounded-lg px-3 py-2">
                                        <option value="CauriWallet">CauriWallet</option>
                                        <option value="MyCauri">MyCauri</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Vitesse d'envoi</label>
                                    <select name="sending_speed" class="block w-full border border-gray-300 rounded-lg px-3 py-2">
                                        <option value="normal">Normal (10 SMS/min)</option>
                                        <option value="fast">Rapide (30 SMS/min)</option>
                                        <option value="slow">Lent (5 SMS/min)</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Message groupé -->
                            <div>
                                <label for="bulk_message" class="block text-sm font-medium text-gray-700 mb-2">Message</label>
                                <textarea id="bulk_message" name="message" rows="4" required
                                        class="block w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                        placeholder="Message pour envoi groupé..."></textarea>
                            </div>

                            <!-- Récapitulatif -->
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                <div class="flex">
                                    <svg class="flex-shrink-0 h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-yellow-800">Récapitulatif</h3>
                                        <div class="mt-2 text-sm text-yellow-700">
                                            <p>Destinataires sélectionnés: <span id="recipients-count">0</span></p>
                                            <p>Coût estimé: <span id="estimated-cost">0</span> crédits SMS</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex justify-end space-x-3">
                                <button type="button" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    Tester sur 1 numéro
                                </button>
                                <button type="submit" class="px-6 py-2 bg-indigo-600 border border-transparent rounded-lg text-sm font-medium text-white hover:bg-indigo-700">
                                    Envoyer à tous
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Contenu Onglet Modèles -->
                <div id="tab-template" class="tab-content p-6 hidden">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Modèles prédéfinis -->
                        <div class="border border-gray-300 rounded-lg p-4 hover:border-indigo-400 cursor-pointer template-card" data-template="welcome">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h4 class="font-medium text-gray-900">Bienvenue</h4>
                                    <p class="text-sm text-gray-600 mt-1">Message de bienvenue pour nouveaux clients</p>
                                </div>
                                <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">Populaire</span>
                            </div>
                            <div class="mt-3 p-3 bg-gray-50 rounded text-sm text-gray-700">
                                "Bienvenue chez CauriWallet ! Votre compte est maintenant actif..."
                            </div>
                        </div>

                        <div class="border border-gray-300 rounded-lg p-4 hover:border-indigo-400 cursor-pointer template-card" data-template="transaction">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h4 class="font-medium text-gray-900">Confirmation transaction</h4>
                                    <p class="text-sm text-gray-600 mt-1">Notification de transaction</p>
                                </div>
                            </div>
                            <div class="mt-3 p-3 bg-gray-50 rounded text-sm text-gray-700">
                                "Transaction de {montant} GNF effectuée avec succès..."
                            </div>
                        </div>

                        <div class="border border-gray-300 rounded-lg p-4 hover:border-indigo-400 cursor-pointer template-card" data-template="promotion">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h4 class="font-medium text-gray-900">Promotion</h4>
                                    <p class="text-sm text-gray-600 mt-1">Message promotionnel</p>
                                </div>
                                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">Marketing</span>
                            </div>
                            <div class="mt-3 p-3 bg-gray-50 rounded text-sm text-gray-700">
                                "Profitez de notre offre spéciale ! Réduction de 10%..."
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- Modal Carnet d'adresses -->
<div id="contacts-modal" class="fixed inset-0 z-50 overflow-y-auto hidden">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center">
        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"></div>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Carnet d'adresses</h3>
                <div class="space-y-2 max-h-60 overflow-y-auto">
                    <!-- Liste des contacts sera chargée ici -->
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" onclick="closeContactsModal()">
                    Fermer
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion des onglets
    const tabs = document.querySelectorAll('.tab-btn');
    const contents = document.querySelectorAll('.tab-content');

    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const tabName = this.dataset.tab;
            
            // Reset tous les onglets
            tabs.forEach(t => {
                t.classList.remove('active', 'border-indigo-500', 'text-indigo-600');
                t.classList.add('border-transparent', 'text-gray-500');
            });
            
            // Activer l'onglet cliqué
            this.classList.add('active', 'border-indigo-500', 'text-indigo-600');
            this.classList.remove('border-transparent', 'text-gray-500');
            
            // Afficher le contenu correspondant
            contents.forEach(content => content.classList.add('hidden'));
            document.getElementById(`tab-${tabName}`).classList.remove('hidden');
        });
    });

    // Compteur de caractères et SMS
    const messageTextarea = document.getElementById('message');
    const charCount = document.getElementById('char-count');
    const smsCount = document.getElementById('sms-count');
    const previewMessage = document.getElementById('preview-message');

    messageTextarea.addEventListener('input', function() {
        const length = this.value.length;
        const smsNumber = Math.ceil(length / 160) || 1;
        
        charCount.textContent = length;
        smsCount.textContent = smsNumber;
        previewMessage.textContent = this.value || 'Votre message apparaîtra ici...';
    });

    // Aperçu expéditeur
    const senderSelect = document.getElementById('sender_name');
    const previewSender = document.getElementById('preview-sender');

    senderSelect.addEventListener('change', function() {
        previewSender.textContent = this.value;
    });

    // Programmation
    const scheduleCheckbox = document.getElementById('schedule_message');
    const scheduleFields = document.getElementById('schedule_fields');

    scheduleCheckbox.addEventListener('change', function() {
        if (this.checked) {
            scheduleFields.classList.remove('hidden');
        } else {
            scheduleFields.classList.add('hidden');
        }
    });

    // Type de message
    const messageTypes = document.querySelectorAll('input[name="message_type"]');
    messageTypes.forEach(radio => {
        radio.addEventListener('change', function() {
            const parent = this.closest('label');
            messageTypes.forEach(r => {
                r.closest('label').classList.remove('border-indigo-500', 'bg-indigo-50');
            });
            if (this.checked) {
                parent.classList.add('border-indigo-500', 'bg-indigo-50');
            }
        });
    });

    // Modèles
    const templateCards = document.querySelectorAll('.template-card');
    templateCards.forEach(card => {
        card.addEventListener('click', function() {
            const template = this.dataset.template;
            let message = '';
            
            switch(template) {
                case 'welcome':
                    message = 'Bienvenue chez CauriWallet ! Votre compte est maintenant actif. Profitez de nos services de transfert d\'argent rapide et sécurisé.';
                    break;
                case 'transaction':
                    message = 'Transaction de {montant} GNF effectuée avec succès vers {destinataire}. Nouveau solde: {solde} GNF. Merci de votre confiance.';
                    break;
                case 'promotion':
                    message = 'Profitez de notre offre spéciale ! Réduction de 10% sur tous les transferts ce mois-ci. Code promo: CAURI10';
                    break;
            }
            
            messageTextarea.value = message;
            messageTextarea.dispatchEvent(new Event('input'));
            
            // Retour à l'onglet SMS individuel
            tabs[0].click();
        });
    });

    // Validation du formulaire
    const form = document.getElementById('single-sms-form');
    form.addEventListener('submit', function(e) {
        const phone = document.getElementById('phone').value;
        const message = document.getElementById('message').value;
        
        if (!phone || !message) {
            e.preventDefault();
            alert('Veuillez remplir tous les champs obligatoires.');
            return;
        }

        // Validation du numéro de téléphone
        const phoneRegex = /^[0-9]{8,15}$/;
        if (!phoneRegex.test(phone.replace(/\s+/g, ''))) {
            e.preventDefault();
            alert('Format de numéro de téléphone invalide.');
            return;
        }

        // Confirmation avant envoi
        const confirm = window.confirm(`Envoyer SMS à ${phone} ?\n\nMessage: "${message.substring(0, 50)}${message.length > 50 ? '...' : ''}"`);
        if (!confirm) {
            e.preventDefault();
        }
    });

    // Gestion du SMS groupé
    const groupCheckboxes = document.querySelectorAll('input[name="groups[]"]');
    const recipientsCount = document.getElementById('recipients-count');
    const estimatedCost = document.getElementById('estimated-cost');

    function updateBulkSummary() {
        let total = 0;
        groupCheckboxes.forEach(checkbox => {
            if (checkbox.checked) {
                switch(checkbox.value) {
                    case 'clients': total += 1234; break;
                    case 'prospects': total += 567; break;
                    case 'vip': total += 89; break;
                }
            }
        });
        
        recipientsCount.textContent = total;
        estimatedCost.textContent = total;
    }

    groupCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkSummary);
    });

    // Upload de fichier
    const fileUpload = document.getElementById('file-upload');
    fileUpload.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const fileName = file.name;
            const fileSize = (file.size / 1024 / 1024).toFixed(2);
            
            // Afficher info du fichier
            const parent = this.closest('div');
            const info = document.createElement('p');
            info.className = 'mt-2 text-xs text-green-600';
            info.textContent = `Fichier sélectionné: ${fileName} (${fileSize} MB)`;
            
            // Supprimer l'ancienne info si elle existe
            const oldInfo = parent.querySelector('.text-green-600');
            if (oldInfo) oldInfo.remove();
            
            parent.appendChild(info);
        }
    });
});

// Fonctions globales
function insertVariable(variable) {
    const textarea = document.getElementById('message');
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = textarea.value;
    
    textarea.value = text.substring(0, start) + variable + text.substring(end);
    textarea.focus();
    textarea.setSelectionRange(start + variable.length, start + variable.length);
    
    // Trigger input event pour mettre à jour les compteurs
    textarea.dispatchEvent(new Event('input'));
}

function openContactsModal() {
    document.getElementById('contacts-modal').classList.remove('hidden');
    // Ici on chargerait les contacts via AJAX
}

function closeContactsModal() {
    document.getElementById('contacts-modal').classList.add('hidden');
}

// Auto-sauvegarde du brouillon
let autoSaveTimer;
function autoSaveDraft() {
    const formData = {
        phone: document.getElementById('phone').value,
        sender_name: document.getElementById('sender_name').value,
        message: document.getElementById('message').value,
        message_type: document.querySelector('input[name="message_type"]:checked')?.value
    };
    
    localStorage.setItem('sms_draft', JSON.stringify(formData));
}

// Restaurer le brouillon au chargement
window.addEventListener('load', function() {
    const draft = localStorage.getItem('sms_draft');
    if (draft) {
        const data = JSON.parse(draft);
        document.getElementById('phone').value = data.phone || '';
        document.getElementById('sender_name').value = data.sender_name || 'CauriWallet';
        document.getElementById('message').value = data.message || '';
        
        if (data.message_type) {
            const radio = document.querySelector(`input[name="message_type"][value="${data.message_type}"]`);
            if (radio) radio.checked = true;
        }
        
        // Trigger les événements pour mettre à jour l'aperçu
        document.getElementById('message').dispatchEvent(new Event('input'));
        document.getElementById('sender_name').dispatchEvent(new Event('change'));
    }
});

// Auto-sauvegarde toutes les 30 secondes
setInterval(autoSaveDraft, 30000);

// Notification de succès/erreur (à intégrer avec votre système de notifications)
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg ${
        type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
    }`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

// Gestion des erreurs AJAX
function handleAjaxError(xhr) {
    let message = 'Une erreur est survenue';
    
    if (xhr.responseJSON && xhr.responseJSON.message) {
        message = xhr.responseJSON.message;
    } else if (xhr.status === 422) {
        message = 'Données invalides';
    } else if (xhr.status === 429) {
        message = 'Trop de requêtes. Veuillez patienter.';
    }
    
    showNotification(message, 'error');
}

// Validation en temps réel
document.getElementById('phone').addEventListener('blur', function() {
    const phone = this.value.replace(/\s+/g, '');
    const phoneRegex = /^[0-9]{8,15}$/;
    
    if (phone && !phoneRegex.test(phone)) {
        this.classList.add('border-red-300', 'focus:border-red-500', 'focus:ring-red-500');
        this.classList.remove('border-gray-300', 'focus:border-indigo-500', 'focus:ring-indigo-500');
    } else {
        this.classList.remove('border-red-300', 'focus:border-red-500', 'focus:ring-red-500');
        this.classList.add('border-gray-300', 'focus:border-indigo-500', 'focus:ring-indigo-500');
    }
});

// Formatage automatique du numéro
document.getElementById('phone').addEventListener('input', function() {
    let value = this.value.replace(/\D/g, '');
    
    // Formatage pour les numéros guinéens
    if (value.length <= 9) {
        value = value.replace(/(\d{3})(\d{3})(\d{3})/, '$1 $2 $3');
    }
    
    this.value = value;
});
</script>

@endsection
@section('style')

<!-- Styles additionnels -->
<style>
.tab-btn.active {
    border-color: #4f46e5 !important;
    color: #4f46e5 !important;
}

.template-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
    transition: all 0.3s ease;
}

input[type="radio"]:checked + div {
    background-color: #eff6ff;
    border-color: #3b82f6;
}

.fade-in {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Responsive design pour mobile */
@media (max-width: 640px) {
    .container {
        padding-left: 1rem;
        padding-right: 1rem;
    }
    
    .grid-cols-2 {
        grid-template-columns: 1fr;
    }
    
    .lg\:grid-cols-2 {
        grid-template-columns: 1fr;
    }
}
</style>
@endsection