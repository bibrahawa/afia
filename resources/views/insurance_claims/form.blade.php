<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
        <label for="claim_number" class="block text-sm font-medium text-gray-700">Numéro de Réclamation</label>
        <input type="text" name="claim_number" id="claim_number" value="{{ old('claim_number', $insuranceClaim->claim_number ?? '') }}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        @error('claim_number')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="invoice_id" class="block text-sm font-medium text-gray-700">Facture ID</label>
        <select name="invoice_id" id="invoice_id" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            <option value="">Sélectionner une facture</option>
            @foreach ($invoices as $invoice)
                <option value="{{ $invoice->id }}" {{ old('invoice_id', $insuranceClaim->invoice_id ?? '') == $invoice->id ? 'selected' : '' }}>
                    {{ $invoice->id }} - Total: {{ number_format($invoice->total_amount, 2) }}
                </option>
            @endforeach
        </select>
        @error('invoice_id')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="insurance_company_id" class="block text-sm font-medium text-gray-700">Compagnie d'Assurance</label>
        <select name="insurance_company_id" id="insurance_company_id" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            <option value="">Sélectionner une compagnie</option>
            @foreach ($insuranceCompanies as $company)
                <option value="{{ $company->id }}" {{ old('insurance_company_id', $insuranceClaim->insurance_company_id ?? '') == $company->id ? 'selected' : '' }}>
                    {{ $company->name }}
                </option>
            @endforeach
        </select>
        @error('insurance_company_id')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="patient_id" class="block text-sm font-medium text-gray-700">Patient</label>
        <select name="patient_id" id="patient_id" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            <option value="">Sélectionner un patient</option>
            @foreach ($patients as $patient)
                <option value="{{ $patient->id }}" {{ old('patient_id', $insuranceClaim->patient_id ?? '') == $patient->id ? 'selected' : '' }}>
                    {{ $patient->getFullNameAttribute() }} (ID: {{ $patient->id }})
                </option>
            @endforeach
        </select>
        @error('patient_id')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="claimed_amount" class="block text-sm font-medium text-gray-700">Montant Réclamé</label>
        <input type="number" step="0.01" name="claimed_amount" id="claimed_amount" value="{{ old('claimed_amount', $insuranceClaim->claimed_amount ?? '0.00') }}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        @error('claimed_amount')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="approved_amount" class="block text-sm font-medium text-gray-700">Montant Approuvé</label>
        <input type="number" step="0.01" name="approved_amount" id="approved_amount" value="{{ old('approved_amount', $insuranceClaim->approved_amount ?? '') }}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        @error('approved_amount')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="paid_amount" class="block text-sm font-medium text-gray-700">Montant Payé</label>
        <input type="number" step="0.01" name="paid_amount" id="paid_amount" value="{{ old('paid_amount', $insuranceClaim->paid_amount ?? '0.00') }}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        @error('paid_amount')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="status" class="block text-sm font-medium text-gray-700">Statut</label>
        <select name="status" id="status" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            @foreach(['draft', 'submitted', 'under_review', 'approved', 'rejected', 'paid'] as $statusOption)
                <option value="{{ $statusOption }}" {{ old('status', $insuranceClaim->status ?? '') == $statusOption ? 'selected' : '' }}>
                    {{ ucfirst($statusOption) }}
                </option>
            @endforeach
        </select>
        @error('status')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="submission_date" class="block text-sm font-medium text-gray-700">Date de Soumission</label>
        <input type="date" name="submission_date" id="submission_date" value="{{ old('submission_date', $insuranceClaim->submission_date?->format('Y-m-d') ?? '') }}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        @error('submission_date')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="approval_date" class="block text-sm font-medium text-gray-700">Date d'Approbation</label>
        <input type="date" name="approval_date" id="approval_date" value="{{ old('approval_date', $insuranceClaim->approval_date?->format('Y-m-d') ?? '') }}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        @error('approval_date')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="payment_date" class="block text-sm font-medium text-gray-700">Date de Paiement</label>
        <input type="date" name="payment_date" id="payment_date" value="{{ old('payment_date', $insuranceClaim->payment_date?->format('Y-m-d') ?? '') }}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        @error('payment_date')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="md:col-span-2">
        <label for="rejection_reason" class="block text-sm font-medium text-gray-700">Raison du Rejet</label>
        <textarea name="rejection_reason" id="rejection_reason" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">{{ old('rejection_reason', $insuranceClaim->rejection_reason ?? '') }}</textarea>
        @error('rejection_reason')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="md:col-span-2">
        <label for="documents" class="block text-sm font-medium text-gray-700">Documents (JSON Array)</label>
        <textarea name="documents" id="documents" rows="5" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder='Ex: ["document1.pdf", "image.jpg"]'>{{ old('documents', json_encode($insuranceClaim->documents ?? [])) }}</textarea>
        <p class="text-gray-500 text-xs mt-1">Entrez un tableau JSON de noms de fichiers ou de chemins. Pour de vrais téléchargements de fichiers, une logique JavaScript et backend supplémentaire serait nécessaire.</p>
        @error('documents')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>
</div>

