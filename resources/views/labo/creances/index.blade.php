@extends('layouts.backend')

@php $gnf = fn ($m) => number_format((float) $m, 0, ',', ' '); @endphp

@section('content')
<div class="container"><div class="page-inner">
    <div class="page-header"><h3 class="fw-bold mb-3">Créances des cliniques partenaires</h3></div>

    <div class="card"><div class="card-body table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>Clinique</th><th class="text-end">À facturer</th><th class="text-end">Facturé non réglé</th><th class="text-end">Reste dû</th><th class="text-center">Relevés en attente</th><th></th></tr></thead>
            <tbody>
            @forelse($lignes as $ligne)
                @php $p = $ligne['partenariat']; $r = $ligne['resume']; @endphp
                <tr class="{{ $r['en_retard'] ? 'table-warning' : '' }}">
                    <td><strong>{{ $p->clinique?->nom }}</strong>@unless($p->estActif())<span class="badge badge-secondary ms-1">suspendu</span>@endunless</td>
                    <td class="text-end">{{ $gnf($r['a_facturer']) }}</td>
                    <td class="text-end">{{ $gnf($r['facture']) }}</td>
                    <td class="text-end"><strong>{{ $gnf($r['reste_du']) }}</strong> GNF</td>
                    <td class="text-center">{{ $r['releves_en_attente'] }}@if($r['en_retard'])<div class="small text-danger">{{ $r['en_retard'] }} en retard</div>@endif</td>
                    <td class="text-end"><a href="{{ route('labo.creances.show', $p) }}" class="btn btn-sm btn-outline-primary">Ouvrir</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">Aucune créance : les partenariats en mode « le laboratoire encaisse le patient » n'en produisent pas.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>
</div></div>
@endsection
