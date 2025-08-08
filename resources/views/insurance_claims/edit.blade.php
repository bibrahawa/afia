<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier la réclamation d'assurance #{{ $insuranceClaim->id }}</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow-md">
        <h1 class="text-2xl font-bold mb-6">Modifier la réclamation d'assurance #{{ $insuranceClaim->id }}</h1>

        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Erreur !</strong>
                <span class="block sm:inline">Veuillez corriger les erreurs ci-dessous.</span>
                <ul class="mt-2 list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('insurance-claims.update', $insuranceClaim) }}" method="POST">
            @csrf
            @method('PUT')

            @include('insurance_claims.form', ['insuranceClaim' => $insuranceClaim])

            <div class="mt-6">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Mettre à jour la réclamation</button>
                <a href="{{ route('insurance-claims.index') }}" class="ml-4 text-gray-600 hover:text-gray-900">Annuler</a>
            </div>
        </form>
    </div>
</body>
</html>
