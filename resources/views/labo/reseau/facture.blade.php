@extends('layouts.backend')

@php $gnf = fn ($m) => number_format((float) $m, 0, ',', ' '); @endphp

@section('style')
    @include('labo.partials.styles')
    <style>.fa-total td { background: #fafbfc; font-weight: 700; color: var(--hali-encre); border-top: 2px solid var(--hali-bordure) !important; }</style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">
    @include('labo.partials.entete', ['titre' => 'Relevé ' . $releve->numero, 'fil' => [route('labo.reseau.factures') => 'Factures', 0 => $releve->numero], 'sousTitre' => ($releve->partenariat?->laboratoire?->nom ?? '') . ' · période du ' . $releve->periode_debut->format('d/m/Y') . ' au ' . $releve->periode_fin->format('d/m/Y') . ($releve->echeance ? ' · à régler avant le ' . $releve->echeance->format('d/m/Y') : '')])

    <div class="hl-kpis">
        <div class="hl-bloc hl-kpi"><span class="hl-kpi-libelle">Dû au laboratoire</span><span class="hl-kpi-valeur">{{ $gnf($rapprochement['du_au_laboratoire']) }} <small>GNF</small></span></div>
        <div class="hl-bloc hl-kpi"><span class="hl-kpi-libelle">Facturé à vos patients</span><span class="hl-kpi-valeur">{{ $gnf($rapprochement['facture_au_patient']) }} <small>GNF</small></span></div>
        <div class="hl-bloc hl-kpi {{ $rapprochement['marge'] < 0 ? 'est-danger' : '' }}"><span class="hl-kpi-libelle">Marge</span><span class="hl-kpi-valeur">{{ $gnf($rapprochement['marge']) }} <small>GNF</small></span>
            @if($rapprochement['marge'] < 0)<span class="hl-kpi-detail" style="color:var(--hali-danger)">vente à perte</span>@endif</div>
    </div>

    @if($rapprochement['facturation_absente'])
        <p class="lb-alerte lb-alerte-avert"><i class="fas fa-exclamation-triangle mt-1" aria-hidden="true"></i><span>Aucune de ces analyses n'a été facturée à un patient dans votre établissement. Soit elles sont incluses dans vos consultations, soit personne n'a encaissé : vérifiez avant de régler ce relevé.</span></p>
    @endif

    <section class="hl-bloc">
        <div class="table-responsive">
            <table class="lb-table">
                <thead><tr><th>Demande</th><th>Patient</th><th>Examens</th><th>Date</th><th class="lb-n">Montant</th></tr></thead>
                <tbody>
                @foreach($releve->creances as $creance)
                    <tr>
                        <td class="lb-fort">{{ $creance->demande?->numero }}</td>
                        <td>{{ $creance->demande?->patient?->full_name }}</td>
                        <td style="font-size:.84rem">{{ $creance->demande?->examens->pluck('examen_nom')->join(', ') }}</td>
                        <td style="font-size:.84rem">{{ $creance->demande?->created_at->format('d/m/Y') }}</td>
                        <td class="lb-n">{{ $gnf($creance->montant) }}</td>
                    </tr>
                @endforeach
                <tr class="fa-total"><td colspan="4" style="text-align:right">Total</td><td class="lb-n">{{ $gnf($releve->montant_total) }} GNF</td></tr>
                <tr class="fa-total"><td colspan="4" style="text-align:right">Reste à payer</td><td class="lb-n">{{ $gnf($releve->resteDu()) }} GNF</td></tr>
                </tbody>
            </table>
        </div>
        <p class="lb-aide" style="padding:12px 18px; margin:0">Les règlements sont enregistrés par le laboratoire : ce relevé se met à jour quand il les saisit.</p>
    </section>
</div></div>
@endsection
