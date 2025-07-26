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
        Schema::create('package_services', function (Blueprint $table) {
            $table->id();
            $table->integer('package_id')->unsigned();
            $table->integer('service_id')->unsigned();
            $table->timestamps();
        });

        Schema::create('package_tests', function (Blueprint $table) {
            $table->id();
            $table->integer('package_id')->unsigned();
            $table->integer('test_id')->unsigned();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_services');
        Schema::dropIfExists('package_tests');
    }
};
