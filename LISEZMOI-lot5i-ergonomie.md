# Lot 5i — Ergonomie des écrans quotidiens

Prérequis : lots 5g et 5h. Aucune migration.

## Corrigé

- « Dr Dr Alpha Barry » : le titre n'est plus ajouté quand le nom le contient déjà
  (nouvel accesseur Employee::nom_affiche, utilisé dans toutes les vues du parcours).
- « Fatoumata BaldéFemme, 41 ans » : séparateur et retour à la ligne rétablis.
- Double espace dans les noms de patients sans deuxième prénom.

## Accueil du jour

Le formulaire « Patient sans rendez-vous » passe dans une FENÊTRE, ouverte par un bouton
en haut à droite. La file d'attente occupe désormais les deux tiers de l'écran, les
rendez-vous à accueillir le tiers restant.

## Ma file d'attente (médecin)

Refonte complète :
- le PATIENT SUIVANT est mis en avant dans une carte à part, avec ses constantes,
  son temps d'attente et un seul bouton « Appeler » ;
- le reste de la file devient une liste numérotée, sobre, avec un bouton secondaire ;
- les attentes de plus de 45 minutes sont signalées ;
- les patients déjà vus sont repliés dans un bloc dépliable.

## Écran de consultation

La colonne de droite passait treize boutons colorés les uns sous les autres.
Désormais :
- le MONTANT des actes est mis en avant, c'est ce que le médecin annonce au patient ;
- le prochain rendez-vous reste accessible ;
- UNE SEULE action verte : « Terminer la consultation », plus « Enregistrer sans terminer » ;
- tout le reste (constantes, hospitalisation, analyses, certificat, impressions, dossier,
  modèle) est rangé dans « Autres actions », replié, en deux groupes : pendant la
  consultation, puis documents et dossier.

## Après installation

    php artisan view:clear && php artisan optimize:clear
