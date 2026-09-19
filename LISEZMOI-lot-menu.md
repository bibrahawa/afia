# Lot Menu — Menu latéral réorganisé + correction des permissions

À appliquer après le lot R2. Aucune migration.

    unzip -o hali-lot-menu.zip -d .
    php artisan db:seed --class="Database\\Seeders\\PermissionSeeder"   # IMPORTANT, voir ci-dessous
    php artisan optimize:clear

## IMPORTANT — permissions peut-être perdues
PermissionSeeder faisait un syncPermissions() qui RETIRAIT aux rôles les permissions des
modules (accueil et parcours, assurance, caisse, rapports, réseau de laboratoires). La notice
du lot S1 faisait lancer ce seeder seul : si vous l'avez fait, votre personnel a pu perdre
l'accès à ces écrans. Ce lot corrige le seeder (il relance les seeders de modules) :
la commande ci-dessus RÉTABLIT tous les droits. Les autorisations fines données à une
personne précise (écran « Autorisations fines ») ne sont pas touchées.

## Nouveau menu (même écrans, rangés par activité)
  Tableau de bord
  Accueil et soins      Accueil du jour · Ma file d'attente · Consultations · Hospitalisations ·
                        Grossesses suivies · Écran de la salle d'attente (nouvel onglet)
  Rendez-vous           Agenda · Mes rendez-vous · Mes horaires · Congés et pauses · Affiche et QR code
  Patients              Patients · Comptes du portail · Patients assurés
  Laboratoire           Laboratoire (sous-menu) · Laboratoires partenaires (sous-menu)
  Caisse et assurances  Caisse · Créances assurance · Contrats et conventions · Organismes payeurs
  Pilotage              Rapports · Statistiques médicales · Journal des SMS
  Paramètres            Catalogue médical · Chambres · Personnel · Comptes et accès
  Plateforme            Établissements · Modules (administrateur de la plateforme)

Une section n'apparaît que si la personne a au moins une entrée ; chaque entrée respecte à la fois
le droit ET le module de la clinique. Fichier devenu inutile : resources/views/labo/partials/sidebar.blade.php

Commit :
    git add -A && git commit -m "feat(menu): menu latéral réorganisé par activité ; PermissionSeeder ne retire plus les droits des modules"
