@extends('layouts.backend')

@section('content')
<div class="container">
  <div class="page-inner">
    <div class="page-header">
      <ul class="breadcrumbs">
        <li class="nav-home">
          <a href="{{ url('/') }}">
            <i class="icon-home"></i>
          </a>
        </li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="{{ url('/') }}">Admin</a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="#">Leaves</a></li>
      </ul>
    </div>

    <div class="row">
      <div class="col-md-12">
        
        {{-- Messages de succès --}}
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <strong>Succès!</strong> {{ session('success') }}
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        {{-- Messages d'erreur --}}
        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <strong>Erreur!</strong> {{ session('error') }}
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        {{-- Erreurs de validation --}}
        @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <strong>Erreurs de validation:</strong>
          <ul class="mb-0">
            @foreach($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        <div class="card">
          <div class="card-header d-flex align-items-center justify-content-between">
            <h4 class="card-title">Liste des congés</h4>
            @can('medecin.leaves')
              <button class="btn btn-primary btn-round" data-bs-toggle="modal" data-bs-target="#addRowModal">
                <i class="fa fa-plus"></i> Ajouter un congé
              </button>
            @endcan
          </div>
          </div>

          <div class="card-body">
            <div class="bg-white rounded-lg shadow-md p-6">
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($leaves as $leave)
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="font-medium text-gray-900">
                                    {{ ucfirst($leave['type']) }}
                                </h4>
                                <p class="text-sm text-gray-600 mt-1">
                                    @if($leave->start_date->isSameDay($leave->end_date))
                                        Le {{ $leave->start_date->format('d/m/Y') }} de {{ $leave->start_date->format('H:i') }} à {{ $leave->end_date->format('H:i') }}
                                    @else
                                        Du {{ $leave->start_date->format('d/m/Y à H:i') }} au {{ $leave->end_date->format('d/m/Y à H:i') }}
                                    @endif
                                </p>
                                @if($leave['reason'])
                                <p class="text-sm text-gray-600 mt-1">{{ $leave['reason'] }}</p>
                                @endif

                                <div class="mt-3 d-flex gap-2">
                                  @can('medecin.leaves')
                                    <button class="btn btn-sm btn-warning edit-button"
                                            data-id="{{ $leave['id'] }}"
                                            data-type="{{ $leave['type'] }}"
                                            data-start="{{ $leave['start_date'] }}"
                                            data-end="{{ $leave['end_date'] }}"
                                            data-reason="{{ $leave['reason'] }}"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editLeaveModal">
                                        Modifier
                                    </button>
                                    <button class="btn btn-sm btn-danger delete-button"
                                            data-id="{{ $leave['id'] }}"
                                            data-type="{{ $leave['type'] }}"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteLeaveModal">
                                        Supprimer
                                    </button>
                                  @endcan
                                </div>
                            </div>

                            <span class="px-3 py-1 rounded-full text-xs font-medium {{ $leave['status'] == 'Approuvé' ? 'bg-success text-white' : 'bg-warning text-dark' }}">
                                {{ ucfirst($leave['status'] ?? 'En attente') }}
                            </span>
                        </div>
                    </div>
                @endforeach
              </div>
            </div>
        </div>

          {{-- Modal Ajouter un congé --}}
        <div class="modal fade" id="addRowModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <form method="POST" action="{{ route('medecin.leaves.store') }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Demander un congé</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Fermer">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    </div>
                    <div class="modal-body space-y-4">
                    <div>
                        <label>Type de congé</label>
                        <select name="type" class="form-control @error('type') is-invalid @enderror" required>
                        <option value="">Sélectionner un type</option>
                        <option value="Vacance" {{ old('type') == 'Vacance' ? 'selected' : '' }}>Vacances</option>
                        <option value="Maladie" {{ old('type') == 'Maladie' ? 'selected' : '' }}>Maladie</option>
                        <option value="Conference" {{ old('type') == 'Conference' ? 'selected' : '' }}>Conférence</option>
                        <option value="Autre" {{ old('type') == 'Autre' ? 'selected' : '' }}>Autre</option>
                        </select>
                        @error('type')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                        <label>Date de début</label>
                        <input type="datetime-local" name="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date') }}" required>
                        @error('start_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        </div>
                        <div class="col-md-6">
                        <label>Date de fin</label>
                        <input type="datetime-local" name="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date') }}" required>
                        @error('end_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        </div>
                    </div>

                    <div>
                        <label>Raison (optionnel)</label>
                        <textarea name="reason" class="form-control @error('reason') is-invalid @enderror" rows="3">{{ old('reason') }}</textarea>
                        @error('reason')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    </div>
                    <div class="modal-footer border-0">
                    <button type="submit" class="btn btn-primary">Demander</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    </div>
                </div>
                </form>
            </div>
        </div>

        <div class="modal fade" id="editLeaveModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <form method="POST" action="{{ route('medecin.leaves.update') }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="id" id="edit_leave_id">
                <div class="modal-content">
                    <div class="modal-header">
                    <h5 class="modal-title">Modifier le congé</h5>
                    <button type="button" class="close" data-bs-dismiss="modal">
                        <span>&times;</span>
                    </button>
                    </div>
                    <div class="modal-body">
                    <div class="form-group">
                        <label>Type</label>
                        <select name="type" id="edit_leave_type" class="form-control @error('type') is-invalid @enderror" required>
                        <option value="Vacance">Vacances</option>
                        <option value="Maladie">Maladie</option>
                        <option value="Conference">Conférence</option>
                        <option value="Autre">Autre</option>
                        </select>
                        @error('type')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <label>Date de début</label>
                            <input type="datetime-local" name="start_date" id="edit_leave_start" class="form-control @error('start_date') is-invalid @enderror" required>
                            @error('start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label>Date de fin</label>
                            <input type="datetime-local" name="end_date" id="edit_leave_end" class="form-control @error('end_date') is-invalid @enderror" required>
                            @error('end_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Raison</label>
                        <textarea name="reason" id="edit_leave_reason" class="form-control @error('reason') is-invalid @enderror" rows="3"></textarea>
                        @error('reason')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    </div>
                    <div class="modal-footer">
                    <button class="btn btn-success" type="submit">Enregistrer</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    </div>
                </div>
                </form>
            </div>
        </div>

        <div class="modal fade" id="deleteLeaveModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <form method="post" id="deleteLeaveForm" action="{{ route('medecin.leaves.destroy') }}">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="id" id="delete_leave_id">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Supprimer le congé</h5>
                            <button type="button" class="close" data-bs-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p class="text-danger">Voulez-vous vraiment supprimer ce congé ?</p>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-danger" type="submit">Supprimer</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        </div>
                    </div>
                </form>
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
  // Remplir les champs du modal de modification
  $('.edit-button').on('click', function () {
    $('#edit_leave_id').val($(this).data('id'));
    $('#edit_leave_type').val($(this).data('type'));
    $('#edit_leave_start').val($(this).data('start'));
    $('#edit_leave_end').val($(this).data('end'));
    $('#edit_leave_reason').val($(this).data('reason'));
  });

  // Supprimer un congé
  $('.delete-button').on('click', function () {
    let id = $(this).data('id');
    $('#delete_leave_id').val(id);
  });

  // Auto-fermeture des alertes après 5 secondes
  setTimeout(function() {
    $('.alert').fadeOut('slow');
  }, 5000);
</script>
@endsection