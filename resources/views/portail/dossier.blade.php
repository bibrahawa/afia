<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon espace santé — {{ $patient->getFullName() }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
<div class="pp-shell">

    {{-- ============ EN-TÊTE IDENTITÉ ============ --}}
    <header class="pp-header">
        <div class="pp-header-top">
            <div>
                <p class="pp-eyebrow">Mon espace santé</p>
                <h1 class="pp-nom">{{ $patient->getFullName() }}</h1>
            </div>
            @if($autresDossiers->isNotEmpty())
                <select class="pp-switch" onchange="location.href=this.value">
                    <option>{{ $patient->getFullName() }} (actuel)</option>
                    @foreach($autresDossiers as $autre)
                        <option value="{{ route('portail.dossier', $autre) }}">{{ $autre->getFullName() }}</option>
                    @endforeach
                </select>
            @endif
        </div>
        <div class="pp-id-chip">
            <span class="pp-id-label">Identifiant santé</span>
            <span class="pp-id-valeur">{{ $patient->identifiant_national_sante }}</span>
        </div>
    </header>

    {{-- ============ BANDEAU SÉCURITÉ — toujours visible, jamais dans un onglet ============ --}}
    @if($patient->antecedant && ($patient->antecedant->allergies || $patient->blood_group))
    <div class="pp-securite">
        @if($patient->blood_group)
            <span class="pp-securite-item"><strong>Groupe sanguin</strong> {{ $patient->blood_group }}</span>
        @endif
        @if($patient->antecedant->allergies)
            <span class="pp-securite-item"><strong>Allergies</strong> {{ $patient->antecedant->allergies }}</span>
        @endif
    </div>
    @endif

    {{-- ============ CONTENU DES ONGLETS ============ --}}
    <main class="pp-body">

        {{-- ---------- ACCUEIL ---------- --}}
        <section class="pp-panel" data-panel="accueil">
            @if($prochainRdv)
                <p class="pp-section-titre">Prochain rendez-vous</p>
                <div class="pp-carte-rdv" style="border-left-color:{{ $prochainRdv->motifRdv->couleur ?? '#0E6659' }}">
                    <span class="pp-rdv-date">{{ $prochainRdv->appointment_datetime->translatedFormat('l j F, H:i') }}</span>
                    <span class="pp-rdv-motif">{{ $prochainRdv->motifRdv->nom ?? 'Consultation' }}</span>
                    <span class="pp-rdv-medecin">Dr. {{ $prochainRdv->employee->full_name }}</span>
                    @if($prochainRdv->canBeCancelled())
                        <form action="{{ route('portail.rdv.annuler', $prochainRdv) }}" method="POST" onsubmit="return confirm('Annuler ce rendez-vous ?');">
                            @csrf
                            <button type="submit" class="pp-lien-discret">Annuler ce rendez-vous</button>
                        </form>
                    @endif
                </div>
            @else
                <div class="pp-vide">
                    <p>Aucun rendez-vous à venir.</p>
                    <a href="{{ route('rdv') }}" class="pp-btn-principal-inline">Prendre rendez-vous</a>
                </div>
            @endif

            <p class="pp-section-titre pp-mt">En un coup d'œil</p>
            <div class="pp-stats">
                <div class="pp-stat"><span class="pp-stat-nombre">{{ $consultations->count() }}</span><span class="pp-stat-label">Consultations enregistrées</span></div>
                <div class="pp-stat"><span class="pp-stat-nombre">{{ $historiqueRdv->count() }}</span><span class="pp-stat-label">Rendez-vous passés</span></div>
            </div>
        </section>

        {{-- ---------- RENDEZ-VOUS ---------- --}}
        <section class="pp-panel" data-panel="rdv" hidden>
            <p class="pp-section-titre">Historique</p>
            @forelse($historiqueRdv as $rdv)
                <div class="pp-ligne-rdv">
                    <div>
                        <span class="pp-ligne-titre">{{ $rdv->motifRdv->nom ?? 'Consultation' }}</span>
                        <span class="pp-ligne-sous">Dr. {{ $rdv->employee->full_name }} — {{ $rdv->appointment_datetime->format('d/m/Y à H:i') }}</span>
                    </div>
                    <span class="pp-statut pp-statut-{{ $rdv->status }}">
                        {{ ['pending'=>'En attente','confirmed'=>'Confirmé','completed'=>'Terminé','cancelled'=>'Annulé','no_show'=>'Absence'][$rdv->status] ?? $rdv->status }}
                    </span>
                </div>
            @empty
                <p class="pp-vide-texte">Aucun rendez-vous passé pour l'instant.</p>
            @endforelse
        </section>

        {{-- ---------- DOSSIER MÉDICAL ---------- --}}
        <section class="pp-panel" data-panel="dossier" hidden>
            <p class="pp-section-titre">Antécédents</p>
            @if($patient->antecedant)
                <div class="pp-fiche">
                    @foreach([
                        'antecedents_medicaux' => 'Antécédents médicaux',
                        'antecedents_chirurgicaux' => 'Antécédents chirurgicaux',
                        'antecedents_gyneco_obstetricaux' => 'Antécédents gynéco-obstétricaux',
                        'antecedents_familiaux' => 'Antécédents familiaux',
                        'allergies' => 'Allergies',
                        'traitements_cours' => 'Traitements en cours',
                    ] as $champ => $libelle)
                        @if($patient->antecedant->$champ)
                            <div class="pp-fiche-ligne">
                                <span class="pp-fiche-label">{{ $libelle }}</span>
                                <span class="pp-fiche-valeur">{{ $patient->antecedant->$champ }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            @else
                <p class="pp-vide-texte">Aucun antécédent enregistré.</p>
            @endif

            <p class="pp-section-titre pp-mt">Consultations récentes</p>
            @forelse($consultations as $c)
                <div class="pp-ligne-rdv">
                    <div>
                        <span class="pp-ligne-titre">{{ $c->motif ?: 'Consultation' }}</span>
                        <span class="pp-ligne-sous">{{ $c->created_at->format('d/m/Y') }}</span>
                    </div>
                </div>
            @empty
                <p class="pp-vide-texte">Aucune consultation enregistrée.</p>
            @endforelse

            <p class="pp-section-titre pp-mt">Résultats de laboratoire</p>
            <p class="pp-vide-texte pp-a-venir">Bientôt disponible ici, directement après validation par le laboratoire.</p>
        </section>

        {{-- ---------- ACCÈS & PARTAGE ---------- --}}
        <section class="pp-panel" data-panel="acces" hidden>
            <p class="pp-hint">Vous décidez qui peut consulter votre dossier complet. Chaque accès peut être retiré à tout moment.</p>

            <p class="pp-section-titre">Accès actifs</p>
            @forelse($consentements->where('statut', 'actif') as $c)
                <div class="pp-ligne-rdv">
                    <div>
                        <span class="pp-ligne-titre">{{ class_basename($c->beneficiaire_type) }} #{{ $c->beneficiaire_id }}</span>
                        <span class="pp-ligne-sous">Jusqu'au {{ $c->expire_le?->format('d/m/Y') }}</span>
                    </div>
                    <form action="{{ route('portail.consentement.revoquer', $c) }}" method="POST" onsubmit="return confirm('Retirer cet accès ?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="pp-lien-discret pp-lien-danger">Retirer</button>
                    </form>
                </div>
            @empty
                <p class="pp-vide-texte">Personne n'a d'accès élargi à votre dossier pour l'instant.</p>
            @endforelse

            @if($demandesEnAttente->isNotEmpty())
                <p class="pp-section-titre pp-mt">En attente de votre confirmation</p>
                @foreach($demandesEnAttente as $d)
                    <div class="pp-ligne-rdv">
                        <div>
                            <span class="pp-ligne-titre">{{ class_basename($d->demandeur_type) }} #{{ $d->demandeur_id }}</span>
                            <span class="pp-ligne-sous">Un SMS de confirmation vous a été envoyé</span>
                        </div>
                    </div>
                @endforeach
            @endif
        </section>

        {{-- ---------- FAMILLE ---------- --}}
        <section class="pp-panel" data-panel="famille" hidden>
            <p class="pp-section-titre">Liens familiaux</p>
            @forelse($patient->relationsFamiliales as $r)
                <div class="pp-ligne-rdv">
                    <div>
                        <span class="pp-ligne-titre">{{ $r->personneLiee->getFullName() }}</span>
                        <span class="pp-ligne-sous">{{ ucfirst($r->type_relation->value) }}</span>
                    </div>
                </div>
            @empty
                <p class="pp-vide-texte">Aucun lien familial enregistré. Rendez-vous à l'accueil de la clinique pour en ajouter.</p>
            @endforelse

            @if($autresDossiers->isNotEmpty())
                <p class="pp-section-titre pp-mt">Dossiers que vous gérez</p>
                @foreach($autresDossiers as $autre)
                    <a href="{{ route('portail.dossier', $autre) }}" class="pp-ligne-rdv">
                        <span class="pp-ligne-titre">{{ $autre->getFullName() }}</span>
                    </a>
                @endforeach
            @endif
        </section>

    </main>

    {{-- ============ NAVIGATION BASSE ============ --}}
    <nav class="pp-tabbar">
        <button class="pp-tab is-active" data-tab="accueil">Accueil</button>
        <button class="pp-tab" data-tab="rdv">Rendez-vous</button>
        <button class="pp-tab" data-tab="dossier">Dossier</button>
        <button class="pp-tab" data-tab="acces">Accès</button>
        <button class="pp-tab" data-tab="famille">Famille</button>
    </nav>
</div>

<style>
:root{
    --bg:#F6F7F5; --surface:#FFFFFF; --ink:#1D2B26; --ink-soft:#5B6B65;
    --primary:#0E6659; --primary-dark:#0A4B41; --accent:#E2A33B;
    --line:#DCE3DF; --danger:#B3431E; --danger-bg:#FBEDE7;
}
*{box-sizing:border-box;}
body{margin:0;}
.pp-shell{
    font-family:-apple-system,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
    background:var(--bg); color:var(--ink);
    min-height:100vh; max-width:560px; margin:0 auto;
    display:flex; flex-direction:column;
}
.pp-header{padding:24px 20px 16px;}
.pp-header-top{display:flex; justify-content:space-between; align-items:flex-start; gap:12px;}
.pp-eyebrow{margin:0; font-size:.8rem; color:var(--ink-soft);}
.pp-nom{margin:2px 0 0; font-size:1.4rem; font-weight:600;}
.pp-switch{border:1px solid var(--line); border-radius:8px; padding:8px; font-size:.85rem; background:var(--surface); font-family:inherit; max-width:150px;}
.pp-id-chip{
    margin-top:14px; display:inline-flex; align-items:center; gap:8px;
    background:var(--surface); border:1px solid var(--line); border-radius:999px; padding:6px 14px;
}
.pp-id-label{font-size:.75rem; color:var(--ink-soft);}
.pp-id-valeur{font-family:"SF Mono",Consolas,monospace; font-weight:600; letter-spacing:.03em; font-size:.9rem;}

.pp-securite{
    background:var(--danger-bg); margin:0 20px 16px; padding:10px 14px; border-radius:8px;
    display:flex; flex-wrap:wrap; gap:16px; font-size:.85rem; color:var(--danger);
}
.pp-securite-item strong{display:block; font-size:.7rem; text-transform:none; opacity:.85;}

.pp-body{flex:1; padding:4px 20px 24px;}
.pp-section-titre{font-size:.95rem; font-weight:600; margin:0 0 10px;}
.pp-mt{margin-top:24px;}
.pp-hint{color:var(--ink-soft); font-size:.9rem; margin:0 0 20px; line-height:1.5;}

.pp-carte-rdv{
    background:var(--surface); border:1px solid var(--line); border-left:4px solid var(--primary);
    border-radius:10px; padding:16px; display:flex; flex-direction:column; gap:2px;
}
.pp-rdv-date{font-weight:600; font-size:1.05rem;}
.pp-rdv-motif{color:var(--ink-soft); font-size:.9rem;}
.pp-rdv-medecin{color:var(--ink-soft); font-size:.9rem; margin-bottom:8px;}

.pp-vide{background:var(--surface); border:1px dashed var(--line); border-radius:10px; padding:24px 16px; text-align:center;}
.pp-vide p{margin:0 0 14px; color:var(--ink-soft);}
.pp-vide-texte{color:var(--ink-soft); font-size:.9rem;}
.pp-a-venir{font-style:italic;}

.pp-btn-principal-inline{
    display:inline-block; background:var(--primary); color:#fff; text-decoration:none;
    padding:12px 22px; border-radius:8px; font-weight:600; font-size:.95rem;
}

.pp-stats{display:flex; gap:12px;}
.pp-stat{flex:1; background:var(--surface); border:1px solid var(--line); border-radius:10px; padding:16px; text-align:center;}
.pp-stat-nombre{display:block; font-size:1.6rem; font-weight:700; color:var(--primary);}
.pp-stat-label{display:block; font-size:.8rem; color:var(--ink-soft); margin-top:2px;}

.pp-ligne-rdv{
    display:flex; justify-content:space-between; align-items:center; gap:10px;
    background:var(--surface); border:1px solid var(--line); border-radius:8px;
    padding:12px 14px; margin-bottom:8px; text-decoration:none; color:inherit;
}
.pp-ligne-titre{display:block; font-weight:500; font-size:.95rem;}
.pp-ligne-sous{display:block; color:var(--ink-soft); font-size:.82rem; margin-top:2px;}

.pp-statut{font-size:.75rem; padding:4px 10px; border-radius:999px; white-space:nowrap; background:var(--line); color:var(--ink-soft);}
.pp-statut-confirmed{background:#EFF6F4; color:var(--primary);}
.pp-statut-completed{background:#EFF6F4; color:var(--primary-dark);}
.pp-statut-cancelled{background:var(--danger-bg); color:var(--danger);}
.pp-statut-pending{background:#FCF3E3; color:#8A5A12;}

.pp-fiche{background:var(--surface); border:1px solid var(--line); border-radius:10px; overflow:hidden;}
.pp-fiche-ligne{padding:12px 14px; border-bottom:1px solid var(--line);}
.pp-fiche-ligne:last-child{border-bottom:none;}
.pp-fiche-label{display:block; font-size:.78rem; color:var(--ink-soft); margin-bottom:3px;}
.pp-fiche-valeur{font-size:.92rem; line-height:1.5;}

.pp-lien-discret{background:none; border:none; color:var(--ink-soft); font-size:.85rem; text-decoration:underline; cursor:pointer; font-family:inherit; padding:0;}
.pp-lien-danger{color:var(--danger);}

.pp-tabbar{
    display:flex; background:var(--surface); border-top:1px solid var(--line);
    position:sticky; bottom:0; padding-bottom:env(safe-area-inset-bottom);
}
.pp-tab{
    flex:1; background:none; border:none; padding:12px 4px 10px; font-family:inherit;
    font-size:.75rem; color:var(--ink-soft); cursor:pointer; border-top:2px solid transparent;
}
.pp-tab.is-active{color:var(--primary); border-top-color:var(--primary); font-weight:600;}
</style>

<script>
document.querySelectorAll('.pp-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.pp-tab').forEach(t => t.classList.remove('is-active'));
        document.querySelectorAll('.pp-panel').forEach(p => p.hidden = true);
        tab.classList.add('is-active');
        document.querySelector(`.pp-panel[data-panel="${tab.dataset.tab}"]`).hidden = false;
        window.scrollTo(0,0);
    });
});
</script>
</body>
</html>
