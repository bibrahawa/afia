@php
    [$libelleType, $iconeType] = $types[$leave->type] ?? [ucfirst($leave->type), 'fa-calendar-minus'];
    [$libelleStatut, $tonStatut] = $statuts[$leave->status ?? 'pending'] ?? $statuts['pending'];
    $memeJour = $leave->start_date->isSameDay($leave->end_date);
    $duree = $memeJour ? null : (int) $leave->start_date->copy()->startOfDay()->diffInDays($leave->end_date->copy()->startOfDay()) + 1;
@endphp
<div class="cg-conge {{ $passe ? 'est-passe' : '' }}">
    <span class="cg-icone" aria-hidden="true"><i class="fas {{ $iconeType }}"></i></span>
    <div>
        <span class="cg-type">{{ $libelleType }}</span> <span class="hl-statut {{ $tonStatut }}">{{ $libelleStatut }}</span>
        <span class="cg-periode d-block">
            @if($memeJour)
                Le {{ $leave->start_date->translatedFormat('l d F') }}, de {{ $leave->start_date->format('H:i') }} à {{ $leave->end_date->format('H:i') }}
            @else
                Du {{ $leave->start_date->translatedFormat('d F Y') }} au {{ $leave->end_date->translatedFormat('d F Y') }} · {{ $duree }} jour{{ $duree > 1 ? 's' : '' }}
            @endif
        </span>
        @if($leave->reason)<span class="cg-sous">{{ $leave->reason }}</span>@endif
    </div>
    @unless($passe)
        <div class="cg-actions">
            <button type="button" class="hl-bouton cg-petit edit-leave-button" data-id="{{ $leave->id }}" data-type="{{ $leave->type }}"
                    data-start="{{ $leave->start_date->format('Y-m-d\TH:i') }}" data-end="{{ $leave->end_date->format('Y-m-d\TH:i') }}" data-reason="{{ $leave->reason }}">Modifier</button>
            <button type="button" class="hl-bouton cg-petit delete-leave-button" style="color:var(--hali-danger)" data-id="{{ $leave->id }}" data-type="{{ $leave->type }}" data-libelle="{{ $libelleType }}">Supprimer</button>
        </div>
    @endunless
</div>
