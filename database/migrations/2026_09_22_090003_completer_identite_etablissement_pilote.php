<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Les documents lisent désormais l'identité de l'établissement courant
 * (IdentiteDocument) au lieu de valeurs écrites en dur. Pour que les
 * documents d'Aprosafe restent identiques au lendemain du déploiement, on
 * reporte dans SA fiche les valeurs qui étaient codées dans les vues —
 * uniquement pour les champs encore vides.
 */
return new class extends Migration
{
    private const PILOTE = [
        'adresse' => 'Kiroti, Conakry, Rep de Guinee',
        'contact' => '628 16 44 22 / 625 47 68 44',
        'email' => 'boubacarbinta2015@gmail.com',
        'logo' => 'assets/img/aprosafe.png',
    ];

    public function up(): void
    {
        $pilote = DB::table('etablissements')->where('slug', 'aprosafe')->first();

        if (! $pilote) {
            return;
        }

        $valeurs = collect(self::PILOTE)
            ->filter(fn ($valeur, $colonne) => blank($pilote->{$colonne} ?? null))
            ->all();

        if ($valeurs) {
            DB::table('etablissements')->where('id', $pilote->id)->update($valeurs + ['updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Rien : on ne sait plus si la valeur a été saisie depuis dans la fiche.
    }
};
