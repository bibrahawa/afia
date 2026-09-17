@extends('layouts.backend')

@section('content')
<div class="container"><div class="page-inner">
    <div class="page-header"><h3 class="fw-bold mb-3">Grossesses suivies</h3></div>

    <div class="card"><div class="card-body table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>Patiente</th><th>Terme</th><th>Accouchement prévu</th><th>Prochaine CPN</th><th>Médecin</th><th></th></tr></thead>
            <tbody>
            @forelse($grossesses as $g)
                @php $prochain = $g->prochainContact(); @endphp
                <tr class="{{ $g->dpa->isPast() ? 'table-warning' : '' }}">
                    <td>{{ $g->patient->full_name }}</td>
                    <td>{{ $g->termeLisible() }}</td>
                    <td>{{ $g->dpa->format('d/m/Y') }}@if($g->dpa->isPast())<div class="small text-danger">terme dépassé</div>@endif</td>
                    <td class="small">@if($prochain){{ $prochain['semaines'] }} SA — {{ $prochain['date_cible']->format('d/m/Y') }}@else — @endif</td>
                    <td class="small">{{ $g->medecin ? 'Dr ' . $g->medecin->full_name : '—' }}</td>
                    <td class="text-end"><a href="{{ route('parcours.grossesses.show', $g) }}" class="btn btn-sm btn-outline-primary">Ouvrir</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">Aucune grossesse suivie.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>
</div></div>
@endsection
