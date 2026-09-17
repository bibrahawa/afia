<?php

namespace App\Models\Parcours;

use App\Models\Consultation;
use App\Models\Employee;
use App\Models\Patient;
use App\Models\User;
use App\Traits\BelongsToEtablissement;
use App\Traits\HeriteEtablissement;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Suivi de grossesse : terme, date prévue d'accouchement, consultations
 * prénatales. Tout se déduit de la DDR — rien à ressaisir à chaque visite.
 */
class Grossesse extends Model
{
    use HeriteEtablissement, BelongsToEtablissement;

    public const DUREE_JOURS = 280; // 40 SA
    public const EN_COURS = 'en_cours';
    public const TERMINEE = 'terminee';
    public const INTERROMPUE = 'interrompue';

    /** Contacts recommandés par l'OMS, en semaines d'aménorrhée. */
    public const CALENDRIER_CPN = [12, 20, 26, 30, 34, 36, 38, 40];

    public const ISSUES = [
        'accouchement' => 'Accouchement',
        'fausse_couche' => 'Fausse couche',
        'interruption' => 'Interruption',
        'transfert' => 'Suivi transféré',
    ];

    // Le patient n'appartient à aucun établissement (table de liaison) : l'établissement
    // vient du contexte, et à défaut du médecin qui suit la grossesse.
    protected static array $etablissementDepuis = ['medecin_id' => Employee::class];

    protected $table = 'grossesses';

    protected $fillable = [
        'etablissement_id', 'patient_id', 'medecin_id', 'ddr', 'dpa', 'gestite', 'parite',
        'statut', 'date_issue', 'issue', 'notes', 'ouverte_par',
    ];

    protected $casts = ['ddr' => 'date', 'dpa' => 'date', 'date_issue' => 'date'];

    public function patient() { return $this->belongsTo(Patient::class); }
    public function medecin() { return $this->belongsTo(Employee::class, 'medecin_id'); }
    public function auteur() { return $this->belongsTo(User::class, 'ouverte_par'); }

    public function consultations()
    {
        return $this->hasMany(Consultation::class)->orderBy('created_at');
    }

    public function estEnCours(): bool
    {
        return $this->statut === self::EN_COURS;
    }

    /** [semaines, jours] d'aménorrhée à une date, ou null hors grossesse plausible. */
    public function terme(?Carbon $date = null): ?array
    {
        $date = $date ?? ($this->date_issue ?? today());
        $jours = (int) $this->ddr->diffInDays($date, true);

        if ($date->lt($this->ddr) || $jours > 44 * 7) {
            return null;
        }

        return [intdiv($jours, 7), $jours % 7];
    }

    public function termeLisible(?Carbon $date = null): string
    {
        $terme = $this->terme($date);

        return $terme ? "{$terme[0]} SA {$terme[1]} j" : '—';
    }

    /**
     * Calendrier des consultations prénatales : date cible de chaque contact,
     * et la consultation qui l'a honoré le cas échéant.
     */
    public function calendrier(): array
    {
        $consultations = $this->consultations()->get();

        return collect(self::CALENDRIER_CPN)->map(function (int $semaines) use ($consultations) {
            $cible = $this->ddr->copy()->addWeeks($semaines);
            $faite = $consultations->first(fn (Consultation $c) => $c->created_at->between($cible->copy()->subWeeks(2), $cible->copy()->addWeeks(2)));

            return [
                'semaines' => $semaines,
                'date_cible' => $cible,
                'consultation' => $faite,
                'statut' => match (true) {
                    $faite !== null => 'faite',
                    $cible->isFuture() => 'a_venir',
                    $cible->diffInDays(today()) <= 14 => 'a_programmer',
                    default => 'manquee',
                },
            ];
        })->all();
    }

    /** Prochain contact à programmer, ou null si le calendrier est épuisé. */
    public function prochainContact(): ?array
    {
        return collect($this->calendrier())->first(fn ($c) => in_array($c['statut'], ['a_programmer', 'a_venir'], true));
    }

    public static function dpaDepuis(Carbon $ddr): Carbon
    {
        return $ddr->copy()->addDays(self::DUREE_JOURS);
    }
}
