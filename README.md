// ==================== INSTALLATION ET CONFIGURATION ====================

/**
 * GUIDE D'INSTALLATION
 * 
 * 1. Migrations:
 *    php artisan migrate
 * 
 * 2. Configuration queue:
 *    php artisan queue:table
 *    php artisan migrate
 * 
 * 3. Démarrer le worker queue:
 *    php artisan queue:work --queue=default --tries=3 --timeout=60
 * 
 * 4. Configuration cron (ajouter à crontab):
 *    * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1

 * * * * * cd /home/bibrah/Documents/PROJETS/PERSO/CLIENT/aprosafe && php artisan schedule:run >> /dev/null 2>&1

 * * * * * cd /home/login/public_html/ton-projet && /usr/local/php8.2/bin/php artisan queue:work --stop-when-empty >> /dev/null 2>&1
 
 * 
 * 5. Variables d'environnement (.env):
 *    NIMBA_SMS_API_KEY=votre_clé_api
 *    NIMBA_SMS_API_URL=https://api.nimbasms.com/v1
 *    NIMBA_SMS_DEFAULT_SENDER=CliniqueSMS
 *    QUEUE_CONNECTION=database
 * 
 * 6. Tests:
 *    php artisan test --filter=AppointmentReminderTest
 * 
 * 7. Commandes utiles:
 *    # Test manuel des rappels
 *    php artisan appointments:send-reminders --type=24h --dry-run

    # Commandes pour débugger les queues
    php artisan queue:work --once  # Traiter un job et s'arrêter
    php artisan queue:listen       # Écouter en continu
    php artisan queue:failed       # Voir les jobs échoués
    php artisan queue:clear
 *    
 *    # Test d'envoi SMS
 *    php artisan sms:test "+224123456789" "Test message"
 *    
 *    # Nettoyage des logs
 *    php artisan sms:clean-logs --days=90
 * 
 * SURVEILLANCE ET MONITORING:
 * 
 * - Logs dans storage/logs/laravel.log
 * - Dashboard admin à /admin/sms-report
 * - Métriques via les modèles AppointmentSmsLog
 * - Alertes en cas d'échec répétés (à implémenter selon besoins)
 **/

//php artisan storage:link
//mkdir -p storage/app/public/factures

// Architecture technique :

// Appointment - Gestion des rendez-vous
// AppointmentSmsLog - Logs des SMS envoyés

// Jobs :

// SendAppointmentReminderJob - Envoi des rappels
// ProcessDoctorUnavailabilityJob - Gestion indisponibilités

// Services :

// SmsService - Interface avec API Nimba SMS

// Commandes Artisan :

// appointments:send-reminders - Envoi manuel/automatique
// sms:test - Test d'envoi SMS
// sms:clean-logs - Nettoyage des logs
