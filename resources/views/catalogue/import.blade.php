@extends('layouts.backend')

@php
    $libelles = ['actes' => ['Actes et services', 'fa-stethoscope', 'Nom, prix, département, famille'], 'examens' => ['Examens', 'fa-vial', 'Nom, prix, type, description'], 'medicaments' => ['Médicaments', 'fa-pills', 'Nom, forme, dosage, posologie, durée, prix']];
    $statuts = ['nouveau' => ['Ajouté', 'hl-s-succes'], 'maj' => ['Prix mis à jour', 'hl-s-info'], 'existe' => ['Ignoré', 'hl-s-neutre'], 'erreur' => ['Erreur', 'hl-s-danger']];
    $gnf = fn ($v) => $v === null ? '—' : number_format((float) $v, 0, ',', ' ');
    $compte = $analyse['compte'] ?? [];
    $aImporter = ($compte['nouveau'] ?? 0) + ($compte['maj'] ?? 0);
@endphp

@section('style')
<style>
    .im-types { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; }
    .im-type { position: relative; display: grid; gap: 4px; margin: 0; padding: 14px 16px; border: 1.5px solid var(--hali-bordure); border-radius: 12px; background: #fff; cursor: pointer; }
    .im-type input { position: absolute; opacity: 0; }
    .im-type i { color: var(--hali-primaire); font-size: 1.2rem; }
    .im-type strong { color: var(--hali-encre); }
    .im-type span { color: var(--hali-discret); font-size: .78rem; }
    .im-type:has(input:checked) { border-color: var(--hali-primaire); background: var(--hali-primaire-pale); box-shadow: 0 0 0 3px var(--hali-primaire-pale); }
    .im-form { display: grid; gap: 18px; padding: 20px 22px; }
    .im-etape { display: flex; gap: 14px; }
    .im-num { display: grid; place-items: center; width: 30px; height: 30px; flex: none; border-radius: 50%; background: var(--hali-primaire); color: #fff; font-weight: 800; }
    .im-etape > div { flex: 1; min-width: 0; }
    .im-etape h3 { margin: 4px 0 8px; color: var(--hali-encre); font-size: .95rem; font-weight: 700; }
    .im-depot { display: grid; place-items: center; gap: 6px; padding: 26px; border: 2px dashed #cbd5e1; border-radius: 14px; background: #fafbfc; color: var(--hali-discret); text-align: center; cursor: pointer; }
    .im-depot:hover, .im-depot.est-survol { border-color: var(--hali-primaire); background: var(--hali-primaire-pale); }
    .im-depot i { color: var(--hali-primaire); font-size: 1.8rem; }
    .im-depot strong { color: var(--hali-encre); }
    .im-options { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .im-options label.im-l { display: block; margin-bottom: 5px; color: var(--hali-encre); font-size: .83rem; font-weight: 650; }
    .im-resume { display: flex; flex-wrap: wrap; gap: 10px; padding: 16px 18px; border-bottom: 1px solid var(--hali-bordure); }
    .im-resume div { flex: 1 1 140px; padding: 12px 14px; border-radius: 12px; background: #f9fafb; }
    .im-resume b { display: block; font-size: 1.5rem; }
    .im-resume span { color: var(--hali-discret); font-size: .8rem; }
    .im-table { width: 100%; border-collapse: collapse; font-size: .86rem; }
    .im-table th { position: sticky; top: 0; padding: 9px 14px; background: #fafbfc; border-bottom: 1px solid var(--hali-bordure); color: var(--hali-discret); font-size: .76rem; font-weight: 600; text-align: left; }
    .im-table td { padding: 9px 14px; border-top: 1px solid #f3f4f6; vertical-align: top; }
    .im-table tr.est-erreur td { background: #fffafa; }
    .im-table tr.est-existe td { color: var(--hali-discret); }
    .im-table .n { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .im-defile { max-height: 520px; overflow: auto; }
    .im-pied { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; padding: 16px 18px; border-top: 1px solid var(--hali-bordure); background: #fafbfc; border-radius: 0 0 var(--hali-rayon) var(--hali-rayon); }
    @media (max-width: 767.98px) { .im-options { grid-template-columns: 1fr; } }
</style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">
    <header class="hl-entete">
        <div>
            <h1>Importer le catalogue</h1>
            <p>Ajoutez des dizaines d'actes, d'examens ou de médicaments d'un coup depuis un fichier Excel. Rien n'est enregistré avant votre confirmation.</p>
        </div>
    </header>

    @include('partials.catalogue')

    @if($errors->any())<div class="hl-note hl-note-danger mb-3" role="alert"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    @if(! $analyse)
        {{-- ============================ Étape 1 : envoyer le fichier --}}
        <form method="POST" action="{{ route('catalogue.import.analyser') }}" enctype="multipart/form-data" class="hl-bloc" id="imForm" style="max-width:920px">
            @csrf
            <div class="im-form">
                <div class="im-etape"><span class="im-num">1</span><div>
                    <h3>Que voulez-vous importer ?</h3>
                    <div class="im-types">
                        @foreach($types as $t)
                            <label class="im-type"><input type="radio" name="type" value="{{ $t }}" @checked(old('type', $typeChoisi) === $t)>
                                <i class="fas {{ $libelles[$t][1] }}" aria-hidden="true"></i><strong>{{ $libelles[$t][0] }}</strong><span>{{ $libelles[$t][2] }}</span></label>
                        @endforeach
                    </div>
                </div></div>

                <div class="im-etape"><span class="im-num">2</span><div>
                    <h3>Préparez votre fichier</h3>
                    <p class="mb-2" style="font-size:.88rem">Partez de notre modèle, ou de votre propre tableau Excel : les colonnes sont reconnues par leur titre (« Nom », « Prix », « Tarif », « Désignation »…), dans n'importe quel ordre.</p>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($types as $t)
                            <a href="{{ route('catalogue.import.modele', $t) }}" class="hl-bouton" data-modele="{{ $t }}" @if($t !== old('type', $typeChoisi)) hidden @endif><i class="fas fa-download" aria-hidden="true"></i> Télécharger le modèle « {{ mb_strtolower($libelles[$t][0]) }} »</a>
                        @endforeach
                    </div>
                </div></div>

                <div class="im-etape"><span class="im-num">3</span><div>
                    <h3>Envoyez-le</h3>
                    <label class="im-depot" id="imDepot" for="imFichier">
                        <i class="fas fa-file-excel" aria-hidden="true"></i>
                        <strong id="imNom">Choisissez ou déposez votre fichier ici</strong>
                        <span>Excel (.xlsx) ou CSV · 2 000 lignes et 5 Mo au plus</span>
                    </label>
                    <input type="file" name="fichier" id="imFichier" accept=".xlsx,.csv,.txt" hidden required>

                    <div class="im-options mt-3">
                        <div data-pour="actes"><label class="im-l" for="imDep">Département si la colonne est vide</label>
                            <select name="departement_defaut" id="imDep" class="form-control">
                                <option value="">—</option>
                                @foreach($departments as $d)<option value="{{ $d->id }}" @selected((int) old('departement_defaut') === $d->id)>{{ $d->name }}</option>@endforeach
                            </select>
                            <p class="small text-muted mb-0 mt-1">Un département inconnu dans le fichier sera créé.</p></div>
                        <div data-pour="actes"><label class="im-l" for="imFamille">Famille si la colonne est vide</label>
                            <select name="famille_defaut" id="imFamille" class="form-control">
                                @foreach(\App\Enums\Assurance\FamilleActe::cases() as $f)<option value="{{ $f->value }}" @selected(old('famille_defaut', 'consultation') === $f->value)>{{ $f->libelle() }}</option>@endforeach
                            </select></div>
                        <div data-pour="examens"><label class="im-l" for="imTypeExamen">Type si la colonne est vide ou inconnue</label>
                            <select name="type_defaut" id="imTypeExamen" class="form-control">
                                @foreach(\App\Services\Catalogue\ImportCatalogueService::TYPES_EXAMEN as $te)<option value="{{ $te }}" @selected(old('type_defaut', 'BIOCHIMIE') === $te)>{{ ucfirst(mb_strtolower($te)) }}</option>@endforeach
                            </select></div>
                    </div>
                    <label class="d-flex gap-2 mt-3 mb-0" style="font-weight:500">
                        <input type="checkbox" name="mettre_a_jour_prix" value="1" @checked(old('mettre_a_jour_prix')) style="margin-top:3px">
                        <span>Mettre à jour le prix des éléments déjà au catalogue<span class="d-block small text-muted">Sinon ils sont ignorés. Les factures déjà émises ne changent jamais.</span></span>
                    </label>
                </div></div>
            </div>
            <div class="im-pied">
                <span class="small text-muted">Étape suivante : un aperçu ligne par ligne, avant tout enregistrement.</span>
                <button type="submit" class="hl-bouton hl-bouton-plein" id="imEnvoyer">Analyser le fichier <span class="spinner-border spinner-border-sm d-none" id="imAttente" role="status"></span></button>
            </div>
        </form>
    @else
        {{-- ============================ Étape 2 : aperçu --}}
        <section class="hl-bloc">
            <h2 class="hl-bloc-titre">Aperçu · {{ $libelles[$analyse['type']][0] }} <small>{{ $analyse['fichier'] ?? '' }}</small></h2>
            <div class="im-resume">
                <div><b style="color:var(--hali-succes)">{{ $compte['nouveau'] ?? 0 }}</b><span>à ajouter</span></div>
                @if(($compte['maj'] ?? 0) > 0)<div><b style="color:var(--hali-info)">{{ $compte['maj'] }}</b><span>prix à mettre à jour</span></div>@endif
                <div><b style="color:var(--hali-discret)">{{ $compte['existe'] ?? 0 }}</b><span>déjà au catalogue, ignorés</span></div>
                <div><b style="color:{{ ($compte['erreur'] ?? 0) ? 'var(--hali-danger)' : 'var(--hali-discret)' }}">{{ $compte['erreur'] ?? 0 }}</b><span>en erreur, non importés</span></div>
            </div>

            @if(! empty($analyse['nouveaux_departements']))
                <p class="hl-note hl-note-alerte" style="margin:14px 18px 0"><i class="fas fa-sitemap" aria-hidden="true"></i> <span>{{ count($analyse['nouveaux_departements']) }} département(s) seront créés : <strong>{{ implode(', ', $analyse['nouveaux_departements']) }}</strong>. Vérifiez l'orthographe : « Pédiatrie » et « Pediatrie » sont reconnus comme le même, mais pas « Pédiatre ».</span></p>
            @endif
            @if(! $analyse['avec_entete'])
                <p class="hl-note hl-note-info" style="margin:14px 18px 0"><span>Aucun titre de colonne reconnu : les colonnes ont été lues dans l'ordre du modèle.</span></p>
            @endif

            <div class="im-defile" style="margin-top:14px">
                <table class="im-table">
                    <thead><tr><th>Ligne</th><th>Nom</th>@if($analyse['type'] === 'actes')<th>Département</th>@endif<th class="n">Prix</th><th>Résultat</th></tr></thead>
                    <tbody>
                        @foreach($analyse['lignes'] as $l)
                            @php([$libelle, $ton] = $statuts[$l['statut']])
                            <tr class="est-{{ $l['statut'] }}">
                                <td class="n" style="color:var(--hali-discret)">{{ $l['ligne'] }}</td>
                                <td style="font-weight:600">{{ $l['nom'] ?: '—' }}</td>
                                @if($analyse['type'] === 'actes')<td>{{ $l['valeurs']['departement'] ?? '' }}@if(! empty($l['valeurs']['departement_a_creer'])) <span class="hl-statut hl-s-alerte">nouveau</span>@endif</td>@endif
                                <td class="n">{{ $gnf($l['valeurs']['prix'] ?? null) }}</td>
                                <td><span class="hl-statut {{ $ton }}">{{ $libelle }}</span>@if($l['message'])<span class="d-block small" style="color:{{ $l['statut'] === 'erreur' ? 'var(--hali-danger)' : 'var(--hali-discret)' }}">{{ $l['message'] }}</span>@endif</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="im-pied">
                <form method="POST" action="{{ route('catalogue.import.annuler') }}">@csrf<button type="submit" class="hl-bouton">Recommencer avec un autre fichier</button></form>
                @if($aImporter > 0)
                    <form method="POST" action="{{ route('catalogue.import.confirmer') }}" id="imConfirmer">@csrf
                        <button type="submit" class="hl-bouton hl-bouton-plein">Importer {{ $aImporter }} ligne{{ $aImporter > 1 ? 's' : '' }}</button></form>
                @else
                    <span class="small text-muted">Rien à importer : corrigez le fichier puis recommencez.</span>
                @endif
            </div>
        </section>
    @endif
</div></div>
@endsection

@section('script')
<script>
(function () {
    var form = document.getElementById('imForm');
    if (form) {
        var champ = document.getElementById('imFichier'), depot = document.getElementById('imDepot');
        function majType() {
            var t = (form.querySelector('[name=type]:checked') || {}).value;
            form.querySelectorAll('[data-modele]').forEach(function (a) { a.hidden = a.dataset.modele !== t; });
            form.querySelectorAll('[data-pour]').forEach(function (d) { d.hidden = d.dataset.pour !== t; });
        }
        form.querySelectorAll('[name=type]').forEach(function (r) { r.addEventListener('change', majType); });
        majType();
        champ.addEventListener('change', function () { if (champ.files[0]) document.getElementById('imNom').textContent = champ.files[0].name; });
        ['dragenter', 'dragover'].forEach(function (e) { depot.addEventListener(e, function (ev) { ev.preventDefault(); depot.classList.add('est-survol'); }); });
        ['dragleave', 'drop'].forEach(function (e) { depot.addEventListener(e, function (ev) { ev.preventDefault(); depot.classList.remove('est-survol'); }); });
        depot.addEventListener('drop', function (ev) {
            if (ev.dataTransfer.files[0]) { champ.files = ev.dataTransfer.files; document.getElementById('imNom').textContent = champ.files[0].name; }
        });
        form.addEventListener('submit', function () { document.getElementById('imEnvoyer').disabled = true; document.getElementById('imAttente').classList.remove('d-none'); });
    }
    var conf = document.getElementById('imConfirmer');
    if (conf) conf.addEventListener('submit', function () { conf.querySelector('button').disabled = true; });
})();
</script>
@endsection
