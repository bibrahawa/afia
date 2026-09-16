@extends('layouts.backend')

@section('content')
<div class="container">
    <div class="page-inner">
      <div class="page-header">
        <ul class="breadcrumbs">
          <li class="nav-home"><a href="{{url('/')}}"><i class="icon-home"></i></a></li>
          <li class="separator"><i class="icon-arrow-right"></i></li>
          <li class="nav-item"><a href="{{ route('module.index') }}">Modules</a></li>
        </ul>
      </div>

      <div class="row">
        <div class="col-md-12">
          <div class="card">
            <div class="card-header">
              <div class="d-flex align-items-center">
                <h4 class="card-title">Catalogue des modules</h4>
                @can('module.create')
                    <button class="btn btn-primary btn-round ms-auto" data-bs-toggle="modal" data-bs-target="#addModuleModal">
                        <i class="fa fa-plus"></i> Nouveau module
                    </button>
                @endcan
              </div>
            </div>
            <div class="card-body">
                <table class="display table table-striped table-hover">
                    <thead class="bg-primary text-white">
                        <tr><th>Code</th><th>Nom</th><th>Description</th><th>Établissements</th><th style="width:15%">Action</th></tr>
                    </thead>
                    <tbody>
                        @foreach($modules as $m)
                            <tr>
                                <td><code>{{ $m->code }}</code></td>
                                <td>{{ $m->nom }}</td>
                                <td>{{ $m->description }}</td>
                                <td>{{ $m->etablissements_count }}</td>
                                <td>
                                    <div class="form-button-action">
                                        @can('module.edit')
                                            <button type="button" class="btn btn-warning btn-round btn-sm edit-module"
                                                data-bs-toggle="modal" data-bs-target="#editModuleModal"
                                                data-action="{{ route('module.update', $m) }}"
                                                data-nom="{{ $m->nom }}" data-description="{{ $m->description }}">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                        @endcan
                                        @can('module.delete')
                                            <form action="{{ route('module.delete', $m) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer ce module ?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-round btn-sm"><i class="fa fa-trash"></i></button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- Modal Ajout --}}
                <div class="modal fade" id="addModuleModal" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog"><div class="modal-content">
                    <div class="modal-header border-0"><h5 class="modal-title">Nouveau module</h5>
                        <button type="button" class="close" data-bs-dismiss="modal"><span>&times;</span></button></div>
                    <form action="{{ route('module.add') }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <div class="mb-2"><label>Code (technique, immuable)</label>
                                <input type="text" name="code" class="form-control" placeholder="ex: laboratoire" required></div>
                            <div class="mb-2"><label>Nom</label>
                                <input type="text" name="nom" class="form-control" required></div>
                            <div class="mb-2"><label>Description</label>
                                <textarea name="description" class="form-control"></textarea></div>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">Créer</button>
                        </div>
                    </form>
                  </div></div>
                </div>

                {{-- Modal Édition (le code n'est pas modifiable, voir contrôleur) --}}
                <div class="modal fade" id="editModuleModal" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog"><div class="modal-content">
                    <div class="modal-header border-0"><h5 class="modal-title">Modifier le module</h5>
                        <button type="button" class="close" data-bs-dismiss="modal"><span>&times;</span></button></div>
                    <form id="editModuleForm" method="POST">
                        @csrf @method('PUT')
                        <div class="modal-body">
                            <div class="mb-2"><label>Nom</label>
                                <input type="text" name="nom" class="form-control" required></div>
                            <div class="mb-2"><label>Description</label>
                                <textarea name="description" class="form-control"></textarea></div>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                        </div>
                    </form>
                  </div></div>
                </div>
            </div>
          </div>
        </div>
      </div>
    </div>
</div>

<script>
document.querySelectorAll('.edit-module').forEach(btn => {
    btn.addEventListener('click', () => {
        const f = document.getElementById('editModuleForm');
        f.action = btn.dataset.action;
        f.querySelector('[name=nom]').value = btn.dataset.nom || '';
        f.querySelector('[name=description]').value = btn.dataset.description || '';
    });
});
</script>
@endsection
