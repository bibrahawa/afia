<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTestReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('test_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_type');
            $table->boolean('sample')->default(0);
            $table->boolean('status')->default(0);
            $table->foreignId('test_id')->constrained()->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('report_id')->constrained()->onDelete('cascade')->onUpdate('cascade');
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
        Schema::dropIfExists('test_reports');
        $table->dropForeign('test_reports_test_id_foreign');
        $table->dropForeign('test_reports_report_id_foreign');
    }
}
