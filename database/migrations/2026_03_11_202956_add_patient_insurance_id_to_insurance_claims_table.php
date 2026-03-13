<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('insurance_claims', function (Blueprint $table) {
            $table->foreignId('patient_insurance_id')
                ->nullable()
                ->after('insurance_company_id')
                ->constrained('patient_insurances')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('insurance_claims', function (Blueprint $table) {
            $table->dropConstrainedForeignId('patient_insurance_id');
        });
    }
};