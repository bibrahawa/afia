# Lot rendez-vous et consultations

Aucune migration. À appliquer APRÈS hali-lot-analyse.zip (admin-theme.css en inclut la suite).

    unzip -o hali-rdv-consultations.zip -d .
    php artisan view:clear && php artisan optimize:clear

## Menu
- « Rendez-vous » ouvre désormais les rendez-vous de TOUTE la clinique (permission appointment.view).
- « Mes rendez-vous » n'apparaît que pour un compte rattaché à un médecin.

## Retour arrière
Chaque fichier remplace l'original du même chemin : `git checkout -- <fichier>` suffit.
