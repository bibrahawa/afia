@extends('layouts.backend')

@php
    $gnf = fn ($v) => number_format((float) $v, 0, ',', ' ');
    $nbMasques = $medicaments->where('actif', false)->count();
@endphp

@section('content')
<div class="container"><div class="page-inner hl">
    <header class="hl-entete">
        <div>
            <h1>Médicaments</h1>
            <p>La liste où le médecin choisit en rédigeant une ordonnance, avec la posologie proposée par défaut.</p>
        </div>
        @can('medicament.create')
            <div class="hl-entete-actions">
                @if(Route::has('catalogue.import'))<a href="{{ route('catalogue.import', ['type' => 'medicaments']) }}" class="hl-bouton"><i class="fas fa-file-import" aria-hidden="true"></i> Importer depuis Excel</a>@endif
                <button type="button" class="hl-bouton hl-bouton-plein" data-bs-toggle="modal" data-bs-target="#addRowModal"><i class="fa fa-plus" aria-hidden="true"></i> Nouveau médicament</button>
            </div>
        @endcan
    </header>

    @include('partials.catalogue')

    @if($errors->any())<div class="hl-note hl-note-danger mb-3" role="alert"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <section class="hl-bloc">
        <div class="cat-outils">
            <label class="cat-recherche mb-0">
                <span class="sr-only visually-hidden">Rechercher</span>
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" class="form-control js-cat-filtre" data-cible="#catListe" data-vide="#catAucun" data-filtre="#catForme" placeholder="Rechercher un médicament…" autocomplete="off">
            </label>
            <select id="catForme" class="form-control" aria-label="Forme">
                <option value="">Toutes les formes</option>
                @foreach($medicaments->pluck('forme')->filter()->unique()->sort() as $f)<option value="{{ $f }}">{{ ucfirst(mb_strtolower($f)) }}</option>@endforeach
            </select>
            @if($nbMasques)<label class="cat-masques"><input type="checkbox" id="catMasques"> Afficher les masqués ({{ $nbMasques }})</label>@endif
        </div>

        @if($medicaments->isEmpty())
            <div class="hl-vide"><i class="fas fa-pills" aria-hidden="true"></i>Aucun médicament. Ajoutez les plus prescrits pour accélérer les ordonnances.</div>
        @else
            <div class="table-responsive">
                <table class="cat-table">
                    <thead><tr><th>Médicament</th><th>Forme</th><th>Posologie proposée</th><th class="cat-n">Prix</th><th></th></tr></thead>
                    <tbody id="catListe">
                    @foreach($medicaments as $medicament)
                        <tr data-recherche="{{ $medicament->nom }} {{ $medicament->dosage }}" data-groupe="{{ $medicament->forme }}" data-masque="{{ $medicament->actif ? 0 : 1 }}">
                            <td><span class="cat-nom">{{ $medicament->nom }}</span>@unless($medicament->actif) <span class="hl-statut hl-s-neutre">Masqué</span>@endunless @if($medicament->dosage)<span class="cat-sous">{{ $medicament->dosage }}</span>@endif</td>
                            <td>@if($medicament->forme)<span class="cat-etiquette">{{ ucfirst(mb_strtolower($medicament->forme)) }}</span>@endif</td>
                            <td style="font-size:.84rem">{{ collect([$medicament->frequence, $medicament->duree])->filter()->implode(' · ') ?: '—' }}
                                @if($medicament->instructions)<span class="cat-sous">{{ \Illuminate\Support\Str::limit($medicament->instructions, 60) }}</span>@endif</td>
                            <td class="cat-n">@if((float) $medicament->amount > 0)<span class="cat-prix">{{ $gnf($medicament->amount) }} <small>GNF</small></span>@else<span class="cat-sous">non vendu</span>@endif</td>
                            <td class="cat-actions">
                                @can('medicament.edit')
                                    <button type="button" class="cat-icone edit-button" title="Modifier" aria-label="Modifier {{ $medicament->nom }}"
                                            data-info="{{ json_encode($medicament->only(['id', 'nom', 'forme', 'dosage', 'frequence', 'duree', 'amount', 'instructions'])) }}"><i class="fa fa-pen"></i></button>
                                @endcan
                                @can('medicament.edit')
                                    @if(Route::has('medicaments.visibilite'))
                                        <form method="POST" action="{{ route('medicaments.visibilite', $medicament) }}">@csrf @method('PATCH')
                                            <button type="submit" class="cat-icone" title="{{ $medicament->actif ? 'Masquer : ne plus proposer' : 'Proposer à nouveau' }}" aria-label="{{ $medicament->actif ? 'Masquer' : 'Réafficher' }} {{ $medicament->nom }}"><i class="fa {{ $medicament->actif ? 'fa-eye-slash' : 'fa-eye' }}"></i></button>
                                        </form>
                                    @endif
                                @endcan
                                @can('medicament.delete')
                                    <button type="button" class="cat-icone est-risque delete-button" title="Supprimer" aria-label="Supprimer {{ $medicament->nom }}" data-id="{{ $medicament->id }}" data-name="{{ $medicament->nom }}"><i class="fa fa-trash"></i></button>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="hl-vide" id="catAucun" hidden>Aucun médicament ne correspond.</div>
        @endif
    </section>

    <div class="modal fade cat-modal" id="addRowModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form class="modal-content" id="addDepartmentForm" action="{{ route('medicaments.store') }}" method="POST">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Nouveau médicament</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
                <div class="modal-body">@include('medicaments._champs', ['p' => '', 'm' => null])</div>
                <div class="modal-footer">
                    <button type="button" class="hl-bouton" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" id="addRowButton" class="hl-bouton hl-bouton-plein">Ajouter <span class="spinner-border spinner-border-sm" role="status" id="addLoader" style="display:none"></span></button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade cat-modal" id="editRowModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form class="modal-content" id="editMedicamentForm" action="" method="POST">
                @csrf @method('PUT')
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header"><h5 class="modal-title">Modifier le médicament</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
                <div class="modal-body">@include('medicaments._champs', ['p' => 'edit_', 'm' => null])</div>
                <div class="modal-footer">
                    <button type="button" class="hl-bouton" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" id="editRowButton" class="hl-bouton hl-bouton-plein">Enregistrer <span class="spinner-border spinner-border-sm" role="status" id="editLoader" style="display:none"></span></button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade cat-modal" id="deleteRowModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form class="modal-content" id="deleteMedicamentForm" action="#" method="POST">
                @csrf @method('DELETE')
                <div class="modal-header"><h5 class="modal-title">Supprimer le médicament</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
                <div class="modal-body"><p class="mb-0" id="medicament_name_to_delete"></p></div>
                <div class="modal-footer">
                    <button type="button" class="hl-bouton" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" id="deleteRowButton" class="hl-bouton cat-danger">Supprimer <span class="spinner-border spinner-border-sm" role="status" id="deleteLoader" style="display:none"></span></button>
                </div>
            </form>
        </div>
    </div>
</div></div>
@endsection

@section('script')
<script>
(function () {
    var modal = function (id) { return bootstrap.Modal.getOrCreateInstance(document.getElementById(id)); };
    document.querySelectorAll('.edit-button').forEach(function (b) {
        b.addEventListener('click', function () {
            var m = JSON.parse(b.dataset.info);
            ['nom', 'forme', 'dosage', 'frequence', 'duree', 'instructions'].forEach(function (k) { document.getElementById('edit_' + k).value = m[k] || ''; });
            document.getElementById('edit_amount').value = m.amount ? Math.round(parseFloat(m.amount)) : '';
            document.getElementById('edit_id').value = m.id;
            document.getElementById('editMedicamentForm').action = '/medicaments/' + m.id;
            modal('editRowModal').show();
        });
    });
    document.querySelectorAll('.delete-button').forEach(function (b) {
        b.addEventListener('click', function () {
            document.getElementById('medicament_name_to_delete').textContent = 'Supprimer « ' + b.dataset.name + ' » ? Un médicament déjà prescrit ne peut pas être supprimé : masquez-le plutôt (icône œil).';
            document.getElementById('deleteMedicamentForm').action = '/medicaments/' + b.dataset.id;
            modal('deleteRowModal').show();
        });
    });
    [['addDepartmentForm', 'addRowButton', 'addLoader'], ['editMedicamentForm', 'editRowButton', 'editLoader'], ['deleteMedicamentForm', 'deleteRowButton', 'deleteLoader']].forEach(function (t) {
        document.getElementById(t[0]).addEventListener('submit', function () { document.getElementById(t[1]).disabled = true; document.getElementById(t[2]).style.display = 'inline-block'; });
    });
})();
</script>
@endsection
