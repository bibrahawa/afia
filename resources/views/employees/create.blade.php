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

        @can('users.create')
            @if(($roles ?? collect())->isNotEmpty())
                <div class="em-acces">
                    <label class="em-acces-case">
                        <input type="checkbox" name="acces" value="1" id="emAcces" @checked(old('acces', request('acces')))>
                        <span><strong><i class="fas fa-key" aria-hidden="true"></i> Lui créer un accès à Hali</strong>
                            <small>Pour qu'elle se connecte avec son téléphone. Inutile pour une personne qui ne se sert pas de l'application.</small></span>
                    </label>
                    <div id="emAccesChamps" @unless(old('acces', request('acces'))) hidden @endunless>
                        @include('employees._acces', ['roles' => $roles, 'utilisateur' => null])
                    </div>
                </div>
            @endif
        @endcan
        <div class="em-pied">
            <a href="{{ route('employee.index') }}" class="hl-bouton">Annuler</a>
            <button type="submit" class="hl-bouton hl-bouton-plein" id="emValider">Enregistrer la fiche</button>
        </div>
    </form>
</div></div>
<style>
    .em-acces { margin: 0 22px 20px; padding: 16px; border: 1px solid var(--hali-bordure); border-radius: 12px; background: #fafbfc; }
    .em-acces-case { display: flex; gap: 12px; margin: 0; cursor: pointer; }
    .em-acces-case input { width: 20px; height: 20px; margin-top: 2px; accent-color: var(--hali-primaire); }
    .em-acces-case strong { display: block; color: var(--hali-encre); }
    .em-acces-case small { display: block; color: var(--hali-discret); font-size: .8rem; }
    #emAccesChamps { margin-top: 16px; padding-top: 16px; border-top: 1px dashed var(--hali-bordure); }
</style>
<script>
(function () {
    var form = document.getElementById('emForm'), caseAcces = document.getElementById('emAcces'), bloc = document.getElementById('emAccesChamps');
    function majAcces() {
        if (!caseAcces) return;
        bloc.hidden = !caseAcces.checked;
        // Champs désactivés quand la case est décochée : ils ne sont ni envoyés ni exigés.
        bloc.querySelectorAll('input, select').forEach(function (c) { c.disabled = !caseAcces.checked; });
        var tel = document.getElementById('acTel'); if (tel) tel.required = caseAcces.checked;
    }
    if (caseAcces) { caseAcces.addEventListener('change', majAcces); majAcces(); }
    form.addEventListener('submit', function () { document.getElementById('emValider').disabled = true; });
})();
</script>
@endsection
