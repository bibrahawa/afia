@extends('layouts.backend')

@section('style')
  <style>
      .break-card {
          transition: all 0.3s ease;
          border-left: 4px solid #3b82f6;
      }
      
      .break-card:hover {
          transform: translateY(-2px);
          box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      }
      
      .break-card.inactive {
          opacity: 0.6;
          border-left-color: #9ca3af;
      }
      
      .day-badge {
          padding: 0.375rem 0.75rem;
          border-radius: 9999px;
          font-size: 0.75rem;
          font-weight: 600;
      }
      
      .time-display {
          font-size: 1.125rem;
          font-weight: 600;
          color: #1f2937;
      }
      
      .nav-tabs-custom {
          border-bottom: 2px solid #e5e7eb;
      }
      
      .nav-tabs-custom .nav-link {
          border: none;
          color: #6b7280;
          font-weight: 600;
          padding: 1rem 1.5rem;
      }
      
      .nav-tabs-custom .nav-link.active {
          color: #3b82f6;
          border-bottom: 3px solid #3b82f6;
          background: transparent;
      }
      
      .tab-content {
          padding-top: 1.5rem;
      }
  </style>
@endsection

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
        <li class="nav-item"><a href="#">Congés & Pauses</a></li>
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
          <div class="card-header">
            <ul class="nav nav-tabs nav-tabs-custom" role="tablist">
              <li class="nav-item" role="presentation">
                <button class="nav-link active" id="leaves-tab" data-bs-toggle="tab" data-bs-target="#leaves" type="button" role="tab">
                  <i class="fas fa-calendar-times me-2"></i>Congés
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="breaks-tab" data-bs-toggle="tab" data-bs-target="#breaks" type="button" role="tab">
                  <i class="fas fa-coffee me-2"></i>Pauses
                </button>
              </li>
            </ul>
          </div>

          <div class="card-body">
            <div class="tab-content">
              {{-- Onglet Congés --}}
              <div class="tab-pane fade show active" id="leaves" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-4">
                  <h4 class="mb-0">Liste des congés</h4>
                  @can('medecin.leaves')
                    <button class="btn btn-primary btn-round" data-bs-toggle="modal" data-bs-target="#addRowModal">
                      <i class="fa fa-plus"></i> Ajouter un congé
                    </button>
                  @endcan
                </div>

                <div class="bg-white rounded-lg p-4">
                  <div class="row g-3">
                    @forelse ($leaves as $leave)
                      <div class="col-md-6 col-lg-4">
                        <div class="border border-gray-200 rounded-lg p-4 h-100">
                          <div class="d-flex justify-content-between align-items-start mb-3">
                            <h5 class="fw-bold mb-0">{{ ucfirst($leave['type']) }}</h5>
                            <span class="badge {{ $leave['status'] == 'Approuvé' ? 'bg-success' : 'bg-warning text-dark' }}">
                              {{ ucfirst($leave['status'] ?? 'En attente') }}
                            </span>
                          </div>
                          
                          <p class="text-muted small mb-2">
                            <i class="far fa-calendar me-1"></i>
                            @if($leave->start_date->isSameDay($leave->end_date))
                              Le {{ $leave->start_date->format('d/m/Y') }} de {{ $leave->start_date->format('H:i') }} à {{ $leave->end_date->format('H:i') }}
                            @else
                              Du {{ $leave->start_date->format('d/m/Y à H:i') }} au {{ $leave->end_date->format('d/m/Y à H:i') }}
                            @endif
                          </p>
                          
                          @if($leave['reason'])
                            <p class="text-muted small mb-3">
                              <i class="far fa-comment-dots me-1"></i>
                              {{ $leave['reason'] }}
                            </p>
                          @endif

                          @can('medecin.leaves')
                            <div class="d-flex gap-2">
                              <button class="btn btn-sm btn-warning edit-button flex-fill"
                                      data-id="{{ $leave['id'] }}"
                                      data-type="{{ $leave['type'] }}"
                                      data-start="{{ $leave['start_date']->format('Y-m-d\TH:i') }}"
                                      data-end="{{ $leave['end_date']->format('Y-m-d\TH:i') }}"
                                      data-reason="{{ $leave['reason'] }}"
                                      data-bs-toggle="modal"
                                      data-bs-target="#editLeaveModal">
                                <i class="fas fa-edit"></i> Modifier
                              </button>
                              <button class="btn btn-sm btn-danger delete-button"
                                      data-id="{{ $leave['id'] }}"
                                      data-type="{{ $leave['type'] }}"
                                      data-bs-toggle="modal"
                                      data-bs-target="#deleteLeaveModal">
                                <i class="fas fa-trash"></i>
                              </button>
                            </div>
                          @endcan
                        </div>
                      </div>
                    @empty
                      <div class="col-12">
                        <div class="alert alert-info text-center">
                          <i class="fas fa-info-circle me-2"></i>
                          Aucun congé enregistré pour le moment.
                        </div>
                      </div>
                    @endforelse
                  </div>
                </div>
              </div>

              {{-- Onglet Pauses --}}
              <div class="tab-pane fade" id="breaks" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-4">
                  <h4 class="mb-0">Gestion des pauses</h4>
                  @can('medecin.leaves')
                    <button class="btn btn-info btn-round" data-bs-toggle="modal" data-bs-target="#addBreakModal">
                      <i class="fa fa-plus"></i> Ajouter une pause
                    </button>
                  @endcan
                </div>

                <div class="alert alert-info">
                  <i class="fas fa-info-circle me-2"></i>
                  Les pauses bloquent automatiquement les créneaux de rendez-vous pendant les périodes définies.
                </div>

                <div class="row g-3">
                  @forelse ($breaks as $break)
                    <div class="col-md-6 col-lg-4">
                      <div class="card break-card {{ !$break->is_active ? 'inactive' : '' }} h-100">
                        <div class="card-body">
                          <div class="d-flex justify-content-between align-items-start mb-3">
                            <span class="day-badge bg-primary text-white">
                              {{ $break->day_of_week }}
                            </span>
                            <div class="form-check form-switch">
                              <input class="form-check-input toggle-break" 
                                     type="checkbox" 
                                     {{ $break->is_active ? 'checked' : '' }}
                                     data-id="{{ $break->id }}">
                            </div>
                          </div>

                          <h5 class="fw-bold mb-2">{{ $break->label ?? 'Pause' }}</h5>
                          
                          <div class="time-display mb-3">
                            <i class="far fa-clock text-primary me-2"></i>
                            {{ \Carbon\Carbon::parse($break->start_time)->format('H:i') }} - 
                            {{ \Carbon\Carbon::parse($break->end_time)->format('H:i') }}
                          </div>

                          <div class="text-muted small mb-3">
                            <i class="fas fa-hourglass-half me-1"></i>
                            Durée: {{ \Carbon\Carbon::parse($break->start_time)->diffInMinutes(\Carbon\Carbon::parse($break->end_time)) }} minutes
                          </div>

                          @can('medecin.leaves')
                            <div class="d-flex gap-2">
                              <button class="btn btn-sm btn-warning edit-break-button flex-fill"
                                      data-id="{{ $break->id }}"
                                      data-day="{{ $break->day_of_week }}"
                                      data-start="{{ \Carbon\Carbon::parse($break->start_time)->format('H:i') }}"
                                      data-end="{{ \Carbon\Carbon::parse($break->end_time)->format('H:i') }}"
                                      data-label="{{ $break->label }}"
                                      data-bs-toggle="modal"
                                      data-bs-target="#editBreakModal">
                                <i class="fas fa-edit"></i> Modifier
                              </button>
                              <button class="btn btn-sm btn-danger delete-break-button"
                                      data-id="{{ $break->id }}"
                                      data-label="{{ $break->label ?? 'cette pause' }}"
                                      data-bs-toggle="modal"
                                      data-bs-target="#deleteBreakModal">
                                <i class="fas fa-trash"></i>
                              </button>
                            </div>
                          @endcan
                        </div>
                      </div>
                    </div>
                  @empty
                    <div class="col-12">
                      <div class="alert alert-warning text-center">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Aucune pause configurée. Cliquez sur "Ajouter une pause" pour en créer.
                      </div>
                    </div>
                  @endforelse
                </div>
              </div>
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
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                  <div class="mb-3">
                    <label class="form-label">Type de congé</label>
                    <select name="type" class="form-select @error('type') is-invalid @enderror" required>
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
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Date de début</label>
                      <input type="datetime-local" name="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date') }}" required>
                      @error('start_date')
                      <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Date de fin</label>
                      <input type="datetime-local" name="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date') }}" required>
                      @error('end_date')
                      <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label">Raison (optionnel)</label>
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

        {{-- Modal Modifier congé --}}
        <div class="modal fade" id="editLeaveModal" tabindex="-1" role="dialog">
          <div class="modal-dialog" role="document">
            <form method="POST" action="{{ route('medecin.leaves.update') }}">
              @csrf
              @method('PUT')
              <input type="hidden" name="id" id="edit_leave_id">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title">Modifier le congé</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                  <div class="mb-3">
                    <label class="form-label">Type</label>
                    <select name="type" id="edit_leave_type" class="form-select" required>
                      <option value="Vacance">Vacances</option>
                      <option value="Maladie">Maladie</option>
                      <option value="Conference">Conférence</option>
                      <option value="Autre">Autre</option>
                    </select>
                  </div>

                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Date de début</label>
                      <input type="datetime-local" name="start_date" id="edit_leave_start" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Date de fin</label>
                      <input type="datetime-local" name="end_date" id="edit_leave_end" class="form-control" required>
                    </div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label">Raison</label>
                    <textarea name="reason" id="edit_leave_reason" class="form-control" rows="3"></textarea>
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

        {{-- Modal Supprimer congé --}}
        <div class="modal fade" id="deleteLeaveModal" tabindex="-1" role="dialog">
          <div class="modal-dialog" role="document">
            <form method="post" action="{{ route('medecin.leaves.destroy') }}">
              @csrf
              @method('DELETE')
              <input type="hidden" name="id" id="delete_leave_id">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title">Supprimer le congé</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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

        {{-- Modal Ajouter une pause --}}
        <div class="modal fade" id="addBreakModal" tabindex="-1" role="dialog">
          <div class="modal-dialog" role="document">
            <form method="POST" action="{{ route('breaks.store') }}">
              @csrf
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title fw-bold">Ajouter une pause</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                  <div class="mb-3">
                    <label class="form-label">Jour de la semaine</label>
                    <select name="day_of_week" class="form-select @error('day_of_week') is-invalid @enderror" required>
                      <option value="">Sélectionner un jour</option>
                      <option value="Lundi">Lundi</option>
                      <option value="Mardi">Mardi</option>
                      <option value="Mercredi">Mercredi</option>
                      <option value="Jeudi">Jeudi</option>
                      <option value="Vendredi">Vendredi</option>
                      <option value="Samedi">Samedi</option>
                      <option value="Dimanche">Dimanche</option>
                    </select>
                    @error('day_of_week')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Heure de début</label>
                      <input type="time" name="start_time" class="form-control @error('start_time') is-invalid @enderror" required>
                      @error('start_time')
                      <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Heure de fin</label>
                      <input type="time" name="end_time" class="form-control @error('end_time') is-invalid @enderror" required>
                      @error('end_time')
                      <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label">Label (optionnel)</label>
                    <input type="text" name="label" class="form-control" placeholder="Ex: Pause déjeuner, Pause café">
                    <small class="text-muted">Laissez vide pour "Pause" par défaut</small>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="submit" class="btn btn-info">Ajouter</button>
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                </div>
              </div>
            </form>
          </div>
        </div>

        {{-- Modal Modifier une pause --}}
        <div class="modal fade" id="editBreakModal" tabindex="-1" role="dialog">
          <div class="modal-dialog" role="document">
            <form method="POST" action="{{ route('breaks.update') }}">
              @csrf
              @method('PUT')
              <input type="hidden" name="id" id="edit_break_id">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title">Modifier la pause</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                  <div class="mb-3">
                    <label class="form-label">Jour de la semaine</label>
                    <select name="day_of_week" id="edit_break_day" class="form-select" required>
                      <option value="Lundi">Lundi</option>
                      <option value="Mardi">Mardi</option>
                      <option value="Mercredi">Mercredi</option>
                      <option value="Jeudi">Jeudi</option>
                      <option value="Vendredi">Vendredi</option>
                      <option value="Samedi">Samedi</option>
                      <option value="Dimanche">Dimanche</option>
                    </select>
                  </div>

                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Heure de début</label>
                      <input type="time" name="start_time" id="edit_break_start" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Heure de fin</label>
                      <input type="time" name="end_time" id="edit_break_end" class="form-control" required>
                    </div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label">Label</label>
                    <input type="text" name="label" id="edit_break_label" class="form-control">
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

        {{-- Modal Supprimer une pause --}}
        <div class="modal fade" id="deleteBreakModal" tabindex="-1" role="dialog">
          <div class="modal-dialog" role="document">
            <form method="POST" action="{{ route('breaks.destroy') }}">
              @csrf
              @method('DELETE')
              <input type="hidden" name="id" id="delete_break_id">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title">Supprimer la pause</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                  <p class="text-danger">Voulez-vous vraiment supprimer <strong id="delete_break_label"></strong> ?</p>
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
@endsection

@section('script')
<script type="text/javascript">
  // Congés - Remplir modal modification
  $(document).on('click', '.edit-button', function () {
    $('#edit_leave_id').val($(this).data('id'));
    $('#edit_leave_type').val($(this).data('type'));
    $('#edit_leave_start').val($(this).data('start'));
    $('#edit_leave_end').val($(this).data('end'));
    $('#edit_leave_reason').val($(this).data('reason'));
  });

  // Congés - Supprimer
  $(document).on('click', '.delete-button', function () {
    $('#delete_leave_id').val($(this).data('id'));
  });

  // Pauses - Remplir modal modification
  $(document).on('click', '.edit-break-button', function () {
    $('#edit_break_id').val($(this).data('id'));
    $('#edit_break_day').val($(this).data('day'));
    $('#edit_break_start').val($(this).data('start'));
    $('#edit_break_end').val($(this).data('end'));
    $('#edit_break_label').val($(this).data('label'));
  });

  // Pauses - Supprimer
  $(document).on('click', '.delete-break-button', function () {
    $('#delete_break_id').val($(this).data('id'));
    $('#delete_break_label').text($(this).data('label'));
  });

  // Toggle actif/inactif pour une pause
  $(document).on('change', '.toggle-break', function () {
    const breakId = $(this).data('id');
    const isActive = $(this).is(':checked');
    
    $.ajax({
      url: `/breaks/${breakId}/toggle`,
      method: 'POST',
      data: {
        _token: '{{ csrf_token() }}',
        is_active: isActive
      },
      success: function(response) {
        if (response.success) {
          const alertHtml = `
            <div class="alert alert-success alert-dismissible fade show" role="alert">
              <strong>Succès!</strong> La pause a été ${isActive ? 'activée' : 'désactivée'}.
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          `;
          $('.page-inner').prepend(alertHtml);
          
          setTimeout(() => location.reload(), 1000);
        }
      },
      error: function() {
        alert('Une erreur est survenue');
        location.reload();
      }
    });
  });

  // Auto-fermeture des alertes
  setTimeout(function() {
    $('.alert').fadeOut('slow');
  }, 5000);
</script>
@endsection