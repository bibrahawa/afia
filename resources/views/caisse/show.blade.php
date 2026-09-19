@extends('layouts.backend')

@php
    $gnf = fn ($v) => number_format((float) $v, 0, ',', ' ');
    $initiales = mb_strtoupper(mb_substr((string) $patient->first_name, 0, 1) . mb_substr((string) $patient->last_name, 0, 1)) ?: 'P';
    $totalActes = $lignes->sum('total');
    $totalAssurance = $lignes->sum('part_assurance');
    $dejaPaye = $lignes->sum('deja_paye');
    // Montant exact, sans « .00 » : « Tout » ne doit jamais dépasser le reste dû.
    $valeurDu = rtrim(rtrim(number_format((float) $resteDu, 2, '.', ''), '0'), '.');
@endphp

@section('style')
<style>
    .cs-grille { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 360px); gap: 16px; align-items: start; }
    .cs-table { width: 100%; border-collapse: collapse; font-size: .88rem; }
    .cs-table th { padding: 10px 16px; background: #fafbfc; border-bottom: 1px solid var(--hali-bordure); color: var(--hali-discret); font-size: .78rem; font-weight: 600; text-align: left; white-space: nowrap; }
    .cs-table td { padding: 12px 16px; border-top: 1px solid #f3f4f6; vertical-align: top; }
    .cs-table tbody tr:first-child td { border-top: 0; }
    .cs-table .cs-n { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
    .cs-table tfoot td { padding: 12px 16px; border-top: 2px solid var(--hali-bordure); background: #fafbfc; font-weight: 700; color: var(--hali-encre); }
    .cs-libelle { color: var(--hali-encre); font-weight: 600; }
    .cs-sous { display: block; color: var(--hali-discret); font-size: .78rem; font-weight: 500; }
    .cs-assurance { color: var(--hali-succes); }
    .cs-reste { color: var(--hali-encre); font-weight: 700; }
    .cs-alerte { display: flex; gap: 6px; margin-top: 6px; color: var(--hali-alerte); font-size: .8rem; }
    .cs-remise { width: 110px; margin-left: auto; text-align: right; }
    .cs-pied-facture { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 8px 12px; padding: 12px 16px; border-top: 1px solid var(--hali-bordure); background: #fafbfc; border-radius: 0 0 var(--hali-rayon) var(--hali-rayon); font-size: .86rem; color: var(--hali-texte); }
    .cs-pied-facture b { color: var(--hali-encre); font-variant-numeric: tabular-nums; }
    .cs-actions-facture { display: flex; gap: 8px; flex-wrap: wrap; }

    .cs-encaisser { position: sticky; top: calc(var(--hali-haut, 78px) + 12px); }
    .cs-du { padding: 16px 18px; border-bottom: 1px solid var(--hali-bordure); background: var(--hali-primaire-pale); border-radius: var(--hali-rayon) var(--hali-rayon) 0 0; }
    .cs-du span { color: var(--hali-discret); font-size: .85rem; font-weight: 600; }
    .cs-du b { display: block; color: var(--hali-encre); font-size: 2rem; font-weight: 700; letter-spacing: -.02em; font-variant-numeric: tabular-nums; }
    .cs-decompte { display: grid; gap: 6px; margin: 0; padding: 12px 18px; border-bottom: 1px solid #f3f4f6; font-size: .85rem; }
    .cs-decompte div { display: flex; justify-content: space-between; }
    .cs-decompte dt { color: var(--hali-discret); font-weight: 500; }
    .cs-decompte dd { margin: 0; font-weight: 600; font-variant-numeric: tabular-nums; }
    .cs-form { display: grid; gap: 12px; padding: 16px 18px; }
    .cs-form label { display: grid; gap: 5px; margin: 0; font-size: .82rem; }
    .cs-montant { position: relative; }
    .cs-montant input { min-height: 50px; padding-right: 60px; font-size: 1.3rem; font-weight: 700; font-variant-numeric: tabular-nums; }
    .cs-montant span { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); color: var(--hali-discret); font-weight: 600; }
    .cs-raccourcis { display: flex; flex-wrap: wrap; gap: 6px; }
    .cs-raccourcis button { min-height: 30px; padding: 0 10px; border: 1px solid var(--hali-bordure); border-radius: 999px; background: #fff; color: var(--hali-texte); font-size: .8rem; font-weight: 600; cursor: pointer; }
    .cs-raccourcis button:hover { border-color: var(--hali-primaire); color: var(--hali-primaire-fonce); background: var(--hali-primaire-pale); }
    .cs-modes { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; }
    .cs-modes label { margin: 0; }
    .cs-modes input { position: absolute; opacity: 0; pointer-events: none; }
    .cs-modes span { display: flex; align-items: center; justify-content: center; min-height: 38px; border: 1px solid var(--hali-bordure); border-radius: 8px; background: #fff; color: var(--hali-texte); font-size: .84rem; font-weight: 600; cursor: pointer; }
    .cs-modes input:checked + span { background: var(--hali-primaire-pale); border-color: var(--hali-primaire); color: var(--hali-primaire-fonce); }
    .cs-modes input:focus-visible + span { outline: 2px solid var(--hali-primaire); outline-offset: 2px; }
    .cs-apres { color: var(--hali-discret); font-size: .82rem; }
    .cs-apres b { color: var(--hali-encre); }
    .cs-valider { min-height: 48px; font-size: .95rem; }

    @media (max-width: 1199.98px) { .cs-grille { grid-template-columns: 1fr; } .cs-encaisser { position: static; } }
</style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">

    <header class="hl-entete">
        <div style="display:flex; align-items:center; gap:14px">
            <span class="hl-avatar" style="width:52px;height:52px;flex-basis:52px;border-radius:14px;font-size:1.05rem" aria-hidden="true">{{ $initiales }}</span>
            <div>
                <h1>{{ $patient->full_name }}</h1>
                <p>Caisse · {{ $lignes->count() }} facture{{ $lignes->count() > 1 ? 's' : '' }} à régler</p>
            </div>
        </div>
        <div class="hl-entete-actions">
            <a href="{{ route('caisse.index') }}" class="hl-bouton"><i class="fas fa-arrow-left" aria-hidden="true"></i> Caisse</a>
            @can('patient.view')
                <a href="{{ route('patient.show', $patient->id) }}" class="hl-bouton">Fiche patient</a>
            @endcan
        </div>
    </header>

    @if($lignes->isEmpty())
        <section class="hl-bloc">
            <div class="hl-vide">
                <i class="fas fa-check-circle" aria-hidden="true" style="color:#a7f3d0"></i>
                Ce patient n'a plus rien à régler.
            </div>
        </section>
    @else
        <div class="cs-grille">
            {{-- ------------------------------------------------ Factures --}}
            <div style="display:grid; gap:16px; min-width:0">
                @foreach($lignes as $l)
                    @php $t = $l['transaction']; $modifiable = auth()->user()?->can('payment.process'); @endphp
                    <section class="hl-bloc">
                        <h2 class="hl-bloc-titre" style="flex-wrap:wrap">
                            {{ $l['libelle'] }}
                            <small>{{ $l['date']->format('d/m/Y') }}@if($l['numero']) · {{ $l['numero'] }}@endif</small>
                        </h2>

                        @foreach($l['alertes'] as $alerte)
                            <p class="cs-alerte" style="margin:10px 18px 0"><i class="fas fa-exclamation-circle" aria-hidden="true"></i> {{ $alerte }}</p>
                        @endforeach

                        @if($l['lignes']->isNotEmpty())
                            <form method="POST" action="{{ route('caisse.remises', $t->id) }}">
                                @csrf
                                <div class="table-responsive">
                                    <table class="cs-table">
                                        <thead>
                                            <tr>
                                                <th>Acte</th>
                                                <th class="cs-n">Montant</th>
                                                <th class="cs-n">Assurance</th>
                                                <th class="cs-n">Remise</th>
                                                <th class="cs-n">Part patient</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($l['lignes'] as $item)
                                                <tr>
                                                    <td>
                                                        <span class="cs-libelle">{{ $item->description }}</span>
                                                        @if((int) $item->quantity > 1)<span class="cs-sous">× {{ (int) $item->quantity }}</span>@endif
                                                    </td>
                                                    <td class="cs-n">{{ $gnf($item->montantBrut()) }}</td>
                                                    <td class="cs-n cs-assurance">{{ (float) $item->insurance_covered_amount > 0 ? '− ' . $gnf($item->insurance_covered_amount) : '—' }}</td>
                                                    <td class="cs-n">
                                                        @if($modifiable && $item->partPatientAvantRemise() > 0)
                                                            <input type="number" class="form-control form-control-sm cs-remise"
                                                                   name="remises[{{ $item->coverage_type_type }}][{{ $item->coverage_type_id }}]"
                                                                   value="{{ (float) $item->discount > 0 ? rtrim(rtrim(number_format((float) $item->discount, 2, '.', ''), '0'), '.') : '' }}"
                                                                   min="0" max="{{ $item->partPatientAvantRemise() }}" step="any" inputmode="numeric"
                                                                   placeholder="0" aria-label="Remise sur {{ $item->description }}"
                                                                   title="Au plus {{ $gnf($item->partPatientAvantRemise()) }} GNF (part patient de la ligne)">
                                                        @else
                                                            {{ (float) $item->discount > 0 ? '− ' . $gnf($item->discount) : '—' }}
                                                        @endif
                                                    </td>
                                                    <td class="cs-n"><strong>{{ $gnf($item->patient_amount) }}</strong></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="cs-pied-facture">
                                    <span>Part patient <b>{{ $gnf($l['part_patient']) }}</b>@if($l['deja_paye'] > 0) · déjà payé {{ $gnf($l['deja_paye']) }}@endif · reste <b>{{ $gnf($l['reste_patient']) }} GNF</b></span>
                                    @if($modifiable)
                                        <span class="cs-actions-facture">
                                            <button type="submit" class="hl-bouton cs-appliquer" hidden>Appliquer les remises</button>
                                            <button type="submit" form="recalcul-{{ $t->id }}" class="hl-bouton" title="Si le patient a présenté une carte d'assurance après la facturation">Recalculer l'assurance</button>
                                        </span>
                                    @endif
                                </div>
                            </form>
                            @if($modifiable)
                                <form method="POST" action="{{ route('caisse.recalculer', $t->id) }}" id="recalcul-{{ $t->id }}"
                                      onsubmit="return confirm('Recalculer la prise en charge de cette facture selon les droits actuels du patient ?');">@csrf</form>
                            @endif
                        @else
                            <div class="cs-pied-facture">
                                <span>Total <b>{{ $gnf($l['total']) }}</b> · reste <b>{{ $gnf($l['reste_patient']) }} GNF</b></span>
                                <span class="cs-sous">Ancienne facture sans détail des actes.</span>
                            </div>
                        @endif
                    </section>
                @endforeach
            </div>

            {{-- ------------------------------------------------ Encaissement --}}
            <aside class="hl-bloc cs-encaisser">
                <div class="cs-du">
                    <span>Reste à payer par le patient</span>
                    <b>{{ $gnf($resteDu) }} <small style="font-size:1rem; color:var(--hali-discret)">GNF</small></b>
                </div>
                <dl class="cs-decompte">
                    <div><dt>Total des actes</dt><dd>{{ $gnf($totalActes) }}</dd></div>
                    @if($totalAssurance > 0)<div><dt>Pris en charge par l'assurance</dt><dd class="cs-assurance">− {{ $gnf($totalAssurance) }}</dd></div>@endif
                    @if($dejaPaye > 0)<div><dt>Déjà payé</dt><dd>− {{ $gnf($dejaPaye) }}</dd></div>@endif
                </dl>

                @can('payment.process')
                    <form method="POST" action="{{ route('caisse.encaisser', $patient->id) }}" class="cs-form" id="csForm">
                        @csrf
                        <label>Montant reçu
                            <span class="cs-montant">
                                <input type="number" name="montant" id="csMontant" class="form-control" min="1" max="{{ $valeurDu }}" step="any"
                                       inputmode="decimal" value="{{ old('montant', $valeurDu) }}" required>
                                <span>GNF</span>
                            </span>
                        </label>
                        <div class="cs-raccourcis" aria-label="Montants rapides">
                            <button type="button" data-montant="{{ $valeurDu }}">Tout ({{ $gnf($resteDu) }})</button>
                            @if($resteDu >= 20000)<button type="button" data-montant="{{ (int) floor($resteDu / 2) }}">Moitié</button>@endif
                        </div>

                        <div>
                            <span style="display:block; margin-bottom:5px; color:var(--hali-encre); font-size:.82rem; font-weight:600">Mode de paiement</span>
                            <div class="cs-modes" role="radiogroup" aria-label="Mode de paiement">
                                @foreach($modes as $valeur => $libelle)
                                    <label><input type="radio" name="source" value="{{ $valeur }}" @checked(old('source', 'CASH') === $valeur)><span>{{ $libelle }}</span></label>
                                @endforeach
                            </div>
                        </div>

                        <label>Note (facultatif)
                            <input type="text" name="description" class="form-control" maxlength="255" value="{{ old('description') }}" placeholder="Ex. acompte, reste la semaine prochaine">
                        </label>

                        <p class="cs-apres mb-0" id="csApres"></p>
                        <p class="hl-note hl-note-alerte mb-0" id="csRemiseEnCours" hidden style="font-size:.8rem">Une remise a été modifiée : cliquez « Appliquer les remises » avant d'encaisser.</p>

                        <button type="submit" class="hl-bouton hl-bouton-plein cs-valider" id="csValider">
                            <i class="fas fa-check" aria-hidden="true"></i> Encaisser
                        </button>
                    </form>
                @else
                    <p class="hl-note hl-note-info m-3">Vous pouvez consulter les montants, mais pas encaisser.</p>
                @endcan
            </aside>
        </div>
    @endif
</div></div>

<script>
(function () {
    // Le bouton de remise n'apparaît que si une remise a été modifiée : on ne
    // risque pas d'encaisser avec une remise saisie mais pas enregistrée.
    document.querySelectorAll('.cs-remise').forEach(function (champ) {
        var initial = champ.value;
        champ.addEventListener('input', function () {
            var form = champ.form, change = false;
            form.querySelectorAll('.cs-remise').forEach(function (c) { if (c.value !== c.defaultValue) change = true; });
            form.querySelector('.cs-appliquer').hidden = !change;
            document.getElementById('csValider') && (document.getElementById('csValider').disabled = change);
            var avis = document.getElementById('csRemiseEnCours');
            if (avis) avis.hidden = !change;
        });
    });
})();

(function () {
    var champ = document.getElementById('csMontant');
    if (!champ) return;
    var du = {{ (float) $resteDu }};
    var apres = document.getElementById('csApres');
    var bouton = document.getElementById('csValider');
    var fmt = function (n) { return new Intl.NumberFormat('fr-FR').format(Math.round(n)); };

    function maj() {
        var m = parseFloat(champ.value) || 0;
        if (m <= 0) { apres.innerHTML = ''; }
        else if (m > du + 0.01) { apres.innerHTML = '<span style="color:var(--hali-danger)">Plus que le reste dû : encaissez ' + fmt(du) + ' GNF et rendez la monnaie.</span>'; }
        else if (du - m >= 1) { apres.innerHTML = 'Il restera <b>' + fmt(du - m) + ' GNF</b> à payer.'; }
        else { apres.innerHTML = 'Le patient sera <b>à jour</b>.'; }
        bouton.innerHTML = '<i class="fas fa-check" aria-hidden="true"></i> Encaisser ' + (m > 0 ? fmt(Math.min(m, du)) + ' GNF' : '');
    }
    champ.addEventListener('input', maj);
    document.querySelectorAll('[data-montant]').forEach(function (b) {
        b.addEventListener('click', function () { champ.value = b.dataset.montant; maj(); champ.focus(); });
    });
    // Un seul envoi, même si la connexion traîne.
    document.getElementById('csForm').addEventListener('submit', function () { bouton.disabled = true; bouton.style.opacity = '.7'; });
    maj();
})();
</script>
@endsection
