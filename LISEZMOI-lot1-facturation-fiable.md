# Lot 1 — Facturation fiable (livraison différentielle)

Prérequis : zip RDV puis zip Lot 0 appliqués (FacturationServiceProvider déclaré).

## Déploiement

1. Sauvegarde complète de la base.
2. Copier les fichiers par-dessus le projet.
3. php artisan migrate      (ajoute annule_le / annule_par / motif_annulation à paiements)
4. php artisan db:seed --class="Database\Seeders\Facturation\FacturationPermissionsSeeder"
   puis donner « payment.cancel » au rôle du responsable de caisse (écran des rôles).
5. php artisan optimize:clear
6. php artisan aprosafe:comptes            (rapport d'écarts des comptes patients)
7. php artisan test --filter="FacturationFiableTest|FondationsFacturationTest|ComptesPatientsTest"

## Vérifications manuelles

- Facture assurée : encaisser la part patient, puis la part assurance → le reçu du patient
  affiche toujours « reste à payer : 0 ».
- Saisir un montant supérieur au reste dû → message « … à rendre au patient ».
- Remise de 10 000 sur une ligne, validée deux fois → une seule remise, total juste.
- Consultation payée : ajouter un examen → accepté, nouveau reste dû.
  Retirer un acte déjà payé → refusé avec message ; annuler le paiement (fiche consultation,
  historique des paiements), retirer l'acte, ré-encaisser → accepté.
