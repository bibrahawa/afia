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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->date('appointment_date');
            $table->time('appointment_time');

             // Ajouter la colonne appointment_datetime (générée automatiquement)
            $table->datetime('appointment_datetime')->nullable();
            
            // Ajouter les colonnes de tracking pour éviter les doublons
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamp('last_minute_reminder_sent_at')->nullable();
            $table->timestamp('confirmation_sent_at')->nullable();
            
            // Ajouter les index pour optimiser les requêtes
            $table->index(['appointment_datetime', 'status'], 'idx_appointment_datetime_status');
            $table->index('reminder_sent_at', 'idx_reminder_sent_at');
            $table->index('last_minute_reminder_sent_at', 'idx_last_minute_reminder_sent_at');
            $table->index(['status', 'appointment_datetime'], 'idx_status_datetime');


            $table->text('cancellation_reason')->nullable();
            $table->boolean('patient_confirmed')->default(false);

            $table->enum('reason', ['consultation_gynecologie', 'consultation_desir_maternite', 'cpn', 'echographie_gynecologique', 
                                    'echographie_obstetricale', 'interpretation_resultats', 'monnitoring_ovulation', 'pose_sterilet_gynecologie', 
                                    'pose_implant', 'autre']);
                                    
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed', 'no_show'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
