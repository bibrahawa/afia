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
        Schema::create('medicaments', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('forme')->nullable(); // comprimé, sirop...
            $table->string('dosage')->nullable(); // 500mg
            $table->string('frequence')->nullable(); // 2 fois/jour
            $table->string('duree')->nullable(); // 5 jours
            $table->text('instructions')->nullable(); // ex: à jeun
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicaments');
    }
};
