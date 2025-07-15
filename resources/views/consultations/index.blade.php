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
            <a href="{{ route('consultation.index') }}">Consultations</a>
          </li>
        </ul>
      </div>

      <div class="row">
        <div class="col-md-12">
          <div class="card">
            <div class="card-header">
              <div class="d-flex align-items-center">
                <h4 class="card-title">Liste des consultations</h4>
                  <a class="btn btn-primary btn-round ms-auto" href="{{ route("consultation.create") }}"><i class="fa fa-plus"></i> Nouvelle consultation</a>
              </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table id="add-row" class="display table table-striped table-hover">
                    <thead class="bg-primary text-white"> <!-- Ajout de couleur d'entête -->
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Département</th>
                            {{-- <th>Médecin</th> --}}
                            <th>Patient</th>
                            <th>Motif</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Département</th>
                            {{-- <th>Médecin</th> --}}
                            <th>Patient</th>
                            <th>Motif</th>
                            <th>Actions</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        @forelse ($consultations as $consultation)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $consultation->created_at->format('d/m/Y H:m:s') }}</td>
                                <td>{{ $consultation->department->name }}</td>
                                {{-- <td>{{ $consultation->medecin->first_name." ".$consultation->medecin->last_name ?? '—' }}</td> --}}
                                <td>{{ $consultation->patient->first_name." ".$consultation->patient->last_name }}</td>
                                <td>{{ Str::limit($consultation->motif, 30) }}</td>
                                <td>
                                    <a href="{{ route('consultation.show', $consultation->id) }}" class="btn btn-sm btn-primary"><i class="fa fa-eye"></i></a>
                                    <a href="{{ route('consultation.edit', $consultation->id) }}" class="btn btn-sm btn-warning"><i class="fa fa-edit"></i></a>
                                    <form action="{{ route('consultation.destroy', $consultation->id) }}" method="POST" style="display:inline-block;">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-danger" onclick="return confirm('Supprimer cette consultation ?')"><i class="fa fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">Aucune consultation enregistrée.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    </table>
                </div>

                <!-- Modal Add -->
                {{-- <div class="modal fade" id="addRowModal" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header border-0">
                                <h5 class="modal-title">
                                    <span class="fw-mediumbold"> Nouveau</span>
                                    <span class="fw-light"> consultation</span>
                                </h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <p class="small">Créez un nouveau consultation en remplissant le formulaire ci-dessous.</p>
                                <form id="addDepartmentForm" action="{{ route('consultation.store') }}" method="POST" enctype="multipart/form-data">
                                    @csrf

                                    <!-- Patient -->
                                    <div class="mb-3">
                                        <label for="patient_id" class="form-label">Patient</label>
                                        <select name="patient_id" class="form-select" required>
                                            @foreach($patients as $patient)
                                                <option value="{{ $patient->id }}">{{ $patient->first_name.' '.$patient->last_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Département -->
                                    <div class="mb-3">
                                        <label for="departement_id" class="form-label">Département</label>
                                        <select name="departement_id" class="form-select" required>
                                            @foreach($departements as $departement)
                                                <option value="{{ $departement->id }}">{{ $departement->nom }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Service -->
                                    <div class="mb-3">
                                        <label for="service_id" class="form-label">Service</label>
                                        <select name="service_id" class="form-select" required>
                                            @foreach($services as $service)
                                                <option value="{{ $service->id }}">{{ $service->nom }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Package -->
                                    <div class="mb-3">
                                        <label for="package_id" class="form-label">Package (facultatif)</label>
                                        <select name="package_id" class="form-select">
                                            <option value="">Aucun</option>
                                            @foreach($packages as $package)
                                                <option value="{{ $package->id }}">{{ $package->nom }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Médecin consultant -->
                                    <div class="mb-3">
                                        <label for="medecin_id" class="form-label">Médecin consultant</label>
                                        <select name="medecin_id" class="form-select" required>
                                            @foreach($medecins as $medecin)
                                                <option value="{{ $medecin->id }}">{{ $medecin->nom }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Motif -->
                                    <div class="mb-3">
                                        <label for="motif" class="form-label">Motif de la consultation</label>
                                        <textarea name="motif" class="form-control" rows="3" required></textarea>
                                    </div>

                                    <!-- Signes cliniques -->
                                    <div class="mb-3">
                                        <label for="signes_cliniques" class="form-label">Signes cliniques (séparés par virgule)</label>
                                        <textarea name="signes_cliniques" class="form-control" rows="3" required></textarea>
                                    </div>

                                    <!-- Diagnostic -->
                                    <div class="mb-3">
                                        <label for="diagnostic" class="form-label">Diagnostic</label>
                                        <textarea name="diagnostic" class="form-control" rows="3" required></textarea>
                                    </div>

                                    <!-- Observation -->
                                    <div class="mb-3">
                                        <label for="observation" class="form-label">Observation (facultatif)</label>
                                        <textarea name="observation" class="form-control" rows="3"></textarea>
                                    </div>

                                    <!-- Prochain rendez-vous -->
                                    <div class="mb-3">
                                        <label for="prochain_rdv" class="form-label">Prochain rendez-vous</label>
                                        <input type="datetime-local" name="prochain_rdv" class="form-control">
                                    </div>

                                    <!-- Prochain médecin -->
                                    <div class="mb-3">
                                        <label for="prochain_medecin" class="form-label">Médecin pour le prochain RDV</label>
                                        <select name="prochain_medecin" class="form-select">
                                            <option value="">Non défini</option>
                                            @foreach($medecins as $medecin)
                                                <option value="{{ $medecin->id }}">{{ $medecin->nom }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Médicaments -->
                                    <h4 class="mt-4">Prescription</h4>
                                    @foreach($medicaments as $medicament)
                                        <div class="border p-3 mb-2 rounded">
                                            <div class="form-check mb-2">
                                                <input type="checkbox" class="form-check-input" name="medicaments[{{ $medicament->id }}][selected]" value="1" id="med{{ $medicament->id }}">
                                                <label class="form-check-label" for="med{{ $medicament->id }}">{{ $medicament->nom }}</label>
                                            </div>
                                            <input type="text" name="medicaments[{{ $medicament->id }}][frequence]" class="form-control mb-2" placeholder="Fréquence (ex: 2x/jour)">
                                            <input type="text" name="medicaments[{{ $medicament->id }}][duree]" class="form-control mb-2" placeholder="Durée (ex: 5 jours)">
                                            <input type="text" name="medicaments[{{ $medicament->id }}][instruction]" class="form-control mb-2" placeholder="Instructions (ex: après repas)">
                                        </div>
                                    @endforeach

                                    <!-- Fichiers -->
                                    <div class="mb-3">
                                        <label for="fichiers" class="form-label">Fichiers (résultats labo, radios...)</label>
                                        <input type="file" name="fichiers[]" multiple class="form-control">
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer border-0">
                                <button type="submit" id="addRowButton" class="btn btn-primary" form="addDepartmentForm">
                                    Enregistrer
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
                </div> --}}

                <!-- Modal Edit -->
                <div class="modal fade" id="editRowModal" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header border-0">
                                <h5 class="modal-title">
                                    <span class="fw-mediumbold"> Modifier</span>
                                    <span class="fw-light"> consultation</span>
                                </h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            {{-- <div class="modal-body">
                                <form id='editDepartmentForm' action="#" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <input type="hidden" name="edit_id" id="edit_id">
                                            <div class="form-group form-group-default">
                                                <label>Select consultation:</label>
                                                <select name="employee_id" id="edit_employee_id" class="form-control">
                                                    <option disabled selected>Selectionnez un docteur</option>
                                                    @foreach ($employees as $employee)
                                                        <option value="{{$employee->id}}">{{$employee->first_name}} {{$employee->middle_name}} {{$employee->last_name}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>consultation Charge:</label>
                                                <div class="input-group">
                                                    <input type="number" name="fee" id="edit_fee" class="form-control" placeholder="fee">
                                                    <span class="input-group-text">GNF</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>OPD Charge:</label>
                                                <div class="input-group">
                                                    <input type="number" name="opd_charge" id="edit_opd_charge" class="form-control" placeholder="fee">
                                                    <span class="input-group-text">GNF</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                            </div> --}}
                            <div class="modal-footer border-0">
                                <!-- Bouton pour la modification -->
                                <button type="submit" class="btn btn-success" id="editRowButton" form="editDepartmentForm">
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
                                <h5 class="modal-title">Êtes-vous sûr de vouloir supprimer ce consultation ?</h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <!-- Formulaire de suppression -->
                                <form id="deleteDepartmentForm" action="#" method="POST">
                                    @csrf
                                    @method('DELETE') <!-- Utiliser la méthode DELETE -->
                                    <p id="department_name_to_delete"></p>
                                    <input type="hidden" id="delete_id" name="id">
                                </form>
                            </div>
                            <div class="modal-footer border-0">
                                <!-- Bouton pour la suppression -->
                                <button type="submit" class="btn btn-danger" id="deleteRowButton" form="deleteDepartmentForm">
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
        // Événement pour modifier un consultation
        $(document).on('click', '.edit-button', function() {
            var consultation = $(this).data('info');
            // Mettre à jour le champ du modal
            $('#edit_id').val(consultation.id);
            $('#edit_fee').val(consultation.fee);
            $('#edit_opd_charge').val(consultation.opd_charge);
            $('#edit.consultation_id').val(consultation.employee);

            $('#editDepartmentForm').attr('action', '/consultation/' + consultation.id);

            // Afficher le modal
            $('#editRowModal').modal('show');
        });

        // Événement pour supprimer un consultation
        $(document).on('click', '.delete-button', function() {
            var id = $(this).data('consultation').id;
            var name = $(this).data('consultation').employee.first_name + " " + $(this).data('consultation').employee.last_name;

            // Afficher le nom du consultation à supprimer
            $('#department_name_to_delete').text("Voulez-vous vraiment supprimer le consultation : " + name + " ?");

            // Mettre à jour l'action du formulaire de suppression avec l'ID du consultation
            $('#delete_id').val(id);
            $('#deleteDepartmentForm').attr('action', '/consultation/' + id);

            // Afficher le modal de confirmation
            $('#deleteRowModal').modal('show');
        });

        // Afficher le loader pour l'ajout de consultation
        $('#addDepartmentForm').on('submit', function() {
            $('#addRowButton').prop('disabled', true);  // Désactive le bouton pour éviter plusieurs clics
            $('#addLoader').show();  // Affiche le loader
        });

        // Afficher le loader pour la modification de consultation
        $('#editDepartmentForm').on('submit', function() {
            $('#editRowButton').prop('disabled', true);  // Désactive le bouton pour éviter plusieurs clics
            $('#editLoader').show();  // Affiche le loader
        });

        // Afficher le loader pour la suppression de consultation
        $('#deleteDepartmentForm').on('submit', function() {
            $('#deleteRowButton').prop('disabled', true);  // Désactive le bouton pour éviter plusieurs clics
            $('#deleteLoader').show();  // Affiche le loader
        });

        // Lorsque la requête est terminée (réponse du serveur)
        $(document).ajaxComplete(function() {
            // Masquer les loaders et réactiver les boutons
            $('#addRowButton').prop('disabled', false);  // Réactive le bouton "Ajouter"
            $('#addLoader').hide();  // Masque le loader "Ajouter"

            $('#editRowButton').prop('disabled', false);  // Réactive le bouton "Modifier"
            $('#editLoader').hide();  // Masque le loader "Modifier"

            $('#deleteRowButton').prop('disabled', false);  // Réactive le bouton "Supprimer"
            $('#deleteLoader').hide();  // Masque le loader "Supprimer"
        });

    </script>
@endsection
