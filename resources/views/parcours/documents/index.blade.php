@extends('layouts.backend')

@section('content')
<div class="container"><div class="page-inner">
    <div class="page-header d-flex align-items-center gap-2">
        <h3 class="fw-bold mb-0">Documents de {{ $patient->full_name }}</h3>
        @can('parcours.dossier')<a href="{{ route('parcours.dossier.show', $patient->id) }}" class="btn btn-sm btn-outline-secondary ms-auto">Dossier</a>@endcan
    </div>

    <div class="card"><div class="card-body table-responsive">
        <table class="table table-sm align-middle">
            <thead><tr><th>Date</th><th>Type</th><th>N°</th><th>Période</th><th>Médecin</th><th></th></tr></thead>
            <tbody>
            @forelse($documents as $d)
                <tr class="{{ $d->annule ? 'text-muted' : '' }}">
                    <td class="small">{{ $d->created_at->format('d/m/Y') }}</td>
                    <td>{{ $d->type->libelle() }}@if($d->annule)<span class="badge badge-danger ms-1">annulé</span>@endif</td>
                    <td class="small">{{ $d->numero }}</td>
                    <td class="small">@if($d->date_debut){{ $d->date_debut->format('d/m/Y') }} → {{ $d->date_fin?->format('d/m/Y') }} ({{ $d->jours }} j)@else — @endif</td>
                    <td class="small">{{ $d->medecin ? 'Dr ' . $d->medecin->full_name : '—' }}</td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('parcours.documents.imprimer', $d) }}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fa fa-print"></i></a>
                        @can('parcours.document')
                            @unless($d->annule)
                                <details class="d-inline-block text-start">
                                    <summary class="btn btn-sm btn-outline-danger">Annuler</summary>
                                    <form method="POST" action="{{ route('parcours.documents.annuler', $d) }}" class="position-absolute bg-white border shadow p-2" style="z-index:10; width:260px">@csrf
                                        <input name="motif_annulation" class="form-control form-control-sm mb-1" maxlength="255" placeholder="Motif de l'annulation" required>
                                        <button class="btn btn-sm btn-danger w-100">Confirmer l'annulation</button>
                                    </form>
                                </details>
                            @endunless
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">Aucun document établi.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>
</div></div>
@endsection
