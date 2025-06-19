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
                            <h4 class="card-title">Les details de la consultation de {{ $consultation->patient->first_name." ".$consultation->patient->last_name }}</h4>
                        </div>
                    </div>

                    <div class="card-body">
                        <h2>Détails de la consultation</h2>

                        <ul>
                            <li><strong>Patient :</strong> {{ $consultation->patient->first_name." ".$consultation->patient->last_name }}</li>
                            <li><strong>Médecin :</strong> {{ $consultation->medecin->first_name." ".$consultation->medecin->last_name ?? 'Non précisé' }}</li>
                            <li><strong>Département :</strong> {{ $consultation->department->name }}</li>
                            <li><strong>Service :</strong>
                                @foreach ($consultation->services as $service)
                                    {{ $service->name." " ?? 'Non précisé' }}
                                @endforeach
                            </li>
                            <li><strong>Package :</strong>
                                @foreach ($consultation->packages as $package)
                                    {{ $package->name." " ?? 'Non précisé' }}
                                @endforeach
                            </li>
                            <li><strong>Motif :</strong> {{ $consultation->motif }}</li>
                            <li><strong>Signes cliniques :</strong> {{ implode(', ', $consultation->signes_cliniques ?? []) }}</li>
                            <li><strong>Diagnostic :</strong> {{ $consultation->diagnostic }}</li>
                            <li><strong>Observation :</strong> {{ $consultation->observation }}</li>
                            <li><strong>Prochain RDV :</strong> {{ $consultation->prochain_rdv }}</li>
                            <li><strong>Médecin RDV :</strong> {{ $consultation->prochainMedecin->first_name ?? 'Non défini' }}</li>
                        </ul>

                        <h4>📦 Ordonnance</h4>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Médicament</th>
                                    <th>Fréquence</th>
                                    <th>Durée</th>
                                    <th>Instruction</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($consultation->medicaments as $med)
                                <tr>
                                    <td>{{ $med->nom }}</td>
                                    <td>{{ $med->frequence }}</td>
                                    <td>{{ $med->duree }}</td>
                                    <td>{{ $med->instructions }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <h4>📁 Fichiers joints</h4>
                        <ul>
                            @foreach($consultation->fichiers as $file)
                                <li><a href="{{ Storage::url($file->chemin) }}" target="_blank">{{ $file->nom_fichier }}</a></li>
                            @endforeach
                        </ul>


                        <div class="mt-4">
                            <h4>Historique des transactions</h4>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Description</th>
                                        <th>Montant</th>
                                        <th>Payer</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        @if ($consultation->transaction != null)
                                            <td>{{ $consultation->transaction->created_at->format('d M Y H:m:s')}}</td>
                                            <td>{{ $consultation->transaction->description }}</td>
                                            <td>{{ $consultation->transaction->total }} FG</td>
                                            <td>{{ $consultation->transaction->montant_payer }}</td>
                                        @endif
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            <h4>Historique des paiements</h4>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Description</th>
                                        <th>Source</th>
                                        {{-- <th>Type</th> --}}
                                        <th>Montant</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if ($consultation->transaction != null)
                                        @foreach($consultation->transaction->paiements as $paiement)
                                        <tr>
                                            <td>{{ $paiement->created_at->format('d M Y H:m:s')}}</td>
                                            <td>{{ $paiement->description}}</td>
                                            <td>{{ $paiement->source}}</td>
                                            {{-- <td>{{ $paiement->type}}</td> --}}
                                            <td>{{ $paiement->montant }} FG</td>
                                        </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>

                        <a href="{{ route('consultation.edit', $consultation->id) }}" class="btn btn-warning mt-3">Modifier</a>
                        <a href="{{ route('consultation.index') }}" class="btn btn-secondary mt-3">Retour à la liste</a>
                        <form action="{{ route('consultations.facturer', $consultation) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-success">💰 Facturer maintenant</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
