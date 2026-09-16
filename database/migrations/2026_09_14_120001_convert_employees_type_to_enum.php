<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `type` était une chaîne libre. En vérifiant tout le code existant,
     * deux problèmes réels trouvés :
     * 1. Le formulaire employees/edit.blade.php enregistre 'Doctor', mais
     *    l'ancien AppointmentController filtrait sur 'Medecin' — aucun
     *    médecin n'aurait donc jamais été proposable à la réservation en
     *    conditions réelles. Corrigé ici ET dans les contrôleurs.
     * 2. 'Acountant' (faute de frappe) était une des options du formulaire.
     *
     * Avec 200 cliniques et autant de personnes différentes saisissant du
     * personnel, une chaîne libre finit par diverger silencieusement —
     * une énumération stricte empêche physiquement la faute de frappe.
     */
    public function up(): void
    {
        // Corrige les données existantes AVANT de contraindre la colonne,
        // sinon l'ALTER échoue sur toute ligne qui ne correspond à aucune
        // valeur de la nouvelle énumération.
        DB::table('employees')->where('type', 'Acountant')->update(['type' => 'Accountant']);
        DB::table('employees')->where('type', 'Medecin')->update(['type' => 'Doctor']);

        Schema::table('employees', function ($table) {
            $table->enum('type', ['Doctor', 'Laboratory', 'Reception', 'Pharmacy', 'Accountant', 'Nurse', 'Other'])
                ->nullable()
                ->default('Other')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function ($table) {
            $table->string('type')->nullable()->change();
        });
    }
};
