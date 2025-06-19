<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePackageSalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('package_sales', function (Blueprint $table) {
            $table->id();
            $table->float('package_price');
            $table->foreignId('patient_id')->constrained()->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('package_id')->constrained()->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade')->onUpdate('cascade');
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
        Schema::dropIfExists('package_sales');
    }
}
