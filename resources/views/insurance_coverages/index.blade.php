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
            <a href="{{ route('insurance-coverages.index') }}">Couverture d'Assurance</a>
          </li>
        </ul>
      </div>

      <div class="row">
        <div class="col-md-12">
          <div class="card">
            <div class="card-header">
              <div class="d-flex align-items-center">
                <h4 class="card-title">Liste des couvertures d'assurance</h4>
                @can('insurance_coverage.create')
                    <button
                    class="btn btn-primary btn-round ms-auto"
                    data-bs-toggle="modal"
                    data-bs-target="#addRowModal"
                    >
                    <i class="fa fa-plus"></i> Ajouter une couverture
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

                @if(session('errors'))
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
                                <th>Assurance</th>
                                <th>Type</th>
                                {{-- <th>Couverture</th> --}}
                                <th>Prix</th>
                                <th>Période</th>
                                {{-- <th>Validité</th> --}}
                                <th>Statut</th>
                                <th style="width: 10%">Action</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <th>ID</th>
                                <th>Assurance</th>
                                <th>Type</th>
                                {{-- <th>Couverture</th> --}}
                                <th>Prix</th>
                                <th>Période</th>
                                {{-- <th>Validité</th> --}}
                                <th>Statut</th>
                                <th>Action</th>
                            </tr>
                        </tfoot>
                        <tbody>
                            @forelse($coverages as $coverage)
                                <tr>
                                    <td>{{ $coverage->id }}</td>
                                    <td>{{ $coverage->insuranceCompany->name }}</td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            {{ class_basename($coverage->coverageable->name ?? $coverage->coverageable->nom ?? $coverage->coverageable->getFullNameAttribute()) }}
                                        </span>
                                    </td>
                                    <td>{{ number_format($coverage->acte_price) }} GNF</td> {{-- $coverage->acte_price }}</td>
                                    {{-- <td>
                                        @if($coverage->min_amount)
                                            Min: {{ number_format($coverage->min_amount, 2) }} F
                                        @endif
                                        @if($coverage->max_amount)
                                            Max: {{ number_format($coverage->max_amount, 2) }} F<br>
                                        @endif
                                    </td> --}}
                                    <td>
                                        @if($coverage->max_usage_count)
                                            {{ $coverage->max_usage_count }} / {{ $coverage->usage_period }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    {{-- <td>
                                        {{ $coverage->valid_from }} 
                                        @if($coverage->valid_to)
                                            <br>au {{ $coverage->valid_to }}
                                        @endif
                                    </td> --}}
                                    <td>
                                        @php
                                            $statusClass = match($coverage->status) {
                                                'active' => 'bg-success',
                                                'inactive' => 'bg-warning text-dark',
                                                default => 'bg-light text-dark'
                                            };
                                        @endphp
                                        <span class="badge {{ $statusClass }}">
                                            {{ ucfirst($coverage->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="form-button-action">
                                            @can('insurance_coverage.view')
                                                <button type="button" 
                                                        class="btn btn-info btn-round btn-sm view-button"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#viewRowModal"
                                                        data-info="{{ $coverage->id }},{{ $coverage->insuranceCompany->name }},{{ $coverage->coverageable->name ?? $coverage->coverageable->nom }},{{ $coverage->acte_price }},{{ $coverage->max_amount }},{{ $coverage->min_amount }},{{ $coverage->max_usage_count }},{{ $coverage->usage_period }},{{ $coverage->valid_from }},{{ $coverage->valid_to }},{{ $coverage->requires_preauthorization }},{{ $coverage->conditions }}, {{ $coverage->coverageable->id }}">
                                                    <i class="fa fa-eye"></i>
                                                </button>
                                            @endcan

                                            @can('insurance_coverage.edit')
                                                <button type="button"
                                                        class="btn btn-warning btn-round btn-sm edit-button"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#editRowModal"
                                                        data-info="{{ $coverage->id }},{{ $coverage->insuranceCompany->id }},{{ $coverage->coverageable_type }},{{ $coverage->acte_price }},{{ $coverage->max_amount }},{{ $coverage->min_amount }},{{ $coverage->max_usage_count }},{{ $coverage->usage_period }},{{ $coverage->valid_from }},{{ $coverage->valid_to }},{{ $coverage->requires_preauthorization }},{{ $coverage->conditions }}, {{ $coverage->coverageable->id }}">
                                                    <i class="fa fa-edit"></i>
                                                </button>
                                            @endcan

                                            @can('insurance_coverage.delete')
                                                <button type="button"
                                                        class="btn btn-danger btn-round btn-sm delete-button"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#deleteRowModal"
                                                        data-id="{{ $coverage->id }}"
                                                        data-name="{{ $coverage->insuranceCompany->name }}">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty

                            @endforelse
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
                                    <span class="fw-light">couverture d'Assurance</span>
                                </h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form id="addCompanyForm" action="{{ route('insurance-coverages.store') }}" method="POST">
                                    @csrf
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label>Compagnie d'Assurance <span class="text-danger">*</span></label>
                                                <select name="insurance_company_id" class="form-control" required>
                                                    <option value="">Sélectionner une compagnie</option>
                                                    @foreach($insuranceCompanies as $company)
                                                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="coverageable_select_picker">Acte couvert</label>
                                                <select id="coverageable_select_picker" class="form-control selectpicker" 
                                                        data-live-search="true"
                                                        data-selected-text-format="count" title="Sélectionner un ou plusieurs actes">
                                                    
                                                    <option selected>Sélectionner un acte</option>

                                                    <optgroup label="Services">
                                                        @foreach ($services as $service)
                                                            <option value="{{ $service->id}}" data-type="Service">{{$service->name}} </option>
                                                        @endforeach
                                                    </optgroup>

                                                    <optgroup label="Médicaments">
                                                        @foreach ($medicaments as $medicament)
                                                            <option value="{{ $medicament->id}}" data-type="Medicament">{{$medicament->nom}} </option>
                                                        @endforeach
                                                    </optgroup>

                                                    <optgroup label="Examens">
                                                        @foreach ($examens as $test)
                                                            <option value="{{ $test->id}}" data-type="Test">{{$test->name}} </option>
                                                        @endforeach
                                                    </optgroup>

                                                    <optgroup label="Packages">
                                                        @foreach ($packages as $package)
                                                            <option value="{{ $package->id}}" data-type="Package">{{$package->name}} </option>
                                                        @endforeach
                                                    </optgroup>

                                                    <optgroup label="Chambres">
                                                        @foreach ($chambres as $chambre)
                                                            <option value="{{ $chambre->id}}" data-type="Chambre">{{$chambre->getFullNameAttribute()}} </option>
                                                        @endforeach
                                                    </optgroup>

                                                </select>
                                            </div>
                                            <input type="hidden" name="coverageable_id" id="coverageable_id" />
                                            <input type="hidden" name="coverageable_type" id="coverageable_type" />
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-sm-4">
                                            <div class="form-group form-group-default">
                                                <label>Prix de l'acte <span class="text-danger">*</span></label>
                                                <input type="number" name="acte_price" class="form-control" required>
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="form-group form-group-default">
                                                <label>Montant min (FG)</label>
                                                <input type="number" name="min_amount" class="form-control" step="0.01" min="0">
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="form-group form-group-default">
                                                <label>Plafond (FG)</label>
                                                <input type="number" name="max_amount" class="form-control" step="0.01" min="0">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-sm-4">
                                            <div class="form-group form-group-default">
                                                <label>Nbre d'utilisations max</label>
                                                <input type="number" name="max_usage_count" class="form-control" min="0">
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="form-group form-group-default">
                                                <label>Période d'utilisation</label>
                                                <select name="usage_period" class="form-control">
                                                    <option value="">Sélectionner une période</option>
                                                    <option value="Mois">Mois</option>
                                                    <option value="Trimestre">Trimestre</option>
                                                    <option value="Annees">Années</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="form-group form-group-default">
                                                <label>Limite de montant par acte (FG)</label>
                                                <input type="number" name="coverage_amount_limit" class="form-control" step="0.01" min="0">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Date de début <span class="text-danger">*</span></label>
                                                <input type="date" name="valid_from" class="form-control" required>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Date de fin</label>
                                                <input type="date" name="valid_to" class="form-control">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-sm-12">
                                            <div class="form-group form-group-default">
                                                <label>Conditions</label>
                                                <textarea name="conditions" class="form-control" rows="3"></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Pré-autorisation requise</label>
                                                <select name="requires_preauthorization" class="form-control">
                                                    <option value="0">Non</option>
                                                    <option value="1">Oui</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Statut</label>
                                                <select name="status" class="form-control">
                                                    <option value="active">Actif</option>
                                                    <option value="inactive">Inactif</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer border-0">
                                <button type="submit" class="btn btn-primary" form="addCompanyForm">
                                    Ajouter
                                    <div class="spinner-border spinner-border-sm text-light" role="status" id="addLoader" style="display: none;">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                </button>
                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Fermer</button>
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
                                    <span class="fw-mediumbold">Détails</span> 
                                    <span class="fw-light">de la couverture</span>
                                </h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="fw-bold">Compagnie d'assurance :</label>
                                            <p id="view_insurance_company"></p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="fw-bold">Type de couverture :</label>
                                            <p id="view_coverageable_type"></p>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="fw-bold">Prix de l'acte :</label>
                                            <p id="view_acte_price"></p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="fw-bold">Montant minimum :</label>
                                            <p id="view_min_amount"></p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="fw-bold">Plafond :</label>
                                            <p id="view_max_amount"></p>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="fw-bold">Limite d'utilisation :</label>
                                            <p id="view_usage_limit"></p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="fw-bold">Montant limite par acte :</label>
                                            <p id="view_coverage_amount_limit"></p>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="fw-bold">Période de validité :</label>
                                            <p id="view_validity_period"></p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="fw-bold">Pré-autorisation requise :</label>
                                            <p id="view_preauth"></p>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="fw-bold">Conditions :</label>
                                            <p id="view_conditions"></p>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="fw-bold">Statut :</label>
                                            <p id="view_status"></p>
                                        </div>
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
                                    <span class="fw-mediumbold">Modifier</span>
                                    <span class="fw-light">la couverture</span>
                                </h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form id="editCoverageForm" action="{{ route('insurance-coverages.update') }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" id="edit_id" name="id">
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label>Compagnie d'Assurance <span class="text-danger">*</span></label>
                                                <select name="insurance_company_id" id="edit_insurance_company_id" class="form-control" required>
                                                    @foreach($insuranceCompanies as $company)
                                                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="edit_coverageable_select_picker">Acte couvert</label>
                                                <select id="edit_coverageable_select_picker" class="form-control selectpicker"
                                                        data-live-search="true"
                                                        data-selected-text-format="count"
                                                        title="Sélectionner un ou plusieurs actes">
                                                    
                                                    <option selected>Sélectionner un acte</option>

                                                    <optgroup label="Services">
                                                        @foreach ($services as $service)
                                                            <option value="{{ $service->id}}" data-type="Service">{{$service->name}} </option>
                                                        @endforeach
                                                    </optgroup>

                                                    <optgroup label="Médicaments">
                                                        @foreach ($medicaments as $medicament)
                                                            <option value="{{ $medicament->id}}" data-type="Medicament">{{$medicament->nom}} </option>
                                                        @endforeach
                                                    </optgroup>

                                                    <optgroup label="Examens">
                                                        @foreach ($examens as $test)
                                                            <option value="{{ $test->id}}" data-type="Test">{{$test->name}} </option>
                                                        @endforeach
                                                    </optgroup>

                                                    <optgroup label="Package">
                                                        @foreach ($packages as $package)
                                                            <option value="{{ $package->id}}" data-type="Package">{{$package->name}} </option>
                                                        @endforeach
                                                    </optgroup>

                                                     <optgroup label="Chambres">
                                                        @foreach ($chambres as $chambre)
                                                            <option value="{{ $chambre->id}}" data-type="Chambre">{{$chambre->getFullNameAttribute()}} </option>
                                                        @endforeach
                                                    </optgroup>
                                                </select>
                                            </div>
                                            <input type="hidden" name="coverageable_id" id="edit_coverageable_id" />
                                            <input type="hidden" name="coverageable_type" id="edit_coverageable_type" />
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-sm-4">
                                            <div class="form-group form-group-default">
                                                <label>Prix de l'acte <span class="text-danger">*</span></label>
                                                <input type="number" id="acte_price" name="acte_price" class="form-control" required>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Montant minimum (F)</label>
                                                <input type="number" id="edit_min_amount" name="min_amount" class="form-control" step="0.01" min="0">
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Plafond (F)</label>
                                                <input type="number" id="edit_max_amount" name="max_amount" class="form-control" step="0.01" min="0">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-sm-4">
                                            <div class="form-group form-group-default">
                                                <label>Nombre d'utilisations max</label>
                                                <input type="number" id="edit_max_usage_count" name="max_usage_count" class="form-control" min="0">
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="form-group form-group-default">
                                                <label>Période d'utilisation</label>
                                                <select name="usage_period" id="edit_usage_period" class="form-control">
                                                    <option value="">Sélectionner une période</option>
                                                    <option value="Mois">Mois</option>
                                                    <option value="Trimestre">Trimestre</option>
                                                    <option value="Annees">Années</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="form-group form-group-default">
                                                <label>Limite de montant par acte (F)</label>
                                                <input type="number" id="edit_coverage_amount_limit" name="coverage_amount_limit" class="form-control" step="0.01" min="0">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Date de début <span class="text-danger">*</span></label>
                                                <input type="date" id="edit_valid_from" name="valid_from" class="form-control" required>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Date de fin</label>
                                                <input type="date" id="edit_valid_to" name="valid_to" class="form-control">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-sm-12">
                                            <div class="form-group form-group-default">
                                                <label>Conditions</label>
                                                <textarea id="edit_conditions" name="conditions" class="form-control" rows="3"></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Pré-autorisation requise</label>
                                                <select name="requires_preauthorization" id="edit_requires_preauthorization" class="form-control">
                                                    <option value="0">Non</option>
                                                    <option value="1">Oui</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Statut</label>
                                                <select name="status" id="edit_status" class="form-control">
                                                    <option value="active">Actif</option>
                                                    <option value="inactive">Inactif</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer border-0">
                                <button type="submit" class="btn btn-success" form="editCoverageForm">
                                    Modifier
                                    <div class="spinner-border spinner-border-sm text-light" role="status" id="editLoader" style="display: none;">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                </button>
                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Fermer</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Delete -->
                <div class="modal fade" id="deleteRowModal" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header border-0">
                                <h5 class="modal-title">Êtes-vous sûr de vouloir supprimer cette couverture ?</h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form id="deleteCompanyForm" action="{{ route('insurance-coverages.destroy') }}" method="POST">
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
    <script>
        $(document).ready(function() {
            // Initialisation de Bootstrap Select
            $('#coverageable_select_picker').selectpicker();
            // Écouteur d'événement pour le changement de sélection (AJOUT)
            $('#coverageable_select_picker').on('changed.bs.select', function (e, clickedIndex, isSelected, oldValue) {
                const selectedOptions = $(this).find('option:selected');
                let ids = [];
                let types = [];
                selectedOptions.each(function() {
                    ids.push($(this).val());
                    types.push($(this).data('type'));
                });

                $('#coverageable_id').val(ids.join(','));
                $('#coverageable_type').val(types.join(','));

                console.log($('#coverageable_id').val());
            });

            // 1. Initialisation de Bootstrap Select pour le modal d'édition
            $('#edit_coverageable_select_picker').selectpicker();

            // 3. Écouteur d'événement pour le changement de sélection (MODIFICATION)
            $('#edit_coverageable_select_picker').on('changed.bs.select', function (e, clickedIndex, isSelected, oldValue) {
                const selectedOptions = $(this).find('option:selected');
                let ids = [];
                let types = [];

                selectedOptions.each(function() {
                    ids.push($(this).val());
                    types.push($(this).data('type'));
                });

                $('#edit_coverageable_id').val(ids.join(','));
                $('#edit_coverageable_type').val(types.join(','));
            });
        });
    </script>

    <script type="text/javascript">
        // Event for viewing coverage details
        $(document).on('click', '.view-button', function() {
            var details = $(this).data('info').split(',');
            
            $('#view_insurance_company').text(details[1] || '-');
            $('#view_coverageable_type').text(details[2] || '-');
            $('#view_acte_price').text(details[3] ? details[3] + ' F' : '-');
            $('#view_min_amount').text(details[5] ? details[5] + ' F' : '-');
            $('#view_max_amount').text(details[4] ? details[4] + ' F' : '-');
            $('#view_usage_limit').text(
                details[6] && details[7] ? 
                `${details[6]} fois par ${details[7].toLowerCase()}` : 
                '-'
            );
            $('#view_validity_period').text(
                details[8] + 
                (details[9] ? ' au ' + details[9] : '')
            );
            $('#view_preauth').text(details[10] == 1 ? 'Oui' : 'Non');
            $('#view_conditions').text(details[11] || '-');
            $('#view_status').html(
                '<span class="badge bg-' + getStatusClass(details[6]) + '">' + 
                (details[6] ? details[6].charAt(0).toUpperCase() + details[6].slice(1) : 'Non défini') + 
                '</span>'
            );
        });

        $(document).on('click', '.edit-button', function() {

            var details = $(this).data('info').split(',');
            $('#edit_id').val(details[0]);
            $('#edit_insurance_company_id').val(details[1]);
            $('#edit_coverageable_type').val(details[2]);
            $('#acte_price').val(details[3]);
            $('#edit_max_amount').val(details[4]);
            $('#edit_min_amount').val(details[5]);
            $('#edit_max_usage_count').val(details[6]);
            $('#edit_usage_period').val(details[7]);
            $('#edit_valid_from').val(details[8]);
            $('#edit_valid_to').val(details[9]);
            $('#edit_requires_preauthorization').val(details[10]);
            $('#edit_conditions').val(details[11]);
            $('#edit_coverageable_id').val(details[12]);
        });

        // Event for deleting coverage
        $(document).on('click', '.delete-button', function() {
            var id = $(this).data('id');
            var name = $(this).data('name');
            
            $('#company_name_to_delete').text(
                "Voulez-vous vraiment supprimer la couverture pour " + name + " ?"
            );
            $('#delete_id').val(id);
        });

        // Show loader for add form submission
        $('#addCompanyForm').on('submit', function() {
            $('#addLoader').show();
        });

        // Show loader for edit form submission
        $('#editCoverageForm').on('submit', function() {
            $('#editLoader').show();
        });

        // Show loader for delete form submission
        $('#deleteCompanyForm').on('submit', function() {
            $('#deleteLoader').show();
        });

        // Function to get status CSS class
        function getStatusClass(status) {
            return status === 'active' ? 'success' : 
                   status === 'inactive' ? 'warning' : 
                   'light';
        }

        // Hide loaders when AJAX completes
        $(document).ajaxComplete(function() {
            $('.spinner-border').hide();
        });


    </script>
@endsection