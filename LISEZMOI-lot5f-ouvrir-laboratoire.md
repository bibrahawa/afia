# Lot 5f — Ouverture d'un laboratoire indépendant

Un seul fichier : app/Console/Commands/Labo/OuvrirLaboratoire.php.
Aucune migration, aucune permission nouvelle.

## Utilisation

    php artisan hali:ouvrir-laboratoire labo-kaloum --nom="Laboratoire Central Kaloum"

    php artisan hali:ouvrir-laboratoire labo-kaloum \
        --nom="Laboratoire Central Kaloum" \
        --adresse="Kaloum, Conakry" \
        --telephone=628164422 \
        --email=contact@labokaloum.gn \
        --prefixe=LKA- \
        --avec-assurance \
        --statut=actif

Options : --sans-catalogue, --mot-de-passe=, --force (sans confirmation).
La commande est relançable : tout est créé « si absent ».

## Ce qu'elle fait

1. Établissement de type « laboratoire », avec son identité et ses préfixes de numérotation.
2. Module laboratoire activé — et rendez-vous, consultation, hospitalisation explicitement
   FERMÉS, y compris si l'établissement avait déjà servi. L'assurance n'est ouverte
   qu'avec --avec-assurance.
3. Permissions : laboratoire, réseau, rapports (et assurance si demandée).
4. Six comptes : gérant, accueil, préleveur, technicien, biologiste, comptable,
   avec numéros provisoires dérivés du slug et mot de passe initial commun.
5. Catalogue modèle importé, avec un avertissement chiffré sur les examens sans prix.
6. Tableau des identifiants à remettre, et la liste de ce qui reste à faire avec le client.

## À faire ensuite avec le client

Remplacer les numéros provisoires, TARIFER le catalogue (un examen à zéro ne facture rien
et bloque les demandes venues des cliniques partenaires), vérifier les valeurs de référence,
mettre le logo, ouvrir les premières cliniques partenaires, passer le statut en « actif ».
