{{-- Navigation commune aux écrans d'organisation du médecin. --}}
<nav class="hl-puces" aria-label="Mon organisation" style="margin-bottom:16px">
    @if(Route::has('medecin.appointments'))
        <a href="{{ route('medecin.appointments') }}" class="hl-puce {{ request()->routeIs('medecin.appointments') ? 'est-actif' : '' }}"><i class="fas fa-calendar-check" aria-hidden="true"></i> Mes rendez-vous</a>
    @endif
    @can('medecin.availabilities')
        <a href="{{ route('medecin.availabilities.index') }}" class="hl-puce {{ request()->routeIs('medecin.availabilities.*') ? 'est-actif' : '' }}"><i class="fas fa-clock" aria-hidden="true"></i> Mes horaires</a>
    @endcan
    @can('medecin.leaves')
        <a href="{{ route('medecin.leaves.index') }}" class="hl-puce {{ request()->routeIs('medecin.leaves.*') ? 'est-actif' : '' }}"><i class="fas fa-umbrella-beach" aria-hidden="true"></i> Congés et pauses</a>
    @endcan
</nav>
