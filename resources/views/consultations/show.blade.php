@extends('layouts.backend')

@section('style')
    <style>
        /* ============================================
        IMPRESSION - Styles d'impression
        ============================================ */
        @media print {
            .no-print { display: none !important; }
            body { font-size: 12px; }
            .card { 
                page-break-inside: avoid; 
                border: 1px solid #ccc; 
                box-shadow: none !important; 
            }
            .card-header { 
                background: #f8f9fa !important; 
                color: #000 !important; 
            }
            table { 
                border-collapse: collapse; 
                width: 100%; 
            }
            table th, table td { 
                border: 1px solid #ddd; 
                padding: 6px; 
            }
            .prescription, .examens-document, .facture-document { 
                max-width: 100% !important; 
                margin: 0 !important;
                padding: 20px !important;
                box-shadow: none !important;
                border-radius: 0 !important;
            }
            .footer, .footer_prescription, .facture-footer {
                position: fixed !important;
                bottom: 0 !important;
                left: 0 !important;
                right: 0 !important;
                width: 100% !important;
                margin-top: 0 !important;
                page-break-inside: avoid !important;
            }
        }

        /* ============================================
        CARDS - Styles généraux des cartes
        ============================================ */
        .card {
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.2s;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }

        .card-header { 
            display: flex; 
            align-items: center;
            padding: 1.25rem 1.5rem;
        }

        .card-header i { 
            margin-right: 12px; 
            font-size: 1.2rem;
        }

        /* Gradients pour les en-têtes */
        .bg-gradient-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }

        .bg-gradient-primary {
            background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);
        }

        /* ============================================
        BOUTONS & BADGES
        ============================================ */
        .badge {
            padding: 6px 12px;
            font-weight: 500;
            border-radius: 20px;
        }

        .action-buttons-fixed {
            position: sticky;
            top: 20px;
            z-index: 100;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        /* ============================================
        SECTION CLINIQUE - Diagnostic
        ============================================ */
        .clinical-info-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.25rem;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
            height: 100%;
        }

        .clinical-info-card:hover {
            background: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-color: #dee2e6;
        }

        .icon-box {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .diagnostic-badge {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 0.75rem 1.25rem;
            border-radius: 10px;
            font-weight: 600;
            display: inline-block;
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.2);
        }

        .observation-box {
            background: white;
            border-left: 4px solid #6c757d;
            padding: 1rem;
            border-radius: 8px;
            font-style: italic;
        }

        /* ============================================
        TABLES - Facturation
        ============================================ */
        .table thead th {
            background: #f8f9fa;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #dee2e6;
        }

        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
        }

        .modern-invoice-table {
            margin-bottom: 0;
        }

        .modern-invoice-table thead tr {
            background: linear-gradient(to right, #f8f9fa, #e9ecef);
            border-bottom: 2px solid #dee2e6;
        }

        .modern-invoice-table thead th {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 1rem 0.75rem;
            border: none;
            color: #495057;
        }

        .modern-invoice-table tbody .invoice-row {
            border-bottom: 1px solid #f1f3f5;
            transition: all 0.2s ease;
        }

        .modern-invoice-table tbody .invoice-row:hover {
            background: #f8f9fa;
        }

        .modern-invoice-table tbody td {
            padding: 1.25rem 0.75rem;
            vertical-align: middle;
        }

        .quantity-badge {
            background: linear-gradient(135deg, #0d6efd, #0dcaf0);
            color: white;
            padding: 0.4rem 0.9rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.875rem;
            display: inline-block;
        }

        .total-row {
            background: linear-gradient(to right, #e9ecef, #f8f9fa);
        }

        .total-row th {
            padding: 1.5rem 0.75rem;
            border-top: 3px solid #dee2e6;
        }

        .total-amount {
            color: #0d6efd;
            font-size: 1.25rem;
            font-weight: 700;
        }

        /* ============================================
        ASSURANCE - Statuts et informations
        ============================================ */
        .insurance-status-card {
            background: linear-gradient(135deg, #e7f3ff 0%, #f0f8ff 100%);
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid #cce5ff;
        }

        .status-item small {
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.6rem 1.2rem;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .status-warning { background: #fff3cd; color: #856404; }
        .status-info { background: #d1ecf1; color: #0c5460; }
        .status-success { background: #d4edda; color: #155724; }
        .status-danger { background: #f8d7da; color: #721c24; }

        .claim-number {
            background: white;
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-weight: 700;
            color: #0d6efd;
            border: 2px dashed #0d6efd;
            display: inline-block;
        }

        /* ============================================
        SERVICES CARDS - Fallback sans facture
        ============================================ */
        .category-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .service-card {
            background: white;
            border-radius: 12px;
            border: 2px solid;
            transition: all 0.3s ease;
            height: 100%;
        }

        .service-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }

        .service-card-success { border-color: #28a745; }
        .service-card-success:hover { 
            border-color: #20c997; 
            box-shadow: 0 8px 20px rgba(40, 167, 69, 0.2); 
        }

        .service-card-info { border-color: #17a2b8; }
        .service-card-info:hover { 
            border-color: #0dcaf0; 
            box-shadow: 0 8px 20px rgba(23, 162, 184, 0.2); 
        }

        .service-card-warning { border-color: #ffc107; }
        .service-card-warning:hover { 
            border-color: #ffca2c; 
            box-shadow: 0 8px 20px rgba(255, 193, 7, 0.2); 
        }

        .service-card-body {
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .service-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .service-card-success .service-icon {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }

        .service-card-info .service-icon {
            background: linear-gradient(135deg, #17a2b8, #0dcaf0);
            color: white;
        }

        .service-card-warning .service-icon {
            background: linear-gradient(135deg, #ffc107, #ffca2c);
            color: #212529;
        }

        .service-name {
            font-size: 0.95rem;
            font-weight: 600;
            color: #212529;
            margin-bottom: 0.5rem;
        }

        .service-price {
            font-size: 1.1rem;
            font-weight: 700;
            color: #0d6efd;
        }

        /* ============================================
        PAIEMENTS - Timeline et résumé financier
        ============================================ */
        .financial-summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
        }

        .financial-summary .amount {
            font-size: 2rem;
            font-weight: 700;
            margin: 10px 0;
        }

        .financial-summary .label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .payment-status {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 13px;
        }

        .payment-status.paid {
            background: #d4edda;
            color: #155724;
        }

        .payment-status.partial {
            background: #fff3cd;
            color: #856404;
        }

        .payment-status.unpaid {
            background: #f8d7da;
            color: #721c24;
        }

        .timeline {
            position: relative;
            padding: 20px 0;
        }

        .timeline-item {
            position: relative;
            padding-left: 40px;
            padding-bottom: 20px;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: 8px;
            top: 0;
            bottom: -20px;
            width: 2px;
            background: #e9ecef;
        }

        .timeline-item:last-child::before {
            display: none;
        }

        .timeline-dot {
            position: absolute;
            left: 0;
            top: 5px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #007bff;
            border: 3px solid white;
            box-shadow: 0 0 0 2px #007bff;
        }

        /* ============================================
        FICHIERS - Cards de fichiers
        ============================================ */
        .hover-card {
            transition: all 0.3s ease;
            border: 1px solid #e0e0e0;
        }

        .hover-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15) !important;
            border-color: #007bff;
        }

        .hover-card .card-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: #333;
            min-height: 40px;
            display: flex;
            align-items: center;
        }

        .hover-card .btn {
            transition: all 0.2s ease;
        }

        .hover-card .btn:hover {
            transform: scale(1.05);
        }

        .vr {
            width: 1px;
            background-color: #dee2e6;
            align-self: stretch;
        }

        /* ============================================
        MODALS - Documents d'impression
        ============================================ */
        .modal-header {
            border-bottom: 2px solid #f0f0f0;
        }

        .modal-print-buttons {
            background: #f8f9fa;
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
        }

        .prescription, .examens-document, .facture-document {
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

        /* ============================================
        RESPONSIVE
        ============================================ */
        @media (max-width: 768px) {
            .action-buttons-fixed {
                position: relative;
                top: 0;
            }
            .financial-summary .amount {
                font-size: 1.5rem;
            }
            .invoice-header-section .col-md-6:last-child {
                text-align: left !important;
                margin-top: 0.5rem;
            }
        }

        /* ============================================
        ANIMATIONS
        ============================================ */
        .btn-loading {
            position: relative;
            pointer-events: none;
        }

        .btn-loading::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            top: 50%;
            left: 50%;
            margin-left: -8px;
            margin-top: -8px;
            border: 2px solid #ffffff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spinner 0.6s linear infinite;
        }

        @keyframes spinner {
            to { transform: rotate(360deg); }
        }
    </style>
@endsection

@section('content')
<div class="container">
    <div class="page-inner">

        <!-- Messages de succès/erreur -->
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <strong>Succès!</strong> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <strong>Erreur!</strong> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        <!-- Breadcrumbs -->
        <div class="page-header no-print mb-4">
            <ul class="breadcrumbs">
                <li class="nav-home">
                    <a href="{{url('/')}}">
                        <i class="icon-home"></i>
                    </a>
                </li>
                <li class="separator"><i class="icon-arrow-right"></i></li>
                <li class="nav-item"><a href="{{ url('/') }}">Admin</a></li>
                <li class="separator"><i class="icon-arrow-right"></i></li>
                <li class="nav-item"><a href="{{ route('consultation.index') }}">Consultations</a></li>
                <li class="separator"><i class="icon-arrow-right"></i></li>
                <li class="nav-item"><a href="#"><b>{{ $consultation->created_at->format('d M Y') }}</b></a></li>
            </ul>
        </div>

        <!-- Boutons d'action flottants -->
        <div class="action-buttons-fixed no-print">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0">
                        <i class="fas fa-file-medical text-primary"></i>
                        Consultation #{{ str_pad($consultation->id, 6, '0', STR_PAD_LEFT) }}
                    </h5>
                    <small class="text-muted">{{ $consultation->created_at->format('d/m/Y à H:i') }}</small>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#ordonnanceModal"> 
                        <i class="fas fa-prescription"></i> Ordonnance
                    </button>
                    <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#examensModal"> 
                        <i class="fas fa-microscope"></i> Examens
                    </button>
                    <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#factureModal"> 
                        <i class="fas fa-file-invoice-dollar"></i> Facture
                    </button>
                    <a href="{{ route('consultation.index') }}" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            </div>
        </div>

        <!-- Résumé financier -->
        @if($consultation->transaction)
        <div class="financial-summary no-print">
            <div class="row text-center">
                <div class="col-md-4">
                    <div class="label">Montant Total</div>
                    <div class="amount">{{ number_format($consultation->transaction->total, 0, ',', ' ') }} GNF</div>
                </div>
                <div class="col-md-4">
                    <div class="label">Montant Payé</div>
                    <div class="amount">{{ number_format($consultation->transaction->montant_payer, 0, ',', ' ') }} GNF</div>
                </div>
                <div class="col-md-4">
                    @php
                        $restant = $consultation->transaction->total - $consultation->transaction->montant_payer;
                    @endphp
                    <div class="label">Restant à Payer</div>
                    <div class="amount" style="color: {{ $restant > 0 ? '#ffeb3b' : '#4caf50' }};">
                        {{ number_format($restant, 0, ',', ' ') }} GNF
                    </div>
                    @if($restant <= 0)
                        <span class="badge bg-success mt-2">✓ Payé intégralement</span>
                    @else
                        <span class="badge bg-warning mt-2">⚠ Paiement partiel</span>
                    @endif
                </div>
            </div>
        </div>
        @endif

        <!-- 1. CARTE INFORMATIONS DU PATIENT -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user-injured"></i>
                            Informations du Patient
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="info-item">
                                    <div class="info-label"><i class="fas fa-user me-1"></i> Patient</div>
                                    <div class="info-value">{{ $consultation->patient->first_name." ".$consultation->patient->last_name }}</div>
                                </div>

                                <div class="info-item">
                                    <div class="info-label"><i class="fas fa-phone me-1"></i> Téléphone</div>
                                    <div class="info-value">
                                        <a href="tel:{{ $consultation->patient->phone }}" class="text-decoration-none">
                                            {{ $consultation->patient->phone }}
                                        </a>
                                    </div>
                                </div>

                                <div class="info-item">
                                    <div class="info-label"><i class="fas fa-map-marker-alt me-1"></i> Adresse</div>
                                    <div class="info-value">{{ $consultation->patient->district }}</div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="info-item">
                                    <div class="info-label"><i class="fas fa-tint me-1"></i> Groupe Sanguin</div>
                                    <div class="info-value">
                                        <span class="badge badge-danger">{{ $consultation->patient->blood_group ?? 'Non précisé' }}</span>
                                    </div>
                                </div>

                                <div class="info-item">
                                    <div class="info-label"><i class="fas fa-user-md me-1"></i> Médecin Traitant</div>
                                    <div class="info-value">Dr. {{ $consultation->medecin->first_name." ".$consultation->medecin->last_name }}</div>
                                </div>

                                <div class="info-item">
                                    <div class="info-label"><i class="fas fa-calendar-check me-1"></i> Prochain RDV</div>
                                    <div class="info-value">
                                        @if($consultation->prochain_rdv)
                                            {{ \Carbon\Carbon::parse($consultation->prochain_rdv)->format('d/m/Y à H:i') }}
                                            @if($consultation->prochainMedecin)
                                                <br><small class="text-muted">avec Dr. {{ $consultation->prochainMedecin->first_name }}</small>
                                            @endif
                                        @else
                                            <span class="text-muted">Non défini</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="info-item">
                                    <div class="info-label"><i class="fas fa-history me-1"></i> Antécédents</div>
                                    <div class="info-value">
                                        @if($consultation->patient->antecedant)
                                            @if($consultation->patient->antecedant->antecedents_medicaux)
                                                <span class="badge badge-info me-1 mb-1">{{ $consultation->patient->antecedant->antecedents_medicaux }}</span>
                                            @endif
                                            @if($consultation->patient->antecedant->allergies)
                                                <span class="badge badge-warning me-1 mb-1">Allergies: {{ $consultation->patient->antecedant->allergies }}</span>
                                            @endif
                                            @if(!$consultation->patient->antecedant->antecedents_medicaux && !$consultation->patient->antecedant->allergies)
                                                <span class="text-muted">Aucun antécédent signalé</span>
                                            @endif
                                        @else
                                            <span class="text-muted">Aucun antécédent signalé</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 2. SECTION DIAGNOSTIC ET EXAMENS CLINIQUES -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-gradient-success text-white">
                        <h5 class="card-title mb-0 d-flex align-items-center">
                            <i class="fas fa-stethoscope me-2"></i>
                            Diagnostic et Examens Cliniques
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            <!-- Département -->
                            <div class="col-md-6 col-lg-4">
                                <div class="clinical-info-card">
                                    <div class="d-flex align-items-start">
                                        <div class="icon-box bg-primary bg-opacity-10 text-primary me-3">
                                            <i class="fas fa-hospital-alt"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <small class="text-muted text-uppercase d-block mb-1">Département</small>
                                            <h6 class="mb-0 fw-semibold">{{ $consultation->department->name }}</h6>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Motif de Consultation -->
                            <div class="col-md-6 col-lg-8">
                                <div class="clinical-info-card">
                                    <div class="d-flex align-items-start">
                                        <div class="icon-box bg-info bg-opacity-10 text-info me-3">
                                            <i class="fas fa-notes-medical"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <small class="text-muted text-uppercase d-block mb-1">Motif de Consultation</small>
                                            <p class="mb-0">{{ $consultation->motif ?? 'Non précisé' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Signes Cliniques -->
                            <div class="col-md-12 col-lg-7">
                                <div class="clinical-info-card">
                                    <div class="d-flex align-items-start">
                                        <div class="icon-box bg-warning bg-opacity-10 text-warning me-3">
                                            <i class="fas fa-heartbeat"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <small class="text-muted text-uppercase d-block mb-2">Signes Cliniques</small>
                                            @if($consultation->signes_cliniques && is_array($consultation->signes_cliniques))
                                                <div class="d-flex flex-wrap gap-2">
                                                    @foreach($consultation->signes_cliniques as $signe)
                                                        <span class="badge rounded-pill bg-warning bg-opacity-25 text-dark px-3 py-2">
                                                            <i class="fas fa-check-circle me-1"></i>{{ $signe }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-muted fst-italic">Non précisé</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Diagnostic -->
                            <div class="col-md-12 col-lg-5">
                                <div class="clinical-info-card h-100">
                                    <div class="d-flex align-items-start h-100">
                                        <div class="icon-box bg-danger bg-opacity-10 text-danger me-3">
                                            <i class="fas fa-diagnoses"></i>
                                        </div>
                                        <div class="flex-grow-1 d-flex flex-column justify-content-center">
                                            <small class="text-muted text-uppercase d-block mb-2">Diagnostic</small>
                                            <div class="diagnostic-badge">
                                                <i class="fas fa-clipboard-check me-2"></i>
                                                {{ $consultation->diagnostic ?? 'Non précisé' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Observations -->
                            @if($consultation->observation)
                                <div class="col-md-12">
                                    <div class="clinical-info-card">
                                        <div class="d-flex align-items-start">
                                            <div class="icon-box bg-secondary bg-opacity-10 text-secondary me-3">
                                                <i class="fas fa-comment-medical"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <small class="text-muted text-uppercase d-block mb-2">Observations Médicales</small>
                                                <div class="observation-box">
                                                    <p class="mb-0 text-dark">{{ $consultation->observation }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. SECTION SERVICES ET FACTURATION -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-gradient-primary text-white">
                        <h5 class="card-title mb-0 d-flex align-items-center">
                            <i class="fas fa-file-invoice-dollar me-2"></i>
                            Services et Facturation
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        @if($consultation->transaction && $consultation->transaction->invoice && $consultation->transaction->invoice->items->count() > 0)
                            <!-- Vue avec Facture Détaillée -->
                            <div class="invoice-details-wrapper">
                                <!-- En-tête de la facture -->
                                <div class="invoice-header-section mb-4">
                                    <div class="row align-items-center">
                                        <div class="col-md-6">
                                            <h6 class="mb-0 d-flex align-items-center">
                                                <i class="fas fa-receipt me-2 text-primary"></i>
                                                Facture N° <span class="ms-2 text-primary">{{ str_pad($consultation->transaction->invoice->id, 6, '0', STR_PAD_LEFT) }}</span>
                                            </h6>
                                        </div>
                                        <div class="col-md-6 text-md-end">
                                            <small class="text-muted">
                                                <i class="far fa-calendar me-1"></i>
                                                {{ $consultation->created_at->format('d/m/Y à H:i') }}
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Table des items -->
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle modern-invoice-table">
                                        <thead>
                                            <tr>
                                                <th style="width: 5%;">#</th>
                                                <th style="width: 20%;">Description</th>
                                                <th class="text-center" style="width: 10%;">Qté</th>
                                                <th class="text-end" style="width: 15%;">Prix Unit.</th>
                                                <th class="text-end" style="width: 15%;">Total</th>
                                                @if($consultation->transaction->invoice->insurance_company_id)
                                                    <th class="text-end" style="width: 15%;">Couverture</th>
                                                @endif
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($consultation->transaction->invoice->items as $index => $item)
                                                <tr class="invoice-row">
                                                    <td class="text-muted">{{ $index + 1 }}</td>
                                                    <td>
                                                        <div class="item-description">
                                                            <strong class="d-block text-dark">{{ $item->description }}</strong>
                                                            @if($item->coverageType)
                                                                <small class="text-muted">
                                                                    <i class="fas fa-tag me-1"></i>
                                                                    {{ class_basename($item->coverage_type_type) }}
                                                                </small>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="quantity-badge">{{ $item->quantity }}</span>
                                                    </td>
                                                    <td class="text-end">
                                                        <span class="text-muted">{{ number_format($item->unit_price, 0, ',', ' ') }} GNF</span>
                                                    </td>
                                                    <td class="text-end">
                                                        <strong class="text-dark">{{ number_format($item->total_amount, 0, ',', ' ') }} GNF</strong>
                                                    </td>
                                                    @if($consultation->transaction->invoice->insurance_company_id)
                                                        <td class="text-end">
                                                            <div class="coverage-info">
                                                                <div class="text-success fw-semibold">
                                                                    <i class="fas fa-shield-alt me-1"></i>
                                                                    {{ number_format($item->insurance_covered_amount, 0, ',', ' ') }} GNF
                                                                </div>
                                                                @if($item->coverage_percentage_applied)
                                                                    <small class="badge bg-success bg-opacity-25 text-success mt-1">
                                                                        {{ $item->coverage_percentage_applied }}% couvert
                                                                    </small>
                                                                @endif
                                                                <div class="text-danger small mt-1">
                                                                    Reste: {{ number_format($item->patient_amount, 0, ',', ' ') }} GNF
                                                                </div>
                                                            </div>
                                                        </td>
                                                    @endif
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr class="total-row">
                                                <th colspan="4" class="text-end">
                                                    <span class="fs-5">Total Général</span>
                                                </th>
                                                <th class="text-end">
                                                    <span class="total-amount">{{ number_format($consultation->transaction->invoice->total_amount, 0, ',', ' ') }} GNF</span>
                                                </th>
                                                @if($consultation->transaction->invoice->insurance_company_id)
                                                    <th class="text-end">
                                                        <div class="text-success fw-bold mb-1">
                                                            <i class="fas fa-check-circle me-1"></i>
                                                            {{ number_format($consultation->transaction->invoice->insurance_amount, 0, ',', ' ') }} GNF
                                                        </div>
                                                        <small class="text-danger d-block">
                                                            Patient: {{ number_format($consultation->transaction->invoice->patient_amount, 0, ',', ' ') }} GNF
                                                        </small>
                                                    </th>
                                                @endif
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                                <!-- Carte de statut d'assurance -->
                                @if($consultation->transaction->invoice->insurance_company_id)
                                    <div class="insurance-status-card mt-4">
                                        <div class="row g-3 align-items-center">
                                            <div class="col-md-4">
                                                <div class="status-item">
                                                    <small class="text-muted text-uppercase d-block mb-2">
                                                        <i class="fas fa-shield-alt me-1"></i>Statut Assurance
                                                    </small>
                                                    @php
                                                        $statusConfig = [
                                                            'pending' => ['color' => 'warning', 'icon' => 'clock', 'label' => 'En attente'],
                                                            'submitted' => ['color' => 'info', 'icon' => 'paper-plane', 'label' => 'Soumis'],
                                                            'approved' => ['color' => 'success', 'icon' => 'check-circle', 'label' => 'Approuvé'],
                                                            'rejected' => ['color' => 'danger', 'icon' => 'times-circle', 'label' => 'Rejeté'],
                                                            'paid' => ['color' => 'success', 'icon' => 'check-double', 'label' => 'Payé']
                                                        ];
                                                        $status = $statusConfig[$consultation->transaction->invoice->insurance_status] ?? ['color' => 'secondary', 'icon' => 'question', 'label' => 'Inconnu'];
                                                    @endphp
                                                    <span class="status-badge status-{{ $status['color'] }}">
                                                        <i class="fas fa-{{ $status['icon'] }} me-2"></i>{{ $status['label'] }}
                                                    </span>
                                                </div>
                                            </div>
                                            @if($consultation->transaction->invoice->insurance_claim_number)
                                                <div class="col-md-4">
                                                    <div class="status-item">
                                                        <small class="text-muted text-uppercase d-block mb-2">
                                                            <i class="fas fa-hashtag me-1"></i>N° de Réclamation
                                                        </small>
                                                        <div class="claim-number">
                                                            {{ $consultation->transaction->invoice->insurance_claim_number }}
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                            @if($consultation->transaction->invoice->insurance_submission_date)
                                                <div class="col-md-4">
                                                    <div class="status-item">
                                                        <small class="text-muted text-uppercase d-block mb-2">
                                                            <i class="far fa-calendar-check me-1"></i>Date de Soumission
                                                        </small>
                                                        <div class="fw-semibold">
                                                            {{ \Carbon\Carbon::parse($consultation->transaction->invoice->insurance_submission_date)->format('d/m/Y') }}
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @else
                            <!-- Vue sans facture : Affichage par catégories -->
                            <div class="services-grid">
                                @if($consultation->packages->count() > 0)
                                    <div class="service-category mb-4">
                                        <div class="category-header mb-3">
                                            <h6 class="mb-0 d-flex align-items-center">
                                                <div class="category-icon bg-success bg-opacity-10 text-success me-2">
                                                    <i class="fas fa-box"></i>
                                                </div>
                                                Packages
                                                <span class="badge bg-success ms-2">{{ $consultation->packages->count() }}</span>
                                            </h6>
                                        </div>
                                        <div class="row g-3">
                                            @foreach ($consultation->packages as $package)
                                                <div class="col-md-6 col-lg-4">
                                                    <div class="service-card service-card-success">
                                                        <div class="service-card-body">
                                                            <div class="service-icon">
                                                                <i class="fas fa-box-open"></i>
                                                            </div>
                                                            <div class="service-details">
                                                                <h6 class="service-name">{{ $package->name }}</h6>
                                                                <div class="service-price">{{ number_format($package->price) }} GNF</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                @if($consultation->services->count() > 0)
                                    <div class="service-category mb-4">
                                        <div class="category-header mb-3">
                                            <h6 class="mb-0 d-flex align-items-center">
                                                <div class="category-icon bg-info bg-opacity-10 text-info me-2">
                                                    <i class="fas fa-concierge-bell"></i>
                                                </div>
                                                Services
                                                <span class="badge bg-info ms-2">{{ $consultation->services->count() }}</span>
                                            </h6>
                                        </div>
                                        <div class="row g-3">
                                            @foreach ($consultation->services as $service)
                                                <div class="col-md-6 col-lg-4">
                                                    <div class="service-card service-card-info">
                                                        <div class="service-card-body">
                                                            <div class="service-icon">
                                                                <i class="fas fa-hands-helping"></i>
                                                            </div>
                                                            <div class="service-details">
                                                                <h6 class="service-name">{{ $service->name }}</h6>
                                                                <div class="service-price">{{ number_format($service->amount) }} GNF</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                @if($consultation->tests->count() > 0)
                                    <div class="service-category mb-4">
                                        <div class="category-header mb-3">
                                            <h6 class="mb-0 d-flex align-items-center">
                                                <div class="category-icon bg-warning bg-opacity-10 text-warning me-2">
                                                    <i class="fas fa-vial"></i>
                                                </div>
                                                Examens Prescrits
                                                <span class="badge bg-warning ms-2">{{ $consultation->tests->count() }}</span>
                                            </h6>
                                        </div>
                                        <div class="row g-3">
                                            @foreach ($consultation->tests as $examen)
                                                <div class="col-md-6 col-lg-4">
                                                    <div class="service-card service-card-warning">
                                                        <div class="service-card-body">
                                                            <div class="service-icon">
                                                                <i class="fas fa-microscope"></i>
                                                            </div>
                                                            <div class="service-details">
                                                                <h6 class="service-name">{{ $examen->name }}</h6>
                                                                <div class="service-price">{{ number_format($examen->amount) }} GNF</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                @if($consultation->packages->count() === 0 && $consultation->services->count() === 0 && $consultation->tests->count() === 0)
                                    <div class="text-center py-5">
                                        <i class="fas fa-inbox fa-4x text-muted mb-3 opacity-25"></i>
                                        <h6 class="text-muted mb-2">Aucun service enregistré</h6>
                                        <p class="text-muted small mb-0">Aucun package, service ou examen n'a été ajouté à cette consultation.</p>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. CARTE ORDONNANCE -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-pills"></i>
                            Ordonnance Médicale
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($consultation->medicaments->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th width="5%">N°</th>
                                            <th width="25%">Médicament</th>
                                            <th width="10%">Qté</th>
                                            <th width="15%">Fréquence</th>
                                            <th width="15%">Durée</th>
                                            <th width="20%">Instructions</th>
                                            <th width="10%">Prix</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($consultation->medicaments as $indice => $med)
                                            <tr>
                                                <td><strong>{{ ++$indice }}</strong></td>
                                                <td><strong>{{ $med->nom }}</strong></td>
                                                <td><span class="badge bg-primary">x{{ $med->pivot->quantity }}</span></td>
                                                <td>{{ $med->frequence }}</td>
                                                <td>{{ $med->duree }}</td>
                                                <td><small class="text-muted">{{ $med->instructions ?: '-' }}</small></td>
                                                <td><strong>{{ number_format($med->amount * $med->pivot->quantity) }} GNF</strong></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-active">
                                            <td colspan="6" class="text-end"><strong>Total Médicaments:</strong></td>
                                            <td><strong>{{ number_format($consultation->medicaments->sum(function($m) { return $m->amount * $m->pivot->quantity; })) }} GNF</strong></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @else
                            <div class="text-center text-muted py-5">
                                <i class="fas fa-prescription-bottle-alt fa-4x mb-3" style="opacity: 0.3;"></i>
                                <p class="mb-0">Aucun médicament prescrit pour cette consultation</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. CARTE FICHIERS ET DOCUMENTS JOINTS -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-paperclip"></i>
                            Fichiers et Documents Joints
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($consultation->fichiers && $consultation->fichiers->count() > 0)
                            <div class="row">
                                @foreach($consultation->fichiers as $file)
                                    <div class="col-md-4 mb-3">
                                        <div class="card h-100 shadow-sm hover-card">
                                            <div class="card-body d-flex flex-column">
                                                <div class="text-center mb-3">
                                                    @php
                                                        $extension = strtolower(pathinfo($file->nom_fichier, PATHINFO_EXTENSION));
                                                        $iconClass = 'fa-file';
                                                        $iconColor = '#6c757d';
                                                        
                                                        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'])) {
                                                            $iconClass = 'fa-file-image';
                                                            $iconColor = '#28a745';
                                                        } elseif (in_array($extension, ['pdf'])) {
                                                            $iconClass = 'fa-file-pdf';
                                                            $iconColor = '#dc3545';
                                                        } elseif (in_array($extension, ['doc', 'docx'])) {
                                                            $iconClass = 'fa-file-word';
                                                            $iconColor = '#0d6efd';
                                                        } elseif (in_array($extension, ['xls', 'xlsx'])) {
                                                            $iconClass = 'fa-file-excel';
                                                            $iconColor = '#198754';
                                                        } elseif (in_array($extension, ['zip', 'rar', '7z'])) {
                                                            $iconClass = 'fa-file-archive';
                                                            $iconColor = '#fd7e14';
                                                        }
                                                    @endphp
                                                    <i class="fas {{ $iconClass }} fa-4x mb-2" style="color: {{ $iconColor }};"></i>
                                                </div>
                                                
                                                <h6 class="card-title text-truncate" title="{{ $file->nom_fichier }}">
                                                    {{ $file->nom_fichier }}
                                                </h6>
                                                
                                                <div class="mt-auto">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <small class="text-muted">
                                                            <i class="far fa-calendar me-1"></i>
                                                            {{ $file->created_at->format('d/m/Y') }}
                                                        </small>
                                                        <span class="badge" style="background-color: {{ $iconColor }};">
                                                            {{ strtoupper($extension) }}
                                                        </span>
                                                    </div>
                                                    
                                                    @if($file->description)
                                                        <p class="text-muted small mb-2">
                                                            <i class="fas fa-info-circle me-1"></i>
                                                            {{ Str::limit($file->description, 50) }}
                                                        </p>
                                                    @endif
                                                    
                                                    <div class="d-flex gap-2">
                                                        <a href="{{ Storage::url($file->chemin) }}" 
                                                        target="_blank" 
                                                        class="btn btn-sm btn-outline-primary flex-fill"
                                                        title="Voir le fichier">
                                                            <i class="fas fa-eye"></i> Voir
                                                        </a>
                                                        <a href="{{ Storage::url($file->chemin) }}" 
                                                        download="{{ $file->nom_fichier }}" 
                                                        class="btn btn-sm btn-outline-success flex-fill"
                                                        title="Télécharger">
                                                            <i class="fas fa-download"></i> Télécharger
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            
                            <!-- Statistiques des fichiers -->
                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <div class="alert alert-light border">
                                        <div class="d-flex justify-content-around text-center">
                                            <div>
                                                <h4 class="mb-0">{{ $consultation->fichiers->count() }}</h4>
                                                <small class="text-muted">Fichier(s) joint(s)</small>
                                            </div>
                                            <div class="vr"></div>
                                            <div>
                                                <h4 class="mb-0">
                                                    @php
                                                        $totalSize = 0;
                                                        foreach($consultation->fichiers as $f) {
                                                            if(Storage::exists($f->chemin)) {
                                                                $totalSize += Storage::size($f->chemin);
                                                            }
                                                        }
                                                        $size = $totalSize < 1024 ? $totalSize . ' B' : 
                                                                ($totalSize < 1048576 ? round($totalSize/1024, 2) . ' KB' : 
                                                                round($totalSize/1048576, 2) . ' MB');
                                                    @endphp
                                                    {{ $size }}
                                                </h4>
                                                <small class="text-muted">Taille totale</small>
                                            </div>
                                            <div class="vr"></div>
                                            <div>
                                                <h4 class="mb-0">
                                                    {{ $consultation->fichiers->unique(function($f) {
                                                        return strtolower(pathinfo($f->nom_fichier, PATHINFO_EXTENSION));
                                                    })->count() }}
                                                </h4>
                                                <small class="text-muted">Type(s) de fichier</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="text-center text-muted py-5">
                                <i class="fas fa-folder-open fa-4x mb-3" style="opacity: 0.3;"></i>
                                <h5 class="mb-2">Aucun fichier joint</h5>
                                <p class="mb-0">Il n'y a aucun document attaché à cette consultation.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. CARTE PAIEMENTS ET TRANSACTIONS -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-credit-card"></i>
                            Informations Financières
                        </h5>
                    </div>
                    <div class="card-body">
                        @if ($consultation->transaction != null)
                            <!-- Transaction -->
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <h6 class="text-primary mb-3">
                                        <i class="fas fa-receipt me-2"></i>Détail de la Transaction
                                    </h6>
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
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
                                                    <td><strong class="text-primary">{{ number_format($consultation->transaction->total, 0, ',', ' ') }} GNF</strong></td>
                                                    <td><strong class="text-success">{{ number_format($consultation->transaction->montant_payer, 0, ',', ' ') }} GNF</strong></td>
                                                    <td>
                                                        @php
                                                            $restant = $consultation->transaction->total - $consultation->transaction->montant_payer;
                                                        @endphp
                                                        @if($restant <= 0)
                                                            <span class="payment-status paid">
                                                                <i class="fas fa-check-circle me-1"></i>Payé intégralement
                                                            </span>
                                                        @elseif($consultation->transaction->montant_payer > 0)
                                                            <span class="payment-status partial">
                                                                <i class="fas fa-exclamation-triangle me-1"></i>Reste: {{ number_format($restant, 0, ',', ' ') }} GNF
                                                            </span>
                                                        @else
                                                            <span class="payment-status unpaid">
                                                                <i class="fas fa-times-circle me-1"></i>Non payé
                                                            </span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Historique des paiements avec timeline -->
                            <div class="row">
                                <div class="col-md-12">
                                    <h6 class="text-success mb-3">
                                        <i class="fas fa-history me-2"></i>Historique des Paiements
                                    </h6>
                                    @if ($consultation->transaction->paiements->count() > 0)
                                        <div class="timeline">
                                            @foreach($consultation->transaction->paiements as $paiement)
                                            <div class="timeline-item">
                                                <div class="timeline-dot"></div>
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="mb-1">{{ number_format($paiement->montant, 0, ',', ' ') }} GNF</h6>
                                                        <p class="mb-1 text-muted">{{ $paiement->description }}</p>
                                                        <small class="text-muted">
                                                            <i class="far fa-calendar me-1"></i>{{ $paiement->created_at->format('d/m/Y à H:i') }}
                                                        </small>
                                                    </div>
                                                    <span class="badge badge-secondary">{{ $paiement->source }}</span>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            Aucun paiement enregistré pour cette consultation.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="alert alert-info text-center py-5">
                                <i class="fas fa-info-circle fa-3x mb-3" style="opacity: 0.5;"></i>
                                <p class="mb-0">Aucune transaction enregistrée pour cette consultation.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Ordonnance -->
        <div class="modal fade" id="ordonnanceModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-dark border-0">
                        <h5 class="modal-title">
                            <i class="fas fa-prescription"></i>
                            <span class="fw-medium ms-2">Ordonnance Médicale</span>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0">
                        <div class="modal-print-buttons no-print">
                            <div class="d-flex justify-content-center gap-2">
                                <button type="button" class="btn btn-success" onclick="printPrescription()">
                                    <i class="fas fa-print me-2"></i>Imprimer
                                </button>
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-2"></i>Fermer
                                </button>
                            </div>
                        </div>

                        <div class="prescription" id="prescriptionContent">
                            <div class="header">
                                <div class="logo-section">
                                    <img src="{{asset($hopital->logo)}}" alt="Logo {{$hopital->name}}" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                </div>
                                <div class="clinic-info">
                                    <div class="clinic-name">{{$hopital->name}}</div>
                                    <div>{{$hopital->address}}</div>
                                    <div>Tél: {{$hopital->contact}}</div>
                                    <div>Email: {{$hopital->email}}</div>
                                </div>
                            </div>

                            <div style="text-align: right; margin-bottom: 20px; font-size: 12px;">
                                Conakry, le {{ $consultation->created_at->format('d/m/Y') }}
                            </div>

                            <div style="margin-bottom: 20px; font-size: 14px;">
                                <div style="font-weight: bold; margin-bottom: 10px;">
                                    Patient: {{ $consultation->patient->first_name." ".$consultation->patient->last_name }}
                                </div>
                                <div>Âge: {{ $consultation->patient->age ?? 'N/A' }} ans</div>
                            </div>

                            <div style="text-align: center; font-size: 18px; font-weight: bold; margin: 30px 0 20px 0; text-decoration: underline;">
                                ORDONNANCE
                            </div>

                            <div style="min-height: 300px; margin-bottom: 60px;">
                                @if($consultation->medicaments->count() > 0)
                                    @foreach($consultation->medicaments as $index => $medicament)
                                        <div style="display: flex; margin-bottom: 15px; font-size: 13px; line-height: 1.5;">
                                            <div style="font-weight: bold; margin-right: 10px; min-width: 20px;">{{ $index + 1 }}.</div>
                                            <div style="flex: 1; margin-right: 20px;">
                                                <div style="font-weight: bold;">{{ $medicament->nom }}</div>
                                                <div style="font-style: italic; margin-top: 2px;">{{ $medicament->frequence }} - {{ $medicament->duree }}</div>
                                                @if($medicament->instructions)
                                                    <div style="font-style: italic; margin-top: 2px;">{{ $medicament->instructions }}</div>
                                                @endif
                                            </div>
                                            <div style="font-weight: bold; min-width: 80px;">Qté: {{ $medicament->pivot->quantity }}</div>
                                        </div>
                                    @endforeach
                                @else
                                    <div style="text-align: center; color: #999; padding: 40px 0;">
                                        <p>Aucun médicament prescrit</p>
                                    </div>
                                @endif
                            </div>

                            <div style="margin-top: 40px; text-align: right; margin-bottom: 80px;">
                                <div>{{$hopital->name}}</div>
                                <div style="margin-top: 40px; font-size: 12px;">
                                    Dr {{ $consultation->medecin->first_name." ".$consultation->medecin->last_name }}
                                </div>
                            </div>

                            <div style="position: absolute; bottom: 20px; left: 40px; right: 40px; font-size: 10px; text-align: center; color: #666; border-top: 1px solid #ddd; padding-top: 15px; background: white;">
                                Tél: {{$hopital->contact}} - Adresse: {{$hopital->address}}<br>
                                Email: {{$hopital->email}} - Site web: {{$hopital->website ?? ''}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Examens -->
        <div class="modal fade" id="examensModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white border-0">
                        <h5 class="modal-title">
                            <i class="fas fa-microscope"></i>
                            <span class="fw-medium ms-2">Demande d'Examens Médicaux</span>
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-0">
                        <div class="modal-print-buttons no-print">
                            <div class="d-flex justify-content-center gap-2">
                                <button type="button" class="btn btn-success" onclick="printExamens()">
                                    <i class="fas fa-print me-2"></i>Imprimer
                                </button>
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-2"></i>Fermer
                                </button>
                            </div>
                        </div>

                        <div class="examens-document" id="examensContent">
                            <div style="text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px;">
                                <div style="font-size: 20px; font-weight: bold; margin-bottom: 10px; text-transform: uppercase;">
                                    Demande d'Examens Médicaux
                                </div>
                                <div style="font-size: 14px; color: #666;">{{$hopital->name}}</div>
                                <div style="font-size: 14px; color: #666;">Service: {{ $consultation->department->name }}</div>
                            </div>

                            <div style="margin-bottom: 25px; border: 1px solid #ddd; padding: 15px; background: #f9f9f9;">
                                <div style="display: flex; justify-content: space-between;">
                                    <div style="width: 48%;">
                                        <strong>Patient:</strong> {{ $consultation->patient->first_name." ".$consultation->patient->last_name }}<br>
                                        <strong>Téléphone:</strong> {{ $consultation->patient->phone }}<br>
                                        <strong>Adresse:</strong> {{ $consultation->patient->district }}
                                    </div>
                                    <div style="width: 48%;">
                                        <strong>Date:</strong> {{ $consultation->created_at->format('d/m/Y H:i') }}<br>
                                        <strong>Médecin:</strong> Dr. {{ $consultation->medecin->first_name." ".$consultation->medecin->last_name }}<br>
                                        <strong>Diagnostic:</strong> {{ $consultation->diagnostic ?? 'En cours' }}
                                    </div>
                                </div>
                            </div>

                            <div style="margin-bottom: 20px;">
                                <strong>Motif de consultation:</strong> {{ $consultation->motif ?? 'Non précisé' }}
                            </div>

                            <div style="margin-bottom: 40px;">
                                <h6 style="margin-bottom: 15px; font-weight: bold; text-transform: uppercase;">Examens Demandés:</h6>
                                
                                @if($consultation->tests->count() > 0)
                                    @foreach($consultation->tests as $index => $test)
                                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #eee;">
                                            <div style="display: flex; align-items: center; width: 100%;">
                                                <div style="margin-right: 15px; font-weight: bold;">{{ $index + 1 }}.</div>
                                                <div style="font-weight: bold; flex: 1;">{{ $test->name }}</div>
                                                <div style="font-weight: bold; color: #007bff; min-width: 100px; text-align: right;">
                                                    {{ number_format($test->amount, 0, ',', ' ') }} GNF
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                    
                                    <div style="margin-top: 20px; text-align: right; font-weight: bold; font-size: 16px;">
                                        Total Examens: {{ number_format($consultation->tests->sum('amount'), 0, ',', ' ') }} GNF
                                    </div>
                                @else
                                    <div style="text-align: center; color: #666; padding: 40px;">
                                        <p>Aucun examen prescrit pour cette consultation</p>
                                    </div>
                                @endif
                            </div>

                            @if($consultation->observation)
                            <div style="margin-top: 40px;">
                                <div style="margin-bottom: 15px;"><strong>Instructions spéciales:</strong></div>
                                <div style="border: 1px solid #ddd; padding: 15px; min-height: 80px; background: #f9f9f9;">
                                    {{ $consultation->observation }}
                                </div>
                            </div>
                            @endif

                            <div style="margin-top: 40px; text-align: right;">
                                <div style="margin-bottom: 60px;">Le Service Médical</div>
                                <div>
                                    Dr {{ $consultation->medecin->first_name." ".$consultation->medecin->last_name }}<br>
                                    <small>{{ $consultation->department->name }}</small>
                                </div>
                            </div>

                            <div style="position: absolute; bottom: 20px; left: 40px; right: 40px; font-size: 10px; text-align: center; color: #666; border-top: 1px solid #ddd; padding-top: 15px; background: white;">
                                Tél: {{$hopital->contact}} - Adresse: {{$hopital->address}}<br>
                                Email: {{$hopital->email}} - Site web: {{$hopital->website ?? ''}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Facture -->
        <div class="modal fade" id="factureModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white border-0">
                        <h5 class="modal-title">
                            <i class="fas fa-file-invoice-dollar"></i>
                            <span class="fw-medium ms-2">Facture Complète</span>
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-0">
                        <div class="modal-print-buttons no-print">
                            <div class="d-flex justify-content-center gap-2">
                                <button type="button" class="btn btn-info text-white" onclick="printFacture()">
                                    <i class="fas fa-print me-2"></i>Imprimer
                                </button>
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-2"></i>Fermer
                                </button>
                            </div>
                        </div>

                        <div class="facture-document" id="factureContent" style="max-width: 800px;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px;">
                                <div style="width: 100px; height: 100px; border: 2px solid #333; display: flex; align-items: center; justify-content: center; background: #f8f8f8;">
                                    <img src="{{asset($hopital->logo)}}" alt="Logo" style="max-width: 90%; max-height: 90%; object-fit: contain;">
                                </div>
                                <div style="flex: 1; text-align: center; margin: 0 20px;">
                                    <div style="font-weight: bold; font-size: 16px;">{{$hopital->name}}</div>
                                    <div style="font-size: 12px;">{{$hopital->address}}</div>
                                    <div style="font-size: 12px;">Tél: {{$hopital->contact}}</div>
                                </div>
                                <div style="text-align: right; font-size: 12px;">
                                    <div><strong>FACTURE N°:</strong> {{ str_pad($consultation->id, 6, '0', STR_PAD_LEFT) }}</div>
                                    <div><strong>Date:</strong> {{ $consultation->created_at->format('d/m/Y') }}</div>
                                    @if($consultation->transaction)
                                        <div><strong>Réf:</strong> TXN-{{ $consultation->transaction->id }}</div>
                                    @endif
                                </div>
                            </div>

                            <div style="text-align: center; font-size: 24px; font-weight: bold; margin: 20px 0; text-transform: uppercase;">
                                FACTURE
                            </div>

                            <div style="display: flex; justify-content: space-between; margin-bottom: 30px;">
                                <div style="width: 48%;">
                                    <div style="font-weight: bold; font-size: 14px; margin-bottom: 10px; text-transform: uppercase;">Facturé à:</div>
                                    <div>{{ $consultation->patient->first_name." ".$consultation->patient->last_name }}</div>
                                    <div>{{ $consultation->patient->phone }}</div>
                                    <div>{{ $consultation->patient->district }}</div>
                                </div>
                                <div style="width: 48%;">
                                    <div style="font-weight: bold; font-size: 14px; margin-bottom: 10px; text-transform: uppercase;">Médecin traitant:</div>
                                    <div>Dr. {{ $consultation->medecin->first_name." ".$consultation->medecin->last_name }}</div>
                                    <div>{{ $consultation->department->name }}</div>
                                    <div>{{ $consultation->created_at->format('d/m/Y à H:i') }}</div>
                                </div>
                            </div>

                            <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
                                <thead>
                                    <tr>
                                        <th style="border: 1px solid #ddd; padding: 12px 8px; background: #f8f9fa; font-weight: bold; text-transform: uppercase; font-size: 12px; width: 5%;">#</th>
                                        <th style="border: 1px solid #ddd; padding: 12px 8px; background: #f8f9fa; font-weight: bold; text-transform: uppercase; font-size: 12px; width: 50%;">Description</th>
                                        <th style="border: 1px solid #ddd; padding: 12px 8px; background: #f8f9fa; font-weight: bold; text-transform: uppercase; font-size: 12px; width: 10%;">Qté</th>
                                        <th style="border: 1px solid #ddd; padding: 12px 8px; background: #f8f9fa; font-weight: bold; text-transform: uppercase; font-size: 12px; width: 15%;">Prix Unit.</th>
                                        <th style="border: 1px solid #ddd; padding: 12px 8px; background: #f8f9fa; font-weight: bold; text-transform: uppercase; font-size: 12px; width: 20%;">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $itemNumber = 1;
                                        $totalGeneral = 0;
                                    @endphp
                                    
                                    @foreach($consultation->packages as $package)
                                        <tr>
                                            <td style="border: 1px solid #ddd; padding: 12px 8px;">{{ $itemNumber++ }}</td>
                                            <td style="border: 1px solid #ddd; padding: 12px 8px;"><strong>Package:</strong> {{ $package->name }}</td>
                                            <td style="border: 1px solid #ddd; padding: 12px 8px;">1</td>
                                            <td style="border: 1px solid #ddd; padding: 12px 8px;">{{ number_format($package->price, 0, ',', ' ') }} GNF</td>
                                            <td style="border: 1px solid #ddd; padding: 12px 8px;">{{ number_format($package->price, 0, ',', ' ') }} GNF</td>
                                        </tr>
                                        @php $totalGeneral += $package->price; @endphp
                                    @endforeach

                                    @foreach($consultation->services as $service)
                                        <tr>
                                            <td style="border: 1px solid #ddd; padding: 12px 8px;">{{ $itemNumber++ }}</td>
                                            <td style="border: 1px solid #ddd; padding: 12px 8px;"><strong>Service:</strong> {{ $service->name }}</td>
                                            <td style="border: 1px solid #ddd; padding: 12px 8px;">1</td>
                                            <td style="border: 1px solid #ddd; padding: 12px 8px;">{{ number_format($service->amount, 0, ',', ' ') }} GNF</td>
                                            <td style="border: 1px solid #ddd; padding: 12px 8px;">{{ number_format($service->amount, 0, ',', ' ') }} GNF</td>
                                        </tr>
                                        @php $totalGeneral += $service->amount; @endphp
                                    @endforeach

                                    @foreach($consultation->tests as $test)
                                        <tr>
                                            <td style="border: 1px solid #ddd; padding: 12px 8px;">{{ $itemNumber++ }}</td>
                                            <td style="border: 1px solid #ddd; padding: 12px 8px;"><strong>Examen:</strong> {{ $test->name }}</td>
                                            <td style="border: 1px solid #ddd; padding: 12px 8px;">1</td>
                                            <td style="border: 1px solid #ddd; padding: 12px 8px;">{{ number_format($test->amount, 0, ',', ' ') }} GNF</td>
                                            <td style="border: 1px solid #ddd; padding: 12px 8px;">{{ number_format($test->amount, 0, ',', ' ') }} GNF</td>
                                        </tr>
                                        @php $totalGeneral += $test->amount; @endphp
                                    @endforeach

                                    @foreach($consultation->medicaments as $medicament)
                                        <tr>
                                            <td style="border: 1px solid #ddd; padding: 12px 8px;">{{ $itemNumber++ }}</td>
                                            <td style="border: 1px solid #ddd; padding: 12px 8px;"><strong>Médicament:</strong> {{ $medicament->nom }}</td>
                                            <td style="border: 1px solid #ddd; padding: 12px 8px;">{{ $medicament->pivot->quantity }}</td>
                                            <td style="border: 1px solid #ddd; padding: 12px 8px;">{{ number_format($medicament->amount, 0, ',', ' ') }} GNF</td>
                                            <td style="border: 1px solid #ddd; padding: 12px 8px;">{{ number_format($medicament->amount * $medicament->pivot->quantity, 0, ',', ' ') }} GNF</td>
                                        </tr>
                                        @php $totalGeneral += ($medicament->amount * $medicament->pivot->quantity); @endphp
                                    @endforeach
                                </tbody>
                            </table>

                            <div style="float: right; width: 300px; margin-bottom: 40px;">
                                <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee;">
                                    <span>Sous-total:</span>
                                    <span>{{ number_format($totalGeneral, 0, ',', ' ') }} GNF</span>
                                </div>
                                @if($consultation->transaction)
                                    <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee;">
                                        <span>Montant Payé:</span>
                                        <span>{{ number_format($consultation->transaction->montant_payer, 0, ',', ' ') }} GNF</span>
                                    </div>
                                    @php
                                        $restant = $consultation->transaction->total - $consultation->transaction->montant_payer;
                                    @endphp
                                    <div style="display: flex; justify-content: space-between; padding: 8px 0; border-top: 2px solid #333; border-bottom: 2px solid #333; font-weight: bold; font-size: 16px; margin-top: 10px;">
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

                            <div style="position: absolute; bottom: 20px; left: 40px; right: 40px; font-size: 10px; text-align: center; color: #666; border-top: 1px solid #ddd; padding-top: 15px; background: white;">
                                Tél: {{$hopital->contact}} - Adresse: {{$hopital->address}}<br>
                                Email: {{$hopital->email}} - Site web: {{$hopital->website ?? ''}}
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
    // Auto-fermeture des alertes
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);

    // Fonction pour imprimer l'ordonnance
    function printPrescription() {
        var content = document.getElementById('prescriptionContent').outerHTML;
        var printWindow = window.open('', '_blank', 'width=800,height=600');
        
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Ordonnance Médicale</title>
                <style>
                    * { margin: 0; padding: 0; box-sizing: border-box; }
                    body { font-family: 'Times New Roman', serif; background: white; }
                    @media print {
                        body { padding: 20px; }
                        .prescription { box-shadow: none !important; }
                    }
                </style>
            </head>
            <body>${content}</body>
            </html>
        `);
        
        printWindow.document.close();
        setTimeout(() => { printWindow.print(); printWindow.close(); }, 250);
    }

    // Fonction pour imprimer les examens
    function printExamens() {
        var content = document.getElementById('examensContent').outerHTML;
        var printWindow = window.open('', '_blank', 'width=800,height=600');
        
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Examens Médicaux</title>
                <style>
                    * { margin: 0; padding: 0; box-sizing: border-box; }
                    body { font-family: 'Times New Roman', serif; background: white; padding: 20px; }
                </style>
            </head>
            <body>${content}</body>
            </html>
        `);
        
        printWindow.document.close();
        setTimeout(() => { printWindow.print(); printWindow.close(); }, 250);
    }

    // Fonction pour imprimer la facture
    function printFacture() {
        var content = document.getElementById('factureContent').outerHTML;
        var printWindow = window.open('', '_blank', 'width=1000,height=800');
        
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Facture Complète</title>
                <style>
                    * { margin: 0; padding: 0; box-sizing: border-box; }
                    body { font-family: 'Times New Roman', serif; background: white; padding: 20px; }
                </style>
            </head>
            <body>${content}</body>
            </html>
        `);
        
        printWindow.document.close();
        setTimeout(() => { printWindow.print(); printWindow.close(); }, 250);
    }
</script>
@endsection