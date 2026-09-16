<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un lien signé Laravel (domaine + chemin + expires + signature HMAC)
     * dépasse largement les 40-50 caractères — coûteux en SMS (chaque
     * segment de 160 caractères compte) et peu engageant à taper/lire sur
     * un téléphone d'entrée de gamme. Ce raccourcisseur maison stocke le
     * lien réel et distribue un code court à la place — pas besoin d'un
     * service tiers, la table est triviale.
     */
    public function up(): void
    {
        Schema::create('liens_courts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->text('url_cible');
            $table->timestamp('expire_le')->nullable();
            $table->timestamp('utilise_le')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liens_courts');
    }
};
