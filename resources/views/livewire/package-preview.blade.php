<div>
    <div class="p-6">

        {{-- Section pour les EXAMENS --}}
        @if($previewTests && $previewTests->isNotEmpty())
            <div class="mb-8">
                <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                    <div class="w-2 h-6 bg-medical-500 rounded-full mr-3"></div>
                    EXAMENS DU PACKAGE
                </h3>
                <div class="bg-medical-50 p-6 rounded-xl">
                    <ul class="list-disc list-inside text-gray-700 space-y-2">
                        @foreach($previewTests as $test)
                            <li>{{ $test->name }} ({{ number_format($test->amount, 0, ',', ' ') }} FG)</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @else
            <div class="mb-8">
                <div class="bg-gray-100 p-4 rounded-lg text-gray-600 italic">
                    Aucun examen sélectionné pour le moment.
                </div>
            </div>
        @endif

        {{-- Section pour les SERVICES --}}
        @if($previewServices && $previewServices->isNotEmpty())
            <div class="mb-8">
                <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                    <div class="w-2 h-6 bg-green-500 rounded-full mr-3"></div>
                    SERVICES DU PACKAGE
                </h3>
                <div class="bg-green-50 p-6 rounded-xl">
                    <ul class="list-disc list-inside text-gray-700 space-y-2">
                        @foreach($previewServices as $service)
                            <li>{{ $service->name }} ({{ number_format($service->amount, 0, ',', ' ') }} FG)</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @else
            <div class="mb-8">
                <div class="bg-gray-100 p-4 rounded-lg text-gray-600 italic">
                    Aucun service sélectionné pour le moment.
                </div>
            </div>
        @endif

        <div class="mb-8">
            <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                <div class="w-2 h-6 bg-purple-500 rounded-full mr-3"></div>
                MONTANT TOTAL DU PACKAGE
            </h3>
            <div class="bg-purple-50 p-6 rounded-xl text-2xl font-bold text-green-700 text-center">
                {{ number_format($totalAmount, 0, ',', ' ') }} FG
            </div>
        </div>

        <div class="mb-8">
            <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                <div class="w-2 h-6 bg-blue-500 rounded-full mr-3"></div>
                DESCRIPTION DU PACKAGE
            </h3>
            <div class="bg-blue-50 p-6 rounded-xl text-gray-700">
                <p>{{ $description ?: 'Aucune description fournie.' }}</p>
            </div>
        </div>
    </div>
</div>
