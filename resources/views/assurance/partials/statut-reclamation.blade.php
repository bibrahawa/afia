{{-- @include('assurance.partials.statut-reclamation', ['statut' => $reclamation->status]) --}}
@php
    [$ton, $libelle] = match ($statut) {
        'draft' => ['hl-s-neutre', 'À envoyer'],
        'submitted', 'under_review' => ['hl-s-info', 'Envoyée'],
        'approved' => ['hl-s-succes', 'Acceptée'],
        'rejected' => ['hl-s-danger', 'Rejetée'],
        'paid' => ['hl-s-succes', 'Réglée'],
        default => ['hl-s-neutre', ucfirst((string) $statut)],
    };
@endphp
<span class="hl-statut {{ $ton }}">{{ $libelle }}</span>
