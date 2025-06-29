@extends('layouts.index')

@section('content')

<div class="card shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center bg-white">
        <h5 class="mb-0">
            <i class="fa fa-users"></i> Rapport des Contrats
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
                <strong>Rapport des Contrats de :{{ is_numeric($entrepriseId) ? \App\Models\Entreprise::find($entrepriseId)?->nom_entreprise : 'Toutes les Entreprises' }}
                </strong>
                {{ is_null($mois) ? 'Début' : dateToFrench($mois) }}
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
                        <th>Salaire Brut</th>
                        <th>Paiement</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($contrats as $contrat)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $contrat->entreprise->nom_entreprise }}</td>
                            <td>{{ $contrat->employe->nom . ' ' . $contrat->employe->prenom }}</td>
                            <td>{{ $contrat->type_contrat }}</td>
                            <td>{{ $contrat->salaire_brut }} GNF</td>
                            <td>{{ $contrat->paiement }}</td>
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
