# Lot D — Assurance

À appliquer après le lot S4. Aucune migration, aucun contrôleur modifié.

    unzip -o hali-lot-D.zip -d .
    php artisan view:clear

1. Socle commun (partials/entete, styles, statut, statut-reclamation) : les 25 écrans
   d'assurance reçoivent le nouvel en-tête, les onglets du module (Contrats, Entreprises,
   Conventions, Créances), les pastilles de statut et les finitions Hali — sans changement
   de formulaires ni de logique.
2. Créances : chiffres clés, part de chaque assureur, ancienneté colorée (60 / 90 jours).
3. Droits du patient : une carte par couverture, jauges de plafond, garanties en grille.

Commit :
    git add -A && git commit -m "feat(assurance): socle commun des 25 écrans, créances et droits du patient redessinés"
