@extends('layouts.backend')

@php
    $nomDe = function ($modele, $type) {
        if (! $modele) return class_basename($type) . ' (supprimé)';
        return $modele->nom ?? $modele->nom_affiche ?? $modele->full_name ?? $modele->name ?? class_basename($type) . ' n° ' . $modele->id;
    };
    $portees = collect(\App\Enums\PorteeAcces::cases())->reject(fn ($p) => $p === \App\Enums\PorteeAcces::InfosVitalesMinimales);
    $libellePortee = fn ($v) => \App\Enums\PorteeAcces::tryFrom($v)?->libelle() ?? $v;
    $statutsDemande = ['en_attente' => ['En attente du patient', 'hl-s-alerte'], 'acceptee' => ['Acceptée', 'hl-s-succes'], 'refusee' => ['Refusée', 'hl-s-danger'], 'expiree' => ['Expirée', 'hl-s-neutre']];
    $actifs = $consentements->filter->estActif();
    $enAttente = $demandes->where('statut', 'en_attente')->filter(fn ($d) => ! $d->expire_le || $d->expire_le->isFuture());
@endphp

@section('style')
<style>
    .cs-grille { display: grid; grid-template-columns: minmax(0, 1.1fr) minmax(0, 1fr); gap: 16px; align-items: start; }
    .cs-colonne { display: grid; gap: 16px; }
    .cs-ligne { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 14px; padding: 13px 18px; border-top: 1px solid #f3f4f6; }
    .cs-ligne:first-child { border-top: 0; }
    .cs-icone { display: grid; place-items: center; width: 38px; height: 38px; flex: none; border-radius: 11px; background: var(--hali-primaire-pale); color: var(--hali-primaire); }
    .cs-nom { color: var(--hali-encre); font-weight: 650; }
    .cs-sous { display: block; color: var(--hali-discret); font-size: .8rem; }
    .cs-portees { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 4px; }
    .cs-portees span { padding: 2px 8px; border-radius: 999px; background: #f3f4f6; color: var(--hali-texte); font-size: .72rem; font-weight: 600; }
    .cs-form { display: grid; gap: 12px; padding: 16px 18px; }
    .cs-form label.cs-l { display: block; margin-bottom: 5px; color: var(--hali-encre); font-size: .83rem; font-weight: 650; }
    .cs-choix { display: grid; gap: 6px; }
    .cs-choix label { display: flex; align-items: center; gap: 10px; margin: 0; padding: 9px 12px; border: 1px solid var(--hali-bordure); border-radius: 10px; font-size: .88rem; cursor: pointer; }
    .cs-choix label:has(input:checked) { border-color: var(--hali-primaire); background: var(--hali-primaire-pale); }
    .cs-code { display: flex; gap: 8px; }
    .cs-code input { max-width: 160px; min-height: 46px; text-align: center; letter-spacing: .4em; font-size: 1.2rem; font-weight: 700; }
    @media (max-width: 991.98px) { .cs-grille { grid-template-columns: 1fr; } }
</style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">
    <header class="hl-entete">
        <div>
            <h1>Partage du dossier</h1>
            <p>{{ $patient->full_name }} · le patient décide qui peut consulter son dossier, et peut retirer un accès à tout moment.</p>
        </div>
        <div class="hl-entete-actions">
            <a href="{{ route('patient.show', $patient) }}" class="hl-bouton"><i class="fas fa-arrow-left" aria-hidden="true"></i> Fiche patient</a>
        </div>
    </header>

    <div class="cs-grille">
        <div class="cs-colonne">
            <section class="hl-bloc">
                <h2 class="hl-bloc-titre">Accès en cours <small>{{ $actifs->count() }}</small></h2>
                @forelse($actifs as $c)
                    <div class="cs-ligne">
                        <span class="cs-icone" aria-hidden="true"><i class="fas fa-hospital"></i></span>
                        <div style="flex:1; min-width:0">
                            <span class="cs-nom">{{ $nomDe($c->beneficiaire, $c->beneficiaire_type) }}</span>
                            <span class="cs-sous">Accordé le {{ $c->accorde_le?->format('d/m/Y') }} · expire le {{ $c->expire_le?->format('d/m/Y') ?? '—' }}</span>
                            <div class="cs-portees">@foreach((array) $c->portee as $v)<span>{{ $libellePortee($v) }}</span>@endforeach</div>
                        </div>
                        @can('consentement.revoquer')
                            <form action="{{ route('consentement.revoquer', $c) }}" method="POST" onsubmit="return confirm('Retirer cet accès ? L\'établissement ne pourra plus consulter ce dossier.');" style="margin:0">
                                @csrf @method('DELETE')
                                <button type="submit" class="hl-bouton" style="min-height:34px; color:var(--hali-danger); border-color:#fecaca">Retirer l'accès</button>
                            </form>
                        @endcan
                    </div>
                @empty
                    <div class="hl-vide" style="padding:26px"><i class="fas fa-user-shield" aria-hidden="true"></i>Aucun établissement n'a d'accès élargi à ce dossier.</div>
                @endforelse
            </section>

            <section class="hl-bloc">
                <h2 class="hl-bloc-titre">Historique des demandes <small>{{ $demandes->count() }}</small></h2>
                @forelse($demandes as $d)
                    @php([$libelle, $ton] = $statutsDemande[$d->statut] ?? [ucfirst(str_replace('_', ' ', $d->statut)), 'hl-s-neutre'])
                    <div class="cs-ligne">
                        <div style="flex:1; min-width:0">
                            <span class="cs-nom">{{ $nomDe($d->demandeur, $d->demandeur_type) }}</span> <span class="hl-statut {{ $ton }}">{{ $libelle }}</span>
                            <span class="cs-sous">{{ $d->created_at->format('d/m/Y à H:i') }}@if($d->motif) · {{ $d->motif }}@endif</span>
                            <div class="cs-portees">@foreach((array) $d->portee_demandee as $v)<span>{{ $libellePortee($v) }}</span>@endforeach</div>
                        </div>
                    </div>
                @empty
                    <div class="hl-vide" style="padding:22px">Aucune demande pour l'instant.</div>
                @endforelse
            </section>
        </div>

        <div class="cs-colonne">
            @can('consentement.demander')
                @if($enAttente->isNotEmpty())
                    <section class="hl-bloc">
                        <h2 class="hl-bloc-titre">Le patient est présent ? <small>confirmation par code</small></h2>
                        <form method="POST" action="{{ route('consentement.confirmer-code', $patient) }}" class="cs-form">
                            @csrf
                            <p class="mb-0" style="font-size:.88rem">Le patient a reçu un code par SMS. S'il accepte, saisissez le code qu'il vous lit.</p>
                            <div class="cs-code">
                                <input type="text" name="code" class="form-control" inputmode="numeric" maxlength="6" placeholder="••••••" required autocomplete="one-time-code" aria-label="Code reçu par le patient">
                                <button type="submit" class="hl-bouton hl-bouton-plein">Confirmer l'accès</button>
                            </div>
                        </form>
                    </section>
                @endif

                <section class="hl-bloc">
                    <h2 class="hl-bloc-titre">Demander l'accès au dossier</h2>
                    <form method="POST" action="{{ route('consentement.demander', $patient) }}" class="cs-form">
                        @csrf
                        <div>
                            <span class="cs-l" style="display:block; margin-bottom:5px; color:var(--hali-encre); font-size:.83rem; font-weight:650">Ce que vous souhaitez consulter</span>
                            <div class="cs-choix">
                                @foreach($portees as $p)
                                    <label><input type="checkbox" name="portees[]" value="{{ $p->value }}" @checked($p === \App\Enums\PorteeAcces::Consultations)> {{ $p->libelle() }}</label>
                                @endforeach
                            </div>
                        </div>
                        <div><label class="cs-l" for="csMotif">Motif</label>
                            <input type="text" name="motif" id="csMotif" class="form-control" maxlength="500" placeholder="Suivi de grossesse, bilan pré-opératoire…"></div>
                        <button type="submit" class="hl-bouton hl-bouton-plein"><i class="fas fa-paper-plane" aria-hidden="true"></i> Envoyer la demande au patient</button>
                        <p class="mb-0" style="color:var(--hali-discret); font-size:.8rem">Le patient reçoit un SMS et accepte ou refuse. La demande expire au bout de 30 minutes. Demandez seulement ce qui est utile à la prise en charge.</p>
                    </form>
                </section>
            @endcan
        </div>
    </div>
</div></div>
@endsection
