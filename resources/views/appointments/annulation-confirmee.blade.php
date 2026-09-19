<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rendez-vous annulé</title>
@include('partials.fond-medical')
</head>
<body>
<div class="ac-shell">
    <div class="ac-carte">
        <div class="ac-icone">✓</div>
        @if($empeche ?? false)
            <h1 class="ac-titre">Créneau libéré</h1>
            <p class="ac-texte">Merci de nous avoir prévenus. Votre rendez-vous est annulé et le créneau est proposé à un autre patient.</p>
            @if(isset($appointment) && $appointment->etablissement?->slug)
                <a href="{{ route('rdv', ['etablissement' => $appointment->etablissement->slug]) }}" class="ac-btn">Reprendre un rendez-vous</a>
            @endif
        @else
            <h1 class="ac-titre">Rendez-vous annulé</h1>
            <p class="ac-texte">
                Ce rendez-vous a bien été annulé. Si quelqu'un a réservé par erreur en utilisant
                votre numéro, vous n'avez rien d'autre à faire.
            </p>
        @endif
    </div>
</div>
<style>
:root{--primary:#0f766e;--bg:#f5f7f6;--surface:#fff;--text:#17231f;--text-soft:#66756f;--border:#e1e8e5;}
*{box-sizing:border-box;}
body{margin:0;background:var(--bg);color:var(--text);font-family:-apple-system,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;}
.ac-shell{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;}
.ac-carte{max-width:420px;width:100%;background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:32px 24px;text-align:center;box-shadow:0 8px 30px rgba(20,40,34,.08);}
.ac-icone{width:56px;height:56px;border-radius:50%;background:#f0fdfa;color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:1.6rem;font-weight:800;margin:0 auto 18px;}
.ac-titre{font-size:1.25rem;font-weight:750;margin:0 0 10px;}
.ac-texte{color:var(--text-soft);font-size:.88rem;line-height:1.55;margin:0;}
.ac-btn{display:inline-flex;align-items:center;justify-content:center;min-height:48px;margin-top:20px;padding:0 22px;border-radius:12px;background:var(--primary);color:#fff;font-weight:700;text-decoration:none;}
</style>
</body>
</html>
