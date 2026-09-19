@extends('layouts.backend')

@php $gnf = fn ($m) => number_format((float) $m, 0, ',', ' '); @endphp

@section('style')
    @include('labo.partials.styles')
    <style>
        .cr-relever { display: grid; grid-template-columns: 1fr 1fr auto; gap: 10px; align-items: end; padding: 14px 18px; border-top: 1px solid var(--hali-bordure); background: #fafbfc; }
        .cr-relever label { display: grid; gap: 4px; margin: 0; font-size: .78rem; font-weight: 600; color: var(--hali-discret); }
        .cr-liste { margin: 0; padding: 0; list-style: none; }
        .cr-liste li { padding: 10px 18px; border-top: 1px solid #f3f4f6; font-size: .86rem; }
        .cr-liste li:first-child { border-top: 0; }
        .cr-ligne { display: flex; justify-content: space-between; gap: 10px; }
        .cr-ligne strong { font-variant-numeric: tabular-nums; }
        @media (max-width: 575.98px) { .cr-relever { grid-template-columns: 1fr; } }
    </style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">
    @include('labo.partials.entete', ['titre' => $partenariat->clinique?->nom ?? 'Clinique', 'fil' => [route('labo.creances.index') => 'Créances', 0 => $partenariat->clinique?->nom], 'sousTitre' => 'Reste dû : ' . $gnf($resume['reste_du']) . ' GNF'])

    <div class="lb-grille-large">
        <div class="lb-colonne">
            <section class="hl-bloc">
                <h2 class="hl-bloc-titre">Analyses à facturer <small>{{ $aFacturer->count() }} · {{ $gnf($aFacturer->sum('montant')) }} GNF</small></h2>
                @if($aFacturer->isEmpty())
                    <div class="hl-vide" style="padding:20px">Rien à facturer pour l'instant.</div>
                @else
                    <div class="table-responsive" style="max-height:340px; overflow-y:auto">
                        <table class="lb-table">
                            <thead><tr><th>Demande</th><th>Patient</th><th>Date</th><th class="lb-n">Montant</th></tr></thead>
                            <tbody>
                            @foreach($aFacturer as $creance)
                                <tr>
                                    <td>{{ $creance->demande?->numero }}</td>
                                    <td>{{ $creance->demande?->patient?->full_name }}</td>
                                    <td>{{ $creance->demande?->created_at->format('d/m/Y') }}</td>
                                    <td class="lb-n">{{ $gnf($creance->montant) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <form method="POST" action="{{ route('labo.creances.releves.store', $partenariat) }}" class="cr-relever">@csrf
                        <label>Du<input type="date" name="periode_debut" class="form-control form-control-sm" value="{{ today()->subMonth()->startOfMonth()->toDateString() }}" required></label>
                        <label>Au<input type="date" name="periode_fin" class="form-control form-control-sm" value="{{ today()->subMonth()->endOfMonth()->toDateString() }}" required></label>
                        <button class="hl-bouton hl-bouton-plein">Préparer un relevé</button>
                    </form>
                @endif
            </section>

            <section class="hl-bloc">
                <h2 class="hl-bloc-titre">Relevés</h2>
                @if($releves->isEmpty())
                    <div class="hl-vide" style="padding:20px">Aucun relevé.</div>
                @else
                    <div class="table-responsive">
                        <table class="lb-table">
                            <thead><tr><th>Relevé</th><th>Période</th><th class="lb-n">Montant</th><th class="lb-n">Reste</th><th>Statut</th><th></th></tr></thead>
                            <tbody>
                            @foreach($releves as $releve)
                                <tr class="{{ $releve->enRetard() ? 'table-warning' : '' }}">
                                    <td class="lb-fort">{{ $releve->numero }}</td>
                                    <td style="font-size:.84rem">{{ $releve->periode_debut->format('d/m/Y') }} → {{ $releve->periode_fin->format('d/m/Y') }}</td>
                                    <td class="lb-n">{{ $gnf($releve->montant_total) }}</td>
                                    <td class="lb-n">{{ $gnf($releve->resteDu()) }}</td>
                                    <td>
                                        <span class="hl-statut {{ $releve->statut === 'solde' ? 'hl-s-succes' : ($releve->statut === 'envoye' ? 'hl-s-info' : 'hl-s-neutre') }}">{{ ucfirst($releve->statut) }}</span>
                                        @if($releve->enRetard())<span class="lb-sous" style="color:var(--hali-danger)">échu le {{ $releve->echeance->format('d/m/Y') }}</span>@endif
                                    </td>
                                    <td class="lb-actions"><a href="{{ route('labo.releves.show', $releve) }}" class="hl-bouton lb-petit">Ouvrir</a></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>

        <div class="lb-colonne">
            <section class="hl-bloc">
                <h2 class="hl-bloc-titre">Enregistrer un règlement</h2>
                <form method="POST" action="{{ route('labo.creances.reglements.store', $partenariat) }}" class="lb-form">@csrf
                    <div class="lb-deux">
                        <div><label class="form-label">Montant reçu *</label><input type="number" step="1" min="1" name="montant" class="form-control" required inputmode="numeric"></div>
                        <div><label class="form-label">Mode</label>
                            <select name="mode" class="form-control">@foreach($modes as $valeur => $libelle)<option value="{{ $valeur }}">{{ $libelle }}</option>@endforeach</select></div>
                        <div><label class="form-label">Référence</label><input name="reference" class="form-control" maxlength="255"></div>
                        <div><label class="form-label">Reçu le *</label><input type="date" name="recu_le" class="form-control" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" required></div>
                    </div>

                    <details class="hl-repli">
                        <summary style="font-size:.84rem; font-weight:600; color:var(--hali-primaire)">Imputer à la main <span class="hl-repli-aide">sinon : des plus anciennes aux plus récentes</span></summary>
                        <table class="lb-table" style="margin-top:8px">
                            <thead><tr><th>Demande</th><th>Relevé</th><th class="lb-n">Reste dû</th><th style="width:110px">Imputé</th></tr></thead>
                            <tbody>
                            @foreach($creancesOuvertes as $creance)
                                <tr>
                                    <td>{{ $creance->demande?->numero }}</td>
                                    <td>{{ $creance->releve?->numero ?? '—' }}</td>
                                    <td class="lb-n">{{ $gnf($creance->resteDu()) }}</td>
                                    <td><input type="number" step="1" min="0" max="{{ $creance->resteDu() }}" name="imputations[{{ $creance->id }}]" class="form-control form-control-sm"></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </details>

                    <textarea name="notes" class="form-control" rows="2" placeholder="Note (facultatif)"></textarea>
                    <button class="hl-bouton hl-bouton-plein" style="min-height:44px">Enregistrer le règlement</button>
                </form>
            </section>

            <section class="hl-bloc">
                <h2 class="hl-bloc-titre">Ancienneté du reste dû</h2>
                <ul class="cr-liste">
                    @foreach($resume['anciennete'] as $tranche => $montant)
                        <li class="cr-ligne" style="{{ $tranche === '90+' && $montant > 0 ? 'color:var(--hali-danger)' : '' }}">
                            <span>{{ $tranche === '90+' ? 'Plus de 90 jours' : $tranche . ' jours' }}</span>
                            <strong>{{ $gnf($montant) }} GNF</strong>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="hl-bloc">
                <h2 class="hl-bloc-titre">Derniers règlements</h2>
                @if($reglements->isEmpty())
                    <div class="hl-vide" style="padding:20px">Aucun règlement enregistré.</div>
                @else
                    <ul class="cr-liste">
                        @foreach($reglements as $reglement)
                            <li>
                                <div class="cr-ligne">
                                    <span style="{{ $reglement->estAnnule() ? 'color:#9ca3af; text-decoration:line-through' : '' }}">
                                        {{ $reglement->recu_le->format('d/m/Y') }} · {{ $modes[$reglement->mode] ?? $reglement->mode }}
                                        @if($reglement->reference)<span class="lb-sous" style="display:inline">{{ $reglement->reference }}</span>@endif
                                    </span>
                                    <strong>{{ $gnf($reglement->montant) }} GNF</strong>
                                </div>
                                @if($reglement->estAnnule())
                                    <span class="lb-sous" style="color:var(--hali-danger)">Annulé : {{ $reglement->motif_annulation }}</span>
                                @else
                                    <details>
                                        <summary style="font-size:.8rem; color:var(--hali-danger); cursor:pointer">Annuler ce règlement</summary>
                                        <form method="POST" action="{{ route('labo.creances.reglements.annuler', $reglement) }}" style="display:flex; gap:6px; margin-top:6px">@csrf
                                            <input name="motif_annulation" class="form-control form-control-sm" maxlength="255" placeholder="Motif (virement rejeté…)" required>
                                            <button class="hl-bouton lb-petit lb-risque">Annuler</button>
                                        </form>
                                    </details>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </div>
</div></div>
@endsection
