<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0f766e">
    <meta name="robots" content="noindex">
    <title>Mot de passe oublié · {{ \App\Support\Marque::nom() }}</title>
    <style>
        :root { --p: #0f766e; --pf: #115e59; --pp: #f0fdfa; --bg: #f4f6f6; --ink: #111827; --soft: #6b7280; --line: #e5e7eb; --danger: #b91c1c; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; background: var(--bg); color: var(--ink); font-family: -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
        .mo { display: grid; min-height: 100vh; place-items: center; padding: 24px 18px; }
        .mo-carte { width: 100%; max-width: 420px; padding: 28px 24px; border: 1px solid var(--line); border-radius: 20px; background: #fff; box-shadow: 0 10px 30px rgba(17, 24, 39, .06); }
        .mo-marque { display: flex; align-items: center; gap: 10px; margin-bottom: 24px; color: var(--soft); font-size: .85rem; font-weight: 600; }
        .mo-logo { display: grid; place-items: center; width: 36px; height: 36px; border-radius: 11px; background: var(--p); color: #fff; font-weight: 800; }
        .mo-etapes { display: flex; gap: 6px; margin-bottom: 18px; }
        .mo-etapes span { flex: 1; height: 4px; border-radius: 999px; background: var(--line); }
        .mo-etapes span.est-fait { background: var(--p); }
        h1 { margin: 0 0 6px; font-size: 1.4rem; letter-spacing: -.02em; }
        .mo-aide { margin: 0 0 20px; color: var(--soft); font-size: .92rem; line-height: 1.5; }
        label { display: block; margin: 14px 0 6px; font-size: .86rem; font-weight: 650; }
        input { width: 100%; padding: 13px 14px; border: 1.5px solid var(--line); border-radius: 12px; outline: 0; font-size: 1rem; }
        input:focus { border-color: var(--p); box-shadow: 0 0 0 4px var(--pp); }
        input.mo-code { font-size: 1.4rem; font-weight: 700; letter-spacing: .45em; text-align: center; }
        .mo-tel { display: flex; border: 1.5px solid var(--line); border-radius: 12px; overflow: hidden; }
        .mo-tel:focus-within { border-color: var(--p); box-shadow: 0 0 0 4px var(--pp); }
        .mo-tel span { display: grid; place-items: center; padding: 0 12px; border-right: 1px solid var(--line); background: #f9fafb; color: var(--soft); font-weight: 600; }
        .mo-tel input { border: 0; box-shadow: none !important; border-radius: 0; }
        .mo-regle { margin: 6px 0 0; color: var(--soft); font-size: .8rem; }
        .mo-btn { width: 100%; min-height: 50px; margin-top: 20px; border: 0; border-radius: 12px; background: var(--p); color: #fff; font-size: 1rem; font-weight: 700; cursor: pointer; }
        .mo-btn:hover { background: var(--pf); }
        .mo-erreurs { margin: 0 0 14px; padding: 12px 14px; border-radius: 12px; background: #fef2f2; color: var(--danger); font-size: .88rem; }
        .mo-erreurs p { margin: 0; }
        .mo-liens { display: flex; justify-content: space-between; gap: 10px; margin-top: 16px; font-size: .88rem; }
        .mo-liens a { color: var(--p); font-weight: 600; text-decoration: none; }
    </style>
@include('partials.fond-medical')
</head>
<body>
<main class="mo">
    <div class="mo-carte">
        <div class="mo-marque"><span class="mo-logo" aria-hidden="true">+</span> {{ \App\Support\Marque::nom() }} · personnel</div>
        <div class="mo-etapes" aria-hidden="true"><span class="est-fait"></span><span class="{{ $etape === 'code' ? 'est-fait' : '' }}"></span></div>

        @if($errors->any())
            <div class="mo-erreurs" role="alert">@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>
        @endif

        @if($etape === 'telephone')
            <h1>Mot de passe oublié</h1>
            <p class="mo-aide">Saisissez le numéro avec lequel vous vous connectez. Vous recevrez un code par SMS pour choisir un nouveau mot de passe.</p>
            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <label for="phone">Numéro de téléphone</label>
                <div class="mo-tel"><span>+224</span><input type="tel" id="phone" name="phone" inputmode="numeric" maxlength="9" pattern="[0-9]{9}" value="{{ old('phone') }}" placeholder="622000000" autocomplete="username" required autofocus></div>
                <button type="submit" class="mo-btn">Recevoir le code</button>
            </form>
        @else
            <h1>Nouveau mot de passe</h1>
            <p class="mo-aide">Si le numéro <strong>{{ $telephone }}</strong> correspond à un compte actif, un code vient de lui être envoyé par SMS (valable 10 minutes).</p>
            <form method="POST" action="{{ route('password.update') }}">
                @csrf
                <label for="code">Code reçu par SMS</label>
                <input type="text" id="code" name="code" class="mo-code" inputmode="numeric" maxlength="6" placeholder="••••••" autocomplete="one-time-code" required autofocus>
                <label for="password">Nouveau mot de passe</label>
                <input type="password" id="password" name="password" minlength="8" autocomplete="new-password" required>
                <p class="mo-regle">8 caractères au moins, avec au moins une lettre et un chiffre.</p>
                <label for="password_confirmation">Confirmer le mot de passe</label>
                <input type="password" id="password_confirmation" name="password_confirmation" minlength="8" autocomplete="new-password" required>
                <button type="submit" class="mo-btn">Changer mon mot de passe</button>
            </form>
            <div class="mo-liens"><a href="{{ route('password.request') }}">Renvoyer un code</a></div>
        @endif

        <div class="mo-liens"><a href="{{ url('/login') }}">← Retour à la connexion</a></div>
    </div>
</main>
</body>
</html>
