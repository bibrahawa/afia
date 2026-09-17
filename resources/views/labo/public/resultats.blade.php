<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Vos résultats — {{ $c['etablissement']['nom'] }}</title>
    {{-- Page légère, sans CSS externe : doit s'ouvrir sur un téléphone d'entrée de gamme en 2G/3G. --}}
    <style>
        body { font-family: -apple-system, Roboto, Arial, sans-serif; margin: 0; background: #f3f6f5; color: #222; font-size: 15px; }
        header { background: #087f6b; color: #fff; padding: 14px 16px; }
        header small { opacity: .85; }
        main { padding: 12px; max-width: 760px; margin: auto; }
        .carte { background: #fff; border-radius: 8px; padding: 12px; margin-bottom: 12px; box-shadow: 0 1px 2px rgba(0,0,0,.06); }
        .bouton { display: block; text-align: center; background: #087f6b; color: #fff; padding: 12px; border-radius: 6px; text-decoration: none; font-weight: bold; }
        .avertissement { font-size: 13px; background: #fff7e6; border-left: 4px solid #e8a317; padding: 10px; }
        .cr-ident { width: 100%; font-size: 13px; } .cr-ident td { display: block; padding: 2px 0; }
        .cr-section { background: #087f6b; color: #fff; font-size: 15px; padding: 6px 8px; border-radius: 4px; margin: 16px 0 6px; }
        .cr-examen { margin-bottom: 12px; } .cr-examen-titre { font-weight: bold; margin: 6px 0; }
        .cr-resultats { width: 100%; border-collapse: collapse; font-size: 13px; }
        .cr-resultats th { text-align: left; color: #777; font-weight: normal; border-bottom: 1px solid #ddd; }
        .cr-resultats td { padding: 5px 3px; border-bottom: 1px solid #f0f0f0; vertical-align: top; }
        .cr-groupe { font-style: italic; color: #666; }
        .cr-anormal { font-weight: bold; color: #b45309; } .cr-critique { font-weight: bold; color: #c0262d; }
        .cr-muted { color: #777; font-size: 12px; } .cr-badge { font-size: 11px; color: #b45309; border: 1px solid #b45309; padding: 0 4px; border-radius: 3px; }
        .cr-rectif { background: #fff1e6; border-left: 4px solid #b45309; padding: 8px; margin-bottom: 8px; }
        .cr-partiel { background: #eef5ff; padding: 8px; margin-bottom: 8px; }
        .cr-commentaire { background: #f4f7f6; padding: 6px; } .cr-signature { margin-top: 12px; font-weight: bold; }
    </style>
</head>
<body>
    <header>
        <strong>{{ $c['etablissement']['nom'] }}</strong><br>
        <small>{{ $c['etablissement']['contact'] }}</small>
    </header>
    <main>
        <div class="carte">
            <a class="bouton" href="{{ request()->fullUrlWithQuery(['pdf' => 1]) }}">Télécharger le compte rendu (PDF)</a>
        </div>
        <div class="carte avertissement">
            Ces résultats doivent être interprétés par votre médecin. Une valeur signalée ↑ ou ↓ n'est pas forcément grave : n'arrêtez ni ne commencez aucun traitement sans avis médical.
        </div>
        <div class="carte">
            @include('labo.comptes-rendus._corps')
        </div>
        <p class="cr-muted" style="text-align:center">Lien personnel et confidentiel, valable pour une durée limitée. Ne le transférez pas.</p>
    </main>
</body>
</html>
