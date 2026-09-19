@extends('layouts.backend')

@php $gnf = fn ($m) => number_format((float) $m, 0, ',', ' '); @endphp

@section('style') @include('labo.partials.styles') @endsection

@section('content')
<div class="container"><div class="page-inner hl">
    @include('labo.partials.entete', ['titre' => 'Créances des cliniques partenaires', 'fil' => [route('labo.creances.index') => 'Créances'], 'sousTitre' => 'Ce que les cliniques vous doivent pour les analyses facturées à leur nom.'])

    @php
        $totalDu = collect($lignes)->sum(fn ($l) => $l['resume']['reste_du']);
        $aFacturerTotal = collect($lignes)->sum(fn ($l) => $l['resume']['a_facturer']);
        $enRetard = collect($lignes)->sum(fn ($l) => $l['resume']['en_retard']);
    @endphp
    <div class="hl-kpis">
        <div class="hl-bloc hl-kpi"><span class="hl-kpi-libelle">Reste dû</span><span class="hl-kpi-valeur">{{ $gnf($totalDu) }} <small>GNF</small></span></div>
        <div class="hl-bloc hl-kpi"><span class="hl-kpi-libelle">À facturer</span><span class="hl-kpi-valeur">{{ $gnf($aFacturerTotal) }} <small>GNF</small></span><span class="hl-kpi-detail">analyses pas encore sur un relevé</span></div>
        <div class="hl-bloc hl-kpi {{ $enRetard ? 'est-danger' : '' }}"><span class="hl-kpi-libelle">Relevés en retard</span><span class="hl-kpi-valeur">{{ $enRetard }}</span></div>
    </div>

    <section class="hl-bloc">
        @if(count($lignes) === 0)
            <div class="hl-vide"><i class="fas fa-file-invoice-dollar" aria-hidden="true"></i>Aucune créance : les partenariats en mode « le laboratoire encaisse le patient » n'en produisent pas.</div>
        @else
            <div class="table-responsive">
                <table class="lb-table">
                    <thead><tr><th>Clinique</th><th class="lb-n">À facturer</th><th class="lb-n">Facturé non réglé</th><th class="lb-n">Reste dû</th><th class="lb-n">Relevés en attente</th><th></th></tr></thead>
                    <tbody>
                    @foreach($lignes as $ligne)
                        @php $p = $ligne['partenariat']; $r = $ligne['resume']; @endphp
                        <tr class="{{ $r['en_retard'] ? 'table-warning' : '' }}">
                            <td><span class="lb-fort">{{ $p->clinique?->nom }}</span>@unless($p->estActif()) <span class="hl-statut hl-s-neutre">suspendu</span>@endunless</td>
                            <td class="lb-n">{{ $gnf($r['a_facturer']) }}</td>
                            <td class="lb-n">{{ $gnf($r['facture']) }}</td>
                            <td class="lb-n"><span class="lb-fort">{{ $gnf($r['reste_du']) }} GNF</span></td>
                            <td class="lb-n">{{ $r['releves_en_attente'] }}@if($r['en_retard'])<span class="lb-sous" style="color:var(--hali-danger)">{{ $r['en_retard'] }} en retard</span>@endif</td>
                            <td class="lb-actions"><a href="{{ route('labo.creances.show', $p) }}" class="hl-bouton lb-petit">Ouvrir</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div></div>
@endsection
