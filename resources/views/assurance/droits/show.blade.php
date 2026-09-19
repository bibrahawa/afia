@extends('layouts.backend')

@php
    $gnf = fn ($m) => number_format((float) $m, 0, ',', ' ') . ' GNF';
    $pct = fn ($t) => rtrim(rtrim(number_format((float) $t, 2, ',', ' '), '0'), ',') . ' %';
    // Jauge « reste / plafond » : largeur utilisée et couleur selon ce qui reste.
    $jauge = function ($reste, $plafond) {
        if ($plafond === null || (float) $plafond <= 0) return null;
        $resteRatio = max(0, min(1, (float) $reste / (float) $plafond));
        return ['utilise' => round((1 - $resteRatio) * 100), 'ton' => $resteRatio <= 0 ? 'est-epuise' : ($resteRatio < .2 ? 'est-bas' : '')];
    };
@endphp

@section('style')
<style>
    .dr-resume { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin-bottom: 16px; padding: 14px 18px; border-radius: var(--hali-rayon); background: var(--hali-primaire-pale); border: 1px solid var(--hali-primaire-clair); color: var(--hali-primaire-fonce); font-size: .9rem; }
    .dr-resume.est-vide { background: var(--hali-alerte-pale); border-color: #fde68a; color: #78350f; }
    .dr-resume strong { font-size: 1rem; }
    .dr-carte { margin-bottom: 16px; }
    .dr-haut { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; padding: 16px 18px; border-bottom: 1px solid var(--hali-bordure); }
    .dr-rang { display: grid; place-items: center; width: 34px; height: 34px; flex: none; border-radius: 10px; background: var(--hali-primaire); color: #fff; font-weight: 800; }
    .dr-organisme { color: var(--hali-encre); font-size: 1.1rem; font-weight: 750; }
    .dr-formule { display: block; color: var(--hali-discret); font-size: .82rem; }
    .dr-carte-id { margin-left: auto; padding: 6px 12px; border-radius: 9px; background: #f3f4f6; font-family: "SF Mono", Consolas, monospace; font-size: .82rem; letter-spacing: .03em; }
    .dr-corps { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 18px; padding: 16px 18px; }
    .dr-bloc h3 { margin: 0 0 8px; color: var(--hali-discret); font-size: .72rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; }
    .dr-bloc p { margin: 0 0 4px; font-size: .88rem; }
    .dr-jauge { margin-bottom: 10px; }
    .dr-jauge-texte { display: flex; justify-content: space-between; gap: 8px; font-size: .84rem; }
    .dr-jauge-texte strong { font-variant-numeric: tabular-nums; }
    .dr-barre { height: 8px; margin-top: 5px; border-radius: 999px; background: var(--hali-succes-pale); overflow: hidden; }
    .dr-barre span { display: block; height: 100%; background: #d1d5db; }
    .dr-jauge.est-bas .dr-jauge-texte strong { color: #b45309; }
    .dr-jauge.est-epuise .dr-jauge-texte strong { color: var(--hali-danger); }
    .dr-jauge.est-epuise .dr-barre { background: var(--hali-danger-pale); }
    .dr-bon { display: flex; flex-wrap: wrap; align-items: baseline; gap: 4px 8px; padding: 8px 10px; margin-bottom: 6px; border-radius: 9px; background: #f9fafb; font-size: .82rem; }
    .dr-bon b { color: var(--hali-encre); }
    .dr-garanties { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 8px; padding: 0 18px 16px; }
    .dr-garantie { display: grid; gap: 4px; padding: 10px 12px; border: 1px solid var(--hali-bordure); border-radius: 10px; }
    .dr-garantie.est-exclu { background: #fafafa; border-style: dashed; }
    .dr-garantie-haut { display: flex; justify-content: space-between; gap: 8px; }
    .dr-famille { color: var(--hali-encre); font-weight: 650; font-size: .86rem; }
    .dr-taux { color: var(--hali-primaire-fonce); font-weight: 800; font-variant-numeric: tabular-nums; }
    .dr-garantie.est-exclu .dr-taux { color: var(--hali-discret); font-weight: 600; }
    .dr-conditions { display: flex; flex-wrap: wrap; gap: 4px; }
    .dr-conditions span { padding: 1px 7px; border-radius: 999px; font-size: .7rem; font-weight: 700; }
    .dr-note { margin: 0; padding: 0 18px 16px; color: var(--hali-discret); font-size: .8rem; }
    .dr-bon-form { padding: 0 18px 18px; }
    .dr-bon-form summary { color: var(--hali-primaire); cursor: pointer; }
    .dr-bon-form .row { margin-top: 8px; }
    @media (max-width: 991.98px) { .dr-corps { grid-template-columns: 1fr; } .dr-carte-id { margin-left: 0; } }
</style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">
    @include('assurance.partials.entete', [
        'titre' => 'Droits de ' . $patient->full_name,
        'fil' => [route('patient.show', $patient->id) => $patient->full_name, 0 => 'Vérification des droits'],
    ])

    @if($errors->any())<div class="hl-note hl-note-danger mb-3" role="alert"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    @if(count($droits['actives']))
        <div class="dr-resume">
            <i class="fas fa-shield-alt" aria-hidden="true"></i>
            <span><strong>{{ count($droits['actives']) }} couverture{{ count($droits['actives']) > 1 ? 's' : '' }} active{{ count($droits['actives']) > 1 ? 's' : '' }}</strong>
                au {{ $droits['date']->format('d/m/Y') }}.
                @if(count($droits['actives']) > 1) Elles s'appliquent dans l'ordre ci-dessous : chacune prend sa part sur ce qui reste après la précédente. @endif
                Le solde est à la charge du patient.</span>
        </div>
    @else
        <div class="dr-resume est-vide"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
            <span><strong>Aucune couverture active aujourd'hui</strong> : tous les actes sont à la charge du patient.</span></div>
    @endif

    @foreach($droits['actives'] as $ligne)
        @php
            $c = $ligne['couverture'];
            $jBenef = $jauge($ligne['reste_beneficiaire'], $ligne['plafond_beneficiaire']);
            $jFamille = $ligne['plafond_famille'] !== null ? $jauge($ligne['reste_famille'], $ligne['plafond_famille']) : null;
        @endphp
        <section class="hl-bloc dr-carte">
            <div class="dr-haut">
                <span class="dr-rang" title="Ordre d'application">{{ $ligne['rang'] }}</span>
                <div style="min-width:0">
                    <span class="dr-organisme">{{ $c->organisme->name }}</span>
                    <span class="dr-formule">
                        @if($c->contrat){{ $c->contrat->libelle ?: 'Police ' . $c->contrat->numero_police }} · formule {{ $c->formule->libelle }}@else Police {{ $c->projection->policy_number }}@endif
                    </span>
                </div>
                <span class="dr-carte-id" title="Numéro de carte">N° {{ $c->projection->policy_number }}</span>
            </div>

            <div class="dr-corps">
                <div class="dr-bloc">
                    <h3>Bénéficiaire</h3>
                    <p><strong>{{ $c->beneficiaire ? $c->beneficiaire->lien->libelle() : 'Assuré(e)' }}</strong>
                        @if($c->estAyantDroit())<span class="text-muted"> de {{ $c->beneficiaire->adhesion->patient->full_name }}</span>@endif</p>
                    <p class="text-muted">Droits du {{ $c->projection->start_date->format('d/m/Y') }} au {{ $c->projection->end_date?->format('d/m/Y') ?? '…' }}</p>
                    <p class="text-muted">Exercice {{ $ligne['exercice'][0]->format('d/m/Y') }} → {{ $ligne['exercice'][1]->format('d/m/Y') }}</p>
                </div>

                <div class="dr-bloc">
                    <h3>Plafonds de l'exercice</h3>
                    @if($ligne['plafond_beneficiaire'] === null)
                        <p><strong>Bénéficiaire :</strong> illimité</p>
                    @else
                        <div class="dr-jauge {{ $jBenef['ton'] ?? '' }}">
                            <div class="dr-jauge-texte"><span>Bénéficiaire</span><span><strong>{{ $gnf(max(0, $ligne['reste_beneficiaire'])) }}</strong> restants</span></div>
                            <div class="dr-barre" title="{{ $jBenef['utilise'] ?? 0 }} % utilisés"><span style="width: {{ $jBenef['utilise'] ?? 0 }}%"></span></div>
                            <span class="text-muted small">sur {{ $gnf($ligne['plafond_beneficiaire']) }}</span>
                        </div>
                    @endif
                    @if($jFamille)
                        <div class="dr-jauge {{ $jFamille['ton'] }}">
                            <div class="dr-jauge-texte"><span>Famille</span><span><strong>{{ $gnf(max(0, $ligne['reste_famille'])) }}</strong> restants</span></div>
                            <div class="dr-barre"><span style="width: {{ $jFamille['utilise'] }}%"></span></div>
                            <span class="text-muted small">sur {{ $gnf($ligne['plafond_famille']) }}</span>
                        </div>
                    @endif
                </div>

                <div class="dr-bloc">
                    <h3>Bons de prise en charge</h3>
                    @forelse($ligne['bons'] as $bon)
                        <div class="dr-bon">
                            <b>N° {{ $bon->numero }}</b>
                            <span>{{ $bon->famille_acte?->libelle() ?? 'Tous actes' }}</span>
                            <span class="text-muted">· {{ $bon->montant_accorde === null ? 'sans plafond' : 'reste ' . $gnf($bon->resteDisponible()) }} · jusqu'au {{ $bon->date_fin->format('d/m/Y') }}</span>
                            @can('assurance.referentiel.manage')
                                @unless($bon->utilisations()->exists())
                                    <form method="POST" action="{{ route('assurance.bons.annuler', $bon) }}" class="d-inline ms-auto" onsubmit="return confirm('Annuler ce bon ?');">@csrf
                                        <button class="btn btn-link btn-sm p-0 text-danger">Annuler</button></form>
                                @endunless
                            @endcan
                        </div>
                    @empty
                        <p class="text-muted">Aucun bon en cours.</p>
                    @endforelse
                </div>
            </div>

            <div class="dr-garanties">
                @foreach($ligne['garanties'] as $g)
                    <div class="dr-garantie {{ $g['exclu'] ? 'est-exclu' : '' }}">
                        <div class="dr-garantie-haut"><span class="dr-famille">{{ $g['famille']->libelle() }}</span><span class="dr-taux">{{ $g['exclu'] ? 'Exclu' : $pct($g['taux']) }}</span></div>
                        @if($g['plafond_par_acte'] !== null)<span class="text-muted small">Plafond {{ $gnf($g['plafond_par_acte']) }} par acte</span>@endif
                        @if($g['accord_prealable'] || $g['fin_carence'] || $g['limite'])
                            <div class="dr-conditions">
                                @if($g['accord_prealable'])<span class="hl-s-alerte">Accord préalable</span>@endif
                                @if($g['fin_carence'])<span class="hl-s-danger">Carence → {{ $g['fin_carence']->format('d/m/Y') }}</span>@endif
                                @if($g['limite'])<span class="hl-s-neutre">{{ $g['limite'][0] }} {{ \App\Models\Assurance\FormuleGarantie::PERIODES[$g['limite'][1]] ?? '' }}</span>@endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            <p class="dr-note">Un acte n'est pris en charge que s'il figure dans la convention de {{ $c->organisme->name }} (règle de sa famille ou ligne par acte).</p>

            @can('assurance.referentiel.manage')
                @if($c->beneficiaire)
                    <details class="dr-bon-form">
                        <summary><i class="fas fa-plus-circle" aria-hidden="true"></i> Enregistrer un bon de prise en charge</summary>
                        <form method="POST" action="{{ route('assurance.bons.store', $c->beneficiaire) }}" class="row g-2">@csrf
                            <div class="col-md-3"><label class="form-label">N° du bon *</label><input name="numero" class="form-control form-control-sm" required maxlength="100"></div>
                            <div class="col-md-3"><label class="form-label">Actes concernés</label>
                                <select name="famille_acte" class="form-control form-control-sm">
                                    <option value="">Tous les actes</option>
                                    @foreach(\App\Enums\Assurance\FamilleActe::cases() as $f)<option value="{{ $f->value }}">{{ $f->libelle() }}</option>@endforeach
                                </select></div>
                            <div class="col-md-2"><label class="form-label">Montant accordé</label><input type="number" min="0" step="1" name="montant_accorde" class="form-control form-control-sm" placeholder="Sans plafond"></div>
                            <div class="col-md-2"><label class="form-label">Valable du *</label><input type="date" name="date_debut" class="form-control form-control-sm" required value="{{ today()->toDateString() }}"></div>
                            <div class="col-md-2"><label class="form-label">au *</label><input type="date" name="date_fin" class="form-control form-control-sm" required value="{{ today()->addDays(30)->toDateString() }}"></div>
                            <div class="col-md-10"><input name="notes" class="form-control form-control-sm" placeholder="Notes (motif, médecin conseil…)" aria-label="Notes"></div>
                            <div class="col-md-2"><button class="btn btn-sm btn-primary w-100">Enregistrer</button></div>
                        </form>
                    </details>
                @endif
            @endcan
        </section>
    @endforeach

    @if($droits['inactives'])
        <section class="hl-bloc">
            <h2 class="hl-bloc-titre">Couvertures qui ne s'appliquent pas aujourd'hui</h2>
            @foreach($droits['inactives'] as $i)
                <div class="d-flex flex-wrap gap-2 px-3 py-2" style="border-top:1px solid #f3f4f6; font-size:.88rem">
                    <strong>{{ $i['ligne']->insuranceCompany?->name }}</strong>
                    <span class="text-muted">police {{ $i['ligne']->policy_number }}</span>
                    @if($i['ligne']->beneficiaire && $i['ligne']->beneficiaire->lien !== \App\Enums\Assurance\LienBeneficiaire::Adherent)
                        <span class="text-muted">({{ mb_strtolower($i['ligne']->beneficiaire->lien->libelle()) }} de {{ $i['ligne']->beneficiaire->adhesion->patient->full_name }})</span>
                    @endif
                    <span class="hl-statut hl-s-neutre ms-auto">{{ $i['raison'] }}</span>
                </div>
            @endforeach
        </section>
    @endif
</div></div>
@endsection
