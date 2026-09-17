@extends('layouts.backend')

@section('content')
<div class="container"><div class="page-inner">
    @include('assurance.partials.entete', ['titre' => 'Conventions tarifaires', 'fil' => [route('assurance.conventions.index') => 'Conventions']])

    <p class="text-muted">Pour chaque organisme : les familles d'actes couvertes au prix catalogue (avec remise éventuelle) et les actes à prix négocié ou exclus.
        Un acte qui n'est couvert ni par une règle de famille ni par une ligne reste à la charge du patient.</p>

    <div class="card"><div class="card-body table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>Organisme</th><th>Familles couvertes</th><th class="text-center">Lignes par acte</th><th></th></tr></thead>
            <tbody>
            @forelse($lignes as $l)
                <tr>
                    <td><a href="{{ route('assurance.conventions.show', $l['organisme']) }}" class="fw-bold">{{ $l['organisme']->name }}</a></td>
                    <td class="small">
                        @forelse($l['familles'] as $f)<span class="badge badge-light me-1">{{ $f->libelle() }}</span>@empty<span class="text-muted">aucune</span>@endforelse
                    </td>
                    <td class="text-center">{{ $l['actes'] }}</td>
                    <td class="text-end"><a href="{{ route('assurance.conventions.show', $l['organisme']) }}" class="btn btn-sm btn-outline-primary">Ouvrir</a></td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-muted text-center">Aucun organisme payeur.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>
</div></div>
@endsection
