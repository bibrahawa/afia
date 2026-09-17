{{--
    À inclure dans resources/views/layouts/sidebar.blade.php, après « Hospitalisations » :
        @include('labo.partials.sidebar')
    N'apparaît que si le module est actif pour l'établissement ET que l'utilisateur a au moins une permission labo.
--}}
@php($etabLabo = \App\Support\EtablissementContext::current())
@if($etabLabo && $etabLabo->aModule('laboratoire') && auth()->user()?->canAny(['labo.tableau_bord', 'labo.demande.view', 'labo.prelevement', 'labo.reception', 'labo.resultat.saisir', 'labo.validation.technique', 'labo.catalogue.view']))
    <li class="nav-section">
        <span class="sidebar-mini-icon"><i class="fa fa-ellipsis-h"></i></span>
        <h4 class="text-section">Laboratoire</h4>
    </li>
    <li class="nav-item {{ request()->routeIs('labo.*') ? 'active submenu' : '' }}">
        <a data-bs-toggle="collapse" href="#menuLabo">
            <i class="fas fa-flask"></i><p>Laboratoire</p><span class="caret"></span>
        </a>
        <div class="collapse {{ request()->routeIs('labo.*') ? 'show' : '' }}" id="menuLabo">
            <ul class="nav nav-collapse">
                @can('labo.tableau_bord')<li class="{{ request()->routeIs('labo.tableau-bord') ? 'active' : '' }}"><a href="{{ route('labo.tableau-bord') }}"><span class="sub-item">Tableau de bord</span></a></li>@endcan
                @can('labo.demande.view')<li class="{{ request()->routeIs('labo.demandes.*') ? 'active' : '' }}"><a href="{{ route('labo.demandes.index') }}"><span class="sub-item">Demandes</span></a></li>@endcan
                @can('labo.prelevement')<li class="{{ request()->routeIs('labo.prelevements.*') ? 'active' : '' }}"><a href="{{ route('labo.prelevements.index') }}"><span class="sub-item">Prélèvements</span></a></li>@endcan
                @can('labo.reception')<li class="{{ request()->routeIs('labo.reception.*') ? 'active' : '' }}"><a href="{{ route('labo.reception.index') }}"><span class="sub-item">Réception</span></a></li>@endcan
                @can('labo.resultat.saisir')<li class="{{ request()->routeIs('labo.paillasse.*') ? 'active' : '' }}"><a href="{{ route('labo.paillasse.index') }}"><span class="sub-item">Paillasse</span></a></li>@endcan
                @can('labo.validation.technique')<li class="{{ request()->routeIs('labo.validation.*') ? 'active' : '' }}"><a href="{{ route('labo.validation.index') }}"><span class="sub-item">Validation</span></a></li>@endcan
                @can('labo.catalogue.view')<li class="{{ request()->routeIs('labo.catalogue.*') ? 'active' : '' }}"><a href="{{ route('labo.catalogue.index') }}"><span class="sub-item">Catalogue</span></a></li>@endcan
            </ul>
        </div>
    </li>
@endif
