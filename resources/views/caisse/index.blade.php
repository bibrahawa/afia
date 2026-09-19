@extends('layouts.backend')

@php
    $gnf = fn ($v) => number_format((float) $v, 0, ',', ' ');
    $initiales = fn ($p) => mb_strtoupper(mb_substr((string) $p->first_name, 0, 1) . mb_substr((string) $p->last_name, 0, 1)) ?: 'P';
    $plusGros = $patients->max('reste');
@endphp

@section('style')
<style>
    .ca-outils { position: relative; padding: 14px 18px; border-bottom: 1px solid var(--hali-bordure); }
    .ca-outils i { position: absolute; left: 32px; top: 50%; transform: translateY(-50%); color: #9ca3af; }
    .ca-outils input { width: 100%; min-height: 42px; padding-left: 40px; }
    .ca-entetes, .ca-ligne { display: grid; align-items: center; gap: 14px; grid-template-columns: minmax(220px, 1.6fr) minmax(130px, .9fr) minmax(120px, .8fr) minmax(140px, .9fr) auto; padding: 12px 18px; }
    .ca-entetes { padding-top: 10px; padding-bottom: 10px; background: #fafbfc; border-bottom: 1px solid var(--hali-bordure); color: var(--hali-discret); font-size: .78rem; font-weight: 600; }
    .ca-ligne { position: relative; border-top: 1px solid #f3f4f6; }
    .ca-ligne:first-of-type { border-top: 0; }
    .ca-ligne:hover { background: var(--hali-primaire-pale); }
    .ca-nom { display: flex; align-items: center; gap: 12px; min-width: 0; }
    .ca-nom a { color: var(--hali-encre); font-weight: 650; text-decoration: none; }
    .ca-nom a::after { content: ""; position: absolute; inset: 0; }
    .ca-sous { display: block; color: var(--hali-discret); font-size: .8rem; }
    .ca-montant { color: var(--hali-encre); font-size: 1.05rem; font-weight: 700; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .ca-montant small { color: var(--hali-discret); font-size: .75rem; font-weight: 600; }
    .ca-actions { position: relative; z-index: 2; display: flex; justify-content: flex-end; }
    .ca-etiquette { display: none; }
    @media (max-width: 991.98px) {
        .ca-entetes { display: none; }
        .ca-ligne { grid-template-columns: 1fr 1fr; gap: 8px 16px; }
        .ca-nom { grid-column: 1 / -1; }
        .ca-actions { grid-column: 1 / -1; justify-content: flex-start; }
        .ca-etiquette { display: block; color: var(--hali-discret); font-size: .75rem; }
    }
</style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">

    <header class="hl-entete">
        <div>
            <h1>Caisse</h1>
            <p>Parts patient à encaisser. La part des assurances est déjà déduite, telle que calculée à la facturation.</p>
        </div>
    </header>

    <div class="hl-kpis">
        <div class="hl-bloc hl-kpi {{ $totalDu > 0 ? 'est-alerte' : '' }}">
            <span class="hl-kpi-libelle">Total à encaisser</span>
            <span class="hl-kpi-valeur">{{ $gnf($totalDu) }} <small>GNF</small></span>
            <span class="hl-kpi-detail">parts patient uniquement</span>
        </div>
        <div class="hl-bloc hl-kpi">
            <span class="hl-kpi-libelle">Patients concernés</span>
            <span class="hl-kpi-valeur">{{ $patients->count() }}</span>
            <span class="hl-kpi-detail">{{ $patients->where('assure', true)->count() }} avec une assurance</span>
        </div>
        <div class="hl-bloc hl-kpi">
            <span class="hl-kpi-libelle">Plus gros reste dû</span>
            <span class="hl-kpi-valeur">{{ $plusGros ? $gnf($plusGros) : '—' }} @if($plusGros)<small>GNF</small>@endif</span>
        </div>
    </div>

    <section class="hl-bloc">
        @if($patients->isEmpty())
            <div class="hl-vide">
                <i class="fas fa-check-circle" aria-hidden="true" style="color:#a7f3d0"></i>
                Rien à encaisser : toutes les parts patient sont réglées.
            </div>
        @else
            <div class="ca-outils">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" id="caRecherche" class="form-control" placeholder="Rechercher un patient par nom ou téléphone…" autocomplete="off" aria-label="Rechercher un patient">
            </div>

            <div class="ca-entetes" aria-hidden="true">
                <span>Patient</span><span>Factures ouvertes</span><span>Assurance</span><span>Reste à payer</span><span></span>
            </div>

            <div id="caListe">
                @foreach($patients as $ligne)
                    @php $p = $ligne['patient']; $tel = $p->telephone ?? null; @endphp
                    <div class="ca-ligne" data-recherche="{{ mb_strtolower($p->full_name . ' ' . $tel) }}">
                        <div class="ca-nom">
                            <span class="hl-avatar" aria-hidden="true">{{ $initiales($p) }}</span>
                            <div style="min-width:0">
                                <a href="{{ route('caisse.show', $p->id) }}">{{ $p->full_name }}</a>
                                <span class="ca-sous">{{ $tel ?: 'Dossier n° ' . str_pad($p->id, 4, '0', STR_PAD_LEFT) }}</span>
                            </div>
                        </div>
                        <div>
                            <span class="ca-etiquette">Factures ouvertes</span>
                            {{ $ligne['pieces'] }} facture{{ $ligne['pieces'] > 1 ? 's' : '' }}
                            <span class="ca-sous">depuis le {{ $ligne['depuis']->format('d/m/Y') }}</span>
                        </div>
                        <div>
                            <span class="ca-etiquette">Assurance</span>
                            @if($ligne['assure'])<span class="hl-statut hl-s-succes">Part déduite</span>@else<span class="ca-sous">Aucune</span>@endif
                        </div>
                        <div>
                            <span class="ca-etiquette">Reste à payer</span>
                            <span class="ca-montant">{{ $gnf($ligne['reste']) }} <small>GNF</small></span>
                        </div>
                        <div class="ca-actions">
                            <a href="{{ route('caisse.show', $p->id) }}" class="hl-bouton hl-bouton-plein"><i class="fas fa-money-bill" aria-hidden="true"></i> Encaisser</a>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="hl-vide" id="caAucun" hidden>Aucun patient ne correspond.</div>
        @endif
    </section>
</div></div>

<script>
(function () {
    var champ = document.getElementById('caRecherche');
    if (!champ) return;
    champ.addEventListener('input', function () {
        var terme = champ.value.trim().toLowerCase(), visibles = 0;
        document.querySelectorAll('.ca-ligne').forEach(function (l) {
            var ok = !terme || l.dataset.recherche.indexOf(terme) !== -1;
            l.hidden = !ok; if (ok) visibles++;
        });
        document.getElementById('caAucun').hidden = visibles > 0;
    });
})();
</script>
@endsection
