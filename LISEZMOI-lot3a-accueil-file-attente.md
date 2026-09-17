# Lot 3a — Accueil et file d'attente (livraison différentielle)

Prérequis : lot 2d appliqué.

## Déploiement

1. Sauvegarde complète de la base.
2. Copier les fichiers par-dessus le projet.
3. bootstrap/providers.php : ajouter `App\Providers\ParcoursServiceProvider::class,`
4. php artisan migrate   (visites, constantes ; consultations.visite_id / appointment_id / statut, diagnostic facultatif)
5. php artisan db:seed --class="Database\Seeders\Parcours\ParcoursPermissionsSeeder"
   (secrétaire : accueil + constantes ; médecin : file + constantes ; admin : tout)
6. Motifs de rendez-vous : renseigner l'« acte facturé » de chaque motif (déjà prévu dans l'écran des motifs).
7. php artisan optimize:clear
8. php artisan test --filter="AccueilEtFileAttenteTest"

## Parcours à vérifier

1. Secrétaire > Accueil du jour : cliquer « Arrivé » sur un rendez-vous → le patient apparaît dans la file,
   l'acte est facturé (« À encaisser »).
2. Saisir les constantes (température, TA…) — valeurs anormales en rouge.
3. Médecin > Ma file d'attente : « Appeler » → la consultation s'ouvre avec motif, allergies et constantes en tête.
4. Enregistrer la consultation → retour à la file, visite terminée, rendez-vous « honoré ».
