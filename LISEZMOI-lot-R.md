# Lot R — Corrections issues de la revue complète

À appliquer après le lot G. Aucune migration.

    unzip -o hali-lot-R.zip -d .
    php artisan optimize:clear

Sécurité
  1. routes/api.php : désactivées les routes publiques (sans connexion, toutes cliniques)
     qui exposaient assurances des patients, actes facturés, soldes des assureurs, organismes.
     Aucun écran actif ne les utilise. Les routes publiques de création (register,
     find-or-create, check-patient, check-account, appointments, login API) restent en
     service mais limitées en débit — À DÉCIDER : une application mobile les utilise-t-elle ?
  2. Hospitalisation : « payer » et « paiement » passent de GET à POST (+ jeton CSRF).
     Un simple lien ou une image piégée pouvait clôturer un séjour et le facturer.

Fiabilité
  3. Connexion : limite par IP 10 → 60 essais / heure (IP partagée par toute une clinique).
     Le verrou par compte (5 essais, 30 min) est inchangé.
  4. Création du personnel avec accès : SMS envoyé hors transaction, rôle vérifié avant écriture.
  5. Import Excel : fichiers piégés (bombe zip) refusés avant lecture.
  6. Menu « Patients assurés » affiché seulement avec patient_insurance.view ;
     bouton « Retour » de l'écran des autorisations réparé.

Commit :
    git add -A && git commit -m "fix(securite): API publique fermée, hospitalisation en POST, limites de connexion, import protégé"
