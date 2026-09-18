<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Etablissement extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nom', 'slug', 'type', 'statut',
        'logo', 'adresse', 'contact', 'email', 'site_web', 'description',
        'prefixe_facture', 'prefixe_patient', 'type_taxe', 'taux_taxe', 'message_facture',
        'numero_pan', 'numero_enregistrement',
        'date_debut_contrat', 'date_fin_contrat',
        'ordre_file',
    ];

    protected $casts = [
        'date_debut_contrat' => 'date',
        'date_fin_contrat' => 'date',
    ];

    /**
     * CORRECTIF DU BUG DE LIAISON DE ROUTE : sans ceci, Laravel résout
     * `{etablissement}` par sa clé primaire (id) par défaut, aussi bien
     * dans les URLs générées par route() que dans le model binding
     * implicite — ce qui contredisait `{etablissement:slug}` utilisé
     * ailleurs et provoquait exactement le bug observé (slug vide dans
     * la vue). Avec ceci, `route('rdv', $etablissement)` et le binding de
     * route utilisent tous les deux le slug, de façon cohérente partout.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function modules()
    {
        return $this->belongsToMany(Module::class)
            ->withPivot(['est_actif', 'active_depuis', 'desactive_le'])
            ->withTimestamps();
    }

    public function utilisateurs()
    {
        return $this->hasMany(User::class);
    }

    public function patients()
    {
        return $this->belongsToMany(Patient::class, 'etablissement_patient')
            ->withPivot(['premiere_visite_le', 'derniere_visite_le'])
            ->withTimestamps();
    }

    /** Codes des modules actifs, chargés une seule fois par requête. */
    private ?array $modulesActifs = null;

    public function aModule(string $code): bool
    {
        // Mémorisé : la barre de menu pose la question une dizaine de fois par page.
        $this->modulesActifs ??= $this->modules()
            ->wherePivot('est_actif', true)
            ->pluck('code')
            ->all();

        return in_array($code, $this->modulesActifs, true);
    }
}
