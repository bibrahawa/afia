# Lot 2b — Moteur de prise en charge (livraison différentielle)

Prérequis : lot 2a appliqué (AssuranceServiceProvider déclaré).

## Déploiement

1. Sauvegarde complète de la base.
2. Copier les fichiers par-dessus le projet.
3. Supprimer le doublon mort : app/Services/InsuranceCoverageService.php (aucune référence).
4. php artisan migrate
5. php artisan optimize:clear
6. Donner « assurance.referentiel.view » au rôle Accueil (vérification des droits).
7. php artisan test --filter="MoteurPriseEnChargeTest|ReferentielAssuranceTest|FacturationFiableTest"

## À paramétrer

- Services : renseigner la famille d'actes (imagerie, soins…) ; par défaut « consultation ».
- Contrats > formule : garanties par famille (taux, plafond par acte, exclusion, accord préalable).

## Vérifications manuelles

- Fiche patient > « Vérifier les droits » : ordre des couvertures, taux par famille, plafonds restants.
- Hospitalisation / imagerie avec accord préalable : sans bon → alerte et part patient ;
  enregistrer le bon depuis la page des droits → nouvelle facture prise en charge.
- Fiche consultation : détail « qui paie quoi » sous chaque ligne.
