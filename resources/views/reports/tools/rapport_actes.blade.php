@extends('layouts.backend')

@section('content')


<div class="container">
    <div class="card shadow-sm mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-white">
            <h5 class="mb-0">
                <i class="fa fa-file-medical"></i> Rapport de la clinique
            </h5>
            <div>
                <a href="#" class="btn btn-sm btn-outline-success me-2" onclick="exportToExcel()">
                    <i class="fa fa-file-excel"></i> Excel
                </a>
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
                    <strong>{{ $clinique_nom ?? 'Clinique Médicale' }} - Rapport d'activité du :</strong>
                    {{ $date_debut ?? '10/07/2025' }}
                    <strong>au</strong>
                    {{ $date_fin ?? '14/07/2025' }}
                </h5>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-bordered text-center align-middle">
                    <thead class="table-primary">
                        <tr>
                            <th>Date</th>
                            <th>Patient</th>
                            <th>Actes</th>
                            <th>Débit</th>
                            <th>Crédit</th>
                            <th>Solde</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $donnees = $donnees ?? [
                                ['date' => '10/07/2025', 'patient' => 'Oury Bah', 'actes' => 'Consultation', 'debit' => 250000, 'credit' => 200000, 'solde' => 50000],
                                ['date' => '11/07/2025', 'patient' => 'Ibrahim Barry', 'actes' => 'Échographie', 'debit' => 200000, 'credit' => 200000, 'solde' => 0],
                                ['date' => '12/07/2025', 'patient' => '', 'actes' => '', 'debit' => 0, 'credit' => 0, 'solde' => 0],
                                ['date' => '13/07/2025', 'patient' => '', 'actes' => '', 'debit' => 0, 'credit' => 0, 'solde' => 0],
                                ['date' => '14/07/2025', 'patient' => '', 'actes' => '', 'debit' => 0, 'credit' => 0, 'solde' => 0]
                            ];
                            $totalDebit = array_sum(array_column($donnees, 'debit'));
                            $totalCredit = array_sum(array_column($donnees, 'credit'));
                            $soldeNet = array_sum(array_column($donnees, 'solde'));
                        @endphp

                        @foreach($donnees as $ligne)
                            <tr>
                                <td>{{ $ligne['date'] }}</td>
                                <td>{{ $ligne['patient'] ?: '-' }}</td>
                                <td>{{ $ligne['actes'] ?: '-' }}</td>
                                <td class="text-end">
                                    {{ $ligne['debit'] > 0 ? number_format($ligne['debit'], 0, ',', ' ') . ' GNF' : '-' }}
                                </td>
                                <td class="text-end">
                                    {{ $ligne['credit'] > 0 ? number_format($ligne['credit'], 0, ',', ' ') . ' GNF' : '-' }}
                                </td>
                                <td class="text-end">
                                    {{ $ligne['solde'] > 0 ? number_format($ligne['solde'], 0, ',', ' ') . ' GNF' : '-' }}
                                </td>
                            </tr>
                        @endforeach

                        <!-- Ligne de totaux -->
                        <tr class="table-warning fw-bold">
                            <td colspan="3" class="text-end">TOTAUX :</td>
                            <td class="text-end">{{ number_format($totalDebit, 0, ',', ' ') }} GNF</td>
                            <td class="text-end">{{ number_format($totalCredit, 0, ',', ' ') }} GNF</td>
                            <td class="text-end">{{ number_format($soldeNet, 0, ',', ' ') }} GNF</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Résumé statistiques -->
            <div class="row mt-4">
                <div class="col-md-3">
                    <div class="card border-primary">
                        <div class="card-body text-center">
                            <h6 class="card-title">Patients traités</h6>
                            <h4 class="text-primary">{{ count(array_filter($donnees, fn($d) => !empty($d['patient']))) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-danger">
                        <div class="card-body text-center">
                            <h6 class="card-title">Total Débit</h6>
                            <h4 class="text-danger">{{ number_format($totalDebit, 0, ',', ' ') }} GNF</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-success">
                        <div class="card-body text-center">
                            <h6 class="card-title">Total Crédit</h6>
                            <h4 class="text-success">{{ number_format($totalCredit, 0, ',', ' ') }} GNF</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-info">
                        <div class="card-body text-center">
                            <h6 class="card-title">Solde Net</h6>
                            <h4 class="text-info">{{ number_format($soldeNet, 0, ',', ' ') }} GNF</h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center mt-4">
                <small class="text-muted">Généré le {{ date('d/m/Y à H:i') }}</small>
            </div>
        </div>
    </div>
</div>

@endsection

<!-- Inclure SheetJS pour l'export Excel -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
    function printDiv(divName) {
        const printContents = document.getElementById(divName).innerHTML;
        const originalContents = document.body.innerHTML;
        document.body.innerHTML = printContents;
        window.print();
        document.body.innerHTML = originalContents;
        location.reload(); // Recharge la page après impression
    }

    function exportToExcel() {
        // Données du rapport
        const donnees = @json($donnees ?? []);
        const clinique = @json($clinique_nom ?? 'Clinique Médicale');
        const dateDebut = @json($date_debut ?? '10/07/2025');
        const dateFin = @json($date_fin ?? '14/07/2025');

        // Créer un nouveau classeur
        const wb = XLSX.utils.book_new();

        // Préparer les données pour Excel
        const excelData = [];
        
        // En-tête avec informations de la clinique
        excelData.push([clinique + ' - Rapport d\'activité']);
        excelData.push(['Période du ' + dateDebut + ' au ' + dateFin]);
        excelData.push(['Généré le ' + new Date().toLocaleString('fr-FR')]);
        excelData.push([]); // Ligne vide

        // En-têtes du tableau
        excelData.push(['Date', 'Patient', 'Actes', 'Débit', 'Crédit', 'Solde']);

        // Données du tableau
        donnees.forEach(ligne => {
            excelData.push([
                ligne.date,
                ligne.patient || '-',
                ligne.actes || '-',
                ligne.debit > 0 ? ligne.debit + ' GNF' : '-',
                ligne.credit > 0 ? ligne.credit + ' GNF' : '-',
                ligne.solde > 0 ? ligne.solde + ' GNF' : '-'
            ]);
        });

        // Calcul des totaux
        const totalDebit = donnees.reduce((sum, item) => sum + item.debit, 0);
        const totalCredit = donnees.reduce((sum, item) => sum + item.credit, 0);
        const soldeNet = donnees.reduce((sum, item) => sum + item.solde, 0);

        // Ligne de totaux
        excelData.push([]);
        excelData.push(['', '', 'TOTAUX:', 
            totalDebit.toLocaleString('fr-FR') + ' GNF',
            totalCredit.toLocaleString('fr-FR') + ' GNF',
            soldeNet.toLocaleString('fr-FR') + ' GNF'
        ]);

        // Statistiques
        const patientsTraites = donnees.filter(d => d.patient && d.patient.trim() !== '').length;
        excelData.push([]);
        excelData.push(['STATISTIQUES:']);
        excelData.push(['Patients traités:', patientsTraites]);
        excelData.push(['Total Débit:', totalDebit.toLocaleString('fr-FR') + ' GNF']);
        excelData.push(['Total Crédit:', totalCredit.toLocaleString('fr-FR') + ' GNF']);
        excelData.push(['Solde Net:', soldeNet.toLocaleString('fr-FR') + ' GNF']);

        // Créer la feuille de calcul
        const ws = XLSX.utils.aoa_to_sheet(excelData);

        // Définir la largeur des colonnes
        ws['!cols'] = [
            { wch: 12 }, // Date
            { wch: 20 }, // Patient
            { wch: 15 }, // Actes
            { wch: 15 }, // Débit
            { wch: 15 }, // Crédit
            { wch: 15 }  // Solde
        ];

        // Ajouter la feuille au classeur
        XLSX.utils.book_append_sheet(wb, ws, "Rapport Clinique");

        // Nom du fichier avec date
        const fileName = 'rapport_clinique_' + dateDebut.replace(/\//g, '-') + '_' + dateFin.replace(/\//g, '-') + '.xlsx';

        // Télécharger le fichier
        XLSX.writeFile(wb, fileName);
    }
</script>