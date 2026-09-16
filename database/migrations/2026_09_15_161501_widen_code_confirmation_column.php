<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Même bug que comptes_patients.code_otp (voir la migration jumelle) :
     * cette colonne a été créée à 10 caractères avant qu'on décide de
     * hacher le code de confirmation de consentement (OtpService::hacher()
     * dans AccesDossierSanteService::demanderAcces()). Pas encore rencontré
     * en test, mais échouerait de façon identique à la première demande
     * d'accès créée.
     */
    public function up(): void
    {
        Schema::table('demandes_acces', function ($table) {
            $table->string('code_confirmation', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('demandes_acces', function ($table) {
            $table->string('code_confirmation', 10)->nullable()->change();
        });
    }
};
