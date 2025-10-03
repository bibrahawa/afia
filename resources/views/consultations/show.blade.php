@extends('layouts.backend')

@section('content')
    <style>
        /* Amélioration impression */
        @media print {
            .no-print { display: none !important; }
            body { font-size: 12px; }
            .card { page-break-inside: avoid; border: 1px solid #ccc; box-shadow: none !important; }
            .card-header { background: #f8f9fa !important; color: #000 !important; }
            table { border-collapse: collapse; width: 100%; }
            table th, table td { border: 1px solid #ddd; padding: 6px; }
            
            /* Styles spécifiques pour l'impression de l'ordonnance */
            .prescription { 
                max-width: 100% !important; 
                margin: 0 !important;
                padding: 20px !important;
                box-shadow: none !important;
                border-radius: 0 !important;
            }
            .footer {
                position: fixed !important;
                bottom: 0 !important;
                left: 0 !important;
                right: 0 !important;
                width: 100% !important;
                margin-top: 0 !important;
                page-break-inside: avoid !important;
            }
        }

        /* Général */
        .info-label { font-size: 12px; font-weight: 600; color: #495057; text-transform: uppercase; }
        .info-value { font-size: 14px; font-weight: 500; color: #212529; }

        /* Card header */
        .card-header { display: flex; align-items: center; }
        .card-header i { margin-right: 8px; }

        /* Table custom */
        .table thead th {
            background: #f1f3f5;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
        }
    </style>

    <style>
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        .prescription {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border-radius: 8px;
            position: relative;
            min-height: 800px;
        }

        .header {
            display: flex;
            align-items: flex-start;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #333;
        }

        .logo-section {
            width: 80px;
            height: 80px;
            border: 2px solid #333;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            background: #f8f8f8;
            flex-shrink: 0;
        }

        .logo-text {
            font-weight: bold;
            font-size: 12px;
            text-align: center;
        }

        .clinic-info {
            flex: 1;
            text-align: center;
            font-size: 12px;
            line-height: 1.4;
        }

        .clinic-name {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .date-location {
            text-align: right;
            margin-bottom: 20px;
            font-size: 12px;
        }

        .patient-info {
            margin-bottom: 20px;
            font-size: 14px;
        }

        .patient-name {
            font-weight: bold;
            margin-bottom: 10px;
        }

        .ordonnance-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin: 30px 0 20px 0;
            text-decoration: underline;
        }

        .prescription-content {
            min-height: 300px;
            margin-bottom: 60px;
        }

        .prescription-item {
            display: flex;
            margin-bottom: 15px;
            font-size: 13px;
            line-height: 1.5;
        }

        .item-number {
            font-weight: bold;
            margin-right: 10px;
            min-width: 20px;
            flex-shrink: 0;
        }

        .medication {
            flex: 1;
            margin-right: 20px;
        }

        .medication-name {
            font-weight: bold;
        }

        .dosage {
            font-style: italic;
            margin-top: 2px;
        }

        .quantity {
            font-weight: bold;
            min-width: 60px;
            flex-shrink: 0;
        }

        .doctor-signature {
            margin-top: 40px;
            text-align: right;
            margin-bottom: 80px;
        }

        .signature-line {
            margin-top: 40px;
            font-size: 12px;
        }

        .footer_prescription {
            position: absolute;
            bottom: 20px;
            left: 0;
            right: 0;
            margin: 0 40px;
            font-size: 10px;
            text-align: center;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 15px;
            line-height: 1.4;
            background: white;
        }

        .editable {
            background: #fffacd;
            border: 1px dashed #ccc;
            padding: 2px 4px;
            min-width: 50px;
            display: inline-block;
        }

        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            transition: background 0.3s ease;
            z-index: 1000;
        }

        .print-button:hover {
            background: #0056b3;
        }

        /* Styles pour les boutons d'impression dans le modal */
        .modal-print-buttons {
            margin-bottom: 20px;
            text-align: center;
        }

        .btn-print-prescription {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin: 0 5px;
            transition: background 0.3s ease;
        }

        .btn-print-prescription:hover {
            background: #218838;
        }

        /* Styles pour les examens */
        .examens-document {
            max-width: 700px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            font-family: 'Times New Roman', serif;
            position: relative;
            min-height: 600px;
        }

        .examens-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }

        .examens-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .examens-subtitle {
            font-size: 14px;
            color: #666;
        }

        .examens-patient-info {
            margin-bottom: 25px;
            border: 1px solid #ddd;
            padding: 15px;
            background: #f9f9f9;
        }

        .examens-list {
            margin-bottom: 40px;
        }

        .examen-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .examen-name {
            font-weight: bold;
            flex: 1;
        }

        .examen-price {
            font-weight: bold;
            color: #007bff;
            min-width: 100px;
            text-align: right;
        }

        /* Styles pour la facture */
        .facture-document {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            font-family: 'Times New Roman', serif;
            position: relative;
            min-height: 800px;
        }

        .facture-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }

        .facture-logo {
            width: 100px;
            height: 100px;
            border: 2px solid #333;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f8f8;
        }

        .facture-info {
            flex: 1;
            text-align: center;
            margin: 0 20px;
        }

        .facture-number {
            text-align: right;
            font-size: 12px;
        }

        .facture-title {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            margin: 20px 0;
            text-transform: uppercase;
        }

        .billing-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .bill-to, .bill-from {
            width: 48%;
        }

        .bill-label {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .facture-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .facture-table th,
        .facture-table td {
            border: 1px solid #ddd;
            padding: 12px 8px;
            text-align: left;
        }

        .facture-table th {
            background: #f8f9fa;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 12px;
        }

        .facture-total {
            float: right;
            width: 300px;
            margin-bottom: 40px;
        }

        .total-line {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .total-line.final {
            border-top: 2px solid #333;
            border-bottom: 2px solid #333;
            font-weight: bold;
            font-size: 16px;
            margin-top: 10px;
        }

        .facture-footer {
            position: absolute;
            bottom: 20px;
            left: 0;
            right: 0;
            margin: 0 40px;
            font-size: 10px;
            text-align: center;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 15px;
            line-height: 1.4;
            background: white;
        }

        @media print {
            body { 
                background: white; 
                padding: 0; 
            }
            .prescription { 
                box-shadow: none; 
                border-radius: 0; 
                padding: 20px;
                min-height: auto;
            }
            .editable {
                background: white;
                border: none;
            }
            .footer_prescription {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                margin: 0 20px;
            }
            .modal-print-buttons {
                display: none !important;
            }
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
                <a 
                    class="btn btn-outline-warning"
                    data-bs-toggle="modal"
                    data-bs-target="#ordonnanceModal"> 
                 <i class="fas fa-print"></i> 
                 Ordonnance
                </a>
                <a 
                    class="btn btn-outline-success"
                    data-bs-toggle="modal"
                    data-bs-target="#examensModal"> 
                 <i class="fas fa-microscope"></i> 
                 Examens
                </a>
                <a 
                    class="btn btn-outline-info"
                    data-bs-toggle="modal"
                    data-bs-target="#factureModal"> 
                 <i class="fas fa-file-invoice-dollar"></i> 
                 Facture Complète
                </a>
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
                            <?php 

                            ?>
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
                                            <span class="badge badge-warning mr-1">
                                                {{ $examen->name." = ".number_format($examen->amount) }} GNF
                                            </span>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="info-item">
                                    <div class="info-label">Prescription(s)</div>
                                    <div class="info-value">
                                        @foreach ($consultation->medicaments as $medicament)
                                            <span class="badge badge-secondary mr-1">
                                                {{ $medicament->nom." = ".number_format($medicament->amount)."(x".$medicament->pivot->quantity.")" }} GNF
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
                        {{-- @if($consultation->fichiers->count() > 0)
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
                        @endif --}}
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
                                            <th>No</th>
                                            <th width="30%">Médicament</th>
                                            <th width="20%">Fréquence</th>
                                            <th width="15%">Durée</th>
                                            <th width="35%">Instructions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($consultation->medicaments as $indice=>$med)
                                            <tr>
                                                <td>{{++$indice}}</td>
                                                <td><strong>{{ $med->nom."(x".$med->pivot->quantity.")"}}</strong></td>
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

        <!-- Modal Ordonnance Améliorée -->
        <div class="modal fade" id="ordonnanceModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-dark border-0">
                        <h5 class="modal-title">
                            <i class="fas fa-prescription"></i>
                            <span class="fw-mediumbold ms-2">Ordonnance Médicale</span>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0">
                        <!-- Barre d'actions -->
                        <div class="modal-print-buttons no-print bg-light p-3 border-bottom">
                            <div class="d-flex justify-content-center gap-2">
                                <button type="button" class="btn btn-success" onclick="printPrescription()">
                                    <i class="fas fa-print me-2"></i>Imprimer
                                </button>
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-2"></i>Fermer
                                </button>
                            </div>
                        </div>

                        <!-- Contenu de l'ordonnance -->
                        <div class="prescription" id="prescriptionContent">
                            <div class="header">
                                <div class="logo-section">
                                    <img src="{{asset($hopital->logo)}}" alt="Logo {{$hopital->name}}" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                </div>
                                <div class="clinic-info">
                                    <div class="clinic-name">{{$hopital->name}}</div>
                                    <div>Spécialités: Gynécologie • Obstétrique • Échographie</div>
                                    <div>Services: Suivi de grossesse • Planification familiale • Infertilité</div>
                                    <div>{{$hopital->address}}</div>
                                    <div>Tél: {{$hopital->contact}}</div>
                                </div>
                            </div>

                            <div class="date-location">
                                Conakry, le {{ $consultation->created_at->format('d/m/Y') }}
                            </div>

                            <div class="patient-info">
                                <div class="patient-name">
                                    M./Mme {{ $consultation->patient->first_name." ".$consultation->patient->last_name }}, 
                                    {{ $consultation->patient->age ?? 'N/A' }} ans
                                </div>
                            </div>

                            <div class="ordonnance-title">ORDONNANCE</div>

                            <div class="prescription-content">
                                @if($consultation->medicaments->count() > 0)
                                    @foreach($consultation->medicaments as $index => $medicament)
                                        <div class="prescription-item">
                                            <div class="item-number">{{ $index + 1 }}.</div>
                                            <div class="medication">
                                                <div class="medication-name">{{ $medicament->nom }}</div>
                                                <div class="dosage">{{ $medicament->frequence }} - {{ $medicament->duree }}</div>
                                                @if($medicament->instructions)
                                                    <div class="dosage">{{ $medicament->instructions }}</div>
                                                @endif
                                            </div>
                                            <div class="quantity">{{ $medicament->pivot->quantity }} BOÎTE(S)</div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="text-center text-muted py-5">
                                        <i class="fas fa-prescription-bottle-alt fa-3x mb-3"></i>
                                        <p>Aucun médicament prescrit</p>
                                    </div>
                                @endif
                            </div>

                            <div class="doctor-signature">
                                <div>{{$hopital->name}}</div>
                                <div class="signature-line">
                                    Dr {{ $consultation->medecin->getFullNameAttribute() }}
                                </div>
                            </div>

                            <div class="footer_prescription">
                                Tél: {{$hopital->contact}} - Adresse: {{$hopital->address}}<br>
                                Email: {{$hopital->email}} - Site web: {{$hopital->website}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Examens Améliorée -->
        <div class="modal fade" id="examensModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white border-0">
                        <h5 class="modal-title">
                            <i class="fas fa-microscope"></i>
                            <span class="fw-mediumbold ms-2">Demande d'Examens Médicaux</span>
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0">
                        <!-- Barre d'actions -->
                        <div class="modal-print-buttons no-print bg-light p-3 border-bottom">
                            <div class="d-flex justify-content-center gap-2">
                                <button type="button" class="btn btn-success" onclick="printExamens()">
                                    <i class="fas fa-print me-2"></i>Imprimer
                                </button>
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-2"></i>Fermer
                                </button>
                            </div>
                        </div>

                        <!-- Contenu des examens -->
                        <div class="examens-document" id="examensContent">
                            <div class="examens-header">
                                <div class="examens-title">Demande d'Examens Médicaux</div>
                                <div class="examens-subtitle">{{$hopital->name}}</div>
                                <div class="examens-subtitle">Service: {{ $consultation->department->name }}</div>
                            </div>

                            <div class="examens-patient-info">
                                <div class="row">
                                    <div class="col-6">
                                        <strong>Patient:</strong> {{ $consultation->patient->getFullNameAttribute() }}<br>
                                        <strong>Téléphone:</strong> {{ $consultation->patient->phone }}<br>
                                        <strong>Adresse:</strong> {{ $consultation->patient->district }}
                                    </div>
                                    <div class="col-6">
                                        <strong>Date:</strong> {{ $consultation->created_at->format('d/m/Y H:i') }}<br>
                                        <strong>Médecin:</strong> Dr. {{ $consultation->medecin->getFullNameAttribute() }}<br>
                                        <strong>Diagnostic:</strong> {{ $consultation->diagnostic ?? 'En cours' }}
                                    </div>
                                </div>
                            </div>

                            <div style="margin-bottom: 20px;">
                                <strong>Motif de consultation:</strong> {{ $consultation->motif ?? 'Non précisé' }}
                            </div>

                            <div class="examens-list">
                                <h6 style="margin-bottom: 15px; font-weight: bold; text-transform: uppercase;">Examens Demandés:</h6>
                                
                                @if($consultation->tests->count() > 0)
                                    @foreach($consultation->tests as $index => $test)
                                        <div class="examen-item">
                                            <div style="display: flex; align-items: center; width: 100%;">
                                                <div style="margin-right: 15px; font-weight: bold;">{{ $index + 1 }}.</div>
                                                <div class="examen-name">{{ $test->name }}</div>
                                                <div class="examen-price">{{ number_format($test->amount, 0, ',', ' ') }} GNF</div>
                                            </div>
                                        </div>
                                    @endforeach
                                    
                                    <div style="margin-top: 20px; text-align: right; font-weight: bold; font-size: 16px;">
                                        Total Examens: {{ number_format($consultation->tests->sum('amount'), 0, ',', ' ') }} GNF
                                    </div>
                                @else
                                    <div style="text-align: center; color: #666; padding: 40px;">
                                        <i class="fas fa-microscope" style="font-size: 48px; margin-bottom: 15px; color: #ddd;"></i>
                                        <p>Aucun examen prescrit pour cette consultation</p>
                                    </div>
                                @endif
                            </div>

                            <div style="margin-top: 40px;">
                                <div style="margin-bottom: 15px;"><strong>Instructions spéciales:</strong></div>
                                <div style="border: 1px solid #ddd; padding: 15px; min-height: 80px; background: #f9f9f9;">
                                    {{ $consultation->observation ?? 'Aucune instruction particulière' }}
                                </div>
                            </div>

                            <div style="margin-top: 40px; text-align: right;">
                                <div style="margin-bottom: 60px;">Le Service Médical</div>
                                <div>
                                    Dr {{ $consultation->medecin->getFullNameAttribute() }}<br>
                                    <small>{{ $consultation->department->name }}</small>
                                </div>
                            </div>

                            <div class="facture-footer">
                                Tél: {{$hopital->contact}} - Adresse: {{$hopital->address}}<br>
                                Email: {{$hopital->email}} - Site web: {{$hopital->website}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Facture Complète Améliorée -->
        <div class="modal fade" id="factureModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white border-0">
                        <h5 class="modal-title">
                            <i class="fas fa-file-invoice-dollar"></i>
                            <span class="fw-mediumbold ms-2">Facture Complète</span>
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0">
                        <!-- Barre d'actions -->
                        <div class="modal-print-buttons no-print bg-light p-3 border-bottom">
                            <div class="d-flex justify-content-center gap-2">
                                <button type="button" class="btn btn-info text-white" onclick="printFacture()">
                                    <i class="fas fa-print me-2"></i>Imprimer
                                </button>
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-2"></i>Fermer
                                </button>
                            </div>
                        </div>

                        <!-- Contenu de la facture -->
                        <div class="facture-document" id="factureContent">
                            <div class="facture-header">
                                <div class="facture-logo">
                                    <img src="{{asset($hopital->logo)}}" alt="Logo {{$hopital->name}}" style="max-width: 90%; max-height: 90%; object-fit: contain;">
                                </div>
                                <div class="facture-info">
                                    <div style="font-weight: bold; font-size: 16px;">{{$hopital->name}}</div>
                                    <div>Spécialités: Gynécologie • Obstétrique • Échographie</div>
                                    <div>Services: Suivi de grossesse • Planification familiale • Infertilité</div>
                                    <div>{{$hopital->address}}</div>
                                    <div>Tél: {{$hopital->contact}}</div>
                                </div>
                                <div class="facture-number">
                                    <div><strong>FACTURE N°:</strong> {{ str_pad($consultation->id, 6, '0', STR_PAD_LEFT) }}</div>
                                    <div><strong>Date:</strong> {{ $consultation->created_at->format('d/m/Y') }}</div>
                                    @if($consultation->transaction)
                                        <div><strong>Référence:</strong> TXN-{{ $consultation->transaction->id }}</div>
                                    @endif
                                </div>
                            </div>

                            <div class="facture-title">FACTURE</div>

                            <div class="billing-info">
                                <div class="bill-to">
                                    <div class="bill-label">Facturé à:</div>
                                    <div>{{ $consultation->patient->first_name." ".$consultation->patient->last_name }}</div>
                                    <div>{{ $consultation->patient->phone }}</div>
                                    <div>{{ $consultation->patient->district }}</div>
                                    @if($consultation->patient->blood_group)
                                        <div>Groupe sanguin: {{ $consultation->patient->blood_group }}</div>
                                    @endif
                                </div>
                                <div class="bill-from">
                                    <div class="bill-label">Médecin traitant:</div>
                                    <div>Dr. {{ $consultation->medecin->first_name." ".$consultation->medecin->last_name }}</div>
                                    <div>{{ $consultation->department->name }}</div>
                                    <div>{{ $consultation->created_at->format('d/m/Y à H:i') }}</div>
                                </div>
                            </div>

                            <table class="facture-table">
                                <thead>
                                    <tr>
                                        <th style="width: 5%;">#</th>
                                        <th style="width: 50%;">Description</th>
                                        <th style="width: 10%;">Qté</th>
                                        <th style="width: 15%;">Prix Unitaire</th>
                                        <th style="width: 20%;">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $itemNumber = 1;
                                        $totalGeneral = 0;
                                    @endphp
                                    
                                    @foreach($consultation->packages as $package)
                                        <tr>
                                            <td>{{ $itemNumber++ }}</td>
                                            <td><strong>Package:</strong> {{ $package->name }}</td>
                                            <td>1</td>
                                            <td>{{ number_format($package->price, 0, ',', ' ') }} GNF</td>
                                            <td>{{ number_format($package->price, 0, ',', ' ') }} GNF</td>
                                        </tr>
                                        @php $totalGeneral += $package->price; @endphp
                                    @endforeach

                                    @foreach($consultation->services as $service)
                                        <tr>
                                            <td>{{ $itemNumber++ }}</td>
                                            <td><strong>Service:</strong> {{ $service->name }}</td>
                                            <td>1</td>
                                            <td>{{ number_format($service->amount, 0, ',', ' ') }} GNF</td>
                                            <td>{{ number_format($service->amount, 0, ',', ' ') }} GNF</td>
                                        </tr>
                                        @php $totalGeneral += $service->amount; @endphp
                                    @endforeach

                                    @foreach($consultation->tests as $test)
                                        <tr>
                                            <td>{{ $itemNumber++ }}</td>
                                            <td><strong>Examen:</strong> {{ $test->name }}</td>
                                            <td>1</td>
                                            <td>{{ number_format($test->amount, 0, ',', ' ') }} GNF</td>
                                            <td>{{ number_format($test->amount, 0, ',', ' ') }} GNF</td>
                                        </tr>
                                        @php $totalGeneral += $test->amount; @endphp
                                    @endforeach

                                    @foreach($consultation->medicaments as $medicament)
                                        <tr>
                                            <td>{{ $itemNumber++ }}</td>
                                            <td><strong>Médicament:</strong> {{ $medicament->nom }}</td>
                                            <td>{{ $medicament->pivot->quantity }}</td>
                                            <td>{{ number_format($medicament->amount, 0, ',', ' ') }} GNF</td>
                                            <td>{{ number_format($medicament->amount * $medicament->pivot->quantity, 0, ',', ' ') }} GNF</td>
                                        </tr>
                                        @php $totalGeneral += ($medicament->amount * $medicament->pivot->quantity); @endphp
                                    @endforeach
                                </tbody>
                            </table>

                            <div class="facture-total">
                                <div class="total-line">
                                    <span>Sous-total:</span>
                                    <span>{{ number_format($totalGeneral, 0, ',', ' ') }} GNF</span>
                                </div>
                                @if($consultation->transaction)
                                    <div class="total-line">
                                        <span>Montant Payé:</span>
                                        <span>{{ number_format($consultation->transaction->montant_payer, 0, ',', ' ') }} GNF</span>
                                    </div>
                                    @php
                                        $restant = $consultation->transaction->total - $consultation->transaction->montant_payer;
                                    @endphp
                                    <div class="total-line final">
                                        <span>Restant à Payer:</span>
                                        <span style="color: {{ $restant > 0 ? '#dc3545' : '#28a745' }};">
                                            {{ number_format($restant, 0, ',', ' ') }} GNF
                                        </span>
                                    </div>
                                @endif
                            </div>

                            <div style="clear: both; margin-top: 30px; text-align: center;">
                                <small style="color: #666;">
                                    Merci pour votre confiance. Cette facture est générée automatiquement.
                                </small>
                            </div>

                            <div class="facture-footer">
                                Tél: {{$hopital->contact}} - Adresse: {{$hopital->address}}<br>
                                Email: {{$hopital->email}} - Site web: {{$hopital->website}}
                            </div>
                        </div>
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

    // Fonction spécifique pour imprimer l'ordonnance
    function printPrescription() {
        var prescriptionContent = document.getElementById('prescriptionContent').outerHTML;
        
        // Créer une nouvelle page avec uniquement l'ordonnance
        var printWindow = window.open('', '_blank', 'width=800,height=600');
        
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Ordonnance Médicale</title>
                <style>
                    * {
                        margin: 0;
                        padding: 0;
                        box-sizing: border-box;
                    }

                    body {
                        font-family: 'Times New Roman', serif;
                        background: white;
                        padding: 0;
                        margin: 0;
                    }

                    .prescription {
                        max-width: 100%;
                        margin: 0 auto;
                        background: white;
                        padding: 40px;
                        position: relative;
                        min-height: 100vh;
                    }

                    .header {
                        display: flex;
                        align-items: flex-start;
                        margin-bottom: 30px;
                        padding-bottom: 20px;
                        border-bottom: 2px solid #333;
                    }

                    .logo-section {
                        width: 80px;
                        height: 80px;
                        border: 2px solid #333;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        margin-right: 20px;
                        background: #f8f8f8;
                        flex-shrink: 0;
                    }

                    .logo-text {
                        font-weight: bold;
                        font-size: 12px;
                        text-align: center;
                    }

                    .clinic-info {
                        flex: 1;
                        text-align: center;
                        font-size: 12px;
                        line-height: 1.4;
                    }

                    .clinic-name {
                        font-weight: bold;
                        font-size: 14px;
                        margin-bottom: 5px;
                    }

                    .date-location {
                        text-align: right;
                        margin-bottom: 20px;
                        font-size: 12px;
                    }

                    .patient-info {
                        margin-bottom: 20px;
                        font-size: 14px;
                    }

                    .patient-name {
                        font-weight: bold;
                        margin-bottom: 10px;
                    }

                    .ordonnance-title {
                        text-align: center;
                        font-size: 18px;
                        font-weight: bold;
                        margin: 30px 0 20px 0;
                        text-decoration: underline;
                    }

                    .prescription-content {
                        min-height: 300px;
                        margin-bottom: 60px;
                    }

                    .prescription-item {
                        display: flex;
                        margin-bottom: 15px;
                        font-size: 13px;
                        line-height: 1.5;
                    }

                    .item-number {
                        font-weight: bold;
                        margin-right: 10px;
                        min-width: 20px;
                        flex-shrink: 0;
                    }

                    .medication {
                        flex: 1;
                        margin-right: 20px;
                    }

                    .medication-name {
                        font-weight: bold;
                    }

                    .dosage {
                        font-style: italic;
                        margin-top: 2px;
                    }

                    .quantity {
                        font-weight: bold;
                        min-width: 60px;
                        flex-shrink: 0;
                    }

                    .doctor-signature {
                        margin-top: 40px;
                        text-align: right;
                        margin-bottom: 80px;
                    }

                    .signature-line {
                        margin-top: 40px;
                        font-size: 12px;
                    }

                    .footer_prescription {
                        position: fixed;
                        bottom: 20px;
                        left: 20px;
                        right: 20px;
                        font-size: 10px;
                        text-align: center;
                        color: #666;
                        border-top: 1px solid #ddd;
                        padding-top: 15px;
                        line-height: 1.4;
                        background: white;
                        page-break-inside: avoid;
                    }

                    .editable {
                        background: white;
                        border: none;
                        padding: 2px 4px;
                        min-width: 50px;
                        display: inline-block;
                    }

                    @media print {
                        body {
                            margin: 0;
                            padding: 0;
                        }
                        
                        .prescription {
                            padding: 20px;
                            min-height: auto;
                        }
                        
                        .footer_prescription {
                            position: fixed;
                            bottom: 0;
                            left: 0;
                            right: 0;
                            margin: 0 20px;
                        }
                    }
                </style>
            </head>
            <body>
                ${prescriptionContent}
            </body>
            </html>
        `);
        
        printWindow.document.close();
        
        // Attendre que le contenu soit chargé avant d'imprimer
        setTimeout(function() {
            printWindow.print();
            printWindow.close();
        }, 250);
    }

    // Fonction pour imprimer les examens
    function printExamens() {
        var examensContent = document.getElementById('examensContent').outerHTML;
        
        var printWindow = window.open('', '_blank', 'width=800,height=600');
        
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Examens Médicaux</title>
                <style>
                    * {
                        margin: 0;
                        padding: 0;
                        box-sizing: border-box;
                    }
                    body {
                        font-family: 'Times New Roman', serif;
                        background: white;
                        padding: 20px;
                    }
                    .examens-document {
                        max-width: 100%;
                        margin: 0 auto;
                        background: white;
                        padding: 40px;
                        font-family: 'Times New Roman', serif;
                    }
                    .examens-header {
                        text-align: center;
                        margin-bottom: 30px;
                        border-bottom: 2px solid #333;
                        padding-bottom: 20px;
                    }
                    .examens-title {
                        font-size: 20px;
                        font-weight: bold;
                        margin-bottom: 10px;
                        text-transform: uppercase;
                    }
                    .examens-subtitle {
                        font-size: 14px;
                        color: #666;
                    }
                    .examens-patient-info {
                        margin-bottom: 25px;
                        border: 1px solid #ddd;
                        padding: 15px;
                        background: #f9f9f9;
                    }
                    .examens-list {
                        margin-bottom: 40px;
                    }
                    .examen-item {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        padding: 10px 0;
                        border-bottom: 1px solid #eee;
                    }
                    .examen-name {
                        font-weight: bold;
                        flex: 1;
                    }
                    .examen-price {
                        font-weight: bold;
                        color: #007bff;
                        min-width: 100px;
                        text-align: right;
                    }
                    .row { display: flex; }
                    .col-6 { width: 50%; padding-right: 15px; }
                    .facture-footer {
                        position: fixed;
                        bottom: 20px;
                        left: 20px;
                        right: 20px;
                        font-size: 10px;
                        text-align: center;
                        color: #666;
                        border-top: 1px solid #ddd;
                        padding-top: 15px;
                        background: white;
                    }
                </style>
            </head>
            <body>
                ${examensContent}
            </body>
            </html>
        `);
        
        printWindow.document.close();
        setTimeout(function() {
            printWindow.print();
            printWindow.close();
        }, 250);
    }

    // Fonction pour imprimer la facture
    function printFacture() {
        var factureContent = document.getElementById('factureContent').outerHTML;
        
        var printWindow = window.open('', '_blank', 'width=1000,height=800');
        
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Facture Complète</title>
                <style>
                    * {
                        margin: 0;
                        padding: 0;
                        box-sizing: border-box;
                    }
                    body {
                        font-family: 'Times New Roman', serif;
                        background: white;
                        padding: 20px;
                    }
                    .facture-document {
                        max-width: 100%;
                        margin: 0 auto;
                        background: white;
                        padding: 40px;
                        font-family: 'Times New Roman', serif;
                    }
                    .facture-header {
                        display: flex;
                        justify-content: space-between;
                        align-items: flex-start;
                        margin-bottom: 30px;
                        border-bottom: 2px solid #333;
                        padding-bottom: 20px;
                    }
                    .facture-logo {
                        width: 100px;
                        height: 100px;
                        border: 2px solid #333;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        background: #f8f8f8;
                    }
                    .logo-text {
                        font-weight: bold;
                        font-size: 12px;
                        text-align: center;
                    }
                    .facture-info {
                        flex: 1;
                        text-align: center;
                        margin: 0 20px;
                    }
                    .facture-number {
                        text-align: right;
                        font-size: 12px;
                    }
                    .facture-title {
                        text-align: center;
                        font-size: 24px;
                        font-weight: bold;
                        margin: 20px 0;
                        text-transform: uppercase;
                    }
                    .billing-info {
                        display: flex;
                        justify-content: space-between;
                        margin-bottom: 30px;
                    }
                    .bill-to, .bill-from {
                        width: 48%;
                    }
                    .bill-label {
                        font-weight: bold;
                        font-size: 14px;
                        margin-bottom: 10px;
                        text-transform: uppercase;
                    }
                    .facture-table {
                        width: 100%;
                        border-collapse: collapse;
                        margin-bottom: 30px;
                    }
                    .facture-table th,
                    .facture-table td {
                        border: 1px solid #ddd;
                        padding: 12px 8px;
                        text-align: left;
                    }
                    .facture-table th {
                        background: #f8f9fa;
                        font-weight: bold;
                        text-transform: uppercase;
                        font-size: 12px;
                    }
                    .facture-total {
                        float: right;
                        width: 300px;
                        margin-bottom: 40px;
                    }
                    .total-line {
                        display: flex;
                        justify-content: space-between;
                        padding: 8px 0;
                        border-bottom: 1px solid #eee;
                    }
                    .total-line.final {
                        border-top: 2px solid #333;
                        border-bottom: 2px solid #333;
                        font-weight: bold;
                        font-size: 16px;
                        margin-top: 10px;
                    }
                    .row { display: flex; }
                    .col-6 { width: 50%; padding-right: 15px; }
                    .facture-footer {
                        position: fixed;
                        bottom: 20px;
                        left: 20px;
                        right: 20px;
                        font-size: 10px;
                        text-align: center;
                        color: #666;
                        border-top: 1px solid #ddd;
                        padding-top: 15px;
                        background: white;
                    }
                </style>
            </head>
            <body>
                ${factureContent}
            </body>
            </html>
        `);
        
        printWindow.document.close();
        setTimeout(function() {
            printWindow.print();
            printWindow.close();
        }, 250);
    }

    // Rendre les éléments éditables cliquables
    document.addEventListener('DOMContentLoaded', function() {
        const editableElements = document.querySelectorAll('.editable');
        editableElements.forEach(element => {
            element.addEventListener('click', function() {
                this.contentEditable = true;
                this.focus();
                this.style.background = '#e6f3ff';
            });
            
            element.addEventListener('blur', function() {
                this.contentEditable = false;
                this.style.background = '#fffacd';
            });
            
            element.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    this.blur();
                }
            });
        });
    });
</script>

@endsection