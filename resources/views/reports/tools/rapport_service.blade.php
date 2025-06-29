@extends('layouts.backend')

@section('content')

<div class="card shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center bg-white">
        <h5 class="mb-0">
            <i class="fa fa-building"></i> Rapport des entreprises
        </h5>
        <div>
            <a href="#" class="btn btn-sm btn-outline-dark me-2" onclick="printDiv('printableArea')">
                <i class="fa fa-print"></i> Imprimer
            </a>
            <a href="#" class="btn btn-sm btn-outline-secondary">
                <i class="fa fa-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    <div class="card-body" id="printableArea">
        <div class="text-center mb-4">
            <h5>
                <strong>Rapport des entreprises du :</strong>
                {{ is_null($from) ? 'Début' : date($from) }}
                <strong>au</strong>
                {{ is_null($to) ? 'Aujourd\'hui' : date($to) }}
            </h5>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-bordered text-center align-middle">
                <thead class="table-primary">
                    <tr>
                        <th>#</th>
                        <th>Department</th>
                        <th>Service</th>
                        <th>Montant</th>
                        <th>Patiente</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rapports as $rapport)
                        <tr>
                            <td>{{ $rapport['department'] }}</td>
                            <td>{{ $rapport['service'] }}</td>
                            <td>{{ $rapport['amount'] }}</td>
                            <td>{{ $rapport['patiente'] }}</td>
                        </tr>
                    @endforeach
                        <div class="total-section">
                            <div class="total-card">
                                <div class="total-row">
                                    <span>Sous-total:</span>
                                    <span>21,000 FCFA</span>
                                </div>
                                <div class="total-row">
                                    <span>TVA (18%):</span>
                                    <span>3,780 FCFA</span>
                                </div>
                                <div class="total-row final">
                                    <span>MONTANT TOTAL:</span>
                                    <span class="amount">24,780 FCFA</span>
                                </div>
                            </div>
                        </div>
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

<script>
    function printDiv(divName) {
        const printContents = document.getElementById(divName).innerHTML;
        const originalContents = document.body.innerHTML;
        document.body.innerHTML = printContents;
        window.print();
        document.body.innerHTML = originalContents;
        location.reload(); // Recharge la page après impression
    }
</script>
