@extends('layouts.backend')

@section('style') @include('labo.partials.styles') @endsection

@section('content')
<div class="container"><div class="page-inner hl">
    @include('labo.partials.entete', ['titre' => 'Demande ' . $demande->numero, 'fil' => [route('labo.reseau.index') => 'Analyses envoyées', 0 => $demande->numero]])

    <div class="lb-grille">
        <section class="hl-bloc">
            <h2 class="hl-bloc-titre">Demande <span class="hl-statut {{ $demande->statut->value === 'publiee' ? 'hl-s-succes' : 'hl-s-info' }}" style="margin-left:auto">{{ $demande->statut->libelle() }}</span></h2>
            <dl class="lb-infos">
                <div><dt>Patient</dt><dd>{{ $demande->patient?->full_name }}</dd></div>
                <div><dt>Laboratoire</dt><dd>{{ $demande->etablissement?->nom }}</dd></div>
                <div><dt>Envoyée</dt><dd>le {{ $demande->created_at->format('d/m/Y à H:i') }}</dd></div>
                <div><dt>Facturation</dt><dd>{{ $demande->mode_facturation->libelle() }}</dd></div>
                <div><dt>Prescripteur</dt><dd>{{ $demande->prescripteur_externe ?? '—' }}</dd></div>
                @if($demande->renseignements_cliniques)<div><dt>Renseignements</dt><dd style="font-weight:500">{{ $demande->renseignements_cliniques }}</dd></div>@endif
            </dl>
            <div class="lb-form" style="padding-top:0">
                <a href="{{ route('labo.reseau.bon', $demande->id) }}" target="_blank" class="hl-bouton"><i class="fa fa-print" aria-hidden="true"></i> Bon d'analyses pour le patient</a>
                @can('labo.reseau.demander')
                    @if($demande->statut->value === 'enregistree')
                        <details class="hl-repli">
                            <summary style="font-size:.84rem; font-weight:600; color:var(--hali-danger)">Annuler la demande</summary>
                            <form method="POST" action="{{ route('labo.reseau.annuler', $demande->id) }}" onsubmit="return confirm('Annuler cette demande auprès du laboratoire ?');" style="display:grid; gap:8px; margin-top:8px">@csrf
                                <input name="motif" class="form-control form-control-sm" maxlength="255" placeholder="Motif (facultatif)">
                                <button class="hl-bouton lb-risque">Confirmer l'annulation</button>
                            </form>
                        </details>
                    @endif
                @endcan
            </div>
        </section>

        <section class="hl-bloc">
            <h2 class="hl-bloc-titre">Examens <small>{{ $demande->examens->count() }}</small>
                @if($compteRendu)
                    <a href="{{ route('labo.reseau.compte-rendu', $demande->id) }}" target="_blank" class="hl-bouton hl-bouton-plein lb-petit" style="margin-left:auto">
                        <i class="fa fa-file-pdf" aria-hidden="true"></i> Compte rendu (v{{ $compteRendu->version }})</a>
                @endif
            </h2>
            <table class="lb-table">
                <tbody>
                @foreach($demande->examens as $ligne)
                    <tr><td class="lb-fort">{{ $ligne->examen_nom }}</td><td class="lb-actions"><span class="hl-statut hl-s-neutre">{{ $ligne->statut->libelle() }}</span></td></tr>
                @endforeach
                </tbody>
            </table>
            @unless($compteRendu)
                <p class="lb-aide" style="padding:12px 18px; border-top:1px solid var(--hali-bordure); margin:0">Le compte rendu apparaîtra ici dès sa publication par le laboratoire.</p>
            @endunless
        </section>
    </div>
</div></div>
@endsection
