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
        Schema::create('insurance_coverages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insurance_company_id')->constrained()->onDelete('cascade');
            $table->morphs('coverageable'); // service, medicament, examen, etc.
            // $table->decimal('coverage_percentage', 5, 2); // 80.00 pour 80%
            $table->decimal('max_amount', 10, 2)->nullable(); // Plafond global
            $table->decimal('min_amount', 10, 2)->nullable(); 
            $table->unsignedInteger('max_usage_count')->nullable(); // Nombre limite d’actes
            $table->enum('usage_period', ['Mois', 'Trimestre', 'Annees'])->nullable(); // Fréquence limite
            $table->decimal('coverage_amount_limit', 10, 2)->nullable(); // Montant max pour cet acte
            $table->text('conditions')->nullable();
            $table->date('valid_from');
            $table->date('valid_to')->nullable();
            $table->boolean('requires_preauthorization')->default(false);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

    }

    public function down()
    {
        Schema::dropIfExists('insurance_coverages');
    }
};
