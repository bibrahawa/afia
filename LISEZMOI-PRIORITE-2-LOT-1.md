# Priorité 2 — lot 1

Aucune migration. 13 fichiers : 4 nouveaux, 9 modifiés.

## 1. Liaison consultation ↔ laboratoire

**Deux lignes à ajouter vous-même** dans `resources/views/consultations/show.blade.php`
(votre fichier n'est pas livré, pour ne rien écraser) :

1. Dans la barre d'actions, **juste avant** le bouton « Retour » :
   ```blade
   @include('labo.partials.consultation-bouton', ['consultation' => $consultation])
   ```
2. **Juste après** la carte « Diagnostic et Examens Cliniques » (après son `</div>` de fermeture de `row mb-4`) :
   ```blade
   @include('labo.partials.consultation-analyses', ['consultation' => $consultation])
   ```

Les deux blocs n'affichent rien si le module labo est inactif ou si l'utilisateur n'a pas les droits.

| Élément | Comportement |
|---|---|
| Bouton « Laboratoire » › Envoyer les examens prescrits | Crée la demande à partir des tests cochés en consultation, en mode « incluse dans la consultation » (pas de double facture). Visible si au moins un test est relié au catalogue labo et qu'aucune demande n'existe déjà. |
| Bouton « Laboratoire » › Nouvelle demande d'analyses | Ouvre le formulaire pré-rempli : patient, médecin prescripteur, motif + diagnostic, examens prescrits pré-cochés. |
| Carte « Analyses de laboratoire » | Demandes de la consultation, statut de chaque examen, résultats anormaux ou critiques signalés, lien vers le dernier compte rendu. Visible par le médecin même si le patient n'a pas payé. |
| Tests non reliés | Avertissement listant les tests de la consultation sans correspondance dans le catalogue labo. |

**Relier les tests** : Laboratoire › Catalogue › modifier un examen › champ « Test de l'ancien catalogue ».
Sans cette correspondance, « Envoyer les examens prescrits » n'apparaît pas.

## 2. Âge du patient (bug corrigé)

`Patient::getAgeAttribute()` lisait `date_of_birth` (colonne inexistante) : `$patient->age` valait toujours null
dans **toute l'application** (liste patients, ordonnances, fiches). Désormais : calcul depuis `birth_date` si lisible,
sinon la colonne `age`. Ajout de `$patient->age_texte` (« 3 ans », « 8 mois », « 12 jours ») et `dateNaissance()`.

## 3. Encaissement

- Fiche demande labo : bouton « Encaisser » vers l'écran des factures impayées (`account.facture`), affiché seulement si
  la part patient n'est pas réglée.
- **Bug corrigé** : une transaction au statut `completed` (posé par `Invoice::markAsPaid`) était considérée impayée →
  résultats retenus à tort. `LaboDemande::partPatientReglee()` accepte maintenant `paid`, `approved`, `completed`.
- Lien vers la consultation d'origine sur la fiche demande.

## 4. Permissions : escalade de privilèges fermée

`UserController::assignPermissions` : un admin de clinique pouvait s'attribuer n'importe quelle permission ou rôle,
dont `super-admin`. Désormais :
- on ne peut attribuer **que les permissions que l'on possède** ;
- `etablissement.*`, `module.*` et le rôle `super-admin` : réservés à l'administrateur plateforme ;
- un rôle n'est attribuable que si l'on possède toutes ses permissions ;
- personne (hors plateforme) ne modifie **ses propres** permissions ;
- les éléments refusés sont affichés, et chaque modification est journalisée (`users.permissions_modifiees`).

## Fichiers

| Fichier | Statut |
|---|---|
| `resources/views/labo/partials/consultation-bouton.blade.php` | nouveau |
| `resources/views/labo/partials/consultation-analyses.blade.php` | nouveau |
| `tests/Feature/PatientAgeTest.php` | nouveau |
| `LISEZMOI-PRIORITE-2-LOT-1.md` | nouveau |
| `app/Http/Controllers/Labo/DemandeController.php` | modifié (`create` pré-rempli) |
| `app/Http/Requests/Labo/StoreDemandeRequest.php` | modifié (`consultation_id`, mode « consultation ») |
| `resources/views/labo/demandes/create.blade.php` | modifié (pré-remplissage) |
| `resources/views/labo/demandes/show.blade.php` | modifié (Encaisser, lien consultation) |
| `app/Http/Controllers/Labo/CatalogueController.php` | modifié (`test_id`) |
| `resources/views/labo/catalogue/form.blade.php` | modifié (champ « Test de l'ancien catalogue ») |
| `app/Models/Labo/LaboDemande.php` | modifié (statut `completed`) |
| `app/Models/Patient.php` | modifié (âge) |
| `app/Http/Controllers/UserController.php` | modifié (`assignPermissions`) |

## Tests

```bash
php artisan optimize:clear
php artisan test --filter=PatientAge
php artisan test --filter=Labo
```

Recette manuelle :
1. Catalogue : relier « Glycémie » au test « Glycémie » de l'ancien catalogue.
2. Consultation avec ce test coché → bouton Laboratoire › « Envoyer les examens prescrits » → demande créée, mode « Incluse dans la consultation », aucune nouvelle facture.
3. Carte « Analyses de laboratoire » : la demande apparaît ; après publication, lien vers le compte rendu.
4. Liste des patients : la colonne âge est remplie.
5. Connecté en admin de clinique : essayer de s'attribuer `etablissement.view` → refusé et signalé.

## Remarque sur votre `show.blade.php`

La vue affiche `$consultation->patient->phone`, qui n'existe plus depuis les comptes patients :
le téléphone s'affiche vide. Remplacez par `$consultation->patient->telephone`.
