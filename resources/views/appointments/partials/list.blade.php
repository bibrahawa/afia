<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>#</th>
                <th>Patient</th>
                <th>Téléphone</th>
                <th>Date</th>
                <th>Heure</th>
                <th>Notes</th>
                <th>Statut</th>
                <th class="text-center">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($appointments as $index => $item)
                <tr data-patient="{{ strtolower($item['patient']['first_name'] ?? '') }} {{ strtolower($item['patient']['last_name'] ?? '') }}"
                    data-date="{{ \Carbon\Carbon::parse($item['appointment_date'])->format('Y-m-d') }}"
                    data-status="{{ $item['status'] }}"
                    data-notes="{{ strtolower($item['notes'] ?? '') }}">
                    <td>
                        <strong class="text-primary">#{{ $appointments->firstItem() + $index }}</strong>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-sm me-2">
                                <span class="avatar-title rounded-circle bg-primary">
                                    {{ substr($item['patient']['first_name'] ?? 'N', 0, 1) }}
                                </span>
                            </div>
                            <div>
                                <strong>{{ $item['patient']['first_name'] ?? 'Nom' }} {{ $item['patient']['last_name'] ?? 'inconnu' }}</strong>
                            </div>
                        </div>
                    </td>
                    <td>
                        @if(isset($patient->telephone))
                            <i class="fas fa-phone text-muted me-1"></i>
                            {{ $patient->telephone }}
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        <i class="far fa-calendar text-muted me-1"></i>
                        {{ \Carbon\Carbon::parse($item['appointment_date'])->locale('fr')->isoFormat('DD MMM YYYY') }}
                    </td>
                    <td>
                        <i class="far fa-clock text-muted me-1"></i>
                        {{ $item['appointment_time']->format('H:i') }}
                    </td>
                    <td>
                        @if ($item['reason'])
                            <span class="text-truncate d-inline-block" style="max-width: 200px;" title="{{ $item['reason'] }}">
                                {{ $item['reason'] }}
                            </span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        <span class="status-badge 
                            {{ $item['status'] === 'pending' ? 'status-pending' :
                            ($item['status'] === 'confirmed' ? 'status-confirmed' :
                            ($item['status'] === 'completed' ? 'status-completed' : 
                            ($item['status'] === 'cancelled' ? 'status-cancelled' : ''))) }}">
                            @if($item['status'] === 'pending')
                                <i class="fas fa-clock me-1"></i> En attente
                            @elseif($item['status'] === 'confirmed')
                                <i class="fas fa-check me-1"></i> Confirmé
                            @elseif($item['status'] === 'completed')
                                <i class="fas fa-check-double me-1"></i> Terminé
                            @elseif($item['status'] === 'cancelled')
                                <i class="fas fa-times me-1"></i> Annulé
                            @endif
                        </span>
                    </td>
                    <td class="text-center">
                        <div class="d-flex justify-content-center gap-1">
                            @if ($item['status'] === 'pending')
                                @can('medecin.confirm_appointment')
                                    <form method="POST" action="{{ route('medecin.appointments.confirm', $item['id']) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success" title="Confirmer">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                @endcan
                            @elseif ($item['status'] === 'confirmed')
                                @can('medecin.complete_appointment')
                                    <form method="POST" action="{{ route('medecin.appointments.complete', $item['id']) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-info" title="Terminer">
                                            <i class="fas fa-check-double"></i>
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('medecin.appointments.cancel', $item['id']) }}" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Annuler" onclick="return confirm('Êtes-vous sûr de vouloir annuler ce rendez-vous ?')">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                @endcan
                            @else
                                <button type="button" class="btn btn-sm btn-secondary" disabled title="Terminé">
                                    <i class="fas fa-check-circle"></i>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center py-5">
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Aucun rendez-vous trouvé
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Pagination --}}
@if($appointments->hasPages())
    <div class="d-flex justify-content-between align-items-center mt-4">
        <div class="text-muted">
            Affichage de {{ $appointments->firstItem() }} à {{ $appointments->lastItem() }} sur {{ $appointments->total() }} résultats
        </div>
        <div>
            {{ $appointments->links() }}
        </div>
    </div>
@endif