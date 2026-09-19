{{--
    Corps du compte rendu, construit UNIQUEMENT à partir de l'instantané $c (jamais des tables vivantes).
    Partagé entre le PDF (DomPDF : tableaux uniquement, ni flexbox ni grille) et la page patient (téléphone).
    Lisible en noir et blanc : un résultat hors norme se repère sans couleur (symbole, gras, encadré pour un critique).
--}}
@if($c['est_rectificatif'])
    <div class="cr-rectif">
        <strong>COMPTE RENDU RECTIFICATIF</strong> : il annule et remplace la version précédente.
        @if($c['motif_rectification'])<br><span class="cr-rectif-motif">{!! nl2br(e($c['motif_rectification'])) !!}</span>@endif
    </div>
@endif
@if($c['est_partiel'])
    <div class="cr-partiel"><strong>Compte rendu partiel</strong> : d'autres résultats de cette demande sont encore en cours.</div>
@endif

<table class="cr-ident">
    <tr>
        <td class="cr-ident-bloc" style="width:55%">
            <span class="cr-etiquette">Patient</span>
            <span class="cr-ident-nom">{{ $c['patient']['nom'] }}</span><br>
            {{ $c['patient']['sexe'] }} · {{ $c['patient']['age'] }}@if($c['patient']['grossesse']) · <strong>{{ $c['patient']['grossesse'] }}</strong>@endif<br>
            <span class="cr-muted">Identifiant santé : {{ $c['patient']['identifiant'] }}</span>
        </td>
        <td class="cr-ident-bloc">
            <span class="cr-etiquette">Demande</span>
            <strong>{{ $c['demande']['numero'] }}</strong> du {{ $c['demande']['date'] }}<br>
            Prescripteur : {{ $c['demande']['prescripteur'] }}<br>
            <span class="cr-muted">Version {{ $cr->version }} · émise le {{ $c['emis_le'] }}</span>
        </td>
    </tr>
</table>

@foreach($c['sections'] as $section)
    <h3 class="cr-section">{{ $section['nom'] }}</h3>
    @foreach($section['examens'] as $ex)
        <div class="cr-examen">
            <table class="cr-examen-entete">
                <tr>
                    <td class="cr-examen-titre">
                        {{ $ex['nom'] }}
                        @if($ex['rectifie_dans_cette_version'])<span class="cr-badge">RECTIFIÉ</span>@endif
                    </td>
                    <td class="cr-examen-methode">
                        @if($ex['methode']){{ $ex['methode'] }}@endif
                        @if($ex['sous_traitant'])@if($ex['methode']) · @endif réalisé par {{ $ex['sous_traitant'] }}@endif
                    </td>
                </tr>
            </table>

            @if($ex['lignes'])
                <table class="cr-resultats">
                    <thead><tr><th class="cr-col-param">Paramètre</th><th class="cr-col-valeur">Résultat</th><th class="cr-col-unite">Unité</th><th class="cr-col-norme">Valeurs de référence</th></tr></thead>
                    <tbody>
                    @php $groupe = null; @endphp
                    @foreach($ex['lignes'] as $l)
                        @if($l['groupe'] && $l['groupe'] !== $groupe)
                            @php $groupe = $l['groupe']; @endphp
                            <tr><td colspan="4" class="cr-groupe">{{ $groupe }}</td></tr>
                        @endif
                        @php
                            $critique = in_array($l['flag'], ['LL', 'HH'], true);
                            $anormal = ! $critique && $l['flag'] && $l['flag'] !== 'N';
                        @endphp
                        <tr class="{{ $critique ? 'cr-ligne-critique' : ($anormal ? 'cr-ligne-anormale' : '') }}">
                            <td class="cr-col-param">{{ $l['libelle'] }}</td>
                            <td class="cr-col-valeur">
                                <span class="{{ $critique ? 'cr-critique' : ($anormal ? 'cr-anormal' : 'cr-normal') }}">{{ $l['valeur'] }}@if($l['symbole']) {{ $l['symbole'] }}@endif</span>
                            </td>
                            <td class="cr-col-unite">{{ $l['unite'] }}</td>
                            <td class="cr-col-norme">{{ $l['norme'] }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif

            @foreach($ex['germes'] as $g)
                <p class="cr-germe"><strong>{{ $g['nom'] }}</strong>@if($g['numeration']) · {{ $g['numeration'] }}@endif</p>
                @if($g['antibiogramme'])
                    <table class="cr-resultats cr-antibio">
                        <thead><tr><th>Antibiotique</th><th class="cr-col-sir">S / I / R</th><th>Antibiotique</th><th class="cr-col-sir">S / I / R</th></tr></thead>
                        <tbody>
                        @foreach(array_chunk($g['antibiogramme'], 2) as $paire)
                            <tr>
                                @foreach($paire as $a)
                                    <td>{{ $a['antibiotique'] }}</td>
                                    <td class="cr-col-sir"><span class="{{ $a['interpretation'] === 'R' ? 'cr-resistant' : '' }}">{{ $a['interpretation'] }}</span></td>
                                @endforeach
                                @if(count($paire) === 1)<td></td><td></td>@endif
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <p class="cr-muted cr-legende-antibio">S : sensible · I : intermédiaire · R : résistant</p>
                @endif
            @endforeach

            @if($ex['commentaire'])<p class="cr-commentaire"><strong>Commentaire du biologiste :</strong> {{ $ex['commentaire'] }}</p>@endif
            <p class="cr-valide">Validé par {{ $ex['valide_par'] }} le {{ $ex['valide_le'] }}</p>
        </div>
    @endforeach
@endforeach

<table class="cr-fin">
    <tr>
        <td class="cr-legende">
            <strong>Lecture</strong> : ↑ ou ↓ en gras = hors des valeurs de référence ; ↑↑ ou ↓↓ encadré = valeur critique ; * = résultat anormal.
            Les valeurs de référence dépendent de l'âge, du sexe et de la méthode. Ces résultats s'interprètent avec votre médecin.
        </td>
        <td class="cr-signature">
            <span class="cr-etiquette">Biologiste{{ count($c['biologistes']) > 1 ? 's' : '' }}</span>
            {{ implode(', ', $c['biologistes']) ?: '—' }}
        </td>
    </tr>
</table>
