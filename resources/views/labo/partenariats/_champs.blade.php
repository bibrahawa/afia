{{-- Champs communs d'un partenariat. $partenariat peut être null. --}}
<div class="col-md-7"><label class="form-label">Facturation des analyses</label>
    <select name="mode_facturation_defaut" class="form-control">
        <option value="patient" @selected(($partenariat?->mode_facturation_defaut ?? 'patient') === 'patient')>Le laboratoire encaisse le patient</option>
        <option value="partenaire" @selected($partenariat?->mode_facturation_defaut === 'partenaire')>Le laboratoire facture la clinique</option>
    </select></div>
<div class="col-12">
    <div class="small text-muted mb-1">
        « Le laboratoire facture la clinique » sans l'option ci-dessous : personne ne facture le patient ici.
        La clinique doit alors avoir encaissé ces analyses de son côté.
    </div>
    <label class="small d-block">
        <input type="hidden" name="clinique_facture_patient" value="0">
        <input type="checkbox" name="clinique_facture_patient" value="1" @checked($partenariat?->clinique_facture_patient)>
        La clinique facture son patient (ses conventions d'assurance s'appliquent) et vous règle sur relevé
    </label>
</div>
<div class="col-md-5"><label class="form-label">Remise (%)</label>
    <input type="number" step="0.01" min="0" max="100" name="remise_pourcentage" class="form-control" value="{{ (float) ($partenariat?->remise_pourcentage ?? 0) }}"></div>
<div class="col-md-5"><label class="form-label">Paiement à (jours)</label>
    <input type="number" min="0" max="180" name="delai_paiement_jours" class="form-control" value="{{ $partenariat?->delai_paiement_jours }}"></div>
<div class="col-md-7"><label class="form-label">Contact</label>
    <input name="contact_nom" class="form-control" maxlength="255" value="{{ $partenariat?->contact_nom }}" placeholder="Nom du référent"></div>
<div class="col-md-5"><label class="form-label">Téléphone</label>
    <input name="contact_telephone" class="form-control" maxlength="30" value="{{ $partenariat?->contact_telephone }}"></div>
<div class="col-12"><label class="form-label">Notes</label>
    <textarea name="notes" class="form-control" rows="2">{{ $partenariat?->notes }}</textarea></div>
