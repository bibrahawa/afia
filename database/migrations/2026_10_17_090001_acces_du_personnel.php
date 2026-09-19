<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lot E1 — Fiche employé et accès fusionnés.
 *
 * 1. users.doit_changer_mot_de_passe : vrai après un mot de passe provisoire
 *    (création d'accès, réinitialisation) ; la personne doit le changer avant
 *    d'utiliser l'application.
 * 2. users.email facultatif : la connexion se fait par téléphone ; beaucoup de
 *    soignants n'ont pas d'adresse e-mail. Reste unique quand il est renseigné.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'doit_changer_mot_de_passe')) {
                $table->boolean('doit_changer_mot_de_passe')->default(false)->after('password');
            }
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'doit_changer_mot_de_passe')) {
                $table->dropColumn('doit_changer_mot_de_passe');
            }
        });
    }
};
