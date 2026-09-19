@extends('layouts.backend')

@section('style')
<style>
    .dp-grille { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 12px; padding: 18px; }
    .dp-carte { display: grid; gap: 12px; padding: 16px; border: 1px solid var(--hali-bordure); border-radius: 12px; background: #fff; }
    .dp-haut { display: flex; align-items: center; gap: 12px; }
    .dp-icone { display: grid; place-items: center; width: 42px; height: 42px; flex: none; border-radius: 12px; background: var(--hali-primaire-pale); color: var(--hali-primaire); font-weight: 800; }
    .dp-nom { color: var(--hali-encre); font-size: 1rem; font-weight: 700; }
    .dp-chiffres { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; }
    .dp-chiffres a, .dp-chiffres span { display: grid; gap: 1px; padding: 8px; border-radius: 9px; background: #f9fafb; color: var(--hali-discret); font-size: .72rem; text-align: center; text-decoration: none; }
    .dp-chiffres a:hover { background: var(--hali-primaire-pale); color: var(--hali-primaire-fonce); }
    .dp-chiffres b { color: var(--hali-encre); font-size: 1.1rem; }
    .dp-actions { display: flex; justify-content: flex-end; gap: 4px; }
</style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">
    <header class="hl-entete">
        <div>
            <h1>Départements</h1>
            <p>Les services de la clinique : chacun a ses médecins, ses actes et ses motifs de rendez-vous.</p>
        </div>
        @can('department.create')
            <div class="hl-entete-actions"><button type="button" class="hl-bouton hl-bouton-plein" data-bs-toggle="modal" data-bs-target="#addRowModal"><i class="fa fa-plus" aria-hidden="true"></i> Nouveau département</button></div>
        @endcan
    </header>

    @include('partials.catalogue')

    <section class="hl-bloc">
        @if($departments->isEmpty())
            <div class="hl-vide"><i class="fas fa-sitemap" aria-hidden="true"></i>Aucun département. Commencez par « Médecine générale ».</div>
        @else
            <div class="dp-grille">
                @foreach($departments as $department)
                    <article class="dp-carte">
                        <div class="dp-haut">
                            <span class="dp-icone" aria-hidden="true">{{ mb_strtoupper(mb_substr($department->name, 0, 1)) }}</span>
                            <span class="dp-nom">{{ ucfirst(mb_strtolower($department->name)) }}</span>
                        </div>
                        <div class="dp-chiffres">
                            @can('service.view')<a href="{{ route('service.index') }}"><b>{{ $department->services_count }}</b>acte{{ $department->services_count > 1 ? 's' : '' }}</a>@else<span><b>{{ $department->services_count }}</b>actes</span>@endcan
                            <span><b>{{ $department->employees_count }}</b>employé{{ $department->employees_count > 1 ? 's' : '' }}</span>
                            @can('motif_rdv.view')<a href="{{ route('motifs-rdv.index') }}"><b>{{ $department->motifs_rdv_count }}</b>motif{{ $department->motifs_rdv_count > 1 ? 's' : '' }}</a>@else<span><b>{{ $department->motifs_rdv_count }}</b>motifs</span>@endcan
                        </div>
                        <div class="dp-actions">
                            @can('department.edit')
                                <button type="button" class="cat-icone edit-button" title="Renommer" aria-label="Renommer {{ $department->name }}" data-id="{{ $department->id }}" data-name="{{ $department->name }}"><i class="fa fa-pen"></i></button>
                            @endcan
                            @can('department.delete')
                                @if($department->services_count + $department->employees_count === 0)
                                    <button type="button" class="cat-icone est-risque delete-button" title="Supprimer" aria-label="Supprimer {{ $department->name }}" data-id="{{ $department->id }}" data-name="{{ $department->name }}"><i class="fa fa-trash"></i></button>
                                @endif
                            @endcan
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <div class="modal fade cat-modal" id="addRowModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
            <form class="modal-content" id="addDepartmentForm" action="{{ route('department.add') }}" method="POST">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Nouveau département</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
                <div class="modal-body"><div><label class="cat-l" for="addNom">Nom</label><input type="text" id="addNom" name="name" class="form-control" placeholder="Pédiatrie, gynécologie…" required></div></div>
                <div class="modal-footer">
                    <button type="button" class="hl-bouton" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" id="addRowButton" class="hl-bouton hl-bouton-plein">Ajouter <span class="spinner-border spinner-border-sm" role="status" id="addLoader" style="display:none"></span></button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade cat-modal" id="editRowModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
            <form class="modal-content" id="editDepartmentForm" action="{{ route('department.update') }}" method="POST">
                @csrf
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header"><h5 class="modal-title">Renommer le département</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
                <div class="modal-body"><div><label class="cat-l" for="edit_name">Nom</label><input type="text" id="edit_name" name="name" class="form-control" required></div></div>
                <div class="modal-footer">
                    <button type="button" class="hl-bouton" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" id="editRowButton" class="hl-bouton hl-bouton-plein">Enregistrer <span class="spinner-border spinner-border-sm" role="status" id="editLoader" style="display:none"></span></button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade cat-modal" id="deleteRowModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
            <form class="modal-content" id="deleteDepartmentForm" action="{{ route('department.delete', ['id' => '']) }}" method="POST">
                @csrf @method('DELETE')
                <input type="hidden" id="delete_id" name="id">
                <div class="modal-header"><h5 class="modal-title">Supprimer le département</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
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
            document.getElementById('edit_id').value = b.dataset.id; document.getElementById('edit_name').value = b.dataset.name;
            modal('editRowModal').show();
        });
    });
    document.querySelectorAll('.delete-button').forEach(function (b) {
        b.addEventListener('click', function () {
            document.getElementById('delete_id').value = b.dataset.id;
            document.getElementById('department_name_to_delete').textContent = 'Supprimer le département « ' + b.dataset.name + ' » ?';
            document.getElementById('deleteDepartmentForm').action = '/department/delete/' + b.dataset.id;
            modal('deleteRowModal').show();
        });
    });
    [['addDepartmentForm', 'addRowButton', 'addLoader'], ['editDepartmentForm', 'editRowButton', 'editLoader'], ['deleteDepartmentForm', 'deleteRowButton', 'deleteLoader']].forEach(function (t) {
        document.getElementById(t[0]).addEventListener('submit', function () { document.getElementById(t[1]).disabled = true; document.getElementById(t[2]).style.display = 'inline-block'; });
    });
})();
</script>
@endsection
