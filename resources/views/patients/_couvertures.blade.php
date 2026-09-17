{{-- Fiche patient : employeurs et couvertures d'assurance (module Assurance). --}}
@php
    $emploisPatient = $patient->emplois()->with('entreprise')->orderByRaw('date_fin IS NOT NULL')->orderByDesc('date_debut')->get();
    $couvertures = $patient->beneficiairesAssurance()
        ->with(['projection', 'adhesion.patient', 'adhesion.formule.contrat.organismePayeur', 'adhesion.formule.contrat.entreprise'])
        ->get()
        ->sortBy(fn ($b) => [($b->projection?->status === 'active') ? 0 : 1, $b->lien === \App\Enums\Assurance\LienBeneficiaire::Adherent ? 0 : 1]);
@endphp

<div class="col-md-12">
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h4 class="card-title">Assurance et employeur</h4>
            @can('assurance.referentiel.view')
                <a href="{{ route('assurance.droits.show', $patient->id) }}" class="btn btn-sm btn-primary ms-auto"><i class="fa fa-check-circle"></i> Vérifier les droits</a>
            @endcan
        </div>
        <div class="card-body">
            <h6 class="text-muted">Couvertures</h6>
            <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead><tr><th>Organisme payeur</th><th>Contrat</th><th>Couvert(e) en tant que</th><th>Taux</th><th>Validité</th><th>Statut</th></tr></thead>
                <tbody>
                @forelse($couvertures as $b)
                    @php $contratP = $b->adhesion->formule->contrat; $proj = $b->projection; @endphp
                    <tr class="{{ $proj?->status === 'active' ? '' : 'text-muted' }}">
                        <td>{{ $contratP->organismePayeur?->name }}</td>
                        <td class="small">
                            @can('assurance.referentiel.view')
                                <a href="{{ route('assurance.adhesions.show', $b->adhesion) }}">{{ $contratP->libelle ?: 'Police ' . $contratP->numero_police }}</a>
                            @else
                                {{ $contratP->libelle ?: 'Police ' . $contratP->numero_police }}
                            @endcan
                            @if($contratP->entreprise)<div class="text-muted">{{ $contratP->entreprise->nom }}</div>@endif
                        </td>
                        <td>
                            {{ $b->lien->libelle() }}
                            @if($b->lien !== \App\Enums\Assurance\LienBeneficiaire::Adherent)
                                <div class="small text-muted">de {{ $b->adhesion->patient->full_name }}</div>
                            @endif
                        </td>
                        <td>{{ rtrim(rtrim(number_format((float) $b->adhesion->formule->taux_prise_en_charge, 2, ',', ' '), '0'), ',') }} %</td>
                        <td class="small">{{ $proj?->start_date?->format('d/m/Y') }} → {{ $proj?->end_date?->format('d/m/Y') ?? '…' }}</td>
                        <td>
                            @switch($proj?->status)
                                @case('active') <span class="badge badge-success">Active</span> @break
                                @case('suspended') <span class="badge badge-warning">Suspendue</span> @break
                                @default <span class="badge badge-secondary">Terminée</span>
                            @endswitch
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted">Aucune couverture enregistrée.</td></tr>
                @endforelse
                </tbody>
            </table>
            </div>

            <h6 class="text-muted mt-3">Employeurs</h6>
            <ul class="list-unstyled small mb-0">
                @forelse($emploisPatient as $emploi)
                    <li class="{{ $emploi->estEnCours() ? '' : 'text-muted' }}">
                        @can('assurance.referentiel.view')
                            <a href="{{ route('assurance.entreprises.show', $emploi->entreprise) }}">{{ $emploi->entreprise->nom }}</a>
                        @else
                            {{ $emploi->entreprise->nom }}
                        @endcan
                        {{ $emploi->poste ? '— ' . $emploi->poste : '' }} {{ $emploi->matricule ? '(matricule ' . $emploi->matricule . ')' : '' }}
                        · {{ $emploi->estEnCours() ? 'en poste' : 'jusqu\'au ' . $emploi->date_fin->format('d/m/Y') }}
                    </li>
                @empty
                    <li class="text-muted">Aucun employeur enregistré.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
