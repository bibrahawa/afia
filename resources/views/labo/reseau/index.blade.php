@extends('layouts.backend')

@section('style')
    @include('labo.partials.styles')
    <style>
        .rs-filtres { display: flex; flex-wrap: wrap; gap: 10px; padding: 14px 18px; border-bottom: 1px solid var(--hali-bordure); }
        .rs-filtres select { width: auto; min-width: 200px; min-height: 40px; }
        .rs-filtres .rs-recherche { position: relative; flex: 1 1 220px; }
        .rs-filtres .rs-recherche i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #9ca3af; }
        .rs-filtres .rs-recherche input { width: 100%; min-height: 40px; padding-left: 40px; }
        .rs-proposition { display: flex; flex-wrap: wrap; align-items: center; gap: 10px 14px; margin-bottom: 12px; padding: 14px 18px; border: 1px solid #bfdbfe; border-radius: var(--hali-rayon); background: var(--hali-info-pale); }
        .rs-proposition form { display: flex; gap: 6px; margin: 0; }
        tr.est-nouveau > td { background: #f0fdf4; }
    </style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; gap:12px">
        <div style="flex:1">@include('labo.partials.entete', ['titre' => 'Analyses envoyées', 'fil' => [route('labo.reseau.index') => 'Analyses envoyées'], 'sousTitre' => $nonVus > 0 ? $nonVus . ' résultat' . ($nonVus > 1 ? 's' : '') . ' pas encore consulté' . ($nonVus > 1 ? 's' : '') : 'Demandes confiées aux laboratoires partenaires.'])</div>
        @can('labo.reseau.demander')
            @if($partenaires->isNotEmpty())
                <a href="{{ route('labo.reseau.create') }}" class="hl-bouton hl-bouton-plein" style="margin-bottom:18px"><i class="fa fa-paper-plane" aria-hidden="true"></i> Envoyer une demande</a>
            @endif
        @endcan
    </div>

    @foreach($propositions as $proposition)
        <div class="rs-proposition">
            <span><i class="fas fa-handshake" aria-hidden="true" style="color:var(--hali-info)"></i> <strong>{{ $proposition->laboratoire?->nom }}</strong> vous propose un partenariat
                @if($proposition->remise_pourcentage > 0)avec une remise de {{ rtrim(rtrim(number_format((float) $proposition->remise_pourcentage, 2, ',', ''), '0'), ',') }} %@endif.</span>
            <span style="margin-left:auto; display:flex; flex-wrap:wrap; gap:8px">
                <form method="POST" action="{{ route('labo.reseau.propositions.accepter', $proposition->id) }}">@csrf
                    <button class="hl-bouton hl-bouton-plein lb-petit">Accepter</button></form>
                <form method="POST" action="{{ route('labo.reseau.propositions.refuser', $proposition->id) }}">@csrf
                    <input name="motif_refus" class="form-control form-control-sm" maxlength="255" placeholder="Motif (facultatif)">
                    <button class="hl-bouton lb-petit lb-risque">Refuser</button></form>
            </span>
        </div>
    @endforeach

    @if($partenaires->isEmpty())
        <p class="lb-alerte lb-alerte-info"><i class="fas fa-info-circle mt-1" aria-hidden="true"></i><span>Aucun laboratoire partenaire pour l'instant. C'est le laboratoire qui ouvre le partenariat, depuis son écran « Cliniques partenaires ».</span></p>
    @endif

    <section class="hl-bloc">
        <form method="GET" class="rs-filtres" id="rsFiltres">
            <label class="rs-recherche mb-0">
                <span class="sr-only visually-hidden">Patient</span>
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" name="patient" value="{{ $filtres['patient'] ?? '' }}" class="form-control" placeholder="Patient… puis Entrée">
            </label>
            <select name="partenariat_id" class="form-control" onchange="this.form.submit()" aria-label="Laboratoire">
                <option value="">Tous les laboratoires</option>
                @foreach($partenaires as $p)<option value="{{ $p->id }}" @selected(($filtres['partenariat_id'] ?? null) == $p->id)>{{ $p->laboratoire?->nom }}</option>@endforeach
            </select>
            <select name="statut" class="form-control" onchange="this.form.submit()" aria-label="Statut">
                <option value="">Tous les statuts</option>
                @foreach($statuts as $statut)<option value="{{ $statut->value }}" @selected(($filtres['statut'] ?? null) === $statut->value)>{{ $statut->libelle() }}</option>@endforeach
            </select>
        </form>

        @if($demandes->isEmpty())
            <div class="hl-vide"><i class="fas fa-paper-plane" aria-hidden="true"></i>Aucune demande envoyée.</div>
        @else
            <div class="table-responsive">
                <table class="lb-table">
                    <thead><tr><th>Demande</th><th>Patient</th><th>Laboratoire</th><th>Examens</th><th>Statut</th><th></th></tr></thead>
                    <tbody>
                    @foreach($demandes as $d)
                        @php($nouveau = $d->premiere_publication_le && ! $d->resultat_vu_le)
                        <tr class="{{ $nouveau ? 'est-nouveau' : '' }} {{ $d->urgence ? 'labo-urgent' : '' }}">
                            <td><a href="{{ route('labo.reseau.show', $d->id) }}" class="lb-fort">{{ $d->numero }}</a>
                                @if($d->urgence) <span class="labo-pastille-urgent"><i class="fas fa-bolt" aria-hidden="true"></i> Urgent</span>@endif
                                <span class="lb-sous">envoyée le {{ $d->created_at->format('d/m/Y à H:i') }}</span></td>
                            <td>{{ $d->patient?->full_name }}</td>
                            <td style="font-size:.84rem">{{ $d->etablissement?->nom }}</td>
                            <td style="font-size:.84rem">{{ $d->examens->pluck('examen_nom')->take(3)->join(', ') }}@if($d->examens->count() > 3) +{{ $d->examens->count() - 3 }}@endif</td>
                            <td><span class="hl-statut {{ $d->statut->value === 'publiee' ? 'hl-s-succes' : ($d->statut->value === 'annulee' ? 'hl-s-neutre' : 'hl-s-info') }}">{{ $d->statut->libelle() }}</span>
                                @if($nouveau)<span class="lb-sous" style="color:var(--hali-succes); font-weight:700">nouveau résultat</span>@endif</td>
                            <td class="lb-actions"><a href="{{ route('labo.reseau.show', $d->id) }}" class="hl-bouton lb-petit {{ $nouveau ? 'hl-bouton-plein' : '' }}">{{ $nouveau ? 'Voir le résultat' : 'Ouvrir' }}</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if($demandes->hasPages())
                <div style="padding:14px 18px; border-top:1px solid var(--hali-bordure); display:flex; justify-content:center">{{ $demandes->withQueryString()->links() }}</div>
            @endif
        @endif
    </section>
</div></div>
@endsection
