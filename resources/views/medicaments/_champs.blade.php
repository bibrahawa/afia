{{-- Champs d'un médicament. $p : préfixe des identifiants (« », « edit_ »). $m : médicament ou null. --}}
@php
    $formes = ['COMPRIMÉ' => 'Comprimé', 'GÉLULE' => 'Gélule', 'SIROP' => 'Sirop', 'INJECTION' => 'Injection', 'PERFUSION' => 'Perfusion', 'CRÈME' => 'Crème',
               'POMMADE' => 'Pommade', 'SUPPOSITOIRE' => 'Suppositoire', 'INHALATEUR' => 'Inhalateur', 'GOUTTES' => 'Gouttes', 'SPRAY' => 'Spray'];
    $m = $m ?? null;
@endphp
<div><label class="cat-l" for="{{ $p }}nom">Nom et dosage</label>
    <input type="text" name="nom" id="{{ $p }}nom" class="form-control" value="{{ old('nom', $m?->nom) }}" placeholder="Paracétamol, Amoxicilline…" required></div>
<div class="cat-deux">
    <div><label class="cat-l" for="{{ $p }}forme">Forme</label>
        <select name="forme" id="{{ $p }}forme" class="form-control">
            @foreach($formes as $v => $l)<option value="{{ $v }}" @selected(old('forme', $m?->forme) === $v)>{{ $l }}</option>@endforeach
        </select></div>
    <div><label class="cat-l" for="{{ $p }}dosage">Dosage</label><input type="text" name="dosage" id="{{ $p }}dosage" class="form-control" value="{{ old('dosage', $m?->dosage) }}" placeholder="500 mg"></div>
</div>
<div class="cat-deux">
    <div><label class="cat-l" for="{{ $p }}frequence">Posologie habituelle</label><input type="text" name="frequence" id="{{ $p }}frequence" class="form-control" value="{{ old('frequence', $m?->frequence) }}" placeholder="3 fois par jour"></div>
    <div><label class="cat-l" for="{{ $p }}duree">Durée habituelle</label><input type="text" name="duree" id="{{ $p }}duree" class="form-control" value="{{ old('duree', $m?->duree) }}" placeholder="5 jours"></div>
</div>
<div><label class="cat-l" for="{{ $p }}instructions">Instructions</label><textarea name="instructions" id="{{ $p }}instructions" class="form-control" rows="2" placeholder="À prendre pendant le repas">{{ old('instructions', $m?->instructions) }}</textarea></div>
<div style="max-width:220px"><label class="cat-l" for="{{ $p }}amount">Prix unitaire</label>
    <div class="cat-montant"><input type="number" min="0" step="1" name="amount" id="{{ $p }}amount" class="form-control" value="{{ old('amount', $m ? (int) round((float) $m->amount) : '') }}" inputmode="numeric" placeholder="0"><span>GNF</span></div>
    <p class="cat-aide">Laissez vide si la clinique ne vend pas ce médicament.</p></div>
<p class="cat-aide mb-0">La posologie et la durée pré-remplissent l'ordonnance ; le médecin peut les modifier.</p>
