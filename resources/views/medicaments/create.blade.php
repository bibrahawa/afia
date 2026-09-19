@extends('layouts.backend')

@section('content')
<div class="container"><div class="page-inner hl">
    <header class="hl-entete">
        <div><h1>Nouveau médicament</h1><p>Il apparaîtra dans la liste de l'ordonnance.</p></div>
        <div class="hl-entete-actions"><a href="{{ route('medicaments.index') }}" class="hl-bouton"><i class="fas fa-arrow-left" aria-hidden="true"></i> Médicaments</a></div>
    </header>

    @include('partials.catalogue')

    @if($errors->any())<div class="hl-note hl-note-danger mb-3" role="alert"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <form action="{{ route('medicaments.store') }}" method="POST" class="hl-bloc cat-modal" style="max-width:640px">
        @csrf
        <div class="modal-body" style="padding:20px 22px">@include('medicaments._champs', ['p' => '', 'm' => null])</div>
        <div class="modal-footer" style="display:flex; justify-content:flex-end; gap:8px; padding:0 22px 20px">
            <a href="{{ route('medicaments.index') }}" class="hl-bouton">Annuler</a>
            <button type="submit" class="hl-bouton hl-bouton-plein">Ajouter le médicament</button>
        </div>
    </form>
</div></div>
@endsection
