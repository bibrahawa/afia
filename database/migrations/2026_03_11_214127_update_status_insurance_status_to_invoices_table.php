<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE invoices 
            MODIFY insurance_status ENUM('pending', 'submitted', 'approved', 'rejected', 'partial', 'paid') NULL
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE invoices 
            MODIFY insurance_status ENUM('pending', 'submitted', 'approved', 'rejected', 'paid') NULL
        ");
    }
};