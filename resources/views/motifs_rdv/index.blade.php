@extends('layouts.backend')

@section('content')
<div class="container">
    <div class="page-inner">
      <div class="page-header">
        <ul class="breadcrumbs">
          <li class="nav-home"><a href="{{url('/')}}"><i class="icon-home"></i></a></li>
          <li class="separator"><i class="icon-arrow-right"></i></li>
          <li class="nav-item"><a href="{{ route('motifs-rdv.index') }}">Motifs de rendez-vous</a></li>
        </ul>
      </div>

      <p class="small text-muted">
          Chaque département a sa propre liste de motifs — pédiatrie et gynécologie ne partagent
          rien ici, même si un nom se ressemble d'un département à l'autre.
      </p>

      @foreach($departments as $department)
      <div class="row">
        <div class="col-md-12">
          <div class="card">
            <div class="card-header">
              <div class="d-flex align-items-center">
                <h4 class="card-title">{{ $department->name }}</h4>
                @can('motif_rdv.create')
                    <button class="btn btn-primary btn-round btn-sm ms-auto" data-bs-toggle="modal"
                        data-bs-target="#addMotifModal-{{ $department->id }}">
                        <i class="fa fa-plus"></i> Motif
                    </button>
                @endcan
              </div>
            </div>
            <div class="card-body">
                @if($department->motifsRdv->isEmpty())
                    <p class="text-muted mb-0">Aucun motif configuré pour ce département.</p>
                @else
                <table class="table table-striped table-hover">
                    <thead><tr><th></th><th>Nom</th><th>Durée</th><th>Marge tampon</th><th>Statut</th><th style="width:15%">Action</th></tr></thead>
                    <tbody>
                        @foreach($department->motifsRdv as $motif)
                            <tr class="{{ $motif->actif ? '' : 'text-muted' }}">
                                <td><span style="display:inline-block;width:12px;height:12px;background:{{ $motif->couleur }};border-radius:2px;"></span></td>
                                <td>{{ $motif->nom }}</td>
                                <td>{{ $motif->duree_minutes_defaut }} min</td>
                                <td>{{ $motif->marge_tampon_minutes }} min</td>
                                <td>
                                    <span class="badge badge-{{ $motif->actif ? 'success' : 'secondary' }}">
                                        {{ $motif->actif ? 'Actif' : 'Désactivé' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="form-button-action">
                                        @can('motif_rdv.edit')
                                            <button type="button" class="btn btn-warning btn-round btn-sm edit-motif"
                                                data-bs-toggle="modal" data-bs-target="#editMotifModal"
                                                data-action="{{ route('motifs-rdv.update', $motif) }}"
                                                data-nom="{{ $motif->nom }}" data-duree="{{ $motif->duree_minutes_defaut }}"
                                                data-marge="{{ $motif->marge_tampon_minutes }}" data-couleur="{{ $motif->couleur }}">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <form action="{{ route('motifs-rdv.toggle', $motif) }}" method="POST" class="d-inline">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="btn btn-secondary btn-round btn-sm" title="Activer/désactiver">
                                                    <i class="fa fa-power-off"></i>
                                                </button>
                                            </form>
                                        @endcan
                                        @can('motif_rdv.delete')
                                            <form action="{{ route('motifs-rdv.delete', $motif) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer ce motif ?');">
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
                @endif
            </div>
          </div>
        </div>
      </div>

      {{-- Modal ajout, une par département --}}
      <div class="modal fade" id="addMotifModal-{{ $department->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog"><div class="modal-content">
          <div class="modal-header border-0"><h5 class="modal-title">Nouveau motif — {{ $department->name }}</h5>
              <button type="button" class="close" data-bs-dismiss="modal"><span>&times;</span></button></div>
          <form action="{{ route('motifs-rdv.add') }}" method="POST">
              @csrf
              <input type="hidden" name="department_id" value="{{ $department->id }}">
              <div class="modal-body">
                  @include('motifs_rdv._form')
              </div>
              <div class="modal-footer border-0">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                  <button type="submit" class="btn btn-primary">Créer</button>
              </div>
          </form>
        </div></div>
      </div>
      @endforeach

      {{-- Modal édition, unique et réutilisée --}}
      <div class="modal fade" id="editMotifModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog"><div class="modal-content">
          <div class="modal-header border-0"><h5 class="modal-title">Modifier le motif</h5>
              <button type="button" class="close" data-bs-dismiss="modal"><span>&times;</span></button></div>
          <form id="editMotifForm" method="POST">
              @csrf @method('PUT')
              <div class="modal-body">
                  @include('motifs_rdv._form')
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

<script>
document.querySelectorAll('.edit-motif').forEach(btn => {
    btn.addEventListener('click', () => {
        const f = document.getElementById('editMotifForm');
        f.action = btn.dataset.action;
        f.querySelector('[name=nom]').value = btn.dataset.nom || '';
        f.querySelector('[name=duree_minutes_defaut]').value = btn.dataset.duree || 15;
        f.querySelector('[name=marge_tampon_minutes]').value = btn.dataset.marge || 5;
        f.querySelector('[name=couleur]').value = btn.dataset.couleur || '#3B82F6';
    });
});
</script>
@endsection
