@extends('layouts.backend')

@php
    $gnf = fn ($v) => number_format((float) $v, 0, ',', ' ');
    $familles = \App\Enums\Assurance\FamilleActe::cases();
    $libelleFamille = fn ($v) => \App\Enums\Assurance\FamilleActe::tryFrom((string) $v)?->libelle() ?? '—';
    $sansPrix = $services->filter(fn ($s) => $s->actif && (float) $s->amount <= 0)->count();
    $nbMasques = $services->where('actif', false)->count();
@endphp

@section('content')
<div class="container"><div class="page-inner hl">
    <header class="hl-entete">
        <div>
            <h1>Actes et services</h1>
            <p>Ce que la clinique facture : consultations, soins, échographies… Le prix s'applique aux nouvelles factures.</p>
        </div>
        @can('service.create')
            <div class="hl-entete-actions">
                @if(Route::has('catalogue.import'))<a href="{{ route('catalogue.import', ['type' => 'actes']) }}" class="hl-bouton"><i class="fas fa-file-import" aria-hidden="true"></i> Importer depuis Excel</a>@endif
                <button type="button" class="hl-bouton hl-bouton-plein" data-bs-toggle="modal" data-bs-target="#addRowModal"><i class="fa fa-plus" aria-hidden="true"></i> Nouvel acte</button>
            </div>
        @endcan
    </header>

    @include('partials.catalogue')

    @if($sansPrix > 0)
        <p class="hl-note hl-note-alerte mb-3"><i class="fas fa-tag" aria-hidden="true"></i> <span><strong>{{ $sansPrix }} acte{{ $sansPrix > 1 ? 's' : '' }} sans prix</strong> : ils seraient facturés 0 GNF.</span></p>
    @endif

    <section class="hl-bloc">
        <div class="cat-outils">
            <label class="cat-recherche mb-0">
                <span class="sr-only visually-hidden">Rechercher</span>
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" class="form-control js-cat-filtre" data-cible="#catListe" data-vide="#catAucun" data-filtre="#catDepartement" placeholder="Rechercher un acte…" autocomplete="off">
            </label>
            <select id="catDepartement" class="form-control" aria-label="Département">
                <option value="">Tous les départements</option>
                @foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
            </select>
            @if($nbMasques)<label class="cat-masques"><input type="checkbox" id="catMasques"> Afficher les masqués ({{ $nbMasques }})</label>@endif
        </div>

        @if($services->isEmpty())
            <div class="hl-vide"><i class="fas fa-stethoscope" aria-hidden="true"></i>Aucun acte. Ajoutez au moins une consultation pour pouvoir facturer.</div>
        @else
            <div class="table-responsive">
                <table class="cat-table">
                    <thead><tr><th>Acte</th><th>Département</th><th>Famille (assurances)</th><th class="cat-n">Prix</th><th></th></tr></thead>
                    <tbody id="catListe">
                    @foreach($services as $service)
                        <tr data-recherche="{{ $service->name }} {{ $service->department?->name }}" data-groupe="{{ $service->department_id }}" data-masque="{{ $service->actif ? 0 : 1 }}">
                            <td><span class="cat-nom">{{ $service->name }}</span>@unless($service->actif) <span class="hl-statut hl-s-neutre">Masqué</span>@endunless</td>
                            <td>{{ $service->department?->name ?? '—' }}</td>
                            <td><span class="cat-etiquette">{{ $libelleFamille($service->famille_acte) }}</span></td>
                            <td class="cat-n">
                                @if((float) $service->amount > 0)<span class="cat-prix">{{ $gnf($service->amount) }} <small>GNF</small></span>@else<span class="cat-zero">0 GNF</span>@endif
                            </td>
                            <td class="cat-actions">
                                @can('service.edit')
                                    {{-- CORRIGÉ : les données passaient en « id,nom,… » séparés par des virgules :
                                         un nom contenant une virgule décalait tous les champs de la modification. --}}
                                    <button type="button" class="cat-icone edit-button" title="Modifier" aria-label="Modifier {{ $service->name }}"
                                            data-id="{{ $service->id }}" data-name="{{ $service->name }}" data-department="{{ $service->department_id }}"
                                            data-amount="{{ (float) $service->amount }}" data-famille="{{ $service->famille_acte ?? 'consultation' }}"><i class="fa fa-pen"></i></button>
                                @endcan
                                @can('service.edit')
                                    @if(Route::has('service.visibilite'))
                                        <form method="POST" action="{{ route('service.visibilite', $service) }}">@csrf @method('PATCH')
                                            <button type="submit" class="cat-icone" title="{{ $service->actif ? 'Masquer : ne plus proposer' : 'Proposer à nouveau' }}" aria-label="{{ $service->actif ? 'Masquer' : 'Réafficher' }} {{ $service->name }}"><i class="fa {{ $service->actif ? 'fa-eye-slash' : 'fa-eye' }}"></i></button>
                                        </form>
                                    @endif
                                @endcan
                                @can('service.delete')
                                    <button type="button" class="cat-icone est-risque delete-button" title="Supprimer" aria-label="Supprimer {{ $service->name }}"
                                            data-id="{{ $service->id }}" data-name="{{ $service->name }}"><i class="fa fa-trash"></i></button>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="hl-vide" id="catAucun" hidden>Aucun acte ne correspond.</div>
        @endif
    </section>

    {{-- ================================ Ajouter --}}
    <div class="modal fade cat-modal" id="addRowModal" tabindex="-1" aria-labelledby="catAjout" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form class="modal-content" id="addDepartmentForm" action="{{ route('service.add') }}" method="POST">
                @csrf
                <div class="modal-header"><h5 class="modal-title" id="catAjout">Nouvel acte</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
                <div class="modal-body">
                    <div><label class="cat-l" for="name">Nom</label><input id="name" name="name" type="text" class="form-control" placeholder="Consultation générale, échographie pelvienne…" required></div>
                    <div class="cat-deux">
                        <div><label class="cat-l" for="addDep">Département</label>
                            <select class="form-control" name="department_id" id="addDep" required>
                                @foreach($departments as $department)<option value="{{ $department->id }}">{{ $department->name }}</option>@endforeach
                            </select></div>
                        <div><label class="cat-l" for="addPrix">Prix</label>
                            <div class="cat-montant"><input type="number" min="0" step="1" name="amount" id="addPrix" class="form-control" placeholder="100000" required inputmode="numeric"><span>GNF</span></div></div>
                    </div>
                    <div><label class="cat-l" for="addFamille">Famille d'actes</label>
                        <select class="form-control" name="famille_acte" id="addFamille">
                            @foreach($familles as $f)<option value="{{ $f->value }}" @selected($f === \App\Enums\Assurance\FamilleActe::Consultation)>{{ $f->libelle() }}</option>@endforeach
                        </select>
                        <p class="cat-aide">Sert aux garanties des assurances : un contrat peut couvrir les consultations à 80 % et la maternité à 100 %.</p></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="hl-bouton" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" id="addRowButton" class="hl-bouton hl-bouton-plein">Ajouter <span class="spinner-border spinner-border-sm" role="status" id="addLoader" style="display:none"></span></button>
                </div>
            </form>
        </div>
    </div>

    {{-- ================================ Modifier --}}
    <div class="modal fade cat-modal" id="editRowModal" tabindex="-1" aria-labelledby="catModif" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form class="modal-content" id="editDepartmentForm" action="{{ route('service.update') }}" method="POST">
                @csrf
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header"><h5 class="modal-title" id="catModif">Modifier l'acte</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
                <div class="modal-body">
                    <div><label class="cat-l" for="edit_name">Nom</label><input id="edit_name" name="name" type="text" class="form-control" required></div>
                    <div class="cat-deux">
                        <div><label class="cat-l" for="edit_department_id">Département</label>
                            <select class="form-control" name="department_id" id="edit_department_id" required>
                                @foreach($departments as $department)<option value="{{ $department->id }}">{{ $department->name }}</option>@endforeach
                            </select></div>
                        <div><label class="cat-l" for="edit_amount">Prix</label>
                            <div class="cat-montant"><input type="number" min="0" step="1" id="edit_amount" name="amount" class="form-control" required inputmode="numeric"><span>GNF</span></div></div>
                    </div>
                    <div><label class="cat-l" for="edit_famille_acte">Famille d'actes</label>
                        <select class="form-control" name="famille_acte" id="edit_famille_acte">
                            @foreach($familles as $f)<option value="{{ $f->value }}">{{ $f->libelle() }}</option>@endforeach
                        </select></div>
                    <p class="cat-aide mb-0">Un nouveau prix ne change pas les factures déjà émises.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="hl-bouton" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" id="editRowButton" class="hl-bouton hl-bouton-plein">Enregistrer <span class="spinner-border spinner-border-sm" role="status" id="editLoader" style="display:none"></span></button>
                </div>
            </form>
        </div>
    </div>

    {{-- ================================ Supprimer --}}
    <div class="modal fade cat-modal" id="deleteRowModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form class="modal-content" id="deleteDepartmentForm" action="{{ route('service.delete', ['id' => '']) }}" method="POST">
                @csrf @method('DELETE')
                <input type="hidden" id="delete_id" name="id">
                <div class="modal-header"><h5 class="modal-title">Supprimer l'acte</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
                <div class="modal-body"><p class="mb-0" id="department_name_to_delete"></p></div>
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
            document.getElementById('edit_id').value = b.dataset.id;
            document.getElementById('edit_name').value = b.dataset.name;
            document.getElementById('edit_department_id').value = b.dataset.department;
            document.getElementById('edit_amount').value = Math.round(parseFloat(b.dataset.amount || 0));
            document.getElementById('edit_famille_acte').value = b.dataset.famille || 'consultation';
            modal('editRowModal').show();
        });
    });
    document.querySelectorAll('.delete-button').forEach(function (b) {
        b.addEventListener('click', function () {
            document.getElementById('delete_id').value = b.dataset.id;
            document.getElementById('department_name_to_delete').textContent = 'Supprimer « ' + b.dataset.name + ' » ? Un acte déjà utilisé en consultation ou facturé ne peut pas être supprimé : masquez-le plutôt (icône œil).';
            document.getElementById('deleteDepartmentForm').action = '/service/delete/' + b.dataset.id;
            modal('deleteRowModal').show();
        });
    });
    [['addDepartmentForm', 'addRowButton', 'addLoader'], ['editDepartmentForm', 'editRowButton', 'editLoader'], ['deleteDepartmentForm', 'deleteRowButton', 'deleteLoader']].forEach(function (t) {
        document.getElementById(t[0]).addEventListener('submit', function () { document.getElementById(t[1]).disabled = true; document.getElementById(t[2]).style.display = 'inline-block'; });
    });
})();
</script>
@endsection
