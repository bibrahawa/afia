<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ordonnance médicale</title>
    <style>
        @page {
            size: A4;
            margin: 0;
        }

        :root {
            --primary-color: #1e88e5;
            --secondary-color: #42a5f5;
            --accent-color: #bbdefb;
            --text-color: #333;
            --light-gray: #f5f5f5;
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
            box-sizing: border-box;
            position: relative;
        }

        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 5rem;
            color: rgba(200, 200, 200, 0.1);
            z-index: 0;
            white-space: nowrap;
        }

        .ordonnance-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
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

        .doctor-info {
            flex: 1;
            text-align: right;
            border-left: 1px solid var(--border-color);
            padding-left: 0.8rem;
            font-size: 0.8rem;
        }

        .doctor-name {
            font-size: 1rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 0.2rem;
        }

        .doctor-title {
            font-style: italic;
            margin-bottom: 0.3rem;
        }

        .doctor-details {
            font-size: 0.8rem;
            color: #555;
        }

        .doctor-details p {
            margin: 0.1rem 0;
        }

        .ordonnance-title {
            text-align: center;
            margin: 1rem 0;
            position: relative;
            z-index: 1;
        }

        .ordonnance-title h1 {
            color: var(--primary-color);
            font-size: 1.5rem;
            margin: 0;
            padding-bottom: 0.3rem;
            border-bottom: 2px solid var(--secondary-color);
            display: inline-block;
        }

        .patient-info {
            margin-bottom: 1rem;
            padding: 0.6rem;
            background-color: var(--light-gray);
            border-left: 3px solid var(--primary-color);
            border-radius: 3px;
            position: relative;
            z-index: 1;
        }

        .patient-info h3 {
            margin: 0 0 0.3rem 0;
            color: var(--primary-color);
            font-size: 0.9rem;
        }

        .patient-details {
            display: flex;
            flex-wrap: wrap;
        }

        .patient-detail {
            flex: 1 1 50%;
            min-width: 200px;
            margin-bottom: 0.3rem;
            font-size: 0.8rem;
        }

        .medications-section {
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
        }

        .medications-header {
            background-color: var(--primary-color);
            color: white;
            padding: 0.4rem 0.6rem;
            border-radius: 3px 3px 0 0;
            font-size: 0.9rem;
        }

        .medications-content {
            border: 1px solid var(--border-color);
            border-top: none;
            padding: 0.5rem;
            border-radius: 0 0 3px 3px;
        }

        .medication-item {
            padding: 0.6rem;
            margin-bottom: 0.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: flex-start;
        }

        .medication-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .medication-icon {
            flex: 0 0 30px;
            font-size: 1.2rem;
            color: var(--primary-color);
            text-align: center;
        }

        .medication-details {
            flex: 1;
        }

        .medication-name {
            font-weight: bold;
            font-size: 0.9rem;
            margin-bottom: 0.1rem;
        }

        .medication-instructions {
            color: #555;
            font-size: 0.8rem;
        }

        .medication-instructions p {
            margin: 0.1rem 0;
        }

        .prescription-notes {
            margin-top: 1rem;
            padding: 0.6rem;
            background-color: var(--accent-color);
            border-radius: 3px;
            position: relative;
            z-index: 1;
            font-size: 0.8rem;
        }

        .prescription-notes h3 {
            margin: 0 0 0.3rem 0;
            font-size: 0.9rem;
        }

        .signature {
            margin-top: 2rem;
            text-align: right;
            position: relative;
            z-index: 1;
        }

        .signature p {
            margin: 0 0 0.2rem 0;
            font-size: 0.8rem;
        }

        .signature-line {
            width: 150px;
            border-top: 1px solid var(--border-color);
            margin-left: auto;
            padding-top: 0.3rem;
            font-size: 0.8rem;
        }

        .footer {
            position: absolute;
            bottom: 1cm;
            left: 1cm;
            right: 1cm;
            padding-top: 0.5rem;
            border-top: 1px solid var(--border-color);
            font-size: 0.7rem;
            color: #777;
            text-align: center;
            z-index: 1;
        }

        .footer p {
            margin: 0.1rem 0;
        }

        .date-section {
            text-align: right;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
            font-size: 0.8rem;
        }

        .date-section p {
            margin: 0;
        }

        .caduceus {
            position: absolute;
            bottom: 2cm;
            right: 2cm;
            opacity: 0.07;
            width: 80px;
            height: auto;
            z-index: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="watermark">ORDONNANCE</div>

        <!-- En-tête de l'ordonnance -->
        <div class="ordonnance-header">
            <div class="clinic-info">
                <img src="/api/placeholder/120/50" alt="Logo Clinique" class="clinic-logo">
                <h2 class="clinic-name">Clinique AprosafeCare</h2>
                <div class="clinic-details">
                    <p>123 Avenue de la Santé, Abidjan, Côte d'Ivoire</p>
                    <p>Tél: +225 27 20 XX XX XX | Email: contact@aprosafe.ci</p>
                </div>
            </div>
            <div class="doctor-info">
                <div class="doctor-name">Dr. {{ $consultation->medecin->first_name }} {{ $consultation->medecin->last_name }}</div>
                <div class="doctor-title">{{ $consultation->medecin->specialite ?? 'Médecin généraliste' }}</div>
                <div class="doctor-details">
                    <p>N° Ordre: {{ $consultation->medecin->numero_ordre ?? 'XXXXX' }}</p>
                    <p>Email: {{ $consultation->medecin->email ?? 'docteur@aprosafe.ci' }}</p>
                </div>
            </div>
        </div>

        <!-- Date de l'ordonnance -->
        <div class="date-section">
            <p>Abidjan, le {{ $consultation->created_at->format('d/m/Y') }}</p>
        </div>

        <!-- Titre de l'ordonnance -->
        <div class="ordonnance-title">
            <h1>ORDONNANCE MÉDICALE</h1>
        </div>

        <!-- Informations du patient -->
        <div class="patient-info">
            <h3>Patient</h3>
            <div class="patient-details">
                <div class="patient-detail">
                    <strong>Nom & Prénom:</strong> {{ $consultation->patient->first_name }} {{ $consultation->patient->last_name }}
                </div>
                <div class="patient-detail">
                    <strong>Âge:</strong> {{ $consultation->patient->age ?? 'Non spécifié' }} ans
                </div>
                <div class="patient-detail">
                    <strong>Sexe:</strong> {{ $consultation->patient->sexe ?? 'Non spécifié' }}
                </div>
                <div class="patient-detail">
                    <strong>Poids:</strong> {{ $consultation->patient->poids ?? 'Non spécifié' }} kg
                </div>
            </div>
        </div>

        <!-- Section des médicaments -->
        <div class="medications-section">
            <div class="medications-header">
                <h3 style="margin: 0;">Prescription médicamenteuse</h3>
            </div>
            <div class="medications-content">
                @foreach($consultation->medicaments as $med)
                <div class="medication-item">
                    <div class="medication-icon">℞</div>
                    <div class="medication-details">
                        <div class="medication-name">{{ $med->nom }}</div>
                        <div class="medication-instructions">
                            <p><strong>Posologie:</strong> {{ $med->frequence }}</p>
                            <p><strong>Durée:</strong> {{ $med->duree }}</p>
                            @if(isset($med->instructions))
                            <p><strong>Instructions:</strong> {{ $med->instructions }}</p>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach

                @if(count($consultation->medicaments) == 0)
                <div class="medication-item">
                    <div class="medication-icon">ℹ️</div>
                    <div class="medication-details">
                        <div class="medication-name">Aucun médicament prescrit</div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Notes supplémentaires -->
        <div class="prescription-notes">
            <h3>Recommandations & conseils</h3>
            <p>{{ $consultation->recommandations ?? 'Suivre le traitement tel que prescrit. Prendre rendez-vous pour une visite de suivi si nécessaire.' }}</p>
        </div>

        <!-- Signature -->
        <div class="signature">
            <p>Signature du médecin</p>
            <div style="height: 50px;"></div>
            <div class="signature-line">
                <p>Dr. {{ $consultation->medecin->first_name }} {{ $consultation->medecin->last_name }}</p>
            </div>
        </div>

        <!-- Caducée en filigrane -->
        <img src="/api/placeholder/80/80" alt="Caducée" class="caduceus">

        <!-- Pied de page -->
        <div class="footer">
            <p>Cette ordonnance est valable pour une durée de 3 mois à compter de la date d'émission.</p>
            <p>Clinique AprosafeCare - Excellence dans les soins de santé - www.aprosafe.ci</p>
        </div>
    </div>
</body>
</html>
