<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('patient_insurances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('insurance_company_id')->constrained()->onDelete('cascade');
            $table->decimal('coverage_percentage', 5, 2); // 80.00 pour 80%
            $table->string('policy_number');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['active', 'suspended', 'expired'])->default('active');
            $table->decimal('annual_limit', 10, 2)->nullable(); // Plafond annuel
            $table->decimal('used_amount', 10, 2)->default(0); // Montant déjà utilisé
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['patient_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('patient_insurances');
    }
};
