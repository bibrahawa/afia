@extends('layouts.backend')

@section('content')

<div class="container">
    <div class="page-inner">
      <div class="page-header">
        <ul class="breadcrumbs">
          <li class="nav-home">
            <a href="{{url('/')}}">
              <i class="icon-home"></i>
            </a>
          </li>
          <li class="separator">
            <i class="icon-arrow-right"></i>
          </li>
          <li class="nav-item">
            <a href="{{ url('/') }}">Admin</a>
          </li>
          <li class="separator">
            <i class="icon-arrow-right"></i>
          </li>
          <li class="nav-item">
            <a href="{{ route('package.index') }}">Package</a>
          </li>
        </ul>
      </div>

      <div class="row">
        <div class="col-md-12">
          <div class="card">
            <div class="card-header">
              <div class="d-flex align-items-center">
                <h4 class="card-title">Liste des packages</h4>
                {{-- Bouton "Ajouter un package" qui redirige vers la page de création --}}
                @can('package.create')
                    <button class="btn btn-primary btn-round ms-auto"
                    data-bs-toggle="modal"
                    data-bs-target="#addRowModal">
                    <i class="fa fa-plus"></i> Ajouter un package
                    </button>
                @endcan
              </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table id="add-row" class="display table table-striped table-hover">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th style="width: 10%">ID</th>
                                <th>Package Name</th>
                                <th>Tests</th>
                                <th>Services</th> {{-- Ajouté pour la cohérence --}}
                                <th>Price</th>
                                <th style="width: 10%">Actions</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <th>ID</th>
                                <th>Package Name</th>
                                <th>Tests</th>
                                <th>Services</th>
                                <th>Price</th>
                                <th>Actions</th>
                            </tr>
                        </tfoot>
                        <tbody>
                            @foreach($packages as $package)
                                <tr>
                                    <td>{{ $package->id}}</td>
                                    <td>{{ $package->name}}</td>
                                    <td>
                                        @foreach($package->tests as $test)
                                            <li> {{$test->name}} </li>
                                        @endforeach
                                    </td>
                                    <td>
                                        @foreach($package->services as $service)
                                            <li> {{$service->name}} </li>
                                        @endforeach
                                    </td>
                                    <td>{{ number_format($package->price, 2)}}</td>
                                    <td>
                                        <div class="form-button-action">
                                            @can('package.edit')
                                                <a  class="btn btn-warning btn-round btn-sm edit-button"
                                                href="{{ route('package.edit', $package->id) }}"><i class="fa fa-edit"></i></a>
                                            @endcan
                                            @can('package.delete')
                                                <button
                                                    type="button"
                                                    class="btn btn-danger btn-round btn-sm delete-button"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteRowModal"
                                                    data-id="{{$package->id}}"
                                                    data-name="{{$package->name}}"
                                                >
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="modal fade" id="addRowModal" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-body">
                                <p class="small">Créez un package en remplissant le formulaire ci-dessous.</p>
                                <form id="addPackageForm" action="{{ route('package.store') }}" method="POST">
                                    @csrf
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <div class="form-group form-group-default">
                                                <label>Nom du package</label>
                                                <input name="name" type="text" id="name" class="form-control" placeholder="Entrez le nom" required/>
                                                @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                                            </div>
                                        </div>

                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label>Départements:</label>
                                                <select name="department_id" id="add_department_id" class="form-control selectpicker" data-live-search="true" title="Sélectionnez un département">
                                                    @foreach ($departments as $dep)
                                                        <option value="{{ $dep->id }}">{{ $dep->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('department') <span class="text-danger">{{ $message }}</span> @enderror
                                            </div>
                                        </div>

                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label>Ajouter des services:</label>
                                                <select name="services[]" id="add_services" class="form-control selectpicker" data-live-search="true" title="Sélectionnez les services" multiple></select>
                                            </div>
                                        </div>

                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label>Ajouter des examens:</label>
                                                {{-- Utilisation de select multiple pour les tests --}}
                                                <select name="tests[]" id="add_tests" class="form-control selectpicker" data-live-search="true" title="Sélectionnez les examens" multiple>
                                                    @foreach($tests as $test)
                                                        <option value="{{ $test->id }}">
                                                            {{ $test->name }} = {{ number_format($test->amount, 0, ',', ' ') }} FG
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('tests') <span class="text-danger">{{ $message }}</span> @enderror
                                            </div>
                                        </div>
                                        <div class="col-sm-12">
                                            <div class="form-group form-group-default">
                                                <label>Description</label>
                                                <textarea name="description" class="form-control" placeholder="Description"></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal-footer border-0">
                                        <button type="submit" class="btn btn-primary" id="addRowButton" form="addPackageForm">
                                            Sauvegarder
                                            <div class="spinner-border spinner-border-sm text-light" role="status" id="addLoader" style="display: none;">
                                                <span class="sr-only">Loading...</span>
                                            </div>
                                        </button>
                                        <a href="{{ route('package.index') }}" class="btn btn-secondary">Annuler</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="deleteRowModal" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header border-0">
                                <h5 class="modal-title">Êtes-vous sûr de vouloir supprimer ce package ?</h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form id="deletePackageForm" action="#" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <p id="package_name_to_delete"></p>
                                    <input type="hidden" id="delete_id" name="id">
                                </form>
                            </div>
                            <div class="modal-footer border-0">
                                <button type="submit" class="btn btn-danger" id="deleteRowButton" form="deletePackageForm">
                                    Supprimer
                                    <div class="spinner-border spinner-border-sm text-light" role="status" id="deleteLoader" style="display: none;">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                </button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    Annuler
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
      </div>
    </div>
</div>

@endsection

@section('script')
<script type="text/javascript">
    const departments = @json($departments);
    const services = @json($services);
    const tests = @json($tests);

    $(document).ready(function () {
        $('.selectpicker').selectpicker();

        $('#editPackageForm').on('submit', function () {
            $('#editRowButton').prop('disabled', true);
            $('#editLoader').show();
        });

        // Fonction universelle : charger les services selon le département
        function updateServices(selectId, departmentId, selectedIds = []) {
            const select = $(selectId);

            // Vider correctement avant remplissage
            select.empty(); // plus sûr que .html('')
            select.selectpicker('destroy'); // détruire l'ancienne instance
            select.html(''); // nettoyer s'il reste du HTML résiduel

            const filtered = services.filter(s => s.department_id == departmentId);
            const added = new Set();

            filtered.forEach(service => {
                if (!added.has(service.id)) {
                    const selected = selectedIds.includes(service.id) ? 'selected' : '';
                    select.append(`<option value="${service.id}" ${selected}>${service.name}</option>`);
                    added.add(service.id);
                }
            });

            // Reinitialiser le selectpicker proprement
            select.selectpicker(); // recrée le selectpicker
        }

        // ✅ Lorsqu’on change le département en mode "ajout"
        $('#add_department_id').on('changed.bs.select', function () {
            const depId = parseInt($(this).val());
            updateServices('#add_services', depId);
        });

        // ✅ Lorsqu’on change le département en mode "modification"
        $('#edit_department_id').on('changed.bs.select', function () {
            const depId = parseInt($(this).val());
            updateServices('#edit_services', depId);
        });

        // ✅ Nettoyer les champs au moment de fermer le modal (facultatif mais propre)
        $('#editRowModal').on('hidden.bs.modal', function () {
            $('#edit_department_id').html('').selectpicker('refresh');
            $('#edit_services').html('').selectpicker('refresh');
            $('#edit_tests').html('').selectpicker('refresh');
        });

        // ✅ Supprimer un package
        $(document).on('click', '.delete-button', function () {
            const id = $(this).data('id');
            const name = $(this).data('name');
            $('#package_name_to_delete').text("Voulez-vous vraiment supprimer le package : " + name + " ?");
            $('#delete_id').val(id);
            $('#deletePackageForm').attr('action', '/package/delete/' + id);
            $('#deleteRowModal').modal('show');
        });

        $('#deletePackageForm').on('submit', function () {
            $('#deleteRowButton').prop('disabled', true);
            $('#deleteLoader').show();
        });

        $('#addPackageForm').on('submit', function () {
            $('#addRowButton').prop('disabled', true);
            $('#addLoader').show();
        });
    });
</script>
@endsection
