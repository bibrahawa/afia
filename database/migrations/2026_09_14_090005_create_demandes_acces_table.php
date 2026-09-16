<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Une demande précède toujours un consentement (sauf accès d'urgence,
     * qui ne passe pas par ce circuit — voir AccesDossierSanteService).
     * `portee_demandee` est un JSON de codes App\Enums\PorteeAcces, pour
     * ne jamais demander/accorder un accès plus large que nécessaire.
     */
    public function up(): void
    {
        Schema::create('demandes_acces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();

            $table->morphs('demandeur'); // User (médecin précis) ou Etablissement
            $table->foreignId('etablissement_id')->nullable()->constrained('etablissements')->nullOnDelete();

            $table->json('portee_demandee');
            $table->enum('canal_confirmation', ['sms', 'app'])->default('sms');
            $table->string('code_confirmation', 10)->nullable();
            $table->text('motif')->nullable();

            $table->enum('statut', ['en_attente', 'acceptee', 'refusee', 'expiree'])->default('en_attente');
            $table->timestamp('expire_le')->nullable();

            $table->timestamps();

            $table->index(['patient_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demandes_acces');
    }
};
