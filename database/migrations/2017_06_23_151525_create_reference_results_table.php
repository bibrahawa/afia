<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReferenceResultsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reference_results', function (Blueprint $table) {
            $table->id();
            $table->string('result');
            $table->string('flag');
            $table->foreignId('test_report_id')->constrained('test_reports')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('test_reference_id')->constrained('test_references')->onDelete('cascade')->onUpdate('cascade');
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
        Schema::dropIfExists('reference_results');
    }
}
