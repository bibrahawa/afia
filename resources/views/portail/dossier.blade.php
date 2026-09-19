<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0f766e">
    <meta name="robots" content="noindex">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Mon espace santé · {{ $patient->getFullName() }}</title>
@php
    $statutsRdv = ['pending' => ['À confirmer', 'st-alerte'], 'confirmed' => ['Confirmé', 'st-ok'], 'completed' => ['Honoré', 'st-neutre'], 'cancelled' => ['Annulé', 'st-neutre'], 'no_show' => ['Absent', 'st-danger']];
    $nomDe = fn ($m, $type) => $m ? ($m->nom ?? $m->nom_affiche ?? $m->full_name ?? $m->name ?? class_basename($type)) : class_basename($type);
    $libellePortee = fn ($v) => \App\Enums\PorteeAcces::tryFrom($v)?->libelle() ?? $v;
    // Clinique pour « Prendre rendez-vous » : la dernière fréquentée par le patient.
    // CORRIGÉ : route('rdv') était appelée sans clinique — erreur 500 dès qu'aucun rendez-vous n'était à venir.
    $cliniqueRdv = $prochainRdv?->etablissement ?? $historiqueRdv->first()?->etablissement ?? $consultations->first()?->etablissement;
    $initiales = mb_strtoupper(mb_substr((string) $patient->first_name, 0, 1) . mb_substr((string) $patient->last_name, 0, 1));
    $accesActifs = $consentements->where('statut', 'actif');
@endphp
    <style>
        :root { --p: #0f766e; --pf: #115e59; --pp: #f0fdfa; --pc: #ccfbf1; --bg: #f4f6f6; --ink: #111827; --soft: #6b7280; --line: #e5e7eb; --danger: #b91c1c; --danger-pp: #fef2f2; --alerte: #b45309; --alerte-pp: #fffbeb; }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg); color: var(--ink); font-family: -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
        .pp { display: flex; flex-direction: column; min-height: 100vh; max-width: 580px; margin: 0 auto; padding-bottom: calc(72px + env(safe-area-inset-bottom)); }
        .pp-tete { padding: max(20px, env(safe-area-inset-top)) 20px 22px; background: var(--p); color: #fff; border-radius: 0 0 22px 22px; }
        .pp-tete-haut { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 18px; font-size: .8rem; opacity: .9; }
        .pp-tete-haut form { margin: 0; }
        .pp-tete-haut button { padding: 6px 10px; border: 1px solid rgba(255, 255, 255, .35); border-radius: 8px; background: none; color: #fff; font-size: .78rem; cursor: pointer; }
        .pp-identite { display: flex; align-items: center; gap: 14px; }
        .pp-avatar { display: grid; place-items: center; width: 54px; height: 54px; flex: none; border-radius: 16px; background: rgba(255, 255, 255, .18); font-size: 1.1rem; font-weight: 800; }
        .pp-nom { margin: 0; font-size: 1.35rem; letter-spacing: -.02em; }
        .pp-id { display: inline-block; margin-top: 4px; padding: 3px 10px; border-radius: 999px; background: rgba(255, 255, 255, .16); font-family: "SF Mono", Consolas, monospace; font-size: .8rem; letter-spacing: .04em; }
        .pp-switch { width: 100%; margin-top: 14px; padding: 10px 12px; border: 0; border-radius: 10px; background: rgba(255, 255, 255, .16); color: #fff; font-size: .88rem; }
        .pp-switch option { color: var(--ink); }
        .pp-securite { display: flex; flex-wrap: wrap; gap: 10px 22px; margin: 14px 16px 0; padding: 12px 14px; border: 1px solid #fecaca; border-radius: 14px; background: var(--danger-pp); color: var(--danger); font-size: .88rem; }
        .pp-securite small { display: block; color: #7f1d1d; font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; }
        .pp-flash { margin: 14px 16px 0; padding: 12px 14px; border-radius: 12px; font-size: .9rem; }
        .pp-flash.ok { background: var(--pp); color: var(--pf); }
        .pp-flash.ko { background: var(--danger-pp); color: var(--danger); }
        main { flex: 1; padding: 18px 16px 10px; }
        .pp-titre { margin: 22px 4px 10px; color: var(--soft); font-size: .75rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; }
        .pp-titre:first-child { margin-top: 4px; }
        .pp-carte { padding: 16px; border: 1px solid var(--line); border-radius: 16px; background: #fff; }
        .pp-rdv { display: flex; gap: 14px; border-left: 5px solid var(--rdv, var(--p)); }
        .pp-cal { display: grid; width: 58px; flex: none; overflow: hidden; border: 1px solid var(--line); border-radius: 12px; text-align: center; align-self: start; }
        .pp-cal span { padding: 2px 0; background: var(--p); color: #fff; font-size: .66rem; font-weight: 700; text-transform: uppercase; }
        .pp-cal b { padding: 4px 0 6px; font-size: 1.45rem; line-height: 1.1; }
        .pp-rdv-heure { font-size: 1.15rem; font-weight: 800; font-variant-numeric: tabular-nums; }
        .pp-sous { display: block; color: var(--soft); font-size: .86rem; }
        .pp-lien { margin-top: 10px; padding: 0; border: 0; background: none; color: var(--danger); font-size: .84rem; font-weight: 600; cursor: pointer; }
        .pp-vide { padding: 26px 16px; border: 1px dashed #d1d5db; border-radius: 16px; background: #fff; color: var(--soft); text-align: center; font-size: .9rem; }
        .pp-bouton { display: inline-flex; align-items: center; justify-content: center; min-height: 46px; margin-top: 12px; padding: 0 20px; border-radius: 12px; background: var(--p); color: #fff; font-weight: 700; text-decoration: none; }
        .pp-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .pp-stat { padding: 16px; border: 1px solid var(--line); border-radius: 16px; background: #fff; }
        .pp-stat b { display: block; color: var(--p); font-size: 1.6rem; }
        .pp-stat span { color: var(--soft); font-size: .82rem; }
        .pp-liste { overflow: hidden; border: 1px solid var(--line); border-radius: 16px; background: #fff; }
        .pp-ligne { display: flex; align-items: center; gap: 12px; padding: 13px 16px; border-top: 1px solid #f3f4f6; color: inherit; text-decoration: none; }
        .pp-ligne:first-child { border-top: 0; }
        .pp-ligne strong { display: block; font-size: .94rem; }
        .pp-ligne form { margin: 0 0 0 auto; }
        .pp-statut { margin-left: auto; flex: none; padding: 3px 10px; border-radius: 999px; font-size: .74rem; font-weight: 700; }
        .st-ok { background: var(--pp); color: var(--pf); } .st-alerte { background: var(--alerte-pp); color: var(--alerte); }
        .st-danger { background: var(--danger-pp); color: var(--danger); } .st-neutre { background: #f3f4f6; color: var(--soft); }
        .pp-fiche div { display: grid; gap: 2px; padding: 12px 16px; border-top: 1px solid #f3f4f6; }
        .pp-fiche div:first-child { border-top: 0; }
        .pp-fiche span { color: var(--soft); font-size: .78rem; font-weight: 600; }
        .pp-portees { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 5px; }
        .pp-portees i { padding: 2px 8px; border-radius: 999px; background: #f3f4f6; color: var(--soft); font-size: .72rem; font-style: normal; font-weight: 600; }
        .pp-note { margin: 0 4px 14px; color: var(--soft); font-size: .88rem; line-height: 1.5; }
        .pp-nouveau { display: flex; align-items: center; gap: 14px; width: 100%; border-color: var(--pc); background: var(--pp); color: inherit; font: inherit; text-align: left; cursor: pointer; }
        .pp-nouveau-icone { display: grid; place-items: center; width: 44px; height: 44px; flex: none; border-radius: 12px; background: var(--p); color: #fff; }
        .pp-nouveau-icone svg { width: 22px; height: 22px; }
        .pp-nouveau strong { display: block; }
        .pp-fleche { margin-left: auto; color: var(--p); font-size: 1.4rem; }
        .pp-nav { position: fixed; bottom: 0; left: 50%; z-index: 10; display: grid; grid-template-columns: repeat(5, 1fr); width: 100%; max-width: 580px; padding-bottom: env(safe-area-inset-bottom); transform: translateX(-50%); border-top: 1px solid var(--line); background: rgba(255, 255, 255, .97); }
        .pp-onglet { display: grid; justify-items: center; gap: 3px; padding: 9px 2px 10px; border: 0; background: none; color: var(--soft); font-size: .7rem; font-weight: 600; cursor: pointer; }
        .pp-onglet svg { width: 22px; height: 22px; }
        .pp-onglet.actif { color: var(--p); }
        .pp-onglet .pastille { position: absolute; margin: -2px 0 0 16px; width: 8px; height: 8px; border-radius: 50%; background: var(--alerte); }
        [hidden] { display: none !important; }
    </style>
</head>
<body>
<div class="pp">
    <header class="pp-tete">
        <div class="pp-tete-haut">
            <span>Mon espace santé</span>
            <form method="POST" action="{{ route('patient-auth.deconnexion') }}">@csrf<button type="submit">Se déconnecter</button></form>
        </div>
        <div class="pp-identite">
            <span class="pp-avatar" aria-hidden="true">{{ $initiales }}</span>
            <div>
                <h1 class="pp-nom">{{ $patient->getFullName() }}</h1>
                <span class="pp-id" title="Identifiant national de santé">ID santé · {{ $patient->identifiant_national_sante }}</span>
            </div>
        </div>
        @if($autresDossiers->isNotEmpty())
            <select class="pp-switch" onchange="if (this.value) location.href = this.value" aria-label="Changer de dossier">
                <option value="">Dossier de {{ $patient->getFullName() }}</option>
                @foreach($autresDossiers as $autre)<option value="{{ route('portail.dossier', $autre) }}">Ouvrir le dossier de {{ $autre->getFullName() }}</option>@endforeach
            </select>
        @endif
    </header>

    @if(session('success'))<div class="pp-flash ok" role="status">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="pp-flash ko" role="alert">{{ session('error') }}</div>@endif

    {{-- Toujours visible : ce qu'un soignant doit savoir en premier. --}}
    @if($patient->blood_group || $patient->antecedant?->allergies)
        <div class="pp-securite">
            @if($patient->blood_group)<span><small>Groupe sanguin</small><strong>{{ $patient->blood_group }}</strong></span>@endif
            @if($patient->antecedant?->allergies)<span><small>Allergies</small><strong>{{ $patient->antecedant->allergies }}</strong></span>@endif
        </div>
    @endif

    <main>
        {{-- ======================= Accueil --}}
        <section data-panel="accueil">
            <h2 class="pp-titre">Prochain rendez-vous</h2>
            @if($prochainRdv)
                @php($d = $prochainRdv->appointment_datetime)
                <div class="pp-carte pp-rdv" style="--rdv: {{ $prochainRdv->motifRdv->couleur ?? '#0f766e' }}">
                    <div class="pp-cal" aria-hidden="true"><span>{{ $d->translatedFormat('M') }}</span><b>{{ $d->format('d') }}</b></div>
                    <div>
                        <span class="pp-rdv-heure">{{ ucfirst($d->translatedFormat('l')) }} à {{ $d->format('H:i') }}</span>
                        <span class="pp-sous">{{ $prochainRdv->motifRdv->nom ?? 'Consultation' }} · {{ $prochainRdv->employee?->nom_affiche }}</span>
                        @if($prochainRdv->etablissement)<span class="pp-sous">{{ $prochainRdv->etablissement->nom }}</span>@endif
                        @if($prochainRdv->canBeCancelled())
                            <form action="{{ route('portail.rdv.annuler', $prochainRdv) }}" method="POST" onsubmit="return confirm('Annuler ce rendez-vous ? La clinique sera prévenue.');">
                                @csrf<button type="submit" class="pp-lien">Annuler ce rendez-vous</button>
                            </form>
                        @endif
                    </div>
                </div>
            @else
                <div class="pp-vide">
                    Aucun rendez-vous à venir.
                    @if($cliniqueRdv?->slug)
                        <br><a href="{{ route('rdv', ['etablissement' => $cliniqueRdv->slug]) }}" class="pp-bouton">Prendre rendez-vous · {{ $cliniqueRdv->nom }}</a>
                    @endif
                </div>
            @endif

            @php($recents = ($resultats ?? collect())->filter(fn ($r) => $r->disponible && $r->date && $r->date->gt(now()->subDays(7))))
            @if($recents->isNotEmpty())
                <h2 class="pp-titre">Nouveau</h2>
                <button type="button" class="pp-carte pp-nouveau" data-aller="dossier">
                    <span class="pp-nouveau-icone" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 3h6M10 3v6l-5 9a2 2 0 0 0 1.7 3h10.6a2 2 0 0 0 1.7-3l-5-9V3"/></svg></span>
                    <span><strong>{{ $recents->count() > 1 ? $recents->count() . ' résultats d\'analyses disponibles' : 'Vos résultats d\'analyses sont disponibles' }}</strong>
                        <span class="pp-sous">{{ $recents->first()->clinique }} · {{ $recents->first()->date->translatedFormat('d M') }}</span></span>
                    <span class="pp-fleche" aria-hidden="true">›</span>
                </button>
            @endif

            <h2 class="pp-titre">En bref</h2>
            <div class="pp-stats">
                <div class="pp-stat"><b>{{ $consultations->count() }}</b><span>consultation{{ $consultations->count() > 1 ? 's' : '' }} récente{{ $consultations->count() > 1 ? 's' : '' }}</span></div>
                <div class="pp-stat"><b>{{ $accesActifs->count() }}</b><span>accès accordé{{ $accesActifs->count() > 1 ? 's' : '' }} à votre dossier</span></div>
            </div>
        </section>

        {{-- ======================= Rendez-vous --}}
        <section data-panel="rdv" hidden>
            <h2 class="pp-titre">Rendez-vous passés</h2>
            @if($historiqueRdv->isEmpty())
                <div class="pp-vide">Aucun rendez-vous passé pour l'instant.</div>
            @else
                <div class="pp-liste">
                    @foreach($historiqueRdv as $rdv)
                        @php([$libelle, $ton] = $statutsRdv[$rdv->status] ?? [$rdv->status, 'st-neutre'])
                        <div class="pp-ligne">
                            <div style="min-width:0">
                                <strong>{{ $rdv->motifRdv->nom ?? 'Consultation' }}</strong>
                                <span class="pp-sous">{{ $rdv->appointment_datetime->translatedFormat('d M Y') }} à {{ $rdv->appointment_datetime->format('H:i') }} · {{ $rdv->employee?->nom_affiche }}</span>
                                @if($rdv->etablissement)<span class="pp-sous">{{ $rdv->etablissement->nom }}</span>@endif
                            </div>
                            <span class="pp-statut {{ $ton }}">{{ $libelle }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- ======================= Dossier --}}
        <section data-panel="dossier" hidden>
            <h2 class="pp-titre">Résultats d'analyses</h2>
            @if(($resultats ?? collect())->isEmpty())
                <div class="pp-vide">Aucun résultat d'analyses pour l'instant.</div>
            @else
                <div class="pp-liste">
                    @foreach($resultats as $r)
                        @if($r->disponible)
                            <a href="{{ $r->lien }}" class="pp-ligne">
                                <div style="min-width:0">
                                    <strong>{{ $r->examens->take(3)->implode(', ') ?: 'Analyses ' . $r->numero }}@if($r->examens->count() > 3) +{{ $r->examens->count() - 3 }}@endif</strong>
                                    <span class="pp-sous">{{ $r->date?->translatedFormat('d M Y') }} · {{ $r->clinique }}</span>
                                </div>
                                @if($r->rectifie)<span class="pp-statut st-alerte">Rectifié</span>@else<span class="pp-statut st-ok">Voir ›</span>@endif
                            </a>
                        @else
                            <div class="pp-ligne">
                                <div style="min-width:0">
                                    <strong>{{ $r->examens->take(3)->implode(', ') ?: 'Analyses ' . $r->numero }}</strong>
                                    <span class="pp-sous">{{ $r->clinique }} · à retirer après règlement à la caisse</span>
                                </div>
                                <span class="pp-statut st-neutre">Prêt</span>
                            </div>
                        @endif
                    @endforeach
                </div>
                <p class="pp-note" style="margin-top:10px">Faites interpréter vos résultats par votre médecin. Une valeur signalée n'est pas forcément grave.</p>
            @endif

            <h2 class="pp-titre">Antécédents</h2>
            @php($champs = ['antecedents_medicaux' => 'Médicaux', 'antecedents_chirurgicaux' => 'Chirurgicaux', 'antecedents_gyneco_obstetricaux' => 'Gynéco-obstétricaux', 'antecedents_familiaux' => 'Familiaux', 'allergies' => 'Allergies', 'traitements_cours' => 'Traitements en cours'])
            @if($patient->antecedant && collect(array_keys($champs))->contains(fn ($c) => filled($patient->antecedant->$c)))
                <div class="pp-liste pp-fiche">
                    @foreach($champs as $champ => $libelle)
                        @if($patient->antecedant->$champ)<div><span>{{ $libelle }}</span>{{ $patient->antecedant->$champ }}</div>@endif
                    @endforeach
                </div>
            @else
                <div class="pp-vide">Aucun antécédent enregistré.</div>
            @endif

            <h2 class="pp-titre">Consultations récentes</h2>
            @if($consultations->isEmpty())
                <div class="pp-vide">Aucune consultation enregistrée.</div>
            @else
                <div class="pp-liste">
                    @foreach($consultations as $c)
                        <div class="pp-ligne">
                            <div><strong>{{ $c->motif ?: 'Consultation' }}</strong>
                                <span class="pp-sous">{{ $c->created_at->translatedFormat('d M Y') }}@if($c->etablissement) · {{ $c->etablissement->nom }}@endif</span></div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- ======================= Accès --}}
        <section data-panel="acces" hidden>
            <p class="pp-note">Vous décidez qui peut consulter votre dossier. Chaque accès peut être retiré à tout moment.</p>
            @if($demandesEnAttente->isNotEmpty())
                <h2 class="pp-titre">En attente de votre réponse</h2>
                <div class="pp-liste">
                    @foreach($demandesEnAttente as $d)
                        <div class="pp-ligne"><div><strong>{{ $nomDe($d->demandeur, $d->demandeur_type) }}</strong><span class="pp-sous">Répondez au SMS reçu pour accepter ou refuser.</span></div></div>
                    @endforeach
                </div>
            @endif
            <h2 class="pp-titre">Accès accordés</h2>
            @if($accesActifs->isEmpty())
                <div class="pp-vide">Personne n'a d'accès élargi à votre dossier.</div>
            @else
                <div class="pp-liste">
                    @foreach($accesActifs as $c)
                        <div class="pp-ligne">
                            <div style="min-width:0">
                                <strong>{{ $nomDe($c->beneficiaire, $c->beneficiaire_type) }}</strong>
                                <span class="pp-sous">Jusqu'au {{ $c->expire_le?->format('d/m/Y') ?? '—' }}</span>
                                <div class="pp-portees">@foreach((array) $c->portee as $v)<i>{{ $libellePortee($v) }}</i>@endforeach</div>
                            </div>
                            <form action="{{ route('portail.consentement.revoquer', $c) }}" method="POST" onsubmit="return confirm('Retirer cet accès ?');">
                                @csrf @method('DELETE')<button type="submit" class="pp-lien" style="margin:0">Retirer</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- ======================= Famille --}}
        <section data-panel="famille" hidden>
            <h2 class="pp-titre">Liens familiaux</h2>
            @if($patient->relationsFamiliales->isEmpty())
                <div class="pp-vide">Aucun lien familial enregistré. Demandez à l'accueil de la clinique de les ajouter.</div>
            @else
                <div class="pp-liste">
                    @foreach($patient->relationsFamiliales as $r)
                        <div class="pp-ligne"><div><strong>{{ $r->personneLiee?->getFullName() }}</strong><span class="pp-sous">{{ ucfirst($r->type_relation->value) }}</span></div></div>
                    @endforeach
                </div>
            @endif
            @if($autresDossiers->isNotEmpty())
                <h2 class="pp-titre">Dossiers dont vous êtes responsable</h2>
                <div class="pp-liste">
                    @foreach($autresDossiers as $autre)
                        <a href="{{ route('portail.dossier', $autre) }}" class="pp-ligne"><strong>{{ $autre->getFullName() }}</strong><span class="pp-statut st-neutre">Ouvrir ›</span></a>
                    @endforeach
                </div>
            @endif
        </section>
    </main>

    <nav class="pp-nav" aria-label="Sections">
        @foreach([
            ['accueil', 'Accueil', '<path d="M3 11l9-8 9 8v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>'],
            ['rdv', 'Rendez-vous', '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>'],
            ['dossier', 'Dossier', '<path d="M6 3h9l5 5v13H6z"/><path d="M14 3v6h6M9 13h8M9 17h6"/>'],
            ['acces', 'Accès', '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/>'],
            ['famille', 'Famille', '<circle cx="9" cy="8" r="3"/><circle cx="17" cy="10" r="2.5"/><path d="M3 20c0-3.5 3-6 6-6s6 2.5 6 6M14 20c0-2.5 1.5-4.5 3.5-4.5S21 17.5 21 20"/>'],
        ] as [$cle, $libelle, $icone])
            <button type="button" class="pp-onglet {{ $loop->first ? 'actif' : '' }}" data-tab="{{ $cle }}" aria-controls="panel-{{ $cle }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $icone !!}</svg>
                @if($cle === 'acces' && $demandesEnAttente->isNotEmpty())<span class="pastille" aria-label="Demande en attente"></span>@endif
                {{ $libelle }}
            </button>
        @endforeach
    </nav>
</div>
<script>
(function () {
    function ouvrir(nom) {
        document.querySelectorAll('[data-panel]').forEach(function (p) { p.hidden = p.dataset.panel !== nom; });
        document.querySelectorAll('.pp-onglet').forEach(function (o) { o.classList.toggle('actif', o.dataset.tab === nom); });
        if (history.replaceState) history.replaceState(null, '', '#' + nom);
        window.scrollTo(0, 0);
    }
    document.querySelectorAll('.pp-onglet').forEach(function (o) { o.addEventListener('click', function () { ouvrir(o.dataset.tab); }); });
    document.querySelectorAll('[data-aller]').forEach(function (b) { b.addEventListener('click', function () { ouvrir(b.dataset.aller); }); });
    // Revenir sur le même onglet après une action (annulation, retrait d'accès).
    var ancre = location.hash.replace('#', '');
    if (ancre && document.querySelector('[data-panel="' + ancre + '"]')) ouvrir(ancre);
})();
</script>
</body>
</html>
