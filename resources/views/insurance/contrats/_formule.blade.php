{{-- Champs d'une formule. $formule peut être null. --}}
<div class="row g-2">
    <div class="col-md-8"><label class="form-label">Libellé <span class="text-danger">*</span></label>
        <input name="libelle" class="form-control" required maxlength="255" placeholder="Ex. Cadres, Agents, Famille" value="{{ $formule?->libelle }}"></div>
    <div class="col-md-4"><label class="form-label">Taux de prise en charge (%) <span class="text-danger">*</span></label>
        <input type="number" step="0.01" min="0" max="100" name="taux_prise_en_charge" class="form-control" required value="{{ $formule?->taux_prise_en_charge }}"></div>
    <div class="col-md-6"><label class="form-label">Plafond annuel par bénéficiaire (GNF)</label>
        <input type="number" step="1" min="0" name="plafond_annuel_beneficiaire" class="form-control" placeholder="Illimité" value="{{ $formule?->plafond_annuel_beneficiaire }}"></div>
    <div class="col-md-6"><label class="form-label">Plafond annuel familial (GNF)</label>
        <input type="number" step="1" min="0" name="plafond_annuel_famille" class="form-control" placeholder="Illimité" value="{{ $formule?->plafond_annuel_famille }}">
        <div class="form-text">Partagé par l'adhérent et ses ayants droit sur l'exercice du contrat.</div></div>
    <div class="col-md-4"><label class="form-label">Délai de carence (jours)</label>
        <input type="number" min="0" max="730" name="delai_carence_jours" class="form-control" value="{{ $formule?->delai_carence_jours ?? 0 }}"></div>
    <div class="col-md-4"><label class="form-label">Enfants couverts jusqu'à (ans)</label>
        <input type="number" min="0" max="30" name="age_max_enfant" class="form-control" required value="{{ $formule?->age_max_enfant ?? 21 }}"></div>
    <div class="col-md-4"><label class="form-label">… ou s'ils sont étudiants (ans)</label>
        <input type="number" min="0" max="35" name="age_max_enfant_etudiant" class="form-control" required value="{{ $formule?->age_max_enfant_etudiant ?? 25 }}"></div>
    <div class="col-12">
        <input type="hidden" name="actif" value="0">
        <label class="form-check-label"><input type="checkbox" name="actif" value="1" class="form-check-input" @checked($formule?->actif ?? true)> Formule active</label>
    </div>
</div>

<h6 class="mt-3">Garanties par famille d'actes</h6>
<p class="small text-muted mb-2">Laisser le taux vide = taux général de la formule. Accord préalable : sans bon de prise en charge valide, l'acte reste à la charge du patient.</p>
<div class="table-responsive">
<table class="table table-sm align-middle small">
    <thead><tr><th>Famille</th><th style="width:120px">Taux (%)</th><th style="width:170px">Plafond par acte (GNF)</th><th class="text-center">Exclu</th><th class="text-center">Accord préalable</th></tr></thead>
    <tbody>
    @foreach(\App\Enums\Assurance\FamilleActe::cases() as $familleActe)
        @php $g = $formule?->garantiePour($familleActe); $cle = 'garanties[' . $familleActe->value . ']'; @endphp
        <tr>
            <td>{{ $familleActe->libelle() }}</td>
            <td><input type="number" step="0.01" min="0" max="100" name="{{ $cle }}[taux]" class="form-control form-control-sm" value="{{ $g?->taux }}"></td>
            <td><input type="number" step="1" min="0" name="{{ $cle }}[plafond_par_acte]" class="form-control form-control-sm" value="{{ $g?->plafond_par_acte }}"></td>
            <td class="text-center"><input type="hidden" name="{{ $cle }}[exclu]" value="0"><input type="checkbox" name="{{ $cle }}[exclu]" value="1" class="form-check-input" @checked($g?->exclu)></td>
            <td class="text-center"><input type="hidden" name="{{ $cle }}[accord_prealable]" value="0"><input type="checkbox" name="{{ $cle }}[accord_prealable]" value="1" class="form-check-input" @checked($g?->accord_prealable)></td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>
