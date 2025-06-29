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
 
 <div class="card shadow mb-4">
     <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom">
         <h5 class="mb-0"><i class="fa fa-file-alt me-2"></i>Rapport des Salariés</h5>
         <div>
             <a href="#" class="btn btn-sm btn-outline-dark" onclick="printDiv('printableArea')">
                 <i class="fa fa-print"></i> Imprimer
             </a>
             <a class="btn btn-sm btn-outline-secondary" href="{{ route('rapports.index') }}">
                 <i class="fa fa-arrow-left"></i> Retour
             </a>
         </div>
     </div>
 
     <div class="card-body" id="printableArea">
         <div class="text-center mb-4">
             <h5><strong>Rapport des Salariés</strong></h5>
             <p>
                 Période :
                 <strong>{{ is_null($periode) ? "Non définie" : dateToFrench($periode) }}</strong>
             </p>
         </div>
 
         <div class="table-responsive">
             <table class="table table-striped table-bordered align-middle text-center">
                 <thead class="table-primary">
                     <tr>
                         <th>#</th>
                         <th>Nom & Prénom</th>
                         <th>Entreprise</th>
                         <th>Charges Sociales</th>
                         <th>Montant Total Payé</th>
                         <th>Date</th>
                     </tr>
                 </thead>
                 <tbody>
                     @foreach($rapport as $ligne)
                         <tr>
                             <td>{{ $loop->iteration }}</td>
                             <td>{{ $ligne['nom'] }} {{ $ligne['prenom'] }}</td>
                             <td>{{ $ligne['entreprise'] }}</td>
                             <td>{{ number_format($ligne['charges_sociales'], 0, ',', ' ') }} GNF</td>
                             <td>{{ number_format($ligne['montant_paye'], 0, ',', ' ') }} GNF</td>
                             <td>{{ $ligne['date_debut'] }}</td>
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
        var printContents = document.getElementById(divName).innerHTML;
        var originalContents = document.body.innerHTML;
        document.body.innerHTML = printContents;
        window.print();
        document.body.innerHTML = originalContents;
    }
 </script>
 