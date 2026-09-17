@extends('layouts.backend')
@section('style') @include('labo.partials.styles')
<style>.param-card { border-left: 3px solid var(--aprosafe-primary, #087f6b); } .norme-row input, .norme-row select { font-size: .8rem; }</style>
@endsection

@php
    $parametres = old('parametres') !== null
        ? collect(old('parametres'))
        : $examen->parametres->map(fn ($p) => [
            'id' => $p->id, 'code' => $p->code, 'libelle' => $p->libelle, 'groupe' => $p->groupe,
            'type_resultat' => $p->type_resultat->value, 'unite' => $p->unite, 'decimales' => $p->decimales,
            'formule' => $p->formule, 'options' => implode("\n", $p->options ?? []),
            'obligatoire' => $p->obligatoire, 'imprimable' => $p->imprimable,
            'normes' => $p->valeursReference->map->only(['sexe', 'age_min_jours', 'age_max_jours', 'grossesse', 'min', 'max', 'critique_min', 'critique_max', 'valeur_attendue', 'texte_affiche'])->all(),
        ]);
@endphp

@section('content')
<div class="container"><div class="page-inner">
    @include('labo.partials.entete', ['titre' => $examen->exists ? 'Modifier : ' . $examen->nom : 'Nouvel examen', 'fil' => [route('labo.catalogue.index') => 'Catalogue']])

    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <form method="POST" action="{{ $examen->exists ? route('labo.catalogue.examens.update', $examen) : route('labo.catalogue.examens.store') }}">@csrf
        @if($examen->exists) @method('PUT') @endif

        <div class="card"><div class="card-header"><h4 class="card-title">Examen</h4></div><div class="card-body">
            <div class="row g-3">
                <div class="col-md-3"><label class="form-label">Section</label>
                    <select name="section_id" class="form-select" required>@foreach($sections as $s)<option value="{{ $s->id }}" @selected(old('section_id', $examen->section_id) == $s->id)>{{ $s->nom }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label">Code</label><input name="code" value="{{ old('code', $examen->code) }}" class="form-control" required></div>
                <div class="col-md-5"><label class="form-label">Nom</label><input name="nom" value="{{ old('nom', $examen->nom) }}" class="form-control" required></div>
                <div class="col-md-2"><label class="form-label">Abréviation</label><input name="abreviation" value="{{ old('abreviation', $examen->abreviation) }}" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Type</label>
                    <select name="type_examen" class="form-select">@foreach($typesExamen as $t)<option value="{{ $t->value }}" @selected(old('type_examen', $examen->type_examen?->value ?? 'standard') === $t->value)>{{ ucfirst($t->value) }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label">Échantillon</label>
                    <select name="type_echantillon" class="form-select">@foreach($typesEchantillon as $v => $l)<option value="{{ $v }}" @selected(old('type_echantillon', $examen->type_echantillon ?? 'sang') === $v)>{{ $l }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label">Contenant</label>
                    <select name="tube" class="form-select"><option value="">—</option>@foreach($tubes as $v => $l)<option value="{{ $v }}" @selected(old('tube', $examen->tube) === $v)>{{ $l }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label">Méthode</label><input name="methode" value="{{ old('methode', $examen->methode) }}" class="form-control"></div>
                <div class="col-md-2"><label class="form-label">Prix (GNF)</label><input type="number" step="1" min="0" name="prix" value="{{ old('prix', (float) $examen->prix) }}" class="form-control" required></div>
                <div class="col-md-2"><label class="form-label">Délai rendu (h)</label><input type="number" min="1" name="delai_rendu_heures" value="{{ old('delai_rendu_heures', $examen->delai_rendu_heures) }}" class="form-control" required></div>
                <div class="col-md-2"><label class="form-label">Volume (mL)</label><input type="number" step="0.1" name="volume_ml" value="{{ old('volume_ml', $examen->volume_ml) }}" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Instructions patient</label><input name="instructions_patient" value="{{ old('instructions_patient', $examen->instructions_patient) }}" class="form-control"></div>
                <div class="col-md-3 d-flex gap-3 align-items-end">
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="a_jeun" value="1" id="ajeun" @checked(old('a_jeun', $examen->a_jeun))><label class="form-check-label" for="ajeun">À jeun</label></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="actif" value="1" id="actif" @checked(old('actif', $examen->actif ?? true))><label class="form-check-label" for="actif">Actif</label></div>
                </div>
                <div class="col-md-3 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="sous_traite" value="1" id="st" @checked(old('sous_traite', $examen->sous_traite))><label class="form-check-label" for="st">Sous-traité</label></div></div>
                <div class="col-md-6"><label class="form-label">Laboratoire sous-traitant</label><input name="laboratoire_sous_traitant" value="{{ old('laboratoire_sous_traitant', $examen->laboratoire_sous_traitant) }}" class="form-control"></div>
            </div>
        </div></div>

        <div class="card"><div class="card-header d-flex align-items-center"><h4 class="card-title">Paramètres et normes</h4>
            <button type="button" class="btn btn-sm btn-outline-primary ms-auto" id="ajouterParam"><i class="fa fa-plus"></i> Paramètre</button></div>
            <div class="card-body" id="parametres">
                <p class="small text-muted">Normes : laissez sexe/âge vides pour « tous ». Âges en jours (1 an = 365). Pour un enfant sans plage pédiatrique, le résultat affichera « norme non définie » — c'est volontaire. Formules : codes des paramètres, ex. <code>CT - HDL - TG / 2.2</code>.</p>
                @foreach($parametres as $i => $p)
                    @include('labo.catalogue._parametre', ['i' => $i, 'p' => $p, 'typesResultat' => $typesResultat])
                @endforeach
            </div>
        </div>

        <div class="d-flex gap-2 mb-4">
            <button class="btn btn-primary">Enregistrer</button>
            <a href="{{ route('labo.catalogue.index') }}" class="btn btn-link">Annuler</a>
        </div>
    </form>

    <template id="modeleParam">@include('labo.catalogue._parametre', ['i' => '__I__', 'p' => ['type_resultat' => 'numerique', 'decimales' => 1, 'imprimable' => true, 'normes' => []], 'typesResultat' => $typesResultat])</template>
    <template id="modeleNorme">@include('labo.catalogue._norme', ['i' => '__I__', 'j' => '__J__', 'n' => []])</template>
</div></div>
@endsection

@section('script')
<script>
(function () {
    const conteneur = document.getElementById('parametres');
    let compteur = Date.now();
    document.getElementById('ajouterParam').addEventListener('click', () => {
        conteneur.insertAdjacentHTML('beforeend', document.getElementById('modeleParam').innerHTML.replaceAll('__I__', 'n' + (compteur++)));
    });
    conteneur.addEventListener('click', e => {
        const b = e.target.closest('button[data-action]'); if (!b) return;
        const carte = b.closest('.param-card');
        if (b.dataset.action === 'supprimer-param') carte.remove();
        if (b.dataset.action === 'supprimer-norme') b.closest('.norme-row').remove();
        if (b.dataset.action === 'ajouter-norme') {
            carte.querySelector('.normes').insertAdjacentHTML('beforeend', document.getElementById('modeleNorme').innerHTML.replaceAll('__I__', carte.dataset.index).replaceAll('__J__', 'n' + (compteur++)));
        }
    });
})();
</script>
@endsection
