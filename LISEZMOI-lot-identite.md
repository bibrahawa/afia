# Lot Identité : fond médical, logos, signature et cachet, signature des médecins

À appliquer après le lot consentement (routes/web.php en est la dernière version).

    unzip -o hali-lot-identite.zip -d .
    php artisan migrate            # 5 colonnes sur etablissements, 1 sur employees
    php artisan storage:link       # INDISPENSABLE : sans lui, les logos téléversés ne s'affichent pas
    php artisan optimize:clear

Vérifier l'extension GD (DomPDF en a besoin pour les PNG transparents) :
    php -m | grep -i "^gd$"
Sans GD : aucune erreur, mais logos, signatures et cachets sont omis des PDF ;
l'écran « Identité de la clinique » affiche alors une alerte.

## 1. Fond médical (option A)
Pictogrammes en filigrane sur les 7 pages publiques (rendez-vous, connexion patient, espace santé,
choix du dossier, annulation ×2, mot de passe oublié). Vectoriel intégré, aucune image à télécharger,
plus pâle sur téléphone, retiré à l'impression.
Page de rendez-vous : logo de la clinique ; message + bouton « Appeler » quand aucun motif n'est ouvert
(avant : page blanche) ; départements sans motif masqués.

## 2. Paramètres › Identité de la clinique (administrateur : setting.access)
Coordonnées imprimées, logo de l'application, logo des documents, signature, cachet, signataire,
aperçu en direct d'une facture.

## 3. Tailles imposées (contrôle dans le navigateur ET sur le serveur)
| Image                  | Formats           | Dimensions acceptées      | Idéal       | Poids max |
|------------------------|-------------------|---------------------------|-------------|-----------|
| Logo de l'application  | PNG, JPG, WEBP    | 128×128 à 2048×2048 px    | 512×512 px  | 1 Mo      |
| Logo des documents     | PNG, JPG          | 300×80 à 2400×1200 px     | 1200×300 px | 1 Mo      |
| Signature              | PNG transparent   | 300×100 à 1600×800 px     | 900×300 px  | 500 Ko    |
| Cachet                 | PNG transparent   | 250×250 à 1200×1200 px    | 600×600 px  | 500 Ko    |
Contrôles : type réel du fichier (pas l'extension), poids, dimensions, proportions, transparence.
Règles communes : app/Support/Images/ImageControlee.php (FICHES).

## 4. Où apparaissent les images
- Logo : menu, page de rendez-vous, tous les documents, rapports imprimés.
- Signature + cachet de la clinique : facture A5, facture d'hospitalisation.
- Cachet de la clinique + nom de la caisse : reçu.
- Signature PROPRE du médecin (Mon profil › Ma signature, déposée par lui seul) :
  ordonnances et demandes d'examens (A5 et 80 mm), certificats médicaux. Jamais le cachet de la clinique.
Signatures et cachets sont stockés hors du dossier public (disque « local ») : aucune adresse web,
seulement intégrés aux documents. Chaque dépôt ou retrait est journalisé.

## À tester
1. Paramètres › Identité de la clinique : envoyer une signature sur fond blanc → refusée avec explication.
2. Envoyer logo, signature et cachet conformes → aperçu, puis une facture et un reçu (1 page chacun).
3. Un médecin : Mon profil › Ma signature → ordonnance signée ; celle d'un autre médecin ne l'est pas.
4. /rdv/<clinique> sur téléphone : fond visible, texte lisible.

Commit :
    git add -A && git commit -m "feat(identite): fond médical, logos, signature et cachet contrôlés, signature propre des médecins"
