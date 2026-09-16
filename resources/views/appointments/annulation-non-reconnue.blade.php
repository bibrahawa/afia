<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Vérification de votre rendez-vous</title>
</head>
<body>
<div class="an-shell">
    <div class="an-carte">
        <div class="an-icone">?</div>

        <h1 class="an-titre">Ce rendez-vous est-il le vôtre ?</h1>
        <p class="an-texte">
            Un rendez-vous a été pris avec ce numéro de téléphone. Si c'est bien vous,
            vous n'avez rien à faire — il reste confirmé normalement.
        </p>

        <div class="an-recap">
            <div class="an-ligne">
                <span class="an-label">Motif</span>
                <span class="an-valeur">{{ $appointment->motifRdv->nom ?? '—' }}</span>
            </div>
            <div class="an-ligne">
                <span class="an-label">Médecin</span>
                <span class="an-valeur">Dr. {{ $appointment->employee->full_name }}</span>
            </div>
            <div class="an-ligne">
                <span class="an-label">Date</span>
                <span class="an-valeur">{{ $appointment->appointment_datetime->translatedFormat('l j F') }}</span>
            </div>
            <div class="an-ligne">
                <span class="an-label">Heure</span>
                <span class="an-valeur">{{ $appointment->appointment_time->format('H:i') }}</span>
            </div>
        </div>

        @if(!in_array($appointment->status, ['pending', 'confirmed']))
            <p class="an-deja-traite">Ce rendez-vous n'est plus actif (déjà annulé ou terminé) — aucune action nécessaire.</p>
        @else
            <p class="an-question">Si vous n'êtes pas à l'origine de cette réservation :</p>

            <form method="POST" action="{{ url()->full() }}" id="formAnnuler">
                @csrf
                <button type="submit" class="an-btn an-btn-danger">Ce n'est pas moi — annuler ce rendez-vous</button>
            </form>

            <p class="an-note">Si c'est bien vous, ignorez simplement ce message.</p>
        @endif
    </div>
</div>

<style>
:root{
    --primary:#087f6b; --primary-dark:#056655; --primary-soft:#e9f7f3;
    --danger:#c4472d; --danger-soft:#fff1ed;
    --bg:#f5f7f6; --surface:#ffffff; --text:#17231f; --text-soft:#66756f; --border:#e1e8e5;
}
*{box-sizing:border-box;}
body{margin:0;background:var(--bg);color:var(--text);font-family:-apple-system,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;}
.an-shell{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;}
.an-carte{max-width:440px;width:100%;background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:28px 24px;box-shadow:0 8px 30px rgba(20,40,34,.08);}
.an-icone{width:52px;height:52px;border-radius:50%;background:var(--danger-soft);color:var(--danger);display:flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:800;margin-bottom:18px;}
.an-titre{font-size:1.3rem;font-weight:750;margin:0 0 8px;letter-spacing:-.02em;}
.an-texte{color:var(--text-soft);font-size:.9rem;line-height:1.55;margin:0 0 20px;}
.an-recap{border:1px solid var(--border);border-radius:14px;padding:6px 16px;margin-bottom:20px;}
.an-ligne{display:flex;justify-content:space-between;padding:11px 0;border-bottom:1px solid var(--border);font-size:.86rem;}
.an-ligne:last-child{border-bottom:none;}
.an-label{color:var(--text-soft);}
.an-valeur{font-weight:650;text-align:right;}
.an-question{font-size:.88rem;font-weight:600;margin:0 0 10px;}
.an-btn{width:100%;min-height:50px;border:0;border-radius:12px;font-size:.92rem;font-weight:700;cursor:pointer;}
.an-btn-danger{background:var(--danger);color:#fff;}
.an-btn-danger:active{opacity:.9;}
.an-note{color:var(--text-soft);font-size:.78rem;text-align:center;margin:14px 0 0;}
.an-deja-traite{color:var(--text-soft);font-size:.88rem;text-align:center;padding:14px;background:var(--bg);border-radius:12px;}
</style>

<script>
document.getElementById('formAnnuler')?.addEventListener('submit', function(e){
    if(!confirm("Confirmer l'annulation de ce rendez-vous ?")){
        e.preventDefault();
    }
});
</script>
</body>
</html>
