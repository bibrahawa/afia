<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `code_otp` a été créé à 10 caractères, pensé pour un code à 6
     * chiffres en clair. Depuis qu'on le hache (OtpService::hacher(),
     * Hash::make() ≈ 60 caractères en bcrypt), chaque écriture échouait
     * avec "Data too long for column". Même correctif que celui déjà fait
     * sur demandes_acces.code_confirmation pour la même raison — colonne
     * dimensionnée avant qu'on décide de hacher les codes à usage unique.
     */
    public function up(): void
    {
        Schema::table('comptes_patients', function ($table) {
            $table->string('code_otp', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('comptes_patients', function ($table) {
            $table->string('code_otp', 10)->nullable()->change();
        });
    }
};
