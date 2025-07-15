@extends('layouts.backend')

@section('content')

<style>
    :root {
        --primary-color: #2563eb;
        --primary-dark: #1d4ed8;
        --secondary-color: #64748b;
        --success-color: #10b981;
        --warning-color: #f59e0b;
        --danger-color: #ef4444;
        --light-bg: #f8fafc;
        --card-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        --card-shadow-hover: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        --border-radius: 0.75rem;
        --border-radius-lg: 1rem;
    }

    .form-label-enhanced {
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .required-mark {
        color: var(--danger-color);
        font-weight: 700;
    }

    .form-control-enhanced {
        border: 2px solid #e5e7eb;
        border-radius: var(--border-radius);
        padding: 0.75rem 1rem;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        background: #fafafa;
    }

    .form-control-enhanced:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        background: white;
        outline: none;
    }

    .form-control-enhanced.readonly {
        background: #f8fafc;
        color: var(--secondary-color);
        cursor: not-allowed;
    }

    .select-wrapper {
        position: relative;
    }

    .btn-enhanced {
        padding: 0.625rem 1.25rem;
        border-radius: var(--border-radius);
        font-weight: 500;
        font-size: 0.9rem;
        border: none;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
    }

    .btn-primary-enhanced {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
        color: white;
    }

    .btn-primary-enhanced:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
    }

    .btn-outline-enhanced {
        background: white;
        border: 2px solid #e5e7eb;
        color: #374151;
    }

    .btn-outline-enhanced:hover {
        background: #f9fafb;
        border-color: var(--primary-color);
        color: var(--primary-color);
    }

    .btn-sm-enhanced {
        padding: 0.375rem 0.75rem;
        font-size: 0.8rem;
    }

    .action-buttons {
        background: white;
        padding: 1.5rem;
        border-radius: var(--border-radius-lg);
        box-shadow: var(--card-shadow);
        display: flex;
        justify-content: flex-end;
        gap: 1rem;
        margin-top: 2rem;
    }

    .modal-content-enhanced {
        border-radius: var(--border-radius-lg);
        border: none;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    }

    .modal-header-enhanced {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
        color: white;
        border-radius: var(--border-radius-lg) var(--border-radius-lg) 0 0;
        padding: 1.5rem;
    }

    .modal-header-enhanced h5 {
        font-weight: 600;
        margin: 0;
    }


    .file-upload-zone {
        border: 2px dashed #d1d5db;
        border-radius: var(--border-radius);
        padding: 2rem;
        text-align: center;
        background: #fafafa;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .file-upload-zone:hover {
        border-color: var(--primary-color);
        background: #f0f8ff;
    }

    .icon-wrapper {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.5rem;
        height: 2.5rem;
        background: rgba(37, 99, 235, 0.1);
        border-radius: 50%;
        margin-right: 0.75rem;
    }

    .fade-in {
        animation: fadeIn 0.5s ease-in;
    }

</style>

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
            <a href="{{ route('patient.index') }}">patientes</a>
          </li>
        </ul>
      </div>

      <div class="row">
        <div class="col-md-12">
          <div class="card">
            <div class="card-header">
              <div class="d-flex align-items-center">
                <h4 class="card-title">Liste des patientes</h4>
                <button
                  class="btn btn-primary btn-round ms-auto"
                  data-bs-toggle="modal"
                  data-bs-target="#addRowModal">
                  <i class="fa fa-plus"></i> Ajouter une patiente
                </button>
              </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table id="add-row" class="display table table-striped table-hover">
                    <thead class="bg-primary text-white"> <!-- Ajout de couleur d'entête -->
                        <tr>
                            <th>ID</th>
					        <th>Nom</th>
					        <th>Telephone</th>
					        <th>Adresse</th>
					        <th>Solde</th>
					        <th>Action</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th>ID</th>
					        <th>Nom</th>
					        <th>Telephone</th>
					        <th>Adresse</th>
					        <th>Solde</th>
					        <th>Action</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        @foreach($patients as $patient)
                            <tr>
                                <td>{{$patient->id}}</td>
                                <td>{{$patient->first_name}} {{$patient->middle_name}} {{$patient->last_name}}</td>
                                <td>{{$patient->phone}}</td>
                                <td>{{$patient->district}}, {{$patient->location}}</td>
                                <td>{{number_format($patient->account->balance) ?? ""}} GNF</td>
                                <td>
                                    <a href="{{ route('patient.show', $patient->id) }}" class="btn btn-sm btn-primary"><i class="fa fa-eye"></i></a>

                                    <div class="form-button-action">
                                        <!-- Modifier : Ajout des data-bs-toggle et data-bs-target -->
                                        <button
                                            type="button"
                                            class="btn btn-warning btn-sm edit-button"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editRowModal"
                                            data-patient_update="{{ $patient }}"
                                        >
                                            <i class="fa fa-edit"></i>
                                        </button>

                                    </div>

                                    <div class="form-button-action">
                                        <!-- Supprimer : Ajout des data-bs-toggle et data-bs-target -->
                                        <button
                                        type="button"
                                        class="btn btn-danger btn-sm delete-button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteRowModal"
                                        data-patient="{{$patient}}"
                                        >
                                        <i class="fa fa-trash"></i>
                                        </button>
                                    </div>

                                    <div class="form-button-action">
                                        <button type="button" class="btn btn-outline-enhanced btn-sm-enhanced mt-2 add-file-button" data-bs-toggle="modal" data-patient="{{$patient}}" data-bs-target="#uploadFileModal">
                                            <i class="fas fa-upload"></i>
                                        </button>
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
                                    <span class="fw-mediumbold">Nouvelle</span>
                                    <span class="fw-light">Patiente</span>
                                </h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <p class="small">Créez une nouvelle patiente en remplissant le formulaire ci-dessous.</p>
                                <form id="addDepartmentForm" action="{{ route('patient.store') }}" method="POST">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-6 form-group">
                                            <label>Prénom :</label>
                                            <input type="text" name="first_name" class="form-control" placeholder="Entrez votre prénom" value="{{ old('first_name') }}" required>
                                            @error('first_name')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 form-group">
                                            <label>Nom :</label>
                                            <input type="text" name="last_name" class="form-control" placeholder="Entrez votre nom de famille" value="{{ old('last_name') }}" required>
                                            @error('last_name')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>

                                        {{-- <div class="col-md-6 form-group">
                                            <label>Email :</label>
                                            <input type="email" name="email" class="form-control" placeholder="exemple@domaine.com" value="{{ old('email') }}">
                                            @error('email')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div> --}}

                                        <div class="col-md-6 form-group">
                                            <label>Téléphone :</label>
                                            <input type="number" name="phone" class="form-control" placeholder="Entrez votre numéro de téléphone" value="{{ old('phone') }}" required>
                                            @error('phone')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>

                                        <input type="hidden" name="gender" value="Femme">

                                        {{-- <div class="col-md-6 form-group">
                                            <label>Genre :</label>
                                            <select name="gender" class="form-control" required>
                                                <option value="">-- Sélectionnez votre genre --</option>
                                                <option value="Homme" {{ old('gender') == 'Homme' ? 'selected' : '' }}>Homme</option>
                                                <option value="Femme" {{ old('gender') == 'Femme' ? 'selected' : '' }}>Femme</option>
                                                <option value="Autre" {{ old('gender') == 'Autre' ? 'selected' : '' }}>Autre</option>
                                            </select>
                                            @error('gender')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div> --}}

                                        <div class="col-md-6 form-group">
                                            <label>Situation matrimoniale :</label>
                                            <select name="marital_status" class="form-control" required>
                                                <option value="">-- Sélectionnez votre situation --</option>
                                                <option value="Marié(e)" {{ old('marital_status') == 'Marié(e)' ? 'selected' : '' }}>Marié(e)</option>
                                                <option value="Célibataire" {{ old('marital_status') == 'Célibataire' ? 'selected' : '' }}>Célibataire</option>
                                                <option value="Autre" {{ old('marital_status') == 'Autre' ? 'selected' : '' }}>Autre</option>
                                            </select>
                                            @error('marital_status')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 form-group">
                                            <label>Groupe sanguin :</label>
                                            <select name="blood_group" class="form-control" required>
                                                <option value="">-- Sélectionnez votre groupe sanguin --</option>
                                                @foreach(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $group)
                                                    <option value="{{ $group }}" {{ old('blood_group') == $group ? 'selected' : '' }}>{{ $group }}</option>
                                                @endforeach
                                            </select>
                                            @error('blood_group')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 form-group">
                                            <label>Age :</label>
                                            <input type="number" name="age" class="form-control" placeholder="Age" value="{{ old('age') }}" required>
                                            @error('age')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 form-group">
                                            <label>Nom du proche :</label>
                                            <input type="text" name="relative_name" class="form-control" placeholder="Nom d’un proche à contacter" value="{{ old('relative_name') }}">
                                            @error('relative_name')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 form-group">
                                            <label>Téléphone du proche :</label>
                                            <input type="number" name="relative_phone" class="form-control" placeholder="Numéro de téléphone du proche" value="{{ old('relative_phone') }}">
                                            @error('relative_phone')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 form-group">
                                            <label>Profession :</label>
                                            <input type="text" name="occupation" class="form-control" placeholder="Votre profession" value="{{ old('occupation') }}" required>
                                            @error('occupation')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 form-group">
                                            <label>Localisation :</label>
                                            <input type="text" name="location" class="form-control" placeholder="Adresse complète, quartier, ville..." value="{{ old('location') }}" required>
                                            @error('location')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>

                                    </div>

                                </form>
                            </div>
                            <div class="modal-footer border-0">
                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">
                                    Fermer
                                </button>

                                <button type="submit" id="addRowButton" class="btn btn-primary" form="addDepartmentForm">
                                    Ajouter
                                    <div class="spinner-border spinner-border-sm text-light" role="status" id="addLoader" style="display: none;">
                                        <span class="sr-only">Loading...</span>
                                    </div>
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
                                    <span class="fw-light"> patient</span>
                                </h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form id="editDepartmentForm" action="" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="row">
                                        <input type="hidden" name="id" id="edit_id" value="{{ $patient->id ?? '' }}">
                                        <div class="col-md-6 form-group">
                                            <label>Prénom :</label>
                                            <input type="text" name="first_name" id="edit_first_name" class="form-control" placeholder="Entrez votre prénom" value="{{ $patient->first_name ?? old('first_name') }}" required>
                                            @error('first_name')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 form-group">
                                            <label>Nom :</label>
                                            <input type="text" name="last_name" id="edit_last_name" class="form-control" placeholder="Entrez votre nom de famille" value="{{ $patient->last_name ?? old('last_name') }}" required>
                                            @error('last_name')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 form-group">
                                            <label>Téléphone :</label>
                                            <input type="number" name="phone" id="edit_phone" class="form-control" placeholder="Entrez votre numéro de téléphone" value="{{ $patient->phone ?? old('phone') }}" required>
                                            @error('phone')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>

                                        <input type="hidden" name="gender" value="Femme">
                                        {{-- Note: Le champ 'gender' est actuellement un input hidden. Si vous souhaitez le modifier via JS,
                                             vous devrez le rendre visible (ex: <select>) et lui donner un id. --}}

                                        <div class="col-md-6 form-group">
                                            <label>Situation matrimoniale :</label>
                                            <select name="marital_status" id="edit_marital_status" class="form-control" required>
                                                <option value="">-- Sélectionnez votre situation --</option>
                                                <option value="Marié(e)" {{ ($patient->marital_status ?? old('marital_status')) == 'Marié(e)' ? 'selected' : '' }}>Marié(e)</option>
                                                <option value="Célibataire" {{ ($patient->marital_status ?? old('marital_status')) == 'Célibataire' ? 'selected' : '' }}>Célibataire</option>
                                                <option value="Autre" {{ ($patient->marital_status ?? old('marital_status')) == 'Autre' ? 'selected' : '' }}>Autre</option>
                                            </select>
                                            @error('marital_status')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 form-group">
                                            <label>Groupe sanguin :</label>
                                            <select name="blood_group" id="edit_blood_group" class="form-control" required>
                                                <option value="">-- Sélectionnez votre groupe sanguin --</option>
                                                @foreach(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $group)
                                                    <option value="{{ $group }}" {{ ($patient->blood_group ?? old('blood_group')) == $group ? 'selected' : '' }}>{{ $group }}</option>
                                                @endforeach
                                            </select>
                                            @error('blood_group')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 form-group">
                                            <label>Age :</label>
                                            <input type="number" name="age" id="edit_age" class="form-control" placeholder="Age" value="{{ $patient->age ?? old('age') }}" required>
                                            @error('age')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 form-group">
                                            <label>Nom du proche :</label>
                                            <input type="text" name="relative_name" id="edit_relative_name" class="form-control" placeholder="Nom d’un proche à contacter" value="{{ $patient->relative_name ?? old('relative_name') }}">
                                            @error('relative_name')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 form-group">
                                            <label>Téléphone du proche :</label>
                                            <input type="number" name="relative_phone" id="edit_relative_phone" class="form-control" placeholder="Numéro de téléphone du proche" value="{{ $patient->relative_phone ?? old('relative_phone') }}">
                                            @error('relative_phone')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 form-group">
                                            <label>Profession :</label>
                                            <input type="text" name="occupation" id="edit_occupation" class="form-control" placeholder="Votre profession" value="{{ $patient->occupation ?? old('occupation') }}" required>
                                            @error('occupation')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 form-group">
                                            <label>Localisation :</label>
                                            <input type="text" name="location" id="edit_location" class="form-control" placeholder="Adresse complète, quartier, ville..." value="{{ $patient->location ?? old('location') }}" required>
                                            @error('location')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <div class="modal-footer border-0">
                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Fermer</button>

                                <!-- Bouton pour la modification -->
                                <button type="submit" class="btn btn-success" id="editRowButton" form="editDepartmentForm">
                                    Modifier
                                    <div class="spinner-border spinner-border-sm text-light" role="status" id="editLoader" style="display: none;">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Delete -->
                <div class="modal fade" id="deleteRowModal" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header border-0">
                                <h5 class="modal-title">Êtes-vous sûr de vouloir supprimer ce patient ?</h5>
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

                <!-- Modal Upload Fichier -->
                <div class="modal fade" id="uploadFileModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content modal-content-enhanced">
                            <div class="modal-header modal-header-enhanced">
                                <h5 class="modal-title">
                                    <i class="fas fa-cloud-upload-alt me-2"></i>
                                    Ajouter un Document
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <form id="addFileForm" action="" method="post" enctype="multipart/form-data">
                                @csrf
                                <div class="modal-body p-4">

                                    <div class="file-upload-zone" onclick="document.getElementById('fileInput').click()">
                                        <i class="fas fa-cloud-upload-alt text-primary fa-3x mb-3"></i>
                                        <h6>Glissez votre fichier ici ou cliquez pour parcourir</h6>
                                        <p class="text-muted mb-0">PDF, JPG, PNG, DOCX - Max 10MB</p>
                                        <input type="file" id="fileInput" name="file" class="d-none" accept=".pdf,.jpg,.jpeg,.png,.docx">
                                    </div>
                                    <div class="form-group-enhanced mt-3">
                                        <label class="form-label-enhanced">
                                            <i class="fas fa-comment text-secondary"></i>
                                            Description du Document
                                        </label>
                                        <input type="text" name="name" class="form-control" placeholder="Ex: Radiographie thoracique du 02/06/2025">
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-enhanced" data-bs-dismiss="modal">
                                        <i class="fas fa-times"></i>
                                        Annuler
                                    </button>

                                    <button type="submit" form="addFileForm" class="btn btn-primary-enhanced">
                                        <i class="fas fa-upload"></i>
                                        Uploader
                                    </button>
                                </div>
                            </form>
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

        // Événement pour modifier un patient
        $(document).on('click', '.edit-button', function() {
            var patient = $(this).data('patient_update');
            // Mettre à jour le champ du modal
            $('#edit_id').val(patient.id);
            $('#edit_first_name').val(patient.first_name);
            $('#edit_last_name').val(patient.last_name);
            $('#edit_email').val(patient.email);
            $('#edit_phone').val(patient.phone);
            $('#edit_gender').val(patient.gender);
            $('#edit_marital_status').val(patient.marital_status);
            $('#edit_blood_group').val(patient.blood_group);
            $('#edit_birth_date').val(patient.birth_date);
            $('#edit_relative_name').val(patient.relative_name);
            $('#edit_relative_phone').val(patient.relative_phone);
            $('#edit_district').val(patient.district);
            $('#edit_location').val(patient.location);
            $('#edit_occupation').val(patient.occupation);
            $('#edit_department').val(patient.description);

            $('#editDepartmentForm').attr('action', '/patient/' + patient.id);

            // Afficher le modal
            $('#editRowModal').modal('show');
        });

        $(document).on('click', '.add-file-button', function() {
            var patient = $(this).data('patient');

            // Mettre à jour l'action du formulaire de suppression avec l'ID du patient
            $('#addFileForm').attr('action', '/patient/file/' + patient.id);

            // Afficher le modal de confirmation
            $('#uploadFileModal').modal('show');
        });

        // Événement pour supprimer un patient
        $(document).on('click', '.delete-button', function() {
            var patient = $(this).data('patient');

            // Afficher le nom du patient à supprimer
            $('#department_name_to_delete').text("Voulez-vous vraiment supprimer le patient : " + patient.first_name +' '+ patient.last_name + " ?");

            // Mettre à jour l'action du formulaire de suppression avec l'ID du patient
            $('#delete_id').val(patient.id);
            $('#deleteDepartmentForm').attr('action', '/patient/' + patient.id);

            // Afficher le modal de confirmation
            $('#deleteRowModal').modal('show');
        });

        // Afficher le loader pour l'ajout de patient
        $('#addDepartmentForm').on('submit', function() {
            $('#addRowButton').prop('disabled', true);  // Désactive le bouton pour éviter plusieurs clics
            $('#addLoader').show();  // Affiche le loader
        });

        // Afficher le loader pour la modification de patient
        $('#editDepartmentForm').on('submit', function() {
            $('#editRowButton').prop('disabled', true);  // Désactive le bouton pour éviter plusieurs clics
            $('#editLoader').show();  // Affiche le loader
        });

        // Afficher le loader pour la suppression de patient
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
