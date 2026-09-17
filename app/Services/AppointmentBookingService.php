<?php

namespace App\Services;

use App\Jobs\SendAppointmentReminderJob;
use App\Models\Appointment;
use App\Models\Employee;
use App\Models\MotifRdv;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

class AppointmentBookingService
{
    public function __construct(protected DisponibiliteService $disponibilite)
    {
    }

    public function book(array $data): Appointment
    {
        return DB::transaction(function () use ($data) {

            $medecin = $this->verrouillerMedecin((int) $data['employee_id']);
            $motif = MotifRdv::findOrFail($data['motif_rdv_id']);
            $date = Carbon::parse($data['appointment_date']);
            $heureDemandee = $data['appointment_time']; // 'H:i'

            if (! $medecin->peutPratiquerMotif($motif)) {
                throw new DomainException('Ce médecin ne prend pas ce type de rendez-vous.');
            }

            // Revérification serveur : ne JAMAIS faire confiance au créneau
            // choisi côté client — un autre patient a pu réserver entre-temps.
            $creneauValide = $this->disponibilite
                ->creneauxDisponibles($medecin, $date, $motif)
                ->contains(fn ($c) => $c['debut']->format('H:i') === $heureDemandee);

            if (! $creneauValide) {
                throw new DomainException('Ce créneau n’est plus disponible. Merci d’en choisir un autre.');
            }

            $dejaPris = Appointment::where('patient_id', $data['patient_id'])
                ->where('employee_id', $medecin->id)
                ->where('appointment_date', $date->toDateString())
                ->whereIn('status', ['pending', 'confirmed'])
                ->exists();

            if ($dejaPris) {
                throw new DomainException('Ce patient a déjà un rendez-vous actif avec ce médecin à cette date.');
            }

            $enAttenteCount = Appointment::where('patient_id', $data['patient_id'])
                ->where('status', 'pending')
                ->count();

            if ($enAttenteCount >= 5) {
                throw new DomainException('Ce patient a trop de rendez-vous en attente. Merci de finaliser ou annuler les rendez-vous existants.');
            }

            $appointment = Appointment::create([
                'employee_id' => $medecin->id,
                'patient_id' => $data['patient_id'],
                'motif_rdv_id' => $motif->id,
                'duree_minutes' => $motif->dureePour($medecin),
                'appointment_date' => $date->toDateString(),
                'appointment_time' => $heureDemandee . ':00',
                'description' => $data['description'] ?? null,
                'status' => 'pending',
            ]);

            return $appointment->fresh(['employee', 'patient', 'motifRdv']);
        });
    }

    /**
     * Déplace un rendez-vous (même médecin, même motif, donc même durée).
     *
     * CORRIGÉ 21/09/2026 :
     *  - les indicateurs de rappels sont remis à zéro : sinon les rappels 24h
     *    et 2h ne repartaient jamais pour le nouvel horaire ;
     *  - le patient est prévenu par SMS (avec lien d'annulation) ;
     *  - le statut est conservé : un rdv confirmé déplacé en accord avec le
     *    patient (cas normal, au téléphone ou au guichet) reste confirmé.
     *    L'ancien passage forcé en « pending » ne menait nulle part, aucun
     *    parcours ne redemandant la confirmation.
     *
     * Modification en place volontaire (plutôt qu'un nouveau rdv chaîné par
     * reprogramme_depuis_id) : le lien d'annulation déjà reçu par SMS reste
     * valable. La colonne reprogramme_depuis_id n'est donc pas utilisée.
     */
    public function reprogrammer(Appointment $appointment, string $nouvelleDate, string $nouvelleHeure): Appointment
    {
        $appointment = DB::transaction(function () use ($appointment, $nouvelleDate, $nouvelleHeure) {

            if (! $appointment->canBeCancelled()) {
                throw new DomainException('Ce rendez-vous est trop proche (moins de 2h) ou déjà passé pour être reprogrammé.');
            }

            $medecin = $this->verrouillerMedecin($appointment->employee_id);
            $motif = $appointment->motifRdv;
            $date = Carbon::parse($nouvelleDate);

            if (! $motif) {
                throw new DomainException('Ce rendez-vous n’a pas de motif : annulez-le puis reprenez un rendez-vous.');
            }

            $creneauValide = $this->disponibilite
                ->creneauxDisponibles($medecin, $date, $motif, null, $appointment->id)
                ->contains(fn ($c) => $c['debut']->format('H:i') === $nouvelleHeure);

            if (! $creneauValide) {
                throw new DomainException('Ce nouveau créneau n’est plus disponible. Merci d’en choisir un autre.');
            }

            $appointment->update([
                'appointment_date' => $date->toDateString(),
                'appointment_time' => $nouvelleHeure . ':00',
                ...$appointment->attributsReinitialisationRappels(),
            ]);

            return $appointment->fresh(['employee', 'patient', 'motifRdv']);
        });

        SendAppointmentReminderJob::dispatch($appointment, 'rescheduling')->afterCommit();

        return $appointment;
    }

    /**
     * Rendez-vous fixé PAR LE MÉDECIN lui-même (ex. « prochain rdv » saisi en
     * fin de consultation). Le médecin peut sortir de sa grille horaire —
     * c'est sa décision — mais on refuse tout chevauchement avec un rdv actif
     * et on passe par le cycle normal (confirmation → SMS).
     *
     * Remplace l'ancien Appointment::create() de ConsultationService, qui ne
     * vérifiait rien (double réservation possible), n'avait ni motif ni
     * durée et n'envoyait aucun SMS.
     */
    public function planifierParMedecin(int $employeeId, int $patientId, Carbon $debut, int $dureeMinutes = DisponibiliteService::DUREE_PAR_DEFAUT, ?string $description = null, ?int $motifRdvId = null): Appointment
    {
        if ($debut->lt(now())) {
            throw new DomainException('La date du prochain rendez-vous est déjà passée.');
        }

        $appointment = DB::transaction(function () use ($employeeId, $patientId, $debut, $dureeMinutes, $description, $motifRdvId) {
            $medecin = $this->verrouillerMedecin($employeeId);

            $this->refuserChevauchement($medecin, $debut, $dureeMinutes);

            return Appointment::create([
                'employee_id' => $medecin->id,
                'patient_id' => $patientId,
                'motif_rdv_id' => $motifRdvId,
                'duree_minutes' => $dureeMinutes,
                'appointment_date' => $debut->toDateString(),
                'appointment_time' => $debut->format('H:i:s'),
                'description' => $description,
                'status' => 'pending',
            ]);
        });

        // Passage pending → confirmed : déclenche le SMS de confirmation
        // (AppointmentObserver → HandleAppointmentStatusChange).
        return app(AppointmentStatusService::class)->confirm($appointment);
    }

    /**
     * Déplacement d'un rdv fixé par le médecin (hors grille). Même règle de
     * non-chevauchement, rappels réinitialisés, patient prévenu.
     */
    public function deplacerParMedecin(Appointment $appointment, Carbon $debut): Appointment
    {
        if ($debut->lt(now())) {
            throw new DomainException('La nouvelle date du rendez-vous est déjà passée.');
        }

        $appointment = DB::transaction(function () use ($appointment, $debut) {
            $medecin = $this->verrouillerMedecin($appointment->employee_id);
            $duree = $appointment->duree_minutes ?: DisponibiliteService::DUREE_PAR_DEFAUT;

            $this->refuserChevauchement($medecin, $debut, $duree, $appointment->id);

            $appointment->update([
                'appointment_date' => $debut->toDateString(),
                'appointment_time' => $debut->format('H:i:s'),
                ...$appointment->attributsReinitialisationRappels(),
            ]);

            return $appointment->fresh(['employee', 'patient']);
        });

        SendAppointmentReminderJob::dispatch($appointment, 'rescheduling')->afterCommit();

        return $appointment;
    }

    /**
     * Verrou de sérialisation : la ligne du MÉDECIN, pas ses rendez-vous.
     *
     * L'ancien verrou (lockForUpdate sur les rdv du jour) ne verrouillait
     * rien quand le médecin n'avait encore aucun rdv ce jour-là : deux
     * réservations simultanées finissaient en deadlock InnoDB (erreur 500).
     * Et whereDate() empêchant l'usage d'index, il verrouillait tout
     * l'historique du médecin. Une ligne unique, toujours présente, sert de
     * mutex propre et bon marché.
     */
    protected function verrouillerMedecin(int $employeeId): Employee
    {
        return Employee::whereKey($employeeId)->lockForUpdate()->firstOrFail();
    }

    protected function refuserChevauchement(Employee $medecin, Carbon $debut, int $dureeMinutes, ?int $exclureRdvId = null): void
    {
        $fin = $debut->copy()->addMinutes($dureeMinutes);

        $conflit = Appointment::where('employee_id', $medecin->id)
            ->where('appointment_date', $debut->toDateString())
            ->whereIn('status', DisponibiliteService::STATUTS_OCCUPANTS)
            ->when($exclureRdvId, fn ($q) => $q->where('id', '!=', $exclureRdvId))
            ->get()
            ->first(function (Appointment $rdv) use ($debut, $fin) {
                $debutRdv = $debut->copy()->setTimeFromTimeString($rdv->appointment_time->format('H:i:s'));
                $finRdv = $debutRdv->copy()->addMinutes($rdv->duree_minutes ?: DisponibiliteService::DUREE_PAR_DEFAUT);

                return $debutRdv->lt($fin) && $finRdv->gt($debut);
            });

        if ($conflit) {
            throw new DomainException(sprintf(
                'Le Dr %s a déjà un rendez-vous à %s ce jour-là.',
                $medecin->full_name,
                $conflit->appointment_time->format('H:i')
            ));
        }
    }
}
