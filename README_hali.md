# Hali — plateforme de gestion clinique

Application Laravel multi-établissements pour cliniques, laboratoires et (à venir) pharmacies,
conçue pour le contexte guinéen : téléphones d'entrée de gamme, connexions lentes, SMS plutôt
que notifications applicatives, français partout.

## Ce que fait l'application

| Module | Contenu |
| --- | --- |
| **Rendez-vous** | Prise de rendez-vous en ligne et au guichet, motifs avec durée réelle, disponibilités par médecin, rappels SMS |
| **Parcours patient** | Accueil du jour, constantes, file d'attente ordonnée par la clinique, écran de consultation en une page, dossier en frise, suivi de grossesse, croissance de l'enfant, certificats et arrêts de travail, écran de salle d'attente, statistiques |
| **Facturation** | Factures par consultation, hospitalisation ou laboratoire, comptes patients, encaissements et annulations tracées, remises plafonnées |
| **Assurance** | Organismes, entreprises, contrats et formules, adhérents et ayants droit, conventions tarifaires, moteur de prise en charge, réclamations, bordereaux, règlements |
| **Laboratoire** | Circuit complet ISO 15189 : demande, prélèvement, paillasse, double validation, comptes rendus versionnés et PDF, SMS au patient, maladies à déclaration obligatoire |
| **Laboratoire en réseau** | Partenariats entre une clinique et un laboratoire, envoi d'analyses, bon d'analyses, résultats, facturation inter-établissements (créances, relevés, règlements) |
| **Rapports** | Activité, recettes, impayés, créances d'assurance, laboratoire, réseau, patients, grossesses — à l'écran, à l'impression ou en CSV |

## Pile technique

- PHP 8.3, Laravel 11/12, MySQL/MariaDB
- Blade + Bootstrap (thème KaiAdmin), JavaScript natif (pas de framework front)
- Spatie Permission (rôles et permissions), barryvdh/laravel-dompdf (PDF), SMS Nimba

## Organisation du code

Chaque module a son sous-dossier dans l'arborescence Laravel standard — pas de dossier `app/Modules` :

```
app/Models/Labo/            app/Services/Assurance/      app/Http/Controllers/Parcours/
app/Enums/Parcours/         app/Support/Facturation/     database/migrations/labo/
routes/labo.php             routes/assurance.php         routes/parcours.php
resources/views/labo/       resources/views/assurance/   resources/views/parcours/
```

Les noms de dossiers et de fichiers sont **en français** : ne les renommez pas, les vues et les
chargements de routes en dépendent.

Chaque module est branché par un service provider déclaré dans `bootstrap/providers.php` :
`FacturationServiceProvider`, `AssuranceServiceProvider`, `LaboServiceProvider`,
`ParcoursServiceProvider`, `RapportsServiceProvider`.

## Installation en local

```bash
git clone <dépôt> hali && cd hali
composer install
cp .env.example .env && php artisan key:generate
# renseigner DB_*, NIMBA_SMS_*, APP_URL dans .env
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Comptes et données de démonstration : voir `database/seeders`.

## La marque

Le nom commercial de la plateforme est **Hali**, centralisé dans `config/marque.php` et lu par
`App\Support\Marque` : écrans, documents, e-mails et SMS. Il se change par `.env`
(`MARQUE_NOM`, `MARQUE_SMS_EXPEDITEUR`) sans toucher au code.

À ne pas confondre : **Aprosafe** est le nom de la *clinique pilote*, c'est-à-dire un
établissement en base — pas la plateforme. Les documents portent toujours le nom de
l'établissement en en-tête, et la marque en pied de page.

Les anciennes commandes `aprosafe:…` restent acceptées en alias le temps que les serveurs
déjà en place mettent à jour leurs tâches cron.

## Principes à respecter en contribuant

1. **Cloisonnement** : tout modèle porte `etablissement_id` et le trait `BelongsToEtablissement`.
   Une écriture chez un autre établissement passe par `App\Support\ContexteTemporaire`, jamais
   par un `withoutGlobalScopes()` isolé.
2. **Un seul chemin pour l'argent** : `SoldeTransaction` pour lire un solde, `PaymentService`
   pour encaisser, `ReglementAssuranceService` pour les assureurs. Aucun calcul de solde ailleurs.
3. **Rien ne se supprime** : une visite se clôt, un document s'annule, un paiement s'annule avec
   motif. Les factures encaissées ou déclarées sont figées.
4. **Pas de valeur inventée** : les z-scores n'apparaissent qu'après import des tables OMS, les
   prix viennent du catalogue de l'établissement.
5. **Tests** : chaque lot livré a sa classe dans `tests/Feature/<Module>/`. `php artisan test`
   doit rester vert.

## Commandes utiles

| Commande | Rôle |
| --- | --- |
| `php artisan hali:comptes [--corriger]` | Contrôle les soldes des comptes patients |
| `php artisan assurance:synchroniser-couvertures` | Recalcule les droits depuis le référentiel |
| `php artisan hali:rappels-cpn --jours=3` | SMS de rappel des consultations prénatales |
| `php artisan hali:importer-normes-oms <fichier> <indicateur> <sexe>` | Tables de croissance OMS |
| `php artisan labo:activer <etablissement>` | Active le module laboratoire et importe le catalogue modèle |

## Déploiement

- Hébergement mutualisé LWS : `docs/GUIDE-DEPLOIEMENT-LWS.md`
- Script de mise en ligne : `scripts/deploy.sh`
- Nettoyage du dépôt : `scripts/nettoyer-projet.sh --dry-run`

## Documentation

- Guides de formation (Word) : assurance, parcours patient, laboratoire en réseau
- Documentations techniques par module, tenues à jour à chaque lot
- Guide d'utilisation complet de l'application
