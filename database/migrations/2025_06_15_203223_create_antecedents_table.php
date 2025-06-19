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
        Schema::create('antecedents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');

            $table->text('antecedents_medicaux')->nullable();
            $table->text('antecedents_chirurgicaux')->nullable();
            $table->text('antecedents_gyneco_obstetricaux')->nullable();
            $table->text('antecedents_familiaux')->nullable();
            $table->text('allergies')->nullable();
            $table->text('traitements_cours')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('antecedents');
    }
};
