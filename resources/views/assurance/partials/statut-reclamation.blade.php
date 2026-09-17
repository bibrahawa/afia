{{-- @include('assurance.partials.statut-reclamation', ['statut' => $reclamation->status]) --}}
@php
    [$classe, $libelle] = match ($statut) {
        'draft' => ['secondary', 'À envoyer'],
        'submitted', 'under_review' => ['info', 'Envoyée'],
        'approved' => ['primary', 'Acceptée'],
        'rejected' => ['danger', 'Rejetée'],
        'paid' => ['success', 'Réglée'],
        default => ['light', $statut],
    };
@endphp
<span class="badge badge-{{ $classe }}">{{ $libelle }}</span>
