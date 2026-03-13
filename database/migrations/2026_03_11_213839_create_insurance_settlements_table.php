<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insurance_company_id')->constrained()->cascadeOnDelete();

            $table->string('settlement_no')->unique()->nullable();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();

            $table->decimal('gross_amount', 14, 2)->default(0);     // total dû avant remise
            $table->decimal('discount_amount', 14, 2)->default(0);  // remise globale accordée
            $table->decimal('paid_amount', 14, 2)->default(0);      // montant réellement encaissé
            $table->decimal('net_amount', 14, 2)->default(0);       // discount + paid
            $table->decimal('remaining_amount', 14, 2)->default(0); // reste non soldé dans ce settlement

            $table->string('payment_method')->nullable();           // TRANSFER, CHEQUE, CASH...
            $table->string('payment_reference')->nullable();        // référence virement / n° chèque
            $table->date('payment_date')->nullable();

            $table->enum('status', ['draft', 'partial', 'paid', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_settlements');
    }
};