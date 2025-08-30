<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ordonnance Médicale</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', serif;
            background-color: #f5f5f5;
            padding: 20px;
        }

        .prescription {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border-radius: 8px;
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
        }

        .doctor-signature {
            margin-top: 60px;
            text-align: right;
        }

        .signature-line {
            margin-top: 40px;
            font-size: 12px;
        }

        .footer {
            margin-top: 40px;
            font-size: 10px;
            text-align: center;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 20px;
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

        @media print {
            body { 
                background: white; 
                padding: 0; 
            }
            .prescription { 
                box-shadow: none; 
                border-radius: 0; 
                padding: 20px; 
            }
            .editable {
                background: white;
                border: none;
            }
        }
    </style>
</head>
<body>
    <button class="print-button" onclick="printPrescription()">Revenir</button>
    <button class="print-button" onclick="printPrescription()">🖨️ Imprimer</button>

    <div class="prescription">
        <div class="header">
            <div class="logo-section">
                <div class="logo-text">
                    LOGO<br>
                    CLINIQUE
                </div>
            </div>
            <div class="clinic-info">
                <div class="clinic-name">Centre Hospitalier National Dalal Jamm</div>
                <div>Service de Cardiologie Interventionnelle</div>
                <div>Clinique des Maladies du Cœur et des Vaisseaux</div>
                <div>Ministère de la Santé et de l'Action Sociale</div>
                <div>République du Sénégal</div>
            </div>
        </div>

        <div class="date-location">
            Dakar, le <span class="editable">23/08/2021</span>
        </div>

        <div class="patient-info">
            <div class="patient-name">
                Mr/Mme <span class="editable">DOUMBOUYA Adama DIALLO</span>, <span class="editable">26 ans</span>
            </div>
        </div>

        <div class="ordonnance-title">ORDONNANCE</div>

        <div class="prescription-item">
            <div class="item-number">1.</div>
            <div class="medication">
                <div class="medication-name">FUGENYL COMP 500 MG</div>
                <div class="dosage">2 comprimés en prise unique au moment des règles</div>
            </div>
            <div class="quantity">1 BOITE</div>
        </div>

        <div class="prescription-item">
            <div class="item-number">2.</div>
            <div class="medication">
                <div class="medication-name">THEOREM COMP 500 MG</div>
                <div class="dosage">2 comprimés en prise unique au moment des règles</div>
            </div>
            <div class="quantity">1 BOITE</div>
        </div>

        <div class="prescription-item">
            <div class="item-number">3.</div>
            <div class="medication">
                <div class="medication-name">FLUCONAZOLE GENU, 150 MG</div>
                <div class="dosage">1 compri en prise unique au moment des règles</div>
            </div>
            <div class="quantity">1 BOITE</div>
        </div>

        <div class="prescription-item">
            <div class="item-number">4.</div>
            <div class="medication">
                <div class="medication-name">BETROPIACTIO SUPP</div>
                <div class="dosage">1 le PM / 15 heures à débuter la veille de la radiographie</div>
            </div>
            <div class="quantity">1 BOITE</div>
        </div>

        <div class="prescription-item">
            <div class="item-number">5.</div>
            <div class="medication">
                <div class="medication-name">PHOSPHATE SUBI</div>
                <div class="dosage">1 comprimé / 12 heures en sautique à débuter la veille de la radiographie</div>
            </div>
            <div class="quantity">1 BOITE</div>
        </div>

        <div class="doctor-signature">
            <div style="margin-bottom: 60px;">Le Service Pharmacie Hôpital GULSAN GULYE</div>
            <div class="signature-line">
                Dr <span class="editable">Mame Diarra NDIAYE</span>
            </div>
        </div>

        <div class="footer">
            Tél: 00221 33 889 08 89 - Fax: 00221 33 889 30 85 - BP: 3001 - Guédiawaye<br>
            Email: info@hopitaldalaljanm.sn - Site web: www.hopitaldalaljanm.sn
        </div>
    </div>

    <script>
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
            });
        });

        // Fonction pour imprimer
        function printPrescription() {
            window.print();
        }
    </script>
</body>
</html>