<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails de la réclamation d'assurance #{{ $insuranceClaim->id }}</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow-md">
        <h1 class="text-2xl font-bold mb-6">Détails de la réclamation d'assurance #{{ $insuranceClaim->id }}</h1>

        <div class="mb-4 space-y-2">
            <p><strong class="font-semibold">Numéro de Réclamation:</strong> {{ $insuranceClaim->claim_number }}</p>
            <p><strong class="font-semibold">Facture ID:</strong> {{ $insuranceClaim->invoice_id }}</p>
            <p><strong class="font-semibold">Compagnie d'Assurance:</strong> {{ $insuranceClaim->insuranceCompany->name ?? 'N/A' }}</p>
            <p><strong class="font-semibold">Patient:</strong> {{ $insuranceClaim->patient->name ?? 'N/A' }}</p>
            <p><strong class="font-semibold">Montant Réclamé:</strong> {{ number_format($insuranceClaim->claimed_amount, 2) }}</p>
            <p><strong class="font-semibold">Montant Approuvé:</strong> {{ number_format($insuranceClaim->approved_amount, 2) ?? 'N/A' }}</p>
            <p><strong class="font-semibold">Montant Payé:</strong> {{ number_format($insuranceClaim->paid_amount, 2) }}</p>
            <p><strong class="font-semibold">Statut:</strong> {{ ucfirst($insuranceClaim->status) }}</p>
            <p><strong class="font-semibold">Date de Soumission:</strong> {{ $insuranceClaim->submission_date?->format('d/m/Y') ?? 'N/A' }}</p>
            <p><strong class="font-semibold">Date d'Approbation:</strong> {{ $insuranceClaim->approval_date?->format('d/m/Y') ?? 'N/A' }}</p>
            <p><strong class="font-semibold">Date de Paiement:</strong> {{ $insuranceClaim->payment_date?->format('d/m/Y') ?? 'N/A' }}</p>
            <p><strong class="font-semibold">Raison du Rejet:</strong> {{ $insuranceClaim->rejection_reason ?? 'N/A' }}</p>
            <div>
                <strong class="font-semibold">Documents:</strong>
                @if (!empty($insuranceClaim->documents))
                    <ul class="list-disc list-inside ml-4">
                        @foreach ($insuranceClaim->documents as $doc)
                            <li>{{ $doc }}</li>
                        @endforeach
                    </ul>
                @else
                    N/A
                @endif
            </div>
        </div>

        <div class="mt-6">
            <a href="{{ route('insurance-claims.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">Retour à la liste</a>
            <a href="{{ route('insurance-claims.edit', $insuranceClaim) }}" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded ml-2">Modifier</a>
        </div>
    </div>
</body>
</html>
