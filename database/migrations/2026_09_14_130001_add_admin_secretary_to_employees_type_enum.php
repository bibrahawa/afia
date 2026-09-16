<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La première conversion en enum (Doctor/Laboratory/Reception/
     * Pharmacy/Accountant/Nurse/Other) ne couvrait pas tous les rôles
     * réellement utilisés dans les seeders existants : 'admin' (le
     * compte système) et 'secretaire' (distinct de la réception au sens
     * strict). Plutôt que de forcer ces rôles dans une case qui ne leur
     * correspond pas vraiment, on élargit l'énumération.
     */
    public function up(): void
    {
        DB::table('employees')->where('type', 'admin')->update(['type' => 'Admin']);
        DB::table('employees')->where('type', 'medecin')->update(['type' => 'Doctor']);
        DB::table('employees')->where('type', 'comptable')->update(['type' => 'Accountant']);
        DB::table('employees')->where('type', 'secretaire')->update(['type' => 'Secretary']);

        Schema::table('employees', function ($table) {
            $table->enum('type', [
                'Doctor', 'Laboratory', 'Reception', 'Pharmacy',
                'Accountant', 'Nurse', 'Secretary', 'Admin', 'Other',
            ])->nullable()->default('Other')->change();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function ($table) {
            $table->enum('type', ['Doctor', 'Laboratory', 'Reception', 'Pharmacy', 'Accountant', 'Nurse', 'Other'])
                ->nullable()->default('Other')->change();
        });
    }
};
