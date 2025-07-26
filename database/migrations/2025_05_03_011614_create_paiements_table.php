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
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->string('paiement_no')->unique()->nullable();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Utilisateur qui a enregistré le paiement
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->string('source')->nullable(); // espèce, mobile money, virement, etc.
            $table->string('description')->nullable(); // ex: Paiement partiel, Paiement médicament, etc.
            $table->enum('type', ['remboursement', 'remise', 'paiement'])->default('paiement');
            $table->float('montant');
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};
