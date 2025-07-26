 @extends('layouts.index')

 @section('content')
 
 <div class="card shadow-sm mb-4">
     <div class="card-header d-flex justify-content-between align-items-center bg-white">
         <h5 class="mb-0">
             <i class="fa fa-users"></i> Rapport des paiements employés
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
                 <strong>Rapport des paiements des employés du :</strong>
                 {{ is_null($from) ? 'Début' : dateToFrench($from) }}
                 <strong>au</strong>
                 {{ is_null($to) ? 'Aujourd\'hui' : dateToFrench($to) }}
             </h5>
         </div>
 
         <div class="table-responsive">
             <table class="table table-bordered table-hover text-center">
                 <thead class="table-primary">
                     <tr>
                         <th>#</th>
                         <th>Nom Entreprise</th>
                         <th>Nom & Prénom</th>
                         <th>Période</th>
                         <th>Genre</th>
                         <th>Poste</th>
                         <th>Date début</th>
                     </tr>
                 </thead>
                 <tbody>
                     @foreach($paies as $paie)
                         @php $entreprise = App\Models\Entreprise::find($paie->entreprise_id); @endphp
                         <tr>
                             <td>{{ $loop->iteration }}</td>
                             <td>{{ $entreprise->nom_entreprise ?? 'N/A' }}</td>
                             <td>{{ $paie->nom . ' ' . $paie->prenom }}</td>
                             <td>{{ $paie->periode }}</td>
                             <td>{{ $paie->genre }}</td>
                             <td>{{ $paie->poste }}</td>
                             <td>{{ carbonDate($paie->date_debut, 'y-m-d') }}</td>
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
                             <th class="bg-light text-end">Salaire brut :</th>
                             <td>{{ twoPlaceDecimal($totaux['salaire_brut']) }} GNF</td>
                         </tr>
                         <tr>
                             <th class="bg-light text-end">Cotisations sociales :</th>
                             <td>{{ $totaux['cotisations_sociales'] }}% <small>taxes</small></td>
                         </tr>
                         <tr>
                             <th class="bg-light text-end">Retenues fiscales :</th>
                             <td>{{ $totaux['retenues_fiscales'] }} GNF</td>
                         </tr>
                         <tr>
                             <th class="bg-light text-end">Salaire net :</th>
                             <td><strong>{{ $totaux['salaire_net'] }} GNF</strong></td>
                         </tr>
                         <tr>
                             <th class="bg-light text-end">Montant total entreprise :</th>
                             <td>{{ $totaux['montant_total_entreprise'] }} GNF</td>
                         </tr>
                     </tbody>
                 </table>
             </div>
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
         location.reload(); // Recharge la page pour rétablir les styles et scripts
     }
 </script>
 