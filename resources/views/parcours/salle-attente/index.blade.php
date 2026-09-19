<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="20">
    <meta name="robots" content="noindex">
    <title>Salle d'attente</title>
    {{--
        Écran de téléviseur, lu à 5 mètres : tailles proportionnelles à la
        largeur de l'écran (vw), contraste fort, aucune ressource externe.
        Seuls le prénom et l'initiale du nom s'affichent : lieu public.
    --}}
    <style>
        :root {
            --fond: #062a26;
            --panneau: #0b3d37;
            --panneau-clair: #10504a;
            --accent: #2dd4bf;
            --appel: #0f766e;
            --texte: #ffffff;
            --doux: rgba(255, 255, 255, .72);
        }
        * { box-sizing: border-box; }
        html, body { height: 100%; }
        body {
            margin: 0; padding: 2.4vw 3vw; overflow: hidden;
            background: var(--fond); color: var(--texte);
            font-family: -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            display: grid; grid-template-rows: auto 1fr auto; gap: 2vw;
        }

        /* En-tête */
        .entete { display: flex; align-items: center; justify-content: space-between; }
        .marque { display: flex; align-items: center; gap: 1vw; }
        .marque-icone { width: 3.2vw; height: 3.2vw; display: grid; place-items: center; border-radius: .8vw; background: var(--appel); }
        .marque-icone svg { width: 1.7vw; height: 1.7vw; }
        .marque h1 { margin: 0; font-size: 2.2vw; font-weight: 700; letter-spacing: -.01em; }
        .horloge { text-align: right; }
        .horloge b { display: block; font-size: 3.2vw; font-weight: 700; font-variant-numeric: tabular-nums; line-height: 1; }
        .horloge span { color: var(--doux); font-size: 1.2vw; }

        /* Colonnes */
        .colonnes { display: grid; grid-template-columns: 1.25fr 1fr; gap: 2.4vw; min-height: 0; }
        .titre-colonne { margin: 0 0 1vw; color: var(--accent); font-size: 1.35vw; font-weight: 700; }

        /* Appels en cours */
        .appels { display: grid; gap: 1.2vw; align-content: start; }
        .appel { display: grid; gap: .3vw; padding: 1.6vw 2vw; border-radius: 1.2vw; background: var(--panneau); border-left: .5vw solid var(--panneau-clair); }
        .appel-nom { font-size: 3.4vw; font-weight: 800; line-height: 1.1; letter-spacing: -.01em; }
        .appel-medecin { color: var(--doux); font-size: 1.6vw; }
        .appel-medecin b { color: var(--texte); font-weight: 600; }

        /* Le dernier appelé : c'est lui qui doit se lever */
        .appel.est-recent { background: var(--appel); border-left-color: var(--accent); padding: 2.4vw 2.4vw; }
        .appel.est-recent .appel-etiquette { color: #ccfbf1; font-size: 1.3vw; font-weight: 700; }
        .appel.est-recent .appel-nom { font-size: 5.2vw; }
        .appel.est-recent .appel-medecin { color: #e6fffa; font-size: 2vw; }
        .appel.est-nouveau { animation: signal 1.2s ease-in-out 3; }
        @keyframes signal {
            0%, 100% { box-shadow: 0 0 0 0 rgba(45, 212, 191, 0); }
            50%      { box-shadow: 0 0 0 .7vw rgba(45, 212, 191, .45); }
        }

        /* Prochains passages */
        .file { display: grid; gap: .8vw; align-content: start; margin: 0; padding: 0; list-style: none; }
        .file li { display: grid; grid-template-columns: 3.4vw minmax(0, 1fr) auto; align-items: center; gap: 1.2vw; padding: 1vw 1.4vw; border-radius: 1vw; background: var(--panneau); }
        .file .rang { width: 3.4vw; height: 3.4vw; display: grid; place-items: center; border-radius: 50%; background: var(--panneau-clair); font-size: 1.6vw; font-weight: 700; font-variant-numeric: tabular-nums; }
        .file li:first-child .rang { background: var(--accent); color: var(--fond); }
        .file .nom { font-size: 2.1vw; font-weight: 700; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .file .medecin { color: var(--doux); font-size: 1.3vw; white-space: nowrap; }
        .suite { margin-top: .6vw; color: var(--doux); font-size: 1.3vw; }

        .vide { padding: 2vw; border-radius: 1.2vw; background: var(--panneau); color: var(--doux); font-size: 1.8vw; }

        /* Pied */
        .pied { display: flex; justify-content: space-between; align-items: center; color: var(--doux); font-size: 1.25vw; }
        .pied b { color: var(--texte); }

        @media (prefers-reduced-motion: reduce) { .appel.est-nouveau { animation: none; } }
        /* Écran vertical ou petit écran : une seule colonne */
        @media (max-aspect-ratio: 1/1) {
            body { overflow: auto; }
            .colonnes { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    @php
        $prenom = fn ($p) => trim($p->first_name . ' ' . mb_strtoupper(mb_substr((string) $p->last_name, 0, 1)) . '.');
        // Le plus récemment appelé en premier : c'est lui que la salle doit voir.
        $appels = $enConsultation->sortByDesc(fn ($v) => $v->appele_le?->timestamp ?? 0)->values();
        $mois = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
        $jours = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
        $prochains = $enAttente->take(7);
    @endphp

    <header class="entete">
        <div class="marque">
            <span class="marque-icone" aria-hidden="true">
                <svg viewBox="0 0 16 16"><path d="M6 1.5h4V6h4.5v4H10v4.5H6V10H1.5V6H6z" fill="#fff"/></svg>
            </span>
            <h1>Salle d'attente</h1>
        </div>
        <div class="horloge">
            <b id="heure">{{ now()->format('H:i') }}</b>
            <span>{{ ucfirst($jours[today()->dayOfWeek]) }} {{ today()->day }} {{ $mois[today()->month] }}</span>
        </div>
    </header>

    <main class="colonnes">
        <section>
            <h2 class="titre-colonne">Appel en cours</h2>
            <div class="appels">
                @forelse($appels->take(3) as $v)
                    @php $recent = $loop->first; $nouveau = $recent && $v->appele_le && $v->appele_le->gt(now()->subMinute()); @endphp
                    <div class="appel {{ $recent ? 'est-recent' : '' }} {{ $nouveau ? 'est-nouveau' : '' }}">
                        @if($recent)<span class="appel-etiquette">Merci de vous présenter</span>@endif
                        <span class="appel-nom">{{ $prenom($v->patient) }}</span>
                        <span class="appel-medecin">chez <b>{{ $v->medecin->nom_affiche }}</b></span>
                    </div>
                @empty
                    <div class="vide">Aucun appel pour le moment.</div>
                @endforelse
            </div>
        </section>

        <section>
            <h2 class="titre-colonne">Prochains passages</h2>
            @if($prochains->isEmpty())
                <div class="vide">Personne en attente.</div>
            @else
                <ol class="file">
                    @foreach($prochains as $v)
                        <li>
                            <span class="rang">{{ $loop->iteration }}</span>
                            <span class="nom">{{ $prenom($v->patient) }}</span>
                            <span class="medecin">{{ $v->medecin->nom_affiche }}</span>
                        </li>
                    @endforeach
                </ol>
                @if($enAttente->count() > $prochains->count())
                    <p class="suite">Et {{ $enAttente->count() - $prochains->count() }} autre{{ $enAttente->count() - $prochains->count() > 1 ? 's' : '' }} personne{{ $enAttente->count() - $prochains->count() > 1 ? 's' : '' }} ensuite.</p>
                @endif
            @endif
        </section>
    </main>

    <footer class="pied">
        <span>Vous serez appelé par votre prénom. <b>Les urgences passent en priorité.</b></span>
        <span>Mise à jour automatique</span>
    </footer>

    <script>
        // Horloge à la minute, sans attendre le rechargement de la page.
        (function () {
            var heure = document.getElementById('heure');
            setInterval(function () {
                var d = new Date();
                heure.textContent = String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
            }, 5000);
        })();
    </script>
</body>
</html>
