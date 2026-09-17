{{-- @include('assurance.partials.entete', ['titre' => '...', 'fil' => [url => libellé]]) --}}
<div class="page-header">
    <h3 class="fw-bold mb-3">{{ $titre }}</h3>
    <ul class="breadcrumbs mb-3">
        <li class="nav-home"><a href="{{ url('/') }}"><i class="icon-home"></i></a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="{{ route('assurance.contrats.index') }}">Assurances</a></li>
        @foreach(($fil ?? []) as $url => $libelle)
            <li class="separator"><i class="icon-arrow-right"></i></li>
            <li class="nav-item">@if(is_string($url))<a href="{{ $url }}">{{ $libelle }}</a>@else{{ $libelle }}@endif</li>
        @endforeach
    </ul>
</div>
