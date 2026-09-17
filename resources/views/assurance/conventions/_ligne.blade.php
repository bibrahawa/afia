{{-- Champs d'une ligne de convention par acte. $ligne peut être null (ajout). --}}
@php
    $periodeLigne = \App\Support\Assurance\ConventionApplicable::periode($ligne?->usage_period);
@endphp
<div class="col-md-2"><label class="form-label small">Prix convention (GNF)</label>
    <input type="number" min="0" step="1" name="acte_price" class="form-control form-control-sm" value="{{ $ligne ? (float) $ligne->acte_price : '' }}" placeholder="Prix catalogue" {{ $ligne ? 'required' : '' }}></div>
<div class="col-md-2"><label class="form-label small">Plafond pris en charge</label>
    <input type="number" min="0" step="1" name="coverage_amount_limit" class="form-control form-control-sm" value="{{ $ligne?->coverage_amount_limit !== null ? (float) $ligne->coverage_amount_limit : '' }}" placeholder="Aucun"></div>
<div class="col-md-1"><label class="form-label small">Nb max</label>
    <input type="number" min="1" name="max_usage_count" class="form-control form-control-sm" value="{{ $ligne?->max_usage_count }}"></div>
<div class="col-md-2"><label class="form-label small">Période</label>
    <select name="usage_period" class="form-control form-control-sm">
        <option value="">—</option>
        @foreach(\App\Models\Assurance\FormuleGarantie::PERIODES as $valeur => $libellePeriode)
            <option value="{{ $valeur }}" @selected($periodeLigne === $valeur)>{{ $libellePeriode }}</option>
        @endforeach
    </select></div>
<div class="col-md-2 small pt-4">
    <input type="hidden" name="requires_preauthorization" value="0">
    <label><input type="checkbox" name="requires_preauthorization" value="1" @checked($ligne?->requires_preauthorization)> Accord préalable</label><br>
    <input type="hidden" name="exclu" value="0">
    <label class="text-danger"><input type="checkbox" name="exclu" value="1" @checked($ligne && $ligne->status === 'inactive')> Exclu (non couvert)</label>
</div>
