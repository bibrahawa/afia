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
            <a href="{{ route('insurance-companies.index') }}">Compagnies d'Assurance</a>
          </li>
        </ul>
      </div>

      <div class="row">
        <div class="col-md-12">
          <div class="card">
            <div class="card-header">
              <div class="d-flex align-items-center">
                <h4 class="card-title">Liste des compagnies d'assurance</h4>
                @can('insurance_company.create')
                    <button
                    class="btn btn-primary btn-round ms-auto"
                    data-bs-toggle="modal"
                    data-bs-target="#addRowModal"
                    >
                    <i class="fa fa-plus"></i> Ajouter une compagnie
                    </button>
                @endcan
              </div>
            </div>

            <div class="card-body">
                <!-- Messages de feedback -->
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fa fa-check-circle me-2"></i>
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fa fa-times-circle me-2"></i>
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="table-responsive">
                    <table id="add-row" class="display table table-striped table-hover">
                        <thead class="bg-primary text-white">
                            <tr>
                            <th style="width: 5%">ID</th>
                            <th>Nom</th>
                            {{-- <th>Code</th> --}}
                            <th>Contact</th>
                            <th>Téléphone</th>
                            {{-- <th>Total</th> --}}
					        {{-- <th>Part Patient</th> --}}
					        {{-- <th>Montant Du</th> --}}
                            {{-- <th>Email</th> --}}
                            <th>Statut</th>
                            <th style="width: 10%">Action</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            {{-- <th>Code</th> --}}
                            <th>Contact</th>
                            <th>Téléphone</th>
                            {{-- <th>Total</th> --}}
					        {{-- <th>Part Patient</th> --}}
					        {{-- <th>Montant Du</th> --}}
                            {{-- <th>Email</th> --}}
                            <th>Statut</th>
                            <th>Action</th>
                            </tr>
                        </tfoot>
                        <tbody>
                            @forelse($companies as $index => $company)
                                <tr>
                                    <td>{{ ++$index }}</td>
                                    <td>{{ $company->name }}</td>
                                    {{-- <td><span class="badge bg-secondary">{{ $company->code }}</span></td> --}}
                                    <td>{{ $company->contact_person ?: '-' }}</td>
                                    <td>
                                        @if($company->phone)
                                            <a href="tel:{{ $company->phone }}" class="text-decoration-none">
                                                {{ $company->phone }}
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    {{-- <td>
                                        @if($company->email)
                                            <a href="mailto:{{ $company->email }}" class="text-decoration-none">
                                                {{ $company->email }}
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td> --}}
                                    <td>
                                        @php
                                            $statusClass = match($company->status) {
                                                'active' => 'bg-success',
                                                'inactive' => 'bg-warning text-dark',
                                                default => 'bg-light text-dark'
                                            };
                                        @endphp
                                        <span class="badge {{ $statusClass }}">
                                            {{ ucfirst($company->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="form-button-action">
                                            <!-- Voir détails -->
                                            @can('insurance_company.view')
                                                <button
                                                    type="button"
                                                    class="btn btn-info btn-round btn-sm view-button"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#viewRowModal"
                                                    data-info="{{ $company->code }},{{ $company->name }},{{ $company->contact_person }},{{ $company->phone }},{{ $company->email }},{{ $company->default_coverage_percentage }},{{ $company->status }},{{ $company->contract_start_date }},{{ $company->contract_end_date }},{{ $company->address }},{{ $company->notes }}"
                                                >
                                                    <i class="fa fa-eye"></i>
                                                </button>
                                            @endcan

                                            <!-- Modifier -->
                                            @can('insurance_company.edit')
                                                <button
                                                    type="button"
                                                    class="btn btn-warning btn-round btn-sm edit-button"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editRowModal"
                                                    data-info="{{ $company->id }},{{ $company->name }},{{ $company->code }},{{ $company->contact_person }},{{ $company->phone }},{{ $company->email }},{{ $company->default_coverage_percentage }},{{ $company->status }},{{ $company->contract_start_date }},{{ $company->contract_end_date }}, {{ $company->address }},{{ $company->notes }}"
                                                >
                                                    <i class="fa fa-edit"></i>
                                                </button>
                                            @endcan

                                            <!-- Supprimer -->
                                            @can('insurance_company.delete')
                                                <button
                                                    type="button"
                                                    class="btn btn-danger btn-round btn-sm delete-button"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteRowModal"
                                                    data-id="{{$company->id}}"
                                                    data-name="{{$company->name}}"
                                                >
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <p class="text-muted">Aucune compagnie d'assurance trouvée.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($companies->hasPages())
                    <div class="d-flex justify-content-center mt-3">
                        {{ $companies->links() }}
                    </div>
                @endif

                <!-- Modal Add -->
                <div class="modal fade" id="addRowModal" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header border-0">
                        <h5 class="modal-title">
                            <span class="fw-mediumbold"> Nouvelle</span>
                            <span class="fw-light"> Compagnie d'Assurance</span>
                        </h5>
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        </div>
                        <div class="modal-body">
                        <p class="small">Créez une nouvelle compagnie d'assurance en remplissant le formulaire ci-dessous.</p>
                        <form id="addCompanyForm" action="{{ route('insurance-companies.store') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group form-group-default">
                                    <label>Nom de la compagnie <span class="text-danger">*</span></label>
                                    <input
                                        id="name"
                                        name="name"
                                        type="text"
                                        class="form-control"
                                        placeholder="Entrez le nom"
                                        required
                                    />
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group form-group-default">
                                    <label>Code unique <span class="text-danger">*</span></label>
                                    <input
                                        id="code"
                                        name="code"
                                        type="text"
                                        class="form-control"
                                        placeholder="Entrez le code"
                                        required
                                    />
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group form-group-default">
                                    <label>Personne de contact</label>
                                    <input
                                        id="contact_person"
                                        name="contact_person"
                                        type="text"
                                        class="form-control"
                                        placeholder="Nom du contact"
                                    />
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group form-group-default">
                                    <label>Téléphone</label>
                                    <input
                                        id="phone"
                                        name="phone"
                                        type="tel"
                                        class="form-control"
                                        placeholder="Numéro de téléphone"
                                    />
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group form-group-default">
                                    <label>Email</label>
                                    <input
                                        id="email"
                                        name="email"
                                        type="email"
                                        class="form-control"
                                        placeholder="Adresse email"
                                    />
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="form-group form-group-default">
                                    <label>Pourcentage de couverture par défaut</label>
                                    <input
                                        id="default_coverage_percentage"
                                        name="default_coverage_percentage"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="100"
                                        class="form-control"
                                        placeholder="Ex: 80.00"
                                    />
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group form-group-default">
                                    <label>Date début contrat</label>
                                    <input
                                        id="contract_start_date"
                                        name="contract_start_date"
                                        type="date"
                                        class="form-control"
                                    />
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group form-group-default">
                                    <label>Date fin contrat</label>
                                    <input
                                        id="contract_end_date"
                                        name="contract_end_date"
                                        type="date"
                                        class="form-control"
                                    />
                                    </div>
                                </div>
                            </div>
                           

                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group form-group-default">
                                    <label>Adresse</label>
                                    <textarea
                                        id="address"
                                        name="address"
                                        class="form-control"
                                        rows="2"
                                        placeholder="Adresse complète"
                                    ></textarea>
                                    </div>
                                </div>
                                
                                <div class="col-sm-6">
                                    <div class="form-group form-group-default">
                                    <label>Notes</label>
                                    <textarea
                                        id="notes"
                                        name="notes"
                                        class="form-control"
                                        rows="3"
                                        placeholder="Notes additionnelles"
                                    ></textarea>
                                    </div>
                                </div>
                            </div>
                        </form>
                        </div>
                        <div class="modal-footer border-0">
                        <button type="submit" id="addRowButton" class="btn btn-primary" form="addCompanyForm">
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

                <!-- Modal View -->
                <div class="modal fade" id="viewRowModal" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header border-0">
                                <h5 class="modal-title">
                                    <span class="fw-mediumbold"> Détails</span>
                                    <span class="fw-light"> Compagnie d'Assurance</span>
                                </h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="fw-bold">Nom :</label>
                                            <p id="view_name"></p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="fw-bold">Code :</label>
                                            <p id="view_code"></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="fw-bold">Contact :</label>
                                            <p id="view_contact_person"></p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="fw-bold">Téléphone :</label>
                                            <p id="view_phone"></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="fw-bold">Email :</label>
                                            <p id="view_email"></p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="fw-bold">Couverture par défaut :</label>
                                            <p id="view_default_coverage_percentage"></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="fw-bold">Date début contrat :</label>
                                            <p id="view_contract_start_date"></p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="fw-bold">Date fin contrat :</label>
                                            <p id="view_contract_end_date"></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="fw-bold">Notes :</label>
                                            <p id="view_notes"></p>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="fw-bold">Adresse :</label>
                                            <p id="view_address"></p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="fw-bold">Statut :</label>
                                        <p id="view_status"></p>
                                    </div>
                                </div>

                            </div>
                            <div class="modal-footer border-0">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
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
                                    <span class="fw-light"> Compagnie d'Assurance</span>
                                </h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form id='editCompanyForm' action="{{route('insurance-companies.update')}}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" id="edit_id" name="id" />
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Nom de la compagnie <span class="text-danger">*</span></label>
                                                <input
                                                    id="edit_name"
                                                    name="name"
                                                    type="text"
                                                    class="form-control"
                                                    placeholder="Entrez le nom"
                                                    required
                                                />
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Code unique <span class="text-danger">*</span></label>
                                                <input
                                                    id="edit_code"
                                                    name="code"
                                                    type="text"
                                                    class="form-control"
                                                    placeholder="Entrez le code"
                                                    required
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Personne de contact</label>
                                                <input
                                                    id="edit_contact_person"
                                                    name="contact_person"
                                                    type="text"
                                                    class="form-control"
                                                    placeholder="Nom du contact"
                                                />
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Téléphone</label>
                                                <input
                                                    id="edit_phone"
                                                    name="phone"
                                                    type="tel"
                                                    class="form-control"
                                                    placeholder="Numéro de téléphone"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Email</label>
                                                <input
                                                    id="edit_email"
                                                    name="email"
                                                    type="email"
                                                    class="form-control"
                                                    placeholder="Adresse email"
                                                />
                                            </div>
                                        </div>
                                        <div class="col-sm-3">
                                            <div class="form-group form-group-default">
                                                <label>Pourcentage de couverture par défaut</label>
                                                <input
                                                    id="edit_default_coverage_percentage"
                                                    name="default_coverage_percentage"
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    max="100"
                                                    class="form-control"
                                                    placeholder="Ex: 80.00"
                                                />
                                            </div>
                                        </div>
                                        <div class="col-sm-3">
                                            <div class="form-group form-group-default">
                                                <label>Statut</label>
                                                <select name="status" id="edit_status" class="form-control">
                                                    <option value="active">Actif</option>
                                                    <option value="inactive">Inactif</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Date début contrat</label>
                                                <input
                                                    id="edit_contract_start_date"
                                                    name="contract_start_date"
                                                    type="date"
                                                    class="form-control"
                                                />
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Date fin contrat</label>
                                                <input
                                                    id="edit_contract_end_date"
                                                    name="contract_end_date"
                                                    type="date"
                                                    class="form-control"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Adresse</label>
                                                <textarea
                                                    id="edit_address"
                                                    name="address"
                                                    class="form-control"
                                                    rows="2"
                                                    placeholder="Adresse complète"
                                                ></textarea>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Notes</label>
                                                <textarea
                                                    id="edit_notes"
                                                    name="notes"
                                                    class="form-control"
                                                    rows="3"
                                                    placeholder="Notes additionnelles"
                                                ></textarea>
                                            </div>
                                        </div>
                                    </div>
                            </div>
                            <div class="modal-footer border-0">
                                <button type="submit" class="btn btn-success" id="editRowButton" form="editCompanyForm">
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
                                <h5 class="modal-title">Êtes-vous sûr de vouloir supprimer cette compagnie ?</h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form id="deleteCompanyForm" action="{{ route('insurance-companies.destroy') }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <p id="company_name_to_delete"></p>
                                    <input type="hidden" id="delete_id" name="id">
                                </form>
                            </div>
                            <div class="modal-footer border-0">
                                <button type="submit" class="btn btn-danger" id="deleteRowButton" form="deleteCompanyForm">
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
        // Événement pour voir les détails d'une compagnie
        $(document).on('click', '.view-button', function() {
            var details = $(this).data('info').split(',');
            
            $('#view_code').text(details[0] || '-');
            $('#view_name').text(details[1] || '-');
            $('#view_contact_person').text(details[2] || '-');
            $('#view_phone').text(details[3] || '-');
            $('#view_email').text(details[4] || '-');
            $('#view_default_coverage_percentage').text(details[5] ? details[5] + '%' : '-');
            $('#view_status').html('<span class="badge bg-' + getStatusClass(details[6]) + '">' + (details[6] ? details[6].charAt(0).toUpperCase() + details[6].slice(1) : 'Non défini') + '</span>');
            $('#view_contract_start_date').text(details[7] || '-');
            $('#view_contract_end_date').text(details[8] || '-');
            $('#view_address').text(details[10] || '-');
            $('#view_notes').text(details[11] || '-');
        });

        // Événement pour modifier une compagnie
        $(document).on('click', '.edit-button', function() {
            var details = $(this).data('info').split(',');
            
            $('#edit_id').val(details[0]);
            $('#edit_name').val(details[1]);
            $('#edit_code').val(details[2]);
            $('#edit_contact_person').val(details[3]);
            $('#edit_phone').val(details[4]);
            $('#edit_email').val(details[5]);
            $('#edit_default_coverage_percentage').val(details[6]);
            $('#edit_status').val(details[7]);
            $('#edit_contract_start_date').val(details[8]);
            $('#edit_contract_end_date').val(details[9]);
            $('#edit_address').val(details[10]);
            $('#edit_notes').val(details[11]);

            // Mettre à jour l'action du formulaire avec l'ID
        });

        // Événement pour supprimer une compagnie
        $(document).on('click', '.delete-button', function() {
            var id = $(this).data('id');
            var name = $(this).data('name');

            // Afficher le nom de la compagnie à supprimer
            $('#company_name_to_delete').text("Voulez-vous vraiment supprimer la compagnie : " + name + " ?");

            // Mettre à jour l'action du formulaire de suppression avec l'ID
            $('#delete_id').val(id);
        });

        // Afficher le loader pour l'ajout de compagnie
        $('#addCompanyForm').on('submit', function() {
            $('#addRowButton').prop('disabled', true);
            $('#addLoader').show();
        });

        // Afficher le loader pour la modification de compagnie
        $('#editCompanyForm').on('submit', function() {
            $('#editRowButton').prop('disabled', true);
            $('#editLoader').show();
        });

        // Afficher le loader pour la suppression de compagnie
        $('#deleteCompanyForm').on('submit', function() {
            $('#deleteRowButton').prop('disabled', true);
            $('#deleteLoader').show();
        });

        // Fonction pour obtenir la classe CSS du statut
        function getStatusClass(status) {
            switch(status) {
                case 'active':
                    return 'success';
                case 'inactive':
                    return 'warning';
                default:
                    return 'light';
            }
        }

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