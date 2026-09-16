@extends('layouts.backend')

@section('content')

<div class="container">
    <div class="page-inner">
      <div class="page-header">
        <ul class="breadcrumbs">
          <li class="nav-home"><a href="{{url('/')}}"><i class="icon-home"></i></a></li>
          <li class="separator"><i class="icon-arrow-right"></i></li>
          <li class="nav-item"><a href="{{ route('etablissement.index') }}">Établissements</a></li>
        </ul>
      </div>

      <div class="row">
        <div class="col-md-12">
          <div class="card">
            <div class="card-header">
              <div class="d-flex align-items-center">
                <h4 class="card-title">Établissements clients</h4>
                @can('etablissement.create')
                    <button class="btn btn-primary btn-round ms-auto" data-bs-toggle="modal" data-bs-target="#addEtablissementModal">
                        <i class="fa fa-plus"></i> Nouvel établissement
                    </button>
                @endcan
              </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="display table table-striped table-hover">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th>Nom</th>
                            <th>Type</th>
                            <th>Statut</th>
                            <th>Utilisateurs</th>
                            <th>Patients vus</th>
                            <th style="width: 15%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($etablissements as $e)
                            <tr>
                                <td>{{ $e->nom }}</td>
                                <td><span class="badge badge-info">{{ ucfirst($e->type) }}</span></td>
                                <td>
                                    <span class="badge badge-{{ ['essai'=>'warning','actif'=>'success','suspendu'=>'danger','resilie'=>'secondary'][$e->statut] }}">
                                        {{ ucfirst($e->statut) }}
                                    </span>
                                </td>
                                <td>{{ $e->utilisateurs_count }}</td>
                                <td>{{ $e->patients_count }}</td>
                                <td>
                                    <div class="form-button-action">
                                        @can('etablissement.licence')
                                            <a href="{{ route('etablissement.modules', $e) }}" class="btn btn-info btn-round btn-sm" title="Gérer la licence">
                                                <i class="fa fa-th-large"></i>
                                            </a>
                                        @endcan
                                        @can('etablissement.edit')
                                            <button type="button" class="btn btn-warning btn-round btn-sm edit-etablissement"
                                                data-bs-toggle="modal" data-bs-target="#editEtablissementModal"
                                                data-action="{{ route('etablissement.update', $e) }}"
                                                data-nom="{{ $e->nom }}" data-type="{{ $e->type }}" data-statut="{{ $e->statut }}"
                                                data-adresse="{{ $e->adresse }}" data-contact="{{ $e->contact }}" data-email="{{ $e->email }}">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                        @endcan
                                        @can('etablissement.delete')
                                            <form action="{{ route('etablissement.delete', $e) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer {{ $e->nom }} ?');">
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
                </div>

                {{-- Modal Ajout --}}
                <div class="modal fade" id="addEtablissementModal" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog"><div class="modal-content">
                    <div class="modal-header border-0">
                        <h5 class="modal-title">Nouvel établissement</h5>
                        <button type="button" class="close" data-bs-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <form action="{{ route('etablissement.add') }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            @include('etablissements._form')
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">Créer</button>
                        </div>
                    </form>
                  </div></div>
                </div>

                {{-- Modal Édition --}}
                <div class="modal fade" id="editEtablissementModal" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog"><div class="modal-content">
                    <div class="modal-header border-0">
                        <h5 class="modal-title">Modifier l'établissement</h5>
                        <button type="button" class="close" data-bs-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <form id="editEtablissementForm" method="POST">
                        @csrf @method('PUT')
                        <div class="modal-body">
                            @include('etablissements._form')
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
document.querySelectorAll('.edit-etablissement').forEach(btn => {
    btn.addEventListener('click', () => {
        const f = document.getElementById('editEtablissementForm');
        f.action = btn.dataset.action;
        f.querySelector('[name=nom]').value = btn.dataset.nom || '';
        f.querySelector('[name=type]').value = btn.dataset.type || '';
        f.querySelector('[name=statut]').value = btn.dataset.statut || '';
        f.querySelector('[name=adresse]').value = btn.dataset.adresse || '';
        f.querySelector('[name=contact]').value = btn.dataset.contact || '';
        f.querySelector('[name=email]').value = btn.dataset.email || '';
    });
});
</script>
@endsection
