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
            <a href="{{ route('package.index') }}">Package</a>
          </li>
        </ul>
      </div>

      <div class="row">
        <div class="col-md-12">
          <div class="card">
            <div class="card-header">
              <div class="d-flex align-items-center">
                <h4 class="card-title">Modification d'un package</h4>
              </div>
            </div>
            <div class="card-body">
                <form id="editPackageForm" action="{{ route('package.update', $package->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="form-group form-group-default">
                                <label>Nom du package</label>
                                <input name="name" type="text" id="edit_name" class="form-control" placeholder="Entrez le nom" value="{{ old('name', $package->name) }}" required/>
                                @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="col-sm-12">
                            <div class="form-group">
                                <label>Départements:</label>
                                <select name="department_id" id="edit_department_id" class="form-control selectpicker" data-live-search="true" title="Sélectionnez un département">
                                    @foreach ($departments as $dep)
                                        <option value="{{ $dep->id }}" {{ $dep->id == $package->department_id ? 'selected' : '' }}>
                                            {{ $dep->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('department') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>

                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label>Famille d'actes (garanties des assurances)</label>
                                                <select name="famille_acte" class="form-control">
                                                    @foreach(\App\Enums\Assurance\FamilleActe::cases() as $familleActe)
                                                        <option value="{{ $familleActe->value }}" @selected(($package->famille_acte ?? 'soins') === $familleActe->value)>{{ $familleActe->libelle() }}</option>
                                                    @endforeach
                                                </select>
                                                <small class="form-text text-muted">Un forfait accouchement se classe en « Maternité ».</small>
                                            </div>
                                        </div>


                        <div class="col-sm-12">
                            <div class="form-group">
                                <label>Ajouter des examens:</label>
                                <select name="tests[]" id="edit_tests" class="form-control selectpicker" data-live-search="true" title="Sélectionnez les examens" multiple>
                                    @foreach($tests as $test)
                                        <option value="{{ $test->id }}"
                                            {{ in_array($test->id, $package->tests->pluck('id')->toArray()) ? 'selected' : '' }}>
                                            {{ $test->name }} = {{ number_format($test->amount, 0, ',', ' ') }} FG
                                        </option>
                                    @endforeach
                                </select>
                                @error('tests') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="col-sm-12">
                            <div class="form-group">
                                <label>Ajouter des services:</label>
                                @php
                                    $packageDepartmentId = $package->department_id;
                                    $selectedServices = $package->services->pluck('id')->toArray();
                                @endphp

                                <select name="services[]" id="edit_services" class="form-control selectpicker" data-live-search="true" title="Sélectionnez les services" multiple>
                                    @foreach ($services as $service)
                                        @if ($service->department_id == $packageDepartmentId)
                                            <option value="{{ $service->id }}"
                                                {{ in_array($service->id, $selectedServices) ? 'selected' : '' }}>
                                                {{ $service->name }}
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        </div>


                        <div class="col-sm-12">
                            <div class="form-group form-group-default">
                                <label>Description</label>
                                <textarea name="description" id="edit_description" class="form-control" placeholder="Description">{{ old('description', $package->description) }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer border-0">
                        <button type="submit" class="btn btn-primary" id="editRowButton" form="editPackageForm">
                            Sauvegarder mes modifications
                            <div class="spinner-border spinner-border-sm text-light" role="status" id="editLoader" style="display: none;">
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

        $(document).ready(function () {
            $('.selectpicker').selectpicker();

            $('#editPackageForm').on('submit', function () {
                $('#editRowButton').prop('disabled', true);
                $('#editLoader').show();
            });
        });
    </script>
@endsection
