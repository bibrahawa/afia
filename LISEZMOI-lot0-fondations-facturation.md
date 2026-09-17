# Lot 0 — Fondations facturation (livraison différentielle)

## Ordre d'application

1. Zip « correctifs-rdv-2026-09-21 » (s'il n'est pas encore appliqué).
2. CE zip, par-dessus. Il contient une version de ConsultationController qui réunit
   les correctifs RDV ET ta correction de destroy() : ne garde pas celle du zip RDV.

## Déploiement

1. Sauvegarde complète de la base.
2. bootstrap/providers.php : ajouter `App\Providers\FacturationServiceProvider::class,`
   (à côté de LaboServiceProvider). Sans cette ligne, rien ne change — ni la carte des
   types, ni les migrations du dossier database/migrations/facturation.
3. php artisan migrate   ← IMMÉDIATEMENT après le déploiement du code
4. php artisan optimize:clear
5. php artisan test --filter="FondationsFacturationTest|ComptesPatientsTest|PriseRendezVousTest"

## Vérifications manuelles

- Aprosafe : une facture A80, un reçu A5, une ordonnance → en-tête identique à avant.
- Clinique de démo : les mêmes documents portent SON nom (plus d'Aprosafe).
- Assurances > Couvertures : créer et modifier une couverture sur un Package et une Chambre.
- Une consultation avec remise : la remise s'applique toujours.
