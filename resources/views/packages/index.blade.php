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
                <button class="btn btn-primary btn-round ms-auto"
                  data-bs-toggle="modal"
                  data-bs-target="#addRowModal">
                  <i class="fa fa-plus"></i> Ajouter un package
                </button>
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
                                        <button  type="button"
                                            class="btn btn-warning btn-round btn-sm edit-button"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editRowModal"
                                            data-info="{{$package->id}},{{$package->name}},{{$package->department_id}}, {{ $package->tests->pluck('id') }},{{$package->services->pluck('id') }}"
                                        >
                                            <i class="fa fa-edit"></i>
                                        </button>

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
                                <p class="small">Créez ou modifiez un package en remplissant le formulaire ci-dessous.</p>
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
                                                    <option value="">Sélectionnez un département</option>
                                                    @foreach ($departments as $dep)
                                                        <option value="{{$dep->id}}">{{ $dep->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('department') <span class="text-danger">{{ $message }}</span> @enderror
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
                                            <div class="form-group">
                                                <label>Ajouter des services:</label>
                                                <select name="services[]" id="add_services" class="form-control selectpicker" data-live-search="true" title="Sélectionnez les services" multiple>
                                                </select>
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
                                        <button type="submit" class="btn btn-primary">
                                            Sauvegarder
                                        </button>
                                        <a href="{{ route('package.index') }}" class="btn btn-secondary">Annuler</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="editRowModal" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-body">
                                <form id="editPackageForm" action="{{ route('package.store') }}" method="POST">
                                    @csrf
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <div class="form-group form-group-default">
                                                <label>Nom du package</label>
                                                <input name="name" type="text" id="edit_name" class="form-control" placeholder="Entrez le nom" required/>
                                                @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                                            </div>
                                        </div>

                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label>Départements:</label>
                                                <select name="department_id" id="edit_department_id" class="form-control selectpicker" data-live-search="true" title="Sélectionnez un département">
                                                    <option value="">Sélectionnez un département</option>
                                                    @foreach ($departments as $dep)
                                                        <option value="{{$dep->id}}">{{ $dep->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('department') <span class="text-danger">{{ $message }}</span> @enderror
                                            </div>
                                        </div>

                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label>Ajouter des examens:</label>
                                                {{-- Utilisation de select multiple pour les tests --}}
                                                <select name="tests[]" id="edit_tests" class="form-control selectpicker" data-live-search="true" title="Sélectionnez les examens" multiple>
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
                                            <div class="form-group">
                                                <label>Ajouter des services:</label>
                                                <select name="services[]" id="edit_services" class="form-control selectpicker" data-live-search="true" title="Sélectionnez les services" multiple>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-sm-12">
                                            <div class="form-group form-group-default">
                                                <label>Description</label>
                                                <textarea name="description" id="edit_description" class="form-control" placeholder="Description"></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal-footer border-0">
                                        <button type="submit" class="btn btn-primary">
                                            Modifier
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
    $(document).ready(function() {



        $('#deletePackageForm').on('submit', function() {
            $('#deleteRowButton').prop('disabled', true);
            $('#deleteLoader').show();
        });

    });

    // Données des départements et services depuis Laravel
        const departments = @json($departments);
        const services = @json($services);

    $(document).ready(function() {
        // Initialiser les selectpickers
        $('.selectpicker').selectpicker();

        // Écouteur d'événement pour le changement de département
        $('#add_department_id').on('changed.bs.select', function() {
            const selectedDepartmentId = $(this).val();

            // Filtrer et mettre à jour les services
            updateServices(selectedDepartmentId);
        });

        // Fonction pour mettre à jour les services
        function updateServices(departmentId) {
            const serviceSelect = $('#add_services');

            serviceSelect.selectpicker('destroy');
            serviceSelect.html('');
            serviceSelect.empty();
            serviceSelect[0].innerHTML = '';

            if (departmentId) {
                const filteredServices = services.filter(service =>
                    parseInt(service.department_id) === parseInt(departmentId)
                );

                filteredServices.forEach(service => {
                    serviceSelect.append(`<option value="${service.id}">${service.name}</option>`);
                });
            }

            serviceSelect.selectpicker({
                liveSearch: true,
                multipleSeparator: ', '
            });
        }

        // Optionnel : Déclencher le filtrage au chargement de la page si un département est déjà sélectionné
        const initialDepartmentId = $('#add_department_id').val();
        if (initialDepartmentId) {
            updateServices(initialDepartmentId);
        }
    });

    $(document).on('click', '.delete-button', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');

        $('#package_name_to_delete').text("Voulez-vous vraiment supprimer le package : " + name + " ?");
        $('#delete_id').val(id);
        $('#deletePackageForm').attr('action', '/package/delete/' + id);

        $('#deleteRowModal').modal('show');
    });

        // Écouteur d'événement pour le bouton d'édition
    $(document).on('click', '.edit-button', function() {

        var info = $(this).data('info').split(',');
        var packageId = info[0];
        var packageName = info[1];
        var departmentId = info[2];
        var amount = info[3];

        // Mettre à jour l'URL du formulaire d'édition
        $('#editPackageForm').attr('action', '/package/update/' + packageId);

        // Mettre à jour les champs du formulaire
        $('#edit_name').val(packageName);
        $('#edit_department_id').val(departmentId);
        $('#edit_department_id').selectpicker('refresh');

        alert(info[4].split(','));
        // Mettre à jour les tests sélectionnés
        $('#edit_tests').val(info[4] ? info[4].split(',') : []);
        $('#edit_tests').selectpicker('refresh');

        // Mettre à jour les services sélectionnés
        $('#edit_services').val(info[5] ? info[5].split(',') : []);
        $('#edit_services').selectpicker('refresh');

        // Afficher la modale d'édition
        $('#editRowModal').modal('show');
    });

</script>


@endsection
