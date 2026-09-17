{{-- En tête de la consultation : ce que l'accueil a déjà saisi, pour que le médecin ne le redemande pas. --}}
@php
    $visite = $consultation->visite;
    $patient = $consultation->patient;
    $antecedents = $patient?->antecedant;
@endphp
<div class="alert alert-light border mb-3">
    <div class="d-flex flex-wrap gap-3 align-items-center">
        <strong>{{ $patient?->full_name }}</strong>
        <span class="text-muted">{{ $patient?->gender }}{{ $patient?->age !== null ? ', ' . $patient->age . ' ans' : '' }}</span>
        @if($visite)
            <span><i class="fas fa-notes-medical text-primary"></i> {{ $visite->motif }}</span>
            @if($visite->urgence)<span class="badge badge-danger">Urgence</span>@endif
            <span class="text-muted small">arrivé(e) à {{ $visite->arrivee_le->format('H:i') }}</span>
        @endif
        @php $grossesseEnCours = $consultation->grossesse ?? null; @endphp
        @if($grossesseEnCours && $grossesseEnCours->estEnCours())
            <span class="badge badge-success"><i class="fas fa-baby"></i> Grossesse : {{ $grossesseEnCours->termeLisible() }} — DPA {{ $grossesseEnCours->dpa->format('d/m/Y') }}</span>
        @endif
        <span class="ms-auto small">
            @can('parcours.dossier')<a href="{{ route('parcours.dossier.show', $patient?->id) }}" target="_blank">Dossier</a>@endcan
            @can('assurance.referentiel.view') · <a href="{{ route('assurance.droits.show', $patient?->id) }}" target="_blank">Droits assurance</a>@endcan
        </span>
    </div>
    @if($antecedents?->allergies)
        <div class="mt-1 text-danger fw-bold"><i class="fas fa-exclamation-triangle"></i> Allergies : {{ $antecedents->allergies }}</div>
    @endif
    @if($visite)
        <div class="mt-1"><i class="fas fa-heartbeat text-danger"></i> @include('parcours.partials.constantes', ['c' => $visite->derniereConstante])</div>
        @if($visite->notes_accueil)<div class="mt-1 small text-muted">Accueil : {{ $visite->notes_accueil }}</div>@endif
    @endif
</div>
