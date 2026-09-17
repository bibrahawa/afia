<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Un patient pris en charge (consultation, hospitalisation…) devient un
 * patient « suivi » par l'établissement : il apparaît dans ses listes, et
 * seulement dans les siennes (voir Patient::scopeSuivisParEtablissement).
 */
trait RattachePatientEtablissement
{
    protected static function bootRattachePatientEtablissement(): void
    {
        static::created(function (Model $model) {
            if (empty($model->patient_id) || empty($model->etablissement_id)) {
                return;
            }

            $maintenant = now();
            DB::table('etablissement_patient')->upsert(
                [[
                    'etablissement_id' => $model->etablissement_id,
                    'patient_id' => $model->patient_id,
                    'premiere_visite_le' => $maintenant,
                    'derniere_visite_le' => $maintenant,
                    'created_at' => $maintenant,
                    'updated_at' => $maintenant,
                ]],
                ['etablissement_id', 'patient_id'],
                ['derniere_visite_le', 'updated_at'] // premiere_visite_le conservée
            );
        });
    }
}
