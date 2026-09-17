# Lot 4d — Corrections et compléments du laboratoire en réseau

Prérequis : lots 4a, 4b et 4c appliqués. Aucune migration, aucune permission nouvelle.

## Déploiement

1. Décompresser à la RACINE du projet.
2. php artisan optimize:clear
3. php artisan test --filter="ReseauCorrectionsTest|ReseauLaboTest|FacturationPartenaireTest|AssuranceEtNotificationReseauTest"

## Corrigé

- Nom du laboratoire et liste des examens vides côté clinique (lectures bloquées par le cloisonnement).
- Facturation par la clinique impossible : ses lignes d'examen appartiennent au laboratoire.
- Prescripteur illisible côté laboratoire (employé d'un autre établissement).
- Créance calculée sur le total arrondi au lieu du prix négocié ligne par ligne.
- Demande envoyée puis facturation de la clinique en échec : la demande est annulée.
- Recherche de patient dépendante du module Assurance.

## Ajouté

- Bon d'analyses imprimable à remettre au patient (examens, à jeun, urgence, règlement).
- Annulation d'une demande par la clinique tant que le laboratoire n'a rien prélevé.
