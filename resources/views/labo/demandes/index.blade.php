@extends('layouts.backend')
@section('style')
    @include('labo.partials.styles')
    <style>
        .dm-filtres { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; padding: 14px 18px; border-bottom: 1px solid var(--hali-bordure); }
        .dm-recherche { position: relative; flex: 1 1 260px; }
        .dm-recherche i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #9ca3af; }
        .dm-recherche input { width: 100%; min-height: 42px; padding-left: 40px; }
        .dm-filtres select, .dm-filtres input[type=date] { width: auto; min-height: 42px; }
        .dm-urgence { display: inline-flex; align-items: center; gap: 8px; margin: 0; padding: 0 12px; min-height: 42px; border: 1px solid var(--hali-bordure); border-radius: 8px; cursor: pointer; font-weight: 600; font-size: .85rem; }
        .dm-urgence:has(input:checked) { border-color: var(--hali-danger); background: var(--hali-danger-pale); color: var(--hali-danger); }
        .dm-table { width: 100%; border-collapse: collapse; font-size: .88rem; }
        .dm-table th { padding: 10px 16px; background: #fafbfc; border-bottom: 1px solid var(--hali-bordure); color: var(--hali-discret); font-size: .78rem; font-weight: 600; text-align: left; white-space: nowrap; }
        .dm-table td { padding: 12px 16px; border-top: 1px solid #f3f4f6; vertical-align: middle; }
        .dm-table tbody tr { position: relative; }
        .dm-table tbody tr:hover > td { background: var(--hali-primaire-pale); }
        .dm-numero { color: var(--hali-encre); font-weight: 700; font-variant-numeric: tabular-nums; text-decoration: none; }
        .dm-numero::after { content: ""; position: absolute; inset: 0; }
        .dm-sous { display: block; color: var(--hali-discret); font-size: .78rem; }
        .dm-examens { max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--hali-texte); }
        .dm-pagination { padding: 14px 18px; border-top: 1px solid var(--hali-bordure); }
        .dm-pagination nav { display: flex; justify-content: center; }
    </style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; gap:12px">
        <div style="flex:1">@include('labo.partials.entete', ['titre' => 'Demandes d\'analyses', 'fil' => [route('labo.demandes.index') => 'Demandes']])</div>
        @can('labo.demande.create')
            <a href="{{ route('labo.demandes.create') }}" class="hl-bouton hl-bouton-plein" style="margin-bottom:18px"><i class="fa fa-plus" aria-hidden="true"></i> Nouvelle demande</a>
        @endcan
    </div>

    <section class="hl-bloc">
        <form method="GET" class="dm-filtres" id="dmFiltres">
            <label class="dm-recherche mb-0">
                <span class="sr-only visually-hidden">Rechercher</span>
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" name="q" value="{{ $filtres['q'] ?? '' }}" class="form-control" placeholder="N° de demande, nom, identifiant santé… puis Entrée">
            </label>
            <select name="statut" class="form-select js-auto" aria-label="Statut">
                <option value="">Tous les statuts</option>
                @foreach($statuts as $s)<option value="{{ $s->value }}" @selected(($filtres['statut'] ?? '') === $s->value)>{{ $s->libelle() }}</option>@endforeach
            </select>
            <input type="date" name="date" value="{{ $filtres['date'] ?? '' }}" class="form-control js-auto" aria-label="Date">
            <label class="dm-urgence"><input type="checkbox" name="urgence" value="1" class="js-auto" @checked(request()->boolean('urgence'))> <i class="fas fa-bolt" aria-hidden="true"></i> Urgences</label>
            @if(! empty($filtres['q']) || ! empty($filtres['statut']) || ! empty($filtres['date']) || request()->boolean('urgence'))
                <a href="{{ route('labo.demandes.index') }}" style="font-size:.85rem; font-weight:600">Effacer</a>
            @endif
        </form>

        @if($demandes->isEmpty())
            <div class="hl-vide"><i class="fas fa-vials" aria-hidden="true"></i>Aucune demande ne correspond.</div>
        @else
            <div class="table-responsive">
                <table class="dm-table">
                    <thead><tr><th>Demande</th><th>Patient</th><th>Prescripteur</th><th>Examens</th><th>Paiement</th><th>Statut</th></tr></thead>
                    <tbody>
                    @foreach($demandes as $d)
                        @php($examens = $d->examens->where('statut', '!=', \App\Enums\Labo\StatutExamen::ANNULE)->pluck('examen_nom'))
                        <tr class="{{ $d->urgence ? 'labo-urgent' : '' }}">
                            <td>
                                <a href="{{ route('labo.demandes.show', $d) }}" class="dm-numero">{{ $d->numero }}</a>
                                @if($d->urgence) <span class="labo-pastille-urgent"><i class="fas fa-bolt" aria-hidden="true"></i> Urgent</span>@endif
                                <span class="dm-sous">{{ $d->created_at->format('d/m/Y à H:i') }}</span>
                            </td>
                            <td>{{ $d->patient->full_name }}<span class="dm-sous">{{ $d->patient->identifiant_national_sante }}</span></td>
                            <td style="font-size:.85rem">{{ $d->nomPrescripteur() }}</td>
                            <td><span class="dm-examens" title="{{ $examens->implode(', ') }}">{{ $examens->implode(', ') }}</span><span class="dm-sous">{{ $examens->count() }} examen{{ $examens->count() > 1 ? 's' : '' }}</span></td>
                            <td>
                                @if($d->mode_facturation !== \App\Enums\Labo\ModeFacturation::LABO)
                                    <span class="hl-statut hl-s-neutre">{{ $d->mode_facturation->libelle() }}</span>
                                @elseif(! $d->transaction)
                                    <span class="hl-statut hl-s-alerte">Non facturée</span>
                                @elseif($d->partPatientReglee())
                                    <span class="hl-statut hl-s-succes">Réglée</span>
                                @else
                                    <span class="hl-statut hl-s-danger">Impayée</span>
                                @endif
                            </td>
                            <td><span class="badge badge-{{ $d->statut->couleur() }}">{{ $d->statut->libelle() }}</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if($demandes->hasPages())
                <div class="dm-pagination">{{ $demandes->withQueryString()->links() }}</div>
            @endif
        @endif
    </section>
</div></div>

<script>
document.querySelectorAll('#dmFiltres .js-auto').forEach(function (c) { c.addEventListener('change', function () { c.form.submit(); }); });
</script>
@endsection
