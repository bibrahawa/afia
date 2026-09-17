<?php

namespace App\Services\Assurance;

use App\Exceptions\Assurance\OperationAssuranceImpossible;
use App\Models\Assurance\Bordereau;
use App\Models\InsuranceClaim;
use App\Models\InsuranceCompany;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Bordereaux d'envoi : regrouper les réclamations d'un payeur sur une
 * période, vérifier, imprimer, envoyer. Une réclamation envoyée fige la
 * facture (FigementFacture) : l'assureur et la clinique ont la même version.
 */
class BordereauService
{
    public function creer(InsuranceCompany $organisme, ?Carbon $debut, ?Carbon $fin, ?string $notes = null, ?int $auteurId = null): Bordereau
    {
        return DB::transaction(function () use ($organisme, $debut, $fin, $notes, $auteurId) {
            $reclamations = $this->reclamationsAEnvoyer($organisme, $debut, $fin)->lockForUpdate()->get();

            if ($reclamations->isEmpty()) {
                throw new OperationAssuranceImpossible("Aucune réclamation à envoyer pour {$organisme->name} sur cette période.");
            }

            $bordereau = Bordereau::create([
                'insurance_company_id' => $organisme->id,
                'periode_debut' => $debut?->toDateString(),
                'periode_fin' => $fin?->toDateString(),
                'statut' => Bordereau::BROUILLON,
                'notes' => $notes,
                'cree_par' => $auteurId,
            ]);

            InsuranceClaim::whereIn('id', $reclamations->pluck('id'))->update(['bordereau_id' => $bordereau->id]);

            return $bordereau;
        });
    }

    /** Réclamations brouillon du payeur, hors bordereau, dont la facture est dans la période. */
    public function reclamationsAEnvoyer(InsuranceCompany $organisme, ?Carbon $debut, ?Carbon $fin)
    {
        return InsuranceClaim::query()
            ->where('insurance_company_id', $organisme->id)
            ->where('status', 'draft')
            ->whereNull('bordereau_id')
            ->where('claimed_amount', '>', 0)
            ->whereHas('invoice', function ($q) use ($debut, $fin) {
                $q->when($debut, fn ($w) => $w->whereDate('created_at', '>=', $debut))
                  ->when($fin, fn ($w) => $w->whereDate('created_at', '<=', $fin));
            });
    }

    public function retirer(Bordereau $bordereau, InsuranceClaim $reclamation): void
    {
        $this->exigerBrouillon($bordereau);

        if ((int) $reclamation->bordereau_id !== (int) $bordereau->id) {
            throw new OperationAssuranceImpossible('Cette réclamation ne fait pas partie du bordereau.');
        }

        $reclamation->update(['bordereau_id' => null]);
    }

    public function envoyer(Bordereau $bordereau, Carbon $dateEnvoi): Bordereau
    {
        $this->exigerBrouillon($bordereau);

        if (! $bordereau->reclamations()->exists()) {
            throw new OperationAssuranceImpossible('Le bordereau est vide.');
        }

        return DB::transaction(function () use ($bordereau, $dateEnvoi) {
            $bordereau->update(['statut' => Bordereau::ENVOYE, 'date_envoi' => $dateEnvoi->toDateString()]);

            $bordereau->reclamations()->where('status', 'draft')->update([
                'status' => 'submitted',
                'submission_date' => $dateEnvoi->toDateString(),
            ]);

            return $bordereau->fresh();
        });
    }

    /** Correction avant toute réponse ou règlement : le bordereau redevient modifiable. */
    public function rouvrir(Bordereau $bordereau): Bordereau
    {
        if ($bordereau->estBrouillon()) {
            return $bordereau;
        }

        $traitee = $bordereau->reclamations()
            ->where(fn ($q) => $q->whereNotNull('approved_amount')->orWhereHas('settlementItems'))
            ->exists();

        if ($traitee) {
            throw new OperationAssuranceImpossible('L\'assureur a déjà répondu ou réglé une réclamation de ce bordereau : il ne peut plus être rouvert.');
        }

        return DB::transaction(function () use ($bordereau) {
            $bordereau->reclamations()->update(['status' => 'draft', 'submission_date' => null]);
            $bordereau->update(['statut' => Bordereau::BROUILLON, 'date_envoi' => null]);

            return $bordereau->fresh();
        });
    }

    private function exigerBrouillon(Bordereau $bordereau): void
    {
        if (! $bordereau->estBrouillon()) {
            throw new OperationAssuranceImpossible('Ce bordereau a déjà été envoyé. Rouvrez-le pour le modifier.');
        }
    }
}
