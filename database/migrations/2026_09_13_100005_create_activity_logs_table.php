<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Journal d'audit append-only. Indispensable dès le multi-tenant :
     * c'est ce qui permet de répondre à "qui a consulté le dossier de ce
     * patient, quand, depuis quel établissement" — question qui finira
     * par arriver (litige, incident de confidentialité, demande d'un
     * client). `etablissement_id` est nullable pour couvrir les actions
     * faites par un super-admin plateforme, hors contexte d'un tenant.
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->nullable()->constrained('etablissements')->nullOnDelete();

            $table->nullableMorphs('causer');   // qui a fait l'action (User, en général)
            $table->nullableMorphs('subject');  // sur quoi (Patient, Consultation, LabResult...)

            $table->string('action');           // 'created', 'updated', 'deleted', 'viewed', 'exported'...
            $table->text('description')->nullable();
            $table->json('proprietes')->nullable(); // snapshot avant/après si pertinent
            $table->string('ip_address')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['etablissement_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
