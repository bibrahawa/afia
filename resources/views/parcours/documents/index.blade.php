@extends('layouts.backend')

@php
    $actifs = $documents->reject(fn ($d) => $d->annule);
    $annules = $documents->filter(fn ($d) => $d->annule);
    $noms = preg_split('/\s+/', trim((string) $patient->full_name));
    $initiales = mb_strtoupper(mb_substr($noms[0] ?? '', 0, 1) . mb_substr(end($noms) ?: '', 0, 1));

    // Arrêt de travail ou certificat : une icône suffit à les distinguer.
    $icone = fn ($d) => str_contains((string) $d->type->value, 'arret') ? 'fa-bed' : (str_contains((string) $d->type->value, 'grossesse') ? 'fa-baby' : 'fa-file-medical');
@endphp

@section('style')
<style>
    .doc-liste { margin: 0; padding: 0; list-style: none; }
    .doc { display: grid; grid-template-columns: 42px minmax(0, 1fr) auto; align-items: center; gap: 14px; padding: 14px 18px; border-top: 1px solid #f3f4f6; }
    .doc:first-child { border-top: 0; }
    .doc-icone { width: 42px; height: 42px; display: grid; place-items: center; border-radius: 11px; background: var(--hali-primaire-pale); color: var(--hali-primaire); }
    .doc-titre { display: flex; flex-wrap: wrap; align-items: center; gap: 6px 10px; color: var(--hali-encre); font-weight: 650; }
    .doc-numero { color: var(--hali-discret); font-size: .8rem; font-weight: 500; font-variant-numeric: tabular-nums; }
    .doc-meta { display: flex; flex-wrap: wrap; gap: 4px 14px; margin-top: 3px; color: var(--hali-discret); font-size: .84rem; }
    .doc-meta b { color: var(--hali-texte); font-weight: 600; }
    .doc-actions { display: flex; align-items: center; gap: 8px; }

    .doc.est-annule .doc-icone { background: #f3f4f6; color: #9ca3af; }
    .doc.est-annule .doc-titre > span:first-child { color: var(--hali-discret); text-decoration: line-through; }
    .doc-motif { margin-top: 4px; color: var(--hali-danger); font-size: .82rem; }

    .doc-annuler { position: relative; }
    .doc-annuler > summary { list-style: none; }
    .doc-annuler > summary::-webkit-details-marker { display: none; }
    .doc-annuler-menu {
        position: absolute; right: 0; top: calc(100% + 6px); z-index: 30; width: 290px; padding: 12px;
        border: 1px solid var(--hali-bordure); border-radius: 10px; background: #fff; box-shadow: var(--hali-ombre-forte);
    }
    .doc-annuler-menu p { margin: 0 0 8px; color: var(--hali-discret); font-size: .8rem; }
    .doc-bouton-risque { border-color: #fecaca; color: var(--hali-danger); }
    .doc-bouton-risque:hover { background: var(--hali-danger-pale); border-color: var(--hali-danger); color: var(--hali-danger); }

    @media (max-width: 767.98px) {
        .doc { grid-template-columns: 42px minmax(0, 1fr); }
        .doc-actions { grid-column: 1 / -1; }
    }
</style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">

    <header class="hl-entete">
        <div style="display:flex; align-items:center; gap:14px">
            <span class="hl-avatar" style="width:52px;height:52px;flex-basis:52px;border-radius:14px;font-size:1.05rem" aria-hidden="true">{{ $initiales }}</span>
            <div>
                <h1>{{ $patient->full_name }}</h1>
                <p>Certificats et arrêts de travail · {{ $actifs->count() }} valide{{ $actifs->count() > 1 ? 's' : '' }}@if($annules->isNotEmpty()) · {{ $annules->count() }} annulé{{ $annules->count() > 1 ? 's' : '' }}@endif</p>
            </div>
        </div>
        <div class="hl-entete-actions">
            @can('parcours.dossier')
                <a href="{{ route('parcours.dossier.show', $patient->id) }}" class="hl-bouton"><i class="fas fa-folder-open" aria-hidden="true"></i> Dossier</a>
            @endcan
        </div>
    </header>

    <section class="hl-bloc">
        @if($documents->isEmpty())
            <div class="hl-vide">
                <i class="fas fa-file-medical" aria-hidden="true"></i>
                Aucun document établi pour ce patient.<br>
                Un certificat ou un arrêt de travail se rédige depuis l'écran de consultation : Autres actions › Certificat ou arrêt de travail.
            </div>
        @else
            <ol class="doc-liste">
                @foreach($documents as $d)
                    <li class="doc {{ $d->annule ? 'est-annule' : '' }}">
                        <span class="doc-icone" aria-hidden="true"><i class="fas {{ $icone($d) }}"></i></span>

                        <div style="min-width:0">
                            <div class="doc-titre">
                                <span>{{ $d->type->libelle() }}</span>
                                <span class="doc-numero">N° {{ $d->numero }}</span>
                                @if($d->annule)<span class="hl-statut hl-s-danger">Annulé</span>@endif
                            </div>
                            <div class="doc-meta">
                                <span>Établi le <b>{{ $d->created_at->format('d/m/Y') }}</b></span>
                                @if($d->date_debut)
                                    <span>Du <b>{{ $d->date_debut->format('d/m/Y') }}</b> au <b>{{ $d->date_fin?->format('d/m/Y') }}</b> · {{ $d->jours }} jour{{ $d->jours > 1 ? 's' : '' }}</span>
                                @endif
                                <span>{{ $d->medecin ? $d->medecin->nom_affiche : '—' }}</span>
                            </div>
                            @if($d->annule && $d->motif_annulation)
                                <div class="doc-motif">Motif de l'annulation : {{ $d->motif_annulation }}</div>
                            @endif
                        </div>

                        <div class="doc-actions">
                            <a href="{{ route('parcours.documents.imprimer', $d) }}" target="_blank" class="hl-bouton">
                                <i class="fa fa-print" aria-hidden="true"></i> {{ $d->annule ? 'Voir' : 'Réimprimer' }}
                            </a>
                            @can('parcours.document')
                                @unless($d->annule)
                                    <details class="doc-annuler">
                                        <summary class="hl-bouton doc-bouton-risque">Annuler</summary>
                                        <form method="POST" action="{{ route('parcours.documents.annuler', $d) }}" class="doc-annuler-menu">@csrf
                                            <p>Le document reste au dossier, barré. Il ne pourra plus être réimprimé comme valide.</p>
                                            <input name="motif_annulation" class="form-control form-control-sm mb-2" maxlength="255" placeholder="Motif de l'annulation" required>
                                            <button class="hl-bouton doc-bouton-risque w-100">Confirmer l'annulation</button>
                                        </form>
                                    </details>
                                @endunless
                            @endcan
                        </div>
                    </li>
                @endforeach
            </ol>
        @endif
    </section>
</div></div>

<script>
// Un seul formulaire d'annulation ouvert, fermé par un clic ailleurs.
document.addEventListener('click', function (e) {
    document.querySelectorAll('details.doc-annuler[open]').forEach(function (menu) {
        if (!menu.contains(e.target)) menu.removeAttribute('open');
    });
});
</script>
@endsection
