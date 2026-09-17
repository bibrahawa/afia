@extends('layouts.backend')
@section('style') @include('labo.partials.styles') @endsection

@section('content')
<div class="container"><div class="page-inner">
    @include('labo.partials.entete', ['titre' => 'Demandes d\'analyses', 'fil' => [route('labo.demandes.index') => 'Demandes']])

    <div class="card">
        <div class="card-header">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4"><label class="form-label small">Recherche</label>
                    <input type="text" name="q" value="{{ $filtres['q'] ?? '' }}" class="form-control" placeholder="N° demande, nom, identifiant santé"></div>
                <div class="col-md-2"><label class="form-label small">Statut</label>
                    <select name="statut" class="form-select"><option value="">Tous</option>
                        @foreach($statuts as $s)<option value="{{ $s->value }}" @selected(($filtres['statut'] ?? '') === $s->value)>{{ $s->libelle() }}</option>@endforeach
                    </select></div>
                <div class="col-md-2"><label class="form-label small">Date</label>
                    <input type="date" name="date" value="{{ $filtres['date'] ?? '' }}" class="form-control"></div>
                <div class="col-md-2"><div class="form-check"><input class="form-check-input" type="checkbox" name="urgence" value="1" id="fUrg" @checked(request()->boolean('urgence'))>
                    <label class="form-check-label" for="fUrg">Urgences</label></div></div>
                <div class="col-md-2 d-flex gap-1">
                    <button class="btn btn-secondary btn-sm">Filtrer</button>
                    @can('labo.demande.create')<a href="{{ route('labo.demandes.create') }}" class="btn btn-primary btn-sm ms-auto"><i class="fa fa-plus"></i> Nouvelle</a>@endcan
                </div>
            </form>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-hover">
                <thead><tr><th>N°</th><th>Date</th><th>Patient</th><th>Prescripteur</th><th>Examens</th><th>Paiement</th><th>Statut</th></tr></thead>
                <tbody>
                @forelse($demandes as $d)
                    <tr class="{{ $d->urgence ? 'labo-urgent' : '' }}" style="cursor:pointer" onclick="window.location='{{ route('labo.demandes.show', $d) }}'">
                        <td><strong>{{ $d->numero }}</strong> @if($d->urgence)<span class="badge badge-danger">URGENT</span>@endif</td>
                        <td>{{ $d->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $d->patient->full_name }}<br><span class="small text-muted">{{ $d->patient->identifiant_national_sante }}</span></td>
                        <td class="small">{{ $d->nomPrescripteur() }}</td>
                        <td class="small">{{ $d->examens->where('statut', '!=', \App\Enums\Labo\StatutExamen::ANNULE)->pluck('examen_nom')->implode(', ') }}</td>
                        <td>
                            @if($d->mode_facturation !== \App\Enums\Labo\ModeFacturation::LABO)
                                <span class="badge badge-light">{{ $d->mode_facturation->libelle() }}</span>
                            @elseif(! $d->transaction)
                                <span class="badge badge-warning">Non facturée</span>
                            @else
                                <span class="badge badge-{{ $d->partPatientReglee() ? 'success' : 'danger' }}">{{ $d->partPatientReglee() ? 'Réglée' : 'Impayée' }}</span>
                            @endif
                        </td>
                        <td><span class="badge badge-{{ $d->statut->couleur() }}">{{ $d->statut->libelle() }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted">Aucune demande.</td></tr>
                @endforelse
                </tbody>
            </table>
            {{ $demandes->links() }}
        </div>
    </div>
</div></div>
@endsection
