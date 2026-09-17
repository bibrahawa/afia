# Lot 3b — Consultation rapide (livraison différentielle)

Prérequis : lot 3a appliqué.

## Déploiement

1. Sauvegarde de la base.
2. Copier les fichiers par-dessus le projet.
3. php artisan migrate   (posologie sur consultation_medicament, modèles de consultation)
4. php artisan optimize:clear
5. php artisan test --filter="ConsultationRapideTest|AccueilEtFileAttenteTest"

Aucune nouvelle permission : l'écran utilise « parcours.file » (médecins).

## Parcours à vérifier

1. Ma file d'attente > Appeler → le nouvel écran s'ouvre avec motif, allergies, constantes.
2. Ajouter un médicament depuis les propositions, régler la posologie dans le tableau.
3. Cliquer un diagnostic fréquent, ajouter un signe, cocher « 1 semaine » pour le prochain rdv.
4. Terminer → retour à la file, facture recalculée, rendez-vous créé.
5. Rouvrir la consultation (fiche) puis « Enregistrer comme modèle » ; le modèle apparaît
   au patient suivant et se charge en un clic.
