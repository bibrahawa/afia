@extends('layouts.backend')

@php
    $icones = ['rdv' => 'fa-calendar-check', 'consultation' => 'fa-stethoscope', 'hospitalisation' => 'fa-procedures', 'laboratoire' => 'fa-flask',
               'assurance' => 'fa-shield-alt', 'pharmacie' => 'fa-pills', 'parcours' => 'fa-route', 'facturation_avancee' => 'fa-cash-register'];
@endphp

@section('style')
<style>
    .li-grille { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 10px; padding: 18px; }
    .li-module { position: relative; display: flex; gap: 12px; margin: 0; padding: 14px; border: 1.5px solid var(--hali-bordure); border-radius: 12px; background: #fff; cursor: pointer; transition: border-color .15s, background-color .15s; }
    .li-module input { position: absolute; opacity: 0; }
    .li-icone { display: grid; place-items: center; width: 40px; height: 40px; flex: none; border-radius: 11px; background: #f3f4f6; color: #9ca3af; }
    .li-texte { flex: 1; min-width: 0; }
    .li-texte strong { display: block; color: var(--hali-encre); }
    .li-texte small { display: block; color: var(--hali-discret); font-size: .78rem; }
    .li-texte code { color: var(--hali-discret); font-size: .72rem; }
    .li-bascule { position: relative; width: 38px; height: 22px; flex: none; margin-top: 2px; border-radius: 999px; background: #d1d5db; transition: background-color .15s; }
    .li-bascule::after { content: ""; position: absolute; top: 3px; left: 3px; width: 16px; height: 16px; border-radius: 50%; background: #fff; box-shadow: 0 1px 2px rgba(0, 0, 0, .2); transition: transform .15s; }
    .li-module:has(input:checked) { border-color: var(--hali-primaire); background: var(--hali-primaire-pale); }
    .li-module:has(input:checked) .li-icone { background: var(--hali-primaire); color: #fff; }
    .li-module:has(input:checked) .li-bascule { background: var(--hali-primaire); }
    .li-module:has(input:checked) .li-bascule::after { transform: translateX(16px); }
    .li-module:has(input:focus-visible) { outline: 2px solid var(--hali-primaire); outline-offset: 2px; }
    .li-pied { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; padding: 14px 18px; border-top: 1px solid var(--hali-bordure); background: #fafbfc; border-radius: 0 0 var(--hali-rayon) var(--hali-rayon); }
</style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">
    <header class="hl-entete">
        <div>
            <h1>Licence · {{ $etablissement->nom }}</h1>
            <p>Les modules que ce client peut utiliser : c'est la licence, et non le type « {{ $etablissement->type }} », qui décide de son menu et de ses accès.</p>
        </div>
        <div class="hl-entete-actions"><a href="{{ route('etablissement.index') }}" class="hl-bouton"><i class="fas fa-arrow-left" aria-hidden="true"></i> Établissements</a></div>
    </header>

    <form action="{{ route('etablissement.modules.sync', $etablissement) }}" method="POST" class="hl-bloc" id="liForm">
        @csrf
        <h2 class="hl-bloc-titre">Modules <small id="liCompte"></small></h2>
        <div class="li-grille">
            @foreach($modules as $module)
                <label class="li-module" for="module-{{ $module->id }}">
                    <input type="checkbox" name="modules[]" value="{{ $module->id }}" id="module-{{ $module->id }}" @checked(in_array($module->id, $actifs))>
                    <span class="li-icone" aria-hidden="true"><i class="fas {{ $icones[$module->code] ?? 'fa-puzzle-piece' }}"></i></span>
                    <span class="li-texte"><strong>{{ $module->nom }}</strong>@if($module->description)<small>{{ $module->description }}</small>@endif<code>{{ $module->code }}</code></span>
                    <span class="li-bascule" aria-hidden="true"></span>
                </label>
            @endforeach
        </div>
        <div class="li-pied">
            <span class="small text-muted">Désactiver un module retire l'écran du menu du client ; ses données sont conservées et réapparaissent à la réactivation.</span>
            <button type="submit" class="hl-bouton hl-bouton-plein">Enregistrer la licence</button>
        </div>
    </form>
</div></div>

<script>
(function () {
    var cases = document.querySelectorAll('#liForm input[type=checkbox]'), compte = document.getElementById('liCompte');
    function maj() { var n = 0; cases.forEach(function (c) { if (c.checked) n++; }); compte.textContent = n + ' sur ' + cases.length + ' activé' + (n > 1 ? 's' : ''); }
    cases.forEach(function (c) { c.addEventListener('change', maj); }); maj();
})();
</script>
@endsection
