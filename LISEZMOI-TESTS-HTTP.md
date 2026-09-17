# Bouton « Résultats » + tests Feature des contrôleurs

Aucune migration. 11 fichiers : 6 nouveaux, 5 modifiés.

## 1. Bouton « Résultats » après validation technique

`StatutExamen::permetSaisie()` n'incluait pas `valide_technique`, alors que la correction y est autorisée
(elle annule la validation technique). Le bouton disparaissait de la fiche demande.

- `permetSaisie()` inclut désormais `valide_technique` et devient la **seule** règle utilisée par
  `ResultatService`, la page de saisie et la fiche demande (les trois avaient chacun leur liste).
- Le bouton s'appelle **« Corriger »** quand l'examen est validé techniquement.

## 2. Tests Feature HTTP (25 tests)

Ils passent par de vraies requêtes : routes, middlewares `auth` / `module` / `can`, validation,
contrôleurs, rendu des vues Blade (donc aussi votre layout `layouts.backend`).

| Fichier | Tests | Couvre |
|---|---|---|
| `Http/CircuitHttpTest` | 6 | Circuit complet de l'enregistrement à la remise (13 écrans), PDF, rejet d'échantillon, blocage par valeur critique puis déblocage, demande invalide, annulation |
| `Http/PermissionsHttpTest` | 10 | Matrice d'accès des 4 rôles labo, actions sensibles refusées au technicien, sans rôle, module désactivé, invité, **404 sur 6 écrans pour une autre clinique**, page d'arrivée par rôle |
| `Http/CatalogueHttpTest` | 5 | Écrans, création avec paramètres / normes / formule, formule invalide, code unique, modification du prix et désactivation |
| `Http/ResultatsPublicsHttpTest` | 4 | Lien signé sans session, lien modifié / non signé / expiré, PDF sans casser la signature, rétention si impayé |

Plus `Unit/Labo/StatutExamenTest` (2 tests).

```bash
php artisan optimize:clear
php artisan test --filter=Labo      # 60 tests attendus
```

Les deux tests PDF sont **ignorés** (et non en échec) si `barryvdh/laravel-dompdf` n'est pas installé.

### Si un test HTTP échoue sur une vue

Les tests rendent aussi votre layout, votre sidebar et votre navbar. Une erreur du type
« Route [xxx] not defined » ou « Attempt to read property on null » dans `layouts/*.blade.php`
vient de ces fichiers, pas du module : envoyez-moi le message, je vous dirai quoi corriger.

## Fichiers

| Fichier | Statut |
|---|---|
| `tests/Feature/Labo/Http/CircuitHttpTest.php` | nouveau |
| `tests/Feature/Labo/Http/PermissionsHttpTest.php` | nouveau |
| `tests/Feature/Labo/Http/CatalogueHttpTest.php` | nouveau |
| `tests/Feature/Labo/Http/ResultatsPublicsHttpTest.php` | nouveau |
| `tests/Unit/Labo/StatutExamenTest.php` | nouveau |
| `tests/Feature/Labo/LaboTestCase.php` | modifié (helper `creerUtilisateur`) |
| `app/Enums/Labo/StatutExamen.php` | modifié |
| `app/Services/Labo/ResultatService.php` | modifié |
| `resources/views/labo/paillasse/saisie.blade.php` | modifié |
| `resources/views/labo/demandes/show.blade.php` | modifié — **inclut le lot 2** |
