<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0f766e">
    <meta name="robots" content="noindex">
    <title>Choisir un dossier · Mon espace santé</title>
    <style>
        :root { --p: #0f766e; --pp: #f0fdfa; --bg: #f4f6f6; --ink: #111827; --soft: #6b7280; --line: #e5e7eb; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; background: var(--bg); color: var(--ink); font-family: -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
        .sd { max-width: 480px; margin: 0 auto; padding: 36px 18px; }
        .sd-marque { display: flex; align-items: center; gap: 10px; margin-bottom: 26px; color: var(--soft); font-size: .85rem; font-weight: 600; }
        .sd-logo { display: grid; place-items: center; width: 34px; height: 34px; border-radius: 10px; background: var(--p); color: #fff; font-weight: 800; }
        h1 { margin: 0 0 6px; font-size: 1.4rem; letter-spacing: -.02em; }
        p { margin: 0 0 22px; color: var(--soft); font-size: .92rem; }
        .sd-liste { display: grid; gap: 10px; }
        .sd-carte { display: flex; align-items: center; gap: 14px; padding: 16px; border: 1px solid var(--line); border-radius: 16px; background: #fff; color: inherit; text-decoration: none; transition: border-color .15s, box-shadow .15s; }
        .sd-carte:hover, .sd-carte:focus-visible { border-color: var(--p); box-shadow: 0 0 0 4px var(--pp); outline: 0; }
        .sd-avatar { display: grid; place-items: center; width: 48px; height: 48px; flex: none; border-radius: 14px; background: var(--pp); color: var(--p); font-weight: 800; }
        .sd-carte strong { display: block; font-size: 1rem; }
        .sd-carte span.sd-role { display: block; color: var(--soft); font-size: .82rem; }
        .sd-fleche { margin-left: auto; color: #9ca3af; font-size: 1.3rem; }
    </style>
@include('partials.fond-medical')
</head>
<body>
<main class="sd">
    <div class="sd-marque"><span class="sd-logo" aria-hidden="true">+</span> Mon espace santé</div>
    <h1>Quel dossier ouvrir ?</h1>
    <p>Votre numéro est lié à plusieurs dossiers : le vôtre et ceux dont vous êtes responsable.</p>
    <div class="sd-liste">
        @foreach($dossiers as $d)
            <a href="{{ route('portail.dossier', $d) }}" class="sd-carte">
                <span class="sd-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr((string) $d->first_name, 0, 1) . mb_substr((string) $d->last_name, 0, 1)) }}</span>
                <span><strong>{{ $d->getFullName() }}</strong><span class="sd-role">{{ $d->pivot->role === 'tuteur' ? 'Vous êtes son responsable' : 'Votre dossier' }}</span></span>
                <span class="sd-fleche" aria-hidden="true">›</span>
            </a>
        @endforeach
    </div>
</main>
</body>
</html>
