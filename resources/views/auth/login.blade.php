<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>Connexion — Hali</title>

    {{-- Favicon en SVG intégré : aucune requête réseau supplémentaire. --}}
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%230f766e'/%3E%3Cpath d='M13.5 8h5v5.5H24v5h-5.5V24h-5v-5.5H8v-5h5.5z' fill='%23fff'/%3E%3C/svg%3E">

    {{--
        Page autonome : ni Bootstrap, ni police externe, ni image.
        Tout tient dans ce fichier (illustration SVG comprise) pour que
        la connexion s'affiche vite même sur une connexion 2G/3G.
        Les couleurs reprennent exactement les jetons de hali.css.
    --}}
    <style>
        :root {
            --hali-primaire: #0f766e;
            --hali-primaire-fonce: #115e59;
            --hali-primaire-clair: #ccfbf1;
            --hali-primaire-pale: #f0fdfa;
            --hali-danger: #b91c1c;
            --hali-danger-pale: #fef2f2;
            --hali-succes: #15803d;
            --hali-succes-pale: #f0fdf4;
            --hali-alerte: #b45309;
            --hali-alerte-pale: #fffbeb;
            --hali-encre: #111827;
            --hali-texte: #374151;
            --hali-discret: #6b7280;
            --hali-bordure: #e5e7eb;
            --hali-surface: #ffffff;
            --hali-rayon-petit: 8px;
        }

        *, *::before, *::after { box-sizing: border-box; }

        html, body { height: 100%; }

        body {
            margin: 0;
            background: var(--hali-surface);
            color: var(--hali-texte);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 15px;
            line-height: 1.55;
            -webkit-font-smoothing: antialiased;
        }

        /* ------------------------------------------------ Écran partagé */

        .connexion {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(400px, 480px);
            min-height: 100vh;
        }

        /* ------------------------------------------------ Volet illustration */

        .volet {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 48px 56px;
            background: var(--hali-primaire-pale);
            overflow: hidden;
        }

        .volet-marque {
            position: absolute;
            top: 32px;
            left: 40px;
        }

        .volet-illustration {
            width: 100%;
            max-width: 560px;
            height: auto;
        }

        .volet-message {
            max-width: 440px;
            margin: 8px auto 0;
            text-align: center;
        }

        .volet-message h2 {
            margin: 0 0 8px;
            color: var(--hali-encre);
            font-size: 1.4rem;
            font-weight: 650;
            line-height: 1.3;
            letter-spacing: -.015em;
        }

        .volet-message p {
            margin: 0;
            color: var(--hali-discret);
            font-size: .95rem;
        }

        /* Le téléphone flotte doucement : le seul mouvement de la page. */
        .flotte { animation: flotter 5s ease-in-out infinite; }

        @keyframes flotter {
            0%, 100% { transform: translateY(0); }
            50%      { transform: translateY(-8px); }
        }

        /* ------------------------------------------------ Marque */

        .marque {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: var(--hali-encre);
            text-decoration: none;
        }

        .marque-icone {
            width: 36px;
            height: 36px;
            flex: 0 0 36px;
            display: grid;
            place-items: center;
            border-radius: 10px;
            background: var(--hali-primaire);
        }

        .marque-nom {
            font-size: 1.35rem;
            font-weight: 700;
            letter-spacing: .02em;
        }

        /* ------------------------------------------------ Volet formulaire */

        .formulaire {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 48px 56px;
            background: var(--hali-surface);
        }

        .formulaire-contenu {
            width: 100%;
            max-width: 380px;
            margin: 0 auto;
        }

        /* Sur grand écran la marque est déjà dans le volet de gauche. */
        .formulaire .marque { display: none; margin-bottom: 36px; }

        .titre {
            margin: 0 0 6px;
            color: var(--hali-encre);
            font-size: 1.6rem;
            font-weight: 700;
            line-height: 1.25;
            letter-spacing: -.02em;
        }

        .sous-titre {
            margin: 0 0 28px;
            color: var(--hali-discret);
        }

        /* ------------------------------------------------ Messages */

        .message {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            margin-bottom: 20px;
            padding: 12px 14px;
            border-left: 4px solid;
            border-radius: var(--hali-rayon-petit);
            font-size: .88rem;
        }

        .message svg { flex: 0 0 18px; margin-top: 1px; }
        .message-erreur { background: var(--hali-danger-pale); border-color: var(--hali-danger); color: #7f1d1d; }
        .message-succes { background: var(--hali-succes-pale); border-color: var(--hali-succes); color: #14532d; }

        /* ------------------------------------------------ Champs */

        .champ { margin-bottom: 18px; }

        .champ-libelle {
            display: block;
            margin-bottom: 6px;
            color: var(--hali-encre);
            font-size: .85rem;
            font-weight: 600;
        }

        /* Le cadre porte la bordure : préfixe et bouton œil restent dedans. */
        .champ-cadre {
            display: flex;
            align-items: stretch;
            min-height: 46px;
            border: 1px solid var(--hali-bordure);
            border-radius: var(--hali-rayon-petit);
            background: var(--hali-surface);
            transition: border-color .12s ease, box-shadow .12s ease;
        }

        .champ-cadre:focus-within {
            border-color: var(--hali-primaire);
            box-shadow: 0 0 0 3px var(--hali-primaire-clair);
        }

        .champ-cadre.est-invalide { border-color: var(--hali-danger); }
        .champ-cadre.est-invalide:focus-within { box-shadow: 0 0 0 3px var(--hali-danger-pale); }

        .champ-prefixe {
            display: flex;
            align-items: center;
            padding: 0 12px;
            border-right: 1px solid var(--hali-bordure);
            color: var(--hali-discret);
            font-size: .92rem;
            font-variant-numeric: tabular-nums;
            user-select: none;
        }

        .champ-saisie {
            flex: 1;
            min-width: 0;
            padding: 0 14px;
            border: 0;
            border-radius: var(--hali-rayon-petit);
            background: transparent;
            color: var(--hali-encre);
            font: inherit;
            font-size: .98rem;
            outline: none;
        }

        .champ-saisie::placeholder { color: #9ca3af; }

        .champ-oeil {
            display: grid;
            place-items: center;
            width: 44px;
            border: 0;
            border-radius: var(--hali-rayon-petit);
            background: transparent;
            color: var(--hali-discret);
            cursor: pointer;
        }

        .champ-oeil:hover { color: var(--hali-primaire); }
        .champ-oeil:focus-visible { outline: 2px solid var(--hali-primaire); outline-offset: -4px; }

        .champ-aide {
            margin: 6px 0 0;
            font-size: .8rem;
        }

        .champ-aide-erreur { color: var(--hali-danger); }
        .champ-aide-alerte { color: var(--hali-alerte); }

        /* ------------------------------------------------ Options */

        .options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin: 4px 0 24px;
            font-size: .88rem;
        }

        .souvenir {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--hali-texte);
            cursor: pointer;
        }

        .souvenir input {
            width: 17px;
            height: 17px;
            margin: 0;
            accent-color: var(--hali-primaire);
            cursor: pointer;
        }

        .lien {
            color: var(--hali-primaire);
            font-weight: 600;
            text-decoration: none;
        }

        .lien:hover { color: var(--hali-primaire-fonce); text-decoration: underline; }
        .lien:focus-visible { outline: 2px solid var(--hali-primaire); outline-offset: 2px; border-radius: 4px; }

        /* ------------------------------------------------ Bouton */

        .bouton {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            min-height: 48px;
            border: 0;
            border-radius: var(--hali-rayon-petit);
            background: var(--hali-primaire);
            color: #fff;
            font: inherit;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(15, 118, 110, .22);
            transition: background-color .12s ease, box-shadow .12s ease;
        }

        .bouton:hover { background: var(--hali-primaire-fonce); }
        .bouton:focus-visible { outline: 2px solid var(--hali-primaire); outline-offset: 3px; }
        .bouton[disabled] { cursor: progress; opacity: .85; box-shadow: none; }

        .bouton-roue {
            display: none;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, .4);
            border-top-color: #fff;
            border-radius: 50%;
            animation: tourner .7s linear infinite;
        }

        .bouton.en-cours .bouton-roue { display: inline-block; }

        @keyframes tourner { to { transform: rotate(360deg); } }

        /* ------------------------------------------------ Pied */

        .aide {
            margin: 28px 0 0;
            padding-top: 20px;
            border-top: 1px solid var(--hali-bordure);
            color: var(--hali-discret);
            font-size: .85rem;
            text-align: center;
        }

        .pied {
            margin: 32px 0 0;
            color: #9ca3af;
            font-size: .78rem;
            text-align: center;
        }

        /* ------------------------------------------------ Tablette et mobile */

        @media (max-width: 991.98px) {
            .connexion { grid-template-columns: 1fr; }

            /* L'illustration disparaît : sur petit écran, le formulaire d'abord. */
            .volet { display: none; }

            .formulaire { padding: 40px 24px; }
            .formulaire .marque { display: inline-flex; }
        }

        @media (max-width: 380px) {
            .options { flex-direction: column; align-items: flex-start; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation: none !important; transition: none !important; }
        }
    </style>
</head>
<body>

<main class="connexion">

    {{-- ============================================ Volet illustration --}}
    <section class="volet" aria-hidden="true">

        <div class="volet-marque">
            <span class="marque">
                <span class="marque-icone">
                    <svg width="18" height="18" viewBox="0 0 16 16"><path d="M6 1.5h4V6h4.5v4H10v4.5H6V10H1.5V6H6z" fill="#fff"/></svg>
                </span>
                <span class="marque-nom">HALI</span>
            </span>
        </div>

        {{-- Illustration originale : l'agenda du jour, la confirmation par SMS,
             le suivi des soins. Ce que fait vraiment la plateforme. --}}
        <svg class="volet-illustration" viewBox="0 0 560 470" xmlns="http://www.w3.org/2000/svg" role="presentation">
            <defs>
                <filter id="ombre" x="-20%" y="-20%" width="140%" height="150%">
                    <feDropShadow dx="0" dy="8" stdDeviation="10" flood-color="#0f766e" flood-opacity=".12"/>
                </filter>
            </defs>

            {{-- Fond : disque doux et orbite pointillée --}}
            <circle cx="280" cy="240" r="200" fill="#ccfbf1" opacity=".55"/>
            <ellipse cx="280" cy="250" rx="255" ry="150" fill="none" stroke="#99f6e4" stroke-width="1.5" stroke-dasharray="4 8" transform="rotate(-12 280 250)"/>
            <ellipse cx="280" cy="448" rx="190" ry="10" fill="#0f766e" opacity=".07"/>

            {{-- Agenda du jour --}}
            <g filter="url(#ombre)">
                <rect x="120" y="112" width="290" height="300" rx="18" fill="#fff"/>
            </g>
            <text x="144" y="150" font-family="-apple-system,Segoe UI,Roboto,Arial,sans-serif" font-size="15" font-weight="700" fill="#111827">Aujourd'hui</text>
            <rect x="322" y="134" width="66" height="24" rx="12" fill="#f0fdfa"/>
            <text x="355" y="150" text-anchor="middle" font-family="-apple-system,Segoe UI,Roboto,Arial,sans-serif" font-size="11" font-weight="600" fill="#0f766e">14 rdv</text>
            <line x1="120" y1="172" x2="410" y2="172" stroke="#f1f2f4" stroke-width="1.5"/>

            {{-- Les blocs n'ont pas la même hauteur : chaque motif a sa durée. --}}
            <g font-family="-apple-system,Segoe UI,Roboto,Arial,sans-serif" font-size="11" fill="#6b7280">
                <text x="140" y="199">08:30</text>
                <text x="140" y="263">08:50</text>
                <text x="140" y="305">08:55</text>
                <text x="140" y="369">09:15</text>
            </g>

            <rect x="186" y="184" width="206" height="56" rx="10" fill="#f0fdfa"/>
            <rect x="186" y="184" width="4" height="56" rx="2" fill="#0f766e"/>
            <circle cx="212" cy="212" r="13" fill="#99f6e4"/>
            <rect x="234" y="202" width="82" height="8" rx="4" fill="#374151" opacity=".75"/>
            <rect x="234" y="216" width="56" height="6" rx="3" fill="#9ca3af" opacity=".6"/>
            <rect x="340" y="204" width="40" height="16" rx="8" fill="#dcfce7"/>
            <circle cx="352" cy="212" r="3" fill="#15803d"/>
            <rect x="358" y="210" width="15" height="4" rx="2" fill="#15803d" opacity=".6"/>

            <rect x="186" y="248" width="206" height="30" rx="10" fill="#fff" stroke="#e5e7eb"/>
            <circle cx="204" cy="263" r="9" fill="#fde68a"/>
            <rect x="222" y="259" width="70" height="7" rx="3.5" fill="#374151" opacity=".6"/>
            <rect x="346" y="256" width="34" height="14" rx="7" fill="#fffbeb"/>
            <circle cx="356" cy="263" r="2.5" fill="#b45309"/>

            <rect x="186" y="286" width="206" height="56" rx="10" fill="#fff" stroke="#e5e7eb"/>
            <circle cx="212" cy="314" r="13" fill="#c7d2fe"/>
            <rect x="234" y="304" width="90" height="8" rx="4" fill="#374151" opacity=".6"/>
            <rect x="234" y="318" width="48" height="6" rx="3" fill="#9ca3af" opacity=".5"/>
            <rect x="340" y="306" width="40" height="16" rx="8" fill="#f3f4f6"/>

            <rect x="186" y="350" width="206" height="42" rx="10" fill="#fff" stroke="#e5e7eb"/>
            <circle cx="208" cy="371" r="11" fill="#fbcfe8"/>
            <rect x="228" y="367" width="76" height="7" rx="3.5" fill="#374151" opacity=".5"/>

            {{-- Calendrier --}}
            <g transform="translate(-36 -30)">
            <g filter="url(#ombre)">
                <rect x="52" y="62" width="120" height="112" rx="14" fill="#fff"/>
            </g>
            <path d="M52 76a14 14 0 0 1 14-14h92a14 14 0 0 1 14 14v14H52z" fill="#0f766e"/>
            <rect x="80" y="54" width="6" height="16" rx="3" fill="#115e59"/>
            <rect x="138" y="54" width="6" height="16" rx="3" fill="#115e59"/>
            <g fill="#e5e7eb">
                <circle cx="74" cy="108" r="6"/><circle cx="98" cy="108" r="6"/><circle cx="122" cy="108" r="6"/><circle cx="146" cy="108" r="6"/>
                <circle cx="74" cy="130" r="6"/><circle cx="122" cy="130" r="6"/><circle cx="146" cy="130" r="6"/>
                <circle cx="74" cy="152" r="6"/><circle cx="98" cy="152" r="6"/><circle cx="146" cy="152" r="6"/>
            </g>
            <circle cx="98" cy="130" r="8" fill="#0f766e"/>
            <circle cx="122" cy="152" r="6" fill="#f59e0b"/>
            </g>

            {{-- Suivi des constantes --}}
            <g transform="translate(-14 62)">
            <g filter="url(#ombre)">
                <rect x="40" y="318" width="138" height="70" rx="14" fill="#fff"/>
            </g>
            <circle cx="70" cy="353" r="15" fill="#fef2f2"/>
            <path d="M70 360.5l-6.2-6a3.8 3.8 0 0 1 5.4-5.4l.8.8.8-.8a3.8 3.8 0 0 1 5.4 5.4z" fill="#b91c1c"/>
            <polyline points="94,354 106,354 112,340 120,368 127,346 132,354 164,354" fill="none" stroke="#0f766e" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
            </g>

            {{-- Croix médicale --}}
            <circle cx="456" cy="92" r="30" fill="#0f766e"/>
            <path d="M450 76h12v10h10v12h-10v10h-12v-10h-10v-12h10z" fill="#fff"/>

            {{-- Téléphone avec la confirmation SMS --}}
            <g class="flotte">
                <g filter="url(#ombre)">
                    <rect x="370" y="200" width="128" height="232" rx="22" fill="#111827"/>
                </g>
                <rect x="378" y="210" width="112" height="212" rx="16" fill="#fff"/>
                <rect x="418" y="216" width="32" height="6" rx="3" fill="#111827"/>

                <rect x="388" y="240" width="92" height="74" rx="12" fill="#f0fdfa"/>
                <circle cx="404" cy="258" r="9" fill="#0f766e"/>
                <polyline points="399.5,258 403,261.5 409,255" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <text x="418" y="262" font-family="-apple-system,Segoe UI,Roboto,Arial,sans-serif" font-size="10" font-weight="700" fill="#115e59">Confirmé</text>
                <rect x="398" y="278" width="72" height="6" rx="3" fill="#0f766e" opacity=".35"/>
                <rect x="398" y="290" width="54" height="6" rx="3" fill="#0f766e" opacity=".25"/>
                <rect x="398" y="302" width="38" height="5" rx="2.5" fill="#0f766e" opacity=".2"/>

                <rect x="388" y="324" width="70" height="30" rx="10" fill="#f3f4f6"/>
                <rect x="398" y="334" width="44" height="5" rx="2.5" fill="#9ca3af" opacity=".7"/>
                <rect x="398" y="343" width="30" height="5" rx="2.5" fill="#9ca3af" opacity=".5"/>

                <rect x="388" y="386" width="92" height="24" rx="12" fill="#0f766e"/>
                <rect x="412" y="396" width="44" height="5" rx="2.5" fill="#fff" opacity=".85"/>
            </g>

            {{-- Petites croix et points : le rappel du soin, sans surcharge --}}
            <g fill="#f59e0b">
                <path d="M228 50h6v8h8v6h-8v8h-6v-8h-8v-6h8z" transform="rotate(12 234 61)"/>
                <path d="M514 300h4v6h6v4h-6v6h-4v-6h-6v-4h6z"/>
            </g>
            <g fill="#0f766e" opacity=".5">
                <path d="M26 230h4v6h6v4h-6v6h-4v-6h-6v-4h6z"/>
                <path d="M300 438h3v5h5v3h-5v5h-3v-5h-5v-3h5z"/>
            </g>
            <circle cx="520" cy="190" r="6" fill="#5eead4"/>
            <circle cx="208" cy="436" r="5" fill="#5eead4"/>
            <circle cx="350" cy="62" r="4" fill="#5eead4"/>
            <circle cx="180" cy="36" r="4" fill="#f59e0b" opacity=".7"/>
            <rect x="500" y="400" width="14" height="14" rx="3" fill="#fde68a" transform="rotate(20 507 407)"/>
        </svg>

        <div class="volet-message">
            <h2>Moins d'attente pour vos patients, une vision claire pour votre clinique.</h2>
            <p>Rendez-vous, consultations, facturation et assurance réunis au même endroit.</p>
        </div>
    </section>

    {{-- ============================================ Volet formulaire --}}
    <section class="formulaire">
        <div class="formulaire-contenu">

            <span class="marque">
                <span class="marque-icone">
                    <svg width="18" height="18" viewBox="0 0 16 16" aria-hidden="true"><path d="M6 1.5h4V6h4.5v4H10v4.5H6V10H1.5V6H6z" fill="#fff"/></svg>
                </span>
                <span class="marque-nom">HALI</span>
            </span>

            <h1 class="titre">Bienvenue sur Hali</h1>
            <p class="sous-titre">Connectez-vous pour accéder à l'espace de gestion de votre clinique.</p>

            @if (session('status'))
                <div class="message message-succes" role="status">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="8 12 11 15 16 9"/></svg>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="message message-erreur" role="alert">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="7.5" x2="12" y2="13"/><line x1="12" y1="16.5" x2="12" y2="16.5"/></svg>
                    <span>
                        @foreach ($errors->all() as $error)
                            {{ $error }}@if (! $loop->last)<br>@endif
                        @endforeach
                    </span>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" id="formConnexion" novalidate>
                @csrf

                <div class="champ">
                    <label class="champ-libelle" for="phone">Téléphone</label>
                    <div class="champ-cadre @error('phone') est-invalide @enderror">
                        <span class="champ-prefixe" aria-hidden="true">+224</span>
                        <input
                            type="tel"
                            name="phone"
                            id="phone"
                            class="champ-saisie"
                            inputmode="numeric"
                            placeholder="622 00 00 00"
                            autocomplete="username"
                            value="{{ old('phone') }}"
                            maxlength="16"
                            required
                            @if (! old('phone')) autofocus @endif
                            @error('phone') aria-invalid="true" @enderror
                        >
                    </div>
                </div>

                <div class="champ">
                    <label class="champ-libelle" for="password">Mot de passe</label>
                    <div class="champ-cadre @error('password') est-invalide @enderror">
                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="champ-saisie"
                            placeholder="Votre mot de passe"
                            autocomplete="current-password"
                            required
                            @if (old('phone')) autofocus @endif
                            aria-describedby="majuscules"
                        >
                        <button type="button" class="champ-oeil" id="basculerMdp" aria-label="Afficher le mot de passe" aria-pressed="false">
                            <svg id="oeilOuvert" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg id="oeilFerme" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display:none"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.5 18.5 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                        </button>
                    </div>
                    <p class="champ-aide champ-aide-alerte" id="majuscules" hidden>La touche Verr. Maj est activée.</p>
                </div>

                <div class="options">
                    <label class="souvenir">
                        <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                        Rester connecté
                    </label>

                    @if (Route::has('password.request'))
                        <a class="lien" href="{{ route('password.request') }}">Mot de passe oublié ?</a>
                    @endif
                </div>

                <button type="submit" class="bouton" id="boutonConnexion">
                    <span class="bouton-roue" aria-hidden="true"></span>
                    <span class="bouton-texte">Se connecter</span>
                </button>
            </form>

            <p class="aide">Pas encore de compte ? Demandez à l'administrateur de votre clinique de vous en créer un.</p>

            <p class="pied">Plateforme de gestion clinique — Guinée</p>
        </div>
    </section>

</main>

<script>
(function () {
    var mdp = document.getElementById('password');
    var bascule = document.getElementById('basculerMdp');
    var ouvert = document.getElementById('oeilOuvert');
    var ferme = document.getElementById('oeilFerme');
    var majuscules = document.getElementById('majuscules');
    var formulaire = document.getElementById('formConnexion');
    var bouton = document.getElementById('boutonConnexion');
    var texteBouton = bouton.querySelector('.bouton-texte');

    // Afficher / masquer le mot de passe.
    bascule.addEventListener('click', function () {
        var visible = mdp.type === 'text';
        mdp.type = visible ? 'password' : 'text';
        ouvert.style.display = visible ? '' : 'none';
        ferme.style.display = visible ? 'none' : '';
        bascule.setAttribute('aria-pressed', String(!visible));
        bascule.setAttribute('aria-label', visible ? 'Afficher le mot de passe' : 'Masquer le mot de passe');
        mdp.focus();
    });

    // Prévenir quand Verr. Maj est actif : cause n°1 des « mot de passe incorrect ».
    function verifierMajuscules(e) {
        if (e.getModifierState) majuscules.hidden = !e.getModifierState('CapsLock');
    }
    mdp.addEventListener('keydown', verifierMajuscules);
    mdp.addEventListener('keyup', verifierMajuscules);
    mdp.addEventListener('blur', function () { majuscules.hidden = true; });

    // Un seul envoi : sur réseau lent, un double clic crée deux requêtes
    // et la seconde échoue sur le jeton CSRF déjà consommé.
    formulaire.addEventListener('submit', function (e) {
        if (!formulaire.checkValidity()) {
            e.preventDefault();
            var invalide = formulaire.querySelector(':invalid');
            if (invalide) {
                invalide.closest('.champ-cadre').classList.add('est-invalide');
                invalide.focus();
            }
            return;
        }
        if (bouton.disabled) { e.preventDefault(); return; }
        bouton.disabled = true;
        bouton.classList.add('en-cours');
        texteBouton.textContent = 'Connexion en cours…';
    });

    // Retire le rouge dès que l'utilisateur corrige le champ.
    formulaire.addEventListener('input', function (e) {
        var cadre = e.target.closest('.champ-cadre');
        if (cadre) cadre.classList.remove('est-invalide');
    });

    // Retour arrière du navigateur : on remet le bouton dans son état normal.
    window.addEventListener('pageshow', function () {
        bouton.disabled = false;
        bouton.classList.remove('en-cours');
        texteBouton.textContent = 'Se connecter';
    });
})();
</script>

</body>
</html>
