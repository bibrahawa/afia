@extends('layouts.backend')
@section('style') @include('labo.partials.styles') @endsection

@section('content')
<div class="container"><div class="page-inner">
    @include('labo.partials.entete', ['titre' => 'Prélèvements en attente', 'fil' => [route('labo.prelevements.index') => 'Prélèvements']])

    @forelse($demandes as $d)
        <div class="card {{ $d->urgence ? 'labo-urgent' : '' }}">
            <div class="card-header d-flex align-items-center">
                <div>
                    <h4 class="card-title mb-0">{{ $d->patient->full_name }} @if($d->urgence)<span class="badge badge-danger">URGENT</span>@endif</h4>
                    <span class="small text-muted">{{ $d->numero }} · {{ $d->created_at->format('d/m H:i') }} ({{ $d->created_at->diffForHumans() }})
                        @if($d->a_jeun_confirme) · à jeun @endif</span>
                </div>
                <a href="{{ route('labo.demandes.etiquettes', $d) }}" target="_blank" class="btn btn-outline-secondary btn-sm ms-auto"><i class="fa fa-barcode"></i> Étiquettes</a>
                <a href="{{ route('labo.demandes.show', $d) }}" class="btn btn-link btn-sm">Fiche</a>
            </div>
            <div class="card-body py-2">
                @foreach($d->echantillons as $e)
                    <div class="d-flex align-items-center border-bottom py-2">
                        <div>
                            @if($e->tube)<span class="labo-tube labo-tube-{{ $e->tube }}"></span>@endif
                            <strong>{{ $e->libelleContenant() }}</strong> <code>{{ $e->code_barres }}</code><br>
                            <span class="small text-muted">{{ $e->examens->pluck('examen_nom')->implode(', ') }}</span>
                            @if($e->remplace_echantillon_id)<span class="badge badge-warning">Re-prélèvement</span>@endif
                        </div>
                        <form method="POST" action="{{ route('labo.echantillons.preleve', $e) }}" class="ms-auto">@csrf
                            <button class="btn btn-sm btn-outline-primary">Prélevé</button>
                            @can('labo.reception')<button name="et_recu" value="1" class="btn btn-sm btn-primary">Prélevé + reçu</button>@endcan
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="card"><div class="card-body text-muted">Aucun prélèvement en attente.</div></div>
    @endforelse
    {{ $demandes->links() }}
</div></div>
@endsection
