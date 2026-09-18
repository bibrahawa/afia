# Lot 5c — Commande d'essai du parcours complet

Un seul fichier : app/Console/Commands/ParcoursTest.php. Aucune migration, aucune permission.

## Utilisation

    php artisan hali:parcours-test                      tous les cas
    php artisan hali:parcours-test --cas=labo --cas=reseau
    php artisan hali:parcours-test --garder             conserve les données créées
    php artisan hali:parcours-test --force              autorise l'exécution en production

Cas disponibles : rdv, consultation, caisse, assurance, urgence, depart, absence,
grossesse, labo, reseau.

## Ce que la commande garantit

- **Rien n'est conservé** par défaut : tout se déroule dans une transaction annulée à la fin.
- **Aucun SMS ne part** : le service d'envoi est remplacé par un double qui les compte.
- **Refus d'exécution en production** sans --force.

## Ce qu'elle vérifie

Chaque cas contrôle des règles, pas seulement l'absence d'erreur : montant facturé égal au
tarif de l'acte, seconde arrivée refusée, posologie conservée, reste dû exact après paiement
partiel, encaissement au-delà du dû refusé, part assurance à 80 %, urgence en tête de file,
facture annulée au départ du patient mais refus si un encaissement existe, consultation
ordinaire qui ne valide pas une CPN, double validation au laboratoire, prix négocié à −10 %,
créance et relevé soldés dans le réseau.

Elle se termine par un bilan et un code de sortie non nul si un contrôle échoue : utilisable
dans une intégration continue ou après un déploiement.

## Pré-requis dans la base

La commande s'appuie sur ce qui existe déjà : un établissement actif, un utilisateur rattaché,
un médecin actif. Les actes, le motif et le médicament d'essai sont créés s'ils manquent.
Les cas labo et reseau s'annoncent ignorés si le module laboratoire n'est pas actif.
