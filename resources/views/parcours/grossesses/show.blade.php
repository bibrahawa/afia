@extends('layouts.backend')

@php
    $issues = \App\Models\Parcours\Grossesse::ISSUES;

    $patiente = $grossesse->patient;
    $noms = preg_split('/\s+/', trim((string) $patiente->full_name));
    $initiales = mb_strtoupper(mb_substr($noms[0] ?? '', 0, 1) . mb_substr(end($noms) ?: '', 0, 1));
    $sansDr = fn ($nom) => preg_replace('/^Dr\.?\s+/i', '', (string) $nom);

    $enCours = $grossesse->estEnCours();
    [$sa, $j] = $grossesse->terme() ?? [0, 0];
    $joursGrossesse = $sa * 7 + $j;
    $avancement = min(100, round($joursGrossesse * 100 / 280));
    $trimestre = $sa < 14 ? '1er trimestre' : ($sa < 28 ? '2e trimestre' : '3e trimestre');
    $joursAvantDpa = (int) today()->diffInDays($grossesse->dpa, false);

    $statuts = [
        'faite'        => ['libelle' => 'Faite',        'ton' => 'hl-s-succes', 'classe' => 'est-faite'],
        'a_venir'      => ['libelle' => 'À venir',      'ton' => 'hl-s-neutre', 'classe' => 'est-a-venir'],
        'a_programmer' => ['libelle' => 'À programmer', 'ton' => 'hl-s-alerte', 'classe' => 'est-a-programmer'],
        'manquee'      => ['libelle' => 'Manquée',      'ton' => 'hl-s-danger', 'classe' => 'est-manquee'],
    ];
    $compte = collect($calendrier)->countBy('statut');

    // Tension : à partir de 140/90 pendant la grossesse, on alerte (pré-éclampsie).
    $tensionHaute = function (?string $tension): bool {
        if (! $tension || ! str_contains($tension, '/')) return false;
        [$sys, $dia] = array_map('intval', explode('/', $tension));
        return $sys >= 140 || $dia >= 90;
    };
    $alerteTension = collect($mesures)->contains(fn ($m) => $tensionHaute($m['tension']));

    // Courbe de poids : dessinée en SVG, sans bibliothèque.
    $poids = collect($mesures)->filter(fn ($m) => $m['poids'] !== null)->values();
    $courbe = null;
    if ($poids->count() >= 2) {
        $min = $poids->min('poids') - 1; $max = $poids->max('poids') + 1;
        $largeur = 300; $hauteur = 70; $n = $poids->count() - 1;
        $points = $poids->map(fn ($m, $i) => [
            round($i * $largeur / $n, 1),
            round($hauteur - ($m['poids'] - $min) * $hauteur / max(0.1, $max - $min), 1),
        ]);
        $courbe = [
            'ligne' => $points->map(fn ($p) => $p[0] . ',' . $p[1])->join(' '),
            'points' => $points,
            'prise' => round($poids->last()['poids'] - $poids->first()['poids'], 1),
        ];
    }

    $kg = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, ',', ''), '0'), ',');
@endphp

@section('content')
<style>
    .gx-avatar { width: 52px; height: 52px; flex: 0 0 52px; display: grid; place-items: center; border-radius: 14px; background: #fdf2f8; color: #9d174d; font-size: 1.1rem; font-weight: 700; }
    .gx-identite { display: flex; align-items: center; gap: 16px; }

    .gx-grille { display: grid; grid-template-columns: minmax(0, 340px) minmax(0, 1fr); gap: 20px; align-items: start; }
    .gx-cote, .gx-principal { min-width: 0; }
    .gx-cote { display: grid; gap: 16px; position: sticky; top: 88px; }
    .gx-principal { display: grid; gap: 16px; }

    /* Terme */
    .gx-terme { padding: 20px 20px 18px; }
    .gx-terme-haut { display: flex; align-items: baseline; justify-content: space-between; gap: 8px; }
    .gx-terme-valeur { font-size: 2.1rem; font-weight: 700; color: var(--hali-encre); letter-spacing: -.02em; font-variant-numeric: tabular-nums; line-height: 1.1; }
    .gx-terme-trim { color: var(--hali-discret); font-size: .85rem; }
    .gx-trimestres { position: relative; display: grid; grid-template-columns: 14fr 14fr 12fr; gap: 3px; margin: 16px 0 6px; }
    .gx-trimestres span { height: 10px; background: #fce7f3; }
    .gx-trimestres span:first-child { border-radius: 999px 0 0 999px; }
    .gx-trimestres span:last-child { border-radius: 0 999px 999px 0; }
    .gx-trimestres i { position: absolute; top: -4px; width: 4px; height: 18px; margin-left: -2px; border-radius: 2px; background: #be185d; box-shadow: 0 0 0 2px #fff; }
    .gx-trimestres b { position: absolute; top: 0; left: 0; height: 10px; border-radius: 999px 0 0 999px; background: #be185d; opacity: .85; }
    .gx-reperes { display: grid; grid-template-columns: 14fr 14fr 12fr; color: #9ca3af; font-size: .72rem; }
    .gx-reperes span:last-child { text-align: right; }

    .gx-faits { display: grid; gap: 9px; margin: 18px 0 0; padding-top: 16px; border-top: 1px solid #f3f4f6; font-size: .88rem; }
    .gx-faits div { display: flex; justify-content: space-between; gap: 12px; }
    .gx-faits dt { color: var(--hali-discret); font-weight: 500; }
    .gx-faits dd { margin: 0; color: var(--hali-encre); font-weight: 600; text-align: right; font-variant-numeric: tabular-nums; }
    .gx-dpa-note { display: block; color: var(--hali-discret); font-weight: 500; font-size: .78rem; }
    .gx-depasse { color: var(--hali-danger) !important; }

    /* Actions */
    .gx-action { border-top: 1px solid #f3f4f6; }
    .gx-action:first-of-type { border-top: 0; }
    .gx-action summary { display: flex; align-items: center; gap: 10px; padding: 13px 18px; list-style: none; color: var(--hali-encre); font-weight: 600; font-size: .88rem; }
    .gx-action summary::-webkit-details-marker { display: none; }
    .gx-action summary::after { content: ""; margin-left: auto; width: 7px; height: 7px; border-right: 2px solid #9ca3af; border-bottom: 2px solid #9ca3af; transform: rotate(45deg); transition: transform .12s ease; }
    .gx-action[open] summary::after { transform: rotate(-135deg); }
    .gx-action summary i { width: 16px; text-align: center; color: var(--hali-discret); }
    .gx-action.est-risque summary, .gx-action.est-risque summary i { color: var(--hali-danger); }
    .gx-action-corps { display: grid; gap: 10px; padding: 0 18px 16px; }
    .gx-action-corps label { display: grid; gap: 4px; margin: 0; font-size: .8rem; }
    .gx-deux { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 10px; }
    .gx-action-corps input, .gx-action-corps select, .gx-action-corps textarea { width: 100%; min-width: 0; }
    .gx-aide { margin: 0; color: var(--hali-discret); font-size: .8rem; }
    .gx-bebe { display: grid; gap: 10px; padding: 12px; border-radius: 10px; background: #fdf2f8; }
    .gx-bebe strong { font-size: .82rem; color: #9d174d; }
    .gx-bouton-risque { border-color: #fecaca; color: var(--hali-danger); }
    .gx-bouton-risque:hover { background: var(--hali-danger-pale); border-color: var(--hali-danger); color: var(--hali-danger); }

    /* Frise des CPN */
    .gx-frise { position: relative; height: 52px; margin: 14px 16px 0; }
    .gx-axe { position: absolute; left: 0; right: 0; top: 22px; height: 4px; border-radius: 999px; background: #f3f4f6; }
    .gx-axe b { position: absolute; left: 0; top: 0; bottom: 0; border-radius: 999px; background: #fbcfe8; }
    .gx-cpn { position: absolute; top: 10px; transform: translateX(-50%); display: grid; justify-items: center; gap: 6px; }
    .gx-cpn span {
        width: 28px; height: 28px; display: grid; place-items: center; border-radius: 50%;
        border: 2px solid #fff; box-shadow: 0 0 0 1px var(--hali-bordure); background: #fff;
        color: var(--hali-discret); font-size: .66rem; font-weight: 700;
    }
    .gx-cpn.est-faite span { background: var(--hali-succes); color: #fff; box-shadow: none; }
    .gx-cpn.est-manquee span { background: var(--hali-danger-pale); color: var(--hali-danger); box-shadow: 0 0 0 1px #fecaca; }
    .gx-cpn.est-a-programmer span { background: var(--hali-alerte); color: #fff; box-shadow: 0 0 0 4px var(--hali-alerte-pale); }
    .gx-aujourdhui { position: absolute; top: 4px; width: 2px; height: 40px; margin-left: -1px; background: #be185d; }
    .gx-aujourdhui em { position: absolute; top: -16px; left: 50%; transform: translateX(-50%); color: #be185d; font-size: .7rem; font-style: normal; font-weight: 700; white-space: nowrap; }

    .gx-resume { display: flex; flex-wrap: wrap; gap: 8px; padding: 0 18px 14px; }

    .gx-contacts { margin: 0; padding: 0; list-style: none; border-top: 1px solid var(--hali-bordure); }
    .gx-contact { display: grid; grid-template-columns: 70px 110px 130px minmax(0, 1fr); align-items: center; gap: 12px; padding: 11px 18px; border-bottom: 1px solid #f3f4f6; font-size: .88rem; }
    .gx-contact:last-child { border-bottom: 0; }
    .gx-contact b { color: var(--hali-encre); font-variant-numeric: tabular-nums; }
    .gx-contact time { color: var(--hali-texte); font-variant-numeric: tabular-nums; }
    .gx-contact a { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .gx-contact .gx-rien { color: #9ca3af; }
    .gx-contact.est-a-programmer { background: var(--hali-alerte-pale); }
    .gx-note-oms { margin: 0; padding: 12px 18px; border-top: 1px solid var(--hali-bordure); color: var(--hali-discret); font-size: .8rem; }

    /* Mesures */
    .gx-mesures-haut { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 16px; align-items: center; padding: 16px 18px; border-bottom: 1px solid var(--hali-bordure); }
    .gx-courbe svg { display: block; width: 100%; max-width: 320px; height: 80px; overflow: visible; }
    .gx-prise { text-align: right; }
    .gx-prise b { display: block; color: var(--hali-encre); font-size: 1.4rem; font-weight: 700; font-variant-numeric: tabular-nums; }
    .gx-prise span { color: var(--hali-discret); font-size: .8rem; }
    .gx-table { width: 100%; border-collapse: collapse; font-size: .88rem; }
    .gx-table th { padding: 10px 18px; background: #fafbfc; border-bottom: 1px solid var(--hali-bordure); color: var(--hali-discret); font-size: .78rem; font-weight: 600; text-align: left; }
    .gx-table td { padding: 11px 18px; border-bottom: 1px solid #f3f4f6; color: var(--hali-texte); font-variant-numeric: tabular-nums; }
    .gx-table tr:last-child td { border-bottom: 0; }
    .gx-table .est-haute { color: var(--hali-danger); font-weight: 700; }

    @media (max-width: 1199.98px) { .gx-grille { grid-template-columns: 1fr; } .gx-cote { position: static; } }
    @media (max-width: 767.98px) {
        .gx-contact { grid-template-columns: 60px 1fr auto; }
        .gx-contact > :last-child { grid-column: 1 / -1; }
        .gx-mesures-haut { grid-template-columns: 1fr; }
        .gx-prise { text-align: left; }
    }
</style>

<div class="container"><div class="page-inner hl">

    <header class="hl-entete">
        <div class="gx-identite">
            <div class="gx-avatar" aria-hidden="true">{{ $initiales }}</div>
            <div>
                <h1>{{ $patiente->full_name }}</h1>
                <p>
                    Suivi de grossesse
                    @if($grossesse->gestite !== null) · G{{ $grossesse->gestite }}P{{ $grossesse->parite ?? 0 }}@endif
                    @if($grossesse->medecin) · Dr {{ $sansDr($grossesse->medecin->full_name) }}@endif
                </p>
            </div>
        </div>
        <div class="hl-entete-actions">
            @if(! $enCours)
                <span class="hl-statut hl-s-neutre" style="align-self:center">
                    {{ $issues[$grossesse->issue] ?? 'Clôturé' }}{{ $grossesse->date_issue ? ' le ' . $grossesse->date_issue->format('d/m/Y') : '' }}
                </span>
            @endif
            <a href="{{ route('parcours.dossier.show', $grossesse->patient_id) }}" class="hl-bouton"><i class="fas fa-folder-open" aria-hidden="true"></i> Dossier de la patiente</a>
        </div>
    </header>

    @if($alerteTension)
        <p class="hl-note hl-note-alerte mb-3" role="alert" style="background: var(--hali-danger-pale); color: #7f1d1d">
            <i class="fas fa-exclamation-triangle mt-1" aria-hidden="true"></i>
            <span><strong>Tension élevée relevée pendant la grossesse</strong> (140/90 ou plus). Voir le détail des mesures ci-dessous.</span>
        </p>
    @endif

    <div class="gx-grille">

        {{-- ============================================ Colonne de côté --}}
        <aside class="gx-cote">
            <section class="hl-bloc gx-terme">
                <div class="gx-terme-haut">
                    <span class="gx-terme-valeur">{{ $enCours ? $grossesse->termeLisible() : $grossesse->termeLisible($grossesse->date_issue) }}</span>
                    <span class="gx-terme-trim">{{ $enCours ? $trimestre : 'à l\'issue' }}</span>
                </div>

                <div class="gx-trimestres" role="progressbar" aria-label="Avancement de la grossesse" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $avancement }}">
                    <span></span><span></span><span></span>
                    <b style="width: {{ $avancement }}%"></b>
                    <i style="left: {{ $avancement }}%"></i>
                </div>
                <div class="gx-reperes" aria-hidden="true"><span>1er trim.</span><span>2e trim.</span><span>40 SA</span></div>

                <dl class="gx-faits">
                    <div>
                        <dt>Accouchement prévu</dt>
                        <dd>
                            {{ $grossesse->dpa->format('d/m/Y') }}
                            @if($enCours)
                                <span class="gx-dpa-note {{ $joursAvantDpa < 0 ? 'gx-depasse' : '' }}">
                                    @if($joursAvantDpa < 0)
                                        Dépassé de {{ abs($joursAvantDpa) }} jour{{ abs($joursAvantDpa) > 1 ? 's' : '' }}
                                    @elseif($joursAvantDpa === 0)
                                        Aujourd'hui
                                    @else
                                        Dans {{ $joursAvantDpa }} jour{{ $joursAvantDpa > 1 ? 's' : '' }}
                                    @endif
                                </span>
                            @endif
                        </dd>
                    </div>
                    <div><dt>Dernières règles</dt><dd>{{ $grossesse->ddr->format('d/m/Y') }}</dd></div>
                    <div><dt>Gestité / parité</dt><dd>{{ $grossesse->gestite ?? '—' }} / {{ $grossesse->parite ?? '—' }}</dd></div>
                    <div><dt>Médecin</dt><dd>{{ $grossesse->medecin ? 'Dr ' . $sansDr($grossesse->medecin->full_name) : '—' }}</dd></div>
                </dl>

                @if($grossesse->notes)
                    <p class="gx-aide mt-3" style="white-space: pre-line">{{ $grossesse->notes }}</p>
                @endif
            </section>

            @can('parcours.grossesse')
                @if($enCours)
                    <section class="hl-bloc">
                        <details class="gx-action">
                            <summary><i class="fas fa-calendar-alt" aria-hidden="true"></i> Corriger la date des dernières règles</summary>
                            <form method="POST" action="{{ route('parcours.grossesses.ddr', $grossesse) }}" class="gx-action-corps">
                                @csrf
                                <p class="gx-aide">Après une échographie de datation. Le terme, la date d'accouchement et le calendrier des CPN sont recalculés.</p>
                                <label>Nouvelle date
                                    <input type="date" name="ddr" class="form-control" value="{{ $grossesse->ddr->toDateString() }}" max="{{ today()->toDateString() }}" required>
                                </label>
                                <button class="hl-bouton hl-bouton-plein" type="submit">Enregistrer la correction</button>
                            </form>
                        </details>

                        <details class="gx-action est-risque">
                            <summary><i class="fas fa-flag-checkered" aria-hidden="true"></i> Clôturer le suivi</summary>
                            <form method="POST" action="{{ route('parcours.grossesses.cloturer', $grossesse) }}" class="gx-action-corps"
                                  onsubmit="return confirm('Clôturer ce suivi de grossesse ? Il ne sera plus modifiable.');">
                                @csrf
                                <div class="gx-deux">
                                    <label>Issue
                                        <select name="issue" id="gx-issue" class="form-control">
                                            @foreach($issues as $valeur => $libelle)
                                                <option value="{{ $valeur }}">{{ $libelle }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                    <label>Date
                                        <input type="date" name="date_issue" class="form-control" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" required>
                                    </label>
                                </div>

                                <div class="gx-bebe" id="gx-bebe">
                                    <strong><i class="fas fa-baby" aria-hidden="true"></i> Nouveau-né (facultatif)</strong>
                                    <p class="gx-aide">Son dossier sera créé et rattaché à sa mère. Le poids devient sa première mesure de croissance.</p>
                                    <div class="gx-deux">
                                        <label>Prénom<input name="nouveau_ne[prenom]" class="form-control" maxlength="50"></label>
                                        <label>Sexe
                                            <select name="nouveau_ne[sexe]" class="form-control">
                                                <option value="Femme">Fille</option>
                                                <option value="Homme">Garçon</option>
                                            </select>
                                        </label>
                                    </div>
                                    <div class="gx-deux">
                                        <label>Poids (kg)<input type="number" step="0.01" min="0.3" max="8" name="nouveau_ne[poids_kg]" class="form-control" inputmode="decimal"></label>
                                        <label>Taille (cm)<input type="number" step="0.1" min="20" max="70" name="nouveau_ne[taille_cm]" class="form-control" inputmode="decimal"></label>
                                    </div>
                                </div>

                                <label>Note (facultatif)
                                    <textarea name="notes" class="form-control" rows="2" maxlength="2000" placeholder="Voie d'accouchement, complications…"></textarea>
                                </label>
                                <button class="hl-bouton gx-bouton-risque" type="submit">Clôturer le suivi</button>
                            </form>
                        </details>
                    </section>
                @endif
            @endcan
        </aside>

        {{-- ============================================ Principal --}}
        <div class="gx-principal">

            <section class="hl-bloc">
                <h2 class="hl-bloc-titre">Consultations prénatales <small>8 contacts recommandés par l'OMS</small></h2>

                <div class="hl-bloc-corps" style="padding-bottom: 6px">
                    <div class="gx-frise" aria-hidden="true">
                        <div class="gx-axe"><b style="width: {{ $avancement }}%"></b></div>
                        @if($enCours)
                            <div class="gx-aujourdhui" style="left: {{ $avancement }}%"><em>Aujourd'hui</em></div>
                        @endif
                        @foreach($calendrier as $contact)
                            <div class="gx-cpn {{ $statuts[$contact['statut']]['classe'] }}" style="left: {{ min(100, $contact['semaines'] * 100 / 40) }}%">
                                <span title="{{ $contact['semaines'] }} SA">@if($contact['statut'] === 'faite')<i class="fas fa-check"></i>@else{{ $contact['semaines'] }}@endif</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="gx-resume">
                    @foreach(['faite', 'a_programmer', 'manquee', 'a_venir'] as $cle)
                        @if(($compte[$cle] ?? 0) > 0)
                            <span class="hl-statut {{ $statuts[$cle]['ton'] }}">{{ $compte[$cle] }} {{ mb_strtolower($statuts[$cle]['libelle']) }}{{ $compte[$cle] > 1 && $cle !== 'a_programmer' && $cle !== 'a_venir' ? 's' : '' }}</span>
                        @endif
                    @endforeach
                </div>

                <ol class="gx-contacts">
                    @foreach($calendrier as $contact)
                        <li class="gx-contact {{ $statuts[$contact['statut']]['classe'] }}">
                            <b>{{ $contact['semaines'] }} SA</b>
                            <time datetime="{{ $contact['date_cible']->toDateString() }}">{{ $contact['date_cible']->format('d/m/Y') }}</time>
                            <span><span class="hl-statut {{ $statuts[$contact['statut']]['ton'] }}">{{ $statuts[$contact['statut']]['libelle'] }}</span></span>
                            @if($contact['consultation'])
                                <a href="{{ route('consultation.show', $contact['consultation']) }}">
                                    Vue le {{ $contact['consultation']->created_at->format('d/m/Y') }}{{ $contact['consultation']->diagnostic ? ' — ' . \Illuminate\Support\Str::limit($contact['consultation']->diagnostic, 50) : '' }}
                                </a>
                            @else
                                <span class="gx-rien">{{ $contact['statut'] === 'a_programmer' ? 'Aucune consultation : à programmer avec la patiente' : '—' }}</span>
                            @endif
                        </li>
                    @endforeach
                </ol>
                <p class="gx-note-oms">Une consultation dans les deux semaines autour de la date cible honore le contact. Calendrier indicatif.</p>
            </section>

            <section class="hl-bloc">
                <h2 class="hl-bloc-titre">Poids et tension <small>repris des constantes prises à l'accueil</small></h2>

                @if(empty($mesures))
                    <div class="hl-vide">
                        <i class="fas fa-weight" aria-hidden="true"></i>
                        Aucune constante relevée depuis le début de la grossesse.
                    </div>
                @else
                    @if($courbe)
                        <div class="gx-mesures-haut">
                            <div class="gx-courbe">
                                <svg viewBox="-6 -6 312 82" preserveAspectRatio="none" role="img" aria-label="Évolution du poids">
                                    <polyline points="{{ $courbe['ligne'] }}" fill="none" stroke="#be185d" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke"/>
                                    @foreach($courbe['points'] as $point)
                                        <circle cx="{{ $point[0] }}" cy="{{ $point[1] }}" r="3.5" fill="#fff" stroke="#be185d" stroke-width="2" vector-effect="non-scaling-stroke"/>
                                    @endforeach
                                </svg>
                            </div>
                            <div class="gx-prise">
                                <b>{{ $courbe['prise'] > 0 ? '+' : '' }}{{ $kg($courbe['prise']) }} kg</b>
                                <span>depuis la première mesure</span>
                            </div>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="gx-table">
                            <thead><tr><th>Date</th><th>Terme</th><th>Poids</th><th>Tension</th></tr></thead>
                            <tbody>
                                @foreach(array_reverse($mesures) as $m)
                                    <tr>
                                        <td>{{ $m['date']->format('d/m/Y') }}</td>
                                        <td>{{ $m['terme'] }}</td>
                                        <td>{{ $m['poids'] !== null ? $kg($m['poids']) . ' kg' : '—' }}</td>
                                        <td class="{{ $tensionHaute($m['tension']) ? 'est-haute' : '' }}">
                                            {{ $m['tension'] ?? '—' }}
                                            @if($tensionHaute($m['tension']))<i class="fas fa-exclamation-triangle ml-1" aria-label="Tension élevée"></i>@endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>
    </div>
</div></div>

<script>
(function () {
    // Le volet nouveau-né n'a de sens que pour un accouchement.
    var issue = document.getElementById('gx-issue');
    var bebe = document.getElementById('gx-bebe');
    if (!issue || !bebe) return;
    function basculer() {
        var accouchement = issue.value === 'accouchement';
        bebe.hidden = !accouchement;
        bebe.querySelectorAll('input, select').forEach(function (c) { c.disabled = !accouchement; });
    }
    issue.addEventListener('change', basculer);
    basculer();
})();
</script>
@endsection
