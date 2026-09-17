<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\InsuranceCoverage;
use App\Support\Facturation\TypesFacturables;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConsultationService
{
    /**
     * Rattache à la consultation les actes sélectionnés à l'écran.
     *
     * CORRIGÉ 22/09/2026 : les identifiants venaient d'un champ JSON et
     * étaient synchronisés sans contrôle — on pouvait rattacher (et donc
     * facturer) le service, l'examen ou le médicament d'une AUTRE clinique.
     * Chaque identifiant est maintenant vérifié via le modèle cloisonné.
     */
    public function attachItems(Consultation $consultation, array $selectedItems): void
    {
        $modeles = [
            'medicaments' => \App\Models\Medicament::class,
            'services' => \App\Models\Service::class,
            'packages' => \App\Models\Package::class,
            'examens' => \App\Models\Test::class,
        ];

        foreach ($selectedItems as $category => $items) {
            $modele = $modeles[$category] ?? null;
            if (! $modele || ! is_array($items)) {
                continue;
            }

            $ids = collect($items)->pluck('id')->filter()->map(fn ($id) => (int) $id)->unique()->values();

            // Global scope BelongsToEtablissement : seuls les actes de l'établissement courant sont trouvés.
            $valides = $modele::whereIn('id', $ids)->pluck('id')->map(fn ($id) => (int) $id);

            if ($ids->diff($valides)->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'selected_items' => "Un ou plusieurs actes sélectionnés n'existent pas dans cet établissement.",
                ]);
            }

            $syncValues = [];

            foreach ($items as $item) {
                if (empty($item['id'])) {
                    continue;
                }

                if ($category === 'medicaments') {
                    $syncValues[(int) $item['id']] = [
                        'quantity' => max(1, (int) ($item['quantity'] ?? 1)),
                    ];
                } else {
                    $syncValues[] = (int) $item['id'];
                }
            }

            match ($category) {
                'medicaments' => $consultation->medicaments()->sync($syncValues),
                'services'    => $consultation->services()->sync($syncValues),
                'packages'    => $consultation->packages()->sync($syncValues),
                'examens'     => $consultation->tests()->sync($syncValues),
            };
        }
    }

    public function calculateAmountAndReturnItems($consultationItem, bool $isConsultation = true): array
    {
        $items = [];
        $totalAmount = 0;
        $consultation = null;
        $transaction = null;

        if ($isConsultation) {
            $consultation = $consultationItem;
            $transaction = $consultationItem->transaction;
        } else {
            $consultation = $consultationItem->transactionable;
            $transaction = $consultationItem;
        }

        $insuranceId = $consultation->patient->activeInsurances()?->first()?->insurance_company_id;

        foreach ($consultation->services ?? [] as $service) {
            $amount = $this->getAmount(TypesFacturables::alias(\App\Models\Service::class), $service->id, $insuranceId, $service);

            $items[] = [
                'acte_type'   => TypesFacturables::alias(\App\Models\Service::class),
                'acte_id'     => $service->id,
                'description' => $service->name,
                'unit_price'  => $amount,
                'quantity'    => 1,
                'total'       => $amount,
            ];

            $totalAmount += $amount;
        }

        foreach ($consultation->packages ?? [] as $package) {
            $amount = $this->getAmount(TypesFacturables::alias(\App\Models\Package::class), $package->id, $insuranceId, $package);

            $items[] = [
                'acte_type'   => TypesFacturables::alias(\App\Models\Package::class),
                'acte_id'     => $package->id,
                'description' => $package->name,
                'unit_price'  => $amount,
                'quantity'    => 1,
                'total'       => $amount,
            ];

            $totalAmount += $amount;
        }

        foreach ($consultation->tests ?? [] as $test) {
            $amount = $this->getAmount(TypesFacturables::alias(\App\Models\Test::class), $test->id, $insuranceId, $test);

            $items[] = [
                'acte_type'   => TypesFacturables::alias(\App\Models\Test::class),
                'acte_id'     => $test->id,
                'description' => $test->name,
                'unit_price'  => $amount,
                'quantity'    => 1,
                'total'       => $amount,
            ];

            $totalAmount += $amount;
        }

        foreach ($consultation->medicaments ?? [] as $medicament) {
            $amount = $this->getAmount(TypesFacturables::alias(\App\Models\Medicament::class), $medicament->id, $insuranceId, $medicament);

            $items[] = [
                'acte_type'   => TypesFacturables::alias(\App\Models\Medicament::class),
                'acte_id'     => $medicament->id,
                'description' => $medicament->nom,
                'unit_price'  => $amount,
                'quantity'    => $medicament->pivot->quantity,
                'total'       => $amount * $medicament->pivot->quantity,
            ];

            $totalAmount += $amount * $medicament->pivot->quantity;
        }

        if (TypesFacturables::est($transaction?->transactionable_type, \App\Models\Hospitalisation::class)) {
            $hospitalisations = [$transaction->transactionable];

            foreach ($hospitalisations as $hospitalisation) {
                $items[] = [
                    'acte_type'   => TypesFacturables::alias(\App\Models\Chambre::class),
                    'acte_id'     => $hospitalisation->chambre_id,
                    'description' => $hospitalisation->date_entree . " au " . $hospitalisation->date_sortie_effective,
                    'unit_price'  => $this->getAmount(TypesFacturables::alias(\App\Models\Chambre::class), $hospitalisation->chambre_id, $insuranceId, $hospitalisation),
                    'quantity'    => $hospitalisation->nombre_jours,
                    'total'       => $this->getAmountHospitalisation(TypesFacturables::alias(\App\Models\Chambre::class), $hospitalisation->chambre_id, $insuranceId, $hospitalisation),
                ];

                $totalAmount += $hospitalisation->total_payer;
            }
        }

        return [
            'items' => $items,
            'total_amount' => $totalAmount,
        ];
    }

    /**
     * « Prochain rendez-vous » saisi par le médecin en fin de consultation.
     *
     * CORRIGÉ 21/09/2026 — passe par AppointmentBookingService : refus de
     * tout chevauchement avec un rdv actif, établissement renseigné, durée
     * enregistrée, SMS de confirmation envoyé. L'ancien code créait le rdv
     * sans aucune vérification (double réservation possible), avec un champ
     * `reason` qui n'existe plus, et ne prévenait pas le patient.
     *
     * Ne lève PAS d'exception : un conflit d'agenda ne doit jamais faire
     * perdre la consultation elle-même (le contrôleur est dans une
     * transaction). Retourne un avertissement à afficher, ou null.
     *
     * Le choix du motif (et donc d'une durée réaliste) sera ajouté avec le
     * chantier consultation — durée par défaut en attendant.
     */
    public function createNextAppointment(Consultation $consultation, string $prochainRdv): ?string
    {
        try {
            app(AppointmentBookingService::class)->planifierParMedecin(
                (int) $consultation->medecin_id,
                (int) $consultation->patient_id,
                Carbon::parse($prochainRdv),
                DisponibiliteService::DUREE_PAR_DEFAUT,
                'Prochain rendez-vous fixé en consultation'
            );

            return null;
        } catch (DomainException $e) {
            return 'Prochain rendez-vous NON créé : ' . $e->getMessage();
        }
    }

    public function updateNextAppointment(Consultation $consultation, ?string $nouveauProchainRdv): ?string
    {
        // Garde-fous liés au formulaire d'édition actuel (repris au chantier
        // consultation) : sans prochain rdv initial, le champ caché contient
        // la date du jour sans heure. On ne crée rien dans ce cas, comme avant.
        if (! $nouveauProchainRdv || ! $consultation->prochain_rdv || strlen(trim($nouveauProchainRdv)) <= 10) {
            return null;
        }

        $nouveau = Carbon::parse($nouveauProchainRdv);

        $ancien = Carbon::parse($consultation->prochain_rdv);

        if ($ancien->equalTo($nouveau)) {
            return null;
        }

        $appointment = Appointment::where('employee_id', $consultation->medecin_id)
            ->where('patient_id', $consultation->patient_id)
            ->where('appointment_date', $ancien->toDateString())
            ->where('appointment_time', $ancien->format('H:i:s'))
            ->whereIn('status', ['pending', 'confirmed'])
            ->first();

        if (! $appointment) {
            // Le rdv d'origine a été annulé ou déplacé entre-temps : on en crée un nouveau.
            return $this->createNextAppointment($consultation, $nouveauProchainRdv);
        }

        try {
            app(AppointmentBookingService::class)->deplacerParMedecin($appointment, $nouveau);

            return null;
        } catch (DomainException $e) {
            return 'Prochain rendez-vous NON déplacé : ' . $e->getMessage();
        }
    }

    public function getActes($transaction): array
    {
        $actes = [];
        $consultation = $transaction->transactionable;
        $insuranceId = $transaction->patient->activeInsurances()?->first()?->insurance_company_id;

        foreach ($consultation->services ?? [] as $item) {
            $actes[] = [
                'id' => $item->id,
                'type' => 'Service',
                'nom' => $item->name,
                'description' => $item->name,
                'prix_unitaire' => $this->getAmount(TypesFacturables::alias(\App\Models\Service::class), $item->id, $insuranceId, $item),
                'quantite' => 1,
                'created_at' => $item->created_at,
            ];
        }

        foreach ($consultation->packages ?? [] as $item) {
            $actes[] = [
                'id' => $item->id,
                'type' => 'Package',
                'nom' => $item->name,
                'description' => $item->name,
                'prix_unitaire' => $this->getAmount(TypesFacturables::alias(\App\Models\Package::class), $item->id, $insuranceId, $item),
                'quantite' => 1,
                'created_at' => $item->created_at,
            ];
        }

        foreach ($consultation->tests ?? [] as $item) {
            $actes[] = [
                'id' => $item->id,
                'type' => 'Test',
                'nom' => $item->name,
                'description' => $item->name,
                'prix_unitaire' => $this->getAmount(TypesFacturables::alias(\App\Models\Test::class), $item->id, $insuranceId, $item),
                'quantite' => 1,
                'created_at' => $item->created_at,
            ];
        }

        foreach ($consultation->medicaments ?? [] as $item) {
            $actes[] = [
                'id' => $item->id,
                'type' => 'Medicament',
                'nom' => $item->nom,
                'description' => $item->nom,
                'prix_unitaire' => $this->getAmount(TypesFacturables::alias(\App\Models\Medicament::class), $item->id, $insuranceId, $item),
                'quantite' => $item->pivot->quantity,
                'created_at' => $item->created_at,
            ];
        }

        if (TypesFacturables::est($transaction->transactionable_type, \App\Models\Hospitalisation::class)) {

            $hospitalisations = [$transaction->transactionable];

            foreach ($hospitalisations as $hospitalisation) {
                $actes[] = [
                    'id' => $hospitalisation->chambre_id,
                    'type' => 'Hospitalisation',
                    'nom' => 'Hospitalisation',
                    'description' => $hospitalisation->date_entree . " au " . $hospitalisation->date_sortie_effective,
                    'prix_unitaire' => $this->getAmount(TypesFacturables::alias(\App\Models\Chambre::class), $hospitalisation->chambre_id, $insuranceId, $hospitalisation),
                    'quantite' => $hospitalisation->nombre_jours,
                    'total' => $this->getAmountHospitalisation(TypesFacturables::alias(\App\Models\Chambre::class), $hospitalisation->chambre_id, $insuranceId, $hospitalisation),
                    'created_at' => $hospitalisation->created_at,
                ];
            }
        }

        return $actes;
    }

    public function getAmount($serviceType, $serviceId, $insuranceId, $acte): float
    {
        $item = $this->getCoverageItem($serviceType, $serviceId, $insuranceId);

        return !empty($item)
            ? (float) $item->acte_price
            : (float) ($acte?->amount ?? $acte?->price ?? $acte->chambre->prix_par_jour);
    }

    private function getAmountHospitalisation($serviceType, $serviceId, $insuranceId, $acte): float
    {
        $item = $this->getCoverageItem($serviceType, $serviceId, $insuranceId);

        return !empty($item)
            ? (float) ($item->acte_price * $acte->nombre_jours)
            : (float) $acte->total_payer;
    }

    private function getCoverageItem($serviceType, $serviceId, $insuranceId)
    {
        if (!$insuranceId) {
            return null;
        }

        return InsuranceCoverage::where('insurance_company_id', $insuranceId)
            ->pourActe((string) $serviceType, (int) $serviceId)
            ->enVigueur()
            ->first();
    }
}