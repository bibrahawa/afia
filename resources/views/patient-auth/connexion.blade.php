<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0f766e">
    <meta name="robots" content="noindex">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Connexion · Mon espace santé</title>
    {{-- Page autonome et légère : elle doit s'ouvrir vite sur un téléphone d'entrée de gamme. --}}
    <style>
        :root { --p: #0f766e; --pf: #115e59; --pp: #f0fdfa; --bg: #f4f6f6; --ink: #111827; --soft: #6b7280; --line: #e5e7eb; --danger: #b91c1c; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; background: var(--bg); color: var(--ink); font-family: -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
        .co { display: grid; min-height: 100vh; place-items: center; padding: 24px 18px max(24px, env(safe-area-inset-bottom)); }
        .co-carte { width: 100%; max-width: 400px; padding: 28px 24px; border: 1px solid var(--line); border-radius: 20px; background: #fff; box-shadow: 0 10px 30px rgba(17, 24, 39, .06); }
        .co-marque { display: flex; align-items: center; gap: 10px; margin-bottom: 26px; }
        .co-logo { display: grid; place-items: center; width: 40px; height: 40px; border-radius: 12px; background: var(--p); color: #fff; font-size: 20px; font-weight: 800; }
        .co-marque strong { display: block; font-size: 1rem; }
        .co-marque span { display: block; color: var(--soft); font-size: .78rem; }
        h1 { margin: 0 0 6px; font-size: 1.45rem; letter-spacing: -.02em; }
        .co-aide { margin: 0 0 22px; color: var(--soft); font-size: .92rem; line-height: 1.5; }
        label { display: block; margin-bottom: 6px; font-size: .86rem; font-weight: 650; }
        .co-tel { display: flex; align-items: stretch; border: 1.5px solid var(--line); border-radius: 12px; background: #fff; overflow: hidden; transition: border-color .15s; }
        .co-tel:focus-within, .co-champ:focus { border-color: var(--p); box-shadow: 0 0 0 4px var(--pp); }
        .co-tel span { display: grid; place-items: center; padding: 0 12px; border-right: 1px solid var(--line); background: #f9fafb; color: var(--soft); font-weight: 600; }
        .co-tel input { flex: 1; min-width: 0; padding: 14px; border: 0; outline: 0; font-size: 1.1rem; letter-spacing: .04em; font-variant-numeric: tabular-nums; }
        .co-champ { width: 100%; padding: 14px; border: 1.5px solid var(--line); border-radius: 12px; outline: 0; font-size: 1.5rem; font-weight: 700; letter-spacing: .5em; text-align: center; font-variant-numeric: tabular-nums; }
        .co-btn { width: 100%; min-height: 52px; margin-top: 18px; border: 0; border-radius: 12px; background: var(--p); color: #fff; font-size: 1rem; font-weight: 700; cursor: pointer; }
        .co-btn:hover:not(:disabled) { background: var(--pf); }
        .co-btn:disabled { background: #e5e7eb; color: var(--soft); cursor: not-allowed; }
        .co-message { min-height: 1.2em; margin: 10px 0 0; font-size: .86rem; }
        .co-message.est-erreur { color: var(--danger); }
        .co-liens { display: flex; justify-content: space-between; gap: 10px; margin-top: 16px; }
        .co-liens button { padding: 4px 0; border: 0; background: none; color: var(--p); font-size: .86rem; font-weight: 600; cursor: pointer; }
        .co-liens button:disabled { color: var(--soft); cursor: default; }
        .co-note { margin: 22px 0 0; padding-top: 16px; border-top: 1px solid var(--line); color: var(--soft); font-size: .78rem; line-height: 1.5; }
        [hidden] { display: none !important; }
    </style>
@include('partials.fond-medical')
</head>
<body>
<main class="co">
    <div class="co-carte">
        <div class="co-marque">
            <span class="co-logo" aria-hidden="true">+</span>
            <div><strong>Mon espace santé</strong><span>Rendez-vous, dossier et partage</span></div>
        </div>

        <section data-step="telephone">
            <h1>Connexion</h1>
            <p class="co-aide">Saisissez le numéro donné à la clinique. Vous recevrez un code par SMS, sans mot de passe à retenir.</p>
            <label for="telephone">Numéro de téléphone</label>
            <div class="co-tel"><span>+224</span><input type="tel" id="telephone" inputmode="numeric" maxlength="12" placeholder="622 00 00 00" autocomplete="tel-national" autofocus></div>
            <p class="co-message" id="telMessage" aria-live="polite"></p>
            <button type="button" class="co-btn" id="btnEnvoyer" disabled>Recevoir le code</button>
        </section>

        <section data-step="code" hidden>
            <h1>Code de vérification</h1>
            <p class="co-aide" id="codeHint"></p>
            <label for="code">Code reçu par SMS</label>
            <input type="text" id="code" class="co-champ" inputmode="numeric" maxlength="6" placeholder="••••••" autocomplete="one-time-code">
            <p class="co-message est-erreur" id="codeFeedback" aria-live="polite"></p>
            <button type="button" class="co-btn" id="btnVerifier" disabled>Se connecter</button>
            <div class="co-liens">
                <button type="button" id="btnChanger">Changer de numéro</button>
                <button type="button" id="btnRenvoyer" disabled>Renvoyer le code</button>
            </div>
        </section>

        <p class="co-note">Votre dossier reste confidentiel : seules les cliniques que vous autorisez peuvent le consulter, et vous pouvez retirer un accès à tout moment.</p>
    </div>
</main>
<script>
(function () {
    var csrf = document.querySelector('meta[name="csrf-token"]').content;
    var tel = document.getElementById('telephone'), code = document.getElementById('code');
    var btnEnvoyer = document.getElementById('btnEnvoyer'), btnVerifier = document.getElementById('btnVerifier'), btnRenvoyer = document.getElementById('btnRenvoyer');
    var telMessage = document.getElementById('telMessage'), feedback = document.getElementById('codeFeedback');
    var telephone = '', minuterie = null;
    var etape = function (nom) { document.querySelectorAll('[data-step]').forEach(function (s) { s.hidden = s.dataset.step !== nom; }); };
    var chiffres = function (v) { return v.replace(/\D/g, '').replace(/^224/, ''); };
    var post = function (url, corps) {
        return fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }, body: JSON.stringify(corps) })
            .then(function (r) { return r.json().catch(function () { return {}; }).then(function (d) { return { statut: r.status, d: d }; }); });
    };

    tel.addEventListener('input', function () {
        var n = chiffres(tel.value).slice(0, 9);
        tel.value = n.replace(/(\d{3})(\d{2})?(\d{2})?(\d{2})?/, function (m, a, b, c, d) { return [a, b, c, d].filter(Boolean).join(' '); });
        btnEnvoyer.disabled = n.length !== 9; telMessage.textContent = '';
    });
    tel.addEventListener('keydown', function (e) { if (e.key === 'Enter' && !btnEnvoyer.disabled) envoyer(); });

    function compteARebours() {
        var reste = 60; btnRenvoyer.disabled = true;
        clearInterval(minuterie);
        minuterie = setInterval(function () {
            reste--; btnRenvoyer.textContent = reste > 0 ? 'Renvoyer (' + reste + ' s)' : 'Renvoyer le code';
            if (reste <= 0) { clearInterval(minuterie); btnRenvoyer.disabled = false; }
        }, 1000);
    }

    function envoyer() {
        telephone = chiffres(tel.value);
        btnEnvoyer.disabled = true; btnEnvoyer.textContent = 'Envoi…';
        post('{{ route('patient-auth.envoyer-code') }}', { telephone: telephone }).then(function (res) {
            btnEnvoyer.textContent = 'Recevoir le code';
            // CORRIGÉ : l'écran passait au code même quand l'envoi était refusé (trop d'essais).
            if (res.statut >= 400) {
                telMessage.className = 'co-message est-erreur';
                telMessage.textContent = res.d.error || (res.d.errors ? 'Numéro invalide : 9 chiffres.' : 'Envoi impossible pour le moment.');
                btnEnvoyer.disabled = false; etape('telephone'); return;
            }
            etape('code');
            document.getElementById('codeHint').textContent = 'Si ce numéro est enregistré, un code a été envoyé au +224 ' + tel.value + '.';
            code.value = ''; btnVerifier.disabled = true; feedback.textContent = '';
            code.focus(); compteARebours();
        }).catch(function () {
            btnEnvoyer.textContent = 'Recevoir le code'; btnEnvoyer.disabled = false;
            telMessage.className = 'co-message est-erreur'; telMessage.textContent = 'Connexion internet interrompue : réessayez.';
        });
    }
    btnEnvoyer.addEventListener('click', envoyer);
    btnRenvoyer.addEventListener('click', envoyer);
    document.getElementById('btnChanger').addEventListener('click', function () { clearInterval(minuterie); etape('telephone'); btnEnvoyer.disabled = false; tel.focus(); });

    function verifier() {
        btnVerifier.disabled = true; btnVerifier.textContent = 'Vérification…'; feedback.textContent = '';
        post('{{ route('patient-auth.verifier-code') }}', { telephone: telephone, code: code.value }).then(function (res) {
            if (res.statut !== 200) {
                feedback.textContent = res.d.error || 'Code invalide.';
                btnVerifier.textContent = 'Se connecter'; btnVerifier.disabled = false; code.select(); return;
            }
            btnVerifier.textContent = 'Connexion…';
            window.location.href = res.d.redirect;
        }).catch(function () { feedback.textContent = 'Connexion internet interrompue : réessayez.'; btnVerifier.textContent = 'Se connecter'; btnVerifier.disabled = false; });
    }
    code.addEventListener('input', function () {
        code.value = code.value.replace(/\D/g, '').slice(0, 6);
        btnVerifier.disabled = code.value.length !== 6;
        if (code.value.length === 6) verifier();
    });
    btnVerifier.addEventListener('click', verifier);
})();
</script>
</body>
</html>
