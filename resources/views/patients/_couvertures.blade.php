{{-- Fiche patient : employeurs et couvertures d'assurance (module Assurance). --}}
@php
    $emploisPatient = $patient->emplois()->with('entreprise')->orderByRaw('date_fin IS NOT NULL')->orderByDesc('date_debut')->get();
    $couvertures = $patient->beneficiairesAssurance()
        ->with(['projection', 'adhesion.patient', 'adhesion.formule.contrat.organismePayeur', 'adhesion.formule.contrat.entreprise'])
        ->get()
        ->sortBy(fn ($b) => [($b->projection?->status === 'active') ? 0 : 1, $b->lien === \App\Enums\Assurance\LienBeneficiaire::Adherent ? 0 : 1]);
@endphp

<section class="hl-bloc" id="assurance">
    <h2 class="hl-bloc-titre" style="flex-wrap:wrap">
        Assurance et employeur
        @can('assurance.referentiel.view')
            <a href="{{ route('assurance.droits.show', $patient->id) }}" class="hl-bouton" style="margin-left:auto; font-size:.82rem; min-height:32px">
                <i class="fa fa-check-circle" aria-hidden="true"></i> Vérifier les droits
            </a>
        @endcan
    </h2>

    @if($couvertures->isEmpty())
        <div class="hl-vide" style="padding:24px">
            <i class="fas fa-shield-alt" aria-hidden="true"></i>
            Aucune couverture d'assurance : le patient paie la totalité de ses soins.
        </div>
    @else
        <div class="table-responsive">
            <table class="fp-table">
                <thead><tr><th>Organisme</th><th>Contrat</th><th>Couvert(e) en tant que</th><th class="fp-montant">Prise en charge</th><th>Validité</th><th>Statut</th></tr></thead>
                <tbody>
                @foreach($couvertures as $b)
                    @php $contratP = $b->adhesion->formule->contrat; $proj = $b->projection; $active = $proj?->status === 'active'; @endphp
                    <tr style="{{ $active ? '' : 'opacity:.65' }}">
                        <td><strong style="color:var(--hali-encre)">{{ $contratP->organismePayeur?->name }}</strong></td>
                        <td>
                            @can('assurance.referentiel.view')
                                <a href="{{ route('assurance.adhesions.show', $b->adhesion) }}">{{ $contratP->libelle ?: 'Police ' . $contratP->numero_police }}</a>
                            @else
                                {{ $contratP->libelle ?: 'Police ' . $contratP->numero_police }}
                            @endcan
                            @if($contratP->entreprise)<span style="display:block; color:var(--hali-discret); font-size:.8rem">{{ $contratP->entreprise->nom }}</span>@endif
                        </td>
                        <td>
                            {{ $b->lien->libelle() }}
                            @if($b->lien !== \App\Enums\Assurance\LienBeneficiaire::Adherent)
                                <span style="display:block; color:var(--hali-discret); font-size:.8rem">de {{ $b->adhesion->patient->full_name }}</span>
                            @endif
                        </td>
                        <td class="fp-montant"><strong>{{ rtrim(rtrim(number_format((float) $b->adhesion->formule->taux_prise_en_charge, 2, ',', ' '), '0'), ',') }} %</strong></td>
                        <td style="white-space:nowrap; font-variant-numeric:tabular-nums">{{ $proj?->start_date?->format('d/m/Y') }} → {{ $proj?->end_date?->format('d/m/Y') ?? 'sans fin' }}</td>
                        <td>
                            @switch($proj?->status)
                                @case('active') <span class="hl-statut hl-s-succes">Active</span> @break
                                @case('suspended') <span class="hl-statut hl-s-alerte">Suspendue</span> @break
                                @default <span class="hl-statut hl-s-neutre">Terminée</span>
                            @endswitch
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div style="padding:14px 18px; border-top:1px solid var(--hali-bordure)">
        <span style="display:block; margin-bottom:6px; color:var(--hali-discret); font-size:.8rem; font-weight:600">Employeurs</span>
        @forelse($emploisPatient as $emploi)
            <div style="padding:4px 0; font-size:.88rem; {{ $emploi->estEnCours() ? '' : 'color:var(--hali-discret)' }}">
                @can('assurance.referentiel.view')
                    <a href="{{ route('assurance.entreprises.show', $emploi->entreprise) }}" style="font-weight:600">{{ $emploi->entreprise->nom }}</a>
                @else
                    <strong>{{ $emploi->entreprise->nom }}</strong>
                @endcan
                {{ $emploi->poste ? '· ' . $emploi->poste : '' }} {{ $emploi->matricule ? '· matricule ' . $emploi->matricule : '' }}
                · {{ $emploi->estEnCours() ? 'en poste' : 'jusqu\'au ' . $emploi->date_fin->format('d/m/Y') }}
            </div>
        @empty
            <span style="color:#9ca3af; font-size:.88rem">Aucun employeur enregistré.</span>
        @endforelse
    </div>
</section>
