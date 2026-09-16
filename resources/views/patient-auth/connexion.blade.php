<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — Mon espace santé</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
<div class="co-shell">
    <div class="co-body">

        <section class="co-step" data-step="telephone">
            <h1 class="co-titre">Mon espace santé</h1>
            <p class="co-hint">Connectez-vous avec votre numéro de téléphone.</p>
            <label class="co-label" for="telephone">Numéro de téléphone</label>
            <input type="tel" id="telephone" class="co-input" inputmode="numeric" maxlength="9" placeholder="622 00 00 00" autocomplete="tel">
            <button type="button" class="co-btn" id="btnEnvoyer" disabled>Recevoir le code</button>
        </section>

        <section class="co-step" data-step="code" hidden>
            <h1 class="co-titre">Vérification</h1>
            <p class="co-hint" id="codeHint"></p>
            <label class="co-label" for="code">Code reçu par SMS</label>
            <input type="text" id="code" class="co-input co-code" inputmode="numeric" maxlength="6" placeholder="••••••">
            <p class="co-feedback" id="codeFeedback"></p>
            <button type="button" class="co-btn" id="btnVerifier" disabled>Se connecter</button>
            <button type="button" class="co-lien" id="btnRenvoyer">Renvoyer le code</button>
        </section>

    </div>
</div>

<style>
:root{--bg:#F6F7F5; --surface:#fff; --ink:#1D2B26; --ink-soft:#5B6B65; --primary:#0E6659; --primary-dark:#0A4B41; --line:#DCE3DF; --danger:#B3431E;}
*{box-sizing:border-box;}
body{margin:0;}
.co-shell{font-family:-apple-system,"Segoe UI",Roboto,Helvetica,Arial,sans-serif; background:var(--bg); color:var(--ink); min-height:100vh; display:flex; align-items:center;}
.co-body{max-width:400px; margin:0 auto; padding:24px; width:100%;}
.co-titre{font-size:1.5rem; font-weight:600; margin:0 0 6px;}
.co-hint{color:var(--ink-soft); font-size:.95rem; margin:0 0 24px; line-height:1.5;}
.co-label{display:block; font-size:.9rem; font-weight:500; margin-bottom:6px;}
.co-input{width:100%; padding:14px; border-radius:8px; border:1px solid var(--line); font-size:1rem; font-family:inherit; background:var(--surface); margin-bottom:16px;}
.co-code{text-align:center; letter-spacing:.5em; font-size:1.3rem;}
.co-btn{width:100%; background:var(--primary); color:#fff; border:none; border-radius:8px; padding:15px; font-size:1rem; font-weight:600; font-family:inherit; cursor:pointer;}
.co-btn:disabled{background:var(--line); color:var(--ink-soft); cursor:not-allowed;}
.co-lien{display:block; width:100%; background:none; border:none; color:var(--ink-soft); text-decoration:underline; font-size:.9rem; margin-top:14px; cursor:pointer; font-family:inherit;}
.co-feedback{font-size:.85rem; color:var(--danger); min-height:1.2em; margin:-8px 0 12px;}
</style>

<script>
(function(){
    const telInput = document.getElementById('telephone');
    const btnEnvoyer = document.getElementById('btnEnvoyer');
    const codeInput = document.getElementById('code');
    const btnVerifier = document.getElementById('btnVerifier');
    const btnRenvoyer = document.getElementById('btnRenvoyer');
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    let telephone = '';

    telInput.addEventListener('input', () => {
        btnEnvoyer.disabled = telInput.value.replace(/\D/g,'').length !== 9;
    });
    codeInput.addEventListener('input', () => {
        btnVerifier.disabled = codeInput.value.length !== 6;
    });

    function envoyerCode(){
        telephone = telInput.value.replace(/\D/g,'');
        btnEnvoyer.disabled = true;
        fetch("{{ route('patient-auth.envoyer-code') }}", {
            method:'POST',
            headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrf},
            body: JSON.stringify({telephone})
        }).then(() => {
            document.querySelector('[data-step="telephone"]').hidden = true;
            document.querySelector('[data-step="code"]').hidden = false;
            document.getElementById('codeHint').textContent = `Code envoyé au ${telInput.value}`;
            codeInput.focus();
        });
    }

    btnEnvoyer.addEventListener('click', envoyerCode);
    btnRenvoyer.addEventListener('click', envoyerCode);

    btnVerifier.addEventListener('click', () => {
        btnVerifier.disabled = true;
        const feedback = document.getElementById('codeFeedback');
        feedback.textContent = '';

        fetch("{{ route('patient-auth.verifier-code') }}", {
            method:'POST',
            headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrf},
            body: JSON.stringify({telephone, code: codeInput.value})
        }).then(r=>r.json().then(data => ({status:r.status, data}))).then(({status,data}) => {
            if(status !== 200){
                feedback.textContent = data.error || 'Code invalide.';
                btnVerifier.disabled = false;
                return;
            }
            window.location.href = data.redirect;
        });
    });
})();
</script>
</body>
</html>
