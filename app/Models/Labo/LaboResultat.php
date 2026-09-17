<?php

namespace App\Models\Labo;

use App\Models\User;
use App\Enums\Labo\FlagResultat;
use App\Traits\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;

class LaboResultat extends Model
{
    use BelongsToEtablissement;

    protected $table = 'labo_resultats';

    protected $fillable = [
        'etablissement_id', 'demande_examen_id', 'parametre_id', 'valeur_numerique', 'valeur_texte', 'flag',
        'libelle', 'unite', 'decimales', 'norme_min', 'norme_max', 'norme_critique_min', 'norme_critique_max',
        'norme_texte', 'source', 'saisi_par', 'saisi_le',
    ];

    protected static function booted(): void
    {
        // Verrou au niveau du modèle, pas seulement de l'interface : aucun
        // chemin de code (contrôleur oublié, tinker, import) ne peut
        // modifier un résultat validé par le biologiste.
        $garde = function (self $resultat) {
            $examen = LaboDemandeExamen::withoutGlobalScope('etablissement')->find($resultat->demande_examen_id);
            if ($examen && $examen->estVerrouille()) {
                throw new \LogicException('Résultat validé par le biologiste : rouvrez l\'examen pour rectification.');
            }
        };

        static::updating($garde);
        static::deleting($garde);
    }

    protected $casts = [
        'flag' => FlagResultat::class,
        'valeur_numerique' => 'float',
        'norme_min' => 'float', 'norme_max' => 'float',
        'norme_critique_min' => 'float', 'norme_critique_max' => 'float',
        'saisi_le' => 'datetime',
    ];

    public function demandeExamen()
    {
        return $this->belongsTo(LaboDemandeExamen::class, 'demande_examen_id');
    }

    public function parametre()
    {
        return $this->belongsTo(LaboParametre::class, 'parametre_id');
    }

    public function historiques()
    {
        return $this->hasMany(LaboResultatHistorique::class, 'resultat_id')->latest('created_at');
    }

    public function alertes()
    {
        return $this->hasMany(LaboAlerteCritique::class, 'resultat_id');
    }

    public function saisiPar()
    {
        return $this->belongsTo(User::class, 'saisi_par');
    }

    public function aUneValeur(): bool
    {
        return $this->valeur_numerique !== null || ($this->valeur_texte !== null && trim($this->valeur_texte) !== '');
    }

    /**
     * Le texte saisi prime quand il existe : « < 0,10 » doit s'imprimer tel
     * quel, même si 0,10 est conservé en numérique pour le calcul du flag.
     */
    public function valeurAffichee(): string
    {
        if ($this->valeur_texte !== null && trim($this->valeur_texte) !== '') {
            return $this->valeur_texte;
        }

        return self::formaterNombre($this->valeur_numerique, $this->decimales);
    }

    public function normeAffichee(): string
    {
        if ($this->norme_texte) {
            return $this->norme_texte;
        }
        if ($this->norme_min !== null && $this->norme_max !== null) {
            return self::formaterNombre($this->norme_min, $this->decimales) . ' – ' . self::formaterNombre($this->norme_max, $this->decimales);
        }
        if ($this->norme_max !== null) {
            return '< ' . self::formaterNombre($this->norme_max, $this->decimales);
        }
        if ($this->norme_min !== null) {
            return '> ' . self::formaterNombre($this->norme_min, $this->decimales);
        }

        return '';
    }

    /** Format français : virgule décimale, espace pour les milliers. */
    public static function formaterNombre(?float $valeur, int $decimales = 1): string
    {
        return $valeur === null ? '' : number_format($valeur, $decimales, ',', ' ');
    }

    /** Représentation texte pour l'historique des modifications. */
    public function valeurBrute(): ?string
    {
        return $this->valeur_numerique !== null ? (string) $this->valeur_numerique : $this->valeur_texte;
    }
}
