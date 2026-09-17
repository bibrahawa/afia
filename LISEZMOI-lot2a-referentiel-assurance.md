# Lot 2a — Référentiel assurance (livraison différentielle)

Prérequis : branche feat/rdv-facturation-lots-0-1 (RDV + lots 0 et 1).

## Déploiement

1. Sauvegarde complète de la base.
2. Copier les fichiers par-dessus le projet.
3. bootstrap/providers.php : ajouter `App\Providers\AssuranceServiceProvider::class,`
4. php artisan migrate
   - crée entreprises, patient_emplois, assurance_contrats, assurance_formules,
     assurance_adhesions, assurance_beneficiaires ; ajoute insurance_companies.type
     et patient_insurances.beneficiaire_id ;
   - reprend chaque ligne patient_insurances existante en contrat / formule /
     adhésion / bénéficiaire (valeurs et consommation conservées à l'identique).
5. php artisan db:seed --class="Database\Seeders\Assurance\AssurancePermissionsSeeder"
   puis donner les permissions assurance.referentiel.* aux rôles concernés.
6. Le module « assurance » doit être actif pour l'établissement (il l'est pour Aprosafe).
7. php artisan optimize:clear
8. php artisan test --filter="ReferentielAssuranceTest|FacturationFiableTest|FondationsFacturationTest"

## Parcours à vérifier

1. Assurance > Compagnies : renseigner le type (assureur, mutuelle, entreprise…).
2. Assurance > Entreprises : créer l'employeur, rattacher un employé (matricule, poste).
3. Assurance > Contrats : créer le contrat (payeur + entreprise souscriptrice), une formule
   (taux, plafond, âge limite des enfants), inscrire l'employé comme adhérent.
4. Sur l'adhésion : ajouter l'épouse (conjoint) et un enfant.
5. Consultation de l'épouse avec un acte conventionné → la part assurance apparaît.
6. Fiche patient : bloc « Assurance et employeur ».
