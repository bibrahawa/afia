# Lot 4e — Corrections issues de l'audit

Prérequis : lots 4a à 4d appliqués.

## Déploiement

1. Sauvegarde de la base.
2. Décompresser à la RACINE du projet.
3. php artisan migrate     (annulation d'un règlement partenaire)
4. php artisan optimize:clear
5. php artisan test --filter="ReseauAuditTest|ReseauCorrectionsTest|ReseauLaboTest|FacturationPartenaireTest|AssuranceEtNotificationReseauTest|DossierEtGrossesseTest"

Aucune permission nouvelle : l'annulation d'un règlement suit labo.partenariat.facturer,
les correspondances suivent labo.reseau.demander.

## Contenu

- SMS de résultats sans nom de patient (numéro de demande seulement).
- Annulation d'un règlement partenaire, avec motif : imputations défaites, créances et relevés rouverts.
- Analyses envoyées au partenaire visibles dans le dossier du patient, avec le nom du laboratoire.
- Rapprochement sur la facture reçue : dû au laboratoire, facturé aux patients, marge, et alerte
  si aucune de ces analyses n'a été facturée.
- Écran « Correspondances labo » côté clinique : examen ↔ acte facturé, prix négocié, marge,
  signalement des ventes à perte.
- Ancienneté du reste dû par tranches (0-30, 30-60, 60-90, plus de 90 jours).
- Verrou sur la préparation d'un relevé (deux comptables simultanés).
- Envoi refusé si le laboratoire n'a pas tarifé un examen sélectionné.
- Avertissement sur l'écran du partenariat quand personne ne facture le patient.
