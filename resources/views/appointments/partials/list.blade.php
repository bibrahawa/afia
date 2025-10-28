@forelse ($appointments as $item)
    <div class="appointment-card bg-white rounded-lg shadow-md p-6" 
         data-patient="{{ strtolower($item->patient->first_name ?? '') }} {{ strtolower($item->patient->last_name ?? '') }}"
         data-date="{{ \Carbon\Carbon::parse($item->appointment_date)->format('Y-m-d') }}"
         data-status="{{ $item->status }}"
         data-notes="{{ strtolower($item->notes ?? '') }}">
        <div class="flex justify-between items-start">
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-user text-blue-600"></i>
                </div>
                <div>
                    <h4 class="font-medium text-gray-900">
                        {{ $item->patient->first_name ?? 'Nom' }} {{ $item->patient->last_name ?? 'inconnu' }}
                    </h4>
                    <p class="text-sm text-gray-600">
                        <i class="far fa-calendar me-1"></i>
                        {{ \Carbon\Carbon::parse($item->appointment_date)->locale('fr')->isoFormat('dddd D MMM YYYY') }} 
                        à {{ $item->appointment_time->format('H:i') }}
                    </p>
                    @if ($item->notes)
                        <p class="text-sm text-gray-600 mt-1">
                            <i class="far fa-comment-dots me-1"></i>
                            {{ $item->notes }}
                        </p>
                    @endif
                    @if(isset($item->patient->user->phone))
                        <p class="text-sm text-gray-500 mt-1">
                            <i class="fas fa-phone me-1"></i>
                            {{ $item->patient->user->phone }}
                        </p>
                    @endif
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <span class="px-3 py-1 rounded-full text-xs font-medium
                    {{ $item->status === 'pending' ? 'bg-yellow-200 text-yellow-800' :
                    ($item->status === 'confirmed' ? 'bg-blue-200 text-blue-800' :
                    ($item->status === 'completed' ? 'bg-green-200 text-green-800' : '')) }}">
                    @if($item->status === 'pending')
                        En attente
                    @elseif($item->status === 'confirmed')
                        Confirmé
                    @elseif($item->status === 'completed')
                        Terminé
                    @endif
                </span>

                @if ($item->status === 'pending')
                    @can('medecin.confirm_appointment')
                        <form method="POST" action="{{ route('medecin.appointments.confirm', $item->id) }}">
                            @csrf
                            <button type="submit" class="px-3 py-1 bg-green-600 text-white rounded-md text-sm hover:bg-green-700">
                                <i class="fas fa-check me-1"></i> Confirmer
                            </button>
                        </form>
                    @endcan
                @elseif ($item->status === 'confirmed')
                    @can('medecin.complete_appointment')
                        <form method="POST" action="{{ route('medecin.appointments.complete', $item->id) }}">
                            @csrf
                            <button type="submit" class="px-3 py-1 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700">
                                <i class="fas fa-check-double me-1"></i> Terminer
                            </button>
                        </form>
                    @endcan
                @endif
            </div>
        </div>
    </div>
@empty
    <div class="col-span-full">
        <div class="alert alert-info text-center">
            <i class="fas fa-info-circle"></i> Aucun rendez-vous trouvé.
        </div>
    </div>
@endforelse

{{-- Pagination avec les paramètres --}}
<div class="mt-4 d-flex justify-content-center">
    {{ $appointments->links() }}
</div>