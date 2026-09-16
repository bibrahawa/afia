<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Prendre rendez-vous</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    <style>
        :root {
            --primary: #087f6b;
            --primary-dark: #056655;
            --primary-soft: #e9f7f3;
            --accent: #e5a23c;
            --bg: #f5f7f6;
            --surface: #ffffff;
            --surface-soft: #f8faf9;
            --text: #17231f;
            --text-soft: #66756f;
            --text-muted: #8a9691;
            --border: #e1e8e5;
            --border-strong: #cfdad5;
            --danger: #c4472d;
            --danger-soft: #fff1ed;
            --warning: #b6791f;
            --warning-soft: #fdf3e3;
            --success: #087f6b;
            --radius-sm: 10px;
            --radius-md: 14px;
            --radius-lg: 20px;
            --shadow-sm: 0 2px 8px rgba(20, 40, 34, .04);
            --shadow-md: 0 8px 30px rgba(20, 40, 34, .08);
            --transition: 180ms ease;
        }
        *, *::before, *::after { box-sizing: border-box; }
        html { min-height: 100%; scroll-behavior: smooth; }
        body {
            margin: 0; min-height: 100vh; background: var(--bg); color: var(--text);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            -webkit-font-smoothing: antialiased; text-rendering: optimizeLegibility;
        }
        button, input, select { font: inherit; }
        button { -webkit-tap-highlight-color: transparent; }
        .rdv-app { min-height: 100vh; display: flex; flex-direction: column; }
        .rdv-shell { width: 100%; min-height: 100vh; display: flex; flex-direction: column; background: var(--bg); }
        @media (min-width: 768px) {
            body { display: flex; justify-content: center; padding: 28px; }
            .rdv-shell { max-width: 760px; min-height: calc(100vh - 56px); border: 1px solid var(--border); border-radius: 24px; overflow: hidden; background: var(--bg); box-shadow: var(--shadow-md); }
        }
        .rdv-network-banner {
            display: flex; align-items: center; gap: 9px; padding: 10px 20px; background: var(--warning-soft); color: var(--warning);
            font-size: .78rem; font-weight: 650; border-bottom: 1px solid #f3e2bd; transform: translateY(-100%); transition: transform 220ms ease;
        }
        .rdv-network-banner.is-visible { transform: translateY(0); }
        .rdv-network-banner button { margin-left: auto; padding: 5px 10px; border: 1px solid #e6c988; border-radius: 8px; background: transparent; color: var(--warning); font-size: .74rem; font-weight: 700; cursor: pointer; }
        .rdv-header { padding: max(18px, env(safe-area-inset-top)) 20px 14px; background: var(--surface); border-bottom: 1px solid var(--border); }
        .rdv-header-inner { max-width: 620px; margin: auto; }
        .rdv-brand { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 14px; }
        .rdv-brand-name { display: flex; align-items: center; gap: 9px; font-size: .9rem; font-weight: 700; color: var(--primary); }
        .rdv-brand-icon { width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; border-radius: 9px; background: var(--primary-soft); color: var(--primary); font-size: 15px; }
        .rdv-secure { display: flex; align-items: center; gap: 5px; color: var(--text-muted); font-size: .72rem; font-weight: 500; }
        .rdv-progress-track { height: 5px; overflow: hidden; border-radius: 999px; background: #e9eeec; }
        .rdv-progress-bar { width: 0%; height: 100%; border-radius: inherit; background: var(--primary); transition: width .35s ease; }
        .rdv-progress-info { display: flex; justify-content: space-between; margin-top: 7px; color: var(--text-muted); font-size: .7rem; }
        .rdv-body { flex: 1; width: 100%; max-width: 620px; margin: 0 auto; padding: 30px 20px 130px; position: relative; overflow: hidden; }
        .rdv-step { animation: rdv-enter-avant 260ms cubic-bezier(.22, .9, .32, 1) both; }
        .rdv-step.rdv-leaving-back { animation-name: rdv-enter-arriere; }
        @keyframes rdv-enter-avant { from { opacity: 0; transform: translateX(18px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes rdv-enter-arriere { from { opacity: 0; transform: translateX(-18px); } to { opacity: 1; transform: translateX(0); } }
        @media (prefers-reduced-motion: reduce) { .rdv-step { animation: none; } }
        .rdv-question { margin: 0; font-size: clamp(1.45rem, 4vw, 1.85rem); line-height: 1.2; letter-spacing: -.035em; font-weight: 750; }
        .rdv-hint { max-width: 520px; margin: 8px 0 24px; color: var(--text-soft); font-size: .92rem; line-height: 1.55; }
        .rdv-list { display: flex; flex-direction: column; gap: 9px; }
        .rdv-departement-titre { margin: 24px 0 9px; color: var(--text-muted); font-size: .72rem; font-weight: 750; letter-spacing: .07em; text-transform: uppercase; }
        .rdv-departement-titre:first-child { margin-top: 0; }
        .rdv-option {
            width: 100%; min-height: 62px; display: flex; align-items: center; gap: 13px; padding: 12px 14px; border: 1px solid var(--border);
            border-radius: var(--radius-md); background: var(--surface); color: var(--text); text-align: left; cursor: pointer; box-shadow: var(--shadow-sm);
            transition: border-color var(--transition), background var(--transition), transform var(--transition), box-shadow var(--transition);
        }
        .rdv-option:hover { border-color: var(--border-strong); box-shadow: 0 4px 15px rgba(20, 40, 34, .07); transform: translateY(-1px); }
        .rdv-option:active { transform: scale(.99); }
        .rdv-option:focus-visible { outline: 3px solid rgba(8, 127, 107, .18); outline-offset: 2px; }
        .rdv-option.is-selected { border-color: var(--primary); background: var(--primary-soft); box-shadow: none; }
        .rdv-option-icon { width: 40px; height: 40px; flex: 0 0 40px; display: flex; align-items: center; justify-content: center; border-radius: 12px; background: var(--surface-soft); color: var(--primary); font-size: 16px; }
        .rdv-option.is-selected .rdv-option-icon { background: var(--primary); color: white; }
        .rdv-option-content { flex: 1; min-width: 0; }
        .rdv-option-titre { display: block; font-size: .94rem; line-height: 1.3; font-weight: 650; }
        .rdv-option-sous { display: block; margin-top: 3px; color: var(--text-soft); font-size: .78rem; line-height: 1.35; }
        .rdv-pill { flex: 0 0 auto; padding: 5px 9px; border-radius: 999px; background: var(--surface-soft); color: var(--text-soft); font-size: .7rem; font-weight: 600; white-space: nowrap; }
        .rdv-chevron { color: var(--text-muted); font-size: 17px; transition: transform var(--transition); }
        .rdv-option:hover .rdv-chevron { transform: translateX(2px); }
        .rdv-suggestion-tag { display: inline-flex; align-items: center; gap: 5px; margin-bottom: 12px; padding: 6px 11px; border-radius: 999px; background: var(--primary-soft); color: var(--primary-dark); font-size: .76rem; font-weight: 650; }
        .rdv-date-section { margin-top: 4px; }
        .rdv-date-title { display: flex; align-items: baseline; justify-content: space-between; gap: 10px; margin-bottom: 10px; color: var(--text-soft); font-size: .78rem; font-weight: 650; }
        .rdv-mois-courant { color: var(--primary); font-weight: 700; text-transform: capitalize; white-space: nowrap; }
        .rdv-jours { display: flex; gap: 8px; overflow-x: auto; padding: 2px 2px 10px; margin: 0 -2px 18px; scrollbar-width: none; -webkit-overflow-scrolling: touch; }
        .rdv-jours::-webkit-scrollbar { display: none; }
        .rdv-mois-divider { display: flex; align-items: center; justify-content: center; flex: 0 0 32px; min-width: 32px; margin-right: 2px; padding-left: 9px; border-left: 1px dashed var(--border-strong); color: var(--text-muted); font-size: .64rem; font-weight: 750; text-transform: uppercase; letter-spacing: .03em; white-space: nowrap; }
        .rdv-mois-divider:first-child { border-left: 0; padding-left: 0; margin-right: 4px; }
        .rdv-jour { width: 66px; min-width: 66px; padding: 10px 5px; border: 1px solid var(--border); border-radius: 13px; background: var(--surface); color: var(--text); cursor: pointer; transition: all var(--transition); }
        .rdv-jour:hover { border-color: var(--primary); }
        .rdv-jour.is-selected { border-color: var(--primary); background: var(--primary); color: white; box-shadow: 0 5px 14px rgba(8, 127, 107, .2); }
        .rdv-jour-nom { display: block; font-size: .68rem; font-weight: 600; text-transform: uppercase; }
        .rdv-jour-num { display: block; margin-top: 3px; font-size: 1.05rem; font-weight: 750; }
        .rdv-periode-titre { margin: 16px 0 9px; color: var(--text-muted); font-size: .72rem; font-weight: 700; }
        .rdv-periode-titre:first-child { margin-top: 0; }
        .rdv-heures { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 9px; }
        .rdv-heure { min-height: 48px; border: 1px solid var(--border); border-radius: 11px; background: var(--surface); color: var(--text); font-size: .86rem; font-weight: 600; cursor: pointer; transition: all var(--transition); }
        .rdv-heure:hover { border-color: var(--primary); color: var(--primary); }
        .rdv-heure.is-selected { border-color: var(--primary); background: var(--primary); color: white; transform: scale(1.03); }
        .rdv-vide { grid-column: 1 / -1; margin: 10px 0; padding: 25px 15px; border: 1px dashed var(--border-strong); border-radius: var(--radius-md); color: var(--text-soft); text-align: center; font-size: .85rem; }
        .rdv-form-group { margin-bottom: 17px; }
        .rdv-label { display: block; margin-bottom: 7px; font-size: .78rem; font-weight: 700; }
        .rdv-input { width: 100%; min-height: 52px; padding: 13px 14px; border: 1px solid var(--border); border-radius: 12px; background: var(--surface); color: var(--text); font-size: 1rem; outline: none; transition: border-color var(--transition), box-shadow var(--transition); }
        .rdv-input::placeholder { color: #a4ada9; }
        .rdv-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(8, 127, 107, .10); }
        .rdv-feedback { min-height: 20px; margin: 7px 0 0; font-size: .78rem; }
        .rdv-feedback.is-ok { color: var(--success); }
        .rdv-feedback.is-error { color: var(--danger); }
        .rdv-patient-card { display: flex; align-items: center; gap: 12px; margin-top: 16px; padding: 14px; border: 1px solid #cde9e1; border-radius: 14px; background: var(--primary-soft); font-size: .86rem; }
        .rdv-patient-avatar { width: 38px; height: 38px; flex: 0 0 38px; display: flex; align-items: center; justify-content: center; border-radius: 50%; background: var(--primary); color: white; font-weight: 700; }
        .rdv-recap { overflow: hidden; border: 1px solid var(--border); border-radius: var(--radius-lg); background: var(--surface); box-shadow: var(--shadow-sm); }
        .rdv-recap-header { padding: 18px; border-bottom: 1px solid var(--border); background: var(--surface-soft); }
        .rdv-recap-header strong { display: block; margin-bottom: 3px; font-size: .9rem; }
        .rdv-recap-header span { color: var(--text-soft); font-size: .76rem; }
        .rdv-recap-body { padding: 4px 18px; }
        .rdv-recap-ligne { display: flex; justify-content: space-between; gap: 20px; padding: 13px 0; border-bottom: 1px solid var(--border); font-size: .84rem; }
        .rdv-recap-ligne:last-child { border-bottom: 0; }
        .rdv-recap-label { color: var(--text-soft); }
        .rdv-recap-valeur { max-width: 60%; font-weight: 650; text-align: right; }
        .rdv-recap-note { display: flex; gap: 10px; margin-top: 14px; padding: 13px 14px; border-radius: 12px; background: var(--surface-soft); border: 1px solid var(--border); color: var(--text-soft); font-size: .78rem; line-height: 1.5; }
        .rdv-succes { padding: 25px 0 10px; text-align: center; }
        .rdv-succes-icone { width: 72px; height: 72px; display: flex; align-items: center; justify-content: center; margin: 0 auto 22px; border-radius: 50%; background: var(--primary-soft); color: var(--primary); font-size: 2rem; font-weight: 800; box-shadow: 0 0 0 9px rgba(8, 127, 107, .05); }
        .rdv-succes .rdv-question { margin-bottom: 8px; }
        .rdv-success-text { max-width: 400px; margin: 0 auto 22px; color: var(--text-soft); font-size: .9rem; line-height: 1.55; }
        .rdv-sms-notice { display: flex; align-items: flex-start; gap: 11px; margin: 20px auto 0; max-width: 460px; padding: 14px 16px; border-radius: 14px; background: var(--primary-soft); border: 1px solid #cde9e1; text-align: left; font-size: .82rem; line-height: 1.5; color: var(--text); }
        .rdv-sms-notice strong { display: block; margin-bottom: 2px; }
        .rdv-post-actions { display: flex; flex-direction: column; gap: 10px; max-width: 460px; margin: 24px auto 0; }
        .rdv-post-btn { min-height: 52px; padding: 0 18px; border-radius: 12px; font-size: .9rem; font-weight: 700; cursor: pointer; transition: transform var(--transition), background var(--transition), border-color var(--transition); }
        .rdv-post-btn:active { transform: scale(.98); }
        .rdv-post-btn-primary { border: 0; background: var(--primary); color: white; box-shadow: 0 4px 12px rgba(8, 127, 107, .16); }
        .rdv-post-btn-primary:hover { background: var(--primary-dark); }
        .rdv-post-btn-secondary { border: 1px solid var(--border-strong); background: var(--surface); color: var(--text); }
        .rdv-actions { position: sticky; bottom: 0; z-index: 10; width: 100%; padding: 12px 20px max(14px, env(safe-area-inset-bottom)); border-top: 1px solid var(--border); background: rgba(245, 247, 246, .94); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); }
        .rdv-actions-inner { max-width: 620px; display: flex; gap: 9px; margin: auto; }
        .rdv-btn { min-height: 52px; border-radius: 12px; font-size: .9rem; font-weight: 700; cursor: pointer; transition: transform var(--transition), background var(--transition), border-color var(--transition); }
        .rdv-btn:active { transform: scale(.98); }
        .rdv-btn-retour { flex: 0 0 auto; padding: 0 17px; border: 1px solid var(--border-strong); background: var(--surface); color: var(--text); }
        .rdv-btn-principal { flex: 1; padding: 0 18px; border: 0; background: var(--primary); color: white; box-shadow: 0 4px 12px rgba(8, 127, 107, .16); display: flex; align-items: center; justify-content: center; gap: 9px; }
        .rdv-btn-principal:hover:not(:disabled) { background: var(--primary-dark); }
        .rdv-btn-principal:disabled { background: #dce4e1; color: #87938e; box-shadow: none; cursor: not-allowed; }
        .rdv-spin { width: 15px; height: 15px; border-radius: 50%; border: 2px solid rgba(255,255,255,.4); border-top-color: white; animation: rdv-rotate .7s linear infinite; }
        @keyframes rdv-rotate { to { transform: rotate(360deg); } }
        .rdv-skeleton { height: 62px; border-radius: var(--radius-md); background: linear-gradient(90deg, #edf1ef 25%, #f7f9f8 37%, #edf1ef 63%); background-size: 400% 100%; animation: rdv-shimmer 1.3s ease infinite; }
        @keyframes rdv-shimmer { 0% { background-position: 100% 0; } 100% { background-position: 0 0; } }
        @media (max-width: 360px) {
            .rdv-body { padding-left: 15px; padding-right: 15px; } .rdv-header { padding-left: 15px; padding-right: 15px; }
            .rdv-actions { padding-left: 15px; padding-right: 15px; } .rdv-network-banner { padding-left: 15px; padding-right: 15px; }
            .rdv-question { font-size: 1.35rem; } .rdv-option { padding: 10px; } .rdv-option-icon { width: 36px; height: 36px; flex-basis: 36px; }
            .rdv-pill { display: none; } .rdv-heures { grid-template-columns: repeat(2, 1fr); }
        }
        @media (min-width: 768px) {
            .rdv-body { padding-left: 35px; padding-right: 35px; } .rdv-header { padding-left: 35px; padding-right: 35px; }
            .rdv-actions { padding-left: 35px; padding-right: 35px; } .rdv-network-banner { padding-left: 35px; padding-right: 35px; }
            .rdv-option { min-height: 68px; } .rdv-heures { grid-template-columns: repeat(4, 1fr); }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { scroll-behavior: auto !important; animation-duration: .01ms !important; animation-iteration-count: 1 !important; transition-duration: .01ms !important; }
        }
    </style>
</head>

<body>

<div id="rdv-app" class="rdv-app">

    <div class="rdv-shell">

        <div class="rdv-network-banner" id="networkBanner" role="status" aria-live="polite">
            <span>⚠️</span>
            <span id="networkBannerText">Connexion instable — certaines actions peuvent prendre plus de temps.</span>
            <button type="button" id="networkRetry" hidden>Réessayer</button>
        </div>

        <header class="rdv-header">
            <div class="rdv-header-inner">
                <div class="rdv-brand">
                    <div class="rdv-brand-name">
                        <span class="rdv-brand-icon">+</span>
                        <span>Prise de rendez-vous</span>
                    </div>
                    <div class="rdv-secure">
                        <span>🔒</span>
                        <span>Sécurisé</span>
                    </div>
                </div>
                <div class="rdv-progress-track">
                    <div class="rdv-progress-bar" id="progressBar"></div>
                </div>
                <div class="rdv-progress-info">
                    <span id="progressLabel">Étape 1 sur 6</span>
                    <span>Rendez-vous</span>
                </div>
            </div>
        </header>

        <main class="rdv-body" id="rdvBody">

            <section class="rdv-step" data-step="motif">
                <div id="suggestionMotif" hidden></div>
                <h1 class="rdv-question">Que venez-vous faire ?</h1>
                <p class="rdv-hint">Choisissez le motif qui correspond le mieux à votre visite.</p>
                <div id="motifsListe" class="rdv-list" aria-busy="true">
                    <div class="rdv-skeleton"></div>
                    <div class="rdv-skeleton"></div>
                    <div class="rdv-skeleton"></div>
                </div>
            </section>

            <section class="rdv-step" data-step="medecin" hidden>
                <h1 class="rdv-question">Avec qui ?</h1>
                <p class="rdv-hint" id="medecinHint"></p>
                <div id="medecinsListe" class="rdv-list"></div>
            </section>

            <section class="rdv-step" data-step="creneau" hidden>
                <h1 class="rdv-question">Quand souhaitez-vous venir ?</h1>
                <p class="rdv-hint">Sélectionnez une date puis un créneau disponible (2 mois à l'avance).</p>
                <div class="rdv-date-section">
                    <div class="rdv-date-title">
                        <span>Dates disponibles</span>
                        <span id="moisCourant" class="rdv-mois-courant"></span>
                    </div>
                    <div id="joursBande" class="rdv-jours" role="tablist" aria-label="Choisir une date"></div>
                    <div class="rdv-date-title">Horaires disponibles</div>
                    <div id="heuresGrille"></div>
                </div>
            </section>

            <section class="rdv-step" data-step="coordonnees" hidden>
                <h1 class="rdv-question">Vos coordonnées</h1>
                <p class="rdv-hint">Votre numéro nous permettra de retrouver votre dossier ou de créer votre fiche patient.</p>
                <input type="text" name="site_web" id="siteWeb" autocomplete="off" tabindex="-1"
                       style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0;">
                <div class="rdv-form-group">
                    <label class="rdv-label" for="telephone">Numéro de téléphone</label>
                    <input type="tel" id="telephone" class="rdv-input" inputmode="numeric" maxlength="12"
                           placeholder="622 00 00 00" autocomplete="tel">
                    <p class="rdv-feedback" id="telephoneFeedback" aria-live="polite"></p>
                </div>
                <div id="nouveauPatient" hidden>
                    <div class="rdv-form-group">
                        <label class="rdv-label" for="prenom">Prénom</label>
                        <input type="text" id="prenom" class="rdv-input" autocomplete="given-name">
                    </div>
                    <div class="rdv-form-group">
                        <label class="rdv-label" for="nom">Nom</label>
                        <input type="text" id="nom" class="rdv-input" autocomplete="family-name">
                    </div>
                    <div class="rdv-form-group">
                        <label class="rdv-label" for="genre">Sexe</label>
                        <select id="genre" class="rdv-input">
                            <option value="">Choisir…</option>
                            <option value="Femme">Femme</option>
                            <option value="Homme">Homme</option>
                        </select>
                    </div>
                </div>
                <div id="patientTrouve" class="rdv-patient-card" hidden></div>
            </section>

            <section class="rdv-step" data-step="verification" hidden>
                <h1 class="rdv-question">Vérification de votre numéro</h1>
                <p class="rdv-hint" id="verifHint">Un code de vérification vient de vous être envoyé par SMS — cette étape n'a lieu qu'une seule fois, à la création de votre dossier.</p>
                <div class="rdv-form-group">
                    <label class="rdv-label" for="codeVerif">Code reçu par SMS</label>
                    <input type="text" id="codeVerif" class="rdv-input" inputmode="numeric" maxlength="6"
                           placeholder="••••••" style="text-align:center;letter-spacing:.5em;font-size:1.2rem;">
                    <p class="rdv-feedback" id="verifFeedback" aria-live="polite"></p>
                </div>
                <button type="button" class="rdv-post-btn rdv-post-btn-secondary" id="btnRenvoyerCode" style="width:auto;min-height:auto;padding:8px 4px;">
                    Renvoyer le code
                </button>
            </section>

            <section class="rdv-step" data-step="recap" hidden>
                <h1 class="rdv-question">Vérifiez votre rendez-vous</h1>
                <p class="rdv-hint">Tout est correct ? Confirmez pour réserver votre créneau.</p>
                <div class="rdv-recap" id="recapContenu"></div>
                <div class="rdv-recap-note">
                    <span>ℹ️</span>
                    <span>Un SMS de confirmation sera envoyé à ce numéro juste après. Vérifiez qu'il est correct avant de valider.</span>
                </div>
            </section>

            <section class="rdv-step" data-step="succes" hidden>
                <div class="rdv-succes">
                    <div class="rdv-succes-icone">✓</div>
                    <h1 class="rdv-question">Rendez-vous confirmé</h1>
                    <p class="rdv-success-text">Votre rendez-vous a bien été enregistré.</p>
                    <div class="rdv-recap" id="succesContenu"></div>
                    <div class="rdv-sms-notice">
                        <span>💬</span>
                        <span>
                            <strong>SMS envoyé</strong>
                            Un message de confirmation avec les détails de votre rendez-vous arrive sur votre téléphone.
                        </span>
                    </div>
                    <div class="rdv-post-actions" id="postActions"></div>
                </div>
            </section>

        </main>

        <footer class="rdv-actions" id="footerActions">
            <div class="rdv-actions-inner">
                <button type="button" class="rdv-btn rdv-btn-retour" id="btnRetour" hidden>Retour</button>
                <button type="button" class="rdv-btn rdv-btn-principal" id="btnSuivant" disabled>
                    <span id="btnSuivantLabel">Continuer</span>
                </button>
            </div>
        </footer>

    </div>

</div>


<script>

(function () {

    "use strict";

    const BASE = "{{ url('/rdv/'.$etablissement->slug.'/api') }}";
    const URL_ACCUEIL = "{{ url('/rdv/'.$etablissement->slug) }}";
    const URL_ESPACE_PATIENT = "{{ url('/mon-compte/connexion') }}";

    const ETAPES = ["motif", "medecin", "creneau", "coordonnees", "verification", "recap", "succes"];
    const ETAPES_VISIBLES = ETAPES.length - 1;

    const TIMEOUT_MS = 12000;
    const JOURS_AFFICHES = 60;

    const etat = {
        etapeIndex: 0,
        motif: null,
        medecin: null,
        medecinRetenu: null,
        date: null,
        heure: null,
        patient: null,
        nouveauPatient: null,
        telephoneVerifie: null
    };

    const progressBar = document.getElementById("progressBar");
    const progressLabel = document.getElementById("progressLabel");
    const btnSuivant = document.getElementById("btnSuivant");
    const btnSuivantLabel = document.getElementById("btnSuivantLabel");
    const btnRetour = document.getElementById("btnRetour");
    const telInput = document.getElementById("telephone");
    const networkBanner = document.getElementById("networkBanner");
    const networkBannerText = document.getElementById("networkBannerText");
    const networkRetry = document.getElementById("networkRetry");

    let derniereActionEchouee = null;

    function csrf() {
        return document.querySelector('meta[name="csrf-token"]').getAttribute("content");
    }

    function escapeHtml(value) {
        const div = document.createElement("div");
        div.textContent = value ?? "";
        return div.innerHTML;
    }

    function formatPhone(value) {
        const digits = value.replace(/\D/g, "");
        return digits.replace(/(\d{3})(?=\d)/g, "$1 ").trim();
    }

    function setBoutonEnCours(enCours, texte) {
        btnSuivant.disabled = enCours;
        if (enCours) {
            btnSuivantLabel.innerHTML = `<span class="rdv-spin"></span>${escapeHtml(texte)}`;
        }
    }

    function requeteJSON(url, options = {}, onRetry) {

        const controller = new AbortController();
        const timer = setTimeout(() => controller.abort(), TIMEOUT_MS);

        return fetch(url, { ...options, signal: controller.signal })
            .then(r => {
                clearTimeout(timer);
                if (!r.ok) {
                    return r.json().catch(() => ({}))
                        .then(data => Promise.reject({ status: r.status, data }));
                }
                return r.json();
            })
            .catch(err => {
                clearTimeout(timer);
                if (err && err.status) {
                    throw err;
                }
                afficherBanniereReseau(onRetry || (() => requeteJSON(url, options, onRetry)));
                throw { reseau: true };
            });
    }

    function afficherBanniereReseau(reessayer) {
        derniereActionEchouee = reessayer;
        networkBannerText.textContent = navigator.onLine
            ? "La connexion est trop lente pour continuer — vérifiez votre réseau."
            : "Vous semblez hors ligne.";
        networkRetry.hidden = false;
        networkBanner.classList.add("is-visible");
    }

    function masquerBanniereReseau() {
        networkBanner.classList.remove("is-visible");
    }

    networkRetry.addEventListener("click", () => {
        masquerBanniereReseau();
        if (derniereActionEchouee) {
            derniereActionEchouee();
        }
    });

    window.addEventListener("online", masquerBanniereReseau);

    window.addEventListener("offline", () => {
        networkBannerText.textContent = "Vous semblez hors ligne.";
        networkRetry.hidden = true;
        networkBanner.classList.add("is-visible");
    });

    function afficherEtape(index, directionArriere) {

        etat.etapeIndex = index;

        document.querySelectorAll(".rdv-step").forEach(step => { step.hidden = true; });

        const current = document.querySelector(`.rdv-step[data-step="${ETAPES[index]}"]`);

        if (current) {
            current.hidden = false;
            current.classList.toggle("rdv-leaving-back", !!directionArriere);
        }

        const etapeCourante = Math.min(index + 1, ETAPES_VISIBLES);
        const progress = (etapeCourante / ETAPES_VISIBLES) * 100;

        progressBar.style.width = `${progress}%`;
        progressLabel.textContent = `Étape ${etapeCourante} sur ${ETAPES_VISIBLES}`;

        btnRetour.hidden = index === 0 || ETAPES[index] === "succes";

        document.getElementById("footerActions").hidden = ETAPES[index] === "succes";

        majBoutonSuivant();

        window.scrollTo({ top: 0, behavior: "smooth" });
    }

    function majBoutonSuivant() {

        const etape = ETAPES[etat.etapeIndex];
        let ok = false;

        if (etape === "motif")   ok = !!etat.motif;
        if (etape === "medecin") ok = !!etat.medecin;
        if (etape === "creneau") ok = !!etat.date && !!etat.heure;

        if (etape === "coordonnees") {
            ok = !!etat.patient || (
                etat.nouveauPatient &&
                etat.nouveauPatient.prenom &&
                etat.nouveauPatient.nom &&
                etat.nouveauPatient.genre
            );
        }

        if (etape === "verification") {
            ok = document.getElementById("codeVerif").value.length === 6;
        }

        if (etape === "recap") ok = true;

        btnSuivant.disabled = !ok;

        btnSuivantLabel.textContent = etape === "recap"
            ? "Confirmer le rendez-vous"
            : (etape === "verification" ? "Vérifier" : "Continuer");
    }

    function suivant() {

        const etape = ETAPES[etat.etapeIndex];

        if (etape === "recap") {
            soumettre();
            return;
        }

        // NOUVEAU — l'étape de vérification ne concerne QUE la création
        // d'un nouveau dossier. Un patient déjà reconnu (etat.patient
        // rempli par verifier-patient) saute directement à la
        // récapitulation, sans aucune friction supplémentaire.
        if (etape === "coordonnees") {
            if (etat.patient) {
                etat.etapeIndex = ETAPES.indexOf("recap");
                afficherEtape(etat.etapeIndex);
                chargerEtape("recap");
                return;
            }
            // Numéro déjà vérifié plus tôt dans CETTE session (ex. retour
            // en arrière puis re-confirmation) — pas la peine de renvoyer
            // un code.
            if (etat.telephoneVerifie === telInput.value.replace(/\D/g, "")) {
                etat.etapeIndex = ETAPES.indexOf("recap");
                afficherEtape(etat.etapeIndex);
                chargerEtape("recap");
                return;
            }
        }

        if (etape === "verification") {
            verifierCode();
            return;
        }

        etat.etapeIndex++;
        afficherEtape(etat.etapeIndex);
        chargerEtape(ETAPES[etat.etapeIndex]);
    }

    function retour() {
        if (etat.etapeIndex <= 0) return;
        etat.etapeIndex--;
        afficherEtape(etat.etapeIndex, true);
    }

    btnSuivant.addEventListener("click", suivant);
    btnRetour.addEventListener("click", retour);

    function chargerEtape(nom) {
        if (nom === "medecin") chargerMedecins();
        if (nom === "creneau") chargerJours();
        if (nom === "verification") envoyerCode();
        if (nom === "recap") afficherRecap();
    }

    /* MOTIFS */
    function chargerMotifs() {

        requeteJSON(`${BASE}/motifs`, {}, chargerMotifs)
            .then(departements => {

                const conteneur = document.getElementById("motifsListe");
                conteneur.innerHTML = "";

                departements.forEach(dep => {

                    const titre = document.createElement("div");
                    titre.className = "rdv-departement-titre";
                    titre.textContent = dep.name;
                    conteneur.appendChild(titre);

                    dep.motifs_rdv.forEach(motif => {

                        const btn = document.createElement("button");
                        btn.type = "button";
                        btn.className = "rdv-option";
                        btn.setAttribute("aria-pressed", "false");
                        btn.style.borderLeftColor = motif.couleur || "var(--border)";

                        btn.innerHTML = `
                            <span class="rdv-option-icon">+</span>
                            <span class="rdv-option-content">
                                <span class="rdv-option-titre">${escapeHtml(motif.nom)}</span>
                                <span class="rdv-option-sous">Consultation</span>
                            </span>
                            <span class="rdv-pill">${motif.duree_minutes_defaut} min</span>
                            <span class="rdv-chevron">›</span>
                        `;

                        btn.addEventListener("click", () => {
                            document.querySelectorAll("#motifsListe .rdv-option").forEach(o => {
                                o.classList.remove("is-selected");
                                o.setAttribute("aria-pressed", "false");
                            });
                            btn.classList.add("is-selected");
                            btn.setAttribute("aria-pressed", "true");
                            etat.motif = motif;
                            majBoutonSuivant();
                        });

                        conteneur.appendChild(btn);
                    });
                });
            })
            .catch(() => {});
    }

    chargerMotifs();

    /* MÉDECINS */
    function chargerMedecins() {

        document.getElementById("medecinHint").textContent = `Motif sélectionné : ${etat.motif.nom}`;

        const conteneur = document.getElementById("medecinsListe");
        conteneur.innerHTML = `<div class="rdv-skeleton"></div><div class="rdv-skeleton"></div>`;

        requeteJSON(`${BASE}/motifs/${etat.motif.id}/medecins`, {}, chargerMedecins)
            .then(medecins => {

                conteneur.innerHTML = "";

                const auto = document.createElement("button");
                auto.type = "button";
                auto.className = "rdv-option";
                auto.setAttribute("aria-pressed", "false");

                auto.innerHTML = `
                    <span class="rdv-option-icon">✓</span>
                    <span class="rdv-option-content">
                        <span class="rdv-option-titre">Peu importe</span>
                        <span class="rdv-option-sous">Premier créneau disponible</span>
                    </span>
                    <span class="rdv-chevron">›</span>
                `;

                auto.addEventListener("click", () =>
                    choisirMedecin(auto, { id: "auto", nom: "Premier disponible" })
                );

                conteneur.appendChild(auto);

                medecins.forEach(m => {

                    const btn = document.createElement("button");
                    btn.type = "button";
                    btn.className = "rdv-option";
                    btn.setAttribute("aria-pressed", "false");

                    btn.innerHTML = `
                        <span class="rdv-option-icon">Dr</span>
                        <span class="rdv-option-content">
                            <span class="rdv-option-titre">${escapeHtml(m.nom)}</span>
                            ${m.speciality ? `<span class="rdv-option-sous">${escapeHtml(m.speciality)}</span>` : ""}
                        </span>
                        <span class="rdv-chevron">›</span>
                    `;

                    btn.addEventListener("click", () => choisirMedecin(btn, m));

                    conteneur.appendChild(btn);
                });
            })
            .catch(() => {});
    }

    function choisirMedecin(btn, medecin) {
        document.querySelectorAll("#medecinsListe .rdv-option").forEach(o => {
            o.classList.remove("is-selected");
            o.setAttribute("aria-pressed", "false");
        });
        btn.classList.add("is-selected");
        btn.setAttribute("aria-pressed", "true");
        etat.medecin = medecin;
        majBoutonSuivant();
    }

    /* DATES */
    function majMoisCourant(d) {
        const el = document.getElementById("moisCourant");
        if (el) el.textContent = d.toLocaleDateString("fr-FR", { month: "long", year: "numeric" });
    }

    function chargerJours() {

        const bande = document.getElementById("joursBande");
        const grille = document.getElementById("heuresGrille");

        bande.innerHTML = '<div class="rdv-skeleton" style="width:66px;flex:0 0 66px"></div>';
        grille.innerHTML = "";

        const params = new URLSearchParams({ motif_rdv_id: etat.motif.id, jours: JOURS_AFFICHES });

        if (etat.medecin.id !== "auto") {
            params.set("employee_id", etat.medecin.id);
        }

        const promesseJours = etat.medecin.id === "auto"
            ? Promise.resolve(Array.from({ length: JOURS_AFFICHES }, (_, i) => {
                const d = new Date();
                d.setDate(d.getDate() + i);
                return d.toISOString().slice(0, 10);
            }))
            : requeteJSON(`${BASE}/dates-disponibles?${params}`, {}, chargerJours);

        promesseJours.then(jours => {

            bande.innerHTML = "";

            let moisPrecedent = null;

            jours.forEach((iso, i) => {

                const d = new Date(iso + "T00:00:00");

                if (d.getMonth() !== moisPrecedent) {
                    const divider = document.createElement("div");
                    divider.className = "rdv-mois-divider";
                    divider.textContent = d.toLocaleDateString("fr-FR", { month: "short" }).replace(".", "");
                    bande.appendChild(divider);
                    moisPrecedent = d.getMonth();
                }

                const btn = document.createElement("button");
                btn.type = "button";
                btn.className = "rdv-jour";
                btn.setAttribute("role", "tab");
                btn.setAttribute("aria-selected", "false");

                btn.innerHTML = `
                    <span class="rdv-jour-nom">${d.toLocaleDateString("fr-FR", { weekday: "short" })}</span>
                    <span class="rdv-jour-num">${d.getDate()}</span>
                `;

                btn.addEventListener("click", () => {
                    document.querySelectorAll(".rdv-jour").forEach(j => {
                        j.classList.remove("is-selected");
                        j.setAttribute("aria-selected", "false");
                    });
                    btn.classList.add("is-selected");
                    btn.setAttribute("aria-selected", "true");
                    etat.date = iso;
                    etat.heure = null;
                    majMoisCourant(d);
                    chargerHeures(iso);
                });

                bande.appendChild(btn);

                if (i === 0) {
                    majMoisCourant(d);
                    btn.click();
                }
            });
        }).catch(() => {});
    }

    function periodeDe(heure) {
        const h = parseInt(heure.split(":")[0], 10);
        if (h < 12) return "Matin";
        if (h < 18) return "Après-midi";
        return "Soir";
    }

    function chargerHeures(date) {

        const grille = document.getElementById("heuresGrille");
        grille.innerHTML = '<div class="rdv-skeleton"></div>';

        const params = new URLSearchParams({ motif_rdv_id: etat.motif.id, date: date });

        const url = etat.medecin.id === "auto"
            ? `${BASE}/creneaux-tous-medecins?${params}`
            : `${BASE}/creneaux?${new URLSearchParams({ employee_id: etat.medecin.id, ...Object.fromEntries(params) })}`;

        requeteJSON(url, {}, () => chargerHeures(date)).then(creneaux => {

            grille.innerHTML = "";

            if (!creneaux.length) {
                grille.innerHTML = `
                    <p class="rdv-vide">
                        Aucun créneau disponible pour cette date.<br>
                        Essayez une autre journée.
                    </p>
                `;
                majBoutonSuivant();
                return;
            }

            const groupes = { "Matin": [], "Après-midi": [], "Soir": [] };
            creneaux.forEach(c => groupes[periodeDe(c.debut)].push(c));

            Object.entries(groupes).forEach(([periode, items]) => {

                if (!items.length) return;

                const titre = document.createElement("div");
                titre.className = "rdv-periode-titre";
                titre.textContent = periode;
                grille.appendChild(titre);

                const rangee = document.createElement("div");
                rangee.className = "rdv-heures";

                items.forEach(c => {

                    const btn = document.createElement("button");
                    btn.type = "button";
                    btn.className = "rdv-heure";
                    btn.setAttribute("aria-pressed", "false");
                    btn.textContent = c.debut;

                    btn.addEventListener("click", () => {
                        document.querySelectorAll(".rdv-heure").forEach(h => {
                            h.classList.remove("is-selected");
                            h.setAttribute("aria-pressed", "false");
                        });
                        btn.classList.add("is-selected");
                        btn.setAttribute("aria-pressed", "true");
                        etat.heure = c.debut;

                        if (etat.medecin.id === "auto" && c.employee_id) {
                            etat.medecinRetenu = { id: c.employee_id, nom: c.medecin };
                        }

                        majBoutonSuivant();
                    });

                    rangee.appendChild(btn);
                });

                grille.appendChild(rangee);
            });
        }).catch(() => {});
    }

    /* TÉLÉPHONE */
    let telTimeout;
    let telRequestId = 0;

    telInput.addEventListener("input", () => {

        let valeur = telInput.value.replace(/\D/g, "").slice(0, 9);
        telInput.value = formatPhone(valeur);

        clearTimeout(telTimeout);
        telRequestId++;

        etat.patient = null;
        etat.nouveauPatient = null;

        const carteExistante = document.getElementById("patientTrouve");
        carteExistante.hidden = true;
        carteExistante.innerHTML = "";
        document.getElementById("nouveauPatient").hidden = true;
        document.getElementById("telephoneFeedback").textContent = "";

        majBoutonSuivant();

        if (valeur.length !== 9) return;

        const requestId = telRequestId;

        telTimeout = setTimeout(() => {

            const feedback = document.getElementById("telephoneFeedback");
            feedback.textContent = "Recherche du dossier…";
            feedback.className = "rdv-feedback";

            function verifierPatient() {

                requeteJSON(`${BASE}/verifier-patient`, {
                    method: "POST",
                    headers: { "Content-Type": "application/json", "Accept": "application/json", "X-CSRF-TOKEN": csrf() },
                    body: JSON.stringify({ phone: valeur })
                }, verifierPatient).then(data => {

                    if (requestId !== telRequestId) return;

                    const carte = document.getElementById("patientTrouve");

                    if (data.is_patient) {

                        feedback.textContent = "Patient reconnu.";
                        feedback.className = "rdv-feedback is-ok";
                        etat.patient = data.patient;

                        document.getElementById("nouveauPatient").hidden = true;
                        carte.hidden = false;
                        carte.innerHTML = `
                            <div class="rdv-patient-avatar">✓</div>
                            <div>
                                Bonjour <strong>${escapeHtml(data.patient.name)}</strong><br>
                                <span style="color:var(--text-soft);font-size:.76rem">Dossier patient retrouvé</span>
                            </div>
                        `;

                    } else {

                        feedback.textContent = "Nouveau patient — quelques informations.";
                        feedback.className = "rdv-feedback";
                        carte.hidden = true;
                        carte.innerHTML = "";
                        document.getElementById("nouveauPatient").hidden = false;
                    }

                    majBoutonSuivant();

                }).catch(() => {
                    if (requestId !== telRequestId) return;
                    document.getElementById("telephoneFeedback").textContent = "";
                });
            }

            verifierPatient();

        }, 450);
    });

    ["prenom", "nom", "genre"].forEach(id => {
        document.getElementById(id).addEventListener("input", () => {
            etat.nouveauPatient = {
                telephone: telInput.value.replace(/\D/g, ""),
                prenom: document.getElementById("prenom").value.trim(),
                nom: document.getElementById("nom").value.trim(),
                genre: document.getElementById("genre").value
            };
            majBoutonSuivant();
        });
    });

    /* VÉRIFICATION OTP — uniquement pour un nouveau patient */
    const codeVerifInput = document.getElementById("codeVerif");
    codeVerifInput.addEventListener("input", majBoutonSuivant);

    function envoyerCode() {

        const feedback = document.getElementById("verifFeedback");
        feedback.textContent = "";
        codeVerifInput.value = "";
        document.getElementById("verifHint").textContent =
            `Un code de vérification vient d'être envoyé au ${formatPhone(telInput.value.replace(/\D/g, ""))} — cette étape n'a lieu qu'une seule fois.`;

        function tenterEnvoi() {
            requeteJSON(`${BASE}/envoyer-code-rdv`, {
                method: "POST",
                headers: { "Content-Type": "application/json", "Accept": "application/json", "X-CSRF-TOKEN": csrf() },
                body: JSON.stringify({ telephone: telInput.value.replace(/\D/g, "") })
            }, tenterEnvoi).then(() => {}).catch(err => {
                if (!err.reseau) {
                    feedback.textContent = (err.data && err.data.error) || "Impossible d'envoyer le code.";
                    feedback.className = "rdv-feedback is-error";
                }
            });
        }

        tenterEnvoi();
    }

    document.getElementById("btnRenvoyerCode").addEventListener("click", envoyerCode);

    function verifierCode() {

        const feedback = document.getElementById("verifFeedback");
        setBoutonEnCours(true, "Vérification…");

        function tenterVerif() {
            requeteJSON(`${BASE}/verifier-code-rdv`, {
                method: "POST",
                headers: { "Content-Type": "application/json", "Accept": "application/json", "X-CSRF-TOKEN": csrf() },
                body: JSON.stringify({ telephone: telInput.value.replace(/\D/g, ""), code: codeVerifInput.value })
            }, tenterVerif).then(() => {

                etat.telephoneVerifie = telInput.value.replace(/\D/g, "");
                setBoutonEnCours(false);
                btnSuivantLabel.textContent = "Continuer";

                etat.etapeIndex = ETAPES.indexOf("recap");
                afficherEtape(etat.etapeIndex);
                chargerEtape("recap");

            }).catch(err => {
                setBoutonEnCours(false);
                majBoutonSuivant();
                if (!err.reseau) {
                    feedback.textContent = (err.data && err.data.error) || "Code invalide.";
                    feedback.className = "rdv-feedback is-error";
                }
            });
        }

        tenterVerif();
    }

    function afficherRecap() {

        const medecinNom = etat.medecinRetenu ? etat.medecinRetenu.nom : etat.medecin.nom;
        const d = new Date(etat.date + "T00:00:00");

        document.getElementById("recapContenu").innerHTML = `
            <div class="rdv-recap-header">
                <strong>Votre rendez-vous</strong>
                <span>Vérifiez les informations avant confirmation.</span>
            </div>
            <div class="rdv-recap-body">
                <div class="rdv-recap-ligne">
                    <span class="rdv-recap-label">Motif</span>
                    <span class="rdv-recap-valeur">${escapeHtml(etat.motif.nom)}</span>
                </div>
                <div class="rdv-recap-ligne">
                    <span class="rdv-recap-label">Médecin</span>
                    <span class="rdv-recap-valeur">${escapeHtml(medecinNom)}</span>
                </div>
                <div class="rdv-recap-ligne">
                    <span class="rdv-recap-label">Date</span>
                    <span class="rdv-recap-valeur">${d.toLocaleDateString("fr-FR", { weekday: "long", day: "numeric", month: "long" })}</span>
                </div>
                <div class="rdv-recap-ligne">
                    <span class="rdv-recap-label">Heure</span>
                    <span class="rdv-recap-valeur">${escapeHtml(etat.heure)}</span>
                </div>
                <div class="rdv-recap-ligne">
                    <span class="rdv-recap-label">Téléphone</span>
                    <span class="rdv-recap-valeur">${escapeHtml(formatPhone(telInput.value.replace(/\D/g, "")))}</span>
                </div>
            </div>
        `;
    }

    function afficherActionsFinales() {

        const conteneur = document.getElementById("postActions");
        const estPatientConnu = !!etat.patient;

        if (estPatientConnu) {
            conteneur.innerHTML = `
                <button type="button" class="rdv-post-btn rdv-post-btn-secondary" id="btnAccueil">
                    Prendre un autre rendez-vous
                </button>
                <button type="button" class="rdv-post-btn rdv-post-btn-primary" id="btnEspace">
                    Voir mon espace patient
                </button>
            `;
            document.getElementById("btnEspace").addEventListener("click", () => {
                window.location.href = URL_ESPACE_PATIENT;
            });
        } else {
            conteneur.innerHTML = `
                <button type="button" class="rdv-post-btn rdv-post-btn-primary" id="btnAccueil">
                    Retour à l'accueil
                </button>
            `;
        }

        document.getElementById("btnAccueil").addEventListener("click", () => {
            window.location.href = URL_ACCUEIL;
        });
    }

    function soumettre() {

        setBoutonEnCours(true, "Confirmation…");

        const employeeId = etat.medecin.id === "auto" ? etat.medecinRetenu.id : etat.medecin.id;

        const envoyer = patientId => {

            function tenterEnvoi() {
                requeteJSON(`${BASE}/prendre`, {
                    method: "POST",
                    headers: { "Content-Type": "application/json", "Accept": "application/json", "X-CSRF-TOKEN": csrf() },
                    body: JSON.stringify({
                        employee_id: employeeId,
                        patient_id: patientId,
                        motif_rdv_id: etat.motif.id,
                        appointment_date: etat.date,
                        appointment_time: etat.heure
                    })
                }, tenterEnvoi).then(data => {

                    document.getElementById("succesContenu").innerHTML = `
                        <div class="rdv-recap-header">
                            <strong>Rendez-vous enregistré</strong>
                            <span>Présentez-vous quelques minutes avant l'heure.</span>
                        </div>
                        <div class="rdv-recap-body">
                            <div class="rdv-recap-ligne">
                                <span class="rdv-recap-label">Motif</span>
                                <span class="rdv-recap-valeur">${escapeHtml(data.appointment.motif)}</span>
                            </div>
                            <div class="rdv-recap-ligne">
                                <span class="rdv-recap-label">Médecin</span>
                                <span class="rdv-recap-valeur">${escapeHtml(data.appointment.doctor)}</span>
                            </div>
                            <div class="rdv-recap-ligne">
                                <span class="rdv-recap-label">Quand</span>
                                <span class="rdv-recap-valeur">${escapeHtml(data.appointment.date)} à ${escapeHtml(data.appointment.time)}</span>
                            </div>
                        </div>
                    `;

                    afficherActionsFinales();

                    etat.etapeIndex = ETAPES.indexOf("succes");
                    afficherEtape(etat.etapeIndex);

                }).catch(err => {
                    setBoutonEnCours(false);
                    majBoutonSuivant();
                    if (!err.reseau) {
                        alert((err.data && err.data.error) || "Une erreur est survenue.");
                    }
                });
            }

            tenterEnvoi();
        };

        if (etat.patient) {
            envoyer(etat.patient.id);
            return;
        }

        function tenterCreerPatient() {
            requeteJSON(`${BASE}/creer-patient`, {
                method: "POST",
                headers: { "Content-Type": "application/json", "Accept": "application/json", "X-CSRF-TOKEN": csrf() },
                body: JSON.stringify({ ...etat.nouveauPatient, site_web: document.getElementById("siteWeb").value })
            }, tenterCreerPatient).then(data => {
                envoyer(data.patient_id);
            }).catch(err => {
                setBoutonEnCours(false);
                majBoutonSuivant();
                if (!err.reseau) {
                    alert((err.data && err.data.error) || "Impossible de créer le dossier patient.");
                }
            });
        }

        tenterCreerPatient();
    }

    afficherEtape(0);

})();

</script>

</body>
</html>
