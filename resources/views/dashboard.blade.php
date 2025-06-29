@extends("layouts.backend")

@section("content")
<div class="container">
    <div class="page-inner">
      <div
        class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4"
      >
        <div>
          <h3 class="fw-bold mb-3">Dashboard Aprosafe</h3>
          {{-- <h6 class="op-7 mb-2"></h6> --}}
        </div>
        <div class="ms-md-auto py-2 py-md-0">
            <a href="{{ route('patient.index') }}" class="btn btn-primary btn-round">Ajouter une patiente</a>
            <a href="{{ route('consultation.create') }}" class="btn btn-label-info btn-round me-2">Nouvelle Consultation</a>
        </div>
      </div>
      <div class="row">
        <div class="col-sm-6 col-md-3">
          <div class="card card-stats card-round">
            <div class="card-body">
              <div class="row align-items-center">
                <div class="col-icon">
                  <div
                    class="icon-big text-center icon-primary bubble-shadow-small"
                  >
                    <i class="fas fa-users"></i>
                  </div>
                </div>
                <div class="col col-stats ms-3 ms-sm-0">
                  <div class="numbers">
                    <p class="card-category">Patiente</p>
                    <h4 class="card-title">{{ $total_patient }}</h4>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-md-3">
          <div class="card card-stats card-round">
            <div class="card-body">
              <div class="row align-items-center">
                <div class="col-icon">
                  <div
                    class="icon-big text-center icon-info bubble-shadow-small"
                  >
                    <i class="fas fa-user-check"></i>
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
        <div class="col-sm-6 col-md-3">
          <div class="card card-stats card-round">
            <div class="card-body">
              <div class="row align-items-center">
                <div class="col-icon">
                  <div
                    class="icon-big text-center icon-success bubble-shadow-small"
                  >
                    <i class="fas fa-luggage-cart"></i>
                  </div>
                </div>
                <div class="col col-stats ms-3 ms-sm-0">
                  <div class="numbers">
                    <p class="card-category">Total Transaction</p>
                    <h4 class="card-title">{{ number_format($transactions->sum('total')) }}</h4>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-md-3">
          <div class="card card-stats card-round">
            <div class="card-body">
              <div class="row align-items-center">
                <div class="col-icon">
                  <div
                    class="icon-big text-center icon-secondary bubble-shadow-small"
                  >
                    <i class="far fa-check-circle"></i>
                  </div>
                </div>
                <div class="col col-stats ms-3 ms-sm-0">
                  <div class="numbers">
                    <p class="card-category">Total Paiment</p>
                    <h4 class="card-title">{{ number_format($transactions->sum('montant_payer')) }}</h4>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-md-4">
          <div class="card card-round">
            <div class="card-body">
              <div class="card-head-row card-tools-still-right">
                <div class="card-title">Liste des patientes</div>
                <div class="card-tools">
                  <div class="dropdown">
                    <button
                      class="btn btn-icon btn-clean me-0"
                      type="button"
                      id="dropdownMenuButton"
                      data-bs-toggle="dropdown"
                      aria-haspopup="true"
                      aria-expanded="false"
                    >
                      <i class="fas fa-ellipsis-h"></i>
                    </button>
                    {{-- <div
                      class="dropdown-menu"
                      aria-labelledby="dropdownMenuButton"
                    >
                      <a class="dropdown-item" href="#">Action</a>
                      <a class="dropdown-item" href="#">Another action</a>
                      <a class="dropdown-item" href="#"
                        >Something else here</a
                      >
                    </div> --}}
                  </div>
                </div>
              </div>
              <div class="card-list py-4">

                @forelse ($patientes as $patient)
                <div class="item-list">
                    <div class="avatar">
                      <img
                        src="{{ asset('assets/img/jm_denis.jpg') }}"
                        alt="..."
                        class="avatar-img rounded-circle"
                      />
                    </div>
                    <div class="info-user ms-3">
                      <div class="username">{{ $patient->last_name." ".$patient->first_name." ( ".$patient->phone." )" }}</div>
                      <div class="status">{{ $patient->occupation }}</div>
                    </div>
                    <a href="{{ route('patient.show', $patient->id) }}" class="btn btn-icon btn-link op-8 me-1">
                        <i class="far fa-eye"></i>
                    </a>
                </div>

                @empty

                <div class="item-list">
                    <b>Vous n'avez pas creer des patients to day</b>
                </div>

                @endforelse
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-8">
          <div class="card card-round">
            <div class="card-header">
              <div class="card-head-row card-tools-still-right">
                <div class="card-title">Historique des transactions</div>
                <div class="card-tools">
                  <div class="dropdown">
                    <button
                      class="btn btn-icon btn-clean me-0"
                      type="button"
                      id="dropdownMenuButton"
                      data-bs-toggle="dropdown"
                      aria-haspopup="true"
                      aria-expanded="false"
                    >
                      <i class="fas fa-ellipsis-h"></i>
                    </button>
                    {{-- <div
                      class="dropdown-menu"
                      aria-labelledby="dropdownMenuButton"
                    >
                      <a class="dropdown-item" href="#">Action</a>
                      <a class="dropdown-item" href="#">Another action</a>
                      <a class="dropdown-item" href="#"
                        >Something else here</a
                      >
                    </div> --}}
                  </div>
                </div>
              </div>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <!-- Projects table -->
                <table class="table align-items-center mb-0">
                  <thead class="thead-light">
                    <tr>
                      <th>ID</th>
                      <th scope="col" class="text-end">Date</th>
                      <th scope="col" class="text-end">Montant</th>
                      <th scope="col" class="text-end">Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse ($transactions as $transaction)
                        <tr>
                            <th scope="row">
                                <button  class="btn btn-icon btn-round btn-success btn-sm me-2">
                                    <i class="fa fa-check"></i>
                                </button>
                                {{ $transaction->id }}
                            </th>
                            <td class="text-end">{{ $transaction->created_at->format('d M Y') }}</td>
                            <td class="text-end">{{ number_format($transaction->montant_payer) }} GNF</td>
                            <td class="text-end">
                                @if ($transaction->total == $transaction->montant_payer)
                                    <span class="badge badge-success">Complete</span>
                                @else
                                    <span class="badge badge-warning">Partiel</span>
                                @endif
                            </td>
                        </tr>
                    @empty

                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
