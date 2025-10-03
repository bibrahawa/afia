<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture de consultation</title>
    <style>
        @page {
            size: A4;
            margin: 0;
        }

        :root {
            --primary-color: #1a73e8;
            --secondary-color: #4285f4;
            --accent-color: #fbbc05;
            --text-color: #333;
            --light-gray: #f8f9fa;
            --border-color: #e0e0e0;
        }

        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.4;
            color: var(--text-color);
            margin: 0;
            padding: 0;
            font-size: 10pt;
        }

        .container {
            width: 21cm;
            height: 29.7cm;
            padding: 1cm;
            margin: 0 auto;
            background-color: white;
            position: relative;
            box-sizing: border-box;
        }

        .facture-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 0.5rem;
        }

        .clinic-info {
            flex: 2;
        }

        .clinic-logo {
            width: 120px;
            height: auto;
            margin-bottom: 0.3rem;
        }

        .clinic-name {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--primary-color);
            margin: 0 0 0.1rem 0;
        }

        .clinic-details {
            font-size: 0.8rem;
            color: #555;
            line-height: 1.2;
        }

        .clinic-details p {
            margin: 0.1rem 0;
        }

        .facture-title {
            flex: 1;
            text-align: right;
        }

        .facture-title h1 {
            color: var(--primary-color);
            font-size: 1.5rem;
            margin: 0;
        }

        .facture-title .facture-number {
            font-size: 0.9rem;
            color: #777;
            margin-top: 0.3rem;
        }

        .facture-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            gap: 1rem;
        }

        .patient-info, .medecin-info {
            flex: 1;
        }

        .info-box {
            background-color: var(--light-gray);
            border-left: 3px solid var(--primary-color);
            padding: 0.5rem;
            border-radius: 3px;
        }

        .info-box h3 {
            margin: 0 0 0.3rem 0;
            color: var(--primary-color);
            font-size: 0.9rem;
        }

        .info-content p {
            margin: 0.2rem 0;
            font-size: 0.8rem;
        }

        .services-section {
            margin-bottom: 1rem;
        }

        .services-header {
            background-color: var(--primary-color);
            color: white;
            padding: 0.4rem 0.6rem;
            border-radius: 3px 3px 0 0;
            font-size: 0.9rem;
        }

        .services-content {
            border: 1px solid var(--border-color);
            border-top: none;
            padding: 0.5rem;
            border-radius: 0 0 3px 3px;
        }

        .services-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8rem;
        }

        .services-table th, .services-table td {
            padding: 0.4rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .services-table th {
            background-color: #f0f0f0;
            font-weight: 600;
        }

        .totals-section {
            margin-top: 1rem;
            font-size: 0.9rem;
        }

        .total-row {
            display: flex;
            justify-content: flex-end;
            padding: 0.3rem 0;
        }

        .total-row.grand-total {
            font-weight: bold;
            font-size: 1rem;
            border-top: 2px solid var(--primary-color);
            padding-top: 0.5rem;
            margin-top: 0.3rem;
        }

        .total-label {
            width: 150px;
            text-align: right;
            padding-right: 1rem;
        }

        .total-value {
            width: 100px;
            text-align: right;
        }

        .appointment-info {
            margin-top: 1rem;
            padding: 0.5rem;
            background-color: #e8f0fe;
            border-radius: 3px;
            border-left: 3px solid var(--secondary-color);
            font-size: 0.8rem;
        }

        .appointment-info h3 {
            color: var(--secondary-color);
            margin: 0 0 0.3rem 0;
            font-size: 0.9rem;
        }

        .appointment-info p {
            margin: 0.2rem 0;
        }

        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 1.5rem;
        }

        .signature-box {
            flex: 1;
            max-width: 45%;
            border-top: 1px solid var(--border-color);
            padding-top: 0.3rem;
            text-align: center;
            font-size: 0.8rem;
        }

        .signature-box div {
            height: 40px;
        }

        .footer {
            position: absolute;
            bottom: 1cm;
            left: 1cm;
            right: 1cm;
            text-align: center;
            font-size: 0.7rem;
            color: #777;
            padding-top: 0.5rem;
            border-top: 1px solid var(--border-color);
        }

        .footer p {
            margin: 0.1rem 0;
        }

        .qr-code {
            position: absolute;
            bottom: 2.5cm;
            right: 1.5cm;
            text-align: center;
        }

        .qr-code img {
            width: 70px;
            height: 70px;
        }

        .qr-code p {
            margin: 0.2rem 0 0 0;
            font-size: 0.7rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- En-tête de la facture -->
        <div class="facture-header">
            <div class="clinic-info">
                <img src="/api/placeholder/120/50" alt="Logo Clinique" class="clinic-logo">
                <h2 class="clinic-name">Clinique AprosafeCare</h2>
                <div class="clinic-details">
                    <p>123 Avenue de la Santé, Abidjan, Côte d'Ivoire</p>
                    <p>Tél: +225 27 20 XX XX XX | Email: contact@aprosafe.ci</p>
                    <p>RCCM: CI-ABJ-XXXX-XXXX | CC: XXXXXXX</p>
                </div>
            </div>
            <div class="facture-title">
                <h1>FACTURE</h1>
                <div class="facture-number">N° {{ $consultation->id }}</div>
                <div>Date: {{ $consultation->created_at->format('d/m/Y') }}</div>
            </div>
        </div>

        <!-- Informations du patient et du médecin -->
        <div class="facture-info">
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
            <div class="medecin-info">
                <div class="info-box">
                    <h3>Médecin Traitant</h3>
                    <div class="info-content">
                        <p><strong>Dr.</strong> {{ $consultation->medecin->first_name }} {{ $consultation->medecin->last_name }}</p>
                        <p><strong>Spécialité:</strong> {{ $consultation->medecin->specialite ?? 'Médecin généraliste' }}</p>
                        <p><strong>N° Ordre:</strong> {{ $consultation->medecin->numero_ordre ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section des services -->
        <div class="services-section">
            <div class="services-header">
                <h3 style="margin: 0;">Détails des prestations</h3>
            </div>
            <div class="services-content">
                <table class="services-table">
                    <thead>
                        <tr>
                            <th>Désignation</th>
                            <th>Département</th>
                            <th>Détails</th>
                            <th style="text-align: right;">Montant</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Consultation médicale</td>
                            <td>{{ $consultation->department->name }}</td>
                            <td>Consultation standard</td>
                            <td style="text-align: right;">{{ number_format($consultation->total * 0.2, 0, ',', ' ') }} GNF</td>
                        </tr>
                        @foreach ($consultation->services as $service)
                        <tr>
                            <td>Service médical</td>
                            <td>{{ $consultation->department->name }}</td>
                            <td>{{ $service->name }}</td>
                            <td style="text-align: right;">{{ number_format($service->prix ?? ($consultation->total * 0.4 / count($consultation->services)), 0, ',', ' ') }} GNF</td>
                        </tr>
                        @endforeach
                        @foreach ($consultation->packages as $package)
                        <tr>
                            <td>Package</td>
                            <td>{{ $consultation->department->name }}</td>
                            <td>{{ $package->name }}</td>
                            <td style="text-align: right;">{{ number_format($package->prix ?? ($consultation->total * 0.4 / count($consultation->packages)), 0, ',', ' ') }} GNF</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section des totaux -->
        <div class="totals-section">
            <div class="total-row">
                <div class="total-label">Total HT:</div>
                <div class="total-value">{{ number_format($consultation->total * 0.85, 0, ',', ' ') }} GNF</div>
            </div>
            <div class="total-row">
                <div class="total-label">TVA (18%):</div>
                <div class="total-value">{{ number_format($consultation->total * 0.15, 0, ',', ' ') }} GNF</div>
            </div>
            <div class="total-row grand-total">
                <div class="total-label">Total TTC:</div>
                <div class="total-value">{{ number_format($consultation->total, 0, ',', ' ') }} GNF</div>
            </div>
            <div class="total-row">
                <div class="total-label">Mode de paiement:</div>
                <div class="total-value">{{ $consultation->mode_paiement }}</div>
            </div>
            <div class="total-row">
                <div class="total-label">Statut:</div>
                <div class="total-value">{{ $consultation->statut }}</div>
            </div>
        </div>

        <!-- Informations sur le prochain rendez-vous -->
        <div class="appointment-info">
            <h3>Prochain rendez-vous</h3>
            <p><strong>Date:</strong> {{ $consultation->prochain_rdv ? $consultation->prochain_rdv->format('d/m/Y H:i') : 'Non défini' }}</p>
            <p><strong>Médecin:</strong> {{ $consultation->prochainMedecin->first_name ?? 'Non défini' }} {{ $consultation->prochainMedecin->last_name ?? '' }}</p>
        </div>

        <!-- Signatures -->
        <div class="signatures">
            <div class="signature-box">
                <p>Signature du médecin</p>
                <div></div>
                <p>Dr. {{ $consultation->medecin->first_name }} {{ $consultation->medecin->last_name }}</p>
            </div>
            <div class="signature-box">
                <p>Signature du patient</p>
                <div></div>
                <p>{{ $consultation->patient->first_name }} {{ $consultation->patient->last_name }}</p>
            </div>
        </div>

        <!-- QR Code pour la vérification -->
        <div class="qr-code">
            <img src="/api/placeholder/70/70" alt="QR Code">
            <p>Vérification</p>
        </div>

        <!-- Pied de page -->
        <div class="footer">
            <p>Clinique AprosafeCare - Excellence dans les soins de santé</p>
            <p>Cette facture est générée électroniquement et est valable sans signature ni cachet.</p>
            <p>www.aprosafe.ci</p>
        </div>
    </div>
</body>
</html>
