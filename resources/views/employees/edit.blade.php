@extends('layouts.backend')

@section('content')
<div class="container"><div class="page-inner hl">
    <header class="hl-entete">
        <div><h1>{{ $employee->nom_affiche }}</h1><p>{{ $employee->type_libelle }} · {{ $employee->department?->name }}</p></div>
        <div class="hl-entete-actions">
            @if($employee->type === 'Doctor' && Route::has('employees.motifs'))
                <a href="{{ route('employees.motifs', $employee) }}" class="hl-bouton"><i class="fas fa-calendar-plus" aria-hidden="true"></i> Motifs pratiqués</a>
            @endif
            <a href="{{ route('employee.index') }}" class="hl-bouton"><i class="fas fa-arrow-left" aria-hidden="true"></i> Personnel</a>
        </div>
    </header>

    @if($errors->any())<div class="hl-note hl-note-danger mb-3" role="alert"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <form action="{{ route('employee.update', $employee->id) }}" method="POST" class="hl-bloc" style="max-width:860px" id="emForm">
        @csrf @method('PUT')
        <div class="em-form">
            @include('employees._champs', ['employee' => $employee])
            <label class="d-flex gap-2 mb-0" style="font-weight:500">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $employee->is_active)) style="margin-top:3px">
                <span>Fiche active<span class="d-block cat-aide" style="margin:0">Décocher retire ce soignant des listes (accueil, rendez-vous) sans effacer son historique.</span></span>
            </label>
        </div>
        <div class="em-pied">
            <a href="{{ route('employee.index') }}" class="hl-bouton">Annuler</a>
            <button type="submit" class="hl-bouton hl-bouton-plein" id="emValider">Enregistrer</button>
        </div>
    </form>
</div></div>
<script>document.getElementById('emForm').addEventListener('submit', function () { document.getElementById('emValider').disabled = true; });</script>
@endsection
