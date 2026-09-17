@extends('layouts.backend')

@php $gnf = fn ($m) => number_format((float) $m, 0, ',', ' '); @endphp

@section('content')
<div class="container"><div class="page-inner">
    @include('assurance.partials.entete', ['titre' => 'Convention — ' . $organisme->name, 'fil' => [route('assurance.conventions.index') => 'Conventions', 0 => $organisme->name]])

    {{-- ------------------------------------------------ Règles par famille --}}
    <div class="card">
        <div class="card-header"><h4 class="card-title">Règles par famille d'actes</h4></div>
        <div class="card-body">
            <p class="small text-muted">Famille cochée : tous ses actes sont couverts au prix catalogue moins la remise, sans ligne à saisir.
                Les lignes par acte ci-dessous restent prioritaires (prix négocié ou exclusion).</p>
            <form method="POST" action="{{ route('assurance.conventions.familles', $organisme) }}">@csrf
                <div class="table-responsive">
                <table class="table table-sm align-middle small">
                    <thead><tr><th>Couverte</th><th>Famille</th><th style="width:110px">Remise (%)</th><th style="width:160px">Plafond par acte</th><th class="text-center">Accord préalable</th><th style="width:150px">Depuis</th><th style="width:150px">Jusqu'au</th></tr></thead>
                    <tbody>
                    @foreach(\App\Enums\Assurance\FamilleActe::cases() as $famille)
                        @php $r = $regles->get($famille->value); $cle = 'familles[' . $famille->value . ']'; @endphp
                        <tr>
                            <td><input type="hidden" name="{{ $cle }}[actif]" value="0"><input type="checkbox" name="{{ $cle }}[actif]" value="1" class="form-check-input" @checked($r) @cannot('assurance.convention.gerer') disabled @endcannot></td>
                            <td>{{ $famille->libelle() }}</td>
                            <td><input type="number" step="0.01" min="0" max="100" name="{{ $cle }}[remise_pourcentage]" class="form-control form-control-sm" value="{{ $r ? (float) $r->remise_pourcentage : 0 }}"></td>
                            <td><input type="number" step="1" min="0" name="{{ $cle }}[plafond_par_acte]" class="form-control form-control-sm" value="{{ $r?->plafond_par_acte !== null ? (float) $r->plafond_par_acte : '' }}" placeholder="Aucun"></td>
                            <td class="text-center"><input type="hidden" name="{{ $cle }}[accord_prealable]" value="0"><input type="checkbox" name="{{ $cle }}[accord_prealable]" value="1" class="form-check-input" @checked($r?->accord_prealable)></td>
                            <td><input type="date" name="{{ $cle }}[valid_from]" class="form-control form-control-sm" value="{{ $r?->valid_from?->toDateString() ?? today()->toDateString() }}"></td>
                            <td><input type="date" name="{{ $cle }}[valid_to]" class="form-control form-control-sm" value="{{ $r?->valid_to?->toDateString() }}"></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                </div>
                @can('assurance.convention.gerer')<button class="btn btn-sm btn-primary">Enregistrer les règles</button>@endcan
            </form>
        </div>
    </div>

    {{-- ------------------------------------------------ Lignes par acte --}}
    <div class="card">
        <div class="card-header"><h4 class="card-title">Actes à prix négocié ou exclus</h4></div>
        <div class="card-body">
            @can('assurance.convention.gerer')
                <form method="POST" action="{{ route('assurance.conventions.actes.store', $organisme) }}" class="row g-2 align-items-end mb-4 border-bottom pb-3">@csrf
                    <div class="col-md-3"><label class="form-label small">Acte *</label>
                        <select name="acte" class="form-control form-control-sm" required>
                            <option value="">— Choisir —</option>
                            @foreach($actes as $type => $liste)
                                @if($liste->isNotEmpty())
                                    <optgroup label="{{ \App\Support\Assurance\CatalogueActes::TYPES[$type]['libelle'] }}">
                                        @foreach($liste as $a)<option value="{{ $type }}:{{ $a['id'] }}">{{ $a['nom'] }} — {{ $gnf($a['prix']) }} GNF</option>@endforeach
                                    </optgroup>
                                @endif
                            @endforeach
                        </select></div>
                    @include('assurance.conventions._ligne', ['ligne' => null])
                    <div class="col-md-12"><button class="btn btn-sm btn-primary"><i class="fa fa-plus"></i> Ajouter à la convention</button></div>
                </form>
            @endcan

            @forelse($lignes as $type => $groupe)
                <h6 class="mt-3">{{ \App\Support\Assurance\CatalogueActes::TYPES[$type]['libelle'] ?? 'Autres' }}</h6>
                <div class="table-responsive">
                <table class="table table-sm align-middle small">
                    <thead><tr><th>Acte</th><th class="text-end">Prix catalogue</th><th class="text-end">Prix convention</th><th>Conditions</th><th></th></tr></thead>
                    <tbody>
                    @foreach($groupe as $ligne)
                        @php $periodeLigne = \App\Support\Assurance\ConventionApplicable::periode($ligne->usage_period); @endphp
                        <tr class="{{ $ligne->status === 'inactive' ? 'table-danger' : '' }}">
                            <td>{{ $catalogue->libelle($ligne->coverageable) }}</td>
                            <td class="text-end text-muted">{{ $catalogue->prixCatalogue($ligne->coverageable) !== null ? $gnf($catalogue->prixCatalogue($ligne->coverageable)) : '—' }}</td>
                            <td class="text-end fw-bold">{{ $ligne->status === 'inactive' ? 'Exclu' : $gnf($ligne->acte_price) }}</td>
                            <td>
                                @if($ligne->coverage_amount_limit !== null) plafond {{ $gnf($ligne->coverage_amount_limit) }} · @endif
                                @if($ligne->max_usage_count && $periodeLigne) {{ $ligne->max_usage_count }} {{ \App\Models\Assurance\FormuleGarantie::PERIODES[$periodeLigne] }} · @endif
                                @if($ligne->requires_preauthorization) accord préalable · @endif
                                depuis {{ $ligne->valid_from?->format('d/m/Y') }}
                            </td>
                            <td class="text-end text-nowrap">
                                @can('assurance.convention.gerer')
                                    <details class="d-inline-block text-start">
                                        <summary class="btn btn-sm btn-warning"><i class="fa fa-edit"></i></summary>
                                        <form method="POST" action="{{ route('assurance.conventions.actes.update', $ligne) }}" class="row g-1 p-2 border bg-white position-absolute shadow" style="z-index:10; width:720px; right:0">@csrf @method('PUT')
                                            @include('assurance.conventions._ligne', ['ligne' => $ligne])
                                            <div class="col-md-3"><button class="btn btn-sm btn-primary w-100 mt-4">Enregistrer</button></div>
                                        </form>
                                    </details>
                                    <form method="POST" action="{{ route('assurance.conventions.actes.destroy', $ligne) }}" class="d-inline"
                                          onsubmit="return confirm('Retirer cet acte de la convention ?');">@csrf @method('DELETE')
                                        <button class="btn btn-sm btn-danger"><i class="fa fa-trash"></i></button></form>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                </div>
            @empty
                <p class="text-muted small">Aucune ligne par acte.</p>
            @endforelse
        </div>
    </div>

    {{-- ------------------------------------------------ Copie --}}
    @can('assurance.convention.gerer')
        @if($autresOrganismes->isNotEmpty())
            <div class="card"><div class="card-body">
                <form method="POST" action="{{ route('assurance.conventions.copier', $organisme) }}" class="row g-2 align-items-end"
                      onsubmit="return this.mode.value !== 'remplacer' || confirm('Remplacer toute la convention actuelle de {{ $organisme->name }} ?');">@csrf
                    <div class="col-md-4"><label class="form-label small">Copier la convention de</label>
                        <select name="source_id" class="form-control form-control-sm" required>
                            @foreach($autresOrganismes as $o)<option value="{{ $o->id }}">{{ $o->name }}</option>@endforeach
                        </select></div>
                    <div class="col-md-4"><label class="form-label small">Mode</label>
                        <select name="mode" class="form-control form-control-sm">
                            <option value="completer">Compléter (ajouter ce qui manque)</option>
                            <option value="remplacer">Remplacer la convention actuelle</option>
                        </select></div>
                    <div class="col-md-4"><button class="btn btn-sm btn-outline-primary w-100">Copier</button></div>
                </form>
            </div></div>
        @endif
    @endcan
</div></div>
@endsection
