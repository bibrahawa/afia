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
                        <th>Patiente</th>
                        <th>Service</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1 ?>
                    @foreach($rapports as $rapport)
                        <tr>
                            <td>{{ $i++ }}</td>
                            <td>{{ $rapport['department'] }}</td>
                            <td>{{ $rapport['patiente'] }}</td>
                            @foreach ($rapport['services'] as $item)
                                <td><i class="fas fa-arrow-right">{{ " ".$item['service']." = ".number_format($item['amount'])." GNF" }}</i></td>
                            @endforeach
                            <td>{{ number_format($rapport['total'])." GNF" }}</td>
                        </tr>
                    @endforeach

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
