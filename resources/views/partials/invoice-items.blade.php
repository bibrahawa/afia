<div class="table-responsive">
    <h6>Facture #{{ $invoice->id }} - Patient: {{ $invoice->transaction->patient->first_name ?? '' }} {{ $invoice->transaction->patient->last_name ?? '' }}</h6>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Description</th>
                <th>Type</th>
                <th>Prix Unit.</th>
                <th>Qté</th>
                <th>Total</th>
                <th>Assurance</th>
                <th>Patient</th>
                <th>% Couv.</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
            <tr>
                <td>{{ $item->description }}</td>
                <td><span class="badge badge-info">{{ ucfirst($item->coverage_type_type) }}</span></td>
                <td>{{ number_format($item->unit_price, 0, ',', ' ') }} FCFA</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ number_format($item->total_amount, 0, ',', ' ') }} FCFA</td>
                <td>{{ number_format($item->insurance_covered_amount, 0, ',', ' ') }} FCFA</td>
                <td>{{ number_format($item->patient_amount, 0, ',', ' ') }} FCFA</td>
                <td>{{ $item->coverage_percentage_applied ? $item->coverage_percentage_applied . '%' : 'N/A' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="fw-bold">
                <td colspan="4">TOTAL</td>
                <td>{{ number_format($invoice->total_amount, 0, ',', ' ') }} FCFA</td>
                <td>{{ number_format($invoice->insurance_amount, 0, ',', ' ') }} FCFA</td>
                <td>{{ number_format($invoice->patient_amount, 0, ',', ' ') }} FCFA</td>
                <td>-</td>
            </tr>
        </tfoot>
    </table>
</div>