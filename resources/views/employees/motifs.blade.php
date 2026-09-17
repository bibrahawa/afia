@extends('layouts.backend')

@section('content')
<div class="container">
    <div class="page-inner">
      <div class="page-header">
        <ul class="breadcrumbs">
          <li class="nav-home"><a href="{{url('/')}}"><i class="icon-home"></i></a></li>
          <li class="separator"><i class="icon-arrow-right"></i></li>
          <li class="nav-item">{{ $employee->full_name }} — Motifs pratiqués</li>
        </ul>
      </div>

      <div class="row">
        <div class="col-md-8">
          <div class="card">
            <div class="card-header"><h4 class="card-title">Motifs du département {{ $employee->department->name }}</h4></div>
            <div class="card-body">
                <p class="small text-muted">
                    Par défaut, {{ $employee->full_name }} reçoit tous les motifs de son département.
                    Décoche un motif pour qu'il ne soit plus proposé avec ce médecin (en ligne comme à
                    l'accueil). La durée personnalisée est optionnelle et ne change rien d'autre.
                </p>
                <form action="{{ route('employees.motifs.sync', $employee) }}" method="POST">
                    @csrf
                    <table class="table">
                        <thead><tr><th>Pratique</th><th>Motif</th><th>Durée par défaut</th><th>Durée personnalisée</th></tr></thead>
                        <tbody>
                        @foreach($motifs as $motif)
                            @php($association = $associations->get($motif->id))
                            @php($pratique = ! $association || $association->pivot->actif)
                            <tr class="{{ $pratique ? '' : 'text-muted' }}">
                                <td>
                                    {{-- Champ caché : une case décochée n'est pas envoyée par le navigateur --}}
                                    <input type="hidden" name="motifs[{{ $motif->id }}][pratique]" value="0">
                                    <input type="checkbox" name="motifs[{{ $motif->id }}][pratique]" value="1" {{ $pratique ? 'checked' : '' }}>
                                </td>
                                <td>{{ $motif->nom }}</td>
                                <td>{{ $motif->duree_minutes_defaut }} min</td>
                                <td>
                                    <input type="number" name="motifs[{{ $motif->id }}][duree_minutes]" class="form-control form-control-sm"
                                        placeholder="défaut" min="1" max="240"
                                        value="{{ $association?->pivot?->duree_minutes }}">
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </form>
            </div>
          </div>
        </div>
      </div>
    </div>
</div>
@endsection
