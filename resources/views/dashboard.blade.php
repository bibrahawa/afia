@extends("layouts.backend")

@section("content")
<div class="container">
    <div class="container-fluid">
    <div class="page-inner">
        <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <div class="flex-grow-1">
                <h3 class="fw-bold mb-3">Tableau de bord — {{ \App\Support\Etablissement\IdentiteDocument::courante()->nom }}</h3>
                <h6 class="op-7 mb-2 d-none d-md-block">Tableau de bord médical - {{ now()->format('d M Y') }}</h6>
            </div>
            <div class="ms-md-auto py-2 py-md-0 d-flex flex-wrap gap-2">
                <a href="{{ route('patient.index') }}" class="btn btn-primary btn-round btn-sm">Nouveau Patient</a>
                <a href="{{ route('consultation.create') }}" class="btn btn-label-info btn-round btn-sm">Nouvelle Consultation</a>
            </div>
        </div>

        <!-- Statistiques principales -->
        <div class="row">
            <div class="col-6 col-sm-6 col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-primary bubble-shadow-small">
                                    <i class="fas fa-user-injured"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Total Patients</p>
                                    <h4 class="card-title">{{ number_format($total_patient ?? 0) }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-6 col-sm-6 col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-info bubble-shadow-small">
                                    <i class="fas fa-stethoscope"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Consultations</p>
                                    <h4 class="card-title">{{ number_format($consultations->count(), 0, ',', ' ') }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-sm-6 col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-success bubble-shadow-small">
                                    <i class="fas fa-file-invoice-dollar"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Chiffre d'Affaires</p>
                                    <h4 class="card-title">{{ number_format($transactions->sum('total')) }} GNF</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-sm-6 col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-secondary bubble-shadow-small">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Paiements Reçus</p>
                                    <h4 class="card-title">{{ number_format($transactions->sum('montant_payer')) }} GNF</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiques secondaires -->
        <div class="row">
            <div class="col-6 col-sm-6 col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-warning bubble-shadow-small">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">RDV Aujourd'hui</p>
                                    <h4 class="card-title">{{ $rdv_today ?? 0 }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-sm-6 col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-danger bubble-shadow-small">
                                    <i class="fas fa-hospital"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Hospitalisés</p>
                                    <h4 class="card-title">{{ $hospitalisations_active ?? 0 }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-sm-6 col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-primary bubble-shadow-small">
                                    <i class="fas fa-bed"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Chambres Libres</p>
                                    <h4 class="card-title">{{ $chambres_libres ?? 0 }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-sm-6 col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-success bubble-shadow-small">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Patients Assurés</p>
                                    <h4 class="card-title">{{ $patients_assures ?? 0 }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenu principal -->
        <div class="row">
            <!-- Liste des patients récents -->
            <div class="col-md-4 col-12 mb-4">
                <div class="card card-round h-100">
                    <div class="card-body">
                        <div class="card-head-row card-tools-still-right">
                            <div class="card-title">Patients Récents</div>
                            <div class="card-tools">
                                <a href="{{ route('patient.index') }}" class="btn btn-label-success btn-round btn-sm">
                                    <span class="btn-label">
                                        <i class="fa fa-plus"></i>
                                    </span>
                                    Voir Tous
                                </a>
                            </div>
                        </div>
                        <div class="card-list py-4">
                            @forelse ($patientes as $patient)
                            <div class="item-list">
                                <div class="avatar">
                                    <span class="avatar-title rounded-circle border border-white bg-primary">
                                        {{ strtoupper(substr($patient->first_name, 0, 1)) }}{{ strtoupper(substr($patient->last_name, 0, 1)) }}
                                    </span>
                                </div>
                                <div class="info-user ms-3">
                                    <div class="username">{{ $patient->last_name." ".$patient->first_name }}</div>
                                    <div class="status">{{ $patient->phone }} • {{ $patient->occupation ?? 'N/A' }}</div>
                                </div>
                                <a href="{{ route('patient.show', $patient->id) }}" class="btn btn-icon btn-link op-8 me-1">
                                    <i class="far fa-eye"></i>
                                </a>
                            </div>
                            @empty
                            <div class="item-list">
                                <div class="text-center">
                                    <i class="fas fa-user-plus fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Aucun patient enregistré aujourd'hui</p>
                                </div>
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rendez-vous du jour -->
            <div class="col-md-4 col-12 mb-4">
                <div class="card card-round h-100">
                    <div class="card-body">
                        <div class="card-head-row card-tools-still-right">
                            <div class="card-title">Rendez-vous du Jour</div>
                            <div class="card-tools">
                                <a href="{{ route('medecin.appointments') }}" class="btn btn-label-info btn-round btn-sm">
                                    <span class="btn-label">
                                        <i class="fa fa-calendar"></i>
                                    </span>
                                    Planning
                                </a>
                            </div>
                        </div>
                        <div class="card-list py-4">
                            @forelse ($rdv_aujourdhui ?? [] as $rdv)
                            <div class="item-list">
                                <div class="avatar">
                                    <div class="avatar-title rounded-circle bg-info">
                                        <i class="fas fa-clock text-white"></i>
                                    </div>
                                </div>
                                <div class="info-user ms-3">
                                    <div class="username">{{ $rdv->heure ?? '09:00' }}</div>
                                    <div class="status">{{ $rdv->patient_nom ?? 'Patient' }} - Dr. {{ $rdv->medecin_nom ?? 'Médecin' }}</div>
                                </div>
                                <span class="badge badge-warning">{{ $rdv->status ?? 'Prévu' }}</span>
                            </div>
                            @empty
                            <div class="item-list">
                                <div class="text-center">
                                    <i class="fas fa-calendar-check fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Aucun rendez-vous programmé</p>
                                </div>
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alertes et notifications -->
            <div class="col-md-4 col-12 mb-4">
                <div class="card card-round h-100">
                    <div class="card-body">
                        <div class="card-head-row card-tools-still-right">
                            <div class="card-title">Alertes Médicales</div>
                            <div class="card-tools">
                                <div class="dropdown">
                                    <button class="btn btn-icon btn-clean me-0" type="button" id="alertsDropdown" data-bs-toggle="dropdown">
                                        <i class="fas fa-bell"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-list py-4">
                            <!-- Paiements en attente -->
                            <div class="item-list">
                                <div class="avatar">
                                    <div class="avatar-title rounded-circle bg-danger">
                                        <i class="fas fa-exclamation text-white"></i>
                                    </div>
                                </div>
                                <div class="info-user ms-3">
                                    <div class="username">{{ $factures_impayees ?? 0 }} Factures impayées</div>
                                    <div class="status">Total: {{ number_format($montant_impaye ?? 0) }} GNF</div>
                                </div>
                                <a href="{{ route('account.facture') }}" class="btn btn-icon btn-link op-8 me-1">
                                    <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>

                            <!-- Stock médicaments -->
                            <div class="item-list">
                                <div class="avatar">
                                    <div class="avatar-title rounded-circle bg-warning">
                                        <i class="fas fa-pills text-white"></i>
                                    </div>
                                </div>
                                <div class="info-user ms-3">
                                    <div class="username">Stock faible</div>
                                    <div class="status">{{ $medicaments_stock_faible ?? 0 }} médicaments</div>
                                </div>
                                <a href="{{ route('medicaments.index') }}" class="btn btn-icon btn-link op-8 me-1">
                                    <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>

                            <!-- Réclamations en attente -->
                            <div class="item-list">
                                <div class="avatar">
                                    <div class="avatar-title rounded-circle bg-info">
                                        <i class="fas fa-file-medical text-white"></i>
                                    </div>
                                </div>
                                <div class="info-user ms-3">
                                    <div class="username">Réclamations assurance</div>
                                    <div class="status">{{ $reclamations_en_attente ?? 0 }} en attente</div>
                                </div>
                                <a href="{{ route('insurance-claims.index') }}" class="btn btn-icon btn-link op-8 me-1">
                                    <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Historique des transactions -->
        <div class="row">
            <div class="col-md-12 col-12 mb-4">
                <div class="card card-round">
                    <div class="card-header">
                        <div class="card-head-row card-tools-still-right">
                            <div class="card-title">Historique des Transactions</div>
                            <div class="card-tools">
                                <a href="{{ route('invoice.index') }}" class="btn btn-label-primary btn-round btn-sm">
                                    <span class="btn-label">
                                        <i class="fa fa-file-invoice"></i>
                                    </span>
                                    Toutes les Factures
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table align-items-center mb-0 table-sm">
                                <thead class="thead-light">
                                    <tr>
                                        <th class="d-none d-md-table-cell">ID</th>
                                        <th class="d-none d-lg-table-cell">Patient</th>
                                        <th class="d-none d-lg-table-cell">Type</th>
                                        <th scope="col" class="text-end">Date</th>
                                        <th scope="col" class="text-end d-none d-sm-table-cell">Total</th>
                                        <th scope="col" class="text-end d-none d-md-table-cell">Payé</th>
                                        <th scope="col" class="text-end">Statut</th>
                                        <th scope="col" class="text-end d-none d-lg-table-cell">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($transactions as $transaction)
                                        <tr>
                                            <th scope="row" class="d-none d-md-table-cell">
                                                <button class="btn btn-icon btn-round btn-success btn-xs me-1">
                                                    <i class="fa fa-check"></i>
                                                </button>
                                                <span class="d-none d-lg-inline">#{{ str_pad($transaction->id, 4, '0', STR_PAD_LEFT) }}</span>
                                            </th>
                                            <td class="d-none d-lg-table-cell">
                                                <div class="d-flex align-items-center">
                                                    {{-- <div class="avatar avatar-xs me-2">
                                                        <span class="avatar-title rounded-circle bg-light text-dark">
                                                            {{ strtoupper(substr($transaction->patient->first_name ?? 'N', 0, 1)) }}
                                                        </span>
                                                    </div> --}}
                                                    <span class="text-truncate" style="max-width: 120px;">{{ $transaction->patient->first_name ?? 'N/A' }} {{ $transaction->patient->last_name ?? 'N/A' }}</span>
                                                </div>
                                            </td>
                                            <td class="d-none d-lg-table-cell">
                                                <span class="badge badge-info badge-sm">{{ $transaction->type ?? 'Consultation' }}</span>
                                            </td>
                                            <td class="text-end">
                                                <div class="d-flex flex-column">
                                                    <small class="fw-bold">{{ $transaction->created_at->format('d/m') }}</small>
                                                    <small class="text-muted d-none d-sm-inline">{{ $transaction->created_at->format('H:i') }}</small>
                                                </div>
                                            </td>
                                            <td class="text-end d-none d-sm-table-cell">
                                                <strong>{{ number_format($transaction->total) }}</strong>
                                                <small class="text-muted d-block">GNF</small>
                                            </td>
                                            <td class="text-end d-none d-md-table-cell">
                                                {{ number_format($transaction->montant_payer) }}
                                                <small class="text-muted d-block">GNF</small>
                                            </td>
                                            <td class="text-end">
                                                @if ($transaction->total == $transaction->montant_payer)
                                                    <span class="badge badge-success badge-sm">Complète</span>
                                                @elseif($transaction->montant_payer > 0)
                                                    <span class="badge badge-warning badge-sm">Partielle</span>
                                                @else
                                                    <span class="badge badge-danger badge-sm">Impayée</span>
                                                @endif
                                            </td>
                                            <td class="text-end d-none d-lg-table-cell">
                                                <div class="dropdown">
                                                    <button class="btn btn-icon btn-clean btn-sm me-0" type="button" data-bs-toggle="dropdown">
                                                        <i class="fas fa-ellipsis-h"></i>
                                                    </button>
                                                    <div class="dropdown-menu dropdown-menu-end">
                                                        <a class="dropdown-item" href="#"><i class="fas fa-eye me-2"></i> Voir</a>
                                                        <a class="dropdown-item" href="#"><i class="fas fa-edit me-2"></i> Modifier</a>
                                                        <a class="dropdown-item" href="#"><i class="fas fa-print me-2"></i> Imprimer</a>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-4">
                                                <i class="fas fa-file-invoice fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">Aucune transaction enregistrée</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Graphiques (à ajouter si nécessaire) -->
        <div class="row mt-4">
            <div class="col-lg-6 col-md-12 col-12 mb-4">
                <div class="card card-round h-100">
                    <div class="card-header">
                        <div class="card-title">Revenus Mensuels</div>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="min-height: 375px">
                            <canvas id="statisticsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-12 col-12 mb-4">
                <div class="card card-round h-100">
                    <div class="card-header">
                        <div class="card-title">Répartition des Consultations</div>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="min-height: 375px">
                            <canvas id="consultationsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection