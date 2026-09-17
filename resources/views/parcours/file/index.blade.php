@extends('layouts.backend')

@section('content')
<div class="container"><div class="page-inner">
    <div class="page-header"><h3 class="fw-bold mb-3">Ma file d'attente</h3></div>

    @if($enConsultation->isNotEmpty())
        <div class="card border-primary">
            <div class="card-header"><h4 class="card-title">En consultation</h4></div>
            <ul class="list-group list-group-flush">
                @foreach($enConsultation as $v)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><strong>{{ $v->patient->full_name }}</strong> — {{ $v->motif }}</span>
                        <span class="d-flex gap-1">
                            <a href="{{ route('parcours.consultation.show', $v->consultation) }}" class="btn btn-sm btn-primary">Reprendre</a>
                            <form method="POST" action="{{ route('parcours.file.terminer', $v) }}">@csrf<button class="btn btn-sm btn-outline-success">Terminer</button></form>
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-header"><h4 class="card-title">En attente <span class="badge badge-warning">{{ $enAttente->count() }}</span></h4></div>
        <ul class="list-group list-group-flush">
            @forelse($enAttente as $index => $v)
                @php $transaction = $v->consultation?->transaction; @endphp
                <li class="list-group-item {{ $v->urgence ? 'list-group-item-danger' : '' }}">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>{{ $v->patient->full_name }}</strong>
                            <span class="text-muted small">{{ $v->patient->gender }}{{ $v->patient->age !== null ? ', ' . $v->patient->age . ' ans' : '' }}</span>
                            @if($v->urgence)<span class="badge badge-danger">Urgence</span>@endif
                            @if($v->appointment)<span class="badge badge-light">RDV {{ $v->appointment->appointment_time?->format('H:i') }}</span>@endif
                            <div class="small">{{ $v->motif }} · attend depuis {{ $v->minutesAttente() }} min
                                @if($transaction && ! in_array($transaction->status, ['paid', 'approved'], true)) · <span class="text-warning">acte non encaissé</span>@endif
                            </div>
                            <div>@include('parcours.partials.constantes', ['c' => $v->derniereConstante])</div>
                            @can('parcours.dossier')<a href="{{ route('parcours.dossier.show', $v->patient_id) }}" target="_blank" class="small">Dossier du patient</a>@endcan
                        </div>
                        <form method="POST" action="{{ route('parcours.file.appeler', $v) }}">@csrf
                            <button class="btn {{ $index === 0 ? 'btn-success' : 'btn-outline-success' }}">Appeler</button></form>
                    </div>
                </li>
            @empty
                <li class="list-group-item text-muted">Personne en attente.</li>
            @endforelse
        </ul>
    </div>

    @if($terminees->isNotEmpty())
        <div class="card">
            <div class="card-header"><h4 class="card-title">Vus aujourd'hui ({{ $terminees->count() }})</h4></div>
            <ul class="list-group list-group-flush small">
                @foreach($terminees as $v)
                    <li class="list-group-item d-flex justify-content-between">
                        <span>{{ $v->terminee_le?->format('H:i') }} — {{ $v->patient->full_name }} · {{ $v->motif }}</span>
                        <a href="{{ route('consultation.show', $v->consultation) }}">Voir</a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div></div>
@endsection
