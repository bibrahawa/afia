@php($f = fn ($k) => "parametres[{$i}][normes][{$j}][{$k}]")
<div class="row g-1 norme-row mb-1 align-items-center">
    <div class="col-md-1"><select name="{{ $f('sexe') }}" class="form-select form-select-sm"><option value="">Tous</option><option value="M" @selected(($n['sexe'] ?? '') === 'M')>H</option><option value="F" @selected(($n['sexe'] ?? '') === 'F')>F</option></select></div>
    <div class="col-md-1"><input name="{{ $f('age_min_jours') }}" value="{{ $n['age_min_jours'] ?? '' }}" class="form-control form-control-sm" placeholder="âge min j"></div>
    <div class="col-md-1"><input name="{{ $f('age_max_jours') }}" value="{{ $n['age_max_jours'] ?? '' }}" class="form-control form-control-sm" placeholder="âge max j"></div>
    <div class="col-md-1"><div class="form-check"><input class="form-check-input" type="checkbox" name="{{ $f('grossesse') }}" value="1" @checked(! empty($n['grossesse']))><label class="form-check-label small">Gross.</label></div></div>
    <div class="col-md-1"><input name="{{ $f('min') }}" value="{{ $n['min'] ?? '' }}" class="form-control form-control-sm" placeholder="min"></div>
    <div class="col-md-1"><input name="{{ $f('max') }}" value="{{ $n['max'] ?? '' }}" class="form-control form-control-sm" placeholder="max"></div>
    <div class="col-md-1"><input name="{{ $f('critique_min') }}" value="{{ $n['critique_min'] ?? '' }}" class="form-control form-control-sm" placeholder="crit. min"></div>
    <div class="col-md-1"><input name="{{ $f('critique_max') }}" value="{{ $n['critique_max'] ?? '' }}" class="form-control form-control-sm" placeholder="crit. max"></div>
    <div class="col-md-2"><input name="{{ $f('valeur_attendue') }}" value="{{ $n['valeur_attendue'] ?? '' }}" class="form-control form-control-sm" placeholder="valeur attendue (Négatif)"></div>
    <div class="col-md-1"><input name="{{ $f('texte_affiche') }}" value="{{ $n['texte_affiche'] ?? '' }}" class="form-control form-control-sm" placeholder="texte"></div>
    <div class="col-md-1"><button type="button" class="btn btn-sm btn-link text-danger" data-action="supprimer-norme"><i class="fa fa-times"></i></button></div>
</div>
