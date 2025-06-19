<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ordonnance d'Examen</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        medical: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1'
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 p-6">
    <div class="max-w-4xl mx-auto bg-white shadow-2xl rounded-xl overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-medical-600 to-medical-700 text-white p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="bg-white/20 p-3 rounded-full">
                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 1.414L10.586 9H7a1 1 0 100 2h3.586l-1.293 1.293a1 1 0 101.414 1.414l3-3a1 1 0 000-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold">CLINIQUE APROSAFE</h1>
                        <p class="text-medical-100 text-sm">Consultations gynécologiques • Suivi de grossesse</p>
                    </div>
                </div>
                <div class="text-right">
                    <div class="bg-white/10 p-3 rounded-lg">
                        <p class="text-sm opacity-90">Date :</p>
                        <input type="date" class="bg-transparent border-none text-white placeholder-white/70 text-lg font-medium focus:outline-none" placeholder="../../....">
                    </div>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <p class="opacity-90">Planification familiale • Infertilité</p>
                    <p class="opacity-90">Échographie obstétricale et gynécologique</p>
                </div>
                <div>
                    <p class="opacity-90">Colposcopie • Adresse : Yembéi • Kribi</p>
                    <p class="opacity-90">Tél : (+224) 628 16 44 22 / 661 31 30 30</p>
                </div>
                <div>
                    <p class="opacity-90">Mail : boubacarbinta2015@gmail.com</p>
                    <p class="opacity-90">https://clinique-aprosafe.com</p>
                </div>
            </div>
        </div>

        <!-- Patient Info -->
        <div class="p-6 bg-gray-50 border-b">
            <div class="flex items-center space-x-4">
                <div class="bg-medical-500 p-2 rounded-full">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom et Prénoms :</label>
                    <input type="text" class="w-full border-2 border-gray-200 rounded-lg px-3 py-2 focus:border-medical-500 focus:outline-none transition-colors" placeholder="Saisir le nom complet du patient">
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="p-6">
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-gray-800 mb-2">ORDONNANCE D'EXAMEN</h2>
                <div class="w-24 h-1 bg-medical-500 mx-auto rounded-full"></div>
            </div>

            <!-- Bilan Hormonal -->
            <div class="mb-8">
                <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                    <div class="w-2 h-6 bg-medical-500 rounded-full mr-3"></div>
                    BILAN HORMONAL
                </h3>

                <div class="bg-medical-50 p-6 rounded-xl">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <label class="flex items-center space-x-3 p-3 bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow cursor-pointer">
                            <input type="checkbox" class="w-5 h-5 text-medical-600 rounded focus:ring-medical-500">
                            <span class="font-medium text-gray-700">AMH</span>
                        </label>
                        <label class="flex items-center space-x-3 p-3 bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow cursor-pointer">
                            <input type="checkbox" class="w-5 h-5 text-medical-600 rounded focus:ring-medical-500">
                            <span class="font-medium text-gray-700">PROLACTINE</span>
                        </label>
                    </div>

                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                        <p class="text-sm font-medium text-yellow-800">EXAMENS À RÉALISER ENTRE LE 3e ET LE 5e JOUR DES RÈGLES</p>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <label class="flex items-center space-x-3 p-3 bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow cursor-pointer">
                            <input type="checkbox" class="w-5 h-5 text-medical-600 rounded focus:ring-medical-500">
                            <span class="font-medium text-gray-700">FSH</span>
                        </label>
                        <label class="flex items-center space-x-3 p-3 bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow cursor-pointer">
                            <input type="checkbox" class="w-5 h-5 text-medical-600 rounded focus:ring-medical-500">
                            <span class="font-medium text-gray-700">E2</span>
                        </label>
                        <label class="flex items-center space-x-3 p-3 bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow cursor-pointer">
                            <input type="checkbox" class="w-5 h-5 text-medical-600 rounded focus:ring-medical-500">
                            <span class="font-medium text-gray-700">LH</span>
                        </label>
                        <label class="flex items-center space-x-3 p-3 bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow cursor-pointer">
                            <input type="checkbox" class="w-5 h-5 text-medical-600 rounded focus:ring-medical-500">
                            <span class="font-medium text-gray-700">TSH</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Examen 22e jour -->
            <div class="mb-8">
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-4">
                    <p class="text-sm font-medium text-blue-800">EXAMENS À RÉALISER LE 22e JOUR DES RÈGLES</p>
                </div>

                <label class="flex items-center space-x-3 p-4 bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow cursor-pointer border-2 border-transparent hover:border-medical-200">
                    <input type="checkbox" class="w-5 h-5 text-medical-600 rounded focus:ring-medical-500">
                    <span class="font-medium text-gray-700">PROGESTÉRONE</span>
                </label>
            </div>

            <!-- Examens Échographiques -->
            <div class="mb-8">
                <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                    <div class="w-2 h-6 bg-green-500 rounded-full mr-3"></div>
                    EXAMENS ÉCHOGRAPHIQUES
                </h3>

                <div class="bg-green-50 p-6 rounded-xl">
                    <div class="bg-green-100 border-l-4 border-green-400 p-4 mb-4">
                        <p class="text-sm font-medium text-green-800">À RÉALISER ENTRE LE 3e ET LE 5e JOUR DES RÈGLES</p>
                    </div>

                    <label class="flex items-center space-x-3 p-4 bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow cursor-pointer border-2 border-transparent hover:border-green-200">
                        <input type="checkbox" class="w-5 h-5 text-green-600 rounded focus:ring-green-500">
                        <span class="font-medium text-gray-700">ÉCHOGRAPHIE CFA</span>
                    </label>
                </div>
            </div>

            <!-- Renseignements Cliniques -->
            <div class="mb-8">
                <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                    <div class="w-2 h-6 bg-purple-500 rounded-full mr-3"></div>
                    RENSEIGNEMENTS CLINIQUES
                </h3>

                <div class="bg-purple-50 p-6 rounded-xl">
                    <textarea class="w-full h-32 border-2 border-purple-200 rounded-lg p-4 focus:border-purple-500 focus:outline-none transition-colors resize-none" placeholder="Saisir les renseignements cliniques..."></textarea>
                </div>
            </div>

            <!-- Signature -->
            <div class="text-right">
                <div class="inline-block bg-gray-100 p-6 rounded-lg">
                    <p class="text-lg font-semibold text-gray-800 mb-4">Le Médecin</p>
                    <div class="w-48 h-24 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center text-gray-400">
                        <span class="text-sm">Signature</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="bg-gray-100 px-6 py-4 text-center text-sm text-gray-600 border-t">
            <p class="italic">Prière de ramener ce bulletin à la prochaine consultation</p>
        </div>

        <!-- Action Buttons -->
        <div class="bg-gray-50 px-6 py-4 flex justify-between items-center border-t">
            <button class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                Annuler
            </button>
            <div class="space-x-3">
                <button class="bg-medical-500 hover:bg-medical-600 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                    Aperçu
                </button>
                <button class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                    Sauvegarder
                </button>
                <button class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                    Imprimer
                </button>
            </div>
        </div>
    </div>

    <script>
        // Simulation d'interactions pour la démo
        document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const label = this.closest('label');
                if (this.checked) {
                    label.classList.add('ring-2', 'ring-offset-2');
                    if (label.querySelector('.text-medical-600')) {
                        label.classList.add('ring-medical-500');
                    } else if (label.querySelector('.text-green-600')) {
                        label.classList.add('ring-green-500');
                    }
                } else {
                    label.classList.remove('ring-2', 'ring-offset-2', 'ring-medical-500', 'ring-green-500');
                }
            });
        });

        // Auto-resize textarea
        document.querySelector('textarea').addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    </script>
</body>
</html>
