@extends('layouts.backend')

@php $gnf = fn ($m) => number_format((float) $m, 0, ',', ' '); @endphp

@section('style')
    @include('labo.partials.styles')
    <style>
        .rl-barre { display: flex; flex-wrap: wrap; align-items: center; gap: 10px 16px; padding: 14px 18px; border-bottom: 1px solid var(--hali-bordure); }
        .rl-barre form { margin: 0; }
        .rl-barre .lb-droite { margin-left: auto; display: flex; gap: 8px; }
        .rl-total td { background: #fafbfc; font-weight: 700; color: var(--hali-encre); border-top: 2px solid var(--hali-bordure) !important; }
    </style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">
    @include('labo.partials.entete', ['titre' => 'Relevé ' . $releve->numero, 'fil' => [route('labo.creances.index') => 'Créances', route('labo.creances.show', $releve->partenariat) => $releve->partenariat->clinique?->nom, 0 => $releve->numero]])

    <div class="hl-kpis">
        <div class="hl-bloc hl-kpi"><span class="hl-kpi-libelle">Montant du relevé</span><span class="hl-kpi-valeur">{{ $gnf($releve->montant_total) }} <small>GNF</small></span></div>
        <div class="hl-bloc hl-kpi"><span class="hl-kpi-libelle">Réglé</span><span class="hl-kpi-valeur">{{ $gnf($releve->montantRegle()) }} <small>GNF</small></span></div>
        <div class="hl-bloc hl-kpi {{ $releve->enRetard() ? 'est-danger' : ($releve->resteDu() > 0 ? 'est-alerte' : '') }}"><span class="hl-kpi-libelle">Reste dû</span><span class="hl-kpi-valeur">{{ $gnf($releve->resteDu()) }} <small>GNF</small></span>
            @if($releve->echeance)<span class="hl-kpi-detail">échéance {{ $releve->echeance->format('d/m/Y') }}</span>@endif</div>
    </div>

    <section class="hl-bloc">
        <div class="rl-barre">
            <div>
                <span class="lb-fort">{{ $releve->partenariat->clinique?->nom }}</span>
                <span class="hl-statut {{ $releve->statut === 'solde' ? 'hl-s-succes' : ($releve->statut === 'envoye' ? 'hl-s-info' : 'hl-s-neutre') }}">{{ ucfirst($releve->statut) }}</span>
                <span class="lb-sous">Période du {{ $releve->periode_debut->format('d/m/Y') }} au {{ $releve->periode_fin->format('d/m/Y') }}</span>
            </div>
            <div class="lb-droite">
                <a href="{{ route('labo.releves.imprimer', $releve) }}" target="_blank" class="hl-bouton"><i class="fa fa-print" aria-hidden="true"></i> Imprimer</a>
                @if(! $releve->estEnvoye())
                    <form method="POST" action="{{ route('labo.releves.envoyer', $releve) }}" onsubmit="return confirm('Marquer ce relevé envoyé ? Les montants seront figés.');">@csrf
                        <button class="hl-bouton hl-bouton-plein">Marquer envoyé</button></form>
                @elseif($releve->montantRegle() < 1)
                    <form method="POST" action="{{ route('labo.releves.rouvrir', $releve) }}" onsubmit="return confirm('Rouvrir ce relevé ?');">@csrf
                        <button class="hl-bouton">Rouvrir</button></form>
                @endif
            </div>
        </div>
        <div class="table-responsive">
            <table class="lb-table">
                <thead><tr><th>Demande</th><th>Patient</th><th>Examens</th><th>Date</th><th class="lb-n">Montant</th><th class="lb-n">Réglé</th><th></th></tr></thead>
                <tbody>
                @foreach($releve->creances as $creance)
                    <tr>
                        <td class="lb-fort">{{ $creance->demande?->numero }}</td>
                        <td>{{ $creance->demande?->patient?->full_name }}</td>
                        <td style="font-size:.84rem">{{ $creance->demande?->examens->pluck('examen_nom')->join(', ') }}</td>
                        <td style="font-size:.84rem">{{ $creance->demande?->created_at->format('d/m/Y') }}</td>
                        <td class="lb-n">{{ $gnf($creance->montant) }}</td>
                        <td class="lb-n">{{ $gnf($creance->montant_regle) }}</td>
                        <td class="lb-actions">
                            @unless($releve->estEnvoye())
                                <form method="POST" action="{{ route('labo.creances.retirer', $creance) }}">@csrf
                                    <button class="hl-bouton lb-petit lb-risque">Retirer</button></form>
                            @endunless
                        </td>
                    </tr>
                @endforeach
                <tr class="rl-total">
                    <td colspan="4" style="text-align:right">Total</td>
                    <td class="lb-n">{{ $gnf($releve->montant_total) }}</td>
                    <td class="lb-n">{{ $gnf($releve->montantRegle()) }}</td>
                    <td></td>
                </tr>
                </tbody>
            </table>
        </div>
    </section>
</div></div>
@endsection
