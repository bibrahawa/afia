{{--
    Champs d'un forfait (création et modification).
    $package : forfait ou null ; $departments, $services, $tests.
    Remplace les listes « selectpicker » (extension jQuery) par des cases à cocher filtrables :
    plus lisible, et les actes restent visibles même sans département choisi.
--}}
@php
    $package = $package ?? null;
    $gnf = fn ($v) => number_format((float) $v, 0, ',', ' ');
    $servicesChoisis = collect(old('services', $package?->services->pluck('id')->all() ?? []))->map(fn ($v) => (int) $v)->all();
    $testsChoisis = collect(old('tests', $package?->tests->pluck('id')->all() ?? []))->map(fn ($v) => (int) $v)->all();
    $sommeActuelle = $package ? $package->services->sum('amount') + $package->tests->sum('amount') : 0;
    $prixSaisi = old('prix_forfait', $package && abs((float) $package->price - $sommeActuelle) >= 1 ? (int) round((float) $package->price) : '');
    $servicesParDep = $services->groupBy('department_id');
@endphp
<div class="pk-form">
    <div class="pk-deux">
        <div><label class="cat-l" for="pkNom">Nom du forfait</label>
            <input name="name" type="text" id="pkNom" class="form-control" value="{{ old('name', $package?->name) }}" placeholder="Accouchement simple, bilan prénatal…" required></div>
        <div><label class="cat-l" for="pkDep">Département</label>
            <select name="department_id" id="pkDep" class="form-control">
                <option value="">—</option>
                @foreach($departments as $dep)<option value="{{ $dep->id }}" @selected((int) old('department_id', $package?->department_id) === $dep->id)>{{ $dep->name }}</option>@endforeach
            </select></div>
    </div>
    <div class="pk-deux">
        <div><label class="cat-l" for="pkFamille">Famille d'actes (assurances)</label>
            <select name="famille_acte" id="pkFamille" class="form-control">
                @foreach(\App\Enums\Assurance\FamilleActe::cases() as $f)<option value="{{ $f->value }}" @selected(old('famille_acte', $package?->famille_acte ?? 'soins') === $f->value)>{{ $f->libelle() }}</option>@endforeach
            </select>
            <p class="cat-aide">Un forfait accouchement se classe en « Maternité ».</p></div>
        <div><label class="cat-l" for="pkDescription">Description</label>
            <textarea name="description" id="pkDescription" class="form-control" rows="2">{{ old('description', $package?->description) }}</textarea></div>
    </div>

    <div class="pk-listes">
        <div class="pk-liste">
            <div class="pk-liste-haut"><span class="cat-l mb-0">Actes inclus</span><input type="search" class="form-control form-control-sm pk-filtre" placeholder="Filtrer…" aria-label="Filtrer les actes"></div>
            <div class="pk-options">
                @foreach($departments as $dep)
                    @continue(! $servicesParDep->has($dep->id))
                    <p class="pk-groupe" data-dep="{{ $dep->id }}">{{ $dep->name }}</p>
                    @foreach($servicesParDep[$dep->id] as $s)
                        <label class="pk-option" data-dep="{{ $dep->id }}" data-texte="{{ mb_strtolower($s->name) }}">
                            <input type="checkbox" name="services[]" value="{{ $s->id }}" data-prix="{{ (float) $s->amount }}" @checked(in_array($s->id, $servicesChoisis, true))>
                            <span>{{ $s->name }}</span><b>{{ $gnf($s->amount) }}</b>
                        </label>
                    @endforeach
                @endforeach
                @foreach($servicesParDep->get('', collect()) as $s)
                    <label class="pk-option" data-dep="0" data-texte="{{ mb_strtolower($s->name) }}">
                        <input type="checkbox" name="services[]" value="{{ $s->id }}" data-prix="{{ (float) $s->amount }}" @checked(in_array($s->id, $servicesChoisis, true))>
                        <span>{{ $s->name }}</span><b>{{ $gnf($s->amount) }}</b>
                    </label>
                @endforeach
            </div>
        </div>
        <div class="pk-liste">
            <div class="pk-liste-haut"><span class="cat-l mb-0">Examens inclus</span><input type="search" class="form-control form-control-sm pk-filtre" placeholder="Filtrer…" aria-label="Filtrer les examens"></div>
            <div class="pk-options">
                @foreach($tests as $t)
                    <label class="pk-option" data-texte="{{ mb_strtolower($t->name) }}">
                        <input type="checkbox" name="tests[]" value="{{ $t->id }}" data-prix="{{ (float) $t->amount }}" @checked(in_array($t->id, $testsChoisis, true))>
                        <span>{{ $t->name }}</span><b>{{ $gnf($t->amount) }}</b>
                    </label>
                @endforeach
            </div>
        </div>
    </div>

    <div class="pk-prix">
        <div><span class="cat-sous">Somme des actes et examens</span><strong id="pkSomme">0 GNF</strong></div>
        <div style="max-width:240px"><label class="cat-l" for="pkPrix">Prix du forfait</label>
            <div class="cat-montant"><input type="number" min="0" step="1" name="prix_forfait" id="pkPrix" class="form-control" value="{{ $prixSaisi }}" placeholder="= somme" inputmode="numeric"><span>GNF</span></div>
            <p class="cat-aide" id="pkRemise">Vide : le forfait coûte la somme de ses éléments.</p></div>
    </div>
</div>

@once
<style>
    .pk-form { display: grid; gap: 14px; }
    .pk-deux { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .pk-listes { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .pk-liste { display: grid; grid-template-rows: auto 1fr; border: 1px solid var(--hali-bordure); border-radius: 12px; overflow: hidden; }
    .pk-liste-haut { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 10px 12px; border-bottom: 1px solid var(--hali-bordure); background: #fafbfc; }
    .pk-liste-haut input { max-width: 150px; }
    .pk-options { max-height: 260px; overflow-y: auto; padding: 6px; }
    .pk-groupe { margin: 8px 6px 4px; color: var(--hali-discret); font-size: .72rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; }
    .pk-option { display: flex; align-items: center; gap: 10px; margin: 0; padding: 8px; border-radius: 8px; font-size: .86rem; cursor: pointer; }
    .pk-option:hover { background: var(--hali-primaire-pale); }
    .pk-option:has(input:checked) { background: var(--hali-primaire-pale); color: var(--hali-primaire-fonce); font-weight: 600; }
    .pk-option input { width: 17px; height: 17px; flex: none; accent-color: var(--hali-primaire); }
    .pk-option span { flex: 1; }
    .pk-option b { color: var(--hali-discret); font-size: .78rem; font-variant-numeric: tabular-nums; }
    .pk-prix { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 12px; padding: 14px 16px; border-radius: 12px; background: var(--hali-primaire-pale); }
    .pk-prix strong { display: block; color: var(--hali-encre); font-size: 1.3rem; font-variant-numeric: tabular-nums; }
    @media (max-width: 767.98px) { .pk-deux, .pk-listes { grid-template-columns: 1fr; } }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.pk-form').forEach(function (f) {
        var fmt = function (n) { return new Intl.NumberFormat('fr-FR').format(Math.round(n)) + ' GNF'; };
        var somme = f.querySelector('#pkSomme'), prix = f.querySelector('#pkPrix'), remise = f.querySelector('#pkRemise');
        function maj() {
            var total = 0;
            f.querySelectorAll('.pk-option input:checked').forEach(function (c) { total += parseFloat(c.dataset.prix || 0); });
            somme.textContent = fmt(total);
            var p = parseFloat(prix.value);
            remise.textContent = isNaN(p) ? 'Vide : le forfait coûte la somme de ses éléments.'
                : (p < total ? 'Remise de ' + fmt(total - p) + ' sur la somme.' : (p > total ? 'Supérieur de ' + fmt(p - total) + ' à la somme.' : 'Égal à la somme.'));
        }
        f.addEventListener('change', maj); prix.addEventListener('input', maj);
        f.querySelectorAll('.pk-filtre').forEach(function (champ) {
            champ.addEventListener('input', function () {
                var t = champ.value.trim().toLowerCase(), liste = champ.closest('.pk-liste');
                liste.querySelectorAll('.pk-option').forEach(function (o) { o.hidden = t && o.dataset.texte.indexOf(t) === -1; });
                liste.querySelectorAll('.pk-groupe').forEach(function (g) {
                    g.hidden = !liste.querySelector('.pk-option[data-dep="' + g.dataset.dep + '"]:not([hidden])');
                });
            });
        });
        maj();
    });
});
</script>
@endonce
