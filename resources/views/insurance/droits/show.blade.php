@extends('layouts.backend')

@php
    $gnf = fn ($m) => number_format((float) $m, 0, ',', ' ') . ' GNF';
    $pct = fn ($t) => rtrim(rtrim(number_format((float) $t, 2, ',', ' '), '0'), ',') . ' %';
@endphp

@section('content')
<div class="container"><div class="page-inner">
    @include('assurance.partials.entete', [
        'titre' => 'Droits de ' . $patient->full_name,
        'fil' => [route('patient.show', $patient->id) => $patient->full_name, 0 => 'Vérification des droits'],
    ])

    <p class="text-muted">Situation au {{ $droits['date']->format('d/m/Y') }}. Les couvertures s'appliquent dans l'ordre ci-dessous :
        chacune prend en charge sa part sur ce qui reste après la précédente ; le solde est à la charge du patient.</p>

    @forelse($droits['actives'] as $ligne)
        @php $c = $ligne['couverture']; @endphp
        <div class="card">
            <div class="card-header d-flex flex-wrap align-items-center gap-2">
                <span class="badge badge-primary">{{ $ligne['rang'] }}</span>
                <h4 class="card-title mb-0">{{ $c->organisme->name }}</h4>
                @if($c->estComplementEmployeur())<span class="badge badge-info">complément employeur</span>@endif
                <span class="small text-muted ms-auto">
                    @if($c->contrat)
                        {{ $c->contrat->libelle ?: 'Police ' . $c->contrat->numero_police }} — {{ $c->formule->libelle }}
                    @else
                        Police {{ $c->projection->policy_number }}
                    @endif
                </span>
            </div>
            <div class="card-body">
                <div class="row small mb-3">
                    <div class="col-md-4">
                        <strong>Couvert(e) en tant que :</strong>
                        {{ $c->beneficiaire ? $c->beneficiaire->lien->libelle() : 'assuré(e)' }}
                        @if($c->estAyantDroit())<br><span class="text-muted">de {{ $c->beneficiaire->adhesion->patient->full_name }}</span>@endif
                        <br><strong>N° de carte :</strong> {{ $c->projection->policy_number }}
                        <br><strong>Droits :</strong> {{ $c->projection->start_date->format('d/m/Y') }} → {{ $c->projection->end_date?->format('d/m/Y') ?? '…' }}
                    </div>
                    <div class="col-md-4">
                        <strong>Exercice :</strong> {{ $ligne['exercice'][0]->format('d/m/Y') }} → {{ $ligne['exercice'][1]->format('d/m/Y') }}
                        <br><strong>Plafond bénéficiaire :</strong>
                        @if($ligne['plafond_beneficiaire'] === null) illimité
                        @else reste <span class="{{ $ligne['reste_beneficiaire'] <= 0 ? 'text-danger fw-bold' : '' }}">{{ $gnf($ligne['reste_beneficiaire']) }}</span> / {{ $gnf($ligne['plafond_beneficiaire']) }}
                        @endif
                        @if($ligne['plafond_famille'] !== null)
                            <br><strong>Plafond familial :</strong> reste <span class="{{ $ligne['reste_famille'] <= 0 ? 'text-danger fw-bold' : '' }}">{{ $gnf($ligne['reste_famille']) }}</span> / {{ $gnf($ligne['plafond_famille']) }}
                        @endif
                    </div>
                    <div class="col-md-4">
                        <strong>Bons de prise en charge valides :</strong>
                        <ul class="list-unstyled mb-0">
                            @forelse($ligne['bons'] as $bon)
                                <li>N° {{ $bon->numero }} — {{ $bon->famille_acte?->libelle() ?? 'tous actes' }}
                                    · {{ $bon->montant_accorde === null ? 'sans plafond' : 'reste ' . $gnf($bon->resteDisponible()) }}
                                    · jusqu'au {{ $bon->date_fin->format('d/m/Y') }}
                                    @can('assurance.referentiel.manage')
                                        @unless($bon->utilisations()->exists())
                                            <form method="POST" action="{{ route('assurance.bons.annuler', $bon) }}" class="d-inline" onsubmit="return confirm('Annuler ce bon ?');">@csrf
                                                <button class="btn btn-link btn-sm p-0 text-danger">annuler</button></form>
                                        @endunless
                                    @endcan
                                </li>
                            @empty
                                <li class="text-muted">Aucun</li>
                            @endforelse
                        </ul>
                    </div>
                </div>

                <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle small mb-0">
                    <thead><tr><th>Famille d'actes</th><th>Prise en charge</th><th>Plafond par acte</th><th>Conditions</th></tr></thead>
                    <tbody>
                    @foreach($ligne['garanties'] as $g)
                        <tr class="{{ $g['exclu'] ? 'text-muted' : '' }}">
                            <td>{{ $g['famille']->libelle() }}</td>
                            <td>{{ $g['exclu'] ? 'Exclu' : $pct($g['taux']) }}</td>
                            <td>{{ $g['plafond_par_acte'] !== null ? $gnf($g['plafond_par_acte']) : '—' }}</td>
                            <td>@if($g['accord_prealable'])<span class="badge badge-warning">Accord préalable</span>@endif</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                </div>
                <p class="small text-muted mt-2 mb-0">Un acte n'est pris en charge que s'il figure dans la convention tarifaire de {{ $c->organisme->name }}.</p>

                @can('assurance.referentiel.manage')
                    @if($c->beneficiaire)
                        <details class="mt-3">
                            <summary class="text-primary" style="cursor:pointer">Enregistrer un bon de prise en charge</summary>
                            <form method="POST" action="{{ route('assurance.bons.store', $c->beneficiaire) }}" class="row g-2 mt-1">@csrf
                                <div class="col-md-3"><label class="form-label">N° du bon *</label><input name="numero" class="form-control form-control-sm" required maxlength="100"></div>
                                <div class="col-md-3"><label class="form-label">Actes concernés</label>
                                    <select name="famille_acte" class="form-control form-control-sm">
                                        <option value="">Tous les actes</option>
                                        @foreach(\App\Enums\Assurance\FamilleActe::cases() as $f)<option value="{{ $f->value }}">{{ $f->libelle() }}</option>@endforeach
                                    </select></div>
                                <div class="col-md-2"><label class="form-label">Montant accordé</label><input type="number" min="0" step="1" name="montant_accorde" class="form-control form-control-sm" placeholder="Sans plafond"></div>
                                <div class="col-md-2"><label class="form-label">Valable du *</label><input type="date" name="date_debut" class="form-control form-control-sm" required value="{{ today()->toDateString() }}"></div>
                                <div class="col-md-2"><label class="form-label">au *</label><input type="date" name="date_fin" class="form-control form-control-sm" required value="{{ today()->addDays(30)->toDateString() }}"></div>
                                <div class="col-md-10"><input name="notes" class="form-control form-control-sm" placeholder="Notes (motif, médecin conseil…)"></div>
                                <div class="col-md-2"><button class="btn btn-sm btn-primary w-100">Enregistrer</button></div>
                            </form>
                        </details>
                    @endif
                @endcan
            </div>
        </div>
    @empty
        <div class="alert alert-warning">Aucune couverture active aujourd'hui : tous les actes sont à la charge du patient.</div>
    @endforelse

    @if($droits['inactives'])
        <div class="card">
            <div class="card-header"><h4 class="card-title">Couvertures qui ne s'appliquent pas aujourd'hui</h4></div>
            <ul class="list-group list-group-flush small">
                @foreach($droits['inactives'] as $i)
                    <li class="list-group-item">
                        <strong>{{ $i['ligne']->insuranceCompany?->name }}</strong> — police {{ $i['ligne']->policy_number }}
                        @if($i['ligne']->beneficiaire && $i['ligne']->beneficiaire->lien !== \App\Enums\Assurance\LienBeneficiaire::Adherent)
                            ({{ mb_strtolower($i['ligne']->beneficiaire->lien->libelle()) }} de {{ $i['ligne']->beneficiaire->adhesion->patient->full_name }})
                        @endif
                        : <span class="text-muted">{{ $i['raison'] }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div></div>
@endsection
