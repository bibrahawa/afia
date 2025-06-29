<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factures Médicales</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Variables CSS pour faciliter la personnalisation */
        :root {
            --primary-color: #2c5aa0;
            --secondary-color: #d32f2f;
            --accent-color: #4caf50;
            --text-color: #333;
            --border-color: #ddd;
            --background-light: #f9f9f9;
        }

        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
            }

            body {
                margin: 0 !important;
                padding: 0 !important;
                background: white !important;
            }

            .page {
                margin: 0 !important;
                padding: 10mm !important;
                box-shadow: none !important;
                border: none !important;
                page-break-after: always;
                height: 297mm !important;
                display: flex !important;
                flex-direction: column !important;
            }

            .no-print {
                display: none !important;
            }

            .invoice {
                page-break-inside: avoid;
                margin-bottom: 0 !important;
            }

            .cut-line {
                display: none !important;
            }
        }

        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            background: #f5f5f5;
            color: var(--text-color);
            line-height: 1.4;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .page {
            width: 210mm;
            height: 297mm;
            background: white;
            margin: 0 auto 20px;
            padding: 15mm;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 8px;
            position: relative;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .invoice {
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 15px;
            position: relative;
            background: white;
            margin-bottom: 0;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .invoice:last-child {
            margin-bottom: 0;
        }

        .cut-line {
            position: absolute;
            bottom: -10px;
            left: 20px;
            right: 20px;
            height: 2px;
            background: repeating-linear-gradient(
                to right,
                #999 0,
                #999 8px,
                transparent 8px,
                transparent 16px
            );
            border-radius: 1px;
        }

        .invoice:last-child .cut-line {
            display: none;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
            flex-wrap: wrap;
            gap: 10px;
            flex-shrink: 0;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
            min-width: 200px;
        }

        .logo-placeholder {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, var(--primary-color), #4a90e2);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 16px;
            flex-shrink: 0;
        }

        .clinic-details {
            flex: 1;
        }

        .clinic-name {
            font-size: 14px;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 5px;
            letter-spacing: 0.3px;
        }

        .clinic-info {
            font-size: 9px;
            color: #666;
            line-height: 1.3;
        }

        .invoice-type {
            background: linear-gradient(135deg, var(--secondary-color), #f44336);
            color: white;
            padding: 10px 15px;
            border-radius: 6px;
            text-align: center;
            box-shadow: 0 2px 6px rgba(211, 47, 47, 0.2);
            min-width: 150px;
        }

        .invoice-type-title {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .invoice-number {
            font-size: 10px;
            opacity: 0.9;
        }

        .patient-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
            padding: 12px;
            background: var(--background-light);
            border-radius: 6px;
            border: 1px solid #e0e0e0;
            flex-shrink: 0;
        }

        .patient-details, .date-details {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .info-group {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .label {
            font-weight: bold;
            font-size: 9px;
            color: var(--primary-color);
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .value {
            font-size: 11px;
            color: var(--text-color);
            font-weight: 500;
        }

        .services {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
            margin-bottom: 15px;
        }

        .services-title {
            font-size: 12px;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 8px;
            padding-bottom: 5px;
            border-bottom: 2px solid var(--accent-color);
            flex-shrink: 0;
        }

        .table-container {
            flex: 1;
            overflow: auto;
            min-height: 0;
        }

        .services-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 1px 4px rgba(0,0,0,0.1);
        }

        .services-table th {
            background: linear-gradient(135deg, var(--primary-color), #4a90e2);
            color: white;
            padding: 8px 6px;
            text-align: left;
            font-weight: bold;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .services-table td {
            padding: 6px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
            font-size: 9px;
        }

        .services-table tr:nth-child(even) {
            background: #fafafa;
        }

        .services-table tr:hover {
            background: #f0f8ff;
        }

        .price-cell {
            text-align: right;
            font-weight: bold;
            color: var(--primary-color);
        }

        .total-section {
            display: flex;
            justify-content: flex-end;
            margin-top: 15px;
            flex-shrink: 0;
        }

        .total-box {
            background: linear-gradient(135deg, #e3f2fd, #f1f8e9);
            border: 2px solid var(--primary-color);
            border-radius: 6px;
            padding: 12px;
            min-width: 180px;
            box-shadow: 0 2px 8px rgba(44, 90, 160, 0.1);
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
            font-size: 10px;
            padding: 3px 0;
        }

        .total-row:last-child {
            margin-bottom: 0;
            font-weight: bold;
            font-size: 12px;
            color: var(--primary-color);
            border-top: 2px solid var(--primary-color);
            padding-top: 8px;
            margin-top: 6px;
        }

        .footer {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #eee;
            font-size: 8px;
            color: #666;
            text-align: center;
            font-style: italic;
            flex-shrink: 0;
        }

        /* Couleurs de bordure selon le type */
        .consultation { border-left: 6px solid #4caf50; }
        .prescription { border-left: 6px solid #ff9800; }
        .exams { border-left: 6px solid #2196f3; }
        .payment { border-left: 6px solid #9c27b0; }

        .no-print {
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, var(--primary-color), #4a90e2);
            color: white;
            padding: 15px 25px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            box-shadow: 0 4px 15px rgba(44, 90, 160, 0.3);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .no-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(44, 90, 160, 0.4);
        }

        /* Responsive Design */
        @media screen and (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .page {
                width: 100%;
                padding: 15px;
                margin-bottom: 15px;
            }

            .header {
                flex-direction: column;
                text-align: center;
            }

            .logo-section {
                justify-content: center;
                min-width: auto;
            }

            .patient-info {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .services-table {
                font-size: 10px;
            }

            .services-table th,
            .services-table td {
                padding: 10px 8px;
            }

            .total-box {
                min-width: auto;
                width: 100%;
            }

            .no-print {
                position: static;
                margin: 20px auto;
                display: block;
                width: fit-content;
            }
        }

        @media screen and (max-width: 480px) {
            .clinic-name {
                font-size: 16px;
            }

            .invoice-type-title {
                font-size: 14px;
            }

            .services-table {
                font-size: 9px;
            }

            .services-table th,
            .services-table td {
                padding: 8px 5px;
            }
        }

        /* Animation d'entrée */
        .invoice {
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <button class="no-print" onclick="window.print()">🖨️ Imprimer</button>

        <div class="page">
            <!-- FACTURE D'EXAMENS -->
            <div class="invoice exams">
                <div class="cut-line"></div>

                <div class="header">
                    <div class="logo-section">
                        <div class="logo-placeholder">
                            <!-- Remplacez par <img src="votre-logo.png" alt="Logo" style="width: 60px; height: 60px; object-fit: contain;"> -->
                            S+
                        </div>
                        <div class="clinic-details">
                            <div class="clinic-name">CLINIQUE MÉDICALE SANTÉ+</div>
                            <div class="clinic-info">
                                123 Avenue de la Santé<br>
                                Abidjan, Côte d'Ivoire<br>
                                Tél: +225 XX XX XX XX<br>
                                Email: contact@sante-plus.ci<br>
                                Laboratoire agréé • Ouvert 24h/24
                            </div>
                        </div>
                    </div>

                    <div class="invoice-type">
                        <div class="invoice-type-title">FACTURE D'EXAMENS</div>
                        <div class="invoice-number">N° EXAM-2024-001</div>
                    </div>
                </div>

                <div class="patient-info">
                    <div class="patient-details">
                        <div class="info-group">
                            <div class="label">Patient</div>
                            <div class="value">M. KOUASSI Jean-Baptiste</div>
                        </div>
                        <div class="info-group">
                            <div class="label">Prescripteur</div>
                            <div class="value">Dr. TRAORÉ Aminata</div>
                        </div>
                        <div class="info-group">
                            <div class="label">Âge</div>
                            <div class="value">45 ans</div>
                        </div>
                    </div>
                    <div class="date-details">
                        <div class="info-group">
                            <div class="label">Date prescription</div>
                            <div class="value">23 Juin 2025</div>
                        </div>
                        <div class="info-group">
                            <div class="label">Date examen</div>
                            <div class="value">24 Juin 2025</div>
                        </div>
                        <div class="info-group">
                            <div class="label">Technicien</div>
                            <div class="value">Mme DIALLO Fatou</div>
                        </div>
                    </div>
                </div>

                <div class="services">
                    <div class="services-title">Détail des Examens</div>
                    <div class="table-container">
                        <table class="services-table">
                            <thead>
                                <tr>
                                    <th>Examen médical</th>
                                    <th>Code</th>
                                    <th>Prix</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Numération Formule Sanguine complète</td>
                                    <td>NFS</td>
                                    <td class="price-cell">8,000</td>
                                    <td class="price-cell">8,000</td>
                                </tr>
                                <tr>
                                    <td>Glycémie à jeun</td>
                                    <td>GLY</td>
                                    <td class="price-cell">3,000</td>
                                    <td class="price-cell">3,000</td>
                                </tr>
                                <tr>
                                    <td>Créatinine sérique</td>
                                    <td>CREA</td>
                                    <td class="price-cell">4,000</td>
                                    <td class="price-cell">4,000</td>
                                </tr>
                                <tr>
                                    <td>Transaminases (ALAT/ASAT)</td>
                                    <td>TGO/TGP</td>
                                    <td class="price-cell">6,000</td>
                                    <td class="price-cell">6,000</td>
                                </tr>
                                <tr>
                                    <td>Cholestérol total + HDL/LDL</td>
                                    <td>CHOL</td>
                                    <td class="price-cell">5,500</td>
                                    <td class="price-cell">5,500</td>
                                </tr>
                                <tr>
                                    <td>Urée sanguine</td>
                                    <td>UREE</td>
                                    <td class="price-cell">3,500</td>
                                    <td class="price-cell">3,500</td>
                                </tr>
                                <tr>
                                    <td>Acide urique</td>
                                    <td>AU</td>
                                    <td class="price-cell">4,500</td>
                                    <td class="price-cell">4,500</td>
                                </tr>
                                <tr>
                                    <td>Protéines totales</td>
                                    <td>PROT</td>
                                    <td class="price-cell">3,800</td>
                                    <td class="price-cell">3,800</td>
                                </tr>
                                <tr>
                                    <td>Albumine</td>
                                    <td>ALB</td>
                                    <td class="price-cell">4,200</td>
                                    <td class="price-cell">4,200</td>
                                </tr>
                                <tr>
                                    <td>Bilirubine totale/directe</td>
                                    <td>BIL</td>
                                    <td class="price-cell">5,000</td>
                                    <td class="price-cell">5,000</td>
                                </tr>
                                <tr>
                                    <td>Phosphatases alcalines</td>
                                    <td>PAL</td>
                                    <td class="price-cell">4,800</td>
                                    <td class="price-cell">4,800</td>
                                </tr>
                                <tr>
                                    <td>Gamma GT</td>
                                    <td>GGT</td>
                                    <td class="price-cell">4,500</td>
                                    <td class="price-cell">4,500</td>
                                </tr>
                                <tr>
                                    <td>CRP (Protéine C-réactive)</td>
                                    <td>CRP</td>
                                    <td class="price-cell">6,500</td>
                                    <td class="price-cell">6,500</td>
                                </tr>
                                <tr>
                                    <td>VS (Vitesse de sédimentation)</td>
                                    <td>VS</td>
                                    <td class="price-cell">2,500</td>
                                    <td class="price-cell">2,500</td>
                                </tr>
                                <tr>
                                    <td>Ferritine</td>
                                    <td>FER</td>
                                    <td class="price-cell">7,000</td>
                                    <td class="price-cell">7,000</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="total-section">
                    <div class="total-box">
                        <div class="total-row">
                            <span>Sous-total:</span>
                            <span>82,300 FCFA</span>
                        </div>
                        <div class="total-row">
                            <span>TVA (18%):</span>
                            <span>14,814 FCFA</span>
                        </div>
                        <div class="total-row">
                            <span>Remise:</span>
                            <span>-2,000 FCFA</span>
                        </div>
                        <div class="total-row">
                            <span>TOTAL:</span>
                            <span>95,114 FCFA</span>
                        </div>
                    </div>
                </div>

                <div class="footer">
                    <strong>Informations importantes:</strong><br>
                    Résultats disponibles sous 24-48h • Prélèvement à jeun requis pour certains examens<br>
                    Facture payable à réception • Merci de votre confiance - CLINIQUE MÉDICALE SANTÉ+
                </div>
            </div>
        </div>
    </div>
</body>
</html>
