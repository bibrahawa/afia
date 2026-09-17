# Lot 3c — Dossier patient, grossesse, statistiques (livraison différentielle)

Prérequis : lot 3b appliqué.

## Déploiement

1. Sauvegarde de la base.
2. Copier les fichiers par-dessus le projet — ATTENTION : décompresser à la RACINE du projet,
   les dossiers gardent leurs noms français (resources/views/parcours, app/Services/Parcours…).
3. php artisan migrate   (table grossesses, consultations.grossesse_id)
4. php artisan db:seed --class="Database\Seeders\Parcours\ParcoursPermissionsSeeder"
   (nouvelles permissions : parcours.dossier, parcours.grossesse, parcours.statistiques)
5. php artisan optimize:clear
6. php artisan test --filter="DossierEtGrossesseTest|ConsultationRapideTest|AccueilEtFileAttenteTest"

## Parcours à vérifier

1. Fiche patient > bouton Dossier : frise des consultations, hospitalisations, analyses, rendez-vous.
2. Sur une patiente : ouvrir un suivi de grossesse avec la DDR → terme, DPA, calendrier des CPN.
3. Consultation rapide de cette patiente : le bandeau grossesse s'affiche, la consultation est
   rattachée au suivi automatiquement.
4. Menu Grossesses suivies : liste triée par date d'accouchement prévue, termes dépassés en jaune.
5. Menu Statistiques : période, médecin, attente moyenne, absences, motifs et diagnostics.
