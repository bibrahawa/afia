<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Réclamations d'Assurance</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-7xl mx-auto bg-white p-6 rounded-lg shadow-md">
        <h1 class="text-2xl font-bold mb-6">Liste des Réclamations d'Assurance</h1>

        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                {{ session('success') }}
            </div> 
        @endif

        <div class="mb-4">
            <a href="{{ route('insurance-claims.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Créer une nouvelle réclamation</a>
        </div>

        @if ($claims->isEmpty())
            <p>Aucune réclamation d'assurance trouvée.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200">
                    <thead>
                        <tr>
                            <th class="py-2 px-4 border-b">Numéro Réclamation</th>
                            <th class="py-2 px-4 border-b">Facture ID</th>
                            <th class="py-2 px-4 border-b">Compagnie Assurance</th>
                            <th class="py-2 px-4 border-b">Patient</th>
                            <th class="py-2 px-4 border-b">Montant Réclamé</th>
                            <th class="py-2 px-4 border-b">Statut</th>
                            <th class="py-2 px-4 border-b">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($claims as $claim)
                            <tr>
                                <td class="py-2 px-4 border-b text-center">{{ $claim->claim_number }}</td>
                                <td class="py-2 px-4 border-b text-center">{{ $claim->invoice_id }}</td>
                                <td class="py-2 px-4 border-b text-center">{{ $claim->insuranceCompany->name ?? 'N/A' }}</td>
                                <td class="py-2 px-4 border-b text-center">{{ $claim->patient->getFullNameAttribute() ?? 'N/A' }}</td>
                                <td class="py-2 px-4 border-b text-center">{{ number_format($claim->claimed_amount, 2) }}</td>
                                <td class="py-2 px-4 border-b text-center">{{ ucfirst($claim->status) }}</td>
                                <td class="py-2 px-4 border-b text-center">
                                    <a href="{{ route('insurance-claims.show', $claim) }}" class="text-blue-600 hover:text-blue-900 mr-2">Voir</a>
                                    <a href="{{ route('insurance-claims.edit', $claim) }}" class="text-yellow-600 hover:text-yellow-900 mr-2">Modifier</a>
                                    <form action="{{ route('insurance-claims.destroy', $claim) }}" method="POST" class="inline-block" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette réclamation ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                {{ $claims->links() }}
            </div>
        @endif
    </div>
</body>
</html>
