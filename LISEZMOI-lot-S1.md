# Lot S1 — SMS par clinique

À appliquer APRÈS hali-planification.zip et les lots A, B, C1, C2
(routes/web.php, sidebar et pages d'annulation en sont les dernières versions).

    unzip -o hali-lot-S1.zip -d .
    php artisan migrate          # table sms_journal, colonne etablissements.sms_expediteur, permissions
    php artisan optimize:clear

## Expéditeur par clinique
1. Faire enregistrer chez Nimba le nom de chaque clinique (11 caractères max, sans accent).
2. Seulement ENSUITE : Administration plateforme › Établissements › Modifier › « Nom d'expéditeur SMS ».
   Un nom non enregistré fait refuser tous les SMS de la clinique (visible dans le journal).
Vide : les SMS partent sous l'expéditeur par défaut (HALI).

## À tester
- Journal des SMS (menu Communication) : `php artisan sms:test 6XXXXXXXX "Essai"` → une ligne apparaît.
- Rappel de la veille, sans rien envoyer :
    php artisan appointments:send-reminders --type=24h --dry-run --force
- Page « Je ne pourrai pas venir » : envoyer un vrai rappel à votre propre numéro,
  ouvrir le lien, libérer le créneau → le rendez-vous passe « Annulé » avec le motif
  « Patient empêché ».
