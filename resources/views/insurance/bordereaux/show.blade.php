@extends('layouts.backend')

@section('content')
<div class="container"><div class="page-inner">
    @include('assurance.partials.entete', [
        'titre' => 'Bordereau ' . $bordereau->numero,
        'fil' => [route('assurance.creances.index') => 'Créances', route('assurance.creances.show', $bordereau->organisme) => $bordereau->organisme->name, 0 => $bordereau->numero],
    ])

    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center gap-2">
            <h4 class="card-title mb-0">{{ $bordereau->organisme->name }}</h4>
            <span class="badge badge-{{ $bordereau->estBrouillon() ? 'secondary' : 'info' }}">{{ $bordereau->estBrouillon() ? 'Brouillon' : 'Envoyé le ' . $bordereau->date_envoi?->format('d/m/Y') }}</span>
            <span class="small text-muted">Période : {{ $bordereau->periode_debut?->format('d/m/Y') ?? '…' }} → {{ $bordereau->periode_fin?->format('d/m/Y') ?? '…' }}</span>
            <div class="ms-auto d-flex gap-2">
                <a href="{{ route('assurance.bordereaux.imprimer', $bordereau) }}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fa fa-print"></i> Imprimer</a>
                @can('assurance.reclamation.gerer')
                    @if($bordereau->estBrouillon())
                        <form method="POST" action="{{ route('assurance.bordereaux.envoyer', $bordereau) }}" class="d-flex gap-1"
                              onsubmit="return confirm('Marquer comme envoyé ? Les factures concernées ne pourront plus être modifiées.');">@csrf
                            <input type="date" name="date_envoi" value="{{ today()->toDateString() }}" class="form-control form-control-sm" required>
                            <button class="btn btn-sm btn-primary text-nowrap">Marquer envoyé</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('assurance.bordereaux.rouvrir', $bordereau) }}" onsubmit="return confirm('Rouvrir le bordereau pour correction ?');">@csrf
                            <button class="btn btn-sm btn-outline-secondary">Rouvrir</button></form>
                    @endif
                @endcan
            </div>
        </div>
        <div class="card-body table-responsive">
            @if($bordereau->notes)<p class="small text-muted">{{ $bordereau->notes }}</p>@endif
            @include('assurance.bordereaux._tableau', ['impression' => false])
        </div>
    </div>
</div></div>
@endsection
