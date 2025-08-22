@extends('layouts.backend')

@section('title', 'Messages SMS')

@section('content')
<div class="container">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <!-- En-tête -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white p-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold">Messages SMS</h1>
                        <p class="text-blue-100 mt-2">
                            Total: {{ $messages->count }} messages
                        </p>
                    </div>
                    <div class="text-right">
                        <span class="bg-blue-500 px-3 py-1 rounded-full text-sm">
                            Page 1
                        </span>
                    </div>
                </div>
            </div>

            <!-- Filtres -->
            <div class="bg-gray-50 p-4 border-b">
                <div class="flex flex-wrap gap-4 items-center">
                    <div class="flex items-center space-x-2">
                        <label class="text-sm font-medium text-gray-700">Expéditeur:</label>
                        <select class="border border-gray-300 rounded px-3 py-1 text-sm">
                            <option value="">Tous</option>
                            <option value="CauriWallet">CauriWallet</option>
                            <option value="MyCauri">MyCauri</option>
                        </select>
                    </div>
                    <div class="flex items-center space-x-2">
                        <label class="text-sm font-medium text-gray-700">Type:</label>
                        <select class="border border-gray-300 rounded px-3 py-1 text-sm">
                            <option value="">Tous</option>
                            <option value="transaction">Transactions</option>
                            <option value="otp">Codes OTP</option>
                            <option value="greeting">Salutations</option>
                        </select>
                    </div>
                    <button class="bg-blue-600 text-white px-4 py-1 rounded text-sm hover:bg-blue-700">
                        Filtrer
                    </button>
                </div>
            </div>

            <!-- Liste des messages -->
            <div class="divide-y divide-gray-200">
                @foreach($messages->results as $message)
                <div class="p-6 hover:bg-gray-50 transition-colors">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <!-- En-tête du message -->
                            <div class="flex items-center space-x-3 mb-3">
                                <div class="flex-shrink-0">
                                    @if($message->sender_name === 'CauriWallet')
                                        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                            </svg>
                                        </div>
                                    @else
                                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">
                                        {{ $message->sender_name }}
                                    </h3>
                                    <div class="flex items-center space-x-4 text-sm text-gray-500">
                                        <span>{{ date('d/m/Y H:i', $message->sent_at) }}</span>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            @if($message->status === 'sent') bg-green-100 text-green-800 @else bg-red-100 text-red-800 @endif">
                                            {{ ucfirst($message->status) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Contenu du message -->
                            <div class="ml-13">
                                <div class="bg-gray-100 rounded-lg p-4 mb-3">
                                    <p class="text-gray-800 leading-relaxed">
                                        {{ $message->message }}
                                    </p>
                                </div>

                                <!-- Détection du type de message -->
                                @if(strpos($message->message, 'débité') !== false)
                                    <div class="flex items-center space-x-2 text-sm">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full bg-red-100 text-red-700">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                            </svg>
                                            Débit
                                        </span>
                                        @php
                                            preg_match('/(\d+(?:\.\d+)*)\s*\(GNF\)/', $message->message, $matches);
                                            $montant = isset($matches[1]) ? number_format((float)str_replace('.', '', $matches[1]), 0, ',', ' ') : 'N/A';
                                        @endphp
                                        <span class="font-medium text-gray-700">{{ $montant }} GNF</span>
                                    </div>
                                @elseif(strpos($message->message, 'OTP') !== false)
                                    <div class="flex items-center space-x-2 text-sm">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full bg-yellow-100 text-yellow-700">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            Code OTP
                                        </span>
                                    </div>
                                @elseif(strpos($message->message, 'transaction') !== false)
                                    <div class="flex items-center space-x-2 text-sm">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full bg-blue-100 text-blue-700">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                                            </svg>
                                            Transaction en attente
                                        </span>
                                    </div>
                                @else
                                    <div class="flex items-center space-x-2 text-sm">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full bg-gray-100 text-gray-700">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"></path>
                                            </svg>
                                            Message
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex-shrink-0 ml-4">
                            <div class="flex space-x-2">
                                <button class="text-gray-400 hover:text-gray-600" title="Voir détails">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </button>
                                <button class="text-gray-400 hover:text-red-600" title="Supprimer">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                <div class="flex-1 flex justify-between sm:hidden">
                    <button class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50" disabled>
                        Précédent
                    </button>
                    <button class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Suivant
                    </button>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Affichage de <span class="font-medium">1</span> à <span class="font-medium">20</span>
                            sur <span class="font-medium">{{ $messages->count }}</span> résultats
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <button class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50" disabled>
                                <span class="sr-only">Précédent</span>
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <button class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-blue-50 text-sm font-medium text-blue-600">
                                1
                            </button>
                            <button class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                2
                            </button>
                            <button class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                3
                            </button>
                            <button class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <span class="sr-only">Suivant</span>
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion des filtres
    const senderFilter = document.querySelector('select[name="sender"]');
    const typeFilter = document.querySelector('select[name="type"]');
    
    // Auto-refresh toutes les 30 secondes
    setInterval(() => {
        // Code pour rafraîchir les données
        console.log('Auto-refresh des messages...');
    }, 30000);
});
</script>
@endpush