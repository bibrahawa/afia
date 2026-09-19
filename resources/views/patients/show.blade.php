@extends('layouts.backend')

@php
    $telephone = $patient->telephone ?: ($patient->user->phone ?? null);
    $initiales = mb_strtoupper(mb_substr((string) $patient->first_name, 0, 1) . mb_substr((string) $patient->last_name, 0, 1)) ?: 'P';

    // Adresse : seulement les morceaux renseignés, sans virgules orphelines.
    $adresse = collect([$patient->location, $patient->district, $patient->state, $patient->country])
        ->map(fn ($v) => trim((string) $v))->filter()->unique()->join(', ');

    $naissance = $patient->birth_date ? \Carbon\Carbon::parse($patient->birth_date) : null;
    $mois = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    $dateLongue = fn ($d) => $d ? ($d->day === 1 ? '1er' : $d->day) . ' ' . $mois[$d->month] . ' ' . $d->year : null;

    $consultations = $patient->consultations->sortByDesc('created_at')->values();
    $hospitalisations = $patient->hospitalisations->sortByDesc('id')->values();
    $derniere = $consultations->first();
    $gnf = fn ($v) => number_format((float) $v, 0, ',', ' ') . ' GNF';

    $enfant = $naissance && $naissance->diffInMonths(today()) <= 60;
@endphp

@section('style')
<style>
    .fp-identite { display: flex; align-items: center; gap: 14px; }
    .fp-identite .hl-avatar { width: 56px; height: 56px; flex-basis: 56px; border-radius: 15px; font-size: 1.15rem; }

    .fp-grille { display: grid; grid-template-columns: minmax(0, 340px) minmax(0, 1fr); gap: 16px; align-items: start; }
    .fp-cote, .fp-principal { display: grid; gap: 16px; min-width: 0; }

    .fp-infos { margin: 0; padding: 6px 18px 12px; }
    .fp-infos div { display: grid; grid-template-columns: 130px minmax(0, 1fr); gap: 10px; padding: 9px 0; border-bottom: 1px solid #f3f4f6; font-size: .88rem; }
    .fp-infos div:last-child { border-bottom: 0; }
    .fp-infos dt { color: var(--hali-discret); font-weight: 500; }
    .fp-infos dd { margin: 0; color: var(--hali-encre); font-weight: 600; overflow-wrap: anywhere; }
    .fp-infos dd.est-vide { color: #9ca3af; font-weight: 500; }
    .fp-infos dd small { display: block; color: var(--hali-discret); font-weight: 500; }
    .fp-groupe { display: inline-grid; place-items: center; min-width: 38px; height: 26px; padding: 0 8px; border-radius: 7px; background: var(--hali-danger-pale); color: var(--hali-danger); font-weight: 700; }

    .fp-raccourcis { display: grid; gap: 4px; padding: 8px; }
    .fp-raccourcis a { display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: 9px; color: var(--hali-texte); text-decoration: none; font-size: .88rem; font-weight: 600; }
    .fp-raccourcis a:hover { background: var(--hali-primaire-pale); color: var(--hali-primaire-fonce); }
    .fp-raccourcis i { width: 18px; color: var(--hali-primaire); text-align: center; }
    .fp-raccourcis small { margin-left: auto; color: var(--hali-discret); font-weight: 500; }

    .fp-table { width: 100%; border-collapse: collapse; font-size: .88rem; }
    .fp-table th { padding: 10px 16px; background: #fafbfc; border-bottom: 1px solid var(--hali-bordure); color: var(--hali-discret); font-size: .78rem; font-weight: 600; text-align: left; white-space: nowrap; }
    .fp-table td { padding: 11px 16px; border-top: 1px solid #f3f4f6; vertical-align: middle; }
    .fp-table tbody tr:first-child td { border-top: 0; }
    .fp-table tbody tr { position: relative; }
    .fp-table tbody tr:hover td { background: var(--hali-primaire-pale); }
    .fp-table .fp-date { color: var(--hali-encre); font-weight: 600; white-space: nowrap; font-variant-numeric: tabular-nums; }
    .fp-table .fp-montant { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
    .fp-table .fp-motif { max-width: 280px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .fp-table .fp-voir { text-align: right; white-space: nowrap; }
    .fp-lien-ligne::after { content: ""; position: absolute; inset: 0; }
    .fp-plus { padding: 12px 16px; border-top: 1px solid var(--hali-bordure); text-align: center; }

    @media (max-width: 1199.98px) { .fp-grille { grid-template-columns: 1fr; } }
    @media (max-width: 767.98px) { .fp-cache-mobile { display: none; } }
</style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">

    <header class="hl-entete">
        <div class="fp-identite">
            <span class="hl-avatar" aria-hidden="true">{{ $initiales }}</span>
            <div>
                <h1>{{ $patient->first_name }} {{ $patient->last_name }}</h1>
                <p>
                    {{ $patient->gender ?: 'Sexe non renseigné' }}
                    @if($patient->age !== null) · {{ $patient->age }} ans @endif
                    · Dossier n° {{ str_pad($patient->id, 4, '0', STR_PAD_LEFT) }}
                </p>
            </div>
        </div>
        <div class="hl-entete-actions">
            <a href="{{ route('patient.index') }}" class="hl-bouton"><i class="fas fa-arrow-left" aria-hidden="true"></i> Patients</a>
            @can('parcours.dossier')
                <a href="{{ route('parcours.dossier.show', $patient->id) }}" class="hl-bouton hl-bouton-plein"><i class="fas fa-folder-open" aria-hidden="true"></i> Dossier médical</a>
            @endcan
        </div>
    </header>

    <div class="fp-grille">

        {{-- ============================================ Colonne de côté --}}
        <aside class="fp-cote">
            <section class="hl-bloc">
                <h2 class="hl-bloc-titre">Informations</h2>
                <dl class="fp-infos">
                    <div><dt>Téléphone</dt><dd class="{{ $telephone ? '' : 'est-vide' }}">{{ $telephone ?: 'Non renseigné' }}</dd></div>
                    <div>
                        <dt>Naissance</dt>
                        <dd class="{{ $naissance ? '' : 'est-vide' }}">
                            @if($naissance)
                                {{ $dateLongue($naissance) }}
                                @if($patient->age !== null)<small>{{ $patient->age }} ans</small>@endif
                            @else
                                Non renseignée
                            @endif
                        </dd>
                    </div>
                    <div><dt>Adresse</dt><dd class="{{ $adresse ? '' : 'est-vide' }}">{{ $adresse ?: 'Non renseignée' }}</dd></div>
                    <div>
                        <dt>Groupe sanguin</dt>
                        <dd class="{{ $patient->blood_group ? '' : 'est-vide' }}">
                            @if($patient->blood_group)<span class="fp-groupe">{{ $patient->blood_group }}</span>@else Non renseigné @endif
                        </dd>
                    </div>
                    @if($patient->occupation)
                        <div><dt>Profession</dt><dd>{{ $patient->occupation }}</dd></div>
                    @endif
                    @if($patient->relative_name)
                        <div>
                            <dt>Personne à prévenir</dt>
                            <dd>{{ $patient->relative_name }}@if($patient->relative_phone)<small>{{ $patient->relative_phone }}</small>@endif</dd>
                        </div>
                    @endif
                </dl>
            </section>

            <section class="hl-bloc">
                <h2 class="hl-bloc-titre">Accès rapides</h2>
                <nav class="fp-raccourcis" aria-label="Accès rapides">
                    @can('parcours.dossier')
                        <a href="{{ route('parcours.dossier.show', $patient->id) }}"><i class="fas fa-stream"></i> Dossier médical en frise</a>
                        <a href="{{ route('parcours.documents.index', $patient->id) }}"><i class="fas fa-file-medical"></i> Certificats et arrêts</a>
                        @if($enfant)
                            <a href="{{ route('parcours.croissance.show', $patient->id) }}"><i class="fas fa-chart-line"></i> Croissance</a>
                        @endif
                    @endcan
                    @can('assurance.referentiel.view')
                        <a href="{{ route('assurance.droits.show', $patient->id) }}"><i class="fas fa-shield-alt"></i> Vérifier les droits d'assurance</a>
                    @endcan
                    @if(Route::has('consentement.historique'))
                        <a href="{{ route('consentement.historique', $patient) }}"><i class="fas fa-user-shield"></i> Partage du dossier</a>
                    @endif
                    <a href="#consultations"><i class="fas fa-stethoscope"></i> Consultations <small>{{ $consultations->count() }}</small></a>
                    <a href="#hospitalisations"><i class="fas fa-procedures"></i> Hospitalisations <small>{{ $hospitalisations->count() }}</small></a>
                </nav>
            </section>
        </aside>

        {{-- ============================================ Principal --}}
        <div class="fp-principal">

            <section class="hl-bloc" id="consultations">
                <h2 class="hl-bloc-titre">
                    Consultations
                    <small>{{ $derniere ? 'dernière le ' . $derniere->created_at->format('d/m/Y') : 'aucune pour l\'instant' }}</small>
                </h2>
                @if($consultations->isEmpty())
                    <div class="hl-vide">
                        <i class="fas fa-stethoscope" aria-hidden="true"></i>
                        Aucune consultation enregistrée.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="fp-table">
                            <thead><tr><th>Date</th><th>Motif</th><th>Médecin</th><th class="fp-cache-mobile">Service</th><th class="fp-montant">Montant</th><th><span class="sr-only visually-hidden">Ouvrir</span></th></tr></thead>
                            <tbody>
                                @foreach($consultations as $consultation)
                                    <tr class="{{ $loop->index >= 10 ? 'js-en-plus' : '' }}" @if($loop->index >= 10) hidden @endif>
                                        <td class="fp-date">{{ $consultation->created_at->format('d/m/Y') }}</td>
                                        <td class="fp-motif" title="{{ $consultation->motif }}">{{ $consultation->motif ?: ($consultation->diagnostic ?: '—') }}</td>
                                        <td>{{ $consultation->medecin?->nom_affiche ?? '—' }}</td>
                                        <td class="fp-cache-mobile">{{ $consultation->department?->name ? ucfirst(mb_strtolower($consultation->department->name)) : '—' }}</td>
                                        <td class="fp-montant">{{ $gnf($consultation->transaction->total ?? 0) }}</td>
                                        <td class="fp-voir"><a href="{{ route('consultation.show', $consultation->id) }}" class="fp-lien-ligne">Ouvrir</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($consultations->count() > 10)
                        <div class="fp-plus">
                            <button type="button" class="hl-bouton js-voir-tout">Afficher les {{ $consultations->count() - 10 }} plus anciennes</button>
                        </div>
                    @endif
                @endif
            </section>

            <section class="hl-bloc" id="hospitalisations">
                <h2 class="hl-bloc-titre">Hospitalisations</h2>
                @if($hospitalisations->isEmpty())
                    <div class="hl-vide">
                        <i class="fas fa-procedures" aria-hidden="true"></i>
                        Aucune hospitalisation enregistrée.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="fp-table">
                            <thead><tr><th>Séjour</th><th class="fp-montant">Prix par jour</th><th class="fp-montant">Durée</th><th class="fp-montant">Total</th><th><span class="sr-only visually-hidden">Facture</span></th></tr></thead>
                            <tbody>
                                @foreach($hospitalisations as $hospitalisation)
                                    @php $jours = (int) ceil((float) $hospitalisation->nombre_jours); @endphp
                                    <tr>
                                        <td class="fp-date">
                                            @if($hospitalisation->date_entree){{ \Carbon\Carbon::parse($hospitalisation->date_entree)->format('d/m/Y') }}@else Hospitalisation @endif
                                            @if($hospitalisation->chambre?->numero)<span class="pt-sous" style="display:block;color:var(--hali-discret);font-weight:500;font-size:.8rem">Chambre {{ $hospitalisation->chambre->numero }}</span>@endif
                                        </td>
                                        <td class="fp-montant">{{ $gnf($hospitalisation->chambre->prix_par_jour ?? 0) }}</td>
                                        <td class="fp-montant">{{ $jours }} jour{{ $jours > 1 ? 's' : '' }}</td>
                                        <td class="fp-montant"><strong>{{ $gnf($hospitalisation->total_payer) }}</strong></td>
                                        <td class="fp-voir">
                                            <a href="{{ route('hospitalisations.facture', $hospitalisation->id) }}" target="_blank" class="hl-bouton"><i class="fas fa-file-invoice" aria-hidden="true"></i> Facture</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            @include('patients._couvertures')
        </div>
    </div>
</div></div>
@endsection

@section('script')
<script>
    document.querySelectorAll('.js-voir-tout').forEach(function (bouton) {
        bouton.addEventListener('click', function () {
            document.querySelectorAll('.js-en-plus').forEach(function (ligne) { ligne.hidden = false; });
            bouton.parentElement.remove();
        });
    });
</script>
@endsection
