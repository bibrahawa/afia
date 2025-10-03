<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reçu de paiement</title>
    <style>
        :root {
            --primary-color: #4caf50;
            --secondary-color: #81c784;
            --accent-color: #e8f5e9;
            --text-color: #333;
            --light-gray: #f5f5f5;
            --border-color: #e0e0e0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }

        .container {
            width: 21cm;
            min-height: 14.8cm; /* A5 height */
            padding: 1.5cm;
            margin: 0 auto;
            background-color: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .receipt-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 1rem;
        }

        .clinic-info {
            flex: 2;
        }

        .clinic-logo {
            width: 150px;
            height: auto;
            margin-bottom: 0.5rem;
        }

        .clinic-name {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
            margin: 0 0 0.25rem 0;
        }

        .clinic-details {
            font-size: 0.9rem;
            color: #555;
        }

        .receipt-title {
            flex: 1;
            text-align: right;
        }

        .receipt-title h1 {
            color: var(--primary-color);
            font-size: 2rem;
            margin: 0;
        }

        .receipt-title .receipt-number {
            font-size: 1rem;
            color: #777;
            margin-top: 0.5rem;
        }

        .receipt-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .patient-info, .payment-info {
            flex: 1;
        }

        .info-box {
            background-color: var(--accent-color);
            border-left: 4px solid var(--primary-color);
            padding: 1rem;
            border-radius: 4px;
        }

        .info-box h3 {
            margin-top: 0;
            color: var(--primary-color);
            font-size: 1.2rem;
        }

        .info-content p {
            margin: 0.3rem 0;
        }

        .payment-details {
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-color);
            border-radius: 4px;
        }

        .payment-header {
            background-color: var(--primary-color);
            color: white;
            padding: 0.7rem 1rem;
            border-radius: 4px 4px 0 0;
        }

        .payment-content {
            padding: 1rem;
        }

        .payment-table {
            width: 100%;
            border-collapse: collapse;
        }

        .payment-table th, .payment-table td {
            padding: 0.8rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .payment-table th {
            background-color: #f0f0f0;
            font-weight: 600;
        }

        .payment-total {
            margin-top: 1rem;
            text-align: right;
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .payment-method {
            margin-top: 1rem;
            padding: 0.8rem;
            background-color: var(--light-gray);
            border-radius: 4px;
        }

        .payment-method-label {
            font-weight: bold;
            margin-right: 1rem;
        }

        .payment-method-value {
            font-weight: bold;
            color: var(--primary-color);
        }

        .payment-status {
            margin-top: 1rem;
            padding: 0.5rem 1rem;
            background-color: var(--primary-color);
            color: white;
            border-radius: 20px;
            display: inline-block;
            font-weight: bold;
        }

        .paid-stamp {
            position: absolute;
            top: 50%;
            right: 10%;
            transform: rotate(-25deg);
            font-size: 5rem;
            font-weight: bold;
            color: rgba(76, 175, 80, 0.15);
            border: 1rem solid rgba(76, 175, 80, 0.15);
            border-radius: 10px;
            padding: 0.5rem 2rem;
        }

        .signature {
            margin-top: 2rem;
            display: flex;
            justify-content: flex-end;
        }

        .signature-box {
            width: 200px;
            text-align: center;
        }

        .signature-line {
            border-top: 1px solid var(--border-color);
            padding-top: 0.5rem;
        }

        .footer {
            margin-top: 2rem;
            text-align: center;
            font-size: 0.9rem;
            color: #777;
            padding-top: 1rem;
            border-top: 1px dashed var(--border-color);
        }

        .footer p {
            margin: 0.2rem 0;
        }

        .qr-code {
            position: absolute;
            bottom: 1.5cm;
            left: 1.5cm;
            width: 80px;
            height: 80px;
        }

        @media print {
            body {
                background-color: white;
            }
            .container {
                box-shadow: none;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- En-tête du reçu -->
        <div class="receipt-header">
            <div class="clinic-info">
                <img src="/api/placeholder/150/60" alt="Logo Clinique" class="clinic-logo">
                <h2 class="clinic-name">Clinique AprosafeCare</h2>
                <div class="clinic-details">
                    <p>123 Avenue de la Santé, Abidjan, Côte d'Ivoire</p>
                    <p>Tél: +225 27 20 XX XX XX | Email: contact@aprosafe.ci</p>
                </div>
            </div>
            <div class="receipt-title">
                <h1>REÇU</h1>
                <div class="receipt-number">N° {{ $consultation->id }}/{{ date('Y') }}</div>
                <div>Date: {{ $consultation->created_at->format('d/m/Y') }}</div>
            </div>
        </div>

        <!-- Informations du patient et du paiement -->
        <div class="receipt-info">
            <div class="patient-info">
                <div class="info-box">
                    <h3>Patient</h3>
                    <div class="info-content">
                        <p><strong>Nom & Prénom:</strong> {{ $consultation->patient->first_name }} {{ $consultation->patient->last_name }}</p>
                        <p><strong>ID Patient:</strong> {{ $consultation->patient->id }}</p>
                        <p><strong>Tél:</strong> {{ $consultation->patient->phone ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
            <div class="payment-info">
                <div class="info-box">
                    <h3>Référence consultation</h3>
                    <div class="info-content">
                        <p><strong>N° Consultation:</strong> {{ $consultation->id }}</p>
                        <p><strong>Médecin:</strong> Dr. {{ $consultation->medecin->first_name }} {{ $consultation->medecin->last_name }}</p>
                        <p><strong>Département:</strong> {{ $consultation->department->name }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Détails du paiement -->
        <div class="payment-details">
            <div class="payment-header">
                <h3 style="margin: 0;">Détails du paiement</h3>
            </div>
            <div class="payment-content">
                <table class="payment-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Détails</th>
                            <th style="text-align: right;">Montant</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Consultation médicale</td>
                            <td>{{ $consultation->department->name }} - Dr. {{ $consultation->medecin->last_name }}</td>
                            <td style="text-align: right;">{{ number_format($consultation->total * 0.2, 0, ',', ' ') }} GNF</td>
                        </tr>
                        @foreach ($consultation->services as $service)
                        <tr>
                            <td>Service médical</td>
                            <td>{{ $service->name }}</td>
                            <td style="text-align: right;">{{ number_format($service->prix ?? ($consultation->total * 0.4 / count($consultation->services)), 0, ',', ' ') }} GNF</td>
                        </tr>
                        @endforeach
                        @foreach ($consultation->packages as $package)
                        <tr>
                            <td>Package médical</td>
                            <td>{{ $package->name }}</td>
                            <td style="text-align: right;">{{ number_format($package->prix ?? ($consultation->total * 0.4 / count($consultation->packages)), 0, ',', ' ') }} GNF</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="payment-total">
                    Total: {{ number_format($consultation->total, 0, ',', ' ') }} GNF
                </div>

                <div class="payment-method">
                    <span class="payment-method-label">Mode de paiement:</span>
                    <span class="payment-method-value">{{ $consultation->mode_paiement }}</span>
                </div>

                <div style="text-align: right; margin-top: 1rem;">
                    <div class="payment-status">
                        {{ $consultation->statut }}
                    </div>
                </div>
            </div>
        </div>

        @if($consultation->statut == 'Payé' || $consultation->statut == 'PAYÉ')
        <div class="paid-stamp">PAYÉ</div>
        @endif

        <!-- Signature -->
        <div class="signature">
            <div class="signature-box">
                <p>Signature du caissier</p>
                <div style="height: 70px;"></div>
                <div class="signature-line">
                    <p>AprosafeCare</p>
                </div>
            </div>
        </div>

        <!-- QR Code -->
        <img src="/api/placeholder/80/80" alt="QR Code" class="qr-code">

        <!-- Pied de page -->
        <div class="footer">
            <p>Ce reçu est généré électroniquement et est valable sans signature ni cachet.</p>
            <p>Merci de votre confiance!</p>
            <p>Clinique AprosafeCare - Excellence dans les soins de santé - www.aprosafe.ci</p>
        </div>
    </div>
</body>
</html>
