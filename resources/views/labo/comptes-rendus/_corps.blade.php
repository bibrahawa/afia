{{-- Corps du compte rendu, construit UNIQUEMENT à partir de l'instantané $c (jamais des tables vivantes). Partagé PDF / page patient. --}}
@if($c['est_rectificatif'])
    <div class="cr-rectif"><strong>COMPTE RENDU RECTIFICATIF</strong> — annule et remplace la version précédente.<br>{!! nl2br(e($c['motif_rectification'])) !!}</div>
@endif
@if($c['est_partiel'])
    <div class="cr-partiel">Compte rendu PARTIEL : d'autres résultats sont en cours.</div>
@endif

<table class="cr-ident">
    <tr>
        <td style="width:55%">
            <strong>{{ $c['patient']['nom'] }}</strong><br>
            {{ $c['patient']['sexe'] }} · {{ $c['patient']['age'] }}@if($c['patient']['grossesse']) · {{ $c['patient']['grossesse'] }}@endif<br>
            <span class="cr-muted">ID santé : {{ $c['patient']['identifiant'] }}</span>
        </td>
        <td>
            Demande <strong>{{ $c['demande']['numero'] }}</strong> du {{ $c['demande']['date'] }}<br>
            Prescripteur : {{ $c['demande']['prescripteur'] }}<br>
            <span class="cr-muted">Version {{ $cr->version }} · émis le {{ $c['emis_le'] }}</span>
        </td>
    </tr>
</table>

@foreach($c['sections'] as $section)
    <h3 class="cr-section">{{ $section['nom'] }}</h3>
    @foreach($section['examens'] as $ex)
        <div class="cr-examen">
            <div class="cr-examen-titre">{{ $ex['nom'] }}
                @if($ex['rectifie_dans_cette_version'])<span class="cr-badge">RECTIFIÉ</span>@endif
                @if($ex['methode'])<span class="cr-muted"> — {{ $ex['methode'] }}</span>@endif
                @if($ex['sous_traitant'])<span class="cr-muted"> — réalisé par {{ $ex['sous_traitant'] }}</span>@endif
            </div>
            @if($ex['lignes'])
                <table class="cr-resultats">
                    <thead><tr><th style="width:42%">Paramètre</th><th style="width:20%">Résultat</th><th style="width:13%">Unité</th><th>Valeurs de référence</th></tr></thead>
                    <tbody>
                    @php($groupe = null)
                    @foreach($ex['lignes'] as $l)
                        @if($l['groupe'] && $l['groupe'] !== $groupe)
                            @php($groupe = $l['groupe'])
                            <tr><td colspan="4" class="cr-groupe">{{ $groupe }}</td></tr>
                        @endif
                        <tr>
                            <td>{{ $l['libelle'] }}</td>
                            <td class="{{ in_array($l['flag'], ['LL', 'HH']) ? 'cr-critique' : ($l['flag'] && $l['flag'] !== 'N' ? 'cr-anormal' : '') }}">{{ $l['valeur'] }} {{ $l['symbole'] }}</td>
                            <td>{{ $l['unite'] }}</td>
                            <td class="cr-muted">{{ $l['norme'] }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
            @foreach($ex['germes'] as $g)
                <p style="margin:4px 0"><strong>{{ $g['nom'] }}</strong> {{ $g['numeration'] }}</p>
                @if($g['antibiogramme'])
                    <table class="cr-resultats">
                        <thead><tr><th>Antibiotique</th><th>S / I / R</th><th>Antibiotique</th><th>S / I / R</th></tr></thead>
                        <tbody>
                        @foreach(array_chunk($g['antibiogramme'], 2) as $paire)
                            <tr>
                                @foreach($paire as $a)<td>{{ $a['antibiotique'] }}</td><td class="{{ $a['interpretation'] === 'R' ? 'cr-anormal' : '' }}">{{ $a['interpretation'] }}</td>@endforeach
                                @if(count($paire) === 1)<td></td><td></td>@endif
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            @endforeach
            @if($ex['commentaire'])<p class="cr-commentaire">{{ $ex['commentaire'] }}</p>@endif
            <p class="cr-muted" style="margin:2px 0 0">Validé par {{ $ex['valide_par'] }} le {{ $ex['valide_le'] }}</p>
        </div>
    @endforeach
@endforeach

<p class="cr-muted" style="margin-top:14px">↑ ↓ : hors valeurs de référence · ↑↑ ↓↓ : valeur critique · * : résultat anormal. Les valeurs de référence dépendent de l'âge, du sexe et de la méthode.</p>
<div class="cr-signature">Biologiste(s) : {{ implode(', ', $c['biologistes']) ?: '—' }}</div>
