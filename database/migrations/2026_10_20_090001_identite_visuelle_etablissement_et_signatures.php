<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Identité visuelle de la clinique et signatures.
 *  - etablissements.logo (existant) : logo de l'application ;
 *  - logo_documents : logo des documents imprimés (sinon le logo de l'application) ;
 *  - signature, cachet : images PRIVÉES (disque « local »), apposées sur factures, reçus, rapports ;
 *  - signataire_nom, signataire_fonction : sous la signature ;
 *  - employees.signature : signature propre à un médecin, sur SES ordonnances et certificats.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('etablissements', function (Blueprint $table) {
            foreach (['logo_documents', 'signature', 'cachet', 'signataire_nom', 'signataire_fonction'] as $colonne) {
                if (! Schema::hasColumn('etablissements', $colonne)) {
                    $table->string($colonne)->nullable();
                }
            }
        });

        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'signature')) {
                $table->string('signature')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('etablissements', function (Blueprint $table) {
            foreach (['logo_documents', 'signature', 'cachet', 'signataire_nom', 'signataire_fonction'] as $colonne) {
                if (Schema::hasColumn('etablissements', $colonne)) {
                    $table->dropColumn($colonne);
                }
            }
        });
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'signature')) {
                $table->dropColumn('signature');
            }
        });
    }
};
