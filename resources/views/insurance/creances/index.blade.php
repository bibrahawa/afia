@extends('layouts.backend')

@php $gnf = fn ($m) => number_format((float) $m, 0, ',', ' '); @endphp

@section('content')
<div class="container"><div class="page-inner">
    @include('assurance.partials.entete', ['titre' => 'Créances assurance', 'fil' => [route('assurance.creances.index') => 'Créances']])

    <p class="text-muted">Ce que chaque organisme payeur doit encore à l'établissement, réclamation par réclamation.
        Préparez les bordereaux d'envoi, saisissez les réponses des assureurs et enregistrez les règlements reçus.</p>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Organisme</th><th class="text-center">À envoyer</th><th class="text-center">Réclamations ouvertes</th><th class="text-end">Reste dû (GNF)</th><th class="text-end">Écarts en attente</th><th>Plus ancienne</th><th></th></tr></thead>
                <tbody>
                @forelse($lignes as $l)
                    <tr>
                        <td><a href="{{ route('assurance.creances.show', $l['organisme']) }}" class="fw-bold">{{ $l['organisme']->name }}</a></td>
                        <td class="text-center">@if($l['a_envoyer'])<span class="badge badge-warning">{{ $l['a_envoyer'] }}</span>@else 0 @endif</td>
                        <td class="text-center">{{ $l['nb_ouvertes'] }}</td>
                        <td class="text-end fw-bold">{{ $gnf($l['reste_du']) }}</td>
                        <td class="text-end {{ $l['ecarts'] > 0 ? 'text-danger' : '' }}">{{ $gnf($l['ecarts']) }}</td>
                        <td class="small">{{ $l['plus_ancienne']?->format('d/m/Y') ?? '—' }}</td>
                        <td class="text-end"><a href="{{ route('assurance.creances.show', $l['organisme']) }}" class="btn btn-sm btn-outline-primary">Ouvrir</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Aucune créance ouverte.</td></tr>
                @endforelse
                </tbody>
                @if($lignes->isNotEmpty())
                    <tfoot><tr class="table-active"><th colspan="3" class="text-end">Total</th><th class="text-end">{{ $gnf($lignes->sum('reste_du')) }}</th><th class="text-end">{{ $gnf($lignes->sum('ecarts')) }}</th><th colspan="2"></th></tr></tfoot>
                @endif
            </table>
        </div>
    </div>
</div></div>
@endsection
