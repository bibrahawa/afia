<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lot S1 — SMS par clinique.
 *
 * 1. etablissements.sms_expediteur : nom d'expéditeur enregistré chez Nimba
 *    pour cette clinique (11 caractères max, lettres/chiffres). Vide : « HALI ».
 * 2. sms_journal : TOUS les SMS envoyés (rappels, confirmations, codes,
 *    résultats, consultations prénatales…), par clinique, avec leur statut.
 *    Les codes de connexion y sont masqués.
 * 3. Permissions sms.journal (voir) et sms.renvoyer.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('etablissements', 'sms_expediteur')) {
            Schema::table('etablissements', function (Blueprint $table) {
                $table->string('sms_expediteur', 11)->nullable()->after('email');
            });
        }

        if (! Schema::hasTable('sms_journal')) {
            Schema::create('sms_journal', function (Blueprint $table) {
                $table->id();
                $table->foreignId('etablissement_id')->nullable()->index();
                $table->string('telephone', 30);
                $table->text('message');
                $table->string('expediteur', 11)->nullable();
                $table->string('type', 40)->default('autre')->index();
                $table->string('statut', 12)->index();          // envoye | echec
                $table->text('erreur')->nullable();
                $table->string('message_id', 100)->nullable();
                $table->nullableMorphs('sujet');                // rendez-vous, compte rendu, grossesse…
                $table->boolean('renvoyable')->default(true);   // faux pour les codes masqués
                $table->foreignId('renvoi_de_id')->nullable();
                $table->foreignId('envoye_par')->nullable();    // utilisateur, si envoi manuel
                $table->timestamps();

                $table->index(['etablissement_id', 'created_at']);
            });
        }

        // Permissions : données aux rôles d'administration et au secrétariat.
        if (Schema::hasTable('permissions')) {
            foreach (['sms.journal', 'sms.renvoyer'] as $nom) {
                \App\Models\Permission::firstOrCreate(['name' => $nom, 'guard_name' => 'web']);
            }
            foreach (['super-admin', 'admin', 'secretaire'] as $role) {
                \App\Models\Role::where('name', $role)->where('guard_name', 'web')->first()?->givePermissionTo(['sms.journal', 'sms.renvoyer']);
            }
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_journal');
        if (Schema::hasColumn('etablissements', 'sms_expediteur')) {
            Schema::table('etablissements', fn (Blueprint $table) => $table->dropColumn('sms_expediteur'));
        }
    }
};
