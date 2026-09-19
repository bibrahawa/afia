{{--
    En-tête des pages du laboratoire.
    @include('labo.partials.entete', ['titre' => '...', 'fil' => [url => libellé], 'sousTitre' => '... (facultatif)'])
--}}
<header class="hl-entete labo-entete">
    <div>
        <nav class="labo-fil" aria-label="Fil d'Ariane">
            <a href="{{ route('labo.tableau-bord') }}">Laboratoire</a>
            @foreach(($fil ?? []) as $url => $libelle)
                <span aria-hidden="true">›</span>
                @if(is_string($url) && $url !== url()->current())<a href="{{ $url }}">{{ $libelle }}</a>@else<span>{{ $libelle }}</span>@endif
            @endforeach
        </nav>
        <h1>{{ $titre }}</h1>
        @if(! empty($sousTitre))<p>{{ $sousTitre }}</p>@endif
    </div>
</header>
