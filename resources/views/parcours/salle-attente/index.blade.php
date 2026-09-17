<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="refresh" content="20">
    <title>Salle d'attente</title>
    <style>
        body { font-family: Arial, sans-serif; background: #0d2b26; color: #fff; margin: 0; padding: 24px 32px; }
        h1 { font-size: 30px; margin: 0 0 18px; letter-spacing: 1px; }
        .colonnes { display: flex; gap: 28px; }
        .appel { flex: 1.2; }
        .attente { flex: 1; }
        .carte { background: #12403a; border-radius: 12px; padding: 18px 22px; margin-bottom: 14px; }
        .carte.appel-actif { background: #087f6b; }
        .nom { font-size: 34px; font-weight: bold; }
        .medecin { font-size: 20px; opacity: .85; }
        .rang { font-size: 22px; opacity: .7; margin-right: 10px; }
        .ligne { display: flex; align-items: center; background: #12403a; border-radius: 10px; padding: 12px 18px; margin-bottom: 10px; font-size: 22px; }
        .titre-colonne { font-size: 18px; text-transform: uppercase; letter-spacing: 2px; opacity: .7; margin-bottom: 10px; }
        .heure { position: absolute; top: 24px; right: 32px; font-size: 20px; opacity: .8; }
        .vide { opacity: .6; font-size: 20px; }
    </style>
</head>
<body>
    <div class="heure">{{ now()->format('H:i') }}</div>
    <h1>Salle d'attente</h1>

    <div class="colonnes">
        <div class="appel">
            <div class="titre-colonne">Patient appelé</div>
            @forelse($enConsultation as $v)
                <div class="carte appel-actif">
                    <div class="nom">{{ $v->patient->first_name }} {{ mb_substr($v->patient->last_name, 0, 1) }}.</div>
                    <div class="medecin">Dr {{ $v->medecin->full_name }}</div>
                </div>
            @empty
                <div class="carte vide">Aucun patient en consultation.</div>
            @endforelse
        </div>

        <div class="attente">
            <div class="titre-colonne">Prochains passages</div>
            @forelse($enAttente->take(8) as $index => $v)
                <div class="ligne">
                    <span class="rang">{{ $index + 1 }}</span>
                    <span>{{ $v->patient->first_name }} {{ mb_substr($v->patient->last_name, 0, 1) }}.</span>
                    <span style="margin-left:auto; font-size:18px; opacity:.8">Dr {{ $v->medecin->full_name }}</span>
                </div>
            @empty
                <div class="ligne vide">Personne en attente.</div>
            @endforelse
        </div>
    </div>
</body>
</html>
