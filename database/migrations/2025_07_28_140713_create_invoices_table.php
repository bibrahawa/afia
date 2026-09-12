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
        // Modification de la table invoices existante
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->onDelete('cascade');
            $table->foreignId('insurance_company_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('patient_insurance_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('total_amount', 10, 2); // S'assurer que c'est bien décimal
            $table->decimal('patient_amount', 10, 2)->default(0); // Part patient
            $table->decimal('insurance_amount', 10, 2)->default(0); // Part assurance
            $table->enum('insurance_status', ['pending', 'submitted', 'approved', 'rejected', 'paid'])->nullable();
            $table->enum('patient_amount_status', ['pending', 'rejected', 'paid'])->nullable();
            $table->date('insurance_submission_date')->nullable();
            $table->date('insurance_payment_date')->nullable();
            $table->string('insurance_claim_number')->nullable();
            $table->text('insurance_notes')->nullable();
            $table->timestamps();
        });

        // Création de la table invoice_items pour le détail
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->morphs('coverage_type');
            $table->string('description');
            $table->decimal('unit_price', 10, 2);
            $table->integer('quantity')->default(1);
            $table->decimal('total_amount', 10, 2);
            $table->decimal('insurance_covered_amount', 10, 2)->default(0);
            $table->decimal('patient_amount', 10, 2)->default(0);
            $table->decimal('coverage_percentage_applied', 5, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};
