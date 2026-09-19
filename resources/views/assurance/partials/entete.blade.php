{{--
    En-tête commun des écrans d'assurance.
    @include('assurance.partials.entete', ['titre' => '...', 'fil' => [url => libellé], 'sousTitre' => '...' (facultatif)])
    Ajoute les onglets du module et le style commun (partials.styles) ; marque la page
    « as-page » pour que ce style ne s'applique qu'aux écrans d'assurance.
--}}
@php
    $ongletsAssurance = collect([
        ['assurance.referentiel.view', 'assurance.contrats.index', ['assurance.contrats.*', 'assurance.adhesions.*', 'assurance.droits.*'], 'fa-file-contract', 'Contrats'],
        ['assurance.referentiel.view', 'assurance.entreprises.index', ['assurance.entreprises.*'], 'fa-building', 'Entreprises'],
        ['assurance.referentiel.view', 'assurance.conventions.index', ['assurance.conventions.*', 'assurance.feuilles-de-soins.*'], 'fa-handshake', 'Conventions'],
        ['assurance.creances.view', 'assurance.creances.index', ['assurance.creances.*', 'assurance.reclamations.*', 'assurance.bordereaux.*', 'assurance.reglements.*'], 'fa-hand-holding-usd', 'Créances'],
    ])->filter(fn ($o) => auth()->user()?->can($o[0]) && Route::has($o[1]));
@endphp
<header class="hl-entete as-entete">
    <div style="min-width:0">
        <nav class="as-fil" aria-label="Fil d'Ariane">
            <a href="{{ Route::has('assurance.creances.index') && auth()->user()?->can('assurance.creances.view') ? route('assurance.creances.index') : route('assurance.contrats.index') }}">Assurances</a>
            @foreach(($fil ?? []) as $url => $libelle)
                @if(! $loop->last)
                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                    @if(is_string($url))<a href="{{ $url }}">{{ $libelle }}</a>@else<span>{{ $libelle }}</span>@endif
                @endif
            @endforeach
        </nav>
        <h1>{{ $titre }}</h1>
        @if(! empty($sousTitre))<p>{{ $sousTitre }}</p>@endif
    </div>
</header>

@if($ongletsAssurance->count() > 1)
    <nav class="as-onglets" aria-label="Module assurance">
        @foreach($ongletsAssurance as [$permission, $route, $motifs, $icone, $libelle])
            <a href="{{ route($route) }}" class="{{ request()->routeIs(...$motifs) ? 'est-actif' : '' }}"><i class="fas {{ $icone }}" aria-hidden="true"></i> {{ $libelle }}</a>
        @endforeach
    </nav>
@endif

@include('assurance.partials.styles')
<script>document.currentScript.closest('.page-inner')?.classList.add('as-page');</script>
