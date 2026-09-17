{{-- Champs communs création / modification d'un contrat. $contrat peut être null. --}}
<div class="row g-2">
    <div class="col-md-6"><label class="form-label">Organisme payeur <span class="text-danger">*</span></label>
        <select name="insurance_company_id" class="form-control" required>
            <option value="">— Choisir —</option>
            @foreach($organismes as $o)
                <option value="{{ $o->id }}" @selected((int) old('insurance_company_id', $contrat?->insurance_company_id) === $o->id)>{{ $o->name }}</option>
            @endforeach
        </select></div>
    <div class="col-md-6"><label class="form-label">Entreprise souscriptrice</label>
        <select name="entreprise_id" class="form-control">
            <option value="">Aucune — contrat individuel / familial</option>
            @foreach($entreprises as $e)
                <option value="{{ $e->id }}" @selected((int) old('entreprise_id', $contrat?->entreprise_id) === $e->id)>{{ $e->nom }}</option>
            @endforeach
        </select></div>
    <div class="col-md-4"><label class="form-label">N° de police <span class="text-danger">*</span></label>
        <input name="numero_police" class="form-control" required maxlength="100" value="{{ old('numero_police', $contrat?->numero_police) }}"></div>
    <div class="col-md-8"><label class="form-label">Libellé</label>
        <input name="libelle" class="form-control" maxlength="255" placeholder="Ex. Contrat santé groupe 2026" value="{{ old('libelle', $contrat?->libelle) }}"></div>
    <div class="col-md-4"><label class="form-label">Début <span class="text-danger">*</span></label>
        <input type="date" name="date_debut" class="form-control" required value="{{ old('date_debut', $contrat?->date_debut?->toDateString()) }}"></div>
    <div class="col-md-4"><label class="form-label">Fin</label>
        <input type="date" name="date_fin" class="form-control" value="{{ old('date_fin', $contrat?->date_fin?->toDateString()) }}"></div>
    <div class="col-md-4"><label class="form-label">Statut</label>
        <select name="statut" class="form-control">
            @foreach(\App\Enums\Assurance\StatutCouverture::cases() as $s)
                <option value="{{ $s->value }}" @selected(old('statut', $contrat?->statut?->value ?? 'active') === $s->value)>{{ $s->libelle() }}</option>
            @endforeach
        </select></div>
    <div class="col-12"><label class="form-label">Notes</label>
        <textarea name="notes" class="form-control" rows="2">{{ old('notes', $contrat?->notes) }}</textarea></div>
</div>
