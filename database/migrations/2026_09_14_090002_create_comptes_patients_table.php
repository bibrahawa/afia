<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Le COMPTE (connexion portail) est volontairement séparé du dossier
     * PATIENT (clinique). Un nouveau-né ou un patient sans smartphone a un
     * dossier `Patient` sans jamais avoir de compte. Un compte peut, à
     * l'inverse, piloter plusieurs dossiers patients (voir la table pivot
     * compte_patient : gestion d'un enfant mineur, délégation familiale).
     *
     * Authentification par téléphone + OTP, plus réaliste dans le contexte
     * guinéen qu'un couple email/mot de passe comme identifiant principal.
     */
    public function up(): void
    {
        Schema::create('comptes_patients', function (Blueprint $table) {
            $table->id();
            $table->string('telephone')->unique();
            $table->string('email')->nullable()->unique();
            $table->timestamp('telephone_verifie_le')->nullable();

            $table->string('code_otp', 10)->nullable();
            $table->timestamp('otp_expire_le')->nullable();

            $table->enum('statut', ['actif', 'suspendu'])->default('actif');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comptes_patients');
    }
};
