<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * L'identifiant exposé au patient (carte, QR, SMS) — jamais l'id
     * auto-incrémenté interne. Généré UNE SEULE FOIS, à la toute première
     * création du dossier patient sur la plateforme, quel que soit
     * l'établissement qui l'a créé. C'est cet identifiant qui ancre le
     * dédoublonnage, l'authentification portail et le consentement.
     */
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('identifiant_national_sante', 20)->nullable()->unique()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn('identifiant_national_sante');
        });
    }
};
