@extends('layouts.backend')

@php $gnf = fn ($m) => number_format((float) $m, 0, ',', ' '); @endphp

@section('content')
<div class="container"><div class="page-inner">
    <div class="page-header"><h3 class="fw-bold mb-3">Envoyer des analyses</h3></div>

    @if(! $partenariat)
        <div class="alert alert-warning">Aucun laboratoire partenaire actif.</div>
    @else
        <form method="POST" action="{{ route('labo.reseau.store') }}">@csrf
            <input type="hidden" name="partenariat_id" value="{{ $partenariat->id }}">
            @if($consultation)<input type="hidden" name="consultation_id" value="{{ $consultation->id }}">@endif

            <div class="row">
                <div class="col-lg-5">
                    <div class="card"><div class="card-body">
                        <div class="mb-2">
                            <label class="form-label">Laboratoire</label>
                            <select class="form-control" onchange="window.location = '{{ route('labo.reseau.create') }}?partenariat_id=' + this.value + '{{ $patient ? '&patient_id=' . $patient->id : '' }}'">
                                @foreach($partenaires as $p)<option value="{{ $p->id }}" @selected($p->id === $partenariat->id)>{{ $p->laboratoire?->nom }}</option>@endforeach
                            </select>
                            <div class="form-text">{{ $partenariat->mode_facturation_defaut === 'partenaire' ? 'Les analyses vous seront facturées par le laboratoire.' : 'Le laboratoire encaisse directement le patient.' }}</div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Patient *</label>
                            @if($patient)
                                <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                                <div class="form-control-plaintext"><strong>{{ $patient->full_name }}</strong>
                                    <span class="text-muted">{{ $patient->gender }}{{ $patient->age !== null ? ', ' . $patient->age . ' ans' : '' }}</span></div>
                            @else
                                @include('assurance.partials.choix-patient', ['id' => 'labo-reseau', 'libelle' => 'Patient', 'url' => route('labo.reseau.patients.recherche')])
                            @endif
                        </div>

                        <div class="mb-2"><label class="form-label">Médecin prescripteur</label>
                            <select name="prescripteur_employee_id" class="form-control">
                                <option value="">—</option>
                                @foreach($medecins as $m)<option value="{{ $m->id }}" @selected($consultation && $consultation->medecin_id === $m->id)>Dr {{ $m->full_name }}</option>@endforeach
                            </select></div>

                        <div class="mb-2"><label class="form-label">Renseignements cliniques</label>
                            <textarea name="renseignements_cliniques" class="form-control" rows="3" placeholder="Utile au biologiste : symptômes, traitement en cours…">{{ $consultation?->diagnostic }}</textarea></div>

                        <div class="row g-2">
                            <div class="col-6"><input type="hidden" name="urgence" value="0">
                                <label class="small"><input type="checkbox" name="urgence" value="1"> Urgent</label></div>
                            <div class="col-6"><input type="hidden" name="grossesse" value="0">
                                <label class="small"><input type="checkbox" name="grossesse" value="1"> Patiente enceinte</label></div>
                            <div class="col-6"><input type="number" min="1" max="45" name="semaines_amenorrhee" class="form-control form-control-sm" placeholder="SA"></div>
                        </div>
                    </div></div>
                </div>

                <div class="col-lg-7">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Catalogue de {{ $partenariat->laboratoire?->nom }}</h4>
                            <form method="GET" class="d-flex gap-2 mt-2">
                                <input type="hidden" name="partenariat_id" value="{{ $partenariat->id }}">
                                @if($patient)<input type="hidden" name="patient_id" value="{{ $patient->id }}">@endif
                                @if($consultation)<input type="hidden" name="consultation_id" value="{{ $consultation->id }}">@endif
                                <input name="q" value="{{ $recherche }}" class="form-control form-control-sm" placeholder="Chercher un examen">
                                <button class="btn btn-sm btn-outline-primary">Chercher</button>
                            </form>
                        </div>
                        <div class="card-body table-responsive" style="max-height:460px">
                            <table class="table table-sm align-middle">
                                <thead><tr><th></th><th>Examen</th><th>Échantillon</th><th>Délai</th><th class="text-end">Prix</th></tr></thead>
                                <tbody>
                                @forelse($catalogue as $examen)
                                    <tr>
                                        <td><input type="checkbox" name="examens[]" value="{{ $examen['id'] }}" class="form-check-input"></td>
                                        <td>{{ $examen['nom'] }} <span class="small text-muted">{{ $examen['code'] }}</span>@if($examen['a_jeun'])<span class="badge badge-light">à jeun</span>@endif</td>
                                        <td class="small">{{ $examen['type_echantillon'] }}</td>
                                        <td class="small">{{ $examen['delai_heures'] }} h</td>
                                        <td class="text-end">{{ $gnf($examen['prix']) }} GNF</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-muted text-center">Aucun examen trouvé.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer d-flex gap-2">
                            <button class="btn btn-primary"><i class="fa fa-paper-plane"></i> Envoyer au laboratoire</button>
                            <a href="{{ route('labo.reseau.index') }}" class="btn btn-secondary">Annuler</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    @endif
</div></div>
@endsection

@section('script')
    @include('assurance.partials.choix-patient-script')
@endsection
