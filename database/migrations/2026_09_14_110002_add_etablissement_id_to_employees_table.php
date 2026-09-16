<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `employees` hérite aujourd'hui son tenant implicitement via
     * `department_id` — trop indirect pour une isolation fiable (voir le
     * principe de défense en profondeur posé au Pilier A : ne jamais faire
     * reposer un filtre de sécurité sur une seule jointure). On le rend
     * explicite.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('etablissement_id')->nullable()->after('id')
                ->constrained('etablissements')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('etablissement_id');
        });
    }
};
