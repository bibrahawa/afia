# Priorité 1 — Cloisonnement complet entre établissements

Ce zip contient **uniquement les fichiers ajoutés ou modifiés par la Priorité 1**.
Ordre d'installation : 1) zip Laboratoire (`aprosafe-module-laboratoire.zip`), 2) ce zip, 3) zip Recette.
Les fichiers de ce zip remplacent ceux du même nom (dont aucun ne figure dans le zip Laboratoire).

## 1. Tables désormais cloisonnées

| Groupe | Tables | Migration |
|---|---|---|
| Facturation (livré avant) | transactions, invoices, invoice_items, paiements, accounts | `2026_09_18_090001` |
| Dossiers cliniques | consultations, hospitalisations, chambres, antecedents, fichier_patients | `2026_09_19_090001` |
| Référentiels | services, tests, packages, medicaments | `2026_09_19_090002` |
| Assurances | insurance_companies, insurance_coverages, patient_insurances, insurance_claims, insurance_settlements, insurance_settlement_items | `2026_09_19_090003` |

- Données existantes rattachées à « aprosafe » (consultations/services/packages : via leur département ;
  hospitalisations : via leur chambre ; réclamations : via leur facture).
- Uniques désormais **par établissement** : `chambres.numero`, `insurance_companies.code`,
  `insurance_claims.claim_number`, `insurance_settlements.settlement_no`.
- Tables pivots (`consultation_*`, `package_services`, `package_tests`) : pas de colonne, elles héritent du parent.
- **Patient reste global.** Ce qui est cloisonné, c'est la liste des patients *suivis* (`etablissement_patient`).

## 2. Les trois niveaux de protection

**Niveau 1 — lectures** (`app/Traits/BelongsToEtablissement.php`, modifié)
Filtre automatique, désormais **fermé par défaut** : un utilisateur connecté sans établissement ne voit
plus rien (avant : il voyait tout). Exception : rôle `super-admin` sans établissement.

**Niveau 2 — écritures** (même trait)
Impossible de créer une fiche pour un autre établissement, de modifier/supprimer celle d'un autre,
ou de changer l'établissement d'une fiche — quel que soit le contrôleur. Plus `EtablissementPolicy`
enregistrée pour 21 modèles (`$this->authorize('update', $consultation)` utilisable partout).

**Niveau 3 — validation** (`app/Providers/EtablissementServiceProvider.php`, nouveau)
Les règles `exists:` / `unique:` ignorent Eloquent. Nouvelles règles, déjà appliquées dans
**24 contrôleurs** :
```php
'chambre_id' => 'required|exists_etablissement:chambres,id',
'numero'     => 'required|unique_etablissement:chambres,numero,' . $chambre->id,
```

## 3. Fuites corrigées au passage

| Où | Problème | Correction |
|---|---|---|
| `Patient::all()` (Hospitalisation, Consultation, Package, Assurances, Réclamations) | toute la base patients de la plateforme dans les listes déroulantes | `Patient::suivisParEtablissement()` |
| Tableau de bord | `Patient::count()` plateforme | patients suivis |
| `PatientController` index / show / edit / update / delete | fiche de n'importe quel patient par son id | limité aux patients suivis |
| `PatientController::rechercheRapide` | recherche partielle sur toute la plateforme (nom + téléphone) | partielle = patients suivis ; autres patients = numéro complet ou identifiant santé exact |
| `UserController` | liste, modification, suppression de TOUS les comptes ; nouvel utilisateur créé sans établissement ; département « 1 » par défaut ; rôle `super-admin` attribuable | `User::deMonEtablissement()`, établissement forcé, département de l'établissement, rôle plateforme interdit |
| `InsuranceCoverage` | acte couvert d'une autre clinique (id polymorphe non vérifiable par `exists`) | contrôle dans le modèle |
| Numéros `CLM-…` | `count() + 1` → doublons | compteur atomique par établissement |
| Numéros `SET-…` | « dernier + 1 » | compteur atomique, reprise des numéros existants |
| Consultation / hospitalisation | patient non rattaché à l'établissement | rattachement automatique |

## 4. Nouveaux fichiers

```
app/Providers/EtablissementServiceProvider.php
app/Policies/EtablissementPolicy.php
app/Support/Migrations/AjoutEtablissement.php
app/Traits/HeriteEtablissement.php
app/Traits/RattachePatientEtablissement.php
database/migrations/2026_09_19_09000{1,2,3}_*.php
tests/Feature/Etablissement/CloisonnementTest.php
```

## 5. Fichiers existants MODIFIÉS (comparer avant d'écraser)

- **Cœur** : `app/Traits/BelongsToEtablissement.php`, `app/Support/EtablissementContext.php`
- **Modèles** : Consultation, Hospitalisation, Chambre, Antecedent, FichierPatient, Service, Test, Package,
  Medicament, InsuranceCompany, InsuranceCoverage, PatientInsurance, InsuranceClaim, InsuranceSettlement,
  InsuranceSettlementItem, Patient, User
  (`User.php` inclut aussi le correctif de connexion : `login_attempts`, `locked_until`, `last_login_at` ajoutés au `$fillable`)
- **Services** : InsuranceCoverageService, InsuranceConsumptionService
- **Contrôleurs** : Api/Api_InsuranceCalculationController, Api/Api_InsuranceCompanyController,
  Api/Api_PatientInsuranceController, Appointment, Chambre, Consultation, Dashboard, Department,
  Disponibilite, Hospitalisation, InsuranceBalance, InsuranceClaim, InsuranceCompany, InsuranceCoverage,
  InsuranceSettlement, Invoices, MotifRdv, Package, Patient, PatientInsurance, Payment, Test, User
- **Requests** : StoreAppointmentRequest

Les modifications de contrôleurs sont mécaniques (règles de validation, `Patient::…`) sauf
`PatientController::rechercheRapide/store` et `UserController::store/index/update/destroy`.

## 6. Installation

1. **Sauvegarde complète** de la base.
2. Copier les fichiers.
3. `bootstrap/providers.php` :
   ```php
   App\Providers\EtablissementServiceProvider::class,
   App\Providers\LaboServiceProvider::class,
   ```
4. **Avant de migrer** — tous les comptes du personnel doivent avoir un établissement :
   ```sql
   SELECT id, name, email FROM users WHERE etablissement_id IS NULL;
   UPDATE users SET etablissement_id = (SELECT id FROM etablissements WHERE slug='aprosafe')
    WHERE etablissement_id IS NULL AND id <> <id_admin_plateforme>;
   ```
   Votre propre compte d'administration plateforme : `etablissement_id` NULL + rôle `super-admin`.
5. `php artisan migrate`
6. Rattacher les patients historiques d'Aprosafe (sinon ils disparaissent des listes) :
   ```sql
   INSERT IGNORE INTO etablissement_patient (etablissement_id, patient_id, premiere_visite_le, derniere_visite_le, created_at, updated_at)
   SELECT e.id, p.id, p.created_at, NOW(), NOW(), NOW() FROM patients p JOIN etablissements e ON e.slug = 'aprosafe';
   ```
7. `php artisan test --filter=Cloisonnement` (base MySQL de test).

## 7. Ce qui n'est PAS couvert — à connaître

- **Tests non exécutés dans mon environnement** (pas d'application Laravel complète) : 10 tests écrits,
  à lancer chez vous. La syntaxe PHP de tous les fichiers est vérifiée.
- **Écran de gestion des établissements** (`EtablissementController`) et **ModuleController** :
  réservés à la plateforme, mais protégés seulement par les permissions existantes. Vérifiez que seul
  `super-admin` a ces permissions.
- `UserController::assignPermissions` : un administrateur de clinique peut encore s'attribuer n'importe
  quelle permission, y compris des permissions plateforme → à restreindre (priorité 2).
- **Impersonation support** (`support_etablissement_id`) : prévue dans le contexte mais aucun écran ne
  la déclenche encore. En attendant, l'admin plateforme voit tout (non filtré).
- **Routes API** : le filtre repose sur `Auth::guard('web')`/`Auth::user()`. Si l'API utilise Sanctum,
  vérifier que le middleware `auth:sanctum` est bien sur toutes les routes `/api/*`.
- `bordereau_assurance.blade.php` interroge `InsuranceCompany` directement depuis la vue : désormais filtré,
  mais à déplacer dans le contrôleur.
- Messages « Un patient existe déjà avec ce numéro : NOM (ID) » : révèlent l'identité d'un patient à
  qui connaît son numéro complet. Comportement conservé (utile au guichet), à rediscuter.
