{{-- @include('assurance.partials.statut', ['statut' => StatutCouverture, 'fin' => ?Carbon]) --}}
@php
    $termine = ($fin ?? null) && $fin->lt(today());
@endphp
@if($termine)
    <span class="badge badge-secondary">Terminée le {{ $fin->format('d/m/Y') }}</span>
@else
    <span class="badge badge-{{ $statut->badge() }}">{{ $statut->libelle() }}</span>
    @if($fin ?? null)<span class="small text-muted">jusqu'au {{ $fin->format('d/m/Y') }}</span>@endif
@endif
