<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvoiceReturnsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoice_returns', function (Blueprint $table) {
            $table->id();
            $table->float('return_amount');
            $table->string('return_reason')->nullable();
            $table->boolean('status')->default(1);
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Utilisateur qui a enregistré le paiement
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade')->onUpdate('cascade');
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invoice_returns');
    }
}
