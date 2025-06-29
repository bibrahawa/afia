@extends('layouts.index')

@section('content')

<div class="card shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center bg-white">
        <h5 class="mb-0">
            <i class="fa fa-calendar-times-o"></i> Rapport des absences
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
                <strong>Rapport des absences du :</strong>
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
                        <th>Entreprise</th>
                        <th>Nom & Prénom</th>
                        <th>Genre</th>
                        <th>Date d'Absence</th>
                        <th>Motif</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($absences as $absence)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $absence->employe->entreprise->nom_entreprise }}</td>
                            <td>{{ $absence->employe->nom . ' ' . $absence->employe->prenom }}</td>
                            <td>{{ $absence->employe->genre }}</td>
                            <td>{{ carbonDate($absence->date_absence, 'y-m-d') }}</td>
                            <td>{{ $absence->motif }}</td>
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
        location.reload(); // Recharge pour restaurer la page normale
    }
</script>
