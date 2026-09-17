# Module Laboratoire — installation

Organisation : chaque dossier Laravel standard contient un sous-dossier `Labo`.
Aucun changement de `composer.json` n'est nécessaire (namespaces `App\…\Labo` standards).

```
app/Console/Commands/Labo/        commande labo:activer
app/Enums/Labo/                   statuts, flags, types
app/Exceptions/Labo/
app/Http/Controllers/Labo/        9 contrôleurs
app/Http/Requests/Labo/
app/Jobs/Labo/                    PDF + SMS
app/Models/Labo/                  16 modèles
app/Providers/LaboServiceProvider.php
app/Services/Labo/                logique métier
app/Support/Labo/                 calculs purs (formules, normes, code-barres)
database/migrations/labo/         tables labo_*
database/seeders/Labo/            permissions + catalogue modèle
resources/views/labo/
routes/labo.php
tests/Unit/Labo/  tests/Feature/Labo/
```

## Fichiers existants MODIFIÉS (à comparer avant d'écraser)

| Fichier | Changement |
|---|---|
| `app/Models/Transaction.php` | trait `BelongsToEtablissement`, numérotation atomique par établissement |
| `app/Models/Paiement.php` | idem |
| `app/Models/Invoice.php`, `InvoiceItem.php` | trait + héritage de l'établissement de la transaction / facture |
| `app/Models/Account.php` | trait (solde patient par établissement) + imports `HasMany`/`MorphTo` manquants |
| `app/Services/BillingItemBuilderService.php` | `buildFromLaboDemande()` |
| `app/Services/BillingService.php` | remises sur `LaboExamen` |

Nouveaux fichiers « cœur » : `app/Services/NumerotationDocumentService.php`,
`database/migrations/2026_09_18_090001_rendre_facturation_multi_etablissement.php`.

## Mise en place

1. **Sauvegarde complète de la base** (la migration facturation touche les pièces comptables).
2. Copier les fichiers.
3. `bootstrap/providers.php` : ajouter `App\Providers\LaboServiceProvider::class,`
4. `bootstrap/app.php` : l'alias `module` doit exister :
   ```php
   ->withMiddleware(function (Middleware $middleware) {
       $middleware->alias(['module' => \App\Http\Middleware\EnsureModuleActive::class]);
   })
   ```
5. `resources/views/layouts/sidebar.blade.php`, après « Hospitalisations » :
   `@include('labo.partials.sidebar')`
6. ```
   php artisan migrate
   php artisan labo:activer aprosafe
   composer require barryvdh/laravel-dompdf   # si absent
   php artisan queue:work                      # jobs PDF et SMS
   ```
7. Attribuer les rôles : Accueil laboratoire, Préleveur, Technicien de laboratoire, Biologiste.
8. Dans Laboratoire › Catalogue : **saisir les prix** (0 après import) et **faire valider les normes par le biologiste**.

## Tests

- `tests/Unit/Labo` (20 tests) : exécutés et verts pendant la génération.
- `tests/Feature/*` : écrits mais **pas exécutés** ici (pas d'application Laravel complète).
  Ils exigent MySQL/MariaDB (`lockForUpdate`, `FIELD()`, `TIMESTAMPDIFF`) — configurer `phpunit.xml`.
  `php artisan test --filter=Labo`

## Points de vigilance

- **Recherche patient** : réutilise `appointment-form.patients.recherche` et `.store`. Si ces routes
  sont réservées à la réception par une permission, élargissez-la aux rôles labo.
- **Facturation multi-établissements** : transactions, factures, lignes, paiements et comptes sont désormais
  scopés. Restent mono-établissement : `InsuranceClaim`, `InsuranceSettlement*`, `Consultation`,
  `Hospitalisation`, `Package`, rapports. Une Transaction créée **sans utilisateur connecté** pour une
  consultation lèvera une exception (établissement inconnu) tant que `Consultation` n'est pas migrée.
- `TransactionService::Paiement()` et `PackageController` utilisent encore des champs/numérotations
  hérités incohérents (déjà le cas avant) : à nettoyer séparément.
- Page patient (lien SMS) : aucune session, URL signée 30 jours, `noindex`, consultation journalisée.
