<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Vos résultats — {{ $c['etablissement']['nom'] }}</title>
    {{-- Page légère, sans CSS externe : doit s'ouvrir sur un téléphone d'entrée de gamme en 2G/3G. --}}
    <style>
        body { font-family: -apple-system, Roboto, Arial, sans-serif; margin: 0; background: #f4f6f6; color: #1f2937; font-size: 15px; line-height: 1.45; }
        header { background: #0f766e; color: #fff; padding: 16px; }
        header strong { font-size: 17px; }
        header small { opacity: .85; }
        main { padding: 12px; max-width: 760px; margin: auto; }
        .carte { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 14px; margin-bottom: 12px; }
        .bouton { display: block; text-align: center; background: #0f766e; color: #fff; padding: 15px; border-radius: 10px; text-decoration: none; font-weight: bold; font-size: 16px; }
        .avertissement { font-size: 14px; background: #fffbeb; border: 1px solid #fde68a; color: #78350f; }
        /* Corps du compte rendu (partagé avec le PDF), version téléphone */
        .cr-muted { color: #6b7280; font-size: 12px; }
        .cr-etiquette { display: block; margin-bottom: 2px; color: #6b7280; font-size: 11px; font-weight: bold; letter-spacing: .4px; text-transform: uppercase; }
        .cr-rectif { background: #fff7ed; border: 1px solid #fdba74; color: #7c2d12; border-radius: 10px; padding: 10px; margin-bottom: 10px; }
        .cr-partiel { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: 10px; margin-bottom: 10px; }
        .cr-ident { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .cr-ident td { display: block; padding: 8px 0; border-bottom: 1px solid #f3f4f6; }
        .cr-ident-nom { font-size: 17px; font-weight: bold; }
        .cr-section { background: #0f766e; color: #fff; font-size: 15px; padding: 7px 10px; border-radius: 8px; margin: 18px 0 8px; }
        .cr-examen { margin-bottom: 16px; }
        .cr-examen-entete { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .cr-examen-entete td { display: block; padding: 0; }
        .cr-examen-titre { font-weight: bold; font-size: 15px; }
        .cr-examen-methode { color: #6b7280; font-size: 12px; }
        .cr-badge { font-size: 11px; color: #b45309; border: 1px solid #b45309; padding: 0 5px; border-radius: 4px; margin-left: 4px; }
        .cr-resultats { width: 100%; border-collapse: collapse; font-size: 14px; }
        .cr-resultats th { text-align: left; color: #6b7280; font-weight: normal; font-size: 12px; border-bottom: 1px solid #e5e7eb; padding: 4px 3px; }
        .cr-resultats td { padding: 7px 3px; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
        .cr-col-norme { color: #6b7280; font-size: 12px; }
        .cr-col-sir { text-align: center; }
        .cr-groupe { font-style: italic; color: #4b5563; }
        .cr-anormal { font-weight: bold; color: #b45309; }
        .cr-critique { font-weight: bold; color: #fff; background: #b91c1c; padding: 1px 6px; border-radius: 5px; }
        .cr-ligne-critique td { background: #fef2f2; }
        .cr-resistant { font-weight: bold; color: #b91c1c; }
        .cr-germe { margin: 8px 0 4px; }
        .cr-commentaire { background: #f0fdfa; border-left: 3px solid #0f766e; padding: 8px 10px; border-radius: 0 8px 8px 0; }
        .cr-valide { margin: 4px 0 0; color: #6b7280; font-size: 12px; }
        .cr-fin { width: 100%; border-collapse: collapse; margin-top: 14px; border-top: 1px solid #e5e7eb; }
        .cr-fin td { display: block; padding-top: 8px; }
        .cr-legende { color: #6b7280; font-size: 12px; }
        .cr-signature { font-weight: bold; }
        /* Petit écran : l'unité reste visible (indispensable), les normes passent à la ligne */
        .cr-col-norme { word-break: break-word; }
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
            @if(request()->query('source') === 'portail' && Route::has('portail.dossier'))
                {{-- Ouvert depuis « Mon espace santé » : retour au dossier --}}
                <a class="bouton" href="{{ route('portail.dossier', $cr->demande()->withoutGlobalScope('etablissement')->value('patient_id')) }}#dossier"
                   style="margin-top:10px; background:#fff; color:#0f766e; border:1px solid #99f6e4">Retour à mon espace santé</a>
            @endif
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
