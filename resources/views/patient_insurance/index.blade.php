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
            <a href="{{ route('insurance_patient.index') }}">Assurances Patients</a>
          </li>
        </ul>
      </div>

      <div class="row">
        <div class="col-md-12">
          <div class="card">
            <div class="card-header">
              <div class="d-flex align-items-center">
                <h4 class="card-title">Liste des assurances patients</h4>
                @can('patient_insurance.create')
                <button
                  class="btn btn-primary btn-round ms-auto"
                  data-bs-toggle="modal"
                  data-bs-target="#addRowModal"
                >
                  <i class="fa fa-plus"></i> Ajouter une assurance
                </button>
                @endcan
              </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table id="add-row" class="display table table-striped table-hover">
                    <thead class="bg-primary text-white">
                        <tr>
                        <th style="width: 5%">ID</th>
                        <th>Patient</th>
                        <th>Compagnie</th>
                        <th>N°</th>
                        <th>Couverture</th>

                        {{-- <th>Début</th> --}}
                        <th>Periode</th>
                        <th>Statut</th>
                        {{-- <th>Plafond</th> --}}
                        <th>Utilisé</th>
                        <th style="width: 10%">Action</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                        <th>ID</th>
                        <th>Patient</th>
                        <th>Compagnie</th>
                        <th>N°</th>
                        <th>Couverture</th>
                        <th>Validite</th>
                        <th>Statut</th>
                        {{-- <th>Plafond</th> --}}
                        <th>Utilisé</th>
                        <th>Action</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        @foreach($patientInsurances as $insurance)
                            <tr>
                                <td>{{ $insurance->id}}</td>
                                <td>{{ $insurance->patient->first_name }} {{ $insurance->patient->last_name }}</td>
                                <td>{{ $insurance->insuranceCompany->name }}</td>
                                <td>{{ $insurance->policy_number }}</td>
                                <td>{{ $insurance->coverage_percentage }}</td>
                                <td>
                                    {{ \Carbon\Carbon::parse($insurance->start_date)->format('d/m/Y') }} -
                                        @if($insurance->end_date)
                                            <br>au {{ $insurance->end_date ? \Carbon\Carbon::parse($insurance->end_date)->format('d/m/Y') : 'N/A' }}
                                        @endif
                                    <br>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $insurance->status == 'active' ? 'success' : ($insurance->status == 'suspended' ? 'warning' : 'danger') }}">
                                        {{ ucfirst($insurance->status) }}
                                    </span>
                                </td>
                                {{-- <td>{{ $insurance->annual_limit ? number_format($insurance->annual_limit, 0, ',', ' ') . ' GNF' : 'N/A' }}</td> --}}
                                <td>{{ number_format($insurance->used_amount, 0, ',', ' ') }} GNF</td>
                                <td>
                                    <div class="form-button-action">
                                        @can('patient_insurance.edit')
                                            <button
                                                type="button"
                                                class="btn btn-warning btn-round btn-sm edit-button"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editRowModal"
                                                data-info="{{$insurance->id}},{{$insurance->patient_id}},{{$insurance->insurance_company_id}},{{$insurance->policy_number}},{{$insurance->start_date}},{{$insurance->end_date}},{{$insurance->status}},{{$insurance->annual_limit}},{{$insurance->used_amount}},{{$insurance->notes}}, {{$insurance->coverage_percentage}}"
                                            >
                                                <i class="fa fa-edit"></i>
                                            </button>
                                        @endcan
                                        @can('patient_insurance.delete')
                                            <button
                                                type="button"
                                                class="btn btn-danger btn-round btn-sm delete-button"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteRowModal"
                                                data-id="{{$insurance->id}}"
                                                data-name="{{$insurance->patient->first_name}} {{$insurance->patient->last_name}} - {{$insurance->policy_number}}"
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

                <!-- Modal Add -->
                <div class="modal fade" id="addRowModal" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header border-0">
                        <h5 class="modal-title">
                            <span class="fw-mediumbold"> Nouvelle</span>
                            <span class="fw-light"> Assurance Patient</span>
                        </h5>
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        </div>
                        <div class="modal-body">
                        <p class="small">Créez une nouvelle assurance patient en remplissant le formulaire ci-dessous.</p>
                        <form id="addInsuranceForm" action="{{ route('insurance_patient.store') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group form-group-default">
                                        <label>Patient</label>
                                        <select id="patient_id" name="patient_id" class="form-control" required>
                                            <option value="">Sélectionner un patient</option>
                                            @foreach($patients as $patient)
                                                <option value="{{ $patient->id }}">{{ $patient->first_name }} {{ $patient->last_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group form-group-default">
                                        <label>Compagnie d'assurance</label>
                                        <select id="insurance_company_id" name="insurance_company_id" class="form-control" required>
                                            <option value="">Sélectionner une compagnie</option>
                                            @foreach($insuranceCompanies as $company)
                                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group form-group-default">
                                        <label>Numéro de police</label>
                                        <input
                                            id="policy_number"
                                            name="policy_number"
                                            type="text"
                                            class="form-control"
                                            placeholder="Entrez le numéro de police"
                                            required
                                        />
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group form-group-default">
                                        <label>Statut</label>
                                        <select id="status" name="status" class="form-control" required>
                                            <option value="active">Actif</option>
                                            <option value="suspended">Suspendu</option>
                                            <option value="expired">Expiré</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group form-group-default">
                                        <label>Date de début</label>
                                        <input
                                            id="start_date"
                                            name="start_date"
                                            type="date"
                                            class="form-control"
                                            required
                                        />
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group form-group-default">
                                        <label>Date de fin</label>
                                        <input
                                            id="end_date"
                                            name="end_date"
                                            type="date"
                                            class="form-control"
                                        />
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group form-group-default">
                                        <label>Plafond annuel (GNF)</label>
                                        <input
                                            id="annual_limit"
                                            name="annual_limit"
                                            type="number"
                                            step="0.01"
                                            class="form-control"
                                            placeholder="Ex: 1000000"
                                        />
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group form-group-default">
                                        <label>Montant utilisé (GNF)</label>
                                        <input
                                            id="used_amount"
                                            name="used_amount"
                                            type="number"
                                            step="0.01"
                                            class="form-control"
                                            placeholder="Ex: 250000"
                                            value="0"
                                        />
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group form-group-default">
                                        <label>Couverture (%) <span class="text-danger">*</span></label>
                                        <input type="number" name="coverage_percentage" class="form-control" step="0.01" min="0" max="100" required>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group form-group-default">
                                        <label>Notes</label>
                                        <textarea
                                            id="notes"
                                            name="notes"
                                            class="form-control"
                                            placeholder="Notes ou commentaires"
                                            rows="3"
                                        ></textarea>
                                    </div>
                                </div>
                            </div>
                        </form>
                        </div>
                        <div class="modal-footer border-0">
                        <button type="submit" id="addRowButton" class="btn btn-primary" form="addInsuranceForm">
                            Ajouter
                            <div class="spinner-border spinner-border-sm text-light" role="status" id="addLoader" style="display: none;">
                                <span class="sr-only">Loading...</span>
                            </div>
                        </button>

                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">
                            Fermer
                        </button>
                        </div>
                    </div>
                    </div>
                </div>

                <!-- Modal Edit -->
                <div class="modal fade" id="editRowModal" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header border-0">
                                <h5 class="modal-title">
                                    <span class="fw-mediumbold"> Modifier</span>
                                    <span class="fw-light"> Assurance Patient</span>
                                </h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form id='editInsuranceForm' action="{{ route('insurance_patient.update') }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" id="edit_id" name="id" />
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Patient</label>
                                                <select id="edit_patient_id" name="patient_id" class="form-control" required>
                                                    <option value="">Sélectionner un patient</option>
                                                    @foreach($patients as $patient)
                                                        <option value="{{ $patient->id }}">{{ $patient->first_name }} {{ $patient->last_name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Compagnie d'assurance</label>
                                                <select id="edit_insurance_company_id" name="insurance_company_id" class="form-control" required>
                                                    <option value="">Sélectionner une compagnie</option>
                                                    @foreach($insuranceCompanies as $company)
                                                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Numéro de police</label>
                                                <input
                                                    id="edit_policy_number"
                                                    name="policy_number"
                                                    type="text"
                                                    class="form-control"
                                                    placeholder="Entrez le numéro de police"
                                                    required
                                                />
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Statut</label>
                                                <select id="edit_status" name="status" class="form-control" required>
                                                    <option value="active">Actif</option>
                                                    <option value="suspended">Suspendu</option>
                                                    <option value="expired">Expiré</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Date de début</label>
                                                <input
                                                    id="edit_start_date"
                                                    name="start_date"
                                                    type="date"
                                                    class="form-control"
                                                    required
                                                />
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Date de fin</label>
                                                <input
                                                    id="edit_end_date"
                                                    name="end_date"
                                                    type="date"
                                                    class="form-control"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Plafond annuel (GNF)</label>
                                                <input
                                                    id="edit_annual_limit"
                                                    name="annual_limit"
                                                    type="number"
                                                    step="0.01"
                                                    class="form-control"
                                                    placeholder="Ex: 1000000"
                                                />
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Montant utilisé (GNF)</label>
                                                <input
                                                    id="edit_used_amount"
                                                    name="used_amount"
                                                    type="number"
                                                    step="0.01"
                                                    class="form-control"
                                                    placeholder="Ex: 250000"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Couverture (%) <span class="text-danger">*</span></label>
                                                <input type="number" name="coverage_percentage" id="edit_coverage_percentage" class="form-control" step="0.01" min="0" max="100" required>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Notes</label>
                                                <textarea
                                                    id="edit_notes"
                                                    name="notes"
                                                    class="form-control"
                                                    placeholder="Notes ou commentaires"
                                                    rows="3"
                                                ></textarea>
                                            </div>
                                        </div>
                                    </div>
                            </div>
                            <div class="modal-footer border-0">
                                <button type="submit" class="btn btn-success" id="editRowButton" form="editInsuranceForm">
                                    Modifier
                                    <div class="spinner-border spinner-border-sm text-light" role="status" id="editLoader" style="display: none;">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                </button>
                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Fermer</button>
                            </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Modal Delete -->
                <div class="modal fade" id="deleteRowModal" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header border-0">
                                <h5 class="modal-title">Êtes-vous sûr de vouloir supprimer cette assurance ?</h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form id="deleteInsuranceForm" action="{{ route('insurance_patient.destroy') }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <p id="insurance_name_to_delete"></p>
                                    <input type="hidden" id="delete_id" name="id">
                                </form>
                            </div>
                            <div class="modal-footer border-0">
                                <button type="submit" class="btn btn-danger" id="deleteRowButton" form="deleteInsuranceForm">
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
        // Événement pour modifier une assurance
        $(document).on('click', '.edit-button', function() {
            var details = $(this).data('info').split(',');
            var id = details[0];
            var patientId = details[1];
            var insuranceCompanyId = details[2];
            var policyNumber = details[3];
            var startDate = details[4];
            var endDate = details[5];
            var status = details[6];
            var annualLimit = details[7];
            var usedAmount = details[8];
            var notes = details[9];
            var coverage_percentage = details[10];

            // Mettre à jour les champs du modal
            $('#edit_id').val(id);
            $('#edit_patient_id').val(patientId);
            $('#edit_insurance_company_id').val(insuranceCompanyId);
            $('#edit_policy_number').val(policyNumber);
            $('#edit_start_date').val(startDate);
            $('#edit_end_date').val(endDate != 'null' ? endDate : '');
            $('#edit_status').val(status);
            $('#edit_annual_limit').val(annualLimit != 'null' ? annualLimit : '');
            $('#edit_used_amount').val(usedAmount);
            $('#edit_notes').val(notes != 'null' ? notes : '');
            $('#edit_coverage_percentage').val(coverage_percentage);

            // Afficher le modal
            $('#editRowModal').modal('show');
        });

        // Événement pour supprimer une assurance
        $(document).on('click', '.delete-button', function() {
            var id = $(this).data('id');
            var name = $(this).data('name');

            // Afficher le nom de l'assurance à supprimer
            $('#insurance_name_to_delete').text("Voulez-vous vraiment supprimer l'assurance de : " + name + " ?");

            // Mettre à jour l'action du formulaire de suppression avec l'ID
            $('#delete_id').val(id);
            // Afficher le modal de confirmation
            $('#deleteRowModal').modal('show');
        });

        // Afficher le loader pour l'ajout d'assurance
        $('#addInsuranceForm').on('submit', function() {
            $('#addRowButton').prop('disabled', true);
            $('#addLoader').show();
        });

        // Afficher le loader pour la modification d'assurance
        $('#editInsuranceForm').on('submit', function() {
            $('#editRowButton').prop('disabled', true);
            $('#editLoader').show();
        });

        // Afficher le loader pour la suppression d'assurance
        $('#deleteInsuranceForm').on('submit', function() {
            $('#deleteRowButton').prop('disabled', true);
            $('#deleteLoader').show();
        });

        // Lorsque la requête est terminée (réponse du serveur)
        $(document).ajaxComplete(function() {
            // Masquer les loaders et réactiver les boutons
            $('#addRowButton').prop('disabled', false);
            $('#addLoader').hide();

            $('#editRowButton').prop('disabled', false);
            $('#editLoader').hide();

            $('#deleteRowButton').prop('disabled', false);
            $('#deleteLoader').hide();
        });

    </script>
@endsection