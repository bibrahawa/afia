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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no')->unique()->nullable();
            $table->morphs('transactionable'); // transactionable_id, transactionable_type
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Utilisateur qui a enregistré le paiement
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->string('description')->nullable(); // ex: Paiement partiel, Paiement médicament, etc.
            $table->float('sub_total'); // avant taxes & remises
            $table->float('tax_amount')->default(0); // TVA ou taxe locale
            $table->float('discount')->default(0); // Réduction appliquée
            $table->float('montant_payer')->default(0);
            $table->float('total');
            $table->enum('status', ['pending', 'partial', 'paid', 'cancel'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
