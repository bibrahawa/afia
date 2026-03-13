<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_settlement_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('insurance_settlement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();

            $table->decimal('invoice_amount', 14, 2)->default(0);           // montant assurance théorique de la facture
            $table->decimal('already_settled_amount', 14, 2)->default(0);   // déjà soldé avant ce settlement
            $table->decimal('remaining_before', 14, 2)->default(0);         // reste avant ce settlement

            $table->decimal('applied_discount_amount', 14, 2)->default(0);  // remise imputée à cette facture
            $table->decimal('applied_paid_amount', 14, 2)->default(0);      // paiement imputé à cette facture

            $table->decimal('settled_amount', 14, 2)->default(0);           // discount + paid
            $table->decimal('remaining_after', 14, 2)->default(0);          // reste après ce settlement

            $table->timestamps();

            $table->index(['invoice_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_settlement_items');
    }
};