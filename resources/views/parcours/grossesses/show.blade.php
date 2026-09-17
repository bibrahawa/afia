@extends('layouts.backend')

@php
    $badges = ['faite' => 'success', 'a_venir' => 'light', 'a_programmer' => 'warning', 'manquee' => 'danger'];
    $libelles = ['faite' => 'Faite', 'a_venir' => 'À venir', 'a_programmer' => 'À programmer', 'manquee' => 'Manquée'];
@endphp

@section('content')
<div class="container"><div class="page-inner">
    <div class="page-header d-flex flex-wrap align-items-center gap-2">
        <h3 class="fw-bold mb-0">Grossesse — {{ $grossesse->patient->full_name }}</h3>
        <span class="badge badge-{{ $grossesse->estEnCours() ? 'success' : 'secondary' }}">
            {{ $grossesse->estEnCours() ? $grossesse->termeLisible() : (\App\Models\Parcours\Grossesse::ISSUES[$grossesse->issue] ?? 'Clôturée') }}
        </span>
        <a href="{{ route('parcours.dossier.show', $grossesse->patient_id) }}" class="btn btn-sm btn-outline-secondary ms-auto">Dossier</a>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card"><div class="card-body small">
                <p class="mb-1"><strong>DDR :</strong> {{ $grossesse->ddr->format('d/m/Y') }}</p>
                <p class="mb-1"><strong>Accouchement prévu :</strong> {{ $grossesse->dpa->format('d/m/Y') }}</p>
                <p class="mb-1"><strong>Gestité / parité :</strong> {{ $grossesse->gestite ?? '—' }} / {{ $grossesse->parite ?? '—' }}</p>
                <p class="mb-1"><strong>Médecin :</strong> {{ $grossesse->medecin ? 'Dr ' . $grossesse->medecin->full_name : '—' }}</p>
                @if($grossesse->date_issue)<p class="mb-1"><strong>Issue :</strong> {{ \App\Models\Parcours\Grossesse::ISSUES[$grossesse->issue] ?? $grossesse->issue }} le {{ $grossesse->date_issue->format('d/m/Y') }}</p>@endif
                @if($grossesse->notes)<p class="text-muted mb-0">{{ $grossesse->notes }}</p>@endif
            </div>
            @can('parcours.grossesse')
                @if($grossesse->estEnCours())
                    <div class="card-footer small">
                        <form method="POST" action="{{ route('parcours.grossesses.ddr', $grossesse) }}" class="d-flex gap-1 mb-2">@csrf
                            <input type="date" name="ddr" class="form-control form-control-sm" value="{{ $grossesse->ddr->toDateString() }}" max="{{ today()->toDateString() }}" required>
                            <button class="btn btn-sm btn-outline-primary text-nowrap">Corriger la DDR</button>
                        </form>
                        <form method="POST" action="{{ route('parcours.grossesses.cloturer', $grossesse) }}" class="row g-1"
                              onsubmit="return confirm('Clôturer ce suivi de grossesse ?');">@csrf
                            <div class="col-6"><select name="issue" class="form-control form-control-sm">
                                @foreach(\App\Models\Parcours\Grossesse::ISSUES as $valeur => $libelle)<option value="{{ $valeur }}">{{ $libelle }}</option>@endforeach
                            </select></div>
                            <div class="col-6"><input type="date" name="date_issue" class="form-control form-control-sm" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" required></div>
                            <div class="col-12"><button class="btn btn-sm btn-outline-danger w-100">Clôturer le suivi</button></div>
                        </form>
                    </div>
                @endif
            @endcan
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h4 class="card-title">Calendrier des consultations prénatales</h4></div>
                <div class="card-body table-responsive">
                    <table class="table table-sm align-middle small">
                        <thead><tr><th>Contact</th><th>Date cible</th><th>Statut</th><th>Consultation</th></tr></thead>
                        <tbody>
                        @foreach($calendrier as $contact)
                            <tr>
                                <td>{{ $contact['semaines'] }} SA</td>
                                <td>{{ $contact['date_cible']->format('d/m/Y') }}</td>
                                <td><span class="badge badge-{{ $badges[$contact['statut']] }}">{{ $libelles[$contact['statut']] }}</span></td>
                                <td>
                                    @if($contact['consultation'])
                                        <a href="{{ route('consultation.show', $contact['consultation']) }}">{{ $contact['consultation']->created_at->format('d/m/Y') }} — {{ \Illuminate\Support\Str::limit($contact['consultation']->diagnostic, 40) }}</a>
                                    @else — @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <p class="small text-muted mb-0">Calendrier indicatif (8 contacts recommandés par l'OMS). Une consultation dans les deux semaines autour de la date cible honore le contact.</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h4 class="card-title">Poids et tension</h4></div>
                <div class="card-body table-responsive">
                    <table class="table table-sm small">
                        <thead><tr><th>Date</th><th>Terme</th><th>Poids</th><th>Tension</th></tr></thead>
                        <tbody>
                        @forelse($mesures as $m)
                            <tr>
                                <td>{{ $m['date']->format('d/m/Y') }}</td>
                                <td>{{ $m['terme'] }}</td>
                                <td>{{ $m['poids'] !== null ? rtrim(rtrim(number_format($m['poids'], 2, ',', ''), '0'), ',') . ' kg' : '—' }}</td>
                                <td>{{ $m['tension'] ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-muted">Aucune constante relevée depuis le début de la grossesse.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div></div>
@endsection
