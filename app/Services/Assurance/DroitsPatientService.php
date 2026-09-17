<?php

namespace App\Services\Assurance;

use App\Enums\Assurance\FamilleActe;
use App\Models\Assurance\PriseEnCharge;
use App\Models\Patient;
use App\Models\PatientInsurance;
use App\Support\Assurance\Couverture;
use Carbon\Carbon;

/**
 * Vérification des droits à l'accueil : ce qui est couvert AUJOURD'HUI, dans
 * quel ordre, à quel taux par famille d'actes, ce qu'il reste sur les
 * plafonds, les bons valides — et pourquoi une couverture enregistrée ne
 * s'applique pas (carence, fin de droits, suspension).
 */
class DroitsPatientService
{
    public function __construct(private CouverturesApplicables $couvertures)
    {
    }

    public function resume(Patient $patient, ?Carbon $date = null): array
    {
        $date ??= today();
        $actives = $this->couvertures->pour($patient->id, $date);

        $lignes = $actives->values()->map(function (Couverture $c, int $index) use ($date) {
            $exercice = $c->exercice($date);
            $consomme = $this->couvertures->consomme([$c->projection->id], $exercice);
            $plafondB = $c->plafondBeneficiaire();
            $plafondF = $c->plafondFamille();
            $consommeF = $plafondF !== null ? $this->couvertures->consomme($this->couvertures->projectionsFamille($c), $exercice) : null;

            return [
                'rang' => $index + 1,
                'couverture' => $c,
                'exercice' => $exercice,
                'plafond_beneficiaire' => $plafondB,
                'reste_beneficiaire' => $plafondB !== null ? max(0.0, round($plafondB - $consomme, 2)) : null,
                'plafond_famille' => $plafondF,
                'reste_famille' => $plafondF !== null ? max(0.0, round($plafondF - $consommeF, 2)) : null,
                'consomme' => $consomme,
                'garanties' => collect(FamilleActe::cases())->map(fn (FamilleActe $f) => [
                    'famille' => $f,
                    'taux' => $c->taux($f),
                    'exclu' => $c->exclu($f),
                    'accord_prealable' => $c->accordPrealableRequis($f),
                    'plafond_par_acte' => $c->plafondParActe($f),
                ])->all(),
                'bons' => $c->beneficiaire
                    ? PriseEnCharge::where('beneficiaire_id', $c->beneficiaire->id)->validesLe($date)->orderBy('date_fin')->get()
                    : collect(),
            ];
        })->all();

        $idsActifs = $actives->map(fn (Couverture $c) => $c->projection->id)->all();

        $inactives = PatientInsurance::where('patient_id', $patient->id)
            ->whereNotIn('id', $idsActifs)
            ->with(['insuranceCompany', 'beneficiaire.adhesion.patient'])
            ->orderByDesc('start_date')
            ->get()
            ->map(fn (PatientInsurance $p) => [
                'ligne' => $p,
                'raison' => match (true) {
                    $p->status === 'suspended' => 'suspendue',
                    $p->status === 'expired' => 'résiliée',
                    $p->start_date && $p->start_date->gt($date) => 'couverte à partir du ' . $p->start_date->format('d/m/Y') . ' (délai de carence ou date de début)',
                    $p->end_date && $p->end_date->lt($date) => 'droits terminés le ' . $p->end_date->format('d/m/Y'),
                    default => 'non applicable',
                },
            ])
            ->all();

        return ['date' => $date, 'actives' => $lignes, 'inactives' => $inactives];
    }
}
