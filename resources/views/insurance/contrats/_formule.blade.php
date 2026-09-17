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
        <div class="form-text">Appliqué à la facturation avec le nouveau moteur de calcul (étape suivante).</div></div>
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
