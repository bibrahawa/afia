<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ordonnance d'Examen</title>
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
            font-family: 'Segoe UI', 'Arial', sans-serif;
            margin: 0;
            padding: 15mm;
            background: #f8f9fa;
            font-size: 11px;
            line-height: 1.5;
            color: #333;
        }

        .prescription-form {
            max-width: 180mm;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            overflow: hidden;
            position: relative;
        }

        .prescription-form::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #e74c3c, #3498db, #2ecc71, #f39c12);
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            position: relative;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .clinic-info {
            flex: 1;
            padding-right: 20px;
        }

        .clinic-name {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 12px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .clinic-details {
            font-size: 11px;
            line-height: 1.6;
            opacity: 0.95;
        }

        .clinic-details strong {
            color: #fff;
            font-weight: 600;
        }

        .patient-section {
            background: rgba(255,255,255,0.15);
            padding: 15px;
            border-radius: 8px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
            min-width: 280px;
        }

        .logo-container {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .logo-placeholder {
            width: 45px;
            height: 45px;
            background: rgba(255,255,255,0.2);
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
        }

        .logo-text {
            color: white;
            font-size: 20px;
            font-weight: bold;
        }

        .logo-title {
            color: white;
            font-size: 12px;
            font-weight: 600;
            opacity: 0.9;
        }

        .patient-field {
            margin-bottom: 12px;
            font-size: 11px;
        }

        .patient-field label {
            font-weight: 600;
            color: white;
            display: inline-block;
            min-width: 70px;
        }

        .input-line {
            border-bottom: 1px solid rgba(255,255,255,0.4);
            display: inline-block;
            min-width: 120px;
            height: 20px;
            margin-left: 8px;
            position: relative;
        }

        .main-content {
            padding: 25px;
        }

        .main-title {
            text-align: center;
            font-size: 22px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 30px;
            position: relative;
            padding-bottom: 10px;
        }

        .main-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            border-radius: 2px;
        }

        .section {
            margin-bottom: 25px;
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            border-left: 4px solid #667eea;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }

        .section-title::before {
            content: '🔬';
            margin-right: 8px;
            font-size: 16px;
        }

        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            font-size: 12px;
            padding: 8px 12px;
            background: white;
            border-radius: 6px;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .checkbox-item:hover {
            background: #e3f2fd;
            border-color: #667eea;
            transform: translateY(-1px);
        }

        .checkbox {
            width: 16px;
            height: 16px;
            border: 2px solid #667eea;
            margin-right: 12px;
            display: inline-block;
            position: relative;
            border-radius: 3px;
            transition: all 0.3s ease;
        }

        .checkbox::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0);
            width: 6px;
            height: 6px;
            background: #667eea;
            border-radius: 1px;
            transition: transform 0.2s ease;
        }

        .checkbox-item:hover .checkbox::after {
            transform: translate(-50%, -50%) scale(1);
        }

        .timing-instruction {
            background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
            border: 1px solid #ffb74d;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 12px;
            font-weight: 600;
            color: #e65100;
            margin: 20px 0 15px 0;
            display: flex;
            align-items: center;
        }

        .timing-instruction::before {
            content: '⏰';
            margin-right: 10px;
            font-size: 16px;
        }

        .clinical-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            border-left: 4px solid #28a745;
            margin-top: 25px;
        }

        .clinical-info .section-title::before {
            content: '📝';
        }

        .clinical-lines {
            margin: 15px 0;
        }

        .input-full-line {
            border-bottom: 1px solid #dee2e6;
            height: 25px;
            margin-bottom: 12px;
            position: relative;
            background: white;
            border-radius: 4px;
            border: 1px solid #e9ecef;
        }

        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .date-signature {
            text-align: left;
        }

        .doctor-signature {
            text-align: right;
        }

        .signature-label {
            font-size: 12px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
        }

        .signature-box {
            width: 120px;
            height: 60px;
            border: 1px dashed #667eea;
            border-radius: 4px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #667eea;
            font-size: 10px;
            text-align: center;
        }

        .footer-note {
            text-align: center;
            font-size: 11px;
            color: #666;
            margin-top: 30px;
            padding: 15px;
            background: linear-gradient(135deg, #e8f5e8 0%, #f0f8f0 100%);
            border-radius: 8px;
            border: 1px solid #c8e6c9;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .footer-note::before {
            content: '💡';
            margin-right: 8px;
            font-size: 14px;
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

        .urgency-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #dc3545;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: bold;
            display: none;
        }

        .qr-code {
            position: absolute;
            bottom: 15px;
            right: 15px;
            width: 40px;
            height: 40px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            color: #666;
        }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()">🖨️ Imprimer</button>

    <div class="prescription-form">
        <div class="urgency-badge">URGENT</div>

        <div class="header">
            <div class="header-content">
                <div class="clinic-info">
                    <div class="clinic-name">🏥 CLINIQUE APROSAFE</div>
                    <div class="clinic-details">
                        <strong>Spécialités:</strong> Gynécologie • Obstétrique • Échographie<br>
                        <strong>Services:</strong> Suivi de grossesse • Planification familiale • Infertilité<br>
                        <strong>📍 Adresse:</strong> Yembé Tabo - Aéroport, Conakry<br>
                        <strong>📞 Tél:</strong> (+224) 628 16 44 22 / 661 31 30 30<br>
                        <strong>✉️ Email:</strong> boubacarbinta2015@gmail.com<br>
                        <strong>🌐 Web:</strong> clinique-aprosafe.com
                    </div>
                </div>
                <div class="patient-section">
                    <div class="logo-container">
                        <div class="logo-placeholder">
                            <div class="logo-text">♀</div>
                        </div>
                        <div class="logo-title">Centre Médical<br>Spécialisé</div>
                    </div>
                    <div class="patient-field">
                        <label>📅 Date:</label>
                        <span class="input-line"></span> / <span class="input-line"></span> / <span class="input-line"></span>
                    </div>
                    <div class="patient-field">
                        <label>👤 Nom:</label>
                        <span class="input-line"></span>
                    </div>
                    <div class="patient-field">
                        <label>Prénoms:</label>
                        <span class="input-line"></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="main-content">
            <div class="main-title">ORDONNANCE D'EXAMENS MÉDICAUX</div>

            <div class="section">
                <div class="section-title">BILAN HORMONAL COMPLET</div>
                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <span class="checkbox"></span>
                        <strong>AMH</strong> (Hormone Anti-Müllérienne)
                    </div>
                    <div class="checkbox-item">
                        <span class="checkbox"></span>
                        <strong>PROLACTINE</strong> (Hormone lactotrope)
                    </div>
                </div>
            </div>

            <div class="timing-instruction">
                EXAMENS À RÉALISER ENTRE LE 3ᵉ ET LE 5ᵉ JOUR DU CYCLE MENSTRUEL
            </div>

            <div class="section">
                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <span class="checkbox"></span>
                        <strong>FSH</strong> (Hormone folliculo-stimulante)
                    </div>
                    <div class="checkbox-item">
                        <span class="checkbox"></span>
                        <strong>E2</strong> (Œstradiol)
                    </div>
                    <div class="checkbox-item">
                        <span class="checkbox"></span>
                        <strong>LH</strong> (Hormone lutéinisante)
                    </div>
                    <div class="checkbox-item">
                        <span class="checkbox"></span>
                        <strong>TSH</strong> (Thyréostimuline)
                    </div>
                </div>
            </div>

            <div class="timing-instruction">
                EXAMEN À RÉALISER LE 22ᵉ JOUR DU CYCLE MENSTRUEL
            </div>

            <div class="section">
                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <span class="checkbox"></span>
                        <strong>PROGESTÉRONE</strong> (Phase lutéale)
                    </div>
                </div>
            </div>

            <div class="section">
                <div class="section-title">EXAMENS ÉCHOGRAPHIQUES</div>
                <div class="timing-instruction">
                    À RÉALISER ENTRE LE 3ᵉ ET LE 5ᵉ JOUR DU CYCLE MENSTRUEL
                </div>
                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <span class="checkbox"></span>
                        <strong>ÉCHOGRAPHIE CFA</strong> (Compte des Follicules Antraux)
                    </div>
                </div>
            </div>

            <div class="clinical-info">
                <div class="section-title">RENSEIGNEMENTS CLINIQUES</div>
                <div class="clinical-lines">
                    <div class="input-full-line"></div>
                    <div class="input-full-line"></div>
                    <div class="input-full-line"></div>
                    <div class="input-full-line"></div>
                </div>
            </div>

            <div class="signature-section">
                <div class="date-signature">
                    <div class="signature-label">📅 Date et cachet</div>
                    <div class="signature-box">Date<br>Cachet médical</div>
                </div>
                <div class="doctor-signature">
                    <div class="signature-label">👨‍⚕️ Signature du Médecin</div>
                    <div class="signature-box">Dr. Signature<br>& Cachet</div>
                </div>
            </div>

            <div class="footer-note">
                Merci de conserver cette ordonnance et de la présenter lors de la prochaine consultation
            </div>
        </div>

        <div class="qr-code">
            QR<br>Code
        </div>
    </div>
</body>
</html>
