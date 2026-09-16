<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `role` distingue le titulaire (le compte EST ce patient, cas adulte
     * standard) du tuteur (le compte gère le dossier d'un mineur ou d'un
     * proche en délégation). C'est ce qui permet à un parent de gérer les
     * dossiers de ses enfants depuis un seul compte, ou à un enfant adulte
     * de gérer celui d'un parent âgé une fois une délégation consentie
     * (voir `consentements`, bénéficiaire = Patient).
     */
    public function up(): void
    {
        Schema::create('compte_patient', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compte_patient_id')->constrained('comptes_patients')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->enum('role', ['titulaire', 'tuteur']);
            $table->timestamps();

            $table->unique(['compte_patient_id', 'patient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compte_patient');
    }
};
