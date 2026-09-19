@extends('layouts.backend')

@section('content')
<div class="container"><div class="page-inner hl">
    <header class="hl-entete">
        <div><h1>Nouvel employé</h1><p>La fiche du membre du personnel. Pour qu'il se connecte, créez-lui aussi un compte utilisateur.</p></div>
        <div class="hl-entete-actions"><a href="{{ route('employee.index') }}" class="hl-bouton"><i class="fas fa-arrow-left" aria-hidden="true"></i> Personnel</a></div>
    </header>

    @if($errors->any())<div class="hl-note hl-note-danger mb-3" role="alert"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <form action="{{ route('employee.store') }}" method="POST" class="hl-bloc" style="max-width:860px" id="emForm">
        @csrf
        <div class="em-form">@include('employees._champs', ['employee' => null])</div>
        <div class="em-pied">
            <a href="{{ route('employee.index') }}" class="hl-bouton">Annuler</a>
            <button type="submit" class="hl-bouton hl-bouton-plein" id="emValider">Enregistrer la fiche</button>
        </div>
    </form>
</div></div>
<script>document.getElementById('emForm').addEventListener('submit', function () { document.getElementById('emValider').disabled = true; });</script>
@endsection
