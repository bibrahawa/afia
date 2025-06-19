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
        Schema::create('examination_orders', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('consultation_id');
            // ->constrained()->onDelete('cascade');
            $table->string('examin_name');
            $table->json('examin_lists')->nullable();
            $table->text('clinical_notes')->nullable();
            $table->string('status')->default('done'); // pending, done, cancelled
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('examination_orders');
    }
};
