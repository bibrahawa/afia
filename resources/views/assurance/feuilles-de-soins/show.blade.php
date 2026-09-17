<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Feuille de soins — {{ $transaction->invoice_no }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #222; margin: 20px; }
        .entete { display: flex; justify-content: space-between; border-bottom: 2px solid #333; padding-bottom: 8px; margin-bottom: 10px; }
        .entete img { max-height: 55px; }
        h1 { font-size: 16px; margin: 0; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #999; padding: 4px; vertical-align: top; }
        th { background: #eee; text-align: left; }
        .grille td { border: none; padding: 2px 4px; }
        .text-end { text-align: right; } .muted { color: #666; } .fw { font-weight: bold; }
        .signatures { display: flex; justify-content: space-between; gap: 20px; margin-top: 30px; }
        .signatures div { flex: 1; border-top: 1px solid #333; padding-top: 4px; min-height: 60px; }
        @media print { .no-print { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()">Imprimer</button>

    <div class="entete">
        <div>
            @if($identite->logoWeb())<img src="{{ $identite->logoWeb() }}" alt="Logo">@endif
            <div class="fw">{{ $identite->nom }}</div>
            <div>{{ $identite->coordonnees() }}</div>
        </div>
        <div class="text-end">
            <h1>Feuille de soins</h1>
            <div>Facture N° <strong>{{ $transaction->invoice_no }}</strong></div>
            <div>Date des soins : {{ $invoice->created_at->format('d/m/Y') }}</div>
        </div>
    </div>

    @foreach($reclamations as $c)
        @php
            $beneficiaire = $c->patientInsurance?->beneficiaire;
            $contrat = $beneficiaire?->adhesion?->formule?->contrat;
        @endphp
        <table class="grille">
            <tr>
                <td><span class="muted">Organisme :</span> <strong>{{ $c->insuranceCompany->name }}</strong> ({{ $c->claim_number }})</td>
                <td><span class="muted">N° de carte :</span> <strong>{{ $c->patientInsurance?->policy_number }}</strong></td>
            </tr>
            <tr>
                <td><span class="muted">Bénéficiaire :</span> {{ $transaction->patient->full_name }}
                    @if($beneficiaire && $beneficiaire->lien !== \App\Enums\Assurance\LienBeneficiaire::Adherent)({{ mb_strtolower($beneficiaire->lien->libelle()) }})@endif</td>
                <td><span class="muted">Assuré principal :</span> {{ $beneficiaire?->adhesion?->patient?->full_name ?? $transaction->patient->full_name }}
                    @if($contrat?->entreprise) — {{ $contrat->entreprise->nom }}@endif</td>
            </tr>
        </table>
    @endforeach

    @if($bons->isNotEmpty())
        <p>Bon(s) de prise en charge : @foreach($bons as $bon)<strong>{{ $bon->numero }}</strong>@if(! $loop->last), @endif @endforeach</p>
    @endif

    <table>
        <thead>
            <tr><th>Acte / produit</th><th class="text-end">Qté</th><th class="text-end">Prix unitaire</th><th class="text-end">Montant</th>
                @foreach($reclamations as $c)<th class="text-end">Part {{ $c->insuranceCompany->name }}</th>@endforeach
                <th class="text-end">Part patient</th></tr>
        </thead>
        <tbody>
        @foreach($invoice->items as $item)
            @php $parts = collect((array) $item->repartition_assurance)->keyBy('insurance_id'); @endphp
            <tr>
                <td>{{ $item->description }}</td>
                <td class="text-end">{{ $item->quantity }}</td>
                <td class="text-end">{{ number_format((float) $item->unit_price, 0, ',', ' ') }}</td>
                <td class="text-end">{{ number_format((float) $item->total_amount, 0, ',', ' ') }}</td>
                @foreach($reclamations as $c)
                    <td class="text-end">{{ number_format((float) ($parts->get($c->patient_insurance_id)['montant'] ?? 0), 0, ',', ' ') }}</td>
                @endforeach
                <td class="text-end">{{ number_format((float) $item->patient_amount, 0, ',', ' ') }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr class="fw"><td colspan="3" class="text-end">Totaux (GNF)</td>
                <td class="text-end">{{ number_format((float) $invoice->total_amount, 0, ',', ' ') }}</td>
                @foreach($reclamations as $c)<td class="text-end">{{ number_format((float) $c->claimed_amount, 0, ',', ' ') }}</td>@endforeach
                <td class="text-end">{{ number_format((float) $invoice->patient_amount, 0, ',', ' ') }}</td></tr>
        </tfoot>
    </table>

    <p class="muted">Je soussigné(e) certifie avoir reçu les soins et produits ci-dessus et autorise leur facturation à l'organisme indiqué.</p>

    <div class="signatures">
        <div>Signature de l'assuré / du bénéficiaire<br><span class="muted">Nom :</span></div>
        <div>Médecin : {{ $medecin?->full_name ?? '' }}<br>Signature</div>
        <div>Cachet de l'établissement</div>
    </div>
</body>
</html>
