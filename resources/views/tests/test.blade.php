@extends('layouts.backend')

@php
    $gnf = fn ($v) => number_format((float) $v, 0, ',', ' ');
    $types = ['BIOCHIMIE' => 'Biochimie', 'IMMUNOLOGIE' => 'Immunologie', 'INFECTOLOGIE' => 'Infectiologie', 'HEMATOLOGIE' => 'Hématologie', 'HEMOSTASE' => 'Hémostase', 'BACTERIOLOGIE' => 'Bactériologie', 'PARASITOLOGIE' => 'Parasitologie'];
    $laboActif = \App\Support\EtablissementContext::current()?->aModule('laboratoire');
    // Examens déjà reliés au catalogue du laboratoire (envoi en un clic depuis la consultation).
    $relies = $laboActif ? \App\Models\Labo\LaboExamen::whereNotNull('test_id')->pluck('test_id')->flip() : collect();
@endphp

@section('content')
<div class="container"><div class="page-inner hl">
    <header class="hl-entete">
        <div>
            <h1>Examens prescrits</h1>
            <p>Les examens que le médecin peut prescrire en consultation, avec leur prix.</p>
        </div>
        @can('test.create')
            <div class="hl-entete-actions">
                @if(Route::has('catalogue.import'))<a href="{{ route('catalogue.import', ['type' => 'examens']) }}" class="hl-bouton"><i class="fas fa-file-import" aria-hidden="true"></i> Importer depuis Excel</a>@endif
                <button type="button" class="hl-bouton hl-bouton-plein" data-bs-toggle="modal" data-bs-target="#addRowModal"><i class="fa fa-plus" aria-hidden="true"></i> Nouvel examen</button>
            </div>
        @endcan
    </header>

    @include('partials.catalogue')

    @if($laboActif && Route::has('labo.catalogue.index'))
        <p class="hl-note hl-note-info mb-3"><i class="fas fa-flask" aria-hidden="true"></i> <span>Pour qu'un examen prescrit parte au laboratoire en un clic, reliez-le à l'examen correspondant dans <a href="{{ route('labo.catalogue.index') }}">Laboratoire › Catalogue</a> (champ « Test de l'ancien catalogue »).</span></p>
    @endif

    <section class="hl-bloc">
        <div class="cat-outils">
            <label class="cat-recherche mb-0">
                <span class="sr-only visually-hidden">Rechercher</span>
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" class="form-control js-cat-filtre" data-cible="#catListe" data-vide="#catAucun" data-filtre="#catType" placeholder="Rechercher un examen…" autocomplete="off">
            </label>
            <select id="catType" class="form-control" aria-label="Type">
                <option value="">Tous les types</option>
                @foreach($types as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach
            </select>
        </div>

        @if($tests->isEmpty())
            <div class="hl-vide"><i class="fas fa-vial" aria-hidden="true"></i>Aucun examen.</div>
        @else
            <div class="table-responsive">
                <table class="cat-table">
                    <thead><tr><th>Examen</th><th>Type</th>@if($laboActif)<th>Laboratoire</th>@endif<th class="cat-n">Prix</th><th></th></tr></thead>
                    <tbody id="catListe">
                    @foreach($tests as $test)
                        <tr data-recherche="{{ $test->name }} {{ $test->description }}" data-groupe="{{ $test->report_type }}">
                            <td><span class="cat-nom">{{ $test->name }}</span>@if($test->description)<span class="cat-sous">{{ \Illuminate\Support\Str::limit($test->description, 80) }}</span>@endif</td>
                            <td><span class="cat-etiquette">{{ $types[$test->report_type] ?? ucfirst(mb_strtolower((string) $test->report_type)) }}</span></td>
                            @if($laboActif)
                                <td>@if($relies->has($test->id))<span class="hl-statut hl-s-succes">Relié</span>@else<span class="cat-sous">non relié</span>@endif</td>
                            @endif
                            <td class="cat-n">@if((float) $test->amount > 0)<span class="cat-prix">{{ $gnf($test->amount) }} <small>GNF</small></span>@else<span class="cat-zero">0 GNF</span>@endif</td>
                            <td class="cat-actions">
                                @can('test.edit')
                                    <button type="button" class="cat-icone edit-button" title="Modifier" aria-label="Modifier {{ $test->name }}"
                                            data-info="{{ json_encode($test->only(['id', 'name', 'amount', 'description', 'report_type'])) }}"><i class="fa fa-pen"></i></button>
                                @endcan
                                @can('test.delete')
                                    <button type="button" class="cat-icone est-risque delete-button" title="Supprimer" aria-label="Supprimer {{ $test->name }}" data-id="{{ $test->id }}" data-name="{{ $test->name }}"><i class="fa fa-trash"></i></button>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="hl-vide" id="catAucun" hidden>Aucun examen ne correspond.</div>
        @endif
    </section>

    @foreach([['addRowModal', 'addTestForm', route('test.store'), 'Nouvel examen', 'Ajouter', 'addRowButton', 'addLoader', ''], ['editRowModal', 'editTestForm', route('test.edit'), "Modifier l'examen", 'Enregistrer', 'editRowButton', 'editLoader', 'edit_']] as [$idModal, $idForm, $action, $titre, $bouton, $idBouton, $idLoader, $p])
        <div class="modal fade cat-modal" id="{{ $idModal }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <form class="modal-content" id="{{ $idForm }}" action="{{ $action }}" method="POST">
                    @csrf
                    @if($p)<input type="hidden" id="edit_id" name="edit_id">@endif
                    <div class="modal-header"><h5 class="modal-title">{{ $titre }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
                    <div class="modal-body">
                        <div><label class="cat-l" for="{{ $p }}name">Nom</label><input id="{{ $p }}name" name="name" type="text" class="form-control" placeholder="Glycémie à jeun, NFS…" required></div>
                        <div class="cat-deux">
                            <div><label class="cat-l" for="{{ $p }}report_type">Type</label>
                                <select class="form-control" name="report_type" id="{{ $p }}report_type">@foreach($types as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select></div>
                            <div><label class="cat-l" for="{{ $p }}amount">Prix</label>
                                <div class="cat-montant"><input type="number" min="0" step="1" id="{{ $p }}amount" name="amount" class="form-control" required inputmode="numeric"><span>GNF</span></div></div>
                        </div>
                        <div><label class="cat-l" for="{{ $p }}description">Description</label><textarea id="{{ $p }}description" name="description" class="form-control" rows="2" placeholder="Préparation du patient, délai…"></textarea></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="hl-bouton" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" id="{{ $idBouton }}" class="hl-bouton hl-bouton-plein">{{ $bouton }} <span class="spinner-border spinner-border-sm" role="status" id="{{ $idLoader }}" style="display:none"></span></button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach

    <div class="modal fade cat-modal" id="deleteRowModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form class="modal-content" id="deleteTestForm" action="{{ route('test.delete') }}" method="POST">
                @csrf @method('DELETE')
                <input type="hidden" id="delete_id" name="id">
                <div class="modal-header"><h5 class="modal-title">Supprimer l'examen</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
                <div class="modal-body"><p class="mb-0" id="test_name_to_delete"></p></div>
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
    var val = function (id, v) { document.getElementById(id).value = v == null ? '' : v; };
    document.querySelectorAll('.edit-button').forEach(function (b) {
        b.addEventListener('click', function () {
            var t = JSON.parse(b.dataset.info);
            val('edit_id', t.id); val('edit_name', t.name); val('edit_amount', Math.round(parseFloat(t.amount || 0)));
            val('edit_description', t.description); val('edit_report_type', t.report_type);
            modal('editRowModal').show();
        });
    });
    document.querySelectorAll('.delete-button').forEach(function (b) {
        b.addEventListener('click', function () {
            val('delete_id', b.dataset.id);
            document.getElementById('test_name_to_delete').textContent = 'Supprimer « ' + b.dataset.name + ' » ? Un examen déjà prescrit ne peut pas être supprimé.';
            modal('deleteRowModal').show();
        });
    });
    [['addTestForm', 'addRowButton', 'addLoader'], ['editTestForm', 'editRowButton', 'editLoader'], ['deleteTestForm', 'deleteRowButton', 'deleteLoader']].forEach(function (t) {
        document.getElementById(t[0]).addEventListener('submit', function () { document.getElementById(t[1]).disabled = true; document.getElementById(t[2]).style.display = 'inline-block'; });
    });
})();
</script>
@endsection
