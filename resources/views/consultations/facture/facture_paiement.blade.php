<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factures Médicales</title>
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 10mm;
            background: white;
            font-size: 10px;
            line-height: 1.2;
        }

        .page {
            width: 190mm;
            height: 277mm;
            margin: 0 auto;
            background: white;
            display: flex;
            flex-direction: column;
        }

        .invoice {
            flex: 1;
            border: 1px solid #ccc;
            margin-bottom: 2mm;
            padding: 3mm;
            position: relative;
        }

        .invoice:last-child {
            margin-bottom: 0;
        }

        .cut-line {
            position: absolute;
            bottom: -1mm;
            left: 0;
            right: 0;
            height: 1px;
            background: repeating-linear-gradient(
                to right,
                #666 0,
                #666 2mm,
                transparent 2mm,
                transparent 4mm
            );
        }

        .invoice:last-child .cut-line {
            display: none;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 3mm;
            padding-bottom: 2mm;
            border-bottom: 1px solid #eee;
        }

        .logo-section {
            flex: 1;
        }

        .clinic-name {
            font-size: 12px;
            font-weight: bold;
            color: #2c5aa0;
            margin-bottom: 1mm;
        }

        .clinic-info {
            font-size: 8px;
            color: #666;
            line-height: 1.3;
        }

        .invoice-type {
            font-size: 11px;
            font-weight: bold;
            color: #d32f2f;
            text-align: right;
            background: #fff3e0;
            padding: 2mm;
            border-radius: 3px;
            border: 1px solid #ffb74d;
        }

        .invoice-number {
            font-size: 8px;
            color: #666;
            margin-top: 1mm;
        }

        .patient-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3mm;
        }

        .patient-details, .date-details {
            flex: 1;
        }

        .patient-details {
            margin-right: 5mm;
        }

        .label {
            font-weight: bold;
            font-size: 8px;
            color: #444;
            margin-bottom: 0.5mm;
        }

        .value {
            font-size: 9px;
            color: #000;
            margin-bottom: 1.5mm;
        }

        .services {
            margin-bottom: 3mm;
        }

        .services-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }

        .services-table th {
            background: #f5f5f5;
            padding: 1.5mm;
            text-align: left;
            border: 1px solid #ddd;
            font-weight: bold;
        }

        .services-table td {
            padding: 1.5mm;
            border: 1px solid #ddd;
            vertical-align: top;
        }

        .services-table tr:nth-child(even) {
            background: #fafafa;
        }

        .total-section {
            display: flex;
            justify-content: flex-end;
            margin-top: 2mm;
        }

        .total-box {
            background: #e3f2fd;
            padding: 2mm;
            border-radius: 3px;
            border: 1px solid #2196f3;
            min-width: 40mm;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1mm;
            font-size: 9px;
        }

        .total-row:last-child {
            margin-bottom: 0;
            font-weight: bold;
            font-size: 10px;
            color: #1976d2;
            border-top: 1px solid #2196f3;
            padding-top: 1mm;
        }

        .footer {
            margin-top: auto;
            font-size: 7px;
            color: #666;
            text-align: center;
            padding-top: 1mm;
            border-top: 1px solid #eee;
        }

        .consultation { border-left: 4px solid #4caf50; }
        .prescription { border-left: 4px solid #ff9800; }
        .exams { border-left: 4px solid #2196f3; }
        .payment { border-left: 4px solid #9c27b0; }

        .no-print {
            position: fixed;
            top: 10px;
            right: 10px;
            background: #2196f3;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
        }

        .no-print:hover {
            background: #1976d2;
        }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()">Imprimer</button>

    <div class="page">
        <!-- FACTURE DE PAIEMENT -->
        <div class="invoice payment">
            <div class="header">
                <div class="logo-section">
                    <div class="clinic-name">CLINIQUE MÉDICALE SANTÉ+</div>
                    <div class="clinic-info">
                        123 Avenue de la Santé<br>
                        Abidjan, Côte d'Ivoire<br>
                        Tél: +225 XX XX XX XX<br>
                        Compte: CI-XXX-XXXX-XXXX
                    </div>
                </div>
                <div class="invoice-type">
                    REÇU DE PAIEMENT
                    <div class="invoice-number">N° PAIE-2024-001</div>
                </div>
            </div>

            <div class="patient-info">
                <div class="patient-details">
                    <div class="label">PAYEUR:</div>
                    <div class="value">M. KOUASSI Jean-Baptiste</div>
                    <div class="label">TÉLÉPHONE:</div>
                    <div class="value">+225 XX XX XX XX</div>
                </div>
                <div class="date-details">
                    <div class="label">DATE PAIEMENT:</div>
                    <div class="value">24 Juin 2025</div>
                    <div class="label">MODE PAIEMENT:</div>
                    <div class="value">Espèces</div>
                    <div class="label">REÇU PAR:</div>
                    <div class="value">Mme KONE Fatou</div>
                </div>
            </div>

            <div class="services">
                <table class="services-table">
                    <thead>
                        <tr>
                            <th>DESCRIPTION</th>
                            <th>FACTURE N°</th>
                            <th>MONTANT</th>
                            <th>STATUT</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Consultation</td>
                            <td>CONS-2024-001</td>
                            <td>23,600 FCFA</td>
                            <td>PAYÉ</td>
                        </tr>
                        <tr>
                            <td>Examens laboratoire</td>
                            <td>EXAM-2024-001</td>
                            <td>24,780 FCFA</td>
                            <td>PAYÉ</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="total-section">
                <div class="total-box">
                    <div class="total-row">
                        <span>Montant reçu:</span>
                        <span>48,380 FCFA</span>
                    </div>
                    <div class="total-row">
                        <span>Monnaie rendue:</span>
                        <span>0 FCFA</span>
                    </div>
                    <div class="total-row">
                        <span>TOTAL PAYÉ:</span>
                        <span>48,380 FCFA</span>
                    </div>
                </div>
            </div>

            <div class="footer">
                Paiement effectué intégralement • Merci de votre confiance • Conservez ce reçu
            </div>
        </div>
    </div>
</body>
</html>
