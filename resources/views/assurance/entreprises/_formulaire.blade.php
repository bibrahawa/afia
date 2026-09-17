{{-- Champs communs création / modification d'une entreprise. $entreprise peut être null. --}}
<div class="row g-2">
    <div class="col-md-6"><label class="form-label">Nom <span class="text-danger">*</span></label>
        <input name="nom" class="form-control" required maxlength="255" value="{{ old('nom', $entreprise?->nom) }}"></div>
    <div class="col-md-3"><label class="form-label">NIF</label>
        <input name="nif" class="form-control" maxlength="50" value="{{ old('nif', $entreprise?->nif) }}"></div>
    <div class="col-md-3"><label class="form-label">Secteur</label>
        <input name="secteur" class="form-control" maxlength="100" placeholder="Mines, banque…" value="{{ old('secteur', $entreprise?->secteur) }}"></div>
    <div class="col-md-6"><label class="form-label">Adresse</label>
        <input name="adresse" class="form-control" maxlength="255" value="{{ old('adresse', $entreprise?->adresse) }}"></div>
    <div class="col-md-6"><label class="form-label">Contact (RH / médecine du travail)</label>
        <input name="contact_nom" class="form-control" maxlength="255" value="{{ old('contact_nom', $entreprise?->contact_nom) }}"></div>
    <div class="col-md-6"><label class="form-label">Téléphone</label>
        <input name="telephone" class="form-control" maxlength="30" value="{{ old('telephone', $entreprise?->telephone) }}"></div>
    <div class="col-md-6"><label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="{{ old('email', $entreprise?->email) }}"></div>
    <div class="col-12"><label class="form-label">Notes</label>
        <textarea name="notes" class="form-control" rows="2">{{ old('notes', $entreprise?->notes) }}</textarea></div>
    <div class="col-12">
        <input type="hidden" name="actif" value="0">
        <label class="form-check-label"><input type="checkbox" name="actif" value="1" class="form-check-input" @checked(old('actif', $entreprise?->actif ?? true))> Entreprise active</label>
    </div>
</div>
