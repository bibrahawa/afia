@extends('layouts.backend')

@section('content')
<style>

    @media print {
        .no-print {
            display: none !important;
        }
        .card {
            page-break-inside: avoid;
            margin-bottom: 20px;
            box-shadow: none !important;
            border: 1px solid #dee2e6 !important;
        }
        .page-break {
            page-break-after: always;
        }
    }

    .info-item {
        margin-bottom: 15px;
    }

    .info-label {
        font-size: 12px;
        font-weight: 600;
        color: #6c757d;
        text-transform: uppercase;
        margin-bottom: 5px;
    }

    .info-value {
        font-size: 14px;
        font-weight: 500;
        color: #212529;
    }

    .section-icon {
        margin-right: 8px;
    }
</style>

<div class="container">
    <div class="page-inner">
        <!-- Breadcrumbs - Non imprimable -->
        <div class="page-header no-print">
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
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#" ><b>Consultation du {{ $consultation->created_at->format('d M Y') }}</b></a>
                </li>
            </ul>
        </div>

        <!-- Boutons d'action - Non imprimable -->
        <div class="row no-print mb-3">
            <div class="col-md-12 text-right">
                {{-- <a href="{{ route('consultations.facture', $consultation->id) }}" class="btn btn-outline-primary"> <i class="fas fa-print"></i> Imprimer Info Patient</a> --}}
                <a href="{{ route('consultations.facture.examen', $consultation->id) }}" class="btn btn-outline-success"> <i class="fas fa-print"></i> Imprimer Examens</a>
                <a href="{{ route('consultations.facture.ordonnance', $consultation->id) }}" class="btn btn-outline-warning"> <i class="fas fa-print"></i> Imprimer Ordonnance</a>
                <a href="{{ route('consultations.facture.medicament', $consultation->id) }}" class="btn btn-outline-danger"> <i class="fas fa-print"></i> Imprimer Medicament</a>
                <a href="{{ route('consultations.facture.paiement', $consultation->id) }}" class="btn btn-outline-danger"> <i class="fas fa-print"></i> Imprimer Paiement</a>
            </div>
        </div>

        <!-- 1. CARTE INFORMATIONS DU PATIENT -->
        <div class="row mb-4" id="patient-info">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="card-title mb-0">
                            <i class="fas fa-user section-icon"></i>
                            Informations de la Patiente
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="info-item">
                                    <div class="info-label">Patiente</div>
                                    <div class="info-value">{{ $consultation->patient->first_name." ".$consultation->patient->last_name }}</div>
                                </div>

                                <div class="info-item">
                                    <div class="info-label">Telephone</div>
                                    <div class="info-value">{{ $consultation->patient->phone}}</div>
                                </div>

                                <div class="info-item">
                                    <div class="info-label">Adresse</div>
                                    <div class="info-value">{{ $consultation->patient->district}}</div>
                                </div>


                            </div>

                            <div class="col-md-4">
                                <div class="info-item">
                                    <div class="info-label">Groupe Sanguin </div>
                                    <div class="info-value">{{ $consultation->patient->blood_group ?? 'Non précisé' }}</div>
                                </div>

                                <div class="info-item">
                                    <div class="info-label">Médecin</div>
                                    <div class="info-value">{{ $consultation->medecin->first_name." ".$consultation->medecin->last_name ?? 'Non précisé' }}</div>
                                </div>

                                <div class="info-item">
                                    <div class="info-label">Prochain RDV</div>
                                    <div class="info-value">
                                        {{ $consultation->prochain_rdv ?? 'Non défini' }}
                                        @if($consultation->prochainMedecin)
                                            <br><small class="text-muted">avec Dr. {{ $consultation->prochainMedecin->first_name }}</small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-item">
                                    <div class="info-label">Antecedents</div>
                                    <div class="info-value">
                                        <span class="badge badge-info mr-1">{{ $consultation->patient->antecedant->antecedents_medicaux ?? 'Non précisé' }}</span>
                                        <span class="badge badge-info mr-1">{{ $consultation->patient->antecedant->antecedents_chirurgicaux ?? 'Non précisé' }}</span>
                                        <span class="badge badge-info mr-1">{{ $consultation->patient->antecedant->antecedents_gyneco_obstetricaux ?? 'Non précisé' }}</span>
                                        <span class="badge badge-info mr-1">{{ $consultation->patient->antecedant->antecedents_familiaux ?? 'Non précisé' }}</span>
                                        <span class="badge badge-info mr-1">{{ $consultation->patient->antecedant->allergies ?? 'Non précisé' }}</span>
                                        <span class="badge badge-info mr-1">{{ $consultation->patient->antecedant->traitements_cours ?? 'Non précisé' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. CARTE EXAMENS ET DIAGNOSTIC -->
        <div class="row mb-4" id="examens-info">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h4 class="card-title mb-0">
                            <i class="fas fa-stethoscope section-icon"></i>
                            Examens et Diagnostic
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-item">
                                    <div class="info-label">Département</div>
                                    <div class="info-value">{{ $consultation->department->name }}</div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Package(s)</div>
                                    <div class="info-value">
                                        @foreach ($consultation->packages as $package)
                                            <span class="badge badge-success mr-1">{{ $package->name." = ". number_format($package->price)." GNF" ?? 'Non précisé' }}</span>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="info-item">
                                    <div class="info-label">Service(s)</div>
                                    <div class="info-value">
                                        @foreach ($consultation->services as $service)
                                            <span class="badge badge-info mr-1">{{ $service->name." = ". number_format($service->amount)." GNF" ?? 'Non précisé' }}</span>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="info-item">
                                    <div class="info-label">Examen(s)</div>
                                    <div class="info-value">
                                        @foreach ($consultation->tests as $examen)
                                            <span class="badge {{ $examen->pivot->facturer ? 'badge-warning' : 'badge-secondary' }} mr-1">
                                                {{ $examen->name }}
                                                <span class="ml-1">
                                                    @if ($examen->pivot->facturer)
                                                        {{ number_format($examen->amount) }} GNF
                                                    @else
                                                        Hors Clinique
                                                    @endif
                                                </span>
                                            </span>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="info-item">
                                    <div class="info-label">Prescription(s)</div>
                                    <div class="info-value">
                                        @foreach ($consultation->medicaments as $medicament)
                                            <span class="badge {{ $medicament->pivot->facturer ? 'badge-warning' : 'badge-secondary' }} mr-1">
                                                {{ $medicament->nom." = " }}
                                                <span class="ml-1">
                                                    @if ($medicament->pivot->facturer)
                                                        {{ number_format($medicament->amount) }} GNF
                                                    @else
                                                        Hors Clinique
                                                    @endif
                                                </span>
                                            </span>
                                        @endforeach
                                    </div>
                                </div>

                            </div>

                            <div class="col-md-6">
                                <div class="info-item">
                                    <div class="info-label">Motif de consultation</div>
                                    <div class="info-value">{{ $consultation->motif ?? 'Non précisé' }}</div>
                                </div>

                                <div class="info-item">
                                    <div class="info-label">Signes cliniques</div>
                                    <div class="info-value">
                                        @if($consultation->signes_cliniques)
                                            @foreach($consultation->signes_cliniques as $signe)
                                                <span class="badge badge-warning mr-1">{{ $signe }}</span>
                                            @endforeach
                                        @else
                                            Non précisé
                                        @endif
                                    </div>
                                </div>

                                <div class="info-item">
                                    <div class="info-label">Diagnostic</div>
                                    <div class="info-value">{{ $consultation->diagnostic ?? 'Non précisé' }}</div>
                                </div>

                                <div class="info-item">
                                    <div class="info-label">Observations</div>
                                    <div class="info-value">{{ $consultation->observation ?? 'Aucune observation' }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Fichiers joints -->
                        @if($consultation->fichiers->count() > 0)
                        <hr>
                        <div class="info-item">
                            <div class="info-label">Fichiers joints</div>
                            <div class="info-value">
                                @foreach($consultation->fichiers as $file)
                                    <a href="{{ Storage::url($file->chemin) }}" target="_blank" class="btn btn-sm btn-outline-primary mr-2 mb-1">
                                        <i class="fas fa-file"></i> {{ $file->nom_fichier }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. CARTE ORDONNANCE -->
        <div class="row mb-4" id="ordonnance-info">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-warning text-dark">
                        <h4 class="card-title mb-0">
                            <i class="fas fa-pills section-icon"></i>
                            Ordonnance Médicale
                        </h4>
                    </div>
                    <div class="card-body">
                        @if($consultation->medicaments->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Disponible</th>
                                            <th width="30%">Médicament</th>
                                            <th width="20%">Fréquence</th>
                                            <th width="15%">Durée</th>
                                            <th width="35%">Instructions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($consultation->medicaments as $med)
                                            <tr>
                                                <td>
                                                    @if ($med->pivot->facturer)
                                                        <i class="fas fa-circle-check"></i>
                                                    @else
                                                        <i class="fas fa-circle"></i>
                                                    @endif
                                                </td>
                                                <td><strong>{{ $med->nom }}</strong></td>
                                                <td>{{ $med->frequence }}</td>
                                                <td>{{ $med->duree }}</td>
                                                <td>{{ $med->instructions }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-prescription-bottle-alt fa-3x mb-3"></i>
                                <p>Aucun médicament prescrit</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. CARTE PAIEMENTS ET TRANSACTIONS -->
        <div class="row mb-4" id="paiement-info">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-danger text-white">
                        <h4 class="card-title mb-0">
                            <i class="fas fa-credit-card section-icon"></i>
                            Informations Financières
                        </h4>
                    </div>
                    <div class="card-body">
                        <!-- Transaction -->
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <h5 class="text-primary">Détail de la Transaction</h5>
                                @if ($consultation->transaction != null)
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Description</th>
                                                    <th>Montant Total</th>
                                                    <th>Montant Payé</th>
                                                    <th>Statut</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>{{ $consultation->transaction->created_at->format('d/m/Y H:i') }}</td>
                                                    <td>{{ $consultation->transaction->description }}</td>
                                                    <td><strong>{{ number_format($consultation->transaction->total, 0, ',', ' ') }} FG</strong></td>
                                                    <td><strong>{{ number_format($consultation->transaction->montant_payer, 0, ',', ' ') }} FG</strong></td>
                                                    <td>
                                                        @php
                                                            $restant = $consultation->transaction->total - $consultation->transaction->montant_payer;
                                                        @endphp
                                                        @if($restant <= 0)
                                                            <span class="badge badge-success">Payé</span>
                                                        @else
                                                            <span class="badge badge-warning">Reste: {{ number_format($restant, 0, ',', ' ') }} FG</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i>
                                        Aucune transaction enregistrée pour cette consultation.
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Historique des paiements -->
                        <div class="row">
                            <div class="col-md-12">
                                <h5 class="text-success">Historique des Paiements</h5>
                                @if ($consultation->transaction != null && $consultation->transaction->paiements->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Description</th>
                                                    <th>Source</th>
                                                    <th>Montant</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($consultation->transaction->paiements as $paiement)
                                                <tr>
                                                    <td>{{ $paiement->created_at->format('d/m/Y H:i') }}</td>
                                                    <td>{{ $paiement->description }}</td>
                                                    <td>
                                                        <span class="badge badge-secondary">{{ $paiement->source }}</span>
                                                    </td>
                                                    <td><strong>{{ number_format($paiement->montant, 0, ',', ' ') }} FG</strong></td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        Aucun paiement enregistré pour cette consultation.
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Boutons d'action - Non imprimable -->
        <div class="row no-print">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body text-center">
                        {{-- <a href="{{ route('consultation.edit', $consultation->id) }}" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Modifier
                        </a> --}}
                        <a href="{{ route('consultation.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Retour à la liste
                        </a>
                        {{-- <a href="{{ route('facture.consultation') }}" class="btn btn-info">
                            <i class="fas fa-file-invoice"></i> Facture
                        </a> --}}
                        {{-- <form action="{{ route('consultations.facturer', $consultation) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-money-bill"></i> Facturer maintenant
                            </button>
                        </form> --}}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function printCard(cardId) {
    var printContents = document.getElementById(cardId).innerHTML;
    var originalContents = document.body.innerHTML;

    document.body.innerHTML = `
        <div style="padding: 20px;">
            <div style="text-align: center; margin-bottom: 30px;">
                <h2>Consultation Médicale</h2>
                <p>{{ $consultation->patient->first_name." ".$consultation->patient->last_name }} - ${new Date().toLocaleDateString()}</p>
                <hr>
            </div>
            ${printContents}
        </div>
    `;

    window.print();
    document.body.innerHTML = originalContents;
    location.reload();
}
</script>

@endsection
