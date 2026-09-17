<?php

namespace App\Models\Parcours;

use App\Models\Patient;
use App\Models\User;
use App\Traits\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;

/** Constantes et paramètres mesurés avant la consultation. */
class Constante extends Model
{
    use BelongsToEtablissement;

    protected $table = 'constantes';

    protected $fillable = [
        'etablissement_id', 'patient_id', 'visite_id', 'poids_kg', 'taille_cm', 'temperature',
        'tension_systolique', 'tension_diastolique', 'pouls', 'frequence_respiratoire',
        'saturation_o2', 'glycemie', 'ddr', 'notes', 'mesure_par', 'mesure_le',
    ];

    protected $casts = [
        'poids_kg' => 'decimal:2',
        'taille_cm' => 'decimal:1',
        'temperature' => 'decimal:1',
        'glycemie' => 'decimal:2',
        'ddr' => 'date',
        'mesure_le' => 'datetime',
    ];

    public function patient() { return $this->belongsTo(Patient::class); }
    public function visite() { return $this->belongsTo(Visite::class); }
    public function auteur() { return $this->belongsTo(User::class, 'mesure_par'); }

    public function imc(): ?float
    {
        if (! $this->poids_kg || ! $this->taille_cm) {
            return null;
        }

        $metres = (float) $this->taille_cm / 100;

        return round((float) $this->poids_kg / ($metres * $metres), 1);
    }

    /** Terme de la grossesse depuis la DDR : [semaines, jours] d'aménorrhée, ou null. */
    public function terme(): ?array
    {
        if (! $this->ddr) {
            return null;
        }

        $jours = (int) $this->ddr->diffInDays($this->mesure_le ?? now(), true);

        return $jours <= 44 * 7 ? [intdiv($jours, 7), $jours % 7] : null;
    }

    /** Date prévue d'accouchement (DDR + 280 jours). */
    public function datePrevueAccouchement(): ?\Carbon\Carbon
    {
        return $this->ddr?->copy()->addDays(280);
    }

    /** Valeurs anormales à signaler en rouge au médecin. */
    public function alertes(): array
    {
        $alertes = [];

        if ($this->temperature !== null && ((float) $this->temperature >= 38 || (float) $this->temperature < 35.5)) {
            $alertes[] = 'température';
        }
        if ($this->tension_systolique && ($this->tension_systolique >= 140 || $this->tension_systolique < 90)) {
            $alertes[] = 'tension';
        }
        if ($this->tension_diastolique && $this->tension_diastolique >= 90) {
            $alertes[] = 'tension';
        }
        if ($this->saturation_o2 && $this->saturation_o2 < 94) {
            $alertes[] = 'saturation';
        }
        if ($this->glycemie !== null && ((float) $this->glycemie >= 1.26 || (float) $this->glycemie < 0.7)) {
            $alertes[] = 'glycémie';
        }
        if ($this->pouls && ($this->pouls > 100 || $this->pouls < 50)) {
            $alertes[] = 'pouls';
        }

        return array_values(array_unique($alertes));
    }
}
