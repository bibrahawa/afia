<?php

namespace Database\Seeders;

use App\Models\Etablissement;
use Illuminate\Database\Seeder;

/**
 * CORRIGÉ — la table `hospitals` n'existe plus : ses informations ont été
 * reprises dans `etablissements`. Ce seeder remplit désormais la fiche de
 * l'établissement Aprosafe (en-têtes de factures, comptes rendus labo…).
 * Nom de classe conservé pour ne casser aucun appel existant.
 *
 * Ne remplace que les champs vides : une fiche modifiée depuis l'interface
 * n'est jamais écrasée.
 */
class HospitalsTableSeeder extends Seeder
{
    public function run(): void
    {
        $aprosafe = Etablissement::where('slug', 'aprosafe')->first();

        if (! $aprosafe) {
            $this->command?->warn('Établissement « aprosafe » absent : lancez EtablissementFoundationSeeder d\'abord.');

            return;
        }

        $infos = [
            'logo' => 'assets/logo/logo.png',
            'adresse' => 'Kiroti, Conakry, République de Guinée',
            'contact' => '+224 628 16 44 22',
            'email' => 'infos@cliniqueaprosafe.com',
            'site_web' => 'cliniqueaprosafe.com',
            'numero_pan' => '123',
            'numero_enregistrement' => '12345',
            'description' => "La Clinique Aprosafe est un établissement médical dédié à prendre soin de votre santé, en mettant l'accent sur la gynécologie, la santé maternelle et la planification familiale.",
            'type_taxe' => 'Health Tax',
            'taux_taxe' => 0,
            'prefixe_facture' => 'AP-',
            'prefixe_patient' => 'PA-',
            'message_facture' => 'Merci de votre confiance',
        ];

        $aRemplir = collect($infos)->filter(fn ($valeur, $champ) => blank($aprosafe->{$champ}) || in_array($aprosafe->{$champ}, ['FAC-', 'PAT-'], true)); // FAC-/PAT- = valeurs par défaut de la migration

        $aprosafe->forceFill($aRemplir->all())->save();

        $this->command?->info('Fiche Aprosafe complétée : ' . ($aRemplir->keys()->implode(', ') ?: 'rien à compléter'));
    }
}
