# Consultation directe et patients repartis

À appliquer APRÈS hali-lot-analyse.zip et hali-rdv-consultations.zip (ce lot remplace
leurs versions de ConsultationController.php, consultations/index.blade.php et
DossierPatientService.php).

    unzip -o hali-consultation-directe.zip -d .
    php artisan migrate          # clôt les consultations des patients déjà repartis
    php artisan route:clear && php artisan view:clear
    php artisan test --filter="ConsultationDirecteEtDepartTest|AccueilEtFileAttenteTest|ConsultationRapideTest"

Nouveau : /parcours/consultations/nouvelle (« Recevoir un patient »), permission parcours.file.
