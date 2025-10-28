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
            @can('consultation.create')
                <div class="card-header">
                    <div class="d-flex align-items-center">
                        <h4 class="card-title">Liste des consultations</h4>
                        <a class="btn btn-primary btn-round ms-auto" href="{{ url("consultation/create") }}"><i class="fa fa-plus"></i> Nouvelle consultation</a>
                    </div>
                </div>
            @endcan

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
                                    @can('consultation.view')
                                        <a href="{{ route('consultation.show', $consultation->id) }}" class="btn btn-sm btn-primary"><i class="fa fa-eye"></i></a>
                                    @endcan
                                    @can('consultation.edit')
                                        <a href="{{ route('consultation.edit', $consultation->id) }}" class="btn btn-sm btn-warning"><i class="fa fa-edit"></i></a>
                                    @endcan
                                    @can('consultation.delete')
                                        <form action="{{ route('consultation.destroy', $consultation->id) }}" method="POST" style="display:inline-block;">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-danger" onclick="return confirm('Supprimer cette consultation ?')"><i class="fa fa-trash"></i></button>
                                        </form>
                                    @endcan
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
