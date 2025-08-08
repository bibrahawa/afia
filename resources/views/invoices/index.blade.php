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
        </ul>
      </div>

      <div class="row">
        <div class="col-md-12">
          <div class="card">
            <div class="card-header">
              <div class="d-flex align-items-center">
                <h4 class="card-title">Liste des factures</h4>
                <button
                  class="btn btn-primary btn-round ms-auto"
                  data-bs-toggle="modal"
                  data-bs-target="#addRowModal"
                >
                  <i class="fa fa-plus"></i> Nouvelle facture
                </button>
              </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table id="add-row" class="display table table-striped table-hover">
                    <thead class="bg-primary text-white">
                        <tr>
                        <th style="width: 5%">ID</th>
                        <th>Transaction</th>
                        <th>Patient</th>
                        <th>Assurance</th>
                        <th>Montant Total</th>
                        <th>Part Patient</th>
                        <th>Part Assurance</th>
                        {{-- <th>Statut Assurance</th>
                        <th>Statut Patient</th> --}}
                        <th>N° Réclamation</th>
                        <th style="width: 12%">Action</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                        <th>ID</th>
                        <th>Transaction</th>
                        <th>Patient</th>
                        <th>Assurance</th>
                        <th>Montant Total</th>
                        <th>Part Patient</th>
                        <th>Part Assurance</th>
                        {{-- <th>Statut Assurance</th>
                        <th>Statut Patient</th> --}}
                        <th>N° Réclamation</th>
                        <th>Action</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        @foreach($invoices as $invoice)
                            <tr>
                                <td>{{ $invoice->id}}</td>
                                <td>TXN-{{ $invoice->transaction_id }}</td>
                                <td>
                                    @if($invoice->transaction && $invoice->transaction->patient)
                                        {{ $invoice->transaction->patient->first_name }} {{ $invoice->transaction->patient->last_name }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td>{{ $invoice->insuranceCompany ? $invoice->insuranceCompany->name : 'Aucune' }}</td>
                                <td>{{ number_format($invoice->total_amount, 0, ',', ' ') }} GNF</td>
                                <td>{{ number_format($invoice->patient_amount, 0, ',', ' ') }} GNF</td>
                                <td>{{ number_format($invoice->insurance_amount, 0, ',', ' ') }} GNF</td>
                                {{-- <td>
                                    @if($invoice->insurance_status)
                                        <span class="badge badge-{{ $invoice->insurance_status == 'paid' ? 'success' : ($invoice->insurance_status == 'approved' ? 'info' : ($invoice->insurance_status == 'rejected' ? 'danger' : 'warning')) }}">
                                            {{ ucfirst($invoice->insurance_status) }}
                                        </span>
                                    @else
                                        <span class="badge badge-secondary">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if($invoice->patient_amount_status)
                                        <span class="badge badge-{{ $invoice->patient_amount_status == 'paid' ? 'success' : ($invoice->patient_amount_status == 'rejected' ? 'danger' : 'warning') }}">
                                            {{ ucfirst($invoice->patient_amount_status) }}
                                        </span>
                                    @else
                                        <span class="badge badge-secondary">N/A</span>
                                    @endif
                                </td> --}}
                                <td>{{ $invoice->insurance_claim_number ?? 'N/A' }}</td>
                                <td>
                                    <div class="form-button-action">
                                        <button
                                            type="button"
                                            class="btn btn-info btn-round btn-sm view-items-button"
                                            data-bs-toggle="modal"
                                            data-bs-target="#viewItemsModal"
                                            data-id="{{$invoice->id}}"
                                            title="Voir les éléments"
                                        >
                                            <i class="fa fa-list"></i>
                                        </button>

                                        <button
                                            type="button"
                                            class="btn btn-warning btn-round btn-sm edit-button"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editRowModal"
                                            data-info="{{$invoice->id}},{{$invoice->transaction_id}},{{$invoice->insurance_company_id}},{{$invoice->patient_insurance_id}},{{$invoice->total_amount}},{{$invoice->patient_amount}},{{$invoice->insurance_amount}},{{$invoice->insurance_status}},{{$invoice->patient_amount_status}},{{$invoice->insurance_submission_date}},{{$invoice->insurance_payment_date}},{{$invoice->insurance_claim_number}},{{ str_replace(',', '|', $invoice->insurance_notes ?? '') }}"
                                        >
                                            <i class="fa fa-edit"></i>
                                        </button>

                                        <button
                                            type="button"
                                            class="btn btn-danger btn-round btn-sm delete-button"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteRowModal"
                                            data-id="{{$invoice->id}}"
                                            data-name="Facture #{{$invoice->id}} - TXN-{{$invoice->transaction_id}}"
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
                            <span class="fw-mediumbold"> Nouvelle</span>
                            <span class="fw-light"> Facture</span>
                        </h5>
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        </div>
                        <div class="modal-body">
                        <p class="small">Créez une nouvelle facture en remplissant le formulaire ci-dessous.</p>
                        <form id="addInvoiceForm" action="{{ route('invoice.add') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-sm-4">
                                    <div class="form-group form-group-default">
                                        <label>Transaction</label>
                                        <select id="transaction_id" name="transaction_id" class="form-control" required>
                                            <option value="">Sélectionner une transaction</option>
                                            @foreach($transactions as $transaction)
                                                <option value="{{ $transaction->id }}">TXN-{{ $transaction->id }} - {{ $transaction->patient->first_name ?? '' }} {{ $transaction->patient->last_name ?? '' }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="form-group form-group-default">
                                        <label>Compagnie d'assurance</label>
                                        <select id="insurance_company_id" name="insurance_company_id" class="form-control">
                                            <option value="">Aucune assurance</option>
                                            @foreach($insuranceCompanies as $company)
                                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="form-group form-group-default">
                                        <label>Assurance patient</label>
                                        <select id="patient_insurance_id" name="patient_insurance_id" class="form-control">
                                            <option value="">Aucune assurance patient</option>
                                            @foreach($patientInsurances as $patientInsurance)
                                                <option value="{{ $patientInsurance->id }}">{{ $patientInsurance->policy_number }} - {{ $patientInsurance->patient->first_name ?? '' }} {{ $patientInsurance->patient->last_name ?? '' }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-4">
                                    <div class="form-group form-group-default">
                                        <label>Montant total (GNF)</label>
                                        <input
                                            id="total_amount"
                                            name="total_amount"
                                            type="number"
                                            step="0.01"
                                            class="form-control"
                                            placeholder="Ex: 50000"
                                            required
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
                                            placeholder="Ex: 15000"
                                            value="0"
                                        />
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="form-group form-group-default">
                                        <label>Part assurance (GNF)</label>
                                        <input
                                            id="insurance_amount"
                                            name="insurance_amount"
                                            type="number"
                                            step="0.01"
                                            class="form-control"
                                            placeholder="Ex: 35000"
                                            value="0"
                                        />
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group form-group-default">
                                        <label>Statut assurance</label>
                                        <select id="insurance_status" name="insurance_status" class="form-control">
                                            <option value="">Aucun statut</option>
                                            <option value="pending">En attente</option>
                                            <option value="submitted">Soumis</option>
                                            <option value="approved">Approuvé</option>
                                            <option value="rejected">Rejeté</option>
                                            <option value="paid">Payé</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group form-group-default">
                                        <label>Statut part patient</label>
                                        <select id="patient_amount_status" name="patient_amount_status" class="form-control">
                                            <option value="">Aucun statut</option>
                                            <option value="pending">En attente</option>
                                            <option value="rejected">Rejeté</option>
                                            <option value="paid">Payé</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-4">
                                    <div class="form-group form-group-default">
                                        <label>Date soumission assurance</label>
                                        <input
                                            id="insurance_submission_date"
                                            name="insurance_submission_date"
                                            type="date"
                                            class="form-control"
                                        />
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="form-group form-group-default">
                                        <label>Date paiement assurance</label>
                                        <input
                                            id="insurance_payment_date"
                                            name="insurance_payment_date"
                                            type="date"
                                            class="form-control"
                                        />
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="form-group form-group-default">
                                        <label>N° réclamation assurance</label>
                                        <input
                                            id="insurance_claim_number"
                                            name="insurance_claim_number"
                                            type="text"
                                            class="form-control"
                                            placeholder="Ex: CLM-2024-001"
                                        />
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="form-group form-group-default">
                                        <label>Notes assurance</label>
                                        <textarea
                                            id="insurance_notes"
                                            name="insurance_notes"
                                            class="form-control"
                                            placeholder="Notes ou commentaires pour l'assurance"
                                            rows="3"
                                        ></textarea>
                                    </div>
                                </div>
                            </div>
                        </form>
                        </div>
                        <div class="modal-footer border-0">
                        <button type="submit" id="addRowButton" class="btn btn-primary" form="addInvoiceForm">
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
                                    <span class="fw-light"> Facture</span>
                                </h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form id='editInvoiceForm' action="{{ route('invoice.update') }}" method="POST">
                                    @csrf
                                    @method('POST')
                                    <input type="hidden" id="edit_id" name="id" />
                                    <div class="row">
                                        <div class="col-sm-4">
                                            <div class="form-group form-group-default">
                                                <label>Transaction</label>
                                                <select id="edit_transaction_id" name="transaction_id" class="form-control" required>
                                                    <option value="">Sélectionner une transaction</option>
                                                    @foreach($transactions as $transaction)
                                                        <option value="{{ $transaction->id }}">TXN-{{ $transaction->id }} - {{ $transaction->patient->first_name ?? '' }} {{ $transaction->patient->last_name ?? '' }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="form-group form-group-default">
                                                <label>Compagnie d'assurance</label>
                                                <select id="edit_insurance_company_id" name="insurance_company_id" class="form-control">
                                                    <option value="">Aucune assurance</option>
                                                    @foreach($insuranceCompanies as $company)
                                                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="form-group form-group-default">
                                                <label>Assurance patient</label>
                                                <select id="edit_patient_insurance_id" name="patient_insurance_id" class="form-control">
                                                    <option value="">Aucune assurance patient</option>
                                                    @foreach($patientInsurances as $patientInsurance)
                                                        <option value="{{ $patientInsurance->id }}">{{ $patientInsurance->policy_number }} - {{ $patientInsurance->patient->first_name ?? '' }} {{ $patientInsurance->patient->last_name ?? '' }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
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
                                                <label>Part assurance (GNF)</label>
                                                <input
                                                    id="edit_insurance_amount"
                                                    name="insurance_amount"
                                                    type="number"
                                                    step="0.01"
                                                    class="form-control"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Statut assurance</label>
                                                <select id="edit_insurance_status" name="insurance_status" class="form-control">
                                                    <option value="">Aucun statut</option>
                                                    <option value="pending">En attente</option>
                                                    <option value="submitted">Soumis</option>
                                                    <option value="approved">Approuvé</option>
                                                    <option value="rejected">Rejeté</option>
                                                    <option value="paid">Payé</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group form-group-default">
                                                <label>Statut part patient</label>
                                                <select id="edit_patient_amount_status" name="patient_amount_status" class="form-control">
                                                    <option value="">Aucun statut</option>
                                                    <option value="pending">En attente</option>
                                                    <option value="rejected">Rejeté</option>
                                                    <option value="paid">Payé</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-4">
                                            <div class="form-group form-group-default">
                                                <label>Date soumission assurance</label>
                                                <input
                                                    id="edit_insurance_submission_date"
                                                    name="insurance_submission_date"
                                                    type="date"
                                                    class="form-control"
                                                />
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="form-group form-group-default">
                                                <label>Date paiement assurance</label>
                                                <input
                                                    id="edit_insurance_payment_date"
                                                    name="insurance_payment_date"
                                                    type="date"
                                                    class="form-control"
                                                />
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="form-group form-group-default">
                                                <label>N° réclamation assurance</label>
                                                <input
                                                    id="edit_insurance_claim_number"
                                                    name="insurance_claim_number"
                                                    type="text"
                                                    class="form-control"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <div class="form-group form-group-default">
                                                <label>Notes assurance</label>
                                                <textarea
                                                    id="edit_insurance_notes"
                                                    name="insurance_notes"
                                                    class="form-control"
                                                    rows="3"
                                                ></textarea>
                                            </div>
                                        </div>
                                    </div>
                            </div>
                            <div class="modal-footer border-0">
                                <button type="submit" class="btn btn-success" id="editRowButton" form="editInvoiceForm">
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

                <!-- Modal View Items -->
                <div class="modal fade" id="viewItemsModal" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-xl" role="document">
                        <div class="modal-content">
                            <div class="modal-header border-0">
                                <h5 class="modal-title">Éléments de la facture</h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div id="invoice-items-content">
                                    <!-- Contenu chargé dynamiquement -->
                                </div>
                            </div>
                            <div class="modal-footer border-0">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Delete -->
                <div class="modal fade" id="deleteRowModal" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header border-0">
                                <h5 class="modal-title">Êtes-vous sûr de vouloir supprimer cette facture ?</h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form id="deleteInvoiceForm" action="#" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <p id="invoice_name_to_delete"></p>
                                    <input type="hidden" id="delete_id" name="id">
                                </form>
                            </div>
                            <div class="modal-footer border-0">
                                <button type="submit" class="btn btn-danger" id="deleteRowButton" form="deleteInvoiceForm">
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
        // Événement pour modifier une facture
        $(document).on('click', '.edit-button', function() {
            var details = $(this).data('info').split(',');
            var id = details[0];
            var transactionId = details[1];
            var insuranceCompanyId = details[2];
            var patientInsuranceId = details[3];
            var totalAmount = details[4];
            var patientAmount = details[5];
            var insuranceAmount = details[6];
            var insuranceStatus = details[7];
            var patientAmountStatus = details[8];
            var insuranceSubmissionDate = details[9];
            var insurancePaymentDate = details[10];
            var insuranceClaimNumber = details[11];
            var insuranceNotes = details[12];

            // Mettre à jour les champs du modal
            $('#edit_id').val(id);
            $('#edit_transaction_id').val(transactionId);
            $('#edit_insurance_company_id').val(insuranceCompanyId != 'null' ? insuranceCompanyId : '');
            $('#edit_patient_insurance_id').val(patientInsuranceId != 'null' ? patientInsuranceId : '');
            $('#edit_total_amount').val(totalAmount);
            $('#edit_patient_amount').val(patientAmount);
            $('#edit_insurance_amount').val(insuranceAmount);
            $('#edit_insurance_status').val(insuranceStatus != 'null' ? insuranceStatus : '');
            $('#edit_patient_amount_status').val(patientAmountStatus != 'null' ? patientAmountStatus : '');
            $('#edit_insurance_submission_date').val(insuranceSubmissionDate != 'null' ? insuranceSubmissionDate : '');
            $('#edit_insurance_payment_date').val(insurancePaymentDate != 'null' ? insurancePaymentDate : '');
            $('#edit_insurance_claim_number').val(insuranceClaimNumber != 'null' ? insuranceClaimNumber : '');
            $('#edit_insurance_notes').val(insuranceNotes != 'null' ? insuranceNotes.replace(/\|/g, ',') : '');

            // Afficher le modal
            $('#editRowModal').modal('show');
        });

        // Événement pour voir les éléments d'une facture
        $(document).on('click', '.view-items-button', function() {
            var invoiceId = $(this).data('id');
            
            // Charger le contenu des éléments via AJAX
            $.ajax({
                url: '/insurance/invoice/' + invoiceId + '/items',
                method: 'GET',
                success: function(response) {
                    $('#invoice-items-content').html(response);
                    $('#viewItemsModal').modal('show');
                },
                error: function() {
                    alert('Erreur lors du chargement des éléments de la facture.');
                }
            });
        });

        // Événement pour supprimer une facture
        $(document).on('click', '.delete-button', function() {
            var id = $(this).data('id');
            var name = $(this).data('name');

            // Afficher le nom de la facture à supprimer
            $('#invoice_name_to_delete').text("Voulez-vous vraiment supprimer la " + name + " ?");

            // Mettre à jour l'action du formulaire de suppression avec l'ID
            $('#delete_id').val(id);
            $('#deleteInvoiceForm').attr('action', '/invoice/delete/' + id);

            // Afficher le modal de confirmation
            $('#deleteRowModal').modal('show');
        });

        // Afficher le loader pour l'ajout de facture
        $('#addInvoiceForm').on('submit', function() {
            $('#addRowButton').prop('disabled', true);
            $('#addLoader').show();
        });

        // Afficher le loader pour la modification de facture
        $('#editInvoiceForm').on('submit', function() {
            $('#editRowButton').prop('disabled', true);
            $('#editLoader').show();
        });

        // Afficher le loader pour la suppression de facture
        $('#deleteInvoiceForm').on('submit', function() {
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