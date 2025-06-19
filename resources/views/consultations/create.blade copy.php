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
                <h4 class="card-title">Nouvelle Consultation</h4>
                <button
                  class="btn btn-primary btn-round ms-auto"
                  data-bs-toggle="modal"
                  data-bs-target="#addRowModal"
                >
                  <i class="fa fa-plus"></i> Ajouter une consultation
                </button>
              </div>
            </div>

            <div class="card-body">

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

                    <button type="submit" id="addRowButton" class="btn btn-primary" form="addDepartmentForm">
                        Enregistrer
                        <div class="spinner-border spinner-border-sm text-light" role="status" id="addLoader" style="display: none;">
                            <span class="sr-only">Loading...</span>
                        </div>
                    </button>

                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">
                        Fermer
                    </button>

                </form>
            </div>
        </div>
      </div>
    </div>
</div>

@endsection
