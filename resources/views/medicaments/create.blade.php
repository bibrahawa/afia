@extends('layouts.backend')

@section('content')
<div class="max-w-2xl mx-auto bg-white p-6 rounded shadow">
    <h2 class="text-xl font-bold mb-4">Ajouter un médicament</h2>

    <form action="{{ route('medicaments.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label class="block font-semibold">Nom</label>
            <input type="text" name="nom" class="w-full border rounded px-3 py-2" required>
        </div>

        <div class="mb-3">
            <label class="block font-semibold">Forme</label>
            <input type="text" name="forme" class="w-full border rounded px-3 py-2" placeholder="Comprimé, sirop...">
        </div>

        <div class="mb-3">
            <label class="block font-semibold">Dosage</label>
            <input type="text" name="dosage" class="w-full border rounded px-3 py-2" placeholder="500mg, 1g...">
        </div>

        <div class="mb-3">
            <label class="block font-semibold">Fréquence</label>
            <input type="text" name="frequence" class="w-full border rounded px-3 py-2" placeholder="2 fois/jour...">
        </div>

        <div class="mb-3">
            <label class="block font-semibold">Durée</label>
            <input type="text" name="duree" class="w-full border rounded px-3 py-2" placeholder="5 jours...">
        </div>

        <div class="mb-3">
            <label class="block font-semibold">Instructions spéciales</label>
            <textarea name="instructions" class="w-full border rounded px-3 py-2"></textarea>
        </div>

        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Enregistrer</button>
    </form>
</div>
@endsection
