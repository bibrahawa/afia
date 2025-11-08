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
            <tr data-patient="{{ strtolower($item->patient->first_name ?? '') }} {{ strtolower($item->patient->last_name ?? '') }}"
                data-date="{{ \Carbon\Carbon::parse($item->appointment_date)->format('Y-m-d') }}"
                data-status="{{ $item->status }}"
                data-notes="{{ strtolower($item->notes ?? '') }}">
                <td>{{ $appointments->firstItem() + $index }}</td>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-sm me-2">
                            <span class="avatar-title rounded-circle bg-primary">
                                {{ substr($item->patient->first_name ?? 'N', 0, 1) }}
                            </span>
                        </div>
                        <div>
                            <strong>{{ $item->patient->first_name ?? 'Nom' }} {{ $item->patient->last_name ?? 'inconnu' }}</strong>
                        </div>
                    </div>
                </td>
                <td>
                    @if(isset($item->patient->user->phone))
                        <i class="fas fa-phone text-muted me-1"></i>
                        {{ $item->patient->user->phone }}
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
                <td>
                    <i class="far fa-calendar text-muted me-1"></i>
                    {{ \Carbon\Carbon::parse($item->appointment_date)->locale('fr')->isoFormat('DD MMM YYYY') }}
                </td>
                <td>
                    <i class="far fa-clock text-muted me-1"></i>
                    {{ $item->appointment_time->format('H:i') }}
                </td>
                <td>
                    @if ($item->notes)
                        <span class="text-truncate d-inline-block" style="max-width: 150px;" title="{{ $item->notes }}">
                            {{ $item->notes }}
                        </span>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
                <td>
                    <span class="status-badge 
                        {{ $item->status === 'pending' ? 'status-pending' :
                        ($item->status === 'confirmed' ? 'status-confirmed' :
                        ($item->status === 'completed' ? 'status-completed' : '')) }}">
                        @if($item->status === 'pending')
                            <i class="fas fa-clock me-1"></i> En attente
                        @elseif($item->status === 'confirmed')
                            <i class="fas fa-check me-1"></i> Confirmé
                        @elseif($item->status === 'completed')
                            <i class="fas fa-check-double me-1"></i> Terminé
                        @endif
                    </span>
                </td>
                <td class="text-center">
                    @if ($item->status === 'pending')
                        @can('medecin.confirm_appointment')
                            <form method="POST" action="{{ route('medecin.appointments.confirm', $item->id) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success" title="Confirmer">
                                    <i class="fas fa-check"></i>
                                </button>
                            </form>
                        @endcan
                    @elseif ($item->status === 'confirmed')
                        @can('medecin.complete_appointment')
                            <form method="POST" action="{{ route('medecin.appointments.complete', $item->id) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-primary" title="Terminer">
                                    <i class="fas fa-check-double"></i>
                                </button>
                            </form>
                        @endcan
                    @else
                        <button type="button" class="btn btn-sm btn-secondary" disabled>
                            <i class="fas fa-check-circle"></i>
                        </button>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="text-center py-4">
                    <i class="fas fa-info-circle text-muted"></i>
                    <p class="text-muted mb-0">Aucun rendez-vous trouvé.</p>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

{{-- Pagination --}}
<div class="mt-4 d-flex justify-content-center">
    {{ $appointments->appends(request()->query())->links() }}
</div>