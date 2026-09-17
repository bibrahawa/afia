<?php

namespace App\Providers;

use App\Policies\EtablissementPolicy;
use App\Support\EtablissementContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

/**
 * Cloisonnement multi-établissements — tout ce qui ne vit pas dans les modèles.
 *
 * Enregistrement : bootstrap/providers.php
 *   App\Providers\EtablissementServiceProvider::class,
 */
class EtablissementServiceProvider extends ServiceProvider
{
    /** Modèles protégés par EtablissementPolicy (niveau 2). */
    public const MODELES_CLOISONNES = [
        \App\Models\Consultation::class,
        \App\Models\Hospitalisation::class,
        \App\Models\Chambre::class,
        \App\Models\Antecedent::class,
        \App\Models\FichierPatient::class,
        \App\Models\Service::class,
        \App\Models\Test::class,
        \App\Models\Package::class,
        \App\Models\Medicament::class,
        \App\Models\InsuranceCompany::class,
        \App\Models\InsuranceCoverage::class,
        \App\Models\PatientInsurance::class,
        \App\Models\InsuranceClaim::class,
        \App\Models\InsuranceSettlement::class,
        \App\Models\Transaction::class,
        \App\Models\Invoice::class,
        \App\Models\Paiement::class,
        \App\Models\Appointment::class,
        \App\Models\Employee::class,
        \App\Models\Department::class,
        \App\Models\MotifRdv::class,
    ];

    public function boot(): void
    {
        foreach (self::MODELES_CLOISONNES as $modele) {
            Gate::policy($modele, EtablissementPolicy::class);
        }

        $this->enregistrerReglesValidation();
    }

    /**
     * `exists:` et `unique:` interrogent la base SANS Eloquent : le filtre
     * par établissement ne s'y applique pas. Sans ces règles, un formulaire
     * trafiqué pourrait rattacher une hospitalisation à la chambre d'une
     * autre clinique, ou un code d'assurance serait « déjà pris » par un
     * autre établissement.
     *
     *   'chambre_id' => 'required|exists_etablissement:chambres,id'
     *   'numero'     => 'required|unique_etablissement:chambres,numero,' . $chambre->id
     */
    private function enregistrerReglesValidation(): void
    {
        Validator::extend('exists_etablissement', function ($attribut, $valeur, $parametres) {
            [$table, $colonne] = [$parametres[0], $parametres[1] ?? 'id'];
            $requete = DB::table($table)->where($colonne, $valeur);

            return $this->filtrer($requete, $table)?->exists() ?? false;
        }, 'La valeur sélectionnée pour :attribute est invalide.');

        Validator::extend('unique_etablissement', function ($attribut, $valeur, $parametres) {
            [$table, $colonne] = [$parametres[0], $parametres[1] ?? $attribut];
            $ignorer = $parametres[2] ?? null;

            $requete = DB::table($table)->where($colonne, $valeur)
                ->when($ignorer !== null && $ignorer !== 'NULL', fn ($q) => $q->where('id', '!=', $ignorer));

            $requete = $this->filtrer($requete, $table);

            return $requete !== null && ! $requete->exists();
        }, 'La valeur :attribute est déjà utilisée dans votre établissement.');
    }

    /** null = aucun établissement courant et pas administrateur plateforme : on refuse. */
    private function filtrer($requete, string $table)
    {
        if ($id = EtablissementContext::id()) {
            return $requete->where("{$table}.etablissement_id", $id);
        }

        return EtablissementContext::estAdministrateurPlateforme() || app()->runningInConsole() ? $requete : null;
    }
}
