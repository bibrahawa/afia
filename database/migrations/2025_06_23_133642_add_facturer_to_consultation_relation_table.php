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
        Schema::table('consultation_medicament', function (Blueprint $table) {
            $table->boolean('facturer')->default(false)->after('medicament_id')->comment('Indicates if the medicament is to be billed');
        });

        Schema::table('consultation_package', function (Blueprint $table) {
            $table->boolean('facturer')->default(false)->after('package_id')->comment('Indicates if the package is to be billed');
        });

        Schema::table('consultation_service', function (Blueprint $table) {
            $table->boolean('facturer')->default(false)->after('service_id')->comment('Indicates if the service is to be billed');
        });

        Schema::table('consultation_test', function (Blueprint $table) {
            $table->boolean('facturer')->default(false)->after('test_id')->comment('Indicates if the examen is to be billed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultation_medicament', function (Blueprint $table) {
            $table->dropColumn('facturer');
        });

        Schema::table('consultation_package', function (Blueprint $table) {
            $table->dropColumn('facturer');
        });

        Schema::table('consultation_service', function (Blueprint $table) {
            $table->dropColumn('facturer');
        });

        Schema::table('consultation_test', function (Blueprint $table) {
            $table->dropColumn('facturer');
        });
    }
};
