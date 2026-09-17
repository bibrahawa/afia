@extends('layouts.backend')

@section('content')

<div class="container">
    <div class="page-inner">
      <div class="page-header">
        <ul class="breadcrumbs">
          <li class="nav-home">
            <a href="{{url('/')}}">
              <i class="icon-home"></i>
            </a>
          </li>
          <li class="separator">
            <i class="icon-arrow-right"></i>
          </li>
          <li class="nav-item">
            <a href="{{ url('/') }}">Admin</a>
          </li>
          <li class="separator">
            <i class="icon-arrow-right"></i>
          </li>
          <li class="nav-item">
            <a href="{{ route('invoice.index') }}">Factures</a>
          </li>
          <li class="separator">
            <i class="icon-arrow-right"></i>
          </li>
          <li class="nav-item">
            <a href="{{ route('invoice.item.index') }}">Éléments de Facture</a>
          </li>
        </ul>
      </div>

      <div class="row">
        <div class="col-md-12">
          <div class="card">
            <div class="card-header">
              <div class="d-flex align-items-center">
                <h4 class="card-title">Liste des éléments de facture</h4>
                <button
                  class="btn btn-primary btn-round ms-auto"
                  data-bs-toggle="modal"
                  data-bs-target="#addRowModal"
                >
                  <i class="fa fa-plus"></i> Nouvel élément
                </button>
              </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table id="add-row" class="display table table-striped table-hover">
                    <thead class="bg-primary text-white">
                        <tr>
                        <th style="width: 5%">ID</th>
                        <th>Facture</th>
                        <th>Description</th>
                        <th>Type de Couverture</th>
                        <th>Prix Unitaire</th>
                        <th>Quantité</th>
                        <th>Montant Total</th>
                        <th>Montant Assurance</th>
                        <th>Part Patient</th>
                        <th>% Couverture</th>
                        <th style="width: 10%">Action</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                        <th>ID</th>
                        <th>Facture</th>
                        <th>Description</th>
                        <th>Type de Couverture</th>
                        <th>Prix Unitaire</th>
                        <th>Quantité</th>
                        <th>Montant Total</th>
                        <th>Montant Assurance</th>
                        <th>Part Patient</th>
                        <th>% Couverture</th>
                        <th>Action</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        @foreach($invoiceItems as $item)
                            <tr>
                                <td>{{ $item->id}}</td>
                                <td>Facture #{{ $item->invoice_id }}</td>
                                <td>{{ $item->description }}</td>
                                <td>
                                    <span class="badge badge-info">
                                        {{ \App\Support\Facturation\TypesFacturables::libelle($item->coverage_type_type) }} #{{ $item->coverage_type_id }}
                                    </span>
                                </td>
                                <td>{{ number_format($item->unit_price, 0, ',', ' ') }} GNF</td>
                                <td>{{ $item->quantity }}</td>
                                <td>{{ number_format($item->total_amount, 0, ',', ' ') }} GNF</td>
                                <td>{{ number_format($item->insurance_covered_amount, 0, ',', ' ') }} GNF</td>
                                <td>{{ number_format($item->patient_amount, 0, ',', ' ') }} GNF</td>
                                <td>{{ $item->coverage_percentage_applied ? $item->coverage_percentage_applied . '%' : 'N/A' }}</td>
                                <td>
                                    <div class="form-button-action">
                                        <button
                                            type="button"
                                            class="btn btn-warning btn-round btn-sm edit-button"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editRowModal"
                                            data-info="{{$item->id}},{{$item->invoice_id}},{{$item->coverage_type_type}},{{$item->coverage_type_id}},{{ str_replace(',', '|', $item->description) }},{{$item->unit_price}},{{$item->quantity}},{{$item->total_amount}},{{$item->insurance_covered_amount}},{{$item->patient_amount}},{{$item->coverage_percentage_applied}}"
                                        >
                                            <i class="fa fa-edit"></i>
                                        </button>

                                        <button
                                            type="button"
                                            class="btn btn-danger btn-round btn-sm delete-button"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteRowModal"
                                            data-id="{{$item->id}}"
                                            data-name="Élément #{{$item->id}} - {{$item->description}}"
                                        >
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    </table>
                </div>

                <!-- Modal Add -->
                <div class="modal fade" id="addRowModal" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <div class="modal-header border-0">
                        <h5 class="modal-title">
                            <span class="fw-mediumbold"> Nouvel</span>
                            <span class="fw-light"> Élément de Facture</span>
                        </h5>
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        </div>
                        <div class="modal-body">
                        <p class="small">Créez un nouvel élément de facture en remplissant le formulaire ci-dessous.</p>
                        <form id="addItemForm" action="{{ route('invoice.item.add') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group form-group-default">
                                        <label>Facture</label>
                                        <select id="invoice_id" name="invoice_id" class="form-control" required>
                                            <option value="">Sélectionner une facture</option>
                                            @foreach($invoices as $invoice)
                                                <option value="{{ $invoice->id }}">Facture #{{ $invoice->id }} - TXN-{{ $invoice->transaction_id }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group form-group-default">
                                        <label>Description</label>
                                        <input
                                            id="description"
                                            name="description"
                                            type="text"
                                            class="form-control"
                                            placeholder="Description de l'élément"
                                            required
                                        />
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group form-group-default">
                                        <label>Type de couverture</label>
                                        <select id="coverage_type_type" name="coverage_type_type" class="form-control" required>
                                            <option value="">Sélectionner un type</option>
                                            <option value="consultation">Consultation</option>
                                            <option value="medication">Médicament</option>
                                            <option value="procedure">Procédure</option>
                                            <option value="hospitalization">Hospitalisation</option>
                                            <option value="examination">Examen</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group form-group-default">
                                        <label>ID de couverture</label>
                                        <input
                                            id="coverage_type_id"
                                            name="coverage_type_id"
                                            type="number"
                                            class="form-control"
                                            placeholder="ID de l'élément de couverture"
                                            required
                                        />
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-4">
                                    <div class="form-group form-group-default">
                                        <label>Prix unitaire (GNF)</label>
                                        <input
                                            id="unit_price"
                                            name="unit_price"
                                            type="number"
                                            step="0.01"
                                            class="form-control"
                                            placeholder="Ex: 5000"
                                            required
                                        />
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="form-group form-group-default">
                                        <label>Quantité</label>
                                        <input
                                            id="quantity"
                                            name="quantity"
                                            type="number"
                                            class="form-control"
                                            placeholder="Ex: 2"
                                            value="1"
                                            required
                                        />
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="form-group form-group-default">
                                        <label>Montant total (GNF)</label>
                                        <input
                                            id="total_amount"
                                            name="total_amount"
                                            type="number"
                                            step="0.01"
                                            class="form-control"
                                            placeholder="Calculé automatiquement"
                                            required
                                        />
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-4">
                                    <div class="form-group form-group-default">
                                        <label>Montant couvert par l'assurance (GNF)</label>
                                        <input
                                            id="insurance_covered_amount"
                                            name="insurance_covered_amount"
                                            type="number"
                                            step="0.01"
                                            class="form-control"
                                            placeholder="Ex: 4000"
                                            value="0"
                                        />
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="form-group form-group-default">
                                        <label>Part patient (GNF)</label>
                                        <input
                                            id="patient_amount"
                                            name="patient_amount"
                                            type="number"
                                            step="0.01"
                                            class="form-control"
                                            placeholder="Ex: 1000"
                                            value="0"
                                        />
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="form-group form-group-default">
                                        <label>Pourcentage de couverture appliqué (%)</label>
                                        <input
                                            id="coverage_percentage_applied"
                                            name="coverage_percentage_applied"
                                            type="number"
                                            step="0.01"
                                            max="100"
                                            min="0"
                                            class="form-control"
                                            placeholder="Ex: 80"
                                        />
                                    </div>
                                </div>
                            </div>
                        </form>
                        </div>
                        <div class="modal-footer border-0">
                        <button type="submit" id="addRowButton" class="btn btn-primary" form="addItemForm">
                            Ajouter
                            <div class="spinner-border spinner-border-sm text-light" role="status" id="addLoader" style="display: none;">
                                <span class="sr-only">Loading...</span>
                            </div>
                        </button>

                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">
                            Fermer
                        </button>
                        </div>
                    </div>
                    </div>
                </div>

                <!-- Modal Edit -->
                <div class="modal fade" id="editRowModal" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-xl" role="document">
                        <div class="modal-content">
                            <div class="modal-header border-0">
                                <h5 class="modal-title">
                                    <span class="fw-mediumbold"> Modifier</span>
                                    <span class="fw-light"> Élément de Facture</span>
                                </h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form id='editItemForm' action="{{ route('invoice.item.update') }}" method="POST">
                                    @csrf
                                    @method('POST')
                                    <input type="hidden" id="edit_id" name="id" />
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Facture</label>
                                                <select id="edit_invoice_id" name="invoice_id" class="form-control" required>
                                                    <option value="">Sélectionner une facture</option>
                                                    @foreach($invoices as $invoice)
                                                        <option value="{{ $invoice->id }}">Facture #{{ $invoice->id }} - TXN-{{ $invoice->transaction_id }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Description</label>
                                                <input
                                                    id="edit_description"
                                                    name="description"
                                                    type="text"
                                                    class="form-control"
                                                    required
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Type de couverture</label>
                                                <select id="edit_coverage_type_type" name="coverage_type_type" class="form-control" required>
                                                    <option value="">Sélectionner un type</option>
                                                    <option value="consultation">Consultation</option>
                                                    <option value="medication">Médicament</option>
                                                    <option value="procedure">Procédure</option>
                                                    <option value="hospitalization">Hospitalisation</option>
                                                    <option value="examination">Examen</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>ID de couverture</label>
                                                <input
                                                    id="edit_coverage_type_id"
                                                    name="coverage_type_id"
                                                    type="number"
                                                    class="form-control"
                                                    required
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-4">
                                            <div class="form-group form-group-default">
                                                <label>Prix unitaire (GNF)</label>
                                                <input
                                                    id="edit_unit_price"
                                                    name="unit_price"
                                                    type="number"
                                                    step="0.01"
                                                    class="form-control"
                                                    required
                                                />
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="form-group form-group-default">
                                                <label>Quantité</label>
                                                <input
                                                    id="edit_quantity"
                                                    name="quantity"
                                                    type="number"
                                                    class="form-control"
                                                    required
                                                />
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="form-group form-group-default">
                                                <label>Montant total (GNF)</label>
                                                <input
                                                    id="edit_total_amount"
                                                    name="total_amount"
                                                    type="number"
                                                    step="0.01"
                                                    class="form-control"
                                                    required
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-4">
                                            <div class="form-group form-group-default">
                                                <label>Montant couvert par l'assurance (GNF)</label>
                                                <input
                                                    id="edit_insurance_covered_amount"
                                                    name="insurance_covered_amount"
                                                    type="number"
                                                    step="0.01"
                                                    class="form-control"
                                                />
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="form-group form-group-default">
                                                <label>Part patient (GNF)</label>
                                                <input
                                                    id="edit_patient_amount"
                                                    name="patient_amount"
                                                    type="number"
                                                    step="0.01"
                                                    class="form-control"
                                                />
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="form-group form-group-default">
                                                <label>Pourcentage de couverture appliqué (%)</label>
                                                <input
                                                    id="edit_coverage_percentage_applied"
                                                    name="coverage_percentage_applied"
                                                    type="number"
                                                    step="0.01"
                                                    max="100"
                                                    min="0"
                                                    class="form-control"
                                                />
                                            </div>
                                        </div>
                                    </div>
                            </div>
                            <div class="modal-footer border-0">
                                <button type="submit" class="btn btn-success" id="editRowButton" form="editItemForm">
                                    Modifier
                                    <div class="spinner-border spinner-border-sm text-light" role="status" id="editLoader" style="display: none;">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                </button>
                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Fermer</button>
                            </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Modal Delete -->
                <div class="modal fade" id="deleteRowModal" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header border-0">
                                <h5 class="modal-title">Êtes-vous sûr de vouloir supprimer cet élément ?</h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form id="deleteItemForm" action="{{ route('invoice.item.delete', ['id' => '']) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <p id="item_name_to_delete"></p>
                                    <input type="hidden" id="delete_id" name="id">
                                </form>
                            </div>
                            <div class="modal-footer border-0">
                                <button type="submit" class="btn btn-danger" id="deleteRowButton" form="deleteItemForm">
                                    Supprimer
                                    <div class="spinner-border spinner-border-sm text-light" role="status" id="deleteLoader" style="display: none;">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                </button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    Annuler
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
      </div>
    </div>
</div>

@endsection

@section('script')
    <script type="text/javascript">
        // Calcul automatique du montant total lors de la saisie
        $(document).on('input', '#unit_price, #quantity', function() {
            var unitPrice = parseFloat($('#unit_price').val()) || 0;
            var quantity = parseInt($('#quantity').val()) || 0;
            var totalAmount = unitPrice * quantity;
            $('#total_amount').val(totalAmount.toFixed(2));
        });

        // Calcul automatique du montant total lors de la modification
        $(document).on('input', '#edit_unit_price, #edit_quantity', function() {
            var unitPrice = parseFloat($('#edit_unit_price').val()) || 0;
            var quantity = parseInt($('#edit_quantity').val()) || 0;
            var totalAmount = unitPrice * quantity;
            $('#edit_total_amount').val(totalAmount.toFixed(2));
        });

        // Événement pour modifier un élément de facture
        $(document).on('click', '.edit-button', function() {
            var details = $(this).data('info').split(',');
            var id = details[0];
            var invoiceId = details[1];
            var coverageTypeType = details[2];
            var coverageTypeId = details[3];
            var description = details[4];
            var unitPrice = details[5];
            var quantity = details[6];
            var totalAmount = details[7];
            var insuranceCoveredAmount = details[8];
            var patientAmount = details[9];
            var coveragePercentageApplied = details[10];

            // Mettre à jour les champs du modal
            $('#edit_id').val(id);
            $('#edit_invoice_id').val(invoiceId);
            $('#edit_coverage_type_type').val(coverageTypeType);
            $('#edit_coverage_type_id').val(coverageTypeId);
            $('#edit_description').val(description.replace(/\|/g, ','));
            $('#edit_unit_price').val(unitPrice);
            $('#edit_quantity').val(quantity);
            $('#edit_total_amount').val(totalAmount);
            $('#edit_insurance_covered_amount').val(insuranceCoveredAmount);
            $('#edit_patient_amount').val(patientAmount);
            $('#edit_coverage_percentage_applied').val(coveragePercentageApplied != 'null' ? coveragePercentageApplied : '');

            // Afficher le modal
            $('#editRowModal').modal('show');
        });

        // Événement pour supprimer un élément de facture
        $(document).on('click', '.delete-button', function() {
            var id = $(this).data('id');
            var name = $(this).data('name');

            // Afficher le nom de l'élément à supprimer
            $('#item_name_to_delete').text("Voulez-vous vraiment supprimer " + name + " ?");

            // Mettre à jour l'action du formulaire de suppression avec l'ID
            $('#delete_id').val(id);
            $('#deleteItemForm').attr('action', '/invoice/item/delete/' + id);

            // Afficher le modal de confirmation
            $('#deleteRowModal').modal('show');
        });

        // Afficher le loader pour l'ajout d'élément
        $('#addItemForm').on('submit', function() {
            $('#addRowButton').prop('disabled', true);
            $('#addLoader').show();
        });

        // Afficher le loader pour la modification d'élément
        $('#editItemForm').on('submit', function() {
            $('#editRowButton').prop('disabled', true);
            $('#editLoader').show();
        });

        // Afficher le loader pour la suppression d'élément
        $('#deleteItemForm').on('submit', function() {
            $('#deleteRowButton').prop('disabled', true);
            $('#deleteLoader').show();
        });

        // Lorsque la requête est terminée (réponse du serveur)
        $(document).ajaxComplete(function() {
            // Masquer les loaders et réactiver les boutons
            $('#addRowButton').prop('disabled', false);
            $('#addLoader').hide();

            $('#editRowButton').prop('disabled', false);
            $('#editLoader').hide();

            $('#deleteRowButton').prop('disabled', false);
            $('#deleteLoader').hide();
        });

    </script>
@endsection