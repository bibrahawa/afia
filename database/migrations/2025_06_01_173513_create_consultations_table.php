<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->text('motif');
            $table->json('signes_cliniques')->nullable();
            $table->text('diagnostic');
            $table->text('observation')->nullable();
            $table->dateTime('prochain_rdv')->nullable();

            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->foreignId('medecin_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('prochain_medecin')->nullable()->constrained('employees')->onDelete('cascade');

            $table->boolean('est_facturee')->default(false);
            $table->timestamps();
        });

        Schema::create('consultation_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('consultation_package', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')->constrained()->onDelete('cascade');
            $table->foreignId('package_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('consultation_test', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')->constrained()->onDelete('cascade');
            $table->foreignId('test_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('consultation_medicament', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')->constrained()->onDelete('cascade');
            $table->foreignId('medicament_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultation_medicament');
        Schema::dropIfExists('consultation_test');
        Schema::dropIfExists('consultation_package');
        Schema::dropIfExists('consultation_service');
        Schema::dropIfExists('consultations');
    }


};
