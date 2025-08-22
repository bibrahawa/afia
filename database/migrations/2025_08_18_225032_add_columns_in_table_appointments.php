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
        Schema::table('appointments', function (Blueprint $table) {
            
            $table->text('cancellation_reason')->nullable()->after('cancelled_at');
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamp('confirmation_sent_at')->nullable();
            $table->boolean('patient_confirmed')->default(false);
            $table->datetime('appointment_datetime')
                    ->virtualAs("TIMESTAMP(appointment_date, appointment_time)")
                    ->index();

            $table->index(['appointment_date', 'status']);
            $table->index(['employee_id', 'appointment_date']);
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['cancellation_reason', 'reminder_sent_at', 'confirmation_sent_at', 'patient_confirmed', 'appointment_datetime']);
        });
    }
};
