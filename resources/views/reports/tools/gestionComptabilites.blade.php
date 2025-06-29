@extends('layouts.index')

@section('content')

<style>
    @media (min-width: 532px) and (max-width: 1199.98px)
         {
            tbody {
                width: 30px;
                display: table-row-group;
                vertical-align: middle;
                border-color: inherit;
            }
            .table {
                font-size: 11px;
                width: 100%;
                border-spacing: 0;
                border-collapse: separate;
            }
            .bg-white, .label-white, .table {
                background: #fff;
            }
            table.visible-lg {
                display: table !important;
            }
        }
</style>

<div class="card shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom">
        <h5 class="mb-0">
            <i class="fa fa-book"></i> Rapport de Comptabilité
        </h5>
        <div>
            <a href="#" class="btn btn-sm btn-outline-dark me-2" onclick="printDiv('printableArea')">
                <i class="fa fa-print"></i> Imprimer
            </a>
            <a href="{{ route('rapports.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fa fa-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    <div class="card-body" id="printableArea">
        <div class="text-center mb-4">
            <h5>
                <strong>Rapport de comptabilité du :</strong>
                {{ is_null($from) ? 'Début' : dateToFrench($from) }}
                <strong>au</strong>
                {{ is_null($to) ? 'Aujourd\'hui' : dateToFrench($to) }}
            </h5>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle text-center">
                <thead class="table-primary">
                    <tr>
                        <th>#</th>
                        <th>Entreprise</th>
                        <th>Date Opération</th>
                        <th>Description</th>
                        <th>Type</th>
                        <th>Montant</th>
                    </tr>
                </thead>
                <tbody>
                    @php $iteration = 1; @endphp
                    @foreach($comptes as $compte)
                        <tr>
                            <td>{{ $iteration++ }}</td>
                            <td>{{ $compte->entreprise->nom_entreprise }}</td>
                            <td>{{ $compte->date_operation }}</td>
                            <td>{{ $compte->description }}</td>
                            <td>{{ ucfirst($compte->type) }}</td>
                            <td>{{ number_format($compte->montant, 0, ',', ' ') }} GNF</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="row justify-content-end mt-4">
            <div class="col-md-6">
                <table class="table table-bordered">
                    <tbody>
                        <tr>
                            <th class="bg-light text-end">Total des entrées :</th>
                            <td>{{ number_format($totalEntree, 0, ',', ' ') }} GNF</td>
                        </tr>
                        <tr>
                            <th class="bg-light text-end">Total des dépenses :</th>
                            <td>{{ number_format($totalDepense, 0, ',', ' ') }} GNF</td>
                        </tr>
                        <tr>
                            <th class="bg-light text-end">Solde :</th>
                            <td><strong>{{ number_format($solde, 0, ',', ' ') }} GNF</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection

{{-- @section('scripts') --}}
<script>
    function printDiv(divName) {
        const printContents = document.getElementById(divName).innerHTML;
        const originalContents = document.body.innerHTML;
        document.body.innerHTML = printContents;
        window.print();
        document.body.innerHTML = originalContents;
        location.reload(); // recharge pour restaurer le JS et styles
    }
</script>
{{-- @endsection --}}

