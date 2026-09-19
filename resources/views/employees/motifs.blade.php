@extends('layouts.backend')

@section('style')
<style>
    .mp-ligne { display: grid; grid-template-columns: auto 14px minmax(0, 1fr) 110px 170px; align-items: center; gap: 14px; padding: 12px 18px; border-top: 1px solid #f3f4f6; }
    .mp-ligne:first-of-type { border-top: 0; }
    .mp-ligne.est-exclu { opacity: .5; }
    .mp-ligne input[type=checkbox] { width: 20px; height: 20px; accent-color: var(--hali-primaire); cursor: pointer; }
    .mp-pastille { width: 12px; height: 12px; border-radius: 4px; }
    .mp-nom { color: var(--hali-encre); font-weight: 650; }
    .mp-defaut { color: var(--hali-discret); font-size: .84rem; font-variant-numeric: tabular-nums; }
    .mp-duree { position: relative; }
    .mp-duree input { padding-right: 44px; }
    .mp-duree span { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: var(--hali-discret); font-size: .8rem; }
    .mp-pied { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; padding: 14px 18px; border-top: 1px solid var(--hali-bordure); background: #fafbfc; border-radius: 0 0 var(--hali-rayon) var(--hali-rayon); }
    @media (max-width: 767.98px) { .mp-ligne { grid-template-columns: auto 14px 1fr; } .mp-ligne > .mp-defaut, .mp-ligne > .mp-duree { grid-column: 3; } }
</style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">
    <header class="hl-entete">
        <div>
            <h1>Motifs pratiqués</h1>
            <p>{{ $employee->nom_affiche }} · {{ $employee->department?->name }}</p>
        </div>
        <div class="hl-entete-actions"><a href="{{ route('employee.index') }}" class="hl-bouton"><i class="fas fa-arrow-left" aria-hidden="true"></i> Personnel</a></div>
    </header>

    <p class="hl-note hl-note-info mb-3"><i class="fas fa-info-circle" aria-hidden="true"></i> <span>Par défaut, un médecin reçoit tous les motifs de son département. Décochez ceux qu'il ne pratique pas : ils ne seront plus proposés avec lui, en ligne comme à l'accueil. La durée personnalisée est facultative.</span></p>

    <form action="{{ route('employees.motifs.sync', $employee) }}" method="POST" class="hl-bloc" id="mpForm">
        @csrf
        <h2 class="hl-bloc-titre">Motifs du département <small id="mpCompte"></small></h2>
        @forelse($motifs as $motif)
            @php
                $association = $associations->get($motif->id);
                $pratique = ! $association || $association->pivot->actif;
            @endphp
            <div class="mp-ligne {{ $pratique ? '' : 'est-exclu' }}">
                <label style="display:contents; cursor:pointer">
                {{-- Champ caché : une case décochée n'est pas envoyée par le navigateur --}}
                <span><input type="hidden" name="motifs[{{ $motif->id }}][pratique]" value="0"><input type="checkbox" class="mp-case" name="motifs[{{ $motif->id }}][pratique]" value="1" @checked($pratique) aria-label="Pratique {{ $motif->nom }}"></span>
                <span class="mp-pastille" style="background: {{ $motif->couleur ?: '#9ca3af' }}" aria-hidden="true"></span>
                <span class="mp-nom">{{ $motif->nom }}</span>
                </label>
                <span class="mp-defaut">{{ $motif->duree_minutes_defaut }} min par défaut</span>
                <span class="mp-duree">
                    <input type="number" name="motifs[{{ $motif->id }}][duree_minutes]" class="form-control form-control-sm" placeholder="{{ $motif->duree_minutes_defaut }}" min="1" max="240"
                           value="{{ $association?->pivot?->duree_minutes }}" aria-label="Durée personnalisée pour {{ $motif->nom }}"><span>min</span>
                </span>
            </div>
        @empty
            <div class="hl-vide" style="padding:24px">Aucun motif actif dans ce département. @can('motif_rdv.view')<a href="{{ route('motifs-rdv.index') }}">Configurer les motifs</a>@endcan</div>
        @endforelse
        @if($motifs->isNotEmpty())
            <div class="mp-pied">
                <span class="mp-defaut" id="mpAlerte"></span>
                <button type="submit" class="hl-bouton hl-bouton-plein">Enregistrer</button>
            </div>
        @endif
    </form>
</div></div>

<script>
(function () {
    var cases = document.querySelectorAll('.mp-case');
    function maj() {
        var n = 0;
        cases.forEach(function (c) { c.closest('.mp-ligne').classList.toggle('est-exclu', !c.checked); if (c.checked) n++; });
        var compte = document.getElementById('mpCompte'); if (compte) compte.textContent = n + ' sur ' + cases.length + ' pratiqué' + (n > 1 ? 's' : '');
        var alerte = document.getElementById('mpAlerte');
        if (alerte) { alerte.textContent = n === 0 ? 'Aucun motif coché : ce médecin ne pourra plus recevoir de rendez-vous.' : ''; alerte.style.color = n === 0 ? 'var(--hali-danger)' : ''; }
    }
    cases.forEach(function (c) { c.addEventListener('change', maj); });
    maj();
})();
</script>
@endsection
