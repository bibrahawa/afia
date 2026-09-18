# Lot 4f — Accord de la clinique, consentement du patient, ancienneté assurance

Prérequis : lots 4a à 4e appliqués.

## Déploiement

1. Sauvegarde de la base.
2. Décompresser à la RACINE du projet.
3. php artisan migrate
   (labo_partenariats : propose_le, accepte_le, accepte_par, motif_refus ;
    labo_demandes : consentement_partage_le, consentement_recueilli_par)
4. php artisan optimize:clear
5. php artisan test --filter="ReseauAccordTest|ReseauAuditTest|ReseauCorrectionsTest|ReseauLaboTest|FacturationPartenaireTest|AssuranceEtNotificationReseauTest"

Aucune permission nouvelle.

## IMPORTANT — partenariats existants

Les partenariats déjà créés restent ACTIFS : la migration ne change pas leur statut.
Seuls les NOUVEAUX partenariats naissent « proposés » et attendent l'accord de la clinique.

## Contenu

- Un partenariat proposé par le laboratoire doit être accepté par la clinique avant tout envoi ;
  elle peut aussi le refuser avec un motif. Écran « Propositions de laboratoires » et bandeau
  sur l'écran des analyses envoyées.
- Le laboratoire ne peut pas suspendre un partenariat qui attend encore une réponse ;
  son écran affiche le statut réel (en attente, actif, suspendu, refusé avec motif).
- Case « le patient a été informé » à l'envoi : la date et l'auteur sont enregistrés
  sur la demande et rappelés sur le bon d'analyses.
- Ancienneté du reste dû par tranches de 30 jours sur l'écran des créances d'assurance,
  comme pour les créances des cliniques partenaires.
