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
            <a href="{{ route('patient.index') }}">Rapport</a>
          </li>
        </ul>
      </div>

        <div class="panel-body">
            <div class="row">
                <!-- Rapport Service -->
                {{-- <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card bg-primary text-white shadow-sm h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-users me-2"></i> Rapports par service</h5>
                            <span class="badge bg-light text-dark">Paiement</span>
                        </div>
                        <div class="card-body text-center">
                            <i class="fas fa-cubes fa-3x mb-3"></i>
                            <h6 class="fw-bold">Rapports par service</h6>
                            <small class="text-light">Paiement par service</small>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <a href="#" class="btn btn-light w-100 text-primary" data-bs-toggle="modal" data-bs-target="#ServiceModal">
                                Voir rapport <i class="fas fa-arrow-right float-end"></i>
                            </a>
                        </div>
                    </div>
                </div> --}}

                {{-- <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card text-white shadow-sm h-100" style="background-color: #14205b;">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i> Rapports Paie</h5>
                            <span class="badge bg-light text-dark">Paie</span>
                        </div>
                        <div class="card-body text-center">
                            <i class="fas fa-chart-bar fa-3x mb-3"></i>
                            <h6 class="fw-bold">Rapports Paie</h6>
                            <small class="text-light">Statistiques de paie</small>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <a href="#" class="btn btn-light w-100 text-primary" data-bs-toggle="modal" data-bs-target="#paiesModal">
                                Voir rapport <i class="fas fa-arrow-right float-end"></i>
                            </a>
                        </div>
                    </div>
                </div> --}}
                
                <!-- Rapport Consultation -->
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card text-white shadow-sm h-100" style="background-color: #00db5b;">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-cash-register me-2"></i> Rapports des actes</h5>
                            <span class="badge bg-light text-dark">Paiement</span>
                        </div>
                        <div class="card-body text-center">
                            <i class="fas fa-cash-register fa-3x mb-3"></i>
                            <h6 class="fw-bold">Rapports des actes</h6>
                            <small class="text-light">Revenus</small>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <a href="#" class="btn btn-light w-100 text-primary" data-bs-toggle="modal" data-bs-target="#rapportActesModal">
                                Voir rapport <i class="fas fa-arrow-right float-end"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Rapport Situation -->
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card text-white shadow-sm h-100" style="background-color: #2c3830;">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-cash-register me-2"></i> Rapports de situation</h5>
                            <span class="badge bg-light text-dark">Comptabilité</span>
                        </div>
                        <div class="card-body text-center">
                            <i class="fas fa-cash-register fa-3x mb-3"></i>
                            <h6 class="fw-bold">Rapports de situation</h6>
                            <small class="text-light">Revenus</small>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <a href="#" class="btn btn-light w-100 text-primary" data-bs-toggle="modal" data-bs-target="#rapportSituationModal">
                                Voir rapport <i class="fas fa-arrow-right float-end"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Rapport d'actes par assurance -->
                {{-- <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card text-white shadow-sm h-100" style="background-color: #5e6460;">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-cash-register me-2"></i> Rapports d'actes par assurance</h5>
                            <span class="badge bg-light text-dark">Comptabilité</span>
                        </div>
                        <div class="card-body text-center">
                            <i class="fas fa-cash-register fa-3x mb-3"></i>
                            <h6 class="fw-bold">Rapports d'actes par assurance</h6>
                            <small class="text-light">Revenus</small>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <a href="#" class="btn btn-light w-100 text-primary" data-bs-toggle="modal" data-bs-target="#rapportActsParAssuranceModal">
                                Voir rapport <i class="fas fa-arrow-right float-end"></i>
                            </a>
                        </div>
                    </div>
                </div> --}}

                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card text-white shadow-sm h-100" style="background-color: #a8ab85;">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-cash-register me-2"></i> Actes par assurance de détailles</h5>
                            <span class="badge bg-light text-dark">Comptabilité</span>
                        </div>
                        <div class="card-body text-center">
                            <i class="fas fa-cash-register fa-3x mb-3"></i>
                            <h6 class="fw-bold">Rapports d'actes par assurance détailles</h6>
                            <small class="text-light">Revenus</small>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <a href="#" class="btn btn-light w-100 text-primary" data-bs-toggle="modal" data-bs-target="#rapportActsParAssuranceDetailModal">
                                Voir rapport <i class="fas fa-arrow-right float-end"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card text-white shadow-sm h-100" style="background-color: #6018b9;">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-cash-register me-2"></i> Actes par assurance</h5>
                            <span class="badge bg-light text-dark">Comptabilité</span>
                        </div>
                        <div class="card-body text-center">
                            <i class="fas fa-cash-register fa-3x mb-3"></i>
                            <h6 class="fw-bold">Rapports d'actes par assurance</h6>
                            <small class="text-light">Revenus</small>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <a href="#" class="btn btn-light w-100 text-primary" data-bs-toggle="modal" data-bs-target="#rapportActsParAssuranceComModal">
                                Voir rapport <i class="fas fa-arrow-right float-end"></i>
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Service -->
        <div class="modal fade" id="ServiceModal" tabindex="-1" role="dialog">
            <form action="{{ route('service.report') }}" method="POST">
                @csrf
                @method('POST')

                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header d-flex justify-content-between align-items-center">
                            <h4 class="modal-title">Rapport par Service</h4>
                            <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>

                        <div class="modal-body py-4 px-4">
                            <div class="row mb-3">
                                <label for="department_id" class="col-md-3 col-form-label fw-semibold">Departement</label>
                                <div class="col-md-9">
                                    <select class="form-control selectpicker" name="department_id" id="department_id" data-live-search="true">
                                        <option value="all" selected>Toutes les departements</option>
                                        @foreach ($departments as $department)
                                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="service_id" class="col-md-3 col-form-label fw-semibold">Service</label>
                                <div class="col-md-9">
                                    <select class="form-control selectpicker" name="services[]" id="add_services" data-live-search="true" title="Sélectionnez les services" multiple>
                                        <option value="all" selected>Tous les services</option>
                                        @foreach ($services as $service)
                                            <option value="{{ $service->id }}">{{ $service->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="from" class="col-md-3 col-form-label fw-semibold">📅 De</label>
                                <div class="col-md-9">
                                    <input type="date" name="from" value="{{ Request::get('from') }}" class="form-control dateTime" placeholder="yyyy-mm-dd" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="to" class="col-md-3 col-form-label fw-semibold">📅 À</label>
                                <div class="col-md-9">
                                    <input type="date" name="to" value="{{ Request::get('to') }}" class="form-control dateTime" placeholder="yyyy-mm-dd" required>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer bg-light px-4 py-3">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            <button type="submit" class="btn btn-primary">✅ Valider</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Gestion des examens -->
        <div class="modal fade" id="paiesModal" tabindex="-1" role="dialog" aria-labelledby="houseBillModalLabel" aria-hidden="true">
            <form action="#" method="POST">
                @csrf
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content border-0 shadow-lg">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title" id="houseBillModalLabel">📄 Rapport par examens</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body py-4 px-4">
                            <div class="row mb-3">
                                <label for="department_id" class="col-md-3 col-form-label fw-semibold">Examen</label>
                                <div class="col-md-9">
                                    <select class="form-control selectpicker" name="examen_id" id="examen_id" data-live-search="true" multiple>
                                        <option value="all">Toutes les examens</option>
                                        @foreach ($examens as $examen)
                                            <option value="{{ $examen->id }}">{{ $examen->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="from" class="col-md-3 col-form-label fw-semibold">📅 De</label>
                                <div class="col-md-9">
                                    <input type="date" name="from" value="{{ Request::get('from') }}" class="form-control dateTime" placeholder="yyyy-mm-dd" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="to" class="col-md-3 col-form-label fw-semibold">📅 À</label>
                                <div class="col-md-9">
                                    <input type="date" name="to" value="{{ Request::get('to') }}" class="form-control dateTime" placeholder="yyyy-mm-dd" required>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer bg-light px-4 py-3">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            <button type="submit" class="btn btn-primary">✅ Valider</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Gestion des consultations -->
        <div class="modal fade" id="rapportActesModal" tabindex="-1" role="dialog" aria-labelledby="rapportActesModalLabel" aria-hidden="true">
            <form action="{{ route('reports.actes')}}" method="POST">
                @csrf
                @method('POST')
                <div class="modal-dialog modal-md" role="document">
                    <div class="modal-content border-0 shadow-md">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title" id="rapportActesModalLabel">📊 Rapport des actes</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body py-4 px-4">
                            <!-- Date de début -->
                            <div class="row mb-3">
                                <label for="from" class="col-md-3 col-form-label fw-semibold">📅 À partir de</label>
                                <div class="col-md-9">
                                    <input type="date" name="from" value="{{ Request::get('from') }}" class="form-control dateTime" placeholder="yyyy-mm-dd" required>
                                </div>
                            </div>

                            <!-- Date de fin -->
                            <div class="row mb-3">
                                <label for="to" class="col-md-3 col-form-label fw-semibold">📅 À</label>
                                <div class="col-md-9">
                                    <input type="date" name="to" value="{{ Request::get('to') }}" class="form-control dateTime" placeholder="yyyy-mm-dd">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-light px-4 py-3">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            <button type="submit" class="btn btn-primary">✅ Valider</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Gestion des situations -->
        <div class="modal fade" id="rapportSituationModal" tabindex="-1" role="dialog" aria-labelledby="rapportSituationModalLabel" aria-hidden="true">
            <form action="{{ route('rapports.situation')}}" method="GET">
                @csrf
                @method('GET')
                <div class="modal-dialog modal-md" role="document">
                    <div class="modal-content border-0 shadow-md">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title" id="rapportSituationModalLabel">📊 Rapport des situations</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body py-4 px-4">
                            <!-- Date de début -->
                            <div class="row mb-3">
                                <label for="from" class="col-md-3 col-form-label fw-semibold">📅 À partir de</label>
                                <div class="col-md-9">
                                    <input type="date" name="from" value="{{ Request::get('from') }}" class="form-control dateTime" placeholder="yyyy-mm-dd" required>
                                </div>
                            </div>

                            <!-- Date de fin -->
                            <div class="row mb-3">
                                <label for="to" class="col-md-3 col-form-label fw-semibold">📅 À</label>
                                <div class="col-md-9">
                                    <input type="date" name="to" value="{{ Request::get('to') }}" class="form-control dateTime" placeholder="yyyy-mm-dd">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-light px-4 py-3">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            <button type="submit" class="btn btn-primary">✅ Valider</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

         <!-- Liste des assurances par actes -->
        <div class="modal fade" id="rapportActsParAssuranceModal" tabindex="-1" role="dialog" aria-labelledby="rapportActsParAssuranceModalLabel" aria-hidden="true">
            <form action="{{ route('rapports.actes.assurance')}}" method="GET">
                @csrf
                @method('GET')
                <div class="modal-dialog modal-md" role="document">
                    <div class="modal-content border-0 shadow-md">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title" id="rapportActsParAssuranceModalLabel">📊 Rapport des actes par assurance</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body py-4 px-4">
                            <!-- Date de début -->
                            <div class="row mb-3">
                                <label for="from" class="col-md-3 col-form-label fw-semibold">📅 À partir de</label>
                                <div class="col-md-9">
                                    <input type="date" name="from" value="{{ Request::get('from') }}" class="form-control dateTime" placeholder="yyyy-mm-dd" required>
                                </div>
                            </div>

                            <!-- Date de fin -->
                            <div class="row mb-3">
                                <label for="to" class="col-md-3 col-form-label fw-semibold">📅 À</label>
                                <div class="col-md-9">
                                    <input type="date" name="to" value="{{ Request::get('to') }}" class="form-control dateTime" placeholder="yyyy-mm-dd">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-light px-4 py-3">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            <button type="submit" class="btn btn-primary">✅ Valider</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Liste des assurances par actes de détail -->
        <div class="modal fade" id="rapportActsParAssuranceDetailModal" tabindex="-1" role="dialog" aria-labelledby="rapportActsParAssuranceDetailModalLabel" aria-hidden="true">
            <form action="{{ route('rapports.actes.assurance.detail')}}" method="GET">
                @csrf
                @method('GET')
                <div class="modal-dialog modal-md" role="document">
                    <div class="modal-content border-0 shadow-md">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title" id="rapportActsParAssuranceDetailModalLabel">📊 Rapport des actes par assurance de détail</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body py-4 px-4">
                            <!-- Date de début -->
                            <div class="row mb-3">
                                <label for="from" class="col-md-3 col-form-label fw-semibold">📅 À partir de</label>
                                <div class="col-md-9">
                                    <input type="date" name="from" value="{{ Request::get('from') }}" class="form-control dateTime" placeholder="yyyy-mm-dd" required>
                                </div>
                            </div>

                            <!-- Date de fin -->
                            <div class="row mb-3">
                                <label for="to" class="col-md-3 col-form-label fw-semibold">📅 À</label>
                                <div class="col-md-9">
                                    <input type="date" name="to" value="{{ Request::get('to') }}" class="form-control dateTime" placeholder="yyyy-mm-dd">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-light px-4 py-3">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            <button type="submit" class="btn btn-primary">✅ Valider</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

         <!-- Liste des assures par compagnies -->
        <div class="modal fade" id="rapportActsParAssuranceComModal" tabindex="-1" role="dialog" aria-labelledby="rapportActsParAssuranceComModalLabel" aria-hidden="true">
            <form action="{{ route('rapports.bordereau.assurance')}}" method="GET">
                @csrf
                @method('GET')
                <div class="modal-dialog modal-md" role="document">
                    <div class="modal-content border-0 shadow-md">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title" id="rapportActsParAssuranceComModalLabel">📊 Rapport des actes par assurance de détail</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <input type="hidden" name="insurance_company_id" value="1">
                        <div class="modal-body py-4 px-4">
                            <!-- Date de début -->
                            <div class="row mb-3">
                                <label for="from" class="col-md-3 col-form-label fw-semibold">📅 À partir de</label>
                                <div class="col-md-9">
                                    <input type="date" name="from" value="{{ Request::get('from') }}" class="form-control dateTime" placeholder="yyyy-mm-dd" required>
                                </div>
                            </div>

                            <!-- Date de fin -->
                            <div class="row mb-3">
                                <label for="to" class="col-md-3 col-form-label fw-semibold">📅 À</label>
                                <div class="col-md-9">
                                    <input type="date" name="to" value="{{ Request::get('to') }}" class="form-control dateTime" placeholder="yyyy-mm-dd">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-light px-4 py-3">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            <button type="submit" class="btn btn-primary">✅ Valider</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    // Données des départements et services depuis Laravel
    const departments = @json($departments);
    const services = @json($services);
    const examens = @json($examens);

    $(document).ready(function() {
        // Initialiser les selectpickers
        $('.selectpicker').selectpicker();

        // Écouteur d'événement pour le changement de département
        $('#department_id').on('changed.bs.select', function() {
            const selectedDepartmentId = $(this).val();
            // Filtrer et mettre à jour les services
            updateServices(selectedDepartmentId);
        });

        // Fonction pour mettre à jour les examens
        function updateServices(departmentId) {
            const serviceSelect = $('#add_services');

            serviceSelect.selectpicker('destroy');
            serviceSelect.html('');
            serviceSelect.empty();
            serviceSelect[0].innerHTML = '';

            if (departmentId) {
                const filteredServices = services.filter(service =>
                    parseInt(service.department_id) === parseInt(departmentId)
                );

                filteredServices.forEach(service => {
                    serviceSelect.append(`<option value="${service.id}">${service.name}</option>`);
                });
            }

            serviceSelect.selectpicker({
                liveSearch: true,
                multipleSeparator: ', '
            });
        }
    });

</script>

@endsection

