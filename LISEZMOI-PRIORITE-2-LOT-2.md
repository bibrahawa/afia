# Priorité 2 — lot 2 : remise tracée et maladies à déclaration obligatoire

1 migration. 22 fichiers : 9 nouveaux, 13 modifiés.
À installer **après** le lot 1 (plusieurs fichiers ont été modifiés dans les deux lots : ce zip contient leur version finale).

## Installation

```bash
php artisan migrate
php artisan optimize:clear
php artisan test --filter=Labo      # 33 tests attendus
```
Aucune nouvelle permission : rien à relancer dans les seeders.

## 1. Remise des résultats au guichet

Fiche demande › carte Comptes rendus › **« Remettre le compte rendu »**.

| Champ | Règle |
|---|---|
| Remis à | Patient, représentant ou prescripteur |
| Nom | Pré-rempli pour le patient ; obligatoire sinon |
| Lien avec le patient + pièce présentée | Obligatoires pour un représentant |
| Pièce | **Type** uniquement (CNI, passeport…), jamais le numéro |
| Motif avant règlement | Obligatoire si la part patient n'est pas réglée ; réservé à la permission `labo.facturation` |

- Toujours la **dernière version** publiée.
- La remise au **prescripteur** n'est jamais bloquée par le paiement.
- Enregistrer ouvre le PDF pour impression (autorisez les fenêtres surgissantes pour le site).
- Historique des remises sous les comptes rendus ; remises avant règlement surlignées.
- Une remise ne se modifie ni ne se supprime (garde du modèle). Journal : `labo.compte_rendu_remis`, `labo.remise_avant_reglement`.

## 2. Maladies à déclaration obligatoire (MDO)

- Nouveaux champs d'examen (Catalogue) : **maladie à déclaration obligatoire** et **notification immédiate**.
- À chaque **validation biologique**, un examen porteur d'une MDO dont un résultat est anormal (ou un germe isolé)
  ouvre une déclaration « à déclarer ». « Invalide » / « Indéterminé » ne déclenchent rien.
- Une rectification qui rend le résultat normal passe la déclaration non faite en « sans objet ».
  Une déclaration déjà faite n'est jamais modifiée automatiquement.
- Menu **Laboratoire › Déclarations (MDO)** (biologiste) : liste à déclarer / déclarées / sans objet,
  bouton « Marquer déclarée » (destinataire, référence de fiche, date, commentaire).
- Bandeau sur le tableau de bord labo quand des déclarations attendent ; badge MDO sur la fiche demande.
- Le logiciel **ne transmet rien** à l'autorité sanitaire : il rappelle et trace.

### ⚠️ À valider avant usage réel

Correspondances pré-remplies dans le catalogue (modèle et copies des établissements) — **indicatives** :

| Examen | Maladie | Immédiate |
|---|---|---|
| Goutte épaisse, TDR paludisme | Paludisme | non |
| Widal | Fièvre typhoïde | non |
| VIH | Infection à VIH | non |
| Syphilis | Syphilis | non |
| AgHBs / Hépatite C | Hépatite B / C | non |
| Coproculture | Diarrhée bactérienne (dont choléra, shigellose) | oui |

La liste officielle, les délais (le logiciel signale un retard après 24 h en immédiat, 7 jours sinon :
`LaboDeclarationMdo::DELAI_*`) et le circuit de notification doivent être confirmés avec l'autorité sanitaire
(ANSS / direction préfectorale de la santé) et le biologiste, puis ajustés dans le catalogue.

## Fichiers

| Fichier | Statut |
|---|---|
| `database/migrations/labo/2026_09_20_100001_create_labo_remises_et_declarations.php` | nouveau |
| `app/Models/Labo/LaboRemise.php`, `LaboDeclarationMdo.php` | nouveaux |
| `app/Services/Labo/RemiseService.php`, `DeclarationMdoService.php` | nouveaux |
| `app/Http/Controllers/Labo/DeclarationController.php` | nouveau |
| `resources/views/labo/declarations/index.blade.php` | nouveau |
| `tests/Feature/Labo/RemiseEtDeclarationTest.php` | nouveau (5 tests) |
| `app/Services/Labo/ValidationService.php` | modifié (détection MDO) |
| `app/Http/Controllers/Labo/CompteRenduController.php` | modifié (`remettre`) |
| `app/Http/Controllers/Labo/CatalogueController.php` | modifié (champs MDO) — **inclut le lot 1** |
| `app/Http/Controllers/Labo/DemandeController.php` | modifié (chargement des remises) — **inclut le lot 1** |
| `app/Http/Controllers/Labo/TableauBordController.php` | modifié |
| `app/Models/Labo/LaboDemande.php` | modifié (relation `remises`) — **inclut le lot 1** |
| `app/Models/Labo/LaboExamen.php` | modifié (`mdo_*`) |
| `database/seeders/Labo/LaboCatalogueModeleSeeder.php` | modifié (MDO du modèle) |
| `resources/views/labo/demandes/show.blade.php` | modifié (remise, badge MDO) — **inclut le lot 1** |
| `resources/views/labo/catalogue/form.blade.php` | modifié (champs MDO) — **inclut le lot 1** |
| `resources/views/labo/tableau-bord.blade.php`, `partials/sidebar.blade.php` | modifiés |
| `routes/labo.php` | modifié (3 routes) |
