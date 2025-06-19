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
                <h4 class="card-title"><i class="fas fa-stethoscope me-2"></i>Nouvelle Consultation</h4>
                </div>
            </div>

            <div class="card-body">
                <form id="addConsultationForm" action="{{ route('consultation.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <!-- Département -->
                        {{-- <div class="col-sm-6">
                            <div class="form-group form-group-default">
                                <label for="department_id">Département</label>
                                <select name="department_id" class="form-control" required>
                                    @foreach($departements as $departement)
                                        <option value="{{ $departement->id }}">{{ $departement->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div> --}}

                        <!-- Fichiers -->
                        <div class="col-sm-6">
                            <div class="form-group form-group-default">
                                <label for="fichiers">Fichiers (résultats labo, radios...)</label>
                                <input type="file" name="fichiers[]" multiple class="form-control">
                            </div>
                        </div>

                        <!-- Patient -->
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="form-label">Patient (facultatif)</label>
                                <select class="selectpicker" name="patient_id" data-live-search="true">
                                    @foreach($patients as $patient)
                                        <option value="{{ $patient->id }}">{{ $patient->first_name.' '.$patient->last_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Service -->
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="form-label">Service (facultatif)</label>
                                <select class="selectpicker" name="services[]" data-live-search="true" multiple>
                                    @foreach($services as $service)
                                        <option value="{{ $service->id }}">{{ $service->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Tests -->
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="form-label">Examens (facultatif)</label>
                                <select class="selectpicker" id="tests" name="tests[]" data-live-search="true" multiple>
                                    @foreach($tests as $test)
                                        <option value="{{ $test->id }}">{{ $test->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Package -->
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="form-label">Package (facultatif)</label>
                                <select class="selectpicker" name="packages[]" data-live-search="true" multiple>
                                    @foreach($packages as $package)
                                        <option value="{{ $package->id }}">{{ $package->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Motif -->
                        <div class="col-sm-6">
                            <div class="form-group form-group-default">
                                <label for="motif">Motif de la consultation</label>
                                <textarea name="motif" class="form-control" rows="3" required></textarea>
                            </div>
                        </div>

                        <!-- Signes cliniques -->
                        <div class="col-sm-6">
                            <div class="form-group form-group-default">
                                <label for="signes_cliniques">Signes cliniques (séparés par virgule)</label>
                                <textarea name="signes_cliniques" class="form-control" rows="3" required></textarea>
                            </div>
                        </div>

                        <!-- Diagnostic -->
                        <div class="col-sm-6">

                            <div class="form-group form-group-default">
                                <label for="diagnostic">Diagnostic</label>
                                <textarea name="diagnostic" class="form-control" rows="3" required></textarea>
                            </div>
                        </div>

                        <!-- Observation -->
                        <div class="col-sm-6">

                            <div class="form-group form-group-default">
                                <label for="observation">Observation (facultatif)</label>
                                <textarea name="observation" class="form-control" rows="3"></textarea>
                            </div>
                        </div>

                        <!-- Prochain médecin -->
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="form-label">Médecin pour le prochain RDV</label>
                                <select class="selectpicker" name="prochain_medecin" data-live-search="true">
                                    @foreach($medecins as $medecin)
                                        <option value="{{ $medecin->id }}">{{ $medecin->first_name." ".$medecin->last_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <!-- Médicaments -->
                            <div class="form-group">
                                <label for="form-label">Prescription</label>
                                <select class="selectpicker" name="medicaments[]" data-live-search="true" multiple>
                                    @foreach($medicaments as $medicament)
                                        <option value="{{ $medicament->id }}">{{ $medicament->nom." ( ". $medicament->dosage." == ". $medicament->frequence." )"}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Prochain rendez-vous -->
                        <div class="col-sm-6">
                            <div class="form-group form-group-default">
                                <label for="prochain_rdv">Prochain rendez-vous </label>
                                <input type="datetime-local" name="prochain_rdv" class="form-control">
                            </div>
                        </div>

                    </div>

                    <div class="modal-footer border-0">
                        <!-- Bouton pour la modification -->
                        <button type="submit" class="btn btn-success" id="saveRowButton" form="addConsultationForm">
                            Enregistrer
                            <div class="spinner-border spinner-border-sm text-light" role="status" id="saveLoader" style="display: none;">
                                <span class="sr-only">Loading...</span>
                            </div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        </div>
    </div>
</div>

@endsection


@section('script')
    <script type="text/javascript">

        // Afficher le loader pour la modification de doctor
        $('#addConsultationForm').on('submit', function() {
            $('#saveRowButton').prop('disabled', true);  // Désactive le bouton pour éviter plusieurs clics
            $('#saveLoader').show();  // Affiche le loader
        });


        // Lorsque la requête est terminée (réponse du serveur)
        $(document).ajaxComplete(function() {
            $('#saveRowButton').prop('disabled', false);  // Réactive le bouton "Modifier"
            $('#saveLoader').hide();  // Masque le loader "Modifier"
        });

    </script>
@endsection
