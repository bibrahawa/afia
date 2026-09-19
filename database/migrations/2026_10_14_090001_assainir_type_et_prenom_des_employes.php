<?php

use App\Models\Employee;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Remet d'aplomb les fiches employés existantes.
 *
 * 1. Type : les écrans enregistraient des valeurs refusées par la colonne ENUM
 *    (« Docteur », « medecin »…). Selon le mode SQL, la fiche était refusée ou
 *    son type vidé : le médecin n'apparaissait dans aucune liste de médecins.
 *    Pour chaque fiche au type vide ou « Other » reliée à un compte, on reprend
 *    le rôle du compte (medecin → Doctor, secretaire → Secretary…).
 * 2. Prénom : « DR. », « DR.DR. », « Dr » ajoutés au prénom à chaque
 *    enregistrement. Le titre est retiré : l'affichage l'ajoute (nom_affiche).
 *
 * Idempotente : la relancer ne change plus rien.
 */
return new class extends Migration
{
    public function up(): void
    {
        // --- 1. Type repris du rôle du compte (tables Spatie)
        if (DB::getSchemaBuilder()->hasTable('model_has_roles')) {
            $roles = DB::table('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where('model_has_roles.model_type', \App\Models\User::class)
                ->pluck('roles.name', 'model_has_roles.model_id');

            DB::table('employees')
                ->whereNotNull('user_id')
                ->where(fn ($q) => $q->whereNull('type')->orWhere('type', '')->orWhere('type', 'Other'))
                ->orderBy('id')
                ->each(function ($employe) use ($roles) {
                    $role = $roles[$employe->user_id] ?? null;
                    $type = $role ? Employee::normaliserType($role) : null;
                    if ($type && $type !== 'Other') {
                        DB::table('employees')->where('id', $employe->id)->update(['type' => $type]);
                    }
                });
        }

        // --- 2. Titre retiré du prénom
        DB::table('employees')
            ->where(fn ($q) => $q->where('first_name', 'like', 'dr %')->orWhere('first_name', 'like', 'dr.%')->orWhere('first_name', 'like', 'docteur %'))
            ->orderBy('id')
            ->each(function ($employe) {
                $propre = trim(preg_replace('/^\s*(?:(?:docteur|dr)\s*\.\s*|(?:docteur|dr)\s+)+/iu', '', $employe->first_name));
                if ($propre !== '' && $propre !== $employe->first_name) {
                    DB::table('employees')->where('id', $employe->id)->update(['first_name' => $propre]);
                }
            });
    }

    public function down(): void
    {
        // Rien à défaire : les anciennes valeurs étaient erronées.
    }
};
