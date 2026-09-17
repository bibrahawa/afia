<?php

namespace App\Policies;

use App\Models\User;
use App\Support\EtablissementContext;
use Illuminate\Database\Eloquent\Model;

/**
 * Policy commune à tous les modèles cloisonnés : une fiche n'est
 * consultable, modifiable ou supprimable que par l'établissement qui la
 * possède (ou par l'administrateur plateforme).
 *
 * Elle ne remplace pas les permissions métier (@can('consultation.edit'),
 * middleware can:…) : elle s'ajoute à elles. Usage dans un contrôleur :
 *
 *   $this->authorize('update', $consultation);
 *   Gate::authorize('delete', $chambre);
 */
class EtablissementPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, Model $model): bool
    {
        return $this->memeEtablissement($user, $model);
    }

    public function update(User $user, Model $model): bool
    {
        return $this->memeEtablissement($user, $model);
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->memeEtablissement($user, $model);
    }

    private function memeEtablissement(User $user, Model $model): bool
    {
        if (EtablissementContext::estAdministrateurPlateforme($user)) {
            return true;
        }

        $courant = EtablissementContext::id();

        return $courant !== null && (int) $model->etablissement_id === (int) $courant;
    }
}
