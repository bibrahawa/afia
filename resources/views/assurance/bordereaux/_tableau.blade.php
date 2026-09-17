{{-- Tableau du bordereau (écran et impression). $bordereau, $impression --}}
@php $gnf = fn ($m) => number_format((float) $m, 0, ',', ' '); @endphp
<table class="table table-sm table-bordered align-middle small">
    <thead>
        <tr><th>N°</th><th>Bénéficiaire</th><th>Assuré principal / carte</th><th>Date</th><th>Actes</th><th class="text-end">Montant actes</th><th class="text-end">Réclamé</th>@unless($impression)<th></th>@endunless</tr>
    </thead>
    <tbody>
    @foreach($bordereau->reclamations as $c)
        @php
            $beneficiaire = $c->patientInsurance?->beneficiaire;
            $adherent = $beneficiaire?->adhesion?->patient;
        @endphp
        <tr>
            <td>@if($impression){{ $c->claim_number }}@else<a href="{{ route('assurance.reclamations.show', $c) }}">{{ $c->claim_number }}</a>
                    @unless($c->pieces->contains('type', 'feuille_soins'))<div><span class="badge badge-warning">feuille de soins manquante</span></div>@endunless
                @endif</td>
            <td>{{ $c->invoice?->transaction?->patient?->full_name }}
                @if($beneficiaire && $beneficiaire->lien !== \App\Enums\Assurance\LienBeneficiaire::Adherent)<div class="text-muted">{{ $beneficiaire->lien->libelle() }}</div>@endif</td>
            <td>{{ $adherent?->full_name ?? $c->invoice?->transaction?->patient?->full_name }}<div class="text-muted">{{ $c->patientInsurance?->policy_number }}</div></td>
            <td>{{ $c->invoice?->created_at?->format('d/m/Y') }}</td>
            <td>
                @foreach($c->lignes as $l)
                    <div>{{ $l->description }}@if($l->quantite > 1) ×{{ $l->quantite }}@endif @if($l->taux !== null)<span class="text-muted">({{ rtrim(rtrim(number_format((float) $l->taux, 2, ',', ''), '0'), ',') }} %)</span>@endif</div>
                @endforeach
            </td>
            <td class="text-end">{{ $gnf($c->lignes->sum('montant_acte')) }}</td>
            <td class="text-end fw-bold">{{ $gnf($c->claimed_amount) }}</td>
            @unless($impression)
                <td class="text-end">
                    @can('assurance.reclamation.gerer')
                        @if($bordereau->estBrouillon())
                            <form method="POST" action="{{ route('assurance.bordereaux.retirer', [$bordereau, $c]) }}" onsubmit="return confirm('Retirer cette réclamation du bordereau ?');">@csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-secondary" title="Retirer"><i class="fa fa-times"></i></button></form>
                        @endif
                    @endcan
                </td>
            @endunless
        </tr>
    @endforeach
    </tbody>
    <tfoot>
        <tr class="table-active"><th colspan="5" class="text-end">Total ({{ $bordereau->reclamations->count() }} réclamations)</th>
            <th class="text-end">{{ $gnf($bordereau->reclamations->sum(fn ($c) => $c->lignes->sum('montant_acte'))) }}</th>
            <th class="text-end">{{ $gnf($bordereau->reclamations->sum('claimed_amount')) }}</th>@unless($impression)<th></th>@endunless</tr>
    </tfoot>
</table>
