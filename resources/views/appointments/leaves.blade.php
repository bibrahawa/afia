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
                <li class="nav-item"><a href="#">Congés &amp; Pauses</a></li>
            </ul>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>Succès !</strong> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Erreur !</strong> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Erreurs de validation :</strong>
                <ul class="mb-0 mt-2">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-md-12">

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
                                        <button class="btn btn-primary btn-round" data-bs-toggle="modal" data-bs-target="#addLeaveModal" type="button">
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
                                                        <h5 class="fw-bold mb-0">{{ ucfirst($leave->type) }}</h5>
                                                        <span class="badge {{ $leave->status === 'approved' ? 'bg-success' : ($leave->status === 'rejected' ? 'bg-danger' : 'bg-warning text-dark') }}">
                                                            {{ ucfirst($leave->status ?? 'pending') }}
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

                                                    @if($leave->reason)
                                                        <p class="text-muted small mb-3">
                                                            <i class="far fa-comment-dots me-1"></i>
                                                            {{ $leave->reason }}
                                                        </p>
                                                    @endif

                                                    @can('medecin.leaves')
                                                        <div class="d-flex gap-2">
                                                            <button
                                                                type="button"
                                                                class="btn btn-sm btn-warning edit-leave-button flex-fill"
                                                                data-id="{{ $leave->id }}"
                                                                data-type="{{ $leave->type }}"
                                                                data-start="{{ $leave->start_date->format('Y-m-d\TH:i') }}"
                                                                data-end="{{ $leave->end_date->format('Y-m-d\TH:i') }}"
                                                                data-reason="{{ $leave->reason }}"
                                                            >
                                                                <i class="fas fa-edit"></i> Modifier
                                                            </button>

                                                            <button
                                                                type="button"
                                                                class="btn btn-sm btn-danger delete-leave-button"
                                                                data-id="{{ $leave->id }}"
                                                                data-type="{{ $leave->type }}"
                                                            >
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    @endcan
                                                </div>
                                            </div>
                                        @empty
                                            <div class="col-12">
                                                <div class="alert alert-info text-center mb-0">
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
                                        <button class="btn btn-info btn-round" data-bs-toggle="modal" data-bs-target="#addBreakModal" type="button">
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

                                                        @can('medecin.leaves')
                                                            <div class="form-check form-switch">
                                                                <input
                                                                    class="form-check-input toggle-break"
                                                                    type="checkbox"
                                                                    {{ $break->is_active ? 'checked' : '' }}
                                                                    data-id="{{ $break->id }}"
                                                                >
                                                            </div>
                                                        @endcan
                                                    </div>

                                                    <h5 class="fw-bold mb-2">{{ $break->label ?? 'Pause' }}</h5>

                                                    <div class="time-display mb-3">
                                                        <i class="far fa-clock text-primary me-2"></i>
                                                        {{ \Carbon\Carbon::parse($break->start_time)->format('H:i') }} -
                                                        {{ \Carbon\Carbon::parse($break->end_time)->format('H:i') }}
                                                    </div>

                                                    <div class="text-muted small mb-3">
                                                        <i class="fas fa-hourglass-half me-1"></i>
                                                        Durée :
                                                        {{ \Carbon\Carbon::parse($break->start_time)->diffInMinutes(\Carbon\Carbon::parse($break->end_time)) }}
                                                        minutes
                                                    </div>

                                                    @can('medecin.leaves')
                                                        <div class="d-flex gap-2">
                                                            <button
                                                                type="button"
                                                                class="btn btn-sm btn-warning edit-break-button flex-fill"
                                                                data-id="{{ $break->id }}"
                                                                data-day="{{ $break->day_of_week }}"
                                                                data-start="{{ \Carbon\Carbon::parse($break->start_time)->format('H:i') }}"
                                                                data-end="{{ \Carbon\Carbon::parse($break->end_time)->format('H:i') }}"
                                                                data-label="{{ $break->label }}"
                                                            >
                                                                <i class="fas fa-edit"></i> Modifier
                                                            </button>

                                                            <button
                                                                type="button"
                                                                class="btn btn-sm btn-danger delete-break-button"
                                                                data-id="{{ $break->id }}"
                                                                data-label="{{ $break->label ?? 'cette pause' }}"
                                                            >
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    @endcan
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="col-12">
                                            <div class="alert alert-warning text-center mb-0">
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
                <div class="modal fade" id="addLeaveModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <form method="POST" action="{{ route('medecin.leaves.store') }}" id="addLeaveForm">
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
                                    <button type="submit" id="addLeaveButton" class="btn btn-primary">
                                        Demander
                                        <span class="spinner-border spinner-border-sm text-light ms-1 d-none" role="status" id="addLeaveLoader">
                                            <span class="visually-hidden">Loading...</span>
                                        </span>
                                    </button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Modal Modifier congé --}}
                <div class="modal fade" id="editLeaveModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <form method="POST" action="" id="editLeaveForm">
                            @csrf
                            @method('PUT')

                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Modifier le congé</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                </div>

                                <div class="modal-body">
                                    <input type="hidden" name="id" id="edit_leave_id">

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
                                    <button class="btn btn-success" type="submit" id="editLeaveButton">
                                        Enregistrer
                                        <span class="spinner-border spinner-border-sm text-light ms-1 d-none" role="status" id="editLeaveLoader">
                                            <span class="visually-hidden">Loading...</span>
                                        </span>
                                    </button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Modal Supprimer congé --}}
                <div class="modal fade" id="deleteLeaveModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <form method="POST" action="" id="deleteLeaveForm">
                            @csrf
                            @method('DELETE')

                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Supprimer le congé</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                </div>

                                <div class="modal-body">
                                    <input type="hidden" name="id" id="delete_leave_id">
                                    <p class="text-danger mb-0">
                                        Voulez-vous vraiment supprimer ce congé
                                        <strong id="delete_leave_type_text"></strong> ?
                                    </p>
                                </div>

                                <div class="modal-footer">
                                    <button class="btn btn-danger" type="submit" id="deleteLeaveButton">
                                        Supprimer
                                        <span class="spinner-border spinner-border-sm text-light ms-1 d-none" role="status" id="deleteLeaveLoader">
                                            <span class="visually-hidden">Loading...</span>
                                        </span>
                                    </button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Modal Ajouter une pause --}}
                <div class="modal fade" id="addBreakModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <form method="POST" action="{{ route('medecin.breaks.store') }}" id="addBreakForm">
                            @csrf
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title fw-bold">Ajouter une pause</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
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
                                    <button type="submit" class="btn btn-info" id="addBreakButton">
                                        Ajouter
                                        <span class="spinner-border spinner-border-sm text-light ms-1 d-none" role="status" id="addBreakLoader">
                                            <span class="visually-hidden">Loading...</span>
                                        </span>
                                    </button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Modal Modifier une pause --}}
                <div class="modal fade" id="editBreakModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <form method="POST" action="" id="editBreakForm">
                            @csrf
                            @method('PUT')

                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Modifier la pause</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                </div>

                                <div class="modal-body">
                                    <input type="hidden" name="id" id="edit_break_id">

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
                                    <button class="btn btn-success" type="submit" id="editBreakButton">
                                        Enregistrer
                                        <span class="spinner-border spinner-border-sm text-light ms-1 d-none" role="status" id="editBreakLoader">
                                            <span class="visually-hidden">Loading...</span>
                                        </span>
                                    </button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Modal Supprimer une pause --}}
                <div class="modal fade" id="deleteBreakModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <form method="POST" action="" id="deleteBreakForm">
                            @csrf
                            @method('DELETE')

                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Supprimer la pause</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                </div>

                                <div class="modal-body">
                                    <input type="hidden" name="id" id="delete_break_id">
                                    <p class="text-danger mb-0">
                                        Voulez-vous vraiment supprimer <strong id="delete_break_label"></strong> ?
                                    </p>
                                </div>

                                <div class="modal-footer">
                                    <button class="btn btn-danger" type="submit" id="deleteBreakButton">
                                        Supprimer
                                        <span class="spinner-border spinner-border-sm text-light ms-1 d-none" role="status" id="deleteBreakLoader">
                                            <span class="visually-hidden">Loading...</span>
                                        </span>
                                    </button>
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
<script>
    $(document).on('click', '.edit-leave-button', function () {
        const id = $(this).data('id');

        $('#edit_leave_id').val(id);
        $('#edit_leave_type').val($(this).data('type'));
        $('#edit_leave_start').val($(this).data('start'));
        $('#edit_leave_end').val($(this).data('end'));
        $('#edit_leave_reason').val($(this).data('reason'));

        $('#editLeaveForm').attr('action', `/medecin/leaves/${id}`);
        $('#editLeaveLoader').addClass('d-none');
        $('#editLeaveButton').prop('disabled', false);

        $('#editLeaveModal').modal('show');
    });

    $(document).on('click', '.delete-leave-button', function () {
        const id = $(this).data('id');
        const type = $(this).data('type');

        $('#delete_leave_id').val(id);
        $('#delete_leave_type_text').text(type);
        $('#deleteLeaveForm').attr('action', `/medecin/leaves/${id}`);
        $('#deleteLeaveLoader').addClass('d-none');
        $('#deleteLeaveButton').prop('disabled', false);

        $('#deleteLeaveModal').modal('show');
    });

    $(document).on('click', '.edit-break-button', function () {
        const id = $(this).data('id');

        $('#edit_break_id').val(id);
        $('#edit_break_day').val($(this).data('day'));
        $('#edit_break_start').val($(this).data('start'));
        $('#edit_break_end').val($(this).data('end'));
        $('#edit_break_label').val($(this).data('label'));

        $('#editBreakForm').attr('action', `/medecin/breaks/${id}`);
        $('#editBreakLoader').addClass('d-none');
        $('#editBreakButton').prop('disabled', false);

        $('#editBreakModal').modal('show');
    });

    $(document).on('click', '.delete-break-button', function () {
        const id = $(this).data('id');
        const label = $(this).data('label');

        $('#delete_break_id').val(id);
        $('#delete_break_label').text(label);
        $('#deleteBreakForm').attr('action', `/medecin/breaks/${id}`);
        $('#deleteBreakLoader').addClass('d-none');
        $('#deleteBreakButton').prop('disabled', false);

        $('#deleteBreakModal').modal('show');
    });

    $(document).on('change', '.toggle-break', function () {
        const breakId = $(this).data('id');
        const isActive = $(this).is(':checked');
        const checkbox = $(this);

        $.ajax({
            url: `/medecin/breaks/${breakId}/toggle`,
            method: 'PATCH',
            data: {
                _token: '{{ csrf_token() }}',
                is_active: isActive
            },
            success: function (response) {
                if (response.success) {
                    const alertHtml = `
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <strong>Succès !</strong> ${response.message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                        </div>
                    `;

                    $('.page-inner').prepend(alertHtml);
                    setTimeout(() => location.reload(), 1000);
                }
            },
            error: function (xhr) {
                const message = xhr.responseJSON?.message || 'Une erreur est survenue.';
                const alertHtml = `
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Erreur !</strong> ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                    </div>
                `;

                $('.page-inner').prepend(alertHtml);
                checkbox.prop('checked', !isActive);
            }
        });
    });

    $('#addLeaveForm').on('submit', function () {
        $('#addLeaveButton').prop('disabled', true);
        $('#addLeaveLoader').removeClass('d-none');
    });

    $('#editLeaveForm').on('submit', function () {
        $('#editLeaveButton').prop('disabled', true);
        $('#editLeaveLoader').removeClass('d-none');
    });

    $('#deleteLeaveForm').on('submit', function () {
        $('#deleteLeaveButton').prop('disabled', true);
        $('#deleteLeaveLoader').removeClass('d-none');
    });

    $('#addBreakForm').on('submit', function () {
        $('#addBreakButton').prop('disabled', true);
        $('#addBreakLoader').removeClass('d-none');
    });

    $('#editBreakForm').on('submit', function () {
        $('#editBreakButton').prop('disabled', true);
        $('#editBreakLoader').removeClass('d-none');
    });

    $('#deleteBreakForm').on('submit', function () {
        $('#deleteBreakButton').prop('disabled', true);
        $('#deleteBreakLoader').removeClass('d-none');
    });

    setTimeout(function () {
        $('.alert').fadeOut('slow');
    }, 5000);
</script>
@endsection