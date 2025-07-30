@extends('layouts.backend')

@section('content')
<div class="container">
    <h2>Couvertures d’assurance</h2>

    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addModal">Ajouter</button>
    @include('insurance_coverages._add_modal')

    <table class="table">
        <thead>
            <tr>
                <th>Assurance</th>
                <th>Type</th>
                <th>ID</th>
                <th>%</th>
                <th>De</th>
                <th>À</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($coverages as $coverage)
            <tr>
                <td>{{ $coverage->insuranceCompany->name }}</td>
                <td>{{ class_basename($coverage->coverageable_type) }}</td>
                <td>{{ $coverage->coverageable_id }}</td>
                <td>{{ $coverage->coverage_percentage }}%</td>
                <td>{{ $coverage->valid_from }}</td>
                <td>{{ $coverage->valid_to ?? '-' }}</td>
                <td>
                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal{{ $coverage->id }}">Modifier</button>
                    <form method="POST" action="{{ route('insurance-coverages.destroy', $coverage->id) }}" class="d-inline">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger btn-sm" onclick="return confirm('Supprimer ?')">Supprimer</button>
                    </form>
                </td>
            </tr>
            @include('insurance_coverages._edit_modal', ['coverage' => $coverage])
            @endforeach
        </tbody>
    </table>

    {{ $coverages->links() }}

    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('insurance-coverages.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header"><h5>Ajouter une couverture</h5></div>
                <div class="modal-body">
                <select name="insurance_company_id" class="form-control mb-2" required>
                    <option value="">-- Assurance --</option>
                    @foreach($companies as $company)
                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>

                <select name="coverageable_type" class="form-control mb-2" required>
                    <option value="App\Models\Service">Service</option>
                    <option value="App\Models\Medicament">Médicament</option>
                    <option value="App\Models\Examen">Examen</option>
                </select>

                <input type="number" name="coverageable_id" class="form-control mb-2" placeholder="ID de l’acte" required>
                <input type="number" name="coverage_percentage" step="0.01" class="form-control mb-2" placeholder="% Couverture" required>
                <input type="date" name="valid_from" class="form-control mb-2" required>
                <input type="date" name="valid_to" class="form-control mb-2">
                </div>
                <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-primary">Ajouter</button>
                </div>
            </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="editModal{{ $coverage->id }}" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('insurance-coverages.update', $coverage->id) }}">
            @csrf @method('PUT')
            <div class="modal-content">
                <div class="modal-header"><h5>Modifier une couverture</h5></div>
                <div class="modal-body">
                <select name="insurance_company_id" class="form-control mb-2" required>
                    <option value="">-- Assurance --</option>
                    @foreach($companies as $company)
                    <option value="{{ $company->id }}" {{ $company->id == $coverage->insurance_company_id ? 'selected' : '' }}>{{ $company->name }}</option>
                    @endforeach
                </select>

                <select name="coverageable_type" class="form-control mb-2" required>
                    <option value="App\Models\Service" {{ $coverage->coverageable_type == 'App\Models\Service' ? 'selected' : '' }}>Service</option>
                    <option value="App\Models\Medicament" {{ $coverage->coverageable_type == 'App\Models\Medicament' ? 'selected' : '' }}>Médicament</option>
                    <option value="App\Models\Examen" {{ $coverage->coverageable_type == 'App\Models\Examen' ? 'selected' : '' }}>Examen</option>
                </select>

                <input type="number" name="coverageable_id" class="form-control mb-2" placeholder="ID de l’acte" value="{{ $coverage->coverageable_id }}" required>
                <input type="number" name="coverage_percentage" step="0.01" class="form-control mb-2" placeholder="% Couverture" value="{{ $coverage->coverage_percentage }}" required>
                <input type="date" name="valid_from" class="form-control mb-2" value="{{ $coverage->valid_from }}" required>
                <input type="date" name="valid_to" class="form-control mb-2" value="{{ $coverage->valid_to }}">
                </div>
                <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-primary">Modifier</button>
                </div>
            </div>
            </form>
        </div>
    </div>
    

</div>
@endsection
