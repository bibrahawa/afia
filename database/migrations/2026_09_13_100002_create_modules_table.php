<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catalogue des modules activables par établissement (rdv, laboratoire,
     * pharmacie, hospitalisation...). C'est une table de référence, gérée
     * par la plateforme, pas par les clients.
     *
     * `code` est ce que le code applicatif utilise (middleware, helpers de
     * menu) — il ne doit jamais changer une fois utilisé en prod.
     */
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('nom');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
