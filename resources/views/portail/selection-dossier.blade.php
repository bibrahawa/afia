<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choisir un dossier</title>
</head>
<body>
<div class="ppsel-shell">
    <h1 class="ppsel-titre">Quel dossier souhaitez-vous consulter ?</h1>
    <div class="ppsel-liste">
        @foreach($dossiers as $d)
            <a href="{{ route('portail.dossier', $d) }}" class="ppsel-carte">
                <span class="ppsel-nom">{{ $d->getFullName() }}</span>
                <span class="ppsel-role">{{ $d->pivot->role === 'tuteur' ? 'Vous êtes tuteur' : 'Votre dossier' }}</span>
            </a>
        @endforeach
    </div>
</div>
<style>
:root{--bg:#F6F7F5; --surface:#fff; --ink:#1D2B26; --ink-soft:#5B6B65; --primary:#0E6659; --line:#DCE3DF;}
*{box-sizing:border-box;}
body{margin:0; font-family:-apple-system,"Segoe UI",Roboto,Helvetica,Arial,sans-serif; background:var(--bg); color:var(--ink);}
.ppsel-shell{max-width:480px; margin:0 auto; padding:40px 20px;}
.ppsel-titre{font-size:1.3rem; font-weight:600; margin-bottom:20px;}
.ppsel-liste{display:flex; flex-direction:column; gap:10px;}
.ppsel-carte{display:flex; flex-direction:column; background:var(--surface); border:1px solid var(--line); border-radius:10px; padding:16px; text-decoration:none; color:inherit;}
.ppsel-nom{font-weight:600;}
.ppsel-role{font-size:.85rem; color:var(--ink-soft); margin-top:2px;}
</style>
</body>
</html>
