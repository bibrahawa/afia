<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture Médicale</title>
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
            @page {
                size: A4;
                margin: 15mm;
            }
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 15mm;
            background: #f8f9fa;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
        }

        .invoice-container {
            max-width: 180mm;
            margin: 0 auto;
            background: white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20mm 15mm;
            position: relative;
        }

        .header::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            right: 0;
            height: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            clip-path: polygon(0 0, 100% 0, 95% 100%, 5% 100%);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .clinic-info {
            flex: 1;
        }

        .clinic-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 8px;
            text-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }

        .clinic-details {
            font-size: 11px;
            opacity: 0.9;
            line-height: 1.5;
        }

        .invoice-type-section {
            text-align: right;
            background: rgba(255,255,255,0.15);
            padding: 12px 16px;
            border-radius: 8px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        }

        .invoice-type {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .invoice-number {
            font-size: 10px;
            opacity: 0.8;
        }

        .main-content {
            padding: 20mm 15mm;
        }

        .patient-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .info-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .label {
            font-size: 9px;
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .value {
            font-size: 12px;
            font-weight: 500;
            color: #333;
        }

        .services-section {
            margin-bottom: 25px;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #667eea;
        }

        .services-table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .services-table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .services-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            font-size: 11px;
        }

        .services-table tr:nth-child(even) {
            background: #f8f9fa;
        }

        .services-table tr:hover {
            background: #e3f2fd;
        }

        .code-cell {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            color: #667eea;
            text-align: center;
        }

        .price-cell {
            text-align: right;
            font-weight: 600;
        }

        .total-section {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .total-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            min-width: 250px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 11px;
            color: #666;
        }

        .total-row.final {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 2px solid #667eea;
            font-size: 14px;
            font-weight: bold;
            color: #333;
        }

        .total-row.final .amount {
            color: #667eea;
            font-size: 16px;
        }

        .footer {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 15px 20px;
            margin-top: 25px;
            border-radius: 8px;
            border-left: 4px solid #28a745;
        }

        .footer-content {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 10px;
            color: #666;
        }

        .footer-icon {
            width: 16px;
            height: 16px;
            background: #28a745;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 10px;
            font-weight: bold;
            flex-shrink: 0;
        }

        .no-print {
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .no-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 48px;
            color: rgba(102, 126, 234, 0.05);
            font-weight: bold;
            z-index: 0;
            pointer-events: none;
        }

        .main-content {
            position: relative;
            z-index: 1;
        }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()">🖨️ Imprimer</button>

    <div class="invoice-container">
        <div class="watermark">CLINIQUE SANTÉ+</div>

        <div class="header">
            <div class="header-content">
                <div class="clinic-info">
                    <div class="clinic-name">CLINIQUE MÉDICALE SANTÉ+</div>
                    <div class="clinic-details">
                        📍 123 Avenue de la Santé, Abidjan<br>
                        🇨🇮 Côte d'Ivoire<br>
                        📞 +225 XX XX XX XX<br>
                        🏥 Laboratoire agréé
                    </div>
                </div>
                <div class="invoice-type-section">
                    <div class="invoice-type">FACTURE D'EXAMENS</div>
                    <div class="invoice-number">N° EXAM-2024-001</div>
                </div>
            </div>
        </div>

        <div class="main-content">
            <div class="patient-section">
                <div class="info-group">
                    <div class="info-item">
                        <div class="label">Patient</div>
                        <div class="value">M. KOUASSI Jean-Baptiste</div>
                    </div>
                    <div class="info-item">
                        <div class="label">Médecin prescripteur</div>
                        <div class="value">Dr. TRAORÉ Aminata</div>
                    </div>
                </div>
                <div class="info-group">
                    <div class="info-item">
                        <div class="label">Date de prescription</div>
                        <div class="value">23 Juin 2025</div>
                    </div>
                    <div class="info-item">
                        <div class="label">Date d'examen</div>
                        <div class="value">24 Juin 2025</div>
                    </div>
                </div>
            </div>

            <div class="services-section">
                <div class="section-title">DÉTAIL DES EXAMENS</div>
                <table class="services-table">
                    <thead>
                        <tr>
                            <th>Examen médical</th>
                            <th>Code</th>
                            <th>Prix unitaire</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Numération Formule Sanguine</td>
                            <td class="code-cell">NFS</td>
                            <td class="price-cell">8,000 FCFA</td>
                            <td class="price-cell">8,000 FCFA</td>
                        </tr>
                        <tr>
                            <td>Glycémie à jeun</td>
                            <td class="code-cell">GLY</td>
                            <td class="price-cell">3,000 FCFA</td>
                            <td class="price-cell">3,000 FCFA</td>
                        </tr>
                        <tr>
                            <td>Créatinine</td>
                            <td class="code-cell">CREA</td>
                            <td class="price-cell">4,000 FCFA</td>
                            <td class="price-cell">4,000 FCFA</td>
                        </tr>
                        <tr>
                            <td>Transaminases (ALAT/ASAT)</td>
                            <td class="code-cell">TGO/TGP</td>
                            <td class="price-cell">6,000 FCFA</td>
                            <td class="price-cell">6,000 FCFA</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="total-section">
                <div class="total-card">
                    <div class="total-row">
                        <span>Sous-total:</span>
                        <span>21,000 FCFA</span>
                    </div>
                    <div class="total-row">
                        <span>TVA (18%):</span>
                        <span>3,780 FCFA</span>
                    </div>
                    <div class="total-row final">
                        <span>MONTANT TOTAL:</span>
                        <span class="amount">24,780 FCFA</span>
                    </div>
                </div>
            </div>

            <div class="footer">
                <div class="footer-content">
                    <div class="footer-icon">i</div>
                    <div>
                        <strong>Informations importantes:</strong> Résultats disponibles sous 24h • Prélèvement à jeun requis pour certains examens • Merci de conserver cette facture pour vos remboursements
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
