@extends('layouts.backend')

@php
    $gnf = fn ($m) => number_format((float) $m, 0, ',', ' ');
    $totalDu = (float) $lignes->sum('reste_du');
    $totalEcarts = (float) $lignes->sum('ecarts');
    $totalAEnvoyer = (int) $lignes->sum('a_envoyer');
    $plusAncienne = $lignes->pluck('plus_ancienne')->filter()->min();
    $age = fn ($date) => $date ? (int) floor(abs($date->diffInDays(today()))) : null;
    // Délai de paiement : au-delà de 60 jours on relance, au-delà de 90 c'est un retard sérieux.
    $tonAge = fn (?int $jours) => $jours === null ? 'hl-s-neutre' : ($jours > 90 ? 'hl-s-danger' : ($jours > 60 ? 'hl-s-alerte' : 'hl-s-succes'));
@endphp

@section('style')
<style>
    .cr-ligne { display: grid; grid-template-columns: minmax(180px, 1.4fr) minmax(170px, 1.2fr) 120px 120px 130px auto; align-items: center; gap: 16px; padding: 14px 18px; border-top: 1px solid #f3f4f6; color: inherit; text-decoration: none; }
    .cr-ligne:first-of-type { border-top: 0; }
    .cr-ligne:hover { background: var(--hali-primaire-pale); color: inherit; text-decoration: none; }
    .cr-organisme { display: flex; align-items: center; gap: 12px; min-width: 0; }
    .cr-initiale { display: grid; place-items: center; width: 40px; height: 40px; flex: none; border-radius: 11px; background: var(--hali-primaire-pale); color: var(--hali-primaire-fonce); font-weight: 800; }
    .cr-nom { display: block; color: var(--hali-encre); font-weight: 700; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .cr-sous { display: block; color: var(--hali-discret); font-size: .78rem; }
    .cr-du { display: grid; min-width: 0; }
    .cr-montant { display: block; color: var(--hali-encre); font-size: 1.05rem; font-weight: 750; font-variant-numeric: tabular-nums; }
    .cr-part { display: block; width: 100%; max-width: 220px; height: 6px; margin-top: 6px; border-radius: 999px; background: #eef2f2; overflow: hidden; }
    .cr-part span { display: block; height: 100%; border-radius: inherit; background: var(--hali-primaire); }
    .cr-chiffre { color: var(--hali-discret); font-weight: 650; font-variant-numeric: tabular-nums; }
    .cr-chiffre.est-rouge { color: var(--hali-danger); }
    .cr-ligne > span:nth-child(5) .hl-statut { margin-bottom: 2px; }
    .cr-entete { display: grid; grid-template-columns: minmax(180px, 1.4fr) minmax(170px, 1.2fr) 120px 120px 130px auto; gap: 16px; padding: 10px 18px; border-bottom: 1px solid var(--hali-bordure); background: #fafbfc; color: var(--hali-discret); font-size: .76rem; font-weight: 600; }
    .cr-fleche { color: #cbd5e1; font-size: 1.2rem; }
    @media (max-width: 991.98px) {
        .cr-entete { display: none; }
        .cr-ligne { grid-template-columns: 1fr 1fr; }
        .cr-organisme, .cr-du { grid-column: 1 / -1; }
        .cr-fleche { display: none; }
    }
</style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">
    @include('assurance.partials.entete', [
        'titre' => 'Créances assurance',
        'sousTitre' => "Ce que chaque organisme payeur doit encore à la clinique. Ouvrez un organisme pour préparer un bordereau, saisir les réponses ou enregistrer un règlement.",
        'fil' => [route('assurance.creances.index') => 'Créances'],
    ])

    <div class="hl-kpis">
        <div class="hl-bloc hl-kpi"><span class="hl-kpi-libelle">Reste dû par les assureurs</span><span class="hl-kpi-valeur">{{ $gnf($totalDu) }} <small>GNF</small></span><span class="hl-kpi-detail">{{ $lignes->count() }} organisme{{ $lignes->count() > 1 ? 's' : '' }}</span></div>
        <div class="hl-bloc hl-kpi"><span class="hl-kpi-libelle">Réclamations à envoyer</span><span class="hl-kpi-valeur" style="color:{{ $totalAEnvoyer ? 'var(--hali-alerte)' : 'inherit' }}">{{ $totalAEnvoyer }}</span><span class="hl-kpi-detail">{{ $totalAEnvoyer ? 'à mettre dans un bordereau' : 'rien en attente' }}</span></div>
        <div class="hl-bloc hl-kpi"><span class="hl-kpi-libelle">Écarts à régler</span><span class="hl-kpi-valeur" style="color:{{ $totalEcarts > 0 ? 'var(--hali-danger)' : 'inherit' }}">{{ $gnf($totalEcarts) }} <small>GNF</small></span><span class="hl-kpi-detail">refusés, à transférer au patient ou à contester</span></div>
        <div class="hl-bloc hl-kpi"><span class="hl-kpi-libelle">Plus ancienne créance</span><span class="hl-kpi-valeur">{{ $age($plusAncienne) ?? '—' }} <small>{{ $plusAncienne ? 'jours' : '' }}</small></span>@if($plusAncienne)<span class="hl-kpi-detail">depuis le {{ $plusAncienne->format('d/m/Y') }}</span>@endif</div>
    </div>

    <section class="hl-bloc">
        @if($lignes->isEmpty())
            <div class="hl-vide"><i class="fas fa-check-circle" aria-hidden="true" style="color:var(--hali-succes)"></i>Aucune créance ouverte : les assureurs ne doivent rien à la clinique.</div>
        @else
            <div class="cr-entete" aria-hidden="true"><span>Organisme</span><span>Reste dû</span><span>À envoyer</span><span>Écarts</span><span>Ancienneté</span><span></span></div>
            @foreach($lignes as $l)
                @php $jours = $age($l['plus_ancienne']); @endphp
                <a href="{{ route('assurance.creances.show', $l['organisme']) }}" class="cr-ligne">
                    <span class="cr-organisme">
                        <span class="cr-initiale" aria-hidden="true">{{ mb_strtoupper(mb_substr($l['organisme']->name, 0, 1)) }}</span>
                        <span style="min-width:0"><span class="cr-nom">{{ $l['organisme']->name }}</span>
                            <span class="cr-sous">{{ $l['nb_ouvertes'] }} réclamation{{ $l['nb_ouvertes'] > 1 ? 's' : '' }} ouverte{{ $l['nb_ouvertes'] > 1 ? 's' : '' }}</span></span>
                    </span>
                    <span class="cr-du">
                        <span class="cr-montant">{{ $gnf($l['reste_du']) }} <small style="color:var(--hali-discret); font-size:.72rem">GNF</small></span>
                        <span class="cr-part" title="{{ $totalDu > 0 ? round($l['reste_du'] * 100 / $totalDu) : 0 }} % du total"><span style="width: {{ $totalDu > 0 ? max(2, round($l['reste_du'] * 100 / $totalDu)) : 0 }}%"></span></span>
                    </span>
                    <span>@if($l['a_envoyer'])<span class="hl-statut hl-s-alerte">{{ $l['a_envoyer'] }} à envoyer</span>@else<span class="cr-sous">—</span>@endif</span>
                    <span class="cr-chiffre {{ $l['ecarts'] > 0 ? 'est-rouge' : '' }}">{{ $l['ecarts'] > 0 ? $gnf($l['ecarts']) : '—' }}</span>
                    <span>@if($jours !== null)<span class="hl-statut {{ $tonAge($jours) }}">{{ $jours }} j</span><span class="cr-sous">depuis le {{ $l['plus_ancienne']->format('d/m/Y') }}</span>@else<span class="cr-sous">—</span>@endif</span>
                    <span class="cr-fleche" aria-hidden="true">›</span>
                </a>
            @endforeach
        @endif
    </section>

    @if($lignes->isNotEmpty())
        <p class="hl-note hl-note-info mt-3"><i class="fas fa-info-circle" aria-hidden="true"></i> <span>Ancienneté : en vert jusqu'à 60 jours, en orange au-delà (relancer l'assureur), en rouge après 90 jours.</span></p>
    @endif
</div></div>
@endsection
