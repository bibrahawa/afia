@extends('layouts.backend')
@section('style') @include('labo.partials.styles') @endsection

@section('content')
<div class="container"><div class="page-inner">
    @include('labo.partials.entete', ['titre' => 'Paillasse — liste de travail', 'fil' => [route('labo.paillasse.index') => 'Paillasse']])

    <div class="mb-3">
        <a href="{{ route('labo.paillasse.index') }}" class="btn btn-sm {{ ! $sectionId ? 'btn-primary' : 'btn-outline-primary' }}">Toutes</a>
        @foreach($sections as $s)
            <a href="{{ route('labo.paillasse.index', ['section' => $s->id]) }}" class="btn btn-sm {{ $sectionId === $s->id ? 'btn-primary' : 'btn-outline-primary' }}">{{ $s->nom }}</a>
        @endforeach
    </div>

    <div class="card"><div class="card-body table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>Examen</th><th>Patient</th><th>Demande</th><th>Reçu</th><th>Échéance</th><th>Statut</th><th></th></tr></thead>
            <tbody>
            @forelse($lignes as $l)
                @php($echeance = $l->echeance())
                <tr class="{{ $l->demande->urgence ? 'labo-urgent' : '' }}">
                    <td><strong>{{ $l->examen_nom }}</strong><br><span class="small text-muted">{{ $l->examen->section->nom }}</span></td>
                    <td>{{ $l->demande->patient->full_name }}</td>
                    <td>{{ $l->demande->numero }} @if($l->demande->urgence)<span class="badge badge-danger">URG</span>@endif</td>
                    <td class="small">{{ optional($l->echantillons->whereNotNull('recu_le')->min('recu_le'), fn ($d) => \Illuminate\Support\Carbon::parse($d)->format('d/m H:i')) }}</td>
                    <td class="small {{ $echeance?->isPast() ? 'text-danger fw-bold' : '' }}">{{ $echeance?->format('d/m H:i') }}</td>
                    <td><span class="badge badge-{{ $l->statut->couleur() }}">{{ $l->statut->libelle() }}</span></td>
                    <td><a href="{{ route('labo.paillasse.saisie', $l) }}" class="btn btn-sm btn-primary">Saisir</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted">Aucun examen en attente sur cette paillasse.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $lignes->links() }}
    </div></div>
</div></div>
@endsection
