<div class="card param-card mb-3" data-index="{{ $i }}">
    <div class="card-body py-2">
        <input type="hidden" name="parametres[{{ $i }}][id]" value="{{ $p['id'] ?? '' }}">
        <div class="row g-2">
            <div class="col-md-2"><label class="form-label small">Code</label><input name="parametres[{{ $i }}][code]" value="{{ $p['code'] ?? '' }}" class="form-control form-control-sm" required></div>
            <div class="col-md-3"><label class="form-label small">Libellé</label><input name="parametres[{{ $i }}][libelle]" value="{{ $p['libelle'] ?? '' }}" class="form-control form-control-sm" required></div>
            <div class="col-md-2"><label class="form-label small">Groupe</label><input name="parametres[{{ $i }}][groupe]" value="{{ $p['groupe'] ?? '' }}" class="form-control form-control-sm"></div>
            <div class="col-md-2"><label class="form-label small">Type</label>
                <select name="parametres[{{ $i }}][type_resultat]" class="form-select form-select-sm">
                    @foreach($typesResultat as $t)<option value="{{ $t->value }}" @selected(($p['type_resultat'] ?? '') === $t->value)>{{ $t->libelle() }}</option>@endforeach
                </select></div>
            <div class="col-md-1"><label class="form-label small">Unité</label><input name="parametres[{{ $i }}][unite]" value="{{ $p['unite'] ?? '' }}" class="form-control form-control-sm"></div>
            <div class="col-md-1"><label class="form-label small">Déc.</label><input type="number" min="0" max="4" name="parametres[{{ $i }}][decimales]" value="{{ $p['decimales'] ?? 1 }}" class="form-control form-control-sm"></div>
            <div class="col-md-1 text-end"><button type="button" class="btn btn-sm btn-link text-danger" data-action="supprimer-param" title="Retirer"><i class="fa fa-trash"></i></button></div>
            <div class="col-md-4"><label class="form-label small">Formule (type calculé)</label><input name="parametres[{{ $i }}][formule]" value="{{ $p['formule'] ?? '' }}" class="form-control form-control-sm"></div>
            <div class="col-md-4"><label class="form-label small">Options (une par ligne)</label><textarea name="parametres[{{ $i }}][options]" rows="1" class="form-control form-control-sm">{{ $p['options'] ?? '' }}</textarea></div>
            <div class="col-md-4 d-flex gap-3 align-items-end">
                <div class="form-check"><input class="form-check-input" type="checkbox" name="parametres[{{ $i }}][obligatoire]" value="1" @checked(! empty($p['obligatoire']))><label class="form-check-label small">Obligatoire</label></div>
                <div class="form-check"><input type="hidden" name="parametres[{{ $i }}][imprimable]" value="0"><input class="form-check-input" type="checkbox" name="parametres[{{ $i }}][imprimable]" value="1" @checked(! empty($p['imprimable']))><label class="form-check-label small">Imprimé</label></div>
            </div>
        </div>
        <div class="normes mt-2">
            @foreach(($p['normes'] ?? []) as $j => $n)
                @include('labo.catalogue._norme', ['i' => $i, 'j' => $j, 'n' => $n])
            @endforeach
        </div>
        <button type="button" class="btn btn-sm btn-link px-0" data-action="ajouter-norme">+ plage de référence</button>
    </div>
</div>
