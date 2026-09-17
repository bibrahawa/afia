@extends('layouts.backend')

@section('style')
<style>
    /* =========================================================
       GLOBAL / SCREEN
    ========================================================= */
    body {
        background: #f5f7fb;
    }

    .card {
        border: none;
        border-radius: 14px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    }

    .card-header {
        border-top-left-radius: 14px !important;
        border-top-right-radius: 14px !important;
    }

    .page-inner {
        padding-bottom: 30px;
    }

    .action-buttons-fixed {
        position: sticky;
        top: 20px;
        z-index: 100;
        background: #fff;
        padding: 15px;
        border-radius: 12px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        margin-bottom: 20px;
    }

    .financial-summary {
        background: linear-gradient(135deg, #4158D0 0%, #6C63FF 55%, #00B4D8 100%);
        color: white;
        padding: 25px;
        border-radius: 16px;
        margin-bottom: 30px;
    }

    .financial-summary .label {
        font-size: 0.9rem;
        opacity: 0.95;
    }

    .financial-summary .amount {
        font-size: 1.8rem;
        font-weight: 700;
        margin-top: 8px;
    }

    .info-item {
        margin-bottom: 18px;
    }

    .info-label {
        font-size: 12px;
        color: #6c757d;
        text-transform: uppercase;
        font-weight: 700;
        margin-bottom: 6px;
        letter-spacing: 0.4px;
    }

    .info-value {
        font-size: 15px;
        font-weight: 600;
        color: #212529;
    }

    .clinical-info-card {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 1.1rem;
        border: 1px solid #e9ecef;
        height: 100%;
    }

    .icon-box {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .diagnostic-badge {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: #fff;
        padding: 0.75rem 1rem;
        border-radius: 10px;
        font-weight: 700;
        display: inline-block;
    }

    .observation-box {
        background: #fff;
        border-left: 4px solid #6c757d;
        padding: 1rem;
        border-radius: 8px;
    }

    .table thead th {
        background: #f8f9fa;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.4px;
        border-bottom: 2px solid #dee2e6;
    }

    .payment-status {
        display: inline-flex;
        align-items: center;
        padding: 8px 14px;
        border-radius: 20px;
        font-weight: 700;
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
        padding: 10px 0;
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
        background: #0d6efd;
        border: 3px solid #fff;
        box-shadow: 0 0 0 2px #0d6efd;
    }

    .hover-card {
        transition: all 0.25s ease;
        border: 1px solid #e9ecef;
    }

    .hover-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 18px rgba(0,0,0,0.10) !important;
        border-color: #0d6efd;
    }

    .service-card {
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e9ecef;
        transition: 0.25s ease;
        height: 100%;
    }

    .service-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 18px rgba(0,0,0,0.08);
    }

    .service-card-body {
        padding: 1.2rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .service-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #0d6efd, #0dcaf0);
        color: #fff;
        font-size: 1.2rem;
        flex-shrink: 0;
    }

    .service-name {
        font-size: 0.95rem;
        font-weight: 700;
        margin-bottom: 4px;
        color: #212529;
    }

    .service-price {
        font-size: 1rem;
        font-weight: 700;
        color: #0d6efd;
    }

    /* =========================================================
       DOCUMENTS
    ========================================================= */
    .modal-print-buttons {
        background: #f8f9fa;
        padding: 15px;
        border-bottom: 1px solid #dee2e6;
    }

    .document-print {
        max-width: 900px;
        margin: 0 auto;
        background: #fff;
        padding: 40px 50px;
        color: #000;
        font-family: "Times New Roman", serif;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        min-height: 1100px;
        position: relative;
    }

    .document-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 20px;
        margin-bottom: 32px;
    }

    .document-left {
        width: 48%;
    }

    .document-right {
        width: 42%;
        text-align: center;
    }

    .document-logo {
        width: 160px;
        margin-bottom: 14px;
    }

    .document-logo img {
        max-width: 100%;
        max-height: 120px;
        object-fit: contain;
    }

    .document-clinic-name {
        font-size: 19px;
        font-weight: 700;
        text-transform: uppercase;
        line-height: 1.35;
        margin-bottom: 4px;
    }

    .document-clinic-line {
        font-size: 13px;
        line-height: 1.6;
    }

    .document-title {
        font-size: 24px;
        font-weight: 700;
        text-transform: uppercase;
        text-decoration: underline;
        margin-bottom: 28px;
    }

    .document-subtitle {
        font-size: 16px;
        font-weight: 700;
        text-transform: uppercase;
        margin-bottom: 8px;
    }

    .document-recipient-name,
    .document-recipient-phone {
        font-size: 15px;
        font-weight: 700;
        line-height: 1.5;
    }

    .document-date {
        margin-top: 20px;
        font-size: 15px;
        font-weight: 700;
    }

    .document-ref {
        margin-top: 10px;
        font-size: 14px;
    }

    .document-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 25px;
        margin-bottom: 36px;
    }

    .document-table th {
        background: #7433a6;
        color: #fff;
        border: 1px solid #666;
        padding: 10px 8px;
        font-size: 14px;
        text-transform: uppercase;
        text-align: center;
    }

    .document-table td {
        border: 1px solid #666;
        padding: 10px 8px;
        font-size: 14px;
        vertical-align: top;
    }

    .text-center-doc {
        text-align: center;
    }

    .text-right-doc {
        text-align: right;
    }

    .totals-box {
        width: 420px;
        margin-left: auto;
        margin-bottom: 25px;
    }

    .totals-line {
        display: flex;
        justify-content: space-between;
        padding: 10px 14px;
        font-size: 14px;
        font-weight: 700;
    }

    .totals-line.gray {
        background: #d9d9d9;
    }

    .totals-line .label {
        width: 60%;
        text-align: right;
        padding-right: 12px;
    }

    .totals-line .value {
        width: 40%;
        text-align: right;
    }

    .amount-words {
        margin-top: 22px;
        font-size: 15px;
        font-weight: 700;
    }

    .amount-words span {
        font-size: 17px;
        text-transform: uppercase;
    }

    .signature-zone {
        margin-top: 55px;
        display: flex;
        justify-content: space-between;
        gap: 25px;
    }

    .signature-box {
        width: 45%;
        text-align: center;
    }

    .signature-box .line {
        margin-top: 60px;
        border-top: 1px solid #000;
        padding-top: 8px;
        font-size: 13px;
        font-weight: 700;
    }

    .document-simple-title {
        text-align: center;
        font-size: 24px;
        font-weight: 700;
        text-transform: uppercase;
        text-decoration: underline;
        margin: 25px 0 25px 0;
    }

    .document-meta-box {
        border: 1px solid #d8d8d8;
        padding: 14px 16px;
        background: #f9f9f9;
        margin-bottom: 22px;
        font-size: 14px;
    }

    .document-section-title {
        font-size: 16px;
        font-weight: 700;
        text-transform: uppercase;
        margin-bottom: 12px;
    }

    .document-footer-note {
        position: absolute;
        left: 50px;
        right: 50px;
        bottom: 20px;
        text-align: center;
        font-size: 11px;
        color: #444;
        border-top: 1px solid #999;
        padding-top: 10px;
    }

    /* =========================================================
       PRINT
    ========================================================= */
    @media print {
        .no-print,
        .modal-header,
        .modal-print-buttons,
        .btn,
        .btn-close {
            display: none !important;
        }

        body {
            background: #fff !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        .document-print {
            box-shadow: none !important;
            margin: 0 !important;
            padding: 25px 35px !important;
            max-width: 100% !important;
            min-height: auto !important;
        }

        .document-footer-note {
            position: fixed;
            left: 35px;
            right: 35px;
            bottom: 10px;
        }

        .card {
            box-shadow: none !important;
            border: 1px solid #ddd !important;
        }
    }

    @media (max-width: 768px) {
        .action-buttons-fixed {
            position: relative;
            top: 0;
        }

        .financial-summary .amount {
            font-size: 1.4rem;
        }

        .document-header {
            flex-direction: column;
        }

        .document-left,
        .document-right {
            width: 100%;
        }

        .totals-box {
            width: 100%;
        }

        .signature-zone {
            flex-direction: column;
        }

        .signature-box {
            width: 100%;
        }
    }
    
</style>

<style>
    .action-buttons-fixed .dropdown-menu {
        border: none;
        border-radius: 12px;
        padding: 8px;
        min-width: 180px;
    }

    .action-buttons-fixed .dropdown-item {
        border-radius: 8px;
        padding: 10px 12px;
        font-weight: 500;
    }

    .action-buttons-fixed .dropdown-item:hover {
        background: #f8f9fa;
    }
</style>

@endsection

@section('content')
@php
    $transaction = $consultation->transaction;
    $invoice = optional($transaction)->invoice;
    $paiements = $transaction ? $transaction->paiements : collect();
    $dernierPaiement = $paiements && $paiements->count() > 0 ? $paiements->sortByDesc('created_at')->first() : null;

    $invoiceItems = $invoice && $invoice->items ? $invoice->items : collect();

    $fallbackItems = collect();

    if ($invoiceItems->count() === 0) {
        foreach ($consultation->packages as $package) {
            $fallbackItems->push((object)[
                'description' => 'Package : ' . $package->name,
                'quantity' => 1,
                'unit_price' => $package->price,
                'total_amount' => $package->price,
            ]);
        }

        foreach ($consultation->services as $service) {
            $fallbackItems->push((object)[
                'description' => 'Service : ' . $service->name,
                'quantity' => 1,
                'unit_price' => $service->amount,
                'total_amount' => $service->amount,
            ]);
        }

        foreach ($consultation->tests as $test) {
            $fallbackItems->push((object)[
                'description' => 'Examen : ' . $test->name,
                'quantity' => 1,
                'unit_price' => $test->amount,
                'total_amount' => $test->amount,
            ]);
        }

        foreach ($consultation->medicaments as $medicament) {
            $fallbackItems->push((object)[
                'description' => 'Médicament : ' . $medicament->nom,
                'quantity' => $medicament->pivot->quantity,
                'unit_price' => $medicament->amount,
                'total_amount' => $medicament->amount * $medicament->pivot->quantity,
            ]);
        }
    }

    $displayItems = $invoiceItems->count() > 0 ? $invoiceItems : $fallbackItems;

    $totalFacture = $invoice ? ($invoice->total_amount ?? 0) : $displayItems->sum('total_amount');
    $montantPaye = $transaction->montant_payer ?? 0;
    $solde = max(0, $totalFacture - $montantPaye);

    $invoiceNumber = $invoice->invoice_no ?? ('FAC-' . now()->format('Y') . str_pad($consultation->id, 5, '0', STR_PAD_LEFT));
    $receiptNumber = 'REC-' . now()->format('Y') . str_pad($consultation->id, 5, '0', STR_PAD_LEFT);

    $montantDernierPaiement = $dernierPaiement->montant ?? $montantPaye;
    $sourceDernierPaiement = $dernierPaiement->source ?? 'Non précisé';
    $referenceTransaction = $transaction ? 'TXN-' . $transaction->id : '---';

    $amountInWords = $amountInWords ?? 'À compléter';
    $paymentAmountInWords = $paymentAmountInWords ?? 'À compléter';
@endphp

<div class="container">
    <div class="page-inner">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <strong>Succès !</strong> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <strong>Erreur !</strong> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="page-header no-print mb-4">
            <ul class="breadcrumbs">
                <li class="nav-home">
                    <a href="{{ url('/') }}"><i class="icon-home"></i></a>
                </li>
                <li class="separator"><i class="icon-arrow-right"></i></li>
                <li class="nav-item"><a href="{{ url('/') }}">Admin</a></li>
                <li class="separator"><i class="icon-arrow-right"></i></li>
                <li class="nav-item"><a href="{{ route('consultation.index') }}">Consultations</a></li>
                <li class="separator"><i class="icon-arrow-right"></i></li>
                <li class="nav-item"><a href="#"><b>{{ $consultation->created_at->format('d M Y') }}</b></a></li>
            </ul>
        </div>

        <div class="action-buttons-fixed no-print">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h5 class="mb-0">
                        <i class="fas fa-file-medical text-primary"></i>
                        Consultation #{{ str_pad($consultation->id, 6, '0', STR_PAD_LEFT) }}
                    </h5>
                    <small class="text-muted">{{ $consultation->created_at->format('d/m/Y à H:i') }}</small>
                </div>

                <div class="d-flex flex-wrap gap-2">

                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-warning dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-prescription me-1"></i> Ordonnance
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li>
                                <a class="dropdown-item" target="_blank"
                                href="{{ route('consultation.rapport.ordonnance.a80', $consultation->id) }}">
                                    <i class="fas fa-receipt me-2 text-secondary"></i>Format A80
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" target="_blank"
                                href="{{ route('consultation.rapport.ordonnance.a5', $consultation->id) }}">
                                    <i class="fas fa-file-alt me-2 text-warning"></i>Format A5
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-success dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-microscope me-1"></i> Examens
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li>
                                <a class="dropdown-item" target="_blank"
                                href="{{ route('consultation.rapport.examens.a80', $consultation->id) }}">
                                    <i class="fas fa-receipt me-2 text-secondary"></i>Format A80
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" target="_blank"
                                href="{{ route('consultation.rapport.examens.a5', $consultation->id) }}">
                                    <i class="fas fa-file-alt me-2 text-success"></i>Format A5
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-info dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-file-invoice-dollar me-1"></i> Facture
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li>
                                <a class="dropdown-item" target="_blank"
                                href="{{ route('consultation.pdf.facture.a80', $consultation->id) }}">
                                    <i class="fas fa-receipt me-2 text-secondary"></i>Format A80
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" target="_blank"
                                href="{{ route('consultation.pdf.facture.a5', $consultation->id) }}">
                                    <i class="fas fa-file-alt me-2 text-info"></i>Format A5
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-money-check-alt me-1"></i> Reçu
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li>
                                <a class="dropdown-item" target="_blank"
                                href="{{ route('consultation.pdf.recu.a80', $consultation->id) }}">
                                    <i class="fas fa-receipt me-2 text-secondary"></i>Format A80
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" target="_blank"
                                href="{{ route('consultation.pdf.recu.a5', $consultation->id) }}">
                                    <i class="fas fa-file-alt me-2 text-primary"></i>Format A5
                                </a>
                            </li>
                        </ul>
                    </div>
                    
                    @include('labo.partials.consultation-bouton', ['consultation' => $consultation])
                    
                    <a href="{{ route('consultation.index') }}" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Retour
                    </a>
                </div>
            </div>
        </div>

        @if($transaction)
            <div class="financial-summary no-print">
                <div class="row text-center">
                    <div class="col-md-4">
                        <div class="label">Montant Total</div>
                        <div class="amount">{{ number_format($totalFacture, 0, ',', ' ') }} GNF</div>
                    </div>
                    <div class="col-md-4">
                        <div class="label">Montant Payé</div>
                        <div class="amount">{{ number_format($montantPaye, 0, ',', ' ') }} GNF</div>
                    </div>
                    <div class="col-md-4">
                        <div class="label">Restant à Payer</div>
                        <div class="amount" style="color: {{ $solde > 0 ? '#ffeb3b' : '#d4edda' }};">
                            {{ number_format($solde, 0, ',', ' ') }} GNF
                        </div>

                        @if($solde <= 0)
                            <span class="badge bg-success mt-2">✓ Payé intégralement</span>
                        @elseif($montantPaye > 0)
                            <span class="badge bg-warning text-dark mt-2">⚠ Paiement partiel</span>
                        @else
                            <span class="badge bg-danger mt-2">✗ Non payé</span>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <!-- Informations Patient -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user-injured me-2"></i>Informations du Patient
                        </h5>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="info-item">
                                    <div class="info-label">Patient</div>
                                    <div class="info-value">
                                        {{ $consultation->patient->first_name . ' ' . $consultation->patient->last_name }}
                                    </div>
                                </div>

                                <div class="info-item">
                                    <div class="info-label">Téléphone</div>
                                    <div class="info-value">{{ $consultation->patient->phone }}</div>
                                </div>

                                <div class="info-item">
                                    <div class="info-label">Adresse</div>
                                    <div class="info-value">{{ $consultation->patient->district }}</div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="info-item">
                                    <div class="info-label">Groupe sanguin</div>
                                    <div class="info-value">{{ $consultation->patient->blood_group ?? 'Non précisé' }}</div>
                                </div>

                                {{-- <div class="info-item">
                                    <div class="info-label">Médecin traitant</div>
                                    <div class="info-value">
                                        Dr. {{ $consultation->medecin->first_name . ' ' . $consultation->medecin->last_name }}
                                    </div>
                                </div> --}}

                                <div class="info-item">
                                    <div class="info-label">Prochain rendez-vous</div>
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
                                    <div class="info-label">Antécédents</div>
                                    <div class="info-value">
                                        @if($consultation->patient->antecedant)
                                            @if($consultation->patient->antecedant->antecedents_medicaux)
                                                <div>{{ $consultation->patient->antecedant->antecedents_medicaux }}</div>
                                            @endif

                                            @if($consultation->patient->antecedant->allergies)
                                                <div>Allergies : {{ $consultation->patient->antecedant->allergies }}</div>
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

        <!-- Diagnostic -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-stethoscope me-2"></i>Diagnostic et Examens Cliniques
                        </h5>
                    </div>

                    <div class="card-body p-4">
                        <div class="row g-4">
                            <div class="col-md-6 col-lg-4">
                                <div class="clinical-info-card">
                                    <div class="d-flex align-items-start">
                                        <div class="icon-box bg-primary bg-opacity-10 text-primary me-3">
                                            <i class="fas fa-hospital-alt"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted text-uppercase d-block mb-1">Département</small>
                                            <h6 class="mb-0 fw-semibold">{{ $consultation->department->name }}</h6>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-8">
                                <div class="clinical-info-card">
                                    <div class="d-flex align-items-start">
                                        <div class="icon-box bg-info bg-opacity-10 text-info me-3">
                                            <i class="fas fa-notes-medical"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted text-uppercase d-block mb-1">Motif de consultation</small>
                                            <p class="mb-0">{{ $consultation->motif ?? 'Non précisé' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12 col-lg-7">
                                <div class="clinical-info-card">
                                    <div class="d-flex align-items-start">
                                        <div class="icon-box bg-warning bg-opacity-10 text-warning me-3">
                                            <i class="fas fa-heartbeat"></i>
                                        </div>
                                        <div class="w-100">
                                            <small class="text-muted text-uppercase d-block mb-2">Signes cliniques</small>

                                            @if($consultation->signes_cliniques && is_array($consultation->signes_cliniques))
                                                <div class="d-flex flex-wrap gap-2">
                                                    @foreach($consultation->signes_cliniques as $signe)
                                                        <span class="badge rounded-pill bg-warning text-dark">{{ $signe }}</span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-muted">Non précisé</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12 col-lg-5">
                                <div class="clinical-info-card h-100">
                                    <div class="d-flex align-items-start h-100">
                                        <div class="icon-box bg-danger bg-opacity-10 text-danger me-3">
                                            <i class="fas fa-diagnoses"></i>
                                        </div>
                                        <div class="d-flex flex-column justify-content-center">
                                            <small class="text-muted text-uppercase d-block mb-2">Diagnostic</small>
                                            <div class="diagnostic-badge">
                                                {{ $consultation->diagnostic ?? 'Non précisé' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if($consultation->observation)
                                <div class="col-md-12">
                                    <div class="clinical-info-card">
                                        <div class="d-flex align-items-start">
                                            <div class="icon-box bg-secondary bg-opacity-10 text-secondary me-3">
                                                <i class="fas fa-comment-medical"></i>
                                            </div>
                                            <div class="w-100">
                                                <small class="text-muted text-uppercase d-block mb-2">Observations médicales</small>
                                                <div class="observation-box">
                                                    <p class="mb-0">{{ $consultation->observation }}</p>
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

        @include('labo.partials.consultation-analyses', ['consultation' => $consultation])

        <!-- Services / Facturation -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-file-invoice-dollar me-2"></i>Services et Facturation
                        </h5>
                    </div>

                    <div class="card-body">
                        @if($displayItems->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Description</th>
                                            <th class="text-center">Qté</th>
                                            <th class="text-end">Prix unitaire</th>
                                            <th class="text-end">Montant</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($displayItems as $index => $item)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td><strong>{{ $item->description }}</strong></td>
                                                <td class="text-center">{{ $item->quantity }}</td>
                                                <td class="text-end">{{ number_format($item->unit_price, 0, ',', ' ') }} GNF</td>
                                                <td class="text-end"><strong>{{ number_format($item->total_amount, 0, ',', ' ') }} GNF</strong></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-active">
                                            <th colspan="4" class="text-end">Total général</th>
                                            <th class="text-end">{{ number_format($totalFacture, 0, ',', ' ') }} GNF</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p class="mb-0">Aucune ligne de facturation disponible.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Ordonnance -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-pills me-2"></i>Ordonnance Médicale
                        </h5>
                    </div>

                    <div class="card-body">
                        @if($consultation->medicaments->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>N°</th>
                                            <th>Médicament</th>
                                            <th>Qté</th>
                                            <th>Fréquence</th>
                                            <th>Durée</th>
                                            <th>Instructions</th>
                                            <th>Prix</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($consultation->medicaments as $indice => $med)
                                            <tr>
                                                <td>{{ $indice + 1 }}</td>
                                                <td><strong>{{ $med->nom }}</strong></td>
                                                <td>{{ $med->pivot->quantity }}</td>
                                                <td>{{ $med->frequence }}</td>
                                                <td>{{ $med->duree }}</td>
                                                <td>{{ $med->instructions ?: '-' }}</td>
                                                <td><strong>{{ number_format($med->amount * $med->pivot->quantity, 0, ',', ' ') }} GNF</strong></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-active">
                                            <th colspan="6" class="text-end">Total médicaments</th>
                                            <th>
                                                {{ number_format($consultation->medicaments->sum(function($m) { return $m->amount * $m->pivot->quantity; }), 0, ',', ' ') }} GNF
                                            </th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-prescription-bottle-alt fa-3x mb-3"></i>
                                <p class="mb-0">Aucun médicament prescrit pour cette consultation.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Fichiers -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-paperclip me-2"></i>Fichiers et Documents Joints
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
                                                        } elseif ($extension === 'pdf') {
                                                            $iconClass = 'fa-file-pdf';
                                                            $iconColor = '#dc3545';
                                                        } elseif (in_array($extension, ['doc', 'docx'])) {
                                                            $iconClass = 'fa-file-word';
                                                            $iconColor = '#0d6efd';
                                                        } elseif (in_array($extension, ['xls', 'xlsx'])) {
                                                            $iconClass = 'fa-file-excel';
                                                            $iconColor = '#198754';
                                                        }
                                                    @endphp

                                                    <i class="fas {{ $iconClass }} fa-4x" style="color: {{ $iconColor }}"></i>
                                                </div>

                                                <h6 class="card-title text-truncate" title="{{ $file->nom_fichier }}">
                                                    {{ $file->nom_fichier }}
                                                </h6>

                                                <div class="mt-auto">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <small class="text-muted">{{ $file->created_at->format('d/m/Y') }}</small>
                                                        <span class="badge" style="background-color: {{ $iconColor }}">
                                                            {{ strtoupper($extension) }}
                                                        </span>
                                                    </div>

                                                    @if($file->description)
                                                        <p class="small text-muted mb-2">{{ \Illuminate\Support\Str::limit($file->description, 50) }}</p>
                                                    @endif

                                                    <div class="d-flex gap-2">
                                                        <a href="{{ Storage::url($file->chemin) }}" target="_blank" class="btn btn-sm btn-outline-primary flex-fill">
                                                            <i class="fas fa-eye"></i> Voir
                                                        </a>
                                                        <a href="{{ Storage::url($file->chemin) }}" download="{{ $file->nom_fichier }}" class="btn btn-sm btn-outline-success flex-fill">
                                                            <i class="fas fa-download"></i> Télécharger
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-folder-open fa-3x mb-3"></i>
                                <p class="mb-0">Aucun document joint à cette consultation.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Finances -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-credit-card me-2"></i>Informations Financières
                        </h5>
                    </div>

                    <div class="card-body">
                        @if($transaction)
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
                                                    <td>{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                                                    <td>{{ $transaction->description }}</td>
                                                    <td><strong class="text-primary">{{ number_format($transaction->total, 0, ',', ' ') }} GNF</strong></td>
                                                    <td><strong class="text-success">{{ number_format($transaction->montant_payer, 0, ',', ' ') }} GNF</strong></td>
                                                    <td>
                                                        @if($solde <= 0)
                                                            <span class="payment-status paid">
                                                                <i class="fas fa-check-circle me-1"></i>Payé intégralement
                                                            </span>
                                                        @elseif($transaction->montant_payer > 0)
                                                            <span class="payment-status partial">
                                                                <i class="fas fa-exclamation-triangle me-1"></i>Reste: {{ number_format($solde, 0, ',', ' ') }} GNF
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

                            <div class="row">
                                <div class="col-md-12">
                                    <h6 class="text-success mb-3">
                                        <i class="fas fa-history me-2"></i>Historique des Paiements
                                    </h6>

                                    @if($paiements->count() > 0)
                                        <div class="timeline">
                                            @foreach($paiements as $paiement)
                                                <div class="timeline-item">
                                                    <div class="timeline-dot"></div>
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <h6 class="mb-1">{{ number_format($paiement->montant, 0, ',', ' ') }} GNF</h6>
                                                            <p class="mb-1 text-muted">{{ $paiement->description }}</p>
                                                            <small class="text-muted">{{ $paiement->created_at->format('d/m/Y à H:i') }}</small>
                                                        </div>
                                                        <span class="badge bg-secondary">{{ $paiement->source }}</span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="alert alert-warning mb-0">
                                            Aucun paiement enregistré pour cette consultation.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="alert alert-info text-center py-4 mb-0">
                                <i class="fas fa-info-circle fa-2x mb-2"></i>
                                <p class="mb-0">Aucune transaction enregistrée pour cette consultation.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@section('script')
<script>
    setTimeout(function () {
        $('.alert').fadeOut('slow');
    }, 5000);
</script>
@endsection