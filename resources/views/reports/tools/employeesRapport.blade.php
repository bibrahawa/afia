@extends('layouts.index')

@section('content')

<div class="card shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center bg-white">
        <h5 class="mb-0">
            <i class="fa fa-users"></i> Rapport des employés
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
                <strong>Rapport des employés du :</strong>
                {{ is_null($from) ? 'Début' : dateToFrench($from) }}
                <strong>au</strong>
                {{ is_null($to) ? 'Aujourd\'hui' : dateToFrench($to) }}
            </h5>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-bordered text-center align-middle">
                <thead class="table-primary">
                    <tr>
                        <th>#</th>
                        <th>Nom Entreprise</th>
                        <th>Nom & Prénom</th>
                        <th>Type de Contrat</th>
                        <th>Genre</th>
                        <th>Poste</th>
                        <th>Date de Début</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transactions as $transaction)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $transaction->entreprise->nom_entreprise }}</td>
                            <td>{{ $transaction->nom . ' ' . $transaction->prenom }}</td>
                            <td>{{ $transaction->type_contrat }}</td>
                            <td>{{ $transaction->genre }}</td>
                            <td>{{ $transaction->poste }}</td>
                            <td>{{ carbonDate($transaction->date_debut, 'y-m-d') }}</td>
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
        location.reload(); // Recharge pour retrouver la page normale
    }
</script>
