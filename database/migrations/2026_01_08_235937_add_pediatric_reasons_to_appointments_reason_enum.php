<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Pour MySQL/MariaDB
        DB::statement("ALTER TABLE appointments MODIFY COLUMN reason ENUM(
            'consultation_gynecologie',
            'consultation_desir_maternite',
            'cpn',
            'echographie_gynecologique',
            'echographie_obstetricale',
            'interpretation_resultats',
            'monnitoring_ovulation',
            'pose_sterilet_gynecologie',
            'pose_implant',
            'consultation_immunologie',
            'consultation_nutrition_obesite',
            'consultation_hematologie',
            'consultation_drepanocytose',
            'autre_pediatre',
            'autre'
        ) NOT NULL");
    }

    public function down(): void
    {
        // Vérifier d'abord si des données utilisent les nouvelles valeurs
        $count = DB::table('appointments')
            ->whereIn('reason', [
                'consultation_immunologie',
                'consultation_nutrition_obesite',
                'consultation_hematologie',
                'consultation_drepanocytose',
                'autre_pediatre'
            ])
            ->count();
            
        if ($count > 0) {
            throw new \Exception("Impossible de rollback : {$count} enregistrements utilisent les nouvelles valeurs ENUM");
        }

        DB::statement("ALTER TABLE appointments MODIFY COLUMN reason ENUM(
            'consultation_gynecologie',
            'consultation_desir_maternite',
            'cpn',
            'echographie_gynecologique',
            'echographie_obstetricale',
            'interpretation_resultats',
            'monnitoring_ovulation',
            'pose_sterilet_gynecologie',
            'pose_implant',
            'autre'
        ) NOT NULL");
    }
};