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
                    Par défaut, un médecin peut recevoir tous les motifs de son département — ne
                    coche/décoche que si tu veux explicitement restreindre ou personnaliser la durée
                    pour {{ $employee->full_name }}. Si aucun médecin de ce département n'a
                    d'association ici, cette liste n'a aucun effet restrictif.
                </p>
                <form action="{{ route('employees.motifs.sync', $employee) }}" method="POST">
                    @csrf
                    <table class="table">
                        <thead><tr><th></th><th>Motif</th><th>Durée par défaut</th><th>Durée personnalisée</th></tr></thead>
                        <tbody>
                        @foreach($motifs as $motif)
                            @php($association = $associations->get($motif->id))
                            <tr>
                                <td>
                                    <input type="checkbox" name="motifs[{{ $motif->id }}][actif]" value="1"
                                        {{ $association?->pivot?->actif ? 'checked' : '' }}>
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
