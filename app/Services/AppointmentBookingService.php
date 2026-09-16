<?php

namespace App\Services;

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

            $medecin = Employee::findOrFail($data['employee_id']);
            $motif = MotifRdv::findOrFail($data['motif_rdv_id']);
            $date = Carbon::parse($data['appointment_date']);
            $heureDemandee = $data['appointment_time']; // 'H:i'

            // Verrouille les rendez-vous existants de ce médecin sur cette
            // date : c'est ce qui remplace le verrou sur une ligne
            // `appointment_slots` de l'ancien système, pour sérialiser deux
            // réservations concurrentes sur le même horaire.
            Appointment::where('employee_id', $medecin->id)
                ->whereDate('appointment_date', $date->toDateString())
                ->lockForUpdate()
                ->get();

            if (! $medecin->peutPratiquerMotif($motif)) {
                throw new DomainException('Ce médecin ne prend pas ce type de rendez-vous.');
            }

            // Revérification serveur : ne JAMAIS faire confiance au créneau
            // choisi côté client. On recalcule les disponibilités réelles
            // et on vérifie que l'horaire demandé en fait toujours partie
            // — un autre patient a pu réserver entre-temps.
            $creneauValide = $this->disponibilite
                ->creneauxDisponibles($medecin, $date, $motif)
                ->contains(fn ($c) => $c['debut']->format('H:i') === $heureDemandee);

            if (! $creneauValide) {
                throw new DomainException('Ce créneau n’est plus disponible. Merci d’en choisir un autre.');
            }

            $dejaPris = Appointment::where('patient_id', $data['patient_id'])
                ->where('employee_id', $medecin->id)
                ->whereDate('appointment_date', $date->toDateString())
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
     * NOUVEAU — déplace un rendez-vous existant vers une nouvelle date/heure,
     * même médecin et même motif (donc même durée). Changer le médecin ou
     * le motif reste volontairement un "annuler puis reprendre" : ça
     * changerait la durée réelle occupée, ce qui mérite de repasser par la
     * validation complète de book() plutôt qu'une reprogrammation qui
     * ferait semblant que rien d'autre n'a changé.
     */
    public function reprogrammer(Appointment $appointment, string $nouvelleDate, string $nouvelleHeure): Appointment
    {
        return DB::transaction(function () use ($appointment, $nouvelleDate, $nouvelleHeure) {

            if (! $appointment->canBeCancelled()) {
                throw new DomainException('Ce rendez-vous est trop proche (moins de 2h) ou déjà passé pour être reprogrammé.');
            }

            $medecin = $appointment->employee;
            $motif = $appointment->motifRdv;
            $date = Carbon::parse($nouvelleDate);

            Appointment::where('employee_id', $medecin->id)
                ->whereDate('appointment_date', $date->toDateString())
                ->lockForUpdate()
                ->get();

            // Même revérification serveur que pour une création — et on
            // s'exclut soi-même du calcul (voir DisponibiliteService),
            // sinon l'horaire d'origine du rdv se bloquerait lui-même.
            $creneauValide = $this->disponibilite
                ->creneauxDisponibles($medecin, $date, $motif, null, $appointment->id)
                ->contains(fn ($c) => $c['debut']->format('H:i') === $nouvelleHeure);

            if (! $creneauValide) {
                throw new DomainException('Ce nouveau créneau n’est plus disponible. Merci d’en choisir un autre.');
            }

            $appointment->update([
                'appointment_date' => $date->toDateString(),
                'appointment_time' => $nouvelleHeure . ':00',
                // Le patient n'a pas reconfirmé ce nouvel horaire — on
                // repasse en attente plutôt que de garder "confirmé" sur
                // un rdv dont l'heure vient de changer sous ses pieds.
                'status' => 'pending',
                'patient_confirmed' => false,
                'confirmed_at' => null,
            ]);

            return $appointment->fresh(['employee', 'patient', 'motifRdv']);
        });
    }
}
