{{-- Liste des rendez-vous du médecin (page « Mes rendez-vous » et réponse AJAX). --}}
@php
    $statutsRdv = [
        'pending' => ['À confirmer', 'hl-s-alerte'],
        'confirmed' => ['Confirmé', 'hl-s-succes'],
        'completed' => ['Honoré', 'hl-s-neutre'],
        'cancelled' => ['Annulé', 'hl-s-danger'],
        'no_show' => ['Absent', 'hl-s-danger'],
    ];
    $joursRdv = ['dim.', 'lun.', 'mar.', 'mer.', 'jeu.', 'ven.', 'sam.'];
    $moisRdv = ['', 'janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
    $parJour = $appointments->getCollection()->groupBy(fn ($a) => \Carbon\Carbon::parse($a->appointment_date)->toDateString());
@endphp

@if($appointments->isEmpty())
    <div class="hl-vide">
        <i class="fas fa-calendar-check" aria-hidden="true"></i>
        Aucun rendez-vous pour cette sélection.
    </div>
@else
    @foreach($parJour as $jour => $rdvDuJour)
        @php $d = \Carbon\Carbon::parse($jour); @endphp
        <h3 class="mr-jour">
            {{ $d->isToday() ? "Aujourd'hui" : ($d->isTomorrow() ? 'Demain' : ucfirst($joursRdv[$d->dayOfWeek]) . ' ' . $d->day . ' ' . $moisRdv[$d->month]) }}
            <small>{{ $rdvDuJour->count() }} rendez-vous</small>
        </h3>
        @foreach($rdvDuJour as $item)
            @php
                [$libelleStatut, $tonStatut] = $statutsRdv[$item->status] ?? [$item->status, 'hl-s-neutre'];
                $p = $item->patient;
            @endphp
            <div class="mr-ligne {{ in_array($item->status, ['completed', 'cancelled', 'no_show'], true) ? 'est-passe' : '' }}">
                <span class="mr-heure">{{ $item->appointment_time?->format('H:i') }}</span>
                <div class="mr-patient">
                    <span class="hl-avatar" aria-hidden="true">{{ $p ? mb_strtoupper(mb_substr((string) $p->first_name, 0, 1) . mb_substr((string) $p->last_name, 0, 1)) : '?' }}</span>
                    <div style="min-width:0">
                        <strong>{{ $p?->full_name ?? 'Patient inconnu' }}</strong>
                        <span class="mr-sous">{{ $p?->telephone ?: 'Téléphone non renseigné' }}</span>
                    </div>
                </div>
                <div style="min-width:0">
                    <span class="mr-motif">
                        @if($item->motifRdv)<i style="background: {{ $item->motifRdv->couleur ?: '#9ca3af' }}"></i>{{ $item->motifRdv->nom }}@else Consultation @endif
                    </span>
                    @if($item->notes)<span class="mr-sous mr-notes" title="{{ $item->notes }}">{{ $item->notes }}</span>@endif
                </div>
                <span><span class="hl-statut {{ $tonStatut }}">{{ $libelleStatut }}</span></span>
                <div class="mr-actions">
                    @if($item->status === 'pending')
                        @can('medecin.confirm_appointment')
                            <form method="POST" action="{{ route('medecin.appointments.confirm', $item->id) }}">@csrf
                                <button type="submit" class="hl-bouton hl-bouton-plein mr-petit">Confirmer</button>
                            </form>
                        @endcan
                    @elseif($item->status === 'confirmed')
                        @can('medecin.complete_appointment')
                            <form method="POST" action="{{ route('medecin.appointments.complete', $item->id) }}">@csrf
                                <button type="submit" class="hl-bouton mr-petit" title="Le patient a été vu">Honoré</button>
                            </form>
                        @endcan
                        @can('medecin.confirm_appointment')
                            <form method="POST" action="{{ route('medecin.appointments.cancel', $item->id) }}"
                                  onsubmit="return confirm('Annuler ce rendez-vous ? Le patient sera prévenu si les SMS sont activés.');">
                                @csrf @method('DELETE')
                                <button type="submit" class="mr-icone est-risque" title="Annuler" aria-label="Annuler le rendez-vous"><i class="fas fa-times"></i></button>
                            </form>
                        @endcan
                    @endif
                    @if($p)
                        @can('parcours.dossier')
                            <a href="{{ route('parcours.dossier.show', $p->id) }}" class="mr-icone" title="Dossier du patient" aria-label="Dossier du patient"><i class="fas fa-folder-open"></i></a>
                        @endcan
                    @endif
                </div>
            </div>
        @endforeach
    @endforeach

    @if($appointments->hasPages())
        <div class="mr-pagination">{{ $appointments->links() }}</div>
    @endif
@endif
