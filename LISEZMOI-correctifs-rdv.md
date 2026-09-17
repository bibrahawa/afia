# Correctifs RDV — livraison différentielle du 21/09/2026

25 fichiers : 21 modifiés, 4 nouveaux. À copier par-dessus le projet en respectant l'arborescence.

Nouveaux : app/Support/CacheDisponibilite.php, app/Http/Requests/PrendreRdvPublicRequest.php,
les 2 migrations 2026_09_21_*, tests/Feature/RendezVous/PriseRendezVousTest.php.

## Déploiement

1. Sauvegarde de la base (la 1re migration modifie `appointments`).
2. php artisan migrate
3. php artisan cache:clear && php artisan view:clear
4. php artisan queue:restart (si un worker tourne)
5. php artisan test --filter=PriseRendezVousTest (base MySQL de test)

## Vérifications manuelles après déploiement

- Prise de rdv en ligne pour aujourd'hui : patient connu, puis nouveau patient (OTP).
- Réception : liste du jour, reprogrammation (le patient reçoit un SMS).
- Écran « Motifs pratiqués » d'un médecin : décocher / recocher un motif.
- Consultation avec « prochain rdv » sur un horaire déjà pris : consultation enregistrée + message d'erreur sur le rdv.
- Si l'application est derrière un proxy / load balancer : vérifier TrustProxies, sinon la limite
  d'envoi d'OTP par IP (10/heure) s'appliquerait à tous les patients à la fois.
