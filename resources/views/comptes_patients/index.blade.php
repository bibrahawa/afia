@extends('layouts.backend')

@section('content')
<div class="container">
    <div class="page-inner">
      <div class="page-header">
        <ul class="breadcrumbs">
          <li class="nav-home"><a href="{{url('/')}}"><i class="icon-home"></i></a></li>
          <li class="separator"><i class="icon-arrow-right"></i></li>
          <li class="nav-item"><a href="{{ route('comptes-patients.index') }}">Comptes patients</a></li>
        </ul>
      </div>

      <div class="row">
        <div class="col-md-12">
          <div class="card">
            <div class="card-header">
              <div class="d-flex align-items-center">
                <h4 class="card-title">Comptes portail patient</h4>
                <button class="btn btn-primary btn-round ms-auto" data-bs-toggle="modal" data-bs-target="#addCompteModal">
                    <i class="fa fa-plus"></i> Nouveau compte
                </button>
              </div>
            </div>
            <div class="card-body">
                <p class="small text-muted">
                    Un compte est la connexion portail (téléphone + OTP), distincte du dossier clinique
                    <code>Patient</code>. Un même compte peut piloter plusieurs dossiers (ses enfants mineurs,
                    un parent en délégation) — voir les dossiers rattachés sous chaque compte.
                </p>
                <table class="display table table-striped table-hover">
                    <thead class="bg-primary text-white">
                        <tr><th>Téléphone</th><th>Email</th><th>Statut</th><th>Dossiers rattachés</th><th style="width:15%">Action</th></tr>
                    </thead>
                    <tbody>
                        @foreach($comptes as $c)
                            <tr>
                                <td>{{ $c->telephone }}</td>
                                <td>{{ $c->email }}</td>
                                <td><span class="badge badge-{{ $c->statut === 'actif' ? 'success' : 'danger' }}">{{ ucfirst($c->statut) }}</span></td>
                                <td>
                                    @foreach($c->patients as $p)
                                        <span class="badge badge-secondary" title="{{ $p->pivot->role }}">
                                            {{ $p->getFullName() }} ({{ $p->pivot->role }})
                                            <form action="{{ route('comptes-patients.detacher', [$c, $p]) }}" method="POST" class="d-inline" onsubmit="return confirm('Détacher ce dossier ?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-link btn-sm p-0 text-white"><i class="fa fa-times"></i></button>
                                            </form>
                                        </span>
                                    @endforeach
                                </td>
                                <td>
                                    <div class="form-button-action">
                                        <button type="button" class="btn btn-success btn-round btn-sm" data-bs-toggle="modal" data-bs-target="#attacherModal-{{ $c->id }}" title="Rattacher un dossier">
                                            <i class="fa fa-link"></i>
                                        </button>
                                        <button type="button" class="btn btn-warning btn-round btn-sm edit-compte"
                                            data-bs-toggle="modal" data-bs-target="#editCompteModal"
                                            data-action="{{ route('comptes-patients.update', $c) }}"
                                            data-telephone="{{ $c->telephone }}" data-email="{{ $c->email }}" data-statut="{{ $c->statut }}">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                        <form action="{{ route('comptes-patients.delete', $c) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer ce compte ? Les dossiers patients seront conservés.');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-round btn-sm"><i class="fa fa-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                            {{-- Modal rattachement, une par ligne --}}
                            <div class="modal fade" id="attacherModal-{{ $c->id }}" tabindex="-1" aria-hidden="true">
                              <div class="modal-dialog"><div class="modal-content">
                                <div class="modal-header border-0"><h5 class="modal-title">Rattacher un dossier — {{ $c->telephone }}</h5>
                                    <button type="button" class="close" data-bs-dismiss="modal"><span>&times;</span></button></div>
                                <form action="{{ route('comptes-patients.attacher', $c) }}" method="POST">
                                    @csrf
                                    <div class="modal-body">
                                        <div class="mb-2"><label>Identifiant national santé du patient</label>
                                            <input type="text" name="identifiant_national_sante" class="form-control" placeholder="ex: GN26A1B2C3" required></div>
                                        <div class="mb-2"><label>Rôle</label>
                                            <select name="role" class="form-control" required>
                                                <option value="titulaire">Titulaire (c'est son propre dossier)</option>
                                                <option value="tuteur">Tuteur (dossier d'un mineur ou délégation)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-0">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                        <button type="submit" class="btn btn-primary">Rattacher</button>
                                    </div>
                                </form>
                              </div></div>
                            </div>
                        @endforeach
                    </tbody>
                </table>

                {{-- Modal Ajout compte --}}
                <div class="modal fade" id="addCompteModal" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog"><div class="modal-content">
                    <div class="modal-header border-0"><h5 class="modal-title">Nouveau compte patient</h5>
                        <button type="button" class="close" data-bs-dismiss="modal"><span>&times;</span></button></div>
                    <form action="{{ route('comptes-patients.add') }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <div class="mb-2"><label>Téléphone</label>
                                <input type="text" name="telephone" class="form-control" required></div>
                            <div class="mb-2"><label>Email (facultatif)</label>
                                <input type="email" name="email" class="form-control"></div>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">Créer</button>
                        </div>
                    </form>
                  </div></div>
                </div>

                {{-- Modal Édition compte --}}
                <div class="modal fade" id="editCompteModal" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog"><div class="modal-content">
                    <div class="modal-header border-0"><h5 class="modal-title">Modifier le compte</h5>
                        <button type="button" class="close" data-bs-dismiss="modal"><span>&times;</span></button></div>
                    <form id="editCompteForm" method="POST">
                        @csrf @method('PUT')
                        <div class="modal-body">
                            <div class="mb-2"><label>Téléphone</label>
                                <input type="text" name="telephone" class="form-control" required></div>
                            <div class="mb-2"><label>Email</label>
                                <input type="email" name="email" class="form-control"></div>
                            <div class="mb-2"><label>Statut</label>
                                <select name="statut" class="form-control" required>
                                    <option value="actif">Actif</option>
                                    <option value="suspendu">Suspendu</option>
                                </select>
                            </div>
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
document.querySelectorAll('.edit-compte').forEach(btn => {
    btn.addEventListener('click', () => {
        const f = document.getElementById('editCompteForm');
        f.action = btn.dataset.action;
        f.querySelector('[name=telephone]').value = btn.dataset.telephone || '';
        f.querySelector('[name=email]').value = btn.dataset.email || '';
        f.querySelector('[name=statut]').value = btn.dataset.statut || 'actif';
    });
});
</script>
@endsection
