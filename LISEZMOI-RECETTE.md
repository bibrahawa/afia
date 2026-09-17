# Recette — outils

Ce zip contient **uniquement 2 commandes** (aucun fichier existant modifié) :
```
app/Console/Commands/DiagnosticCompte.php
app/Console/Commands/Demo/GenererDemo.php
```
Installer après le zip Laboratoire et le zip Priorité 1. Laravel 11 découvre les commandes automatiquement
(`php artisan list aprosafe` pour vérifier).

## Compte bloqué / connexion impossible
```bash
php artisan aprosafe:compte 622099672                 # diagnostic seul (ne modifie rien)
php artisan aprosafe:compte 622099672 --debloquer
php artisan aprosafe:compte 622099672 --etablissement=aprosafe --role=admin
php artisan aprosafe:compte 622099672 --mot-de-passe="Nouveau@2026"
php artisan cache:clear                               # si « Trop de tentatives depuis cette adresse IP »
```
La connexion se fait par **téléphone (9 chiffres)**, pas par e-mail.

Le correctif `$fillable` de `app/Models/User.php` (compteur d'échecs jamais remis à zéro) est livré dans le
**zip Priorité 1**. Après l'avoir installé, remettez les compteurs à zéro :
```sql
UPDATE users SET login_attempts = 0, locked_until = NULL;
```

## Données de démonstration
```bash
php artisan aprosafe:demo clinique-demo-a --nom="Clinique Démo Kaloum"
php artisan aprosafe:demo clinique-demo-b --nom="Clinique Démo Ratoma"
```
Chaque commande crée : 8 comptes (admin, secrétaire, médecin, comptable, accueil labo, préleveur,
technicien, biologiste — mot de passe `Demo@2026`, téléphones affichés à la fin), chambres 101/102/201,
un service, un test, l'assurance NSIA, 4 patients (femme 32 ans, homme 58 ans, enfant 3 ans, homme sans âge)
et 11 demandes labo, une à chaque étape du circuit :

| # | État |
|---|---|
| 1 | Enregistrée, à prélever |
| 2 | Prélevée, non reçue |
| 3 | Reçue, à saisir |
| 4 | URGENTE, valeur critique non signalée |
| 5 | Validée techniquement |
| 6 | Validée biologiquement, à publier |
| 7 | Publiée v1 |
| 8 | Rectifiée (v1 + v2 rectificatif) |
| 9 | Enfant — normes non définies |
| 10 | Échantillon rejeté, re-prélèvement attendu |
| 11 | Facturée, non réglée |

Refusée en production (sauf `--force`). Relançable : comptes et patients réutilisés, nouvelles demandes créées.
Une étape en échec est signalée (✖ + message) sans bloquer les suivantes : envoyez-moi ces messages.
