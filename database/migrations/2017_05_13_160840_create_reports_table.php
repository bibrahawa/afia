<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade')->onUpdate('cascade');
            $table->integer('doctor_id')->nullable();
            $table->string('report')->nullable();
            $table->string('result')->nullable();
            $table->boolean('status')->default(0);
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
        Schema::dropIfExists('reports');
        $table->dropForeign('reports_lab_id_foreign');
        $table->dropForeign('reports_patient_id_foreign');

    }
}
