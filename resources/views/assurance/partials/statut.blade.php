{{-- @include('assurance.partials.statut', ['statut' => StatutCouverture, 'fin' => ?Carbon]) --}}
@php
    $termine = ($fin ?? null) && $fin->lt(today());
    $tonsStatut = ['success' => 'hl-s-succes', 'warning' => 'hl-s-alerte', 'danger' => 'hl-s-danger', 'info' => 'hl-s-info', 'primary' => 'hl-s-info'];
@endphp
@if($termine)
    <span class="hl-statut hl-s-neutre">Terminée le {{ $fin->format('d/m/Y') }}</span>
@else
    <span class="hl-statut {{ $tonsStatut[$statut->badge()] ?? 'hl-s-neutre' }}">{{ $statut->libelle() }}</span>
    @if($fin ?? null)<span class="small text-muted">jusqu'au {{ $fin->format('d/m/Y') }}</span>@endif
@endif
