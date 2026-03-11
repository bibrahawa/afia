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
                    <a href="{{ route('patient.index') }}">Patients</a>
                </li>
            </ul>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <a href="{{ route('patient.index') }}" class="btn-primary btn-sm">
                                <i class="icon-arrow-left" style="color: white"> </i>
                            </a>
                            &nbsp;&nbsp;&nbsp;&nbsp;
                            <h4 class="card-title">Historique de la patiente {{ $patient->first_name." ".$patient->last_name }}</h4>
                        </div>
                    </div>

                    <div class="card-body">
                        <h2>Information de la patiente</h2>
                        <ul>
                            <li><strong>Nom :</strong> {{ $patient->first_name." ".$patient->last_name }}</li>
                            <li><strong>Phone :</strong> {{ $patient->user->phone }}</li>
                            <li><strong>Adresse :</strong> {{$patient->location}}, {{$patient->district}}, {{$patient->state}}, {{$patient->country}}</li>
                            @if ($patient->relative_name)
                                <li><strong>Nom de son epoux :</strong> {{ $patient->relative_name }}</li>
                                <li><strong>Phone de son epoux :</strong> {{ $patient->relative_phone }}</li>
                            @endif
                            <li><strong>Age :</strong> {{ $patient->age }} ans</li>
                            <li><strong>Groupe sangins :</strong> {{ $patient->blood_group }}</li>
                            <li><strong>Genre :</strong> {{$patient->gender}}</li>
                            <li><strong>Date de naissance :</strong> {{$patient->birth_date}}</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <a href="{{ route('patient.index') }}" class="btn-primary btn-sm">
                                <i class="icon-arrow-left" style="color: white"> </i>
                            </a>
                            &nbsp;&nbsp;&nbsp;&nbsp;
                            <h4 class="card-title">Historique des consultations</h4>
                        </div>
                    </div>

                    <div class="card-body">
                        <h4><strong>📦 Consultations</strong></h4>
                        <div class="table-responsive">
                            <table id="add-row" class="display table table-striped table-hover">
                                <thead class="bg-primary text-white"> <!-- Ajout de couleur d'entête -->
                                    <tr>
                                        <th>#</th>
                                        <th>Date</th>
                                        <th>Département</th>
                                        <th>Médecin</th>
                                        <th>Motif</th>
                                        <th>Total</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr>
                                        <th>#</th>
                                        <th>Date</th>
                                        <th>Département</th>
                                        <th>Médecin</th>
                                        <th>Motif</th>
                                        <th>Total</th>
                                        <th>Actions</th>
                                    </tr>
                                </tfoot>
                                <tbody>
                                    @forelse ($patient->consultations as $consultation)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $consultation->created_at->format('d/m/Y') }}</td>
                                            <td>{{ $consultation->department->name }}</td>
                                            <td>{{ $consultation->medecin->first_name." ".$consultation->medecin->last_name ?? '—' }}</td>
                                            <td>{{ Str::limit($consultation->motif, 30) }}</td>
                                            <td>{{ number_format($consultation->transaction->total ?? 0)." GNF" }}</td>
                                            <td>
                                                <a href="{{ route('consultation.show', $consultation->id) }}" class="btn btn-sm btn-primary"><i class="fa fa-eye"></i></a>
                                                {{-- <a href="{{ route('consultations.facturer', $consultation) }}" class="btn btn-sm btn-info"><i class="fa fa-file-invoice"></i></a> --}}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">Aucune consultation enregistrée.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <a href="{{ route('patient.index') }}" class="btn-primary btn-sm">
                                <i class="icon-arrow-left" style="color: white"> </i>
                            </a>
                            &nbsp;&nbsp;&nbsp;&nbsp;
                            <h4 class="card-title">Historique des hospitalisations</h4>
                        </div>
                    </div>

                    <div class="card-body">
                        <h4><strong>📦 Hospitalisation</strong></h4>
                        <div class="table-responsive">
                            <table id="add-row" class="display table table-striped table-hover">
                                <thead class="bg-primary text-white">
                                    <tr>
                                        <th>#</th>
                                        <th>Description</th>
                                        <th>Prix/Jour</th>
                                        <th>Nbre de Jours</th>
                                        <th>Total</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr>
                                        <th>#</th>
                                        <th>Description</th>
                                        <th>Prix/Jour</th>
                                        <th>Nbre de Jours</th>
                                        <th>Total</th>
                                        <th>Actions</th>
                                    </tr>
                                </tfoot>
                                <tbody>
                                    @forelse ($patient->hospitalisations as $hospitalisation)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>Hospitalisation ({{ number_format(ceil($hospitalisation->nombre_jours)) }} jours)</td>
                                            <td>{{ number_format($hospitalisation->chambre->prix_par_jour) }} GNF</td>
                                            <td>{{ number_format(ceil($hospitalisation->nombre_jours))." Jours" }}</td>
                                            <td>{{ number_format($hospitalisation->total_payer) }} GNF</td>
                                            <td>
                                                <a href="{{ route('hospitalisations.facture', $hospitalisation->id) }}"
                                                    class="btn btn-info btn-round btn-sm" target="_blank">
                                                    📄
                                                 </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">Aucune hospitalisation enregistrée.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
